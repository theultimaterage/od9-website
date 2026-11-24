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

Since the cleanup of 2026-11-22 it also holds three things about the TREE, read from git, because
a rule that refuses a folder is the second line and "the file is not there" is the first:
  - the old local admin door stays gone. It was 65 wrapper pages under private/admin, a router
    under admin/, config/bootstrap.php and two root debug scripts; on a server every one of them
    could only fault. One of them coming back is a finding.
  - under private/ only the refusal rule ships. Anything else tracked there must be left out by
    the deploy's excludes.
  - no tracked file is named by this repository's own ignore rules. private/logs/security.log sat
    in this public repository for nine months that way: it was added before the rule, and an
    ignore rule does nothing for a file that is already tracked. `git ls-files -ci` lists exactly
    that shape; the one deliberate case is named below with its reason.

Runs on Windows Python against local XAMPP (the deploy's local_base_url). Exit 0 clean,
1 something is served or reachable that must not be, 2 it could not check.
"""
import fnmatch
import io
import json
import os
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
# The old local admin door, retired 2026-11-22. None of it may come back.
RETIRED_ADMIN_DIRS = ("private/admin/",)
RETIRED_ADMIN_FILES = ("admin/index.php", "admin/.htaccess", "config/bootstrap.php", "check_db.php", "check-admin.php")
# The one thing under private/ that a deploy sends: the rule that refuses the folder.
PRIVATE_SHIPS = ("private/.htaccess",)
# Tracked although an ignore rule names it, on purpose, each with its reason.
TRACKED_ON_PURPOSE = {
    "config/database.php": "tracked since the first commit; it reads its password from the environment (scanned "
                           "clean 2026-11-22), is excluded from the deploy, and the fleet sweep compares it with "
                           "production's own copy under a standing acknowledgement",
}
# The variables that tie a git command to ONE repository. A commit hook exports its own (for a
# commit that names its paths, GIT_INDEX_FILE is that commit's index, absolute), and a git call
# on another repository must not inherit them (claude-skills df0bf0e, 2026-11-22).
GIT_LOCAL = {"GIT_DIR", "GIT_WORK_TREE", "GIT_INDEX_FILE", "GIT_PREFIX", "GIT_COMMON_DIR",
             "GIT_OBJECT_DIRECTORY", "GIT_ALTERNATE_OBJECT_DIRECTORIES"}


class _NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, *a, **k):
        return None


def status(path: str, base: str = BASE, headers: dict | None = None) -> int:
    """The status the server gives for one path, redirects not followed. 0 = no answer.
    `headers` lets a caller name the site (Host), as tests/check_cloudflare_only.py does."""
    opener = urllib.request.build_opener(_NoRedirect)
    try:
        with opener.open(urllib.request.Request(base + path, headers=headers or {}), timeout=20) as r:
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
    """Deployable PHP that literally requires a Windows path. No folder is exempt since the old
    admin door was retired. `files` is {repo path: text}. Pure."""
    return sorted(path for path, text in files.items()
                  if path.endswith(".php") and WINDOWS_REQUIRE.search(text) and not excluded(path, patterns))


def retired_back(tracked: list) -> list:
    """Tracked paths that belong to the retired admin door. Pure."""
    return sorted(p for p in tracked if p.startswith(RETIRED_ADMIN_DIRS) or p in RETIRED_ADMIN_FILES)


def private_surplus(tracked: list, patterns: list) -> list:
    """Tracked files under private/ that a deploy would send, other than the refusal rule. Pure."""
    return sorted(p for p in tracked
                  if p.startswith("private/") and p not in PRIVATE_SHIPS and not excluded(p, patterns))


def unexplained_ignored(listed: list) -> list:
    """Of the tracked files an ignore rule names (git ls-files -ci --exclude-standard), the ones
    nobody has explained. Pure."""
    return sorted(p for p in listed if p and p not in TRACKED_ON_PURPOSE)


def tree_findings(tracked: list, ignored_tracked: list, excludes: list, php_files: dict, admin_on_disk: bool) -> list:
    """What the tree itself must not hold, as findings. Pure."""
    out = [f"{p} is back: the old admin door was retired on 2026-11-22 and can only fault on a server"
           for p in retired_back(tracked)]
    if admin_on_disk:
        out.append("private/admin/ exists on disk: rsync sends the working tree, tracked or not")
    out += [f"{p} would be deployed: under private/ only the refusal rule ships (leave its folder out in the "
            "deploy's excludes)" for p in private_surplus(tracked, excludes)]
    out += [f"{p} is tracked although this repository's own ignore rules name it: a file added before its rule "
            "keeps being published. Untrack it (git rm --cached), or explain it in TRACKED_ON_PURPOSE"
            for p in unexplained_ignored(ignored_tracked)]
    out += [f"{p} requires a Windows path and would be deployed: it cannot run on the server"
            for p in windows_requires(php_files, excludes)]
    return out


def deploy_excludes() -> list:
    cfg = json.loads(io.open(ROOT / "tools" / "prod-deploy-config.v2.json", encoding="utf-8").read())
    return list(cfg["mappings"][0].get("exclude", []))


def git_env() -> dict:
    """The environment for a git call on a repository this check names itself."""
    names = set(GIT_LOCAL)
    try:
        said = subprocess.run(["git", "rev-parse", "--local-env-vars"], capture_output=True, text=True,
                              timeout=15, stdin=subprocess.DEVNULL)
        names |= {n.strip() for n in said.stdout.splitlines() if n.strip().startswith("GIT_")}
    except Exception:  # noqa: BLE001 - the fixed list above still covers the ones that bite
        pass
    return {k: v for k, v in os.environ.items() if k not in names}


def git_lines(repo: pathlib.Path, *args: str) -> list:
    out = subprocess.run(["git", "-C", str(repo), *args], capture_output=True, text=True, encoding="utf-8",
                         errors="replace", timeout=120, stdin=subprocess.DEVNULL, env=git_env())
    if out.returncode != 0:
        raise RuntimeError(f"git {' '.join(args[:2])} failed: {out.stderr.strip()[:160]}")
    return [ln for ln in out.stdout.splitlines() if ln.strip()]


def tracked_php() -> dict:
    files = {}
    for rel in git_lines(ROOT, "ls-files", "*.php"):
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
    check("a deployable page that requires a Windows path is found wherever it sits (no folder is exempt "
          "since the admin door was retired); a file the deploy leaves out, a test for a path and a document are not",
          windows_requires(tree, ["tools/", "*.py"]) == ["config/bootstrap.php", "index.php", "private/admin/tiers/list.php"])
    tracked = ["index.php", "private/.htaccess", "private/admin/login.php", "private/email-templates/welcome.php",
               "private/migrations/004.sql", "admin/index.php", "config/bootstrap.php", "config/database.php",
               "check_db.php", "administrator/x.php"]
    check("every part of the retired admin door is named if it comes back, and nothing else is",
          retired_back(tracked) == ["admin/index.php", "check_db.php", "config/bootstrap.php", "private/admin/login.php"])
    check("under private/ only the refusal rule ships: a file the deploy would send is found, one the excludes "
          "leave out is not",
          private_surplus(tracked, ["migrations/"]) == ["private/admin/login.php", "private/email-templates/welcome.php"]
          and private_surplus(tracked, ["migrations/", "private/email-templates/", "private/admin/"]) == [])
    check("a tracked file that an ignore rule names is a finding unless it is explained here",
          unexplained_ignored(["config/database.php", "private/logs/security.log", ""]) == ["private/logs/security.log"])
    dirty = tree_findings(tracked, ["config/database.php", "private/logs/security.log"], ["migrations/", "tools/"],
                          tree, True)
    check("the tree's findings are all reported together: four retired paths, the folder on disk, two files "
          "private/ would ship, one unexplained ignored file, three Windows requires",
          len(dirty) == 11 and sum("is back" in f for f in dirty) == 4 and sum("exists on disk" in f for f in dirty) == 1
          and sum("would be deployed: under private/" in f for f in dirty) == 2
          and sum("ignore rules name it" in f for f in dirty) == 1 and sum("requires a Windows path" in f for f in dirty) == 3)
    check("a tree with none of it reports nothing",
          tree_findings(["index.php", "private/.htaccess", "private/migrations/004.sql", "config/database.php"],
                        ["config/database.php"], ["migrations/"], {"index.php": "<?php echo 1;"}, False) == [])
    with tempfile.TemporaryDirectory(ignore_cleanup_errors=True) as td:
        repo = pathlib.Path(td) / "r"
        (repo / "logs").mkdir(parents=True)
        (repo / "config").mkdir()
        (repo / "logs" / "app.log").write_text("x", encoding="utf-8")
        (repo / "config" / "database.php").write_text("<?php", encoding="utf-8")
        (repo / "keep.txt").write_text("x", encoding="utf-8")
        decoy = pathlib.Path(td) / "another-commits-index"
        was = os.environ.get("GIT_INDEX_FILE")
        os.environ["GIT_INDEX_FILE"] = str(decoy)      # what a commit hook exports for a path-named commit
        try:
            subprocess.run(["git", "init", "-q", str(repo)], capture_output=True, timeout=60,
                           stdin=subprocess.DEVNULL, env=git_env())
            git_lines(repo, "add", "logs/app.log", "config/database.php", "keep.txt")
            before_rule = git_lines(repo, "ls-files", "-ci", "--exclude-standard")
            (repo / ".gitignore").write_text("logs/" + chr(10) + "config/database.php" + chr(10), encoding="utf-8")
            after_rule = git_lines(repo, "ls-files", "-ci", "--exclude-standard")
        finally:
            if was is None:
                os.environ.pop("GIT_INDEX_FILE", None)
            else:
                os.environ["GIT_INDEX_FILE"] = was
        check("on a real repository: nothing is listed before the ignore rule exists, and a file added before "
              "its rule is listed after it (the security.log shape); the explained one is not a finding",
              before_rule == [] and sorted(after_rule) == ["config/database.php", "logs/app.log"]
              and unexplained_ignored(after_rule) == ["logs/app.log"])
        check("this check's git calls do not write into another commit's index (a commit hook exports one)",
              not decoy.exists())
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
        # /config/zz-no-such-file.php does not exist: a missing name inside a refused folder must
        # still answer 403, or the sweep's daily watch on a removed file would stop meaning anything.
        must_refuse = (f"{web}/.hidden/probe.txt", "/.git/HEAD", "/.git/config", "/.svn/entries",
                       SWEEP_PROBE, "/.env", "/.user.ini", "/config/zz-no-such-file.php", "/config/database.php",
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
    tracked = git_lines(ROOT, "ls-files")
    excludes = deploy_excludes()
    if len(tracked) < 100:
        print(f"origin-refusals: git listed only {len(tracked)} tracked file(s) here. UNCHECKED, not clean.")
        return 2
    findings += tree_findings(tracked, git_lines(ROOT, "ls-files", "-ci", "--exclude-standard"), excludes,
                              tracked_php(), (ROOT / "private" / "admin").exists())
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
