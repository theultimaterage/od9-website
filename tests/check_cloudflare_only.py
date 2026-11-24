"""The server answers this site only through Cloudflare, or from itself. A pre-deploy gate.

    python tests/check_cloudflare_only.py --selftest   # prove each judgment can fail
    python tests/check_cloudflare_only.py              # the checks, against local XAMPP + Cloudflare's list

WHY. The server's address is public (mail.offda9.com is a DNS-only record on it, and that name lands in
this docroot). On 2026-11-22 a scanner went straight to the address, read /.git and ran a script, and
Cloudflare saw none of it. The rule in .htaccess (the <If> that reads %{CONN_REMOTE_ADDR}) refuses any
request whose TCP peer is neither one of Cloudflare's published ranges nor the server's own address,
except /.well-known/acme-challenge/. This proves that rule, the file it lives in, and the list it holds.

WHAT IT ASKS, and why each one can fail:
  1. Local Apache, THIS checkout's own .htaccess, from 127.0.0.1 (which is neither Cloudflare nor the
     server). The rule is off locally unless a request names the site, so each request names offda9.com:
     - /robots.txt must be REFUSED (403) on offda9.com, www. and mail. With the rule gone it answers 200.
     - the same file without a site name must still be served (200): the local checkout keeps working.
     - /.well-known/acme-challenge/<made up> must stay OPEN (404, not 403): certificates renew through it.
     - /robots.txt/.well-known/acme-challenge/x must be refused: path info cannot borrow the exception.
     - ncz-media/ (a folder whose own rule grants files of one shape) must still be refused.
  2. A scratch folder under tools/ (never deployed), for what the real tree cannot show by itself:
     - an acme-challenge folder with NO .htaccess beside it stays open: the rule's own exception works
       without .well-known/.htaccess's help.
     - folders that grant with a plain Require, or inside <FilesMatch>, are still refused: the <If>
       merges after them.
     - a folder that grants inside its own <If>, or with "Satisfy Any", IS reopened. Those two shapes are
       why the tree scan below exists; if Apache stops reopening them, this says so instead of trusting
       a premise nobody re-measured.
     - two copies of the shipped <If>, differing ONLY in whether 127.0.0.1 stands in for the server's
       own address: one serves, one refuses. Both answers of this file's expression, decided by the
       connecting address alone.
  3. The rule's text: exactly one such <If>; it refuses and nothing else; it reads CONN_REMOTE_ADDR and
     never REMOTE_ADDR or -R (mod_remoteip rewrites those to the visitor: measured on a scratch Apache
     with mod_remoteip, 2026-11-23, a REMOTE_ADDR rule refuses real visitors and believes a forged
     header); it is on for every name on a server; its addresses are Cloudflare's CURRENT list, read
     from Cloudflare at every run, plus the server's address from the deploy config, and nothing else.
     A missing range refuses every visitor Cloudflare routes through it; an extra one is a door.
  4. The tree: no tracked .htaccess may reopen what the rule refuses ("Satisfy Any" outside
     .well-known/.htaccess, or a Require that grants inside an <If>/<ElseIf>/<Else>).

Exit 0 clean, 1 a finding, 2 could not check (local Apache down, Cloudflare's list unreadable).

Sources (read 2026-11-23):
  https://www.cloudflare.com/ips/ ("intended to be the definitive source of Cloudflare's current IP ranges",
      last updated 2023-09-28, and it points at the API read here: api.cloudflare.com/client/v4/ips)
  https://httpd.apache.org/docs/2.4/mod/mod_remoteip.html (the connection's own address stays in
      CONN_REMOTE_ADDR while REMOTE_ADDR is replaced)
  https://httpd.apache.org/docs/2.4/expr.html (CONN_REMOTE_ADDR, -ipmatch, req_novary)
  https://httpd.apache.org/docs/2.4/sections.html (<If> merges after a subdirectory's own directives)
  https://httpd.apache.org/docs/2.4/mod/mod_access_compat.html (Satisfy Any admits on the host check alone)
"""
import io
import ipaddress
import json
import pathlib
import re
import shutil
import sys
import tempfile
import urllib.request

sys.path.insert(0, str(pathlib.Path(__file__).resolve().parent))
from check_origin_refusals import ROOT, git_lines, status  # noqa: E402  one implementation of each

BASE = "http://127.0.0.1/od9"          # IPv4 loopback on purpose: the connecting address must be 127.0.0.1
SITE = "offda9.com"
NAMES = ("offda9.com", "www.offda9.com", "mail.offda9.com")
VARIANT_HOST = "cf-only-variant.test"  # a name only the scratch copies answer to
CF_IPS_URL = "https://api.cloudflare.com/client/v4/ips"
HTACCESS = ROOT / ".htaccess"
REFUSED = 403
CONN_TERM = re.compile(r"%\{CONN_REMOTE_ADDR\}\s+-ipmatch\s+'([^']+)'")
SERVER_ONLY = "%{DOCUMENT_ROOT} !~ m#^[A-Za-z]:#"
GRANTS = re.compile(r"^\s*(Require\s+(?!all\s+denied\s*$)\S|Allow\s+from\b)", re.I)
SATISFY_ANY = re.compile(r"^\s*Satisfy\s+Any\b", re.I)
SATISFY_ANY_HOME = ".well-known/.htaccess"   # grants the whole folder on purpose; see the rule's comments


def joined_lines(text: str) -> list:
    """Config lines as Apache reads them: a line ending in a backslash continues onto the next."""
    out, buf = [], ""
    for raw in text.replace("\r\n", "\n").split("\n"):
        if raw.endswith("\\"):
            buf += raw[:-1]
            continue
        out.append(buf + raw)
        buf = ""
    if buf:
        out.append(buf)
    return out


ADDRESS_KEYED = re.compile(r"-ipmatch|REMOTE_ADDR|(^|[\s(!])-R\s")


def rule_blocks(text: str) -> list:
    """Every top-level <If> whose expression tests an address, as (expression, [body lines]). Keyed on ANY
    address test, not on CONN_REMOTE_ADDR, so a rule switched to the wrong variable is still recognised as
    the rule and reported for what it reads. Pure."""
    blocks, lines, i = [], joined_lines(text), 0
    while i < len(lines):
        m = re.match(r'^\s*<If\s+"(.*)"\s*>\s*$', lines[i])
        if m and ADDRESS_KEYED.search(m.group(1)):
            body, i = [], i + 1
            while i < len(lines) and not re.match(r"^\s*</If>\s*$", lines[i]):
                if lines[i].strip() and not lines[i].strip().startswith("#"):
                    body.append(lines[i].strip())
                i += 1
            blocks.append((m.group(1), body))
        i += 1
    return blocks


def canonical(addr: str) -> str:
    return str(ipaddress.ip_network(addr, strict=False))


def rule_findings(text: str, cloudflare: list, server_addr: str) -> list:
    """What is wrong with the rule's TEXT. Pure."""
    blocks = rule_blocks(text)
    if not blocks:
        return ["the Cloudflare-only rule is gone from .htaccess (no <If> reading %{CONN_REMOTE_ADDR}): every "
                "rule kept at Cloudflare is advisory again for anyone who knows the server's address"]
    if len(blocks) > 1:
        return [f"{len(blocks)} <If> blocks read %{{CONN_REMOTE_ADDR}}; the rule is ONE block, so a second one is "
                "either a copy that will drift or a grant that merges after it"]
    expr, body = blocks[0]
    out = []
    if body != ["Require all denied"]:
        out.append(f"the rule's body must be exactly 'Require all denied', found {body}")
    if re.search(r"(?<!CONN_)REMOTE_ADDR\b", expr) or re.search(r"(^|[\s(!])-R\s", expr):
        out.append("the rule reads REMOTE_ADDR (or -R): on the server mod_remoteip rewrites it to the visitor "
                   "named in CF-Connecting-IP, so the rule would refuse every real visitor and believe a forged "
                   "header. It must read %{CONN_REMOTE_ADDR} only")
    if SERVER_ONLY not in expr:
        out.append(f"the rule no longer holds '{SERVER_ONLY}': on the server it must be on for EVERY name that "
                   "reaches this docroot (mail.offda9.com included), not only when a request names the site")
    if "acme-challenge" not in expr:
        out.append("the rule no longer exempts /.well-known/acme-challenge/: certificate renewals for the "
                   "DNS-only name mail.offda9.com come straight to the server")
    found = {canonical(a) for a in CONN_TERM.findall(expr)}
    wanted = {canonical(a) for a in cloudflare} | {canonical(server_addr)}
    for missing in sorted(wanted - found):
        who = "the server's own address (the fleet sweep's origin probe and cPanel's AutoSSL check connect from " \
              "it)" if missing == canonical(server_addr) else "a Cloudflare range: visitors routed through it would be refused"
        out.append(f"{missing} is not let in: {who}")
    for extra in sorted(found - wanted):
        out.append(f"{extra} is let in but is neither Cloudflare's (current list) nor the server's own address: "
                   "a door that skips Cloudflare")
    return out


def tree_findings(files: dict) -> list:
    """Tracked .htaccess files that would reopen what the rule refuses. `files` is {repo path: text}. Pure."""
    out = []
    for path, text in sorted(files.items()):
        depth = 0
        for n, line in enumerate(joined_lines(text), 1):
            s = line.strip()
            if not s or s.startswith("#"):
                continue
            if SATISFY_ANY.match(s) and path != SATISFY_ANY_HOME:
                out.append(f"{path}:{n} says 'Satisfy Any': it lets a request past every Require, so this folder "
                           "answers requests that skip Cloudflare")
            if re.match(r"^<(If|ElseIf|Else)\b", s, re.I):
                depth += 1
            elif re.match(r"^</(If|ElseIf|Else)>", s, re.I):
                depth = max(0, depth - 1)
            elif depth and GRANTS.match(s):
                out.append(f"{path}:{n} grants inside an <If>/<ElseIf>/<Else> ('{s}'): it merges after the "
                           "Cloudflare-only rule and reopens its folder to requests that skip Cloudflare")
    return out


def judge(answers: dict, wanted: dict) -> tuple[list, list]:
    """(findings, unchecked) from {key: status} against {key: (status, why)}. Pure."""
    findings, unchecked = [], []
    for key, (want, why) in wanted.items():
        got = answers.get(key, 0)
        if got == 0:
            unchecked.append(key)
        elif got != want:
            hint = " (404 means nothing refused it: the rule is gone or does not cover it)" if want == REFUSED and got == 404 else ""
            findings.append(f"{key[1]} {key[0]} answered {got}, owed {want}: {why}{hint}")
    return findings, unchecked


def variant(block_text: str, admit_loopback: bool, server_addr: str) -> str:
    """A scratch copy of the shipped <If>, answering only to VARIANT_HOST, optionally with 127.0.0.1 standing
    in for the server's own address. Each substitution must happen exactly once, or the copy is not the
    shipped rule and proves nothing."""
    pairs = [(r"offda9\.com", VARIANT_HOST.replace(".", "\\."))]
    if admit_loopback:
        pairs.append((f"'{server_addr}'", "'127.0.0.1'"))
    out = block_text
    for old, new in pairs:
        if out.count(old) != 1:
            raise ValueError(f"the shipped rule holds {out.count(old)} copies of {old!r}, not 1")
        out = out.replace(old, new)
    return out


def shipped_block_text(text: str) -> str:
    """The rule's <If> ... </If> exactly as written (continuation lines included)."""
    for m in re.finditer(r"^<If\b.*?^</If>[ \t]*$", text, re.S | re.M):
        if "CONN_REMOTE_ADDR" in m.group(0):
            return m.group(0) + "\n"
    raise ValueError("no <If> reading CONN_REMOTE_ADDR in .htaccess")


def cloudflare_ranges(timeout: int = 20, attempts: int = 3) -> list:
    """Cloudflare's published ranges, read now. Three tries (2 s, then 4 s apart): one dropped connection
    should not refuse a deploy, and no answer after three is UNCHECKED, never a pass."""
    import time
    req = urllib.request.Request(CF_IPS_URL, headers={"User-Agent": "od9-deploy-gate/1"})
    for n in range(attempts):
        try:
            with urllib.request.urlopen(req, timeout=timeout) as r:
                data = json.loads(r.read().decode("utf-8"))
            break
        except Exception:  # noqa: BLE001 - retried, then raised to the caller as unchecked
            if n == attempts - 1:
                raise
            time.sleep(2 * (2 ** n))
    result = data.get("result") or {}
    ranges = list(result.get("ipv4_cidrs") or []) + list(result.get("ipv6_cidrs") or [])
    if not data.get("success") or len(ranges) < 10:
        raise ValueError(f"Cloudflare's list came back unusable ({len(ranges)} ranges, success={data.get('success')})")
    return ranges


def server_address() -> str:
    cfg = json.loads(io.open(ROOT / "tools" / "prod-deploy-config.v2.json", encoding="utf-8").read())
    return cfg["ssh"]["host"]


SAMPLE = """Options -Indexes
<If "(%{DOCUMENT_ROOT} !~ m#^[A-Za-z]:# || req_novary('Host') =~ m#(^|\\.)offda9\\.com\\.?(:[0-9]+)?$#i) \\
    && %{REQUEST_FILENAME} !~ m#/\\.well-known/acme-challenge(/[^/]*)?$# \\
    && !(   %{CONN_REMOTE_ADDR} -ipmatch '173.245.48.0/20' \\
         || %{CONN_REMOTE_ADDR} -ipmatch '2400:cb00::/32' \\
         || %{CONN_REMOTE_ADDR} -ipmatch '203.0.113.5' )">
    Require all denied
</If>
"""


def selftest() -> int:
    ok = True

    def check(name: str, passed: bool) -> None:
        nonlocal ok
        ok &= bool(passed)
        print(f"  selftest {'PASS' if passed else 'FAIL'}: {name}")
    cf, me = ["173.245.48.0/20", "2400:cb00::/32"], "203.0.113.5"
    check("the shipped shape, its list matching Cloudflare's plus the server, is clean", rule_findings(SAMPLE, cf, me) == [])
    check("continuation lines are joined as Apache joins them, so the whole expression is one line",
          len(rule_blocks(SAMPLE)) == 1 and "203.0.113.5" in rule_blocks(SAMPLE)[0][0])
    gone = rule_findings("Options -Indexes\n", cf, me)
    check("a file without the rule is a finding", len(gone) == 1 and "gone" in gone[0])
    remote = rule_findings(SAMPLE.replace("CONN_REMOTE_ADDR", "REMOTE_ADDR"), cf, me)
    check("a rule switched wholly to REMOTE_ADDR is recognised as the rule and reported for reading it "
          "(mod_remoteip rewrites it to the visitor)",
          any("reads REMOTE_ADDR" in f for f in remote) and not any("gone" in f for f in remote))
    dash_r = rule_findings(SAMPLE.replace("%{CONN_REMOTE_ADDR} -ipmatch '2400:cb00::/32'", "-R '2400:cb00::/32'"), cf, me)
    check("a rule using -R (REMOTE_ADDR by another name) is a finding", any("reads REMOTE_ADDR" in f for f in dash_r))
    short = rule_findings(SAMPLE, cf + ["131.0.72.0/22"], me)
    check("a Cloudflare range the rule lacks is a finding that names it",
          len(short) == 1 and "131.0.72.0/22" in short[0] and "refused" in short[0])
    extra = rule_findings(SAMPLE, ["2400:cb00::/32"], me)
    check("an address the rule lets in that is neither Cloudflare's nor the server's is a finding",
          len(extra) == 1 and "173.245.48.0/20" in extra[0] and "door" in extra[0])
    no_self = rule_findings(SAMPLE.replace(" \\\n         || %{CONN_REMOTE_ADDR} -ipmatch '203.0.113.5'", ""), cf, me)
    check("losing the server's own address is a finding (the sweep's probe would go blind)",
          len(no_self) == 1 and "203.0.113.5" in no_self[0] and "sweep" in no_self[0])
    check("addresses compare as networks, not strings (a /32 written bare is the same address)",
          rule_findings(SAMPLE.replace("'203.0.113.5'", "'203.0.113.5/32'"), cf, me) == [])
    local_only = rule_findings(SAMPLE.replace("%{DOCUMENT_ROOT} !~ m#^[A-Za-z]:# || ", ""), cf, me)
    check("a rule that is only on when a request names the site is a finding (mail. lands here too)",
          any("EVERY name" in f for f in local_only))
    check("a rule without the ACME exception is a finding",
          any("acme-challenge" in f for f in rule_findings(SAMPLE.replace("acme-challenge", "acme-x"), cf, me)))
    granted = rule_findings(SAMPLE.replace("Require all denied", "Require all granted"), cf, me)
    check("a rule whose body grants is a finding", any("exactly 'Require all denied'" in f for f in granted))
    two = rule_findings(SAMPLE + SAMPLE, cf, me)
    check("two copies of the rule is a finding", len(two) == 1 and "2 <If>" in two[0])
    tree = {".well-known/.htaccess": "Options -Indexes\nSatisfy Any\nAllow from all\n",
            "private/.htaccess": '<If "%{DOCUMENT_ROOT} !~ /^[A-Za-z]:/">\n    Require all denied\n</If>\n',
            "ncz-media/.htaccess": 'Require all denied\n<FilesMatch "^x$">\n    Require all granted\n</FilesMatch>\n',
            "docs-like/.htaccess": "# Satisfy Any (a comment)\n# <If> Require all granted\n"}
    check("the live tree's shapes are clean: .well-known's Satisfy Any, private/'s denying <If>, ncz-media's "
          "FilesMatch grant, comments", tree_findings(tree) == [])
    bad = tree_findings({**tree, "x/.htaccess": "Allow from all\nSatisfy any\n",
                         "y/.htaccess": '<If "true">\n  Require all granted\n</If>\n',
                         "z/.htaccess": '<If "%{HTTPS} == \'on\'">\n</If>\n<Else>\n  Require ip 10.0.0.0/8\n</Else>\n'})
    check("Satisfy Any outside .well-known, a grant inside <If>, and a grant inside <Else> are each found",
          len(bad) == 3 and sum("Satisfy Any" in f for f in bad) == 1 and sum("grants inside" in f for f in bad) == 2)
    wanted = {("/robots.txt", "offda9.com"): (403, "w"), ("/robots.txt", "localhost"): (200, "w"),
              ("/.well-known/acme-challenge/z", "offda9.com"): (404, "w")}
    clean, none = judge({k: v[0] for k, v in wanted.items()}, wanted)
    check("answers equal to what is owed are clean", clean == [] and none == [])
    served, _ = judge({("/robots.txt", "offda9.com"): 200, ("/robots.txt", "localhost"): 200,
                       ("/.well-known/acme-challenge/z", "offda9.com"): 404}, wanted)
    check("a direct request that is served is a finding", len(served) == 1 and "answered 200" in served[0])
    missing, _ = judge({("/robots.txt", "offda9.com"): 404, ("/robots.txt", "localhost"): 200,
                        ("/.well-known/acme-challenge/z", "offda9.com"): 404}, wanted)
    check("a 404 where a refusal is owed is a finding that says nothing refused it",
          len(missing) == 1 and "nothing refused it" in missing[0])
    closed, _ = judge({("/robots.txt", "offda9.com"): 403, ("/robots.txt", "localhost"): 200,
                       ("/.well-known/acme-challenge/z", "offda9.com"): 403}, wanted)
    check("a refused ACME path is a finding (certificates could not renew)", len(closed) == 1)
    _, blind = judge({}, wanted)
    check("no answer is UNCHECKED, never clean", len(blind) == 3)
    block = shipped_block_text(SAMPLE)
    v_self, v_other = variant(block, True, me), variant(block, False, me)
    check("the two scratch copies differ only in 127.0.0.1 standing in for the server's address",
          "'127.0.0.1'" in v_self and "'127.0.0.1'" not in v_other and "cf-only-variant" in v_other
          and v_self.replace("'127.0.0.1'", f"'{me}'") == v_other)
    try:
        variant(block.replace("'203.0.113.5'", "'198.51.100.1'"), True, me)
        refused_variant = False
    except ValueError:
        refused_variant = True
    check("a copy whose substitution does not happen exactly once is refused, not run", refused_variant)
    check("an address that does not answer reads as 0, not as an answer",
          status("/", base="http://127.0.0.1:9", headers={"Host": SITE}) == 0)
    print("cloudflare-only selftest:", "ALL PASS" if ok else "FAILURES")
    return 0 if ok else 1


def main() -> int:
    if "--selftest" in sys.argv:
        return selftest()
    # Only NO answer means local Apache is down. Any status means it is up: a 403 here is the rule refusing the
    # local checkout itself, which the checks below report as a finding instead of misreading it as "down".
    if status("/robots.txt", base=BASE) == 0:
        print(f"cloudflare-only: {BASE}/robots.txt got no answer. Start local Apache "
              "(C:\\xampp\\apache_start.bat). UNCHECKED, not clean.")
        return 2
    try:
        cloudflare = cloudflare_ranges()
    except Exception as e:  # noqa: BLE001 - any failure to read the list is "could not check"
        print(f"cloudflare-only: could not read Cloudflare's list from {CF_IPS_URL} ({e}). UNCHECKED, not clean.")
        return 2
    text = io.open(HTACCESS, encoding="utf-8").read()
    server = server_address()
    findings = rule_findings(text, cloudflare, server)
    tracked = [p for p in git_lines(ROOT, "ls-files") if p == ".htaccess" or p.endswith("/.htaccess")]
    if len(tracked) < 5:
        print(f"cloudflare-only: git listed only {len(tracked)} .htaccess file(s). UNCHECKED, not clean.")
        return 2
    findings += tree_findings({p: io.open(ROOT / p, encoding="utf-8", errors="replace").read() for p in tracked})

    wanted = {}
    for name in NAMES:
        wanted[("/robots.txt", name)] = (REFUSED, "a request whose connecting address is neither Cloudflare's nor "
                                                  "the server's must be refused on every name of this docroot")
    wanted[("/robots.txt", "127.0.0.1")] = (200, "without a site name the rule is off: the local checkout must keep working")
    wanted[("/.well-known/acme-challenge/zz-cf-only-probe", SITE)] = (404, "the ACME path must stay open to everyone "
                                                                          "(a made-up token is simply missing)")
    wanted[("/robots.txt/.well-known/acme-challenge/x", SITE)] = (REFUSED, "path info must not borrow the ACME exception")
    wanted[("/ncz-media/00000000000000000000000000000000.mp4", SITE)] = (REFUSED, "a folder whose own rule grants "
                                                                              "must still be refused")
    scratch = pathlib.Path(tempfile.mkdtemp(prefix="cf-only-probe-", dir=ROOT / "tools"))
    web = "/tools/" + scratch.name
    try:
        acme = scratch / "acme" / ".well-known" / "acme-challenge"
        acme.mkdir(parents=True)
        (acme / "probe-token").write_text("token", encoding="utf-8")
        plants = {
            "granted": ("Require all granted\n", REFUSED, "a folder granting with a plain Require is still refused"),
            "filesgranted": ('<FilesMatch "^probe\\.txt$">\n    Require all granted\n</FilesMatch>\n', REFUSED,
                             "a folder granting inside <FilesMatch> is still refused"),
            "ifgranted": ('<If "true">\n    Require all granted\n</If>\n', 200,
                          "PREMISE: a grant inside a folder's own <If> reopens it (why the tree scan refuses that shape)"),
            "satisfyany": ("Allow from all\nSatisfy Any\n", 200,
                           "PREMISE: 'Satisfy Any' reopens a folder (why the tree scan refuses that shape)"),
        }
        try:
            block = shipped_block_text(text)
            for name, admit in (("variant-self", True), ("variant-other", False)):
                plants[name] = (variant(block, admit, server), 200 if admit else REFUSED,
                                "the shipped expression with 127.0.0.1 standing in for the server's address must serve"
                                if admit else "the shipped expression must refuse 127.0.0.1 (neither Cloudflare nor the server)")
        except ValueError as e:
            findings.append(f"the two scratch copies of the shipped rule could not be built ({e}), so both answers "
                            "of its expression were not asked")
        for name, (rule, want, why) in plants.items():
            (scratch / name).mkdir()
            (scratch / name / "probe.txt").write_text("x", encoding="utf-8")
            (scratch / name / ".htaccess").write_text(rule, encoding="utf-8", newline="\n")
            host = VARIANT_HOST if name.startswith("variant") else SITE
            wanted[(f"{web}/{name}/probe.txt", host)] = (want, why)
        wanted[(f"{web}/acme/.well-known/acme-challenge/probe-token", SITE)] = (
            200, "the rule's own ACME exception must hold with no .well-known/.htaccess beside it")
        answers = {key: status(key[0], base=BASE, headers={"Host": key[1]}) for key in wanted}
    finally:
        shutil.rmtree(scratch, ignore_errors=True)
    found, unchecked = judge(answers, wanted)
    findings += found
    for line in findings:
        print("  FINDING:", line)
    for key in unchecked:
        print("  UNCHECKED:", key[1], key[0], "(no answer)")
    if unchecked:
        print(f"cloudflare-only: {len(unchecked)} of {len(wanted)} request(s) got no answer. UNCHECKED, not clean.")
        return 2
    print(f"cloudflare-only: {len(wanted)} requests on local Apache, Cloudflare's list ({len(cloudflare)} ranges) "
          f"read, {len(tracked)} .htaccess files scanned, {len(findings)} finding(s)")
    return 1 if findings else 0


if __name__ == "__main__":
    sys.exit(main())
