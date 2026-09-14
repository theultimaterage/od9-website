#!/usr/bin/env python3
"""
tools/prod-deploy.py — thin launcher for the canonical prod-deploy engine.

This repo carries only its config. The engine lives once, in the prod-deploy
skill, so improvements propagate to every site instead of drifting across
per-repo copies.

Replaces the 234-line repo-local tools/deploy.py, which was a parallel
implementation and was already broken: its config pointed at `web_src: "public"`,
a directory that no longer exists after the public/ -> root restructure. Its
central justification — "the SSH user (ultimaterage) cannot write offda9's web
root directly", hence SCP-to-/tmp + sudo install — is also obsolete: this repo
now connects AS offda9, which owns its writable docroot.

deploy.py was retired 2026-08-11 once this path was proven in production. Its
two extra capabilities were checked before removal, not assumed away:
  * opcache reset — NOT needed. Prod runs opcache.validate_timestamps=1 with
    revalidate_freq=2, so deployed changes are picked up within ~2s. Confirmed
    empirically: faq.php and insights.php served correctly the instant they
    landed.
  * Cloudflare cache purge — genuinely lost. Static assets may sit stale at the
    edge until TTL. Purge by hand if a CSS/JS change must appear immediately;
    a post_deploy hook in the shared engine is the proper fix.

Config is passed explicitly because the legacy tools/prod-deploy-config.json
(different schema, gitignored) still exists — it is no longer a deploy config,
but it holds the Cloudflare zone id + API token used for manual DNS work. Do
NOT rename the v2 file over it.

Run from the repo root, under WSL (for native rsync/ssh):
  wsl python3 tools/prod-deploy.py --dry     # preview rsync diff, no writes
  wsl python3 tools/prod-deploy.py           # full deploy (needs deploy_enabled:true)
  wsl python3 tools/prod-deploy.py --rollback

See ~/.claude/skills/prod-deploy/SKILL.md for the schema, flags, and exit codes.
"""
import os
import runpy
import sys
import glob
import re


def _route_crawl_candidates(repo: str) -> list:
    """Where route_crawl.py may live. Under WSL — the way this deploy is run —
    '~' is the WSL home, not C:/Users/<you>, so the Windows homes on the repo's
    drive (/mnt/c/Users/*) are probed too. Until 2026-09-11 the wrapper looked
    only at '~' and silently skipped the crawl on every WSL-driven deploy."""
    out = []
    env_dir = os.environ.get("CLAUDE_SKILLS_DIR")
    if env_dir:
        out.append(os.path.join(env_dir, "route-crawl", "route_crawl.py"))
    out.append(os.path.expanduser("~/.claude/skills/route-crawl/route_crawl.py"))
    m = re.match(r"^(/mnt/[a-z])/", repo.replace("\\", "/"))
    if m:
        out.extend(sorted(glob.glob(m.group(1) + "/Users/*/.claude/skills/route-crawl/route_crawl.py")))
    return out


def _find_route_crawl(repo: str):
    for c in _route_crawl_candidates(repo):
        if os.path.exists(c):
            return c
    return None


if "--where-is-crawl" in sys.argv:
    # Prover for the resolution above: exit 0 only when the crawl script resolves.
    # Run it from Windows AND via `wsl bash -lc` — the second is the one that failed.
    _repo0 = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
    _found0 = _find_route_crawl(_repo0)
    for _c in _route_crawl_candidates(_repo0):
        print(("  found   " if os.path.exists(_c) else "  absent  ") + _c)
    print("route_crawl.py: " + (_found0 or "NOT FOUND"))
    sys.exit(0 if _found0 else 1)

_CONFIG = os.path.join(os.path.dirname(os.path.abspath(__file__)), "prod-deploy-config.v2.json")

_CANDIDATES = [
    "/mnt/c/Users/Rage/.claude/skills/prod-deploy/deploy.py",      # WSL view of Windows home
    os.path.expanduser("~/.claude/skills/prod-deploy/deploy.py"),  # native invocation
]

# Default to this repo's v2 config unless the caller named one explicitly.
if not any(a == "--config" or a.startswith("--config=") for a in sys.argv[1:]):
    sys.argv.extend(["--config", _CONFIG])

# CITATION GATE (2026-09-08): the Codex lessons quote the manifesto verbatim,
# and the manifesto's own repair worklists condemn fabricated and re-dated
# citations that have not been repaired yet. Nothing checked whether a condemned
# one had already been published. It runs on EVERY deploy, not only when
# curriculum files change, because it takes under a second and the thing it
# prevents is putting an invented source on a site whose whole claim is that it
# does not invent. --skip-citation-gate bypasses; say why in the commit.
# It also runs on --dry, unlike the browser checks: those cost a minute, this
# costs under a second, and a dry run that skips a gate reports a pass the real
# deploy might not give.
if "--skip-citation-gate" in sys.argv:
    sys.argv.remove("--skip-citation-gate")
else:
    import subprocess
    _root = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
    _cg = os.path.join(_root, "tools", "citation_gate.py")
    if os.path.exists(_cg):
        print("=== Citation gate (published lessons vs the manifesto worklists) ===", flush=True)
        _cenv = {**os.environ, "PYTHONIOENCODING": "utf-8", "PYTHONUTF8": "1"}
        if subprocess.run([sys.executable, _cg], env=_cenv).returncode != 0:
            sys.exit("ABORT: a published lesson carries a citation the manifesto's worklists "
                     "condemn, or a worklist names a source nobody has classified")

# FORGE WORK LOG (2026-09-08): the Forge publishes what has been done to the
# manifesto, derived from the book's own git history. Deploying the page while
# that log is behind the book means publishing a ledger that is silently out of
# date -- which is the exact failure this whole page exists to refuse. Runs on
# --dry too; it is a JSON diff and costs nothing. It does NOT block when the
# manifesto checkout is absent, because a machine without the book cannot know,
# and saying so out loud beats failing forever.
# --skip-forge-timeline bypasses; say why in the commit.
if "--skip-forge-timeline" in sys.argv:
    sys.argv.remove("--skip-forge-timeline")
else:
    import subprocess
    _root = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
    _ft = os.path.join(_root, "tools", "forge_timeline.py")
    if os.path.exists(_ft):
        print("=== Forge work log (the ledger vs the manifesto's git history) ===", flush=True)
        _fenv = {**os.environ, "PYTHONIOENCODING": "utf-8", "PYTHONUTF8": "1"}
        if subprocess.run([sys.executable, _ft, "--check"], env=_fenv).returncode != 0:
            sys.exit("ABORT: the Forge's work log is behind the manifesto. "
                     "Run: python tools/forge_timeline.py, then commit the map.")

# MANIFESTO MAP FRESHNESS (2026-09-14): data/manifesto-map.json is a BUILD
# ARTIFACT of the bot repo's tools/build_manifesto_map.py, but it is committed
# and DEPLOYED from here -- so the guard and the artifact lived in different
# repos, and od9 could ship a stale map any time nobody happened to run the bot's
# test suite. That matters now because the map's inputs finally move on their
# own: canon state comes from curriculum/lessons.json (this repo) and preached
# state from the manifesto's sermon beat sheets, so preaching a sermon or seeding
# a lesson makes this artifact stale without touching a single file the deploy
# would otherwise notice. Publishing the Atlas while it is behind is the exact
# failure the page exists to refuse -- a chapter that was preached still reading
# "awaiting its sermon".
#
# Like the Forge gate above, it does NOT block when the builder or the manifesto
# is absent: a machine without the book cannot know, and saying so out loud beats
# failing forever. It writes nothing -- a checker that repaired the artifact it
# judges would make every deploy pass by fixing itself.
# --skip-map-freshness bypasses; say why in the commit.
if "--skip-map-freshness" in sys.argv:
    sys.argv.remove("--skip-map-freshness")
else:
    import subprocess
    _bot = "/mnt/c/Users/Rage/IdeaProjects/OD9-Discord-Bot"
    if not os.path.exists(_bot):
        _bot = r"C:\Users\Rage\IdeaProjects\OD9-Discord-Bot"
    _bmm = os.path.join(_bot, "tools", "build_manifesto_map.py")
    _bpy = os.path.join(_bot, "venv", "Scripts", "python.exe")
    if os.path.exists(_bmm) and os.path.exists(_bpy):
        if _bmm.startswith("/mnt/c/"):
            _bmm = "C:/" + _bmm[len("/mnt/c/"):]   # the Windows interpreter needs a Windows path
        print("=== Manifesto map (the Atlas vs the lesson registry + sermon sheets) ===", flush=True)
        _menv = {**os.environ, "PYTHONIOENCODING": "utf-8", "PYTHONUTF8": "1"}
        if subprocess.run([_bpy, _bmm, "--check"], env=_menv).returncode != 0:
            sys.exit("ABORT: the Atlas map is behind its sources. Run: "
                     "python tools/build_manifesto_map.py (in the bot repo), "
                     "then commit data/manifesto-map.json here.")
    else:
        print("=== Manifesto map: builder not on this machine - UNCHECKED, not clean ===", flush=True)

# CODEX ANCHORS (2026-09-14): a canon lesson declares the manifesto section each
# passage came from, and the Atlas's section satellites link into the lesson at
# that anchor. A merge renumbers sections underneath those declarations -- the
# Volume 1 consolidation shifted two of ch7's by one (ethics inserted as the new
# section II) and left six more pointing PAST THE END of a chapter cut from ~19
# sections to 10. Nothing checked, so the Atlas offered landings that go nowhere.
# Rides a ratchet: only a NEW wrong anchor fails, so known debt cannot hold the
# deploy hostage. Runs on --dry (a text scan, ~2s), and does NOT block when the
# manifesto is absent -- the tool says so itself and exits 0.
# --skip-codex-anchors bypasses; say why in the commit.
if "--skip-codex-anchors" in sys.argv:
    sys.argv.remove("--skip-codex-anchors")
else:
    import subprocess
    _root = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
    _ca = os.path.join(_root, "tools", "codex_anchors.py")
    if os.path.exists(_ca):
        print("=== Codex anchors (lesson landings vs the manifesto's sections) ===", flush=True)
        _aenv = {**os.environ, "PYTHONIOENCODING": "utf-8", "PYTHONUTF8": "1"}
        if subprocess.run([sys.executable, _ca, "--verify"], env=_aenv).returncode != 0:
            sys.exit("ABORT: a canon lesson points at a manifesto section that does not "
                     "exist. Fix the anchor (tools/codex_anchors.py --verify), or lower "
                     "the ceiling with --ratchet after a real repair.")

# RAIL LABELS (2026-09-14): the board's progress rail is keyed to the curriculum
# in two places that nothing was comparing — RAIL_LABELS by content_id, and
# RAIL_CHAPTERS as position ranges over each tier's ordered required modules. The
# file HAS a selftest, but it proved the labels FIT (length budget, fallback
# totality) rather than that they still MATCH: seed or retire one module and a
# label points at nothing, or a chapter range stops covering the last stop and
# rail_chapter_for() silently returns null. Measured clean the day this was
# wired; clean by discipline is the state every other break this week was in.
#
# Runs the WINDOWS php deliberately. Both /usr/bin/php under WSL and the CLI php
# on PATH ship without the sqlite driver, so the curriculum half would skip
# itself — the same reason the Atlas checks reach for the bot's Windows venv.
# It reads the LOCAL bot DB (what $isLocal resolves to here), so it validates the
# code being shipped against this machine's curriculum copy; keep that copy in
# step with prod or the check is honest about the wrong data.
# --skip-rail-labels bypasses; say why in the commit.
if "--skip-rail-labels" in sys.argv:
    sys.argv.remove("--skip-rail-labels")
else:
    import subprocess
    _root = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
    _rl = os.path.join(_root, "dashboard", "includes", "rail-labels.php")
    _wphp = "/mnt/c/tools/php84/php.exe"
    if not os.path.exists(_wphp):
        _wphp = r"C:\tools\php84\php.exe"
    if os.path.exists(_rl) and os.path.exists(_wphp):
        if _rl.startswith("/mnt/c/"):
            _rl = "C:/" + _rl[len("/mnt/c/"):]   # the Windows interpreter needs a Windows path
        print("=== Rail labels (the board's rail vs the live curriculum) ===", flush=True)
        _renv2 = {**os.environ, "PYTHONIOENCODING": "utf-8", "PYTHONUTF8": "1"}
        if subprocess.run([_wphp, _rl, "--selftest"], env=_renv2).returncode != 0:
            sys.exit("ABORT: the board's progress rail no longer matches the curriculum. "
                     "Fix dashboard/includes/rail-labels.php (php includes/rail-labels.php "
                     "--selftest names the exact label or range).")
    else:
        print("=== Rail labels: no sqlite-capable php on this machine - UNCHECKED, not clean ===",
              flush=True)

# CHAPTER REFERENCES (2026-09-09): the manifesto is consolidating 67 chapters to
# 52, and 2,264 sentences say "Chapter N". Every merge invalidates the ones
# pointing at what it absorbed -- demoting ch2 to Appendix A broke twenty in a
# single afternoon. The map is the registry; this holds the prose to it, and only
# for chapters whose source has actually moved, so it is not permanently red.
# --skip-chapter-refs bypasses; say why in the commit.
if "--skip-chapter-refs" in sys.argv:
    sys.argv.remove("--skip-chapter-refs")
else:
    import subprocess
    _root = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
    _cr = os.path.join(_root, "tools", "chapter_refs.py")
    if os.path.exists(_cr):
        print("=== Chapter references (the prose vs the decided skeleton) ===", flush=True)
        _renv = {**os.environ, "PYTHONIOENCODING": "utf-8", "PYTHONUTF8": "1"}
        if subprocess.run([sys.executable, _cr], env=_renv).returncode != 0:
            sys.exit("ABORT: a sentence points at a chapter that no longer exists. "
                     "Rewrite each in context, then deploy.")

# ATLAS CHECKS (2026-09-05): when the tree touches an Atlas file, the browser
# checks run before the engine (tools/atlas_checks.py — Playwright from the
# bot repo's venv, against local Apache like the render gate). Skipped on
# --dry. --skip-atlas-checks bypasses; say why in the commit.
_ATLAS_PATHS = ["atlas.php", "js/atlas.js", "js/atlas-sound.js", "api/v1/atlas-live.php", "api/v1/atlas-ping.php",
                "data/manifesto-map.json", "data/atlas-objects.json", "audio/atlas", "images/atlas"]
if "--skip-atlas-checks" in sys.argv:
    sys.argv.remove("--skip-atlas-checks")
elif "--dry" not in sys.argv:
    import subprocess
    _root = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
    _touched = subprocess.run(["git", "-C", _root, "diff", "--name-only", "HEAD~5", "HEAD", "--"] + _ATLAS_PATHS,
                              capture_output=True, text=True).stdout.split()
    _touched += subprocess.run(["git", "-C", _root, "status", "--porcelain", "--"] + _ATLAS_PATHS,
                               capture_output=True, text=True).stdout.split()
    if _touched:
        _venv = "/mnt/c/Users/Rage/IdeaProjects/OD9-Discord-Bot/venv/Scripts/python.exe"
        _py = _venv if os.path.exists(_venv) else r"C:\Users\Rage\IdeaProjects\OD9-Discord-Bot\venv\Scripts\python.exe"
        _script = os.path.join(_root, "tools", "atlas_checks.py")
        if _script.startswith("/mnt/c/"):
            _script = "C:/" + _script[len("/mnt/c/"):]     # the Windows interpreter needs a Windows path
        print("=== Atlas browser checks (tools/atlas_checks.py) ===", flush=True)
        # UTF-8 for the child, ALWAYS (2026-09-06): the checks print star glyphs
        # and arrows in their labels, and a Windows child inherits a cp1252
        # stdout when its output is a pipe — UnicodeEncodeError, which the gate
        # reads as a failed check and aborts a healthy deploy. The script also
        # guards its own stdout; this is the half that protects the NEXT script.
        _cenv = {**os.environ, "PYTHONIOENCODING": "utf-8", "PYTHONUTF8": "1"}
        _rc = subprocess.run([_py, _script], env=_cenv).returncode
        if _rc != 0:
            sys.exit("ABORT: Atlas browser checks failed (exit %d) — fix them, or --skip-atlas-checks with a reason in the commit" % _rc)

# ROUTE CRAWL (2026-09-08): the deploy's own smoke tests check a handful of named
# URLs. This requests EVERY page the site serves and attributes any 5xx to the
# fatal that caused it, so a page nobody thought to list cannot break unnoticed.
# It runs AFTER the engine — including after the Cloudflare purge — so it sees
# what a visitor sees rather than what the edge cached before the deploy.
# --skip-route-crawl bypasses; say why in the commit.
_skip_crawl = "--skip-route-crawl" in sys.argv
if _skip_crawl:
    sys.argv.remove("--skip-route-crawl")
_dry_run = "--dry" in sys.argv

for _engine in _CANDIDATES:
    if os.path.exists(_engine):
        sys.argv[0] = _engine
        _rc_deploy = 0
        try:
            runpy.run_path(_engine, run_name="__main__")
        except SystemExit as _e:          # the engine exits rather than returning
            if isinstance(_e.code, str):  # a refusal message: print it as the engine would
                print(_e.code, file=sys.stderr)
                _rc_deploy = 1
            else:
                _rc_deploy = _e.code or 0
        if _rc_deploy == 0 and not _dry_run and not _skip_crawl:
            import subprocess
            _repo = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
            _crawl = _find_route_crawl(_repo)
            print("")
            print("=== Route crawl (every page, and why any 5xx happened) ===", flush=True)
            if not _crawl:
                # A gate that cannot be found is a failure, not a skip: the files
                # are live, so say so, exit non-zero, and name the by-hand command.
                sys.exit("DEPLOYED, BUT THE ROUTE CRAWL DID NOT RUN — route_crawl.py was not found. Looked in:"
                         + "".join(chr(10) + "  " + c for c in _route_crawl_candidates(_repo))
                         + chr(10) + "Run it by hand from the repo root on Windows: "
                         "python ~/.claude/skills/route-crawl/route_crawl.py  (or set CLAUDE_SKILLS_DIR).")
            print("  script: " + _crawl, flush=True)
            _cenv = {**os.environ, "PYTHONIOENCODING": "utf-8", "PYTHONUTF8": "1"}
            if subprocess.run([sys.executable, _crawl], cwd=_repo, env=_cenv).returncode != 0:
                sys.exit("DEPLOYED, BUT A ROUTE IS RETURNING 5xx. The files are already live "
                         "— fix forward, or roll back from the backup named above.")
        sys.exit(_rc_deploy)
else:
    sys.exit("prod-deploy engine not found. Looked in:" + "".join(
        chr(10) + "  " + c for c in _CANDIDATES))
