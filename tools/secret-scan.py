#!/usr/bin/env python
"""secret-scan — refuse to commit credential material. Canonical, fleet-wide.

MERGED 2026-08-12 from two independent implementations that each caught what the other
missed. Keeping both would have been a parallel-implementation problem; picking one would
have thrown away real coverage.

  * OD9-Discord-Bot/scripts/scan_secrets.py — NAME-based. Flags `SOMETHING_PASSWORD =
    "literal"` regardless of the value's shape, which is the only way to catch a
    credential with no recognisable format (the old offda9 DB password, which reached
    config + two skills + git history). Brought over whole: php define()/putenv(), .env
    and shell assignment, generic code assignment, the mysql `-p<pass>` inline flag,
    placeholder/`.example`/filesystem-path exemptions, and value redaction.

  * claude-skills/tools/secret-scan.py — VALUE-based. Flags a token by its provider
    format even when the variable is innocently named. Brought over whole: provider
    patterns, the public-identifier distinction, `--staged` blob reading, and — the part
    neither had — a SELFTEST that runs before every scan and FAILS CLOSED.

Name-based alone misses `foo = "sq0csp-..."`. Value-based alone misses `DB_PASSWORD =
"hunter2islong"`. This does both.

WHAT IT WILL NOT DO
-------------------
Print a secret. Values are redacted to `ab***(len 24)`. A gate that echoes what it caught
turns a near-miss into a leak in terminal scrollback and CI logs.

PUBLIC vs SECRET
----------------
Square's application id is the OAuth `client_id`, published in every authorize URL a
merchant sees. Reported, never blocking. A gate that cries wolf gets bypassed, and then
it is not a gate.

FAILS CLOSED
------------
Any scan runs the selftest first and refuses to vouch for a commit if the detector is
broken. "Found nothing" and "cannot find anything" are indistinguishable from the outside,
and that indistinguishability is the failure mode this fleet keeps re-learning.

Usage:
  secret-scan.py --selftest              prove the detector works  (exit 0 = healthy)
  secret-scan.py --staged                scan staged blobs         (exit 1 = secrets)
  printf '%s\\n' a b | secret-scan.py --stdin
  secret-scan.py --path <dir>            walk a tree on disk
  secret-scan.py <file>...  [--working]  named files; --working reads disk not the index
"""
from __future__ import annotations

import os
import re
import subprocess
import sys

for _s in (sys.stdout, sys.stderr):
    if hasattr(_s, "reconfigure"):
        try:
            _s.reconfigure(encoding="utf-8", errors="replace")
        except Exception:
            pass

# --- name-based (from OD9) ------------------------------------------------------------
SECRET_NAME = (r"[A-Z0-9_]*(?:PASSWORD|PASSWD|PASS|PWD|SECRET|TOKEN|API_?KEY|ACCESS_?KEY"
               r"|CREDENTIAL|PRIVATE_?KEY|AUTH_?KEY)[A-Z0-9_]*")

# Template files are placeholders by definition.
ALLOW_FILE = re.compile(r"(?:\.example\b|\.sample\b|\.dist\b|\.template\b|[._-]example\.)", re.I)

PLACEHOLDER = re.compile(
    r"(?:^$|^\$|^`|your[_-]|^my[_-]|generate|match[_-]the|change[_-]?me|example|placeholder"
    r"|replace|todo|^xxx|dummy|sample|fake|^none$|^null$|getenv|\$_ENV|\$_SERVER|\$GLOBALS"
    r"|\$\{|\$\(|process\.env|os\.environ|<.+>|\.\.\.|\{|^test|mock|abc123|deadbeef|123456"
    # `^test` is anchored, so it missed sq0csp-test-secret in SquareOAuthTest.php — a
    # fixture the gate would have blocked every commit to. Match test/fake markers
    # anywhere in the value, which is where they actually appear in seeded credentials.
    r"|[-_]test[-_]|test[-_]secret|[-_]fake[-_]|not[-_]a[-_]real)", re.I)

# Fixture data. A bcrypt hash is a ONE-WAY hash, not a usable credential, and seed files
# carry them by convention (database/seed.sql documents its own password as admin123 on
# the line above). Blocking every repo that ships seed data would get this gate switched
# off within a week — and the hash of a throwaway dev password is not what it exists to
# catch. A hash in ordinary application code still blocks; only fixture paths are exempt.
FIXTURE_PATH = re.compile(
    r"(?:^|/)(?:tests?|fixtures?|seeds?|__tests__)/|(?:^|/)seed[^/]*\.sql$|snapshot[^/]*\.json$", re.I)
HASH_ONLY = {"bcrypt hash"}

# Names that IDENTIFY a credential rather than hold one: TOKEN_NAME = "csrf_token" is a
# field name, SESSION_COOKIE_NAME is a cookie key. Real in core/CSRF.php, and exactly the
# kind of everyday constant that would make developers stop reading this gate's output.
NAMES_A_THING = re.compile(r"(?:_NAME|_FIELD|_HEADER|_PARAM|_COOKIE|_PREFIX|_LABEL)$", re.I)

# Lint-suppression markers. Four skills in this very repo declare
# IGNORE_TOKEN = "brand-lint:ignore" — the NAME matches SECRET_NAME (…TOKEN…) so the
# name-based rule fired on all of them. A convention this fleet uses everywhere must not
# read as a credential, or the gate gets switched off within a week.
LINT_MARKER = re.compile(r"^[a-z0-9][a-z0-9._-]*:[a-z0-9][a-z0-9._-]*$", re.I)

# A filesystem path is not a secret — e.g. PRIVATE_KEY_PATH = "~/.ssh/id_rsa".
IS_PATH = re.compile(
    r"^[a-zA-Z]:[\\/]|^[/~]|[\\/].+[\\/]|\.(?:pem|key|crt|p12|pfx|exe|sh|py|php|json|ini|cnf)$", re.I)

PHP_RULES = [
    ("php define", re.compile(r"""define\s*\(\s*['"](%s)['"]\s*,\s*['"]([^'"]*)['"]""" % SECRET_NAME)),
    ("php putenv", re.compile(r"""putenv\s*\(\s*['"](%s)=([^'"]*)['"]""" % SECRET_NAME)),
]
ENV_RULE = ("env assign", re.compile(
    r"""^\s*(?:export\s+)?(%s)\s*=\s*['"]?([^\s'"#]+)['"]?""" % SECRET_NAME))
CODE_RULE = ("code assign", re.compile(r"""(%s)\s*[=:]\s*['"]([^'"\n]{8,})['"]""" % SECRET_NAME))
MYSQL_LINE = re.compile(r"\b(?:mysql|mariadb|mysqldump|mariadb-dump)\b", re.I)
# Space before -p, so `--platform` and `-platform` do not read as a password flag.
MYSQL_PFLAG = re.compile(r"(?:^|\s)-p['\"]?([^\s'\"]{8,})")

# --- value-based -----------------------------------------------------------------------
# Tails are length-bounded so a bare prefix in prose ("a sq0csp- secret, 50 chars") does
# NOT match. Without the bound the gate fires on its own documentation.
UNIVERSAL = [
    ("square production app secret", re.compile(r"\bsq0csp-[A-Za-z0-9_\-]{20,}")),
    ("square sandbox app secret",    re.compile(r"\bsq0csb-[A-Za-z0-9_\-]{20,}")),
    ("square access token",          re.compile(r"\bEAAA[A-Za-z0-9_\-]{25,}")),
    ("AWS access key id",            re.compile(r"\bAKIA[0-9A-Z]{16}\b")),
    ("PEM private key",              re.compile(r"-----BEGIN (?:[A-Z ]+ )?PRIVATE KEY-----")),
    ("Stripe key",                   re.compile(r"\b[rs]k_(?:live|test)_[0-9a-zA-Z]{16,}\b")),
    ("GitHub token",                 re.compile(r"\bgh[posru]_[0-9A-Za-z]{30,}\b")),
    ("Slack token",                  re.compile(r"\bxox[baprs]-[0-9A-Za-z-]{10,}\b")),
    ("Google API key",               re.compile(r"\bAIza[0-9A-Za-z_\-]{35}\b")),
    ("openai/anthropic key",         re.compile(r"\bsk-[A-Za-z0-9_\-]{30,}")),
    ("bcrypt hash",                  re.compile(r"\$2[aby]\$\d{2}\$[./A-Za-z0-9]{53}")),
    ("bitwarden session",            re.compile(r"BW_SESSION\s*=\s*[\"']?[A-Za-z0-9+/=]{40,}")),
]

PUBLIC = [
    ("square application id (public client_id)", re.compile(r"\bsq0idp-[A-Za-z0-9_\-]{18,}")),
    ("square sandbox app id (public)",           re.compile(r"\bsandbox-sq0idb-[A-Za-z0-9_\-]{18,}")),
]

# A generic "long high-entropy string" rule was tried here and REMOVED 2026-08-12.
# Measured on real trees it fired almost exclusively on composer.lock hashes and minified
# JS — standard artifacts, on every commit. By this file's own standard ("a gate that
# cries wolf gets bypassed, and then it is not a gate") that rule costs more than it
# catches: anything it would have found with a recognisable format is already covered by
# UNIVERSAL, and anything without one is covered by the NAME-based rules. Neither source
# scanner had it. Do not reintroduce it without a precision measurement on these repos.


def redact(v: str) -> str:
    v = v.strip()
    return "*" * len(v) if len(v) <= 3 else f"{v[:2]}***(len {len(v)})"


def scan(path: str, text: str) -> tuple[list[str], list[str]]:
    """Return (secret findings, public-identifier notes). Values always redacted."""
    if ALLOW_FILE.search(path):
        return [], []
    secrets: list[str] = []
    publics: list[str] = []
    is_php = path.lower().endswith(".php")
    is_env = bool(re.search(r"(?:^|/)\.env|\.(?:env|sh|bash|ini|cfg|conf|config)$", path, re.I))

    for i, line in enumerate(text.splitlines(), 1):
        rules = list(PHP_RULES) if is_php else []
        if is_env:
            rules.append(ENV_RULE)
        rules.append(CODE_RULE)
        seen = set()
        for label, rx in rules:
            for m in rx.finditer(line):
                name, val = m.group(1), m.group(2)
                if not val or PLACEHOLDER.search(val) or LINT_MARKER.match(val):
                    continue
                if NAMES_A_THING.search(name):
                    continue
                if label == "code assign" and IS_PATH.search(val):
                    continue
                if (name, val) in seen:
                    continue
                seen.add((name, val))
                secrets.append(f"{path}:{i}: {label} {name} = {redact(val)}")

        if MYSQL_LINE.search(line):
            for m in MYSQL_PFLAG.finditer(line):
                val = m.group(1)
                if not PLACEHOLDER.search(val):
                    secrets.append(f"{path}:{i}: mysql -p flag = {redact(val)}")

        for label, rx in UNIVERSAL:
            if rx.search(line):
                if label in HASH_ONLY and FIXTURE_PATH.search(path):
                    continue
                secrets.append(f"{path}:{i}: {label} (redacted)")
        for label, rx in PUBLIC:
            if rx.search(line):
                publics.append(f"{path}:{i}: {label}")
    return secrets, publics


# --- selftest ---------------------------------------------------------------------------
# Positives are ASSEMBLED AT RUNTIME from harmless fragments so no complete credential
# literal exists in this file. Written the obvious way, the scanner flags its own fixtures
# and refuses every commit that touches it — and the fix is NOT a path exemption, which
# would be the one place in the repo a real secret could hide from the gate.
_J = "".join
FIXTURES = [
    # (name, path, body, expect_hit)
    ("provider token",   "a.py",   "client_secret = '" + _J(["sq0", "csp-"]) + "A1b2C3d4E5f6G7h8I9j0K1l2M3n4'", True),
    ("github token",     "a.sh",   "GH=" + _J(["gh", "p_"]) + "b" * 36, True),
    ("aws key",          "a.txt",  _J(["AK", "IA"]) + "IOSFODNN7EXAMPLE", True),
    ("pem key",          "a.txt",  "-----BEGIN " + _J(["OPENSSH PRIVATE", " KEY"]) + "-----", True),
    # Name-based positives are assembled too — a plain literal here makes the scanner
    # flag its own fixtures and refuse every commit that touches this file.
    ("named password",   "a.py",   _J(["DB_PASS", "WORD"]) + ' = "' + _J(["hunter2", "islongenough"]) + '"', True),
    ("php define",       "a.php",  "define('" + _J(["DB_", "PASS"]) + "', '" + _J(["realvalue", "12345"]) + "');", True),
    ("php putenv",       "a.php",  "putenv('" + _J(["API_", "TOKEN"]) + "=" + _J(["realvalue", "12345"]) + "');", True),
    ("env assign",       ".env",   _J(["DB_PASS", "WORD"]) + "=" + _J(["realvalue", "12345"]), True),
    ("mysql -p flag",    "a.sh",   "mysql -u root -p" + _J(["hunter2", "islong"]) + " db", True),
    # negatives — each is a real shape from this fleet that must NOT block a commit
    ("prose mention",    "a.md",   "It holds a `sq0csp-` production secret, 50 chars, sha 6962ea07.", False),
    ("placeholder",      "a.py",   'password = "your-password-here"', False),
    ("lint marker",      "a.py",   'IGNORE_TOKEN = "brand-lint:ignore"', False),
    ("names a thing",    "a.php",  """define('TOKEN_NAME', 'csrf_token');""", False),
    ("test fixture val", "a.php",  "putenv('" + _J(["SQUARE_APP", "_SECRET"]) + "=sq0csp-test-secret');", False),
    ("bcrypt in seed",   "database/seed.sql", _J(["$2y", "$10$"]) + "a" * 53, False),
    # ...but the SAME hash in ordinary application code must still block.
    ("bcrypt in app",    "core/Auth.php",     _J(["$2y", "$10$"]) + "a" * 53, True),
    ("env var deref",    "a.py",   'password = "$BW_PASSWORD"', False),
    ("getenv ref",       "a.php",  """define('DB_PASS', getenv('DB_PASS'));""", False),
    ("key PATH not key", "a.py",   'PRIVATE_KEY_PATH = "~/.ssh/id_rsa_automation"', False),
    ("example file",     ".env.example", "DB_PASSWORD=realvalue12345", False),
    ("mysql --platform", "a.sh",   "docker run --platform linux/amd64 mysql:8", False),
    ("public clientid",  "a.py",   "client_id = 'sq0idp-HJIx7E5OcNjh9Fy22Y3VSg'", False),
    ("plain code",       "a.py",   "def deploy(host, key):\n    return run(['ssh', host])", False),
]


def selftest(verbose: bool = True) -> bool:
    ok = True
    for name, path, body, want in FIXTURES:
        hits, _ = scan(path, body)
        got = bool(hits)
        good = got == want
        ok &= good
        if verbose:
            print(f"    {'OK  ' if good else 'FAIL'} {name:18} expect={'hit' if want else 'clean':5} "
                  f"got={'hit' if got else 'clean'}" + (f"   [{hits[0]}]" if hits and not good else ""))
    return ok


# --- I/O --------------------------------------------------------------------------------
def staged_paths() -> list[str]:
    r = subprocess.run(["git", "diff", "--cached", "--name-only", "--diff-filter=ACM"],
                       capture_output=True, text=True, encoding="utf-8", errors="replace")
    return [p for p in r.stdout.splitlines() if p.strip()]


def read_text(path: str, use_working: bool) -> str | None:
    if not use_working:
        try:
            r = subprocess.run(["git", "show", f":{path}"], capture_output=True)
            if r.returncode == 0:
                if b"\0" in r.stdout[:8000]:
                    return None                      # binary
                return r.stdout.decode("utf-8", "replace")
        except Exception:
            pass
    try:
        with open(path, "rb") as fh:
            raw = fh.read()
        if b"\0" in raw[:8000]:
            return None
        return raw.decode("utf-8", "replace")
    except Exception:
        return None


def main() -> int:
    argv = sys.argv[1:]
    if "--selftest" in argv:
        print("  secret-scan selftest:")
        ok = selftest()
        print("  " + ("healthy" if ok else "BROKEN"))
        return 0 if ok else 2

    if not selftest(verbose=False):
        print("  secret-scan: DETECTOR SELFTEST FAILED — refusing to vouch for this commit.")
        print("  Run: python tools/secret-scan.py --selftest")
        return 2

    working = "--working" in argv
    named = [a for a in argv if not a.startswith("--")]

    if "--path" in argv:
        # Audit mode. The hook never uses this — it scans the bounded staged set — but an
        # audit that takes 10 minutes on a large PHP tree does not get run, so skip the
        # things that cannot hold source-controlled credentials: binaries, media, and
        # anything oversized (a 5 MB minified bundle is not where a secret hides, and
        # regexing it dominates the runtime).
        root = argv[argv.index("--path") + 1]
        skip_d = {".git", "__pycache__", "node_modules", ".venv", "vendor", "dist", "build",
                  "logs", "backups", "uploads", "storage", ".idea", ".pytest_cache"}
        skip_x = {".png", ".jpg", ".jpeg", ".gif", ".ico", ".svg", ".webp", ".pdf", ".zip",
                  ".gz", ".tar", ".mp3", ".mp4", ".wav", ".woff", ".woff2", ".ttf", ".otf",
                  ".pyc", ".exe", ".dll", ".so", ".class", ".jar", ".map", ".bin", ".dat"}
        # Suffix matches, because splitext() sees ".js" in "html5-qrcode.min.js" — the
        # reason a minified bundle got scanned (and flagged) in the first measurement.
        skip_suffix = (".min.js", ".min.css", ".lock", "-lock.json", ".bundle.js")
        MAX = 1_000_000
        paths = []
        for dp, dns, fns in os.walk(root):
            dns[:] = [d for d in dns if d not in skip_d]
            for f in fns:
                fl = f.lower()
                if os.path.splitext(fl)[1] in skip_x or fl.endswith(skip_suffix):
                    continue
                fp = os.path.join(dp, f)
                try:
                    if os.path.getsize(fp) > MAX:
                        continue
                except OSError:
                    continue
                paths.append(fp)
        working = True
    elif "--stdin" in argv:
        paths = [l.strip() for l in sys.stdin.read().splitlines() if l.strip()]
    elif named:
        paths = named
    else:
        paths = staged_paths()

    n_sec = n_pub = n_files = 0
    for p in paths:
        text = read_text(p, working)
        if text is None:
            continue
        n_files += 1
        sec, pub = scan(p.replace("\\", "/"), text)
        for f in sec:
            n_sec += 1
            print(f"  SECRET  {f}")
        for f in pub:
            n_pub += 1
            print(f"  public  {f}  (not blocking)")

    if n_sec:
        print(f"\n  {n_sec} secret(s) in {n_files} scanned file(s). COMMIT REFUSED.")
        print("  Move the value into Bitwarden and reference it via /secret.")
        print("  False positive? Widen PLACEHOLDER/IS_PATH/ALLOW_FILE in this file —")
        print("  do NOT reach for --no-verify, which disables the gate for everything.")
        return 1
    print(f"  secret-scan: clean ({n_files} file(s)"
          + (f", {n_pub} public identifier(s)" if n_pub else "") + ")")
    return 0


if __name__ == "__main__":
    sys.exit(main())
