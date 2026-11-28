#!/usr/bin/env python3
"""This site's copies of the engine's form checks are the engine's own files. A pre-deploy gate.

    python tests/check_form_checks.py --selftest     prove each judgment can fail (scratch repos, offline)
    python tests/check_form_checks.py                the gate: the copies under includes/form-checks/ are the engine's
    python tests/check_form_checks.py --write        regenerate the copies from the tip of the engine's main

WHY. The guard in front of this site's forms (the contact form, the email signup on join.php, wake-up.php and the
popup every page carries, the Discord bot's /link_email) is the engine's FormGuard, HumanCheck, SignupBrake and
SignupVolume, kept once in shared-platform core/. This account cannot read the engine's files on the server, so it
carries generated copies, and a copy that drifts is the three-copies problem the form checks exist to end (spec:
https://claude.ai/artifact/4UWXZQzkUhCvdCZ9Y9b4nR, piece 4, 2026-11-28).

A launcher: the gate is the fleet's one implementation (~/.claude/skills/prod-deploy/form_checks.py), so every site
judges its copies the same way. What it asks and why each answer can fail is written out there. This repo carries
only its facts, in tests/form-checks.json.
"""
import os
import pathlib
import runpy
import sys

HERE = pathlib.Path(__file__).resolve().parent
ENGINE = pathlib.Path(os.path.expanduser("~")) / ".claude" / "skills" / "prod-deploy" / "form_checks.py"
if not ENGINE.exists():
    print(f"form-checks: the gate is not at {ENGINE}. UNCHECKED, not clean.")
    sys.exit(2)
rest = sys.argv[1:]
sys.argv = [str(ENGINE)] + (["--selftest"] if "--selftest" in rest else ["--site", str(HERE / "form-checks.json")] + rest)
runpy.run_path(str(ENGINE), run_name="__main__")
