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

for _engine in _CANDIDATES:
    if os.path.exists(_engine):
        sys.argv[0] = _engine
        runpy.run_path(_engine, run_name="__main__")  # propagates the engine's exit code
        break
else:
    sys.exit("prod-deploy engine not found. Looked in:\n  " + "\n  ".join(_CANDIDATES))
