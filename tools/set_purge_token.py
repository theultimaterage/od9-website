#!/usr/bin/env python3
"""set_purge_token.py - place the Cloudflare token the od9 deploy purges with, and refuse one that can do more.

WHY (2026-11-22). The deploy clears Cloudflare's cache for the files it sent, and reads the
token for that from the gitignored tools/prod-deploy-config.json (the tracked config says
"api_token_from": "prod-deploy-config.json:cf_api_token"). A token that sits in a plain file and
is read by every deploy should be able to do one thing. One that can ONLY purge one zone's cache
does little harm there; one that can also read or edit DNS, zone settings or certificates does
not belong in it, whatever it was first made for. So a purge-only token is the only kind this
tool will place, and --audit asks the same questions of the token already in the file, for a
deploy gate to run.

THE VALUE NEVER APPEARS on a command line, in output or in a log. It arrives in the environment,
straight from the vault:

    bash ~/.claude/skills/secret/secret.sh run --item "<vault item>" --field "API Token" \
        --key OD9_CF_PURGE_TOKEN -- python tools/set_purge_token.py

    ... -- python tools/set_purge_token.py --dry-run   the plan; Cloudflare is not asked, nothing is written
    python tools/set_purge_token.py --check             what the file holds now: length, prefix, hash prefix
    python tools/set_purge_token.py --audit             ask Cloudflare what the file's token can do (the deploy gate)
    python tools/set_purge_token.py --selftest

BEFORE anything is written, Cloudflare is asked, in this order:
  1. is the token active                       GET  /user/tokens/verify
  2. can it read DNS, zone settings,           GET  four list endpoints; each must be REFUSED
     certificates, or page rules
  3. can it purge this zone                    POST /zones/{zone}/purge_cache, one small public file
A token that passes 1, is refused at every read in 2 and passes 3 is placed. Anything else leaves
the file exactly as it was. The purge in 3 is sent only for a token that passed 2, and never by
--audit, so a refused token causes no side effect. An answer that is neither a clear yes nor a
clear refusal (a rate limit, a server error, an unknown zone) is "could not ask", exit 2: counting
a 429 as a refusal would let a broad token through.

THE WRITE changes one field and nothing else: the old value is replaced as text, so the file's
formatting stays byte for byte, then the result is parsed and compared with the old content.
It is written to a temporary file, flushed, and moved into place. No backup copy is kept: a
backup of this file is a credential at rest, and the vault is the store. Running it again with
the same value says "already in place" and asks nobody.

EVERY RUN appends one line to tools/deploy-runs/purge-token.jsonl (gitignored): the action and
12-character hash prefixes, never a value.

Vendor pages, read 2026-11-22:
  token formats (cfut_ + 40 characters + an 8-character checksum; cfk_ is the Global API Key):
    https://developers.cloudflare.com/fundamentals/api/get-started/token-formats/
  verify a token:  https://developers.cloudflare.com/fundamentals/api/get-started/create-token/
  which permission each endpoint accepts (Cloudflare's OpenAPI spec, "x-api-token-group"):
    POST /zones/{zone_id}/purge_cache         Cache Purge
    GET  /zones/{zone_id}/dns_records         DNS Read, DNS Write
    GET  /zones/{zone_id}/settings/{setting}  Zone Settings Read, Zone Settings Write
    GET  /zones/{zone_id}/ssl/certificate_packs   SSL and Certificates Read, Write
    GET  /zones/{zone_id}/pagerules           Zone Read, Zone Write, Page Rules Read, Write
    https://developers.cloudflare.com/api/
  purge needs "Cache Purge" for the zone: https://developers.cloudflare.com/cache/guides/invalidate-cache/

Exit codes: 0 placed, already in place, or the audit is satisfied. 1 refused (not a user token,
not active, can do more than purge, cannot purge). 2 could not run or could not ask (no value,
the file is missing or not the expected shape, Cloudflare gave no clear answer). 3 this tool crashed.
"""
from __future__ import annotations

import argparse
import contextlib
import datetime
import hashlib
import io
import json
import os
import pathlib
import re
import sys
import tempfile
import urllib.error
import urllib.request

HERE = pathlib.Path(__file__).resolve().parent
CONFIG = HERE / "prod-deploy-config.json"            # gitignored: cf_zone_id and cf_api_token
TRACKED_CONFIG = HERE / "prod-deploy-config.v2.json"   # tracked: site_url, and the pointer to the field
LOG = HERE / "deploy-runs" / "purge-token.jsonl"       # gitignored: one line per run, hashes only
ENV_KEY = "OD9_CF_PURGE_TOKEN"
FIELD, ZONE_FIELD = "cf_api_token", "cf_zone_id"
API = "https://api.cloudflare.com/client/v4"
TIMEOUT_S = 20
USER_TOKEN = re.compile(r"cfut_[A-Za-z0-9]{40}[0-9a-f]{8}")
ANY_CF_SECRET = re.compile(r"cf[a-z]{1,4}_[A-Za-z0-9]{20,}")
# Reads a purge token must NOT be able to make. None of these endpoints accepts "Cache Purge".
MUST_BE_REFUSED = (
    ("DNS", "/zones/{zone}/dns_records?per_page=1"),
    ("zone settings", "/zones/{zone}/settings/ssl"),
    ("certificates", "/zones/{zone}/ssl/certificate_packs"),
    ("page rules", "/zones/{zone}/pagerules"),
)
CLEAR_REFUSAL = (400, 401, 403)                         # the token is not allowed; anything else is not an answer
EX_OK, EX_REFUSED, EX_COULD_NOT, EX_CRASH = 0, 1, 2, 3
NL = chr(10)

_secrets: set[str] = set()


def h(value: str) -> str:
    return hashlib.sha256((value or "").encode("utf-8")).hexdigest()[:12]


def scrub(text: str) -> str:
    """Credentials never leave the client: remove every known value and anything shaped like one."""
    for s in _secrets:
        if s:
            text = text.replace(s, "<token>")
    return ANY_CF_SECRET.sub("<token>", text)


def say(text: str = "") -> None:
    print(scrub(text))


class CouldNotAsk(Exception):
    """Cloudflare gave no clear answer: not a yes and not a refusal."""


def http(method: str, path: str, token: str, body):
    """One call to Cloudflare: (status, envelope). Raises OSError when no answer arrived."""
    data = json.dumps(body).encode("utf-8") if body is not None else None
    req = urllib.request.Request(API + path, data=data, method=method, headers={
        "Authorization": "Bearer " + token, "Content-Type": "application/json",
        "User-Agent": "od9-set-purge-token/1"})
    try:
        with urllib.request.urlopen(req, timeout=TIMEOUT_S) as r:
            raw, status = r.read(), r.status
    except urllib.error.HTTPError as e:
        raw, status = e.read(), e.code
    try:
        env = json.loads(raw.decode("utf-8", "replace"))
    except ValueError:
        env = {}
    return status, env if isinstance(env, dict) else {}


def why(env: dict) -> str:
    errs = env.get("errors") or []
    said = "; ".join(f"{e.get('code')} {e.get('message')}" for e in errs if isinstance(e, dict))
    return (said or "no reason given")[:160]


def answer(transport, method: str, path: str, token: str, body=None) -> tuple[bool, int, dict]:
    """(allowed, status, envelope). Raises CouldNotAsk on anything that is neither yes nor a refusal."""
    try:
        status, env = transport(method, path, token, body)
    except OSError as e:
        raise CouldNotAsk(f"{method} {path.split('?')[0]}: no answer ({type(e).__name__}: {e})") from e
    if status == 200 and env.get("success") is True:
        return True, status, env
    if status in CLEAR_REFUSAL:
        return False, status, env
    raise CouldNotAsk(f"{method} {path.split('?')[0]}: HTTP {status} is neither yes nor a refusal ({why(env)})")


def ask_cloudflare(token: str, zone: str, probe_url: str, transport, purge: bool) -> tuple[str, list[str], list[str]]:
    """(verdict, lines, families it can read). Verdict: narrow, broad, inactive, cannot-purge."""
    lines: list[str] = []
    ok, status, env = answer(transport, "GET", "/user/tokens/verify", token)
    active = ok and (env.get("result") or {}).get("status") == "active"
    lines.append(f"  active at Cloudflare: {'yes' if active else 'NO'} (HTTP {status}"
                 + ("" if active else f", {why(env)}") + ")")
    if not active:
        return "inactive", lines, []
    can_read = []
    for family, path in MUST_BE_REFUSED:
        ok, status, env = answer(transport, "GET", path.format(zone=zone), token)
        lines.append(f"  can read {family}: {'YES' if ok else 'no'} (HTTP {status})")
        if ok:
            can_read.append(family)
    if can_read:
        return "broad", lines, can_read
    if purge:
        ok, status, env = answer(transport, "POST", f"/zones/{zone}/purge_cache", token, {"files": [probe_url]})
        lines.append(f"  can purge this zone: {'yes' if ok else 'NO'} (HTTP {status}"
                     + ("" if ok else f", {why(env)}") + ")")
        if not ok:
            return "cannot-purge", lines, []
    return "narrow", lines, []


def load_config(path: pathlib.Path) -> tuple[str, dict]:
    with io.open(path, encoding="utf-8", newline="") as fh:
        raw = fh.read()
    cfg = json.loads(raw)
    if not isinstance(cfg, dict) or FIELD not in cfg:
        raise ValueError(f"no {FIELD} field")
    if not isinstance(cfg.get(ZONE_FIELD), str) or not cfg[ZONE_FIELD]:
        raise ValueError(f"no {ZONE_FIELD}")
    return raw, cfg


def probe_url_from(tracked: pathlib.Path) -> str:
    site = json.loads(tracked.read_text(encoding="utf-8")).get("site_url")
    if not isinstance(site, str) or not site.startswith("https://"):
        raise ValueError("the tracked config has no https site_url")
    return site.rstrip("/") + "/robots.txt"


def replaced_text(raw: str, cfg: dict, new: str) -> str:
    """The file's text with ONE field changed, proven by parsing the result."""
    pat = re.compile(r'("' + re.escape(FIELD) + r'"\s*:\s*)' + re.escape(json.dumps(cfg.get(FIELD))))
    hits = len(pat.findall(raw))
    if hits != 1:
        raise ValueError(f"the field appears {hits} time(s) as text; expected exactly once")
    out = pat.sub(lambda m: m.group(1) + json.dumps(new), raw)
    expect = dict(cfg)
    expect[FIELD] = new
    if json.loads(out) != expect:
        raise ValueError("the edit would change more than the one field")
    return out


def write_atomic(path: pathlib.Path, text: str) -> None:
    tmp = path.with_name(path.name + ".tmp-place")
    with io.open(tmp, "w", encoding="utf-8", newline="") as fh:
        fh.write(text)
        fh.flush()
        os.fsync(fh.fileno())
    os.replace(tmp, path)


def record(log_path: pathlib.Path, **fields) -> None:
    fields["ts"] = datetime.datetime.now(datetime.timezone.utc).strftime("%Y-%m-%dT%H:%M:%SZ")
    try:
        log_path.parent.mkdir(parents=True, exist_ok=True)
        with io.open(log_path, "a", encoding="utf-8", newline="") as fh:
            fh.write(scrub(json.dumps(fields, sort_keys=True)) + NL)
    except OSError as e:
        say(f"  (the run could not be recorded in {log_path.name}: {type(e).__name__})")


def shape_problem(token: str) -> str | None:
    if not token:
        return f"nothing arrived in ${ENV_KEY}: run this through secret.sh run (see the top of this file)"
    if token.startswith("cfk_"):
        return "this is a Global API Key (cfk_): it can do everything on the account and never goes in a file"
    if token.startswith("cfat_"):
        return "this is an account-owned token (cfat_): this tool verifies user tokens (cfut_) only"
    if not USER_TOKEN.fullmatch(token):
        return ("this is not shaped like a Cloudflare user API token (cfut_, 40 characters, an 8-character "
                "checksum): tokens made or rolled since 2026 all are")
    return None


def place(token: str, config: pathlib.Path, tracked: pathlib.Path, log_path: pathlib.Path, transport,
          dry_run: bool) -> int:
    _secrets.add(token)
    problem = shape_problem(token)
    if problem:
        say(f"REFUSED: {problem}.")
        return EX_COULD_NOT if not token else EX_REFUSED
    try:
        raw, cfg = load_config(config)
        probe = probe_url_from(tracked)
    except (OSError, ValueError) as e:
        say(f"COULD NOT RUN: {config.name} or {tracked.name} is not readable as expected ({type(e).__name__}: {e}).")
        return EX_COULD_NOT
    old = cfg.get(FIELD) or ""
    _secrets.add(old)
    if old == token:
        say(f"already in place: {config.name} holds this token (sha256 {h(token)}). Nothing asked, nothing written.")
        record(log_path, action="unchanged", token=h(token))
        return EX_OK
    say(f"plan: replace {FIELD} in {config.name} (now sha256 {h(old)}, length {len(old)}) "
        f"with the token from ${ENV_KEY} (sha256 {h(token)}).")
    if dry_run:
        say("DRY RUN: Cloudflare was not asked and nothing was written.")
        return EX_OK
    try:
        verdict, lines, can_read = ask_cloudflare(token, cfg[ZONE_FIELD], probe, transport, purge=True)
    except CouldNotAsk as e:
        say(f"COULD NOT ASK Cloudflare: {e}. Nothing was written.")
        record(log_path, action="could-not-ask", token=h(token))
        return EX_COULD_NOT
    for line in lines:
        say(line)
    if verdict != "narrow":
        reason = {"inactive": "Cloudflare does not call this token active",
                  "broad": "it can read " + ", ".join(can_read) + ": a token for this file may only purge",
                  "cannot-purge": "it cannot purge this zone, which is the one thing the deploy needs"}[verdict]
        say(f"REFUSED: {reason}. {config.name} is unchanged.")
        record(log_path, action="refused", verdict=verdict, token=h(token), can_read=can_read)
        return EX_REFUSED
    try:
        write_atomic(config, replaced_text(raw, cfg, token))
        _raw, back = load_config(config)
    except (OSError, ValueError) as e:
        say(f"COULD NOT WRITE {config.name}: {type(e).__name__}: {e}.")
        return EX_COULD_NOT
    if back.get(FIELD) != token:
        say(f"COULD NOT WRITE: {config.name} does not hold the new token after the write.")
        return EX_COULD_NOT
    record(log_path, action="placed", old=h(old), token=h(token))
    say(f"placed: {config.name} now holds a token that can only purge (sha256 {h(token)}; was {h(old)}).")
    return EX_OK


def audit(config: pathlib.Path, tracked: pathlib.Path, log_path: pathlib.Path, transport) -> int:
    """The deploy gate: what can the token in the file do? Never purges."""
    try:
        _raw, cfg = load_config(config)
        probe = probe_url_from(tracked)
    except (OSError, ValueError) as e:
        say(f"purge-token audit: COULD NOT RUN ({config.name}: {type(e).__name__}: {e}).")
        return EX_COULD_NOT
    token = cfg.get(FIELD) or ""
    _secrets.add(token)
    if not token:
        say(f"purge-token audit: {config.name} holds no token. The deploy's purge step will say NOT PURGED.")
        return EX_OK
    try:
        verdict, lines, can_read = ask_cloudflare(token, cfg[ZONE_FIELD], probe, transport, purge=False)
    except CouldNotAsk as e:
        say(f"purge-token audit: COULD NOT ASK Cloudflare ({e}). A gate that could not look is not a pass.")
        return EX_COULD_NOT
    for line in lines:
        say(line)
    if verdict == "inactive":
        say("purge-token audit: the token in the file is not active (rolled or deleted). It can do nothing; the "
            "deploy's purge step will fail loudly until a purge-only token is placed with tools/set_purge_token.py.")
        return EX_OK
    if verdict == "broad":
        say(f"purge-token audit: REFUSED. The token in {config.name} (sha256 {h(token)}) can read "
            + ", ".join(can_read) + ". A deploy must not run with a token that can do more than purge: roll that "
            "token at Cloudflare, make one with the Cache Purge permission for this zone only, and place it with "
            "tools/set_purge_token.py (the command is at the top of that file).")
        record(log_path, action="audit-refused", token=h(token), can_read=can_read)
        return EX_REFUSED
    say(f"purge-token audit: ok. The token in the file is active and is refused DNS, zone settings, certificates "
        f"and page rules (sha256 {h(token)}).")
    return EX_OK


def check(config: pathlib.Path, env_token: str) -> int:
    try:
        _raw, cfg = load_config(config)
    except (OSError, ValueError) as e:
        say(f"COULD NOT RUN: {config.name}: {type(e).__name__}: {e}.")
        return EX_COULD_NOT
    token = cfg.get(FIELD) or ""
    _secrets.update({token, env_token})
    prefix = token.split("_")[0] + "_" if "_" in token[:6] else "(none)"
    say(f"{config.name}: {FIELD} has length {len(token)}, prefix {prefix}, sha256 {h(token)}.")
    if env_token:
        say(f"${ENV_KEY}: sha256 {h(env_token)}; the same token: {'yes' if env_token == token else 'no'}.")
    return EX_OK


# --- selftest -----------------------------------------------------------------------------------

def selftest() -> int:
    ok = True

    def expect(label: str, cond: bool) -> None:
        nonlocal ok
        ok = ok and bool(cond)
        print(f"  [{'ok' if cond else 'FAIL'}] {label}")

    good = "cfut_" + "A1b2C3d4E5" * 4 + "0a1b2c3d"       # made up: the shape, not a token
    other = "cfut_" + "Z9y8X7w6V5" * 4 + "1b2c3d4e"
    zone = "zone123"

    def cloudflare(active=True, can_read=(), can_purge=True, down=False, echo=False, read_status=403):
        calls: list[tuple[str, str]] = []

        def transport(method, path, token, body):
            calls.append((method, path.split("?")[0]))
            if down:
                raise OSError("no route to host")
            denied = {"success": False, "errors": [{"code": 10000, "message": "Authentication error"}]}
            if path == "/user/tokens/verify":
                if active:
                    return 200, {"success": True, "result": {"status": "active"}}
                return 401, {"success": False, "errors": [
                    {"code": 1000, "message": "Invalid API Token" + (" " + token if echo else "")}]}
            if method == "POST":
                return (200, {"success": True}) if can_purge else (403, denied)
            for family, p in MUST_BE_REFUSED:
                if path == p.format(zone=zone):
                    return (200, {"success": True, "result": []}) if family in can_read else (read_status, denied)
            return 404, {}

        transport.calls = calls        # type: ignore[attr-defined]
        return transport

    def run(fn, *args) -> tuple[int, str]:
        out = io.StringIO()
        with contextlib.redirect_stdout(out):
            rc = fn(*args)
        return rc, out.getvalue()

    with tempfile.TemporaryDirectory() as td:
        d = pathlib.Path(td)
        cfgp, tracked, logp = d / "prod-deploy-config.json", d / "prod-deploy-config.v2.json", d / "runs" / "p.jsonl"
        original = ('{' + NL + '  "_doc": "kept ' + chr(0x2014) + ' as it is",' + NL + '  "cf_zone_id": "' + zone + '",'
                    + NL + '  "cf_api_token": "' + other + '",' + NL + '  "never_deploy": ["a", "b"]' + NL + '}' + NL)
        tracked.write_text(json.dumps({"site_url": "https://example.test"}), encoding="utf-8")

        def reset() -> None:
            with io.open(cfgp, "w", encoding="utf-8", newline="") as fh:
                fh.write(original)

        def text() -> str:
            with io.open(cfgp, encoding="utf-8", newline="") as fh:
                return fh.read()

        expect("a made-up user token has the right shape; a Global API Key, an account token, an unprefixed "
               "40-character token and a short checksum each have a named problem",
               shape_problem(good) is None and "Global API Key" in (shape_problem("cfk_" + "a" * 48) or "")
               and "account-owned" in (shape_problem("cfat_" + "a" * 48) or "")
               and shape_problem("a" * 40) is not None and shape_problem(good[:-1]) is not None)

        reset()
        cf = cloudflare()
        rc, said = run(place, good, cfgp, tracked, logp, cf, False)
        expect("a token that is active, refused at all four reads and able to purge is placed, and only the one "
               "value in the file changes (formatting and the other fields byte for byte)",
               rc == EX_OK and text() == original.replace(other, good) and ("POST", f"/zones/{zone}/purge_cache") in cf.calls)
        expect("nothing printed and nothing logged carries either value",
               good not in said and other not in said and good not in logp.read_text(encoding="utf-8")
               and other not in logp.read_text(encoding="utf-8") and h(good) in said)

        cf = cloudflare()
        before = text()
        rc, said = run(place, good, cfgp, tracked, logp, cf, False)
        expect("running it again with the same value says so, asks nobody and leaves the file alone",
               rc == EX_OK and "already in place" in said and cf.calls == [] and text() == before)

        for label, kwargs, want in (
                ("a token that can read DNS is refused as too broad", {"can_read": ("DNS",)}, EX_REFUSED),
                ("a token that can read certificates is refused as too broad", {"can_read": ("certificates",)}, EX_REFUSED),
                ("a token that cannot purge is refused", {"can_purge": False}, EX_REFUSED),
                ("a token Cloudflare does not call active is refused", {"active": False}, EX_REFUSED),
                ("no answer from Cloudflare is 'could not ask', not a verdict", {"down": True}, EX_COULD_NOT),
                ("a rate-limited read (429) is 'could not ask', never counted as a refusal", {"read_status": 429}, EX_COULD_NOT),
        ):
            reset()
            cf = cloudflare(**kwargs)
            rc, said = run(place, good, cfgp, tracked, logp, cf, False)
            expect(label + ", and the file is exactly as it was", rc == want and text() == original)
            if kwargs.get("can_read"):
                expect("  ... and no purge was sent for it (a refused token causes no side effect)",
                       not any(m == "POST" for m, _p in cf.calls))

        reset()
        cf = cloudflare(active=False, echo=True)
        rc, said = run(place, good, cfgp, tracked, logp, cf, False)
        expect("when Cloudflare's own error text repeats the token, what is printed does not",
               rc == EX_REFUSED and good not in said and "<token>" in said)

        reset()
        cf = cloudflare()
        rc, said = run(place, good, cfgp, tracked, logp, cf, True)
        expect("a dry run prints the plan, asks nobody and writes nothing",
               rc == EX_OK and "DRY RUN" in said and cf.calls == [] and text() == original)

        rc, said = run(place, "", cfgp, tracked, logp, cloudflare(), False)
        expect("no value in the environment is 'could not run', and says how to run it", rc == EX_COULD_NOT and ENV_KEY in said)
        cfgp.write_text('{"cf_zone_id": "z"}', encoding="utf-8")
        rc, _ = run(place, good, cfgp, tracked, logp, cloudflare(), False)
        expect("a config with no token field is 'could not run'", rc == EX_COULD_NOT)
        cfgp.write_text("{not json", encoding="utf-8")
        rc, _ = run(place, good, cfgp, tracked, logp, cloudflare(), False)
        expect("a config that is not JSON is 'could not run'", rc == EX_COULD_NOT)

        reset()
        for label, kwargs, want, word in (
                ("audit: a narrow token passes", {}, EX_OK, "audit: ok"),
                ("audit: a token that can read DNS REFUSES the deploy", {"can_read": ("DNS",)}, EX_REFUSED, "REFUSED"),
                ("audit: a token that is no longer active passes with a note (it can do nothing)", {"active": False}, EX_OK, "not active"),
                ("audit: no answer is 'could not ask'", {"down": True}, EX_COULD_NOT, "COULD NOT ASK"),
                ("audit: a rate limit is 'could not ask'", {"read_status": 429}, EX_COULD_NOT, "COULD NOT ASK"),
        ):
            cf = cloudflare(**kwargs)
            rc, said = run(audit, cfgp, tracked, logp, cf)
            expect(label + ", without purging anything and without printing the value",
                   rc == want and word in said and other not in said and not any(m == "POST" for m, _p in cf.calls))
        cfgp.write_text(original.replace(other, ""), encoding="utf-8")
        rc, said = run(audit, cfgp, tracked, logp, cloudflare())
        expect("audit: a file with no token passes and says the purge will not run", rc == EX_OK and "NOT PURGED" in said)

        reset()
        rc, said = run(check, cfgp, good)
        expect("check prints length, prefix and hash prefix, and never a value",
               rc == EX_OK and h(other) in said and other not in said and good not in said and "the same token: no" in said)

    print("set_purge_token selftest: " + ("PASS" if ok else "FAIL"))
    return 0 if ok else 1


def main(argv: list[str] | None = None) -> int:
    for stream in (sys.stdout, sys.stderr):
        with contextlib.suppress(AttributeError, ValueError):
            stream.reconfigure(encoding="utf-8", errors="replace")   # type: ignore[union-attr]
    ap = argparse.ArgumentParser(description=(__doc__ or "").split(NL)[0])
    ap.add_argument("--check", action="store_true", help="what the file holds now (hash only); nothing is asked or written")
    ap.add_argument("--audit", action="store_true", help="ask Cloudflare what the file's token can do; exit 1 if more than purge")
    ap.add_argument("--dry-run", action="store_true", help="the plan for placing the value in the environment; no network, no write")
    ap.add_argument("--selftest", action="store_true")
    a = ap.parse_args(argv)
    try:
        if a.selftest:
            return selftest()
        if a.check:
            return check(CONFIG, os.environ.get(ENV_KEY, ""))
        if a.audit:
            return audit(CONFIG, TRACKED_CONFIG, LOG, http)
        return place(os.environ.get(ENV_KEY, ""), CONFIG, TRACKED_CONFIG, LOG, http, a.dry_run)
    except Exception as e:  # noqa: BLE001 - a crash gets its own exit code and never prints a value
        say(f"set_purge_token CRASHED: {type(e).__name__}: {e}")
        return EX_CRASH


if __name__ == "__main__":
    sys.exit(main())
