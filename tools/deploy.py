#!/usr/bin/env python
"""deploy.py - self-contained prod deploy for the OD9 website (offda9.com).

Canonical source is THIS repo's public/ dir. Maps public/<path> ->
/home/offda9/public_html/<path>. The SSH user (ultimaterage) cannot write
offda9's web root directly, so each file is SCP'd to /tmp staging then
sudo-installed (cp + chown offda9:offda9 + chmod 644) - the same mechanic as
the bot repo's /deploy-web. Every target is backed up first and rolled back on
install failure; prod is curl-verified afterward.

Config: tools/prod-deploy-config.json (gitignored - holds host/user/key path).

Usage:
    python tools/deploy.py --files support.php robots.txt includes/nav.php
    python tools/deploy.py --files support.php --dry-run
    python tools/deploy.py --files sitemap.php --verify-url https://offda9.com/sitemap.xml
"""
from __future__ import annotations
import argparse
import json
import os
import subprocess
import sys
import time
from pathlib import Path

REPO_ROOT = Path(__file__).resolve().parent.parent
CONFIG_PATH = REPO_ROOT / "tools" / "prod-deploy-config.json"

if hasattr(sys.stdout, "reconfigure"):
    try:
        sys.stdout.reconfigure(encoding="utf-8")
    except Exception:
        pass

_C = sys.stdout.isatty()
def green(s):  return f"\033[32m{s}\033[0m" if _C else s
def red(s):    return f"\033[31m{s}\033[0m" if _C else s
def yellow(s): return f"\033[33m{s}\033[0m" if _C else s
def dim(s):    return f"\033[2m{s}\033[0m"  if _C else s


def load_config() -> dict:
    if not CONFIG_PATH.exists():
        sys.exit(red(f"[deploy] missing config: {CONFIG_PATH}"))
    cfg = json.loads(CONFIG_PATH.read_text(encoding="utf-8"))
    for k in ("host", "user", "ssh_key", "web_src", "remote_doc_root", "prod_owner"):
        if not cfg.get(k):
            sys.exit(red(f"[deploy] config missing required key '{k}'"))
    return cfg


def ssh(cfg: dict, cmd: str, timeout: int = 60) -> tuple[int, str, str]:
    full = ["ssh", "-i", cfg["ssh_key"], "-o", "StrictHostKeyChecking=no",
            f'{cfg["user"]}@{cfg["host"]}', cmd]
    res = subprocess.run(full, capture_output=True, text=True, timeout=timeout)
    return res.returncode, res.stdout.strip(), res.stderr.strip()


def scp(cfg: dict, local: Path, remote: str, timeout: int = 180) -> tuple[int, str]:
    full = ["scp", "-i", cfg["ssh_key"], "-o", "StrictHostKeyChecking=no",
            str(local), f'{cfg["user"]}@{cfg["host"]}:{remote}']
    res = subprocess.run(full, capture_output=True, text=True, timeout=timeout)
    return res.returncode, (res.stdout + res.stderr).strip()


def prod_path(cfg: dict, rel: str) -> str:
    return f'{cfg["remote_doc_root"]}/{rel}'


def staged_name(rel: str) -> str:
    # Flatten nested paths into a single staging filename (no collisions).
    return rel.replace("/", "__")


def _site_base(url: str) -> str:
    from urllib.parse import urlsplit
    p = urlsplit(url)
    return f"{p.scheme}://{p.netloc}"


def opcache_reset(cfg: dict, verify_url: str) -> None:
    """Clear the web FPM pool's opcache so deployed edits actually take effect.
    A file edit is invisible to PHP-FPM until opcache_reset() runs IN that pool,
    so drop a one-shot resetter into the docroot, hit it over HTTPS, remove it.
    Absence of this step caused hours of 'deployed but prod didn't change'."""
    tmp = f"{cfg['remote_doc_root']}/_ocreset.php"
    php = '<?php echo function_exists("opcache_reset")&&opcache_reset()?"OK":"NONE";'
    ssh(cfg, f"printf '%s' '{php}' | sudo tee '{tmp}' >/dev/null && "
             f"sudo chown {cfg['prod_owner']} '{tmp}'")
    url = f"{_site_base(verify_url)}/_ocreset.php?x={int(time.time())}"
    r = subprocess.run(["curl", "-sS", "-m", "15", url], capture_output=True, text=True)
    ssh(cfg, f"sudo rm -f '{tmp}'")
    print(green(f"[deploy] opcache reset -> {r.stdout.strip() or '(no output)'}"))


def cf_purge(cfg: dict) -> None:
    """Purge Cloudflare so newly-deployed static assets aren't masked by a stale
    edge cache - including a previously-cached 404 (the od9.css trap)."""
    token, zone = cfg.get("cf_api_token"), cfg.get("cf_zone_id")
    if not token or not zone:
        print(dim("[deploy] cloudflare purge skipped (set cf_api_token + cf_zone_id in config)"))
        return
    r = subprocess.run(["curl", "-sS", "-m", "20", "-X", "POST",
                        "-H", f"Authorization: Bearer {token}",
                        "-H", "Content-Type: application/json",
                        "--data", '{"purge_everything":true}',
                        f"https://api.cloudflare.com/client/v4/zones/{zone}/purge_cache"],
                       capture_output=True, text=True)
    ok = '"success":true' in r.stdout
    print((green if ok else yellow)(
        f"[deploy] cloudflare purge: {'OK' if ok else 'FAILED ' + r.stdout[:140]}"))


def main() -> int:
    ap = argparse.ArgumentParser()
    ap.add_argument("--files", nargs="+", required=True,
                    help="paths relative to public/ (e.g. support.php includes/nav.php library/.htaccess)")
    ap.add_argument("--dry-run", action="store_true", help="print the plan, touch nothing")
    ap.add_argument("--skip-lint", action="store_true", help="skip the local php -l gate")
    ap.add_argument("--verify-url", default=None, help="URL to curl after deploy (default from config)")
    args = ap.parse_args()

    cfg = load_config()
    web_src = REPO_ROOT / cfg["web_src"]
    verify_url = args.verify_url or cfg.get("verify_url", "https://offda9.com/")
    never = set(cfg.get("never_deploy", []))

    files = [f.replace("\\", "/").lstrip("/") for f in args.files]
    blocked = [f for f in files if f in never or ".env" in f or "sp-credentials" in f]
    files = [f for f in files if f not in blocked]
    if not files:
        sys.exit(red("[deploy] no deployable files (all excluded)"))

    missing = [f for f in files if not (web_src / f).exists()]
    if missing:
        sys.exit(red(f"[deploy] FATAL - missing in {cfg['web_src']}/: {missing}"))

    print(f"[deploy] {'=' * 64}")
    print(f"[deploy] target: {cfg['user']}@{cfg['host']}:{cfg['remote_doc_root']}")
    print(f"[deploy] files ({len(files)}):")
    for f in files:
        print(f"   {cfg['web_src']}/{f}  ->  {prod_path(cfg, f)}")
    if blocked:
        print(yellow(f"[deploy] EXCLUDED (never_deploy): {', '.join(blocked)}"))

    # Local PHP syntax gate.
    if not args.skip_lint:
        php_bin = os.environ.get("PHP_BIN", "C:/xampp/php/php.exe")
        php_files = [f for f in files if f.endswith(".php")]
        for f in php_files:
            r = subprocess.run([php_bin, "-l", str(web_src / f)],
                               capture_output=True, text=True)
            if r.returncode != 0:
                sys.exit(red(f"[deploy] php -l FAILED for {f}:\n{r.stdout}{r.stderr}"))
        print(green(f"[deploy] php -l clean ({len(php_files)} PHP file(s))"))

    if args.dry_run:
        print(yellow("[deploy] DRY-RUN - nothing sent to prod"))
        return 0

    ts = time.strftime("%Y%m%d-%H%M%S")
    backup = f"/tmp/od9web-backup-{ts}"
    staging = f"/tmp/od9web-staging-{ts}"
    rc, _, err = ssh(cfg, f"mkdir -p {backup} {staging}")
    if rc != 0:
        sys.exit(red(f"[deploy] could not create /tmp dirs (SSH ok?): {err}"))

    # Backup existing targets.
    for f in files:
        p = prod_path(cfg, f)
        ssh(cfg, f"if [ -f '{p}' ]; then sudo cp -p '{p}' '{backup}/{staged_name(f)}'; fi")
    print(dim(f"[deploy] backups -> {backup}"))

    # Stage via SCP.
    for f in files:
        rc, msg = scp(cfg, web_src / f, f"{staging}/{staged_name(f)}")
        if rc != 0:
            ssh(cfg, f"rm -rf {staging}")
            sys.exit(red(f"[deploy] SCP FAILED for {f}: {msg}"))
    print(green(f"[deploy] staged {len(files)} file(s) -> {staging}"))

    # Install (sudo cp + chown + chmod) per file.
    failures = []
    for f in files:
        p = prod_path(cfg, f)
        st = f"{staging}/{staged_name(f)}"
        parent = str(Path(p).parent).replace("\\", "/")
        cmd = (f"sudo mkdir -p '{parent}' && sudo cp '{st}' '{p}' "
               f"&& sudo chown {cfg['prod_owner']} '{p}' && sudo chmod 644 '{p}'")
        rc, _, err = ssh(cfg, cmd)
        if rc != 0:
            failures.append(f)
            print(red(f"   FAIL {f}: {err}"))
        else:
            print(green(f"   OK   {f}"))

    if failures:
        print(red(f"[deploy] {len(failures)} install failure(s) - rolling back"))
        for f in failures:
            p = prod_path(cfg, f)
            ssh(cfg, f"if [ -f '{backup}/{staged_name(f)}' ]; then "
                     f"sudo cp -p '{backup}/{staged_name(f)}' '{p}'; "
                     f"sudo chown {cfg['prod_owner']} '{p}'; fi")
        ssh(cfg, f"rm -rf {staging}")
        sys.exit(red(f"[deploy] FAILED + rolled back. backups kept: {backup}"))

    # Make the new bytes actually take effect, then bust the edge cache.
    opcache_reset(cfg, verify_url)

    # Prod sanity curl.
    r = subprocess.run(["curl", "-sS", "-o", os.devnull, "-w", "%{http_code}", "-L", verify_url],
                       capture_output=True, text=True, timeout=20)
    code = r.stdout.strip()
    if code != "200":
        ssh(cfg, f"rm -rf {staging}")
        sys.exit(red(f"[deploy] prod curl {verify_url} -> {code} (not 200). backups: {backup}"))
    print(green(f"[deploy] prod verify {verify_url} -> 200"))
    cf_purge(cfg)
    ssh(cfg, f"rm -rf {staging}")

    log = REPO_ROOT / "tools" / "deploys.log"
    try:
        with open(log, "a", encoding="utf-8") as fh:
            fh.write(f"{time.strftime('%Y-%m-%dT%H:%M:%S%z')}\tOK\t{len(files)}\t{','.join(files)}\n")
    except Exception:
        pass

    print(green(f"[deploy] PASS - {len(files)} file(s) live. backups: {backup} (7-day window)"))
    return 0


if __name__ == "__main__":
    sys.exit(main())
