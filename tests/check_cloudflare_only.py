#!/usr/bin/env python3
"""The server answers this site only through Cloudflare, or from itself. A pre-deploy gate.

    python tests/check_cloudflare_only.py --selftest     prove each judgment can fail
    python tests/check_cloudflare_only.py                the checks: local Apache, Cloudflare's list, the tree
    python tests/check_cloudflare_only.py --live         ask the live server (one refused request per listed name)

WHY. The server's address is public (mail.offda9.com is a DNS-only record on it, and that name lands in this
docroot). On 2026-11-22 a scanner went straight to the address, read /.git and ran a script, and Cloudflare saw none
of it. The rule in .htaccess (the <If> that reads %{CONN_REMOTE_ADDR}) refuses any request whose TCP peer is neither
one of Cloudflare's published ranges nor the server's own address, except /.well-known/acme-challenge/.

A launcher since 2026-11-26. The gate was written here on 2026-11-23; when freshthaplatform.com and freshthaband.com
got the same rule it moved, whole, to the fleet's one implementation (~/.claude/skills/prod-deploy/
cloudflare_only.py) so three sites cannot drift into three gates. What it asks and why each answer can fail is
written out there. This repo carries only its facts, in tests/cloudflare-only.json.
"""
import os
import pathlib
import runpy
import sys

HERE = pathlib.Path(__file__).resolve().parent
ENGINE = pathlib.Path(os.path.expanduser("~")) / ".claude" / "skills" / "prod-deploy" / "cloudflare_only.py"
if not ENGINE.exists():
    print(f"cloudflare-only: the gate is not at {ENGINE}. UNCHECKED, not clean.")
    sys.exit(2)
rest = sys.argv[1:]
sys.argv = [str(ENGINE)] + (["--selftest"] if "--selftest" in rest else ["--site", str(HERE / "cloudflare-only.json")] + rest)
runpy.run_path(str(ENGINE), run_name="__main__")
