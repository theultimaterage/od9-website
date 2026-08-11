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

for _engine in _CANDIDATES:
    if os.path.exists(_engine):
        sys.argv[0] = _engine
        runpy.run_path(_engine, run_name="__main__")  # propagates the engine's exit code
        break
else:
    sys.exit("prod-deploy engine not found. Looked in:\n  " + "\n  ".join(_CANDIDATES))
