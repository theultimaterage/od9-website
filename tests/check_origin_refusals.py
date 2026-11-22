"""What this site's own rules must refuse, proven on local Apache. A pre-deploy gate.

    python tests/check_origin_refusals.py --selftest   # prove each judgment can fail
    python tests/check_origin_refusals.py              # the checks, against local XAMPP

WHY. On 2026-11-22 a scanner (gitdump/1.0) read /.git/HEAD, /.git/config and /.git/index
from the production ORIGIN, then asked for 120 of the paths the index lists. 68 answered
with a PHP fault, and one, private/admin/generate-wrappers.php, checked no login and wrote
36 files. Nothing had seen any of it for eight months: the dotfile rule matches a file's
NAME (and /.git/HEAD is named HEAD), and from the workstation Cloudflare's edge refuses
those paths, so every outside probe read "protected". The same generator had been run by
outside traffic on 2026-09-24 and only its 36 files were cleaned up.

Local Apache serves this checkout with the SAME .htaccess files production gets, and no
edge in front, so it answers what the origin would. Each rule is tested with a file that
really exists and a twin that must still be served: a refusal with nothing behind it
proves nothing.

Refused means 403, a rule answering. A 404 only says nothing was there, which is what a
server with the rule deleted says too; so the dot-directory rule answers 403 on purpose,
and a 404 where a refusal is owed is a finding here.

Runs on Windows Python against local XAMPP (the deploy's local_base_url). Exit 0 clean,
1 something is served or reachable that must not be, 2 it could not check.
"""
import fnmatch
import io
import json
import pathlib
import re
import shutil
import subprocess
import sys
import tempfile
import urllib.error
import urllib.request

BASE = "http://localhost/od9"
ROOT = pathlib.Path(__file__).resolve().parent.parent
# Refused means a rule answered: 403. A 404 is NOT that. It says nothing was there, which
# is also what a server with the rule deleted says about a path that does not exist, and a
# check that reads the two alike cannot tell a live rule from a missing one.
REFUSED = 403
# The path the fleet sweep asks the production origin for every day (origin_must_refuse in
# the deploy config). Nothing of it exists anywhere, so only the dot-directory rule can make
# it a 403. It starts with an ORDINARY name on purpose: Apache gives a missing path the name
# of its first missing part, so /.nothing/x is refused by the dotfile rule (a name starting
# with a dot) whether the dot-directory rule exists or not. That first probe was tried here
# on 2026-11-22: with the rule taken out it still answered 403, and proved nothing.
SWEEP_PROBE = "/sentinel-probe/.d/x"
# The exact test private/.htaccess ships, and the same test turned round. Local Apache's
# docroot starts with a drive letter, so the shipped one lets a request through here and
# the reversed one refuses it: both answers of the one expression, seen on a real server.
SHIPPED_IF = '<If "%{DOCUMENT_ROOT} !~ /^[A-Za-z]:/">'
REVERSED_IF = '<If "%{DOCUMENT_ROOT} =~ /^[A-Za-z]:/">'
WINDOWS_REQUIRE = re.compile(r"""(?:require|include)(?:_once)?[ (]*['"][A-Za-z]:[/\\]""")
# Where a literal Windows require is known and can do no harm on a server: private/ is
# refused over HTTP there, and so is config/.
DEAD_ON_SERVERS = ("private/", "config/bootstrap.php")


class _NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, *a, **k):
        return None


def status(path: str, base: str = BASE) -> int:
    """The status the server gives for one path, redirects not followed. 0 = no answer."""
    opener = urllib.request.build_opener(_NoRedirect)
    try:
        with opener.open(base + path, timeout=20) as r:
            return r.status
    except urllib.error.HTTPError as e:
        return e.code
    except Exception:  # noqa: BLE001 - "no answer" is the answer; the caller treats 0 as unchecked
        return 0


def judge(answers: dict, must_refuse: tuple, must_serve: tuple) -> tuple[list, list]:
    """(findings, unchecked) from {path: status}. Pure, so the selftest can drive it."""
    findings, unchecked = [], []
    for path in must_refuse:
        code = answers.get(path, 0)
        if code == 0:
            unchecked.append(path)
        elif code == 404:
            findings.append(f"{path} answered 404: nothing REFUSES it. A rule of ours must answer 403 here; "
                            "it is gone, or it never covered this path")
        elif code != REFUSED:
            findings.append(f"{path} answered {code}: it must be refused (403)")
    for path in must_serve:
        code = answers.get(path, 0)
        if code == 0:
            unchecked.append(path)
        elif code != 200:
            findings.append(f"{path} answered {code}: it must still be served (200), or the rule is too wide")
    return findings, unchecked


def excluded(path: str, patterns: list) -> bool:
    """Is this repo path left out of the deploy? rsync's exclude rules, as far as this needs."""
    base = path.rsplit("/", 1)[-1]
    for p in patterns:
        if p.endswith("/"):
            if ("/" + path).find("/" + p) >= 0:
                return True
        elif "/" in p:
            if path == p or fnmatch.fnmatch(path, p):
                return True
        elif fnmatch.fnmatch(base, p):
            return True
    return False


def windows_requires(files: dict, patterns: list) -> list:
    """Deployable PHP that literally requires a Windows path, outside the places where that
    is known and refused. `files` is {repo path: text}. Pure."""
    return sorted(path for path, text in files.items()
                  if path.endswith(".php") and WINDOWS_REQUIRE.search(text)
                  and not path.startswith(DEAD_ON_SERVERS) and not excluded(path, patterns))


def deploy_excludes() -> list:
    cfg = json.loads(io.open(ROOT / "tools" / "prod-deploy-config.v2.json", encoding="utf-8").read())
    return list(cfg["mappings"][0].get("exclude", []))


def tracked_php() -> dict:
    out = subprocess.run(["git", "-C", str(ROOT), "ls-files", "*.php"], capture_output=True, text=True,
                         encoding="utf-8", errors="replace", timeout=120)
    if out.returncode != 0:
        raise RuntimeError("git could not list the tracked files")
    files = {}
    for rel in out.stdout.splitlines():
        try:
            files[rel] = io.open(ROOT / rel, encoding="utf-8", errors="replace").read()
        except OSError:
            continue
    return files


def selftest() -> int:
    ok = True

    def check(name: str, passed: bool) -> None:
        nonlocal ok
        ok &= bool(passed)
        print(f"  selftest {'PASS' if passed else 'FAIL'}: {name}")
    served, _ = judge({"/.git/HEAD": 200, "/": 200}, ("/.git/HEAD",), ("/",))
    check("a dot-directory file that is served is a finding", len(served) == 1 and "/.git/HEAD" in served[0])
    clean, none = judge({"/.git/HEAD": 403, "/x": 403, "/": 200}, ("/.git/HEAD", "/x"), ("/",))
    check("a 403 is the refusal, and a served control is clean", clean == [] and none == [])
    absent, _ = judge({SWEEP_PROBE: 404, "/": 200}, (SWEEP_PROBE,), ("/",))
    check("a 404 is NOT a refusal: nothing there is what a missing rule says too",
          len(absent) == 1 and "nothing REFUSES" in absent[0])
    wide, _ = judge({"/.well-known/agent.json": 404}, (), ("/.well-known/agent.json",))
    check("a rule so wide that it refuses .well-known is a finding", len(wide) == 1)
    _, blind = judge({}, ("/.git/HEAD",), ("/",))
    check("a path the server did not answer is UNCHECKED, never clean", blind == ["/.git/HEAD", "/"])
    moved, _ = judge({"/private/admin/login.php": 302}, ("/private/admin/login.php",), ())
    check("a redirect is not a refusal: the page was reached", len(moved) == 1)
    tree = {"index.php": "<?php require_once 'C:/xampp/htdocs/x/Bootstrap.php';",
            "private/admin/tiers/list.php": "<?php require_once 'C:/xampp/htdocs/x/admin.php';",
            "config/bootstrap.php": "<?php require_once 'C:/xampp/htdocs/x/Bootstrap.php';",
            "includes/env.php": "<?php $local = strpos(__DIR__, 'C:/xampp') === 0;",
            "tools/dev.php": "<?php include 'D:\\\\work\\\\x.php';",
            "notes.md": "require_once 'C:/xampp/x.php';"}
    check("a deployable page that requires a Windows path is found; the known dead ones, a file the "
          "deploy leaves out, a test for a path and a document are not",
          windows_requires(tree, ["tools/", "*.py"]) == ["index.php"])
    check("the exclude matcher reads a folder at any depth, a path and a name pattern",
          excluded("a/tools/x.php", ["tools/"]) and excluded("config/database.php", ["config/database.php"])
          and excluded("x/y.bak", ["*.bak"]) and not excluded("toolsmith/x.php", ["tools/"]))
    check("an address that does not answer reads as 0, not as a refusal",
          status("/", base="http://127.0.0.1:9") == 0)
    print("origin-refusals selftest:", "ALL PASS" if ok else "FAILURES")
    return 0 if ok else 1


def main() -> int:
    if "--selftest" in sys.argv:
        return selftest()
    if status("/") != 200:
        print(f"origin-refusals: {BASE}/ did not answer 200. Start local Apache (C:\\xampp\\apache_start.bat). "
              f"UNCHECKED, not clean.")
        return 2
    findings: list = []
    unchecked: list = []
    ran = 0
    # A real file inside a dot-directory two levels down, and its twin beside it. tools/ is
    # in the local docroot and is never deployed.
    scratch = pathlib.Path(tempfile.mkdtemp(prefix="refusal-probe-", dir=ROOT / "tools"))
    web = "/tools/" + scratch.name
    try:
        (scratch / ".hidden").mkdir()
        (scratch / ".hidden" / "probe.txt").write_text("x", encoding="utf-8")
        (scratch / "seen").mkdir()
        (scratch / "seen" / "probe.txt").write_text("x", encoding="utf-8")
        shipped = io.open(ROOT / "private" / ".htaccess", encoding="utf-8").read()
        if SHIPPED_IF not in shipped or "Require all denied" not in shipped:
            findings.append("private/.htaccess no longer holds the refusal this check proves "
                            f"({SHIPPED_IF} ... Require all denied)")
        for name, rule in (("reversed", shipped.replace(SHIPPED_IF, REVERSED_IF)), ("shipped", shipped)):
            (scratch / name).mkdir()
            (scratch / name / "probe.txt").write_text("x", encoding="utf-8")
            (scratch / name / ".htaccess").write_text(rule, encoding="utf-8", newline="\n")
        must_refuse = (f"{web}/.hidden/probe.txt", "/.git/HEAD", "/.git/config", "/.svn/entries",
                       SWEEP_PROBE, "/.env", "/.user.ini", "/config/bootstrap.php", "/config/database.php",
                       f"{web}/reversed/probe.txt")
        must_serve = (f"{web}/seen/probe.txt", "/.well-known/agent.json", "/llms.txt",
                      f"{web}/shipped/probe.txt")
        answers = {p: status(p) for p in must_refuse + must_serve}
        ran = len(answers)
        findings_, unchecked = judge(answers, must_refuse, must_serve)
        findings += findings_
        if (ROOT / ".git" / "HEAD").is_file() and answers.get("/.git/HEAD") == REFUSED:
            print("  ok: this checkout's own .git/HEAD is on disk and local Apache answers 403 for it")
        # The sweep's daily question is only worth asking if the listed path is this one.
        listed = json.loads(io.open(ROOT / "tools" / "prod-deploy-config.v2.json",
                                    encoding="utf-8").read()).get("origin_must_refuse") or []
        if SWEEP_PROBE not in listed:
            findings.append(f"{SWEEP_PROBE} is not in origin_must_refuse in the deploy config: nothing would "
                            "ask the production origin whether the dot-directory rule is live")
    finally:
        shutil.rmtree(scratch, ignore_errors=True)
    if (ROOT / "private" / "admin" / "generate-wrappers.php").exists():
        findings.append("private/admin/generate-wrappers.php is back: it writes files and checks no login")
    stray = windows_requires(tracked_php(), deploy_excludes())
    findings += [f"{p} requires a Windows path and would be deployed: it cannot run on the server" for p in stray]
    for line in findings:
        print("  FINDING:", line)
    for path in unchecked:
        print("  UNCHECKED:", path, "(no answer)")
    if unchecked or ran < 14:
        print(f"origin-refusals: could not check {len(unchecked)} of {ran} address(es). UNCHECKED, not clean.")
        return 2
    print(f"origin-refusals: {ran} addresses asked on local Apache, {len(findings)} finding(s)")
    return 1 if findings else 0


if __name__ == "__main__":
    sys.exit(main())
