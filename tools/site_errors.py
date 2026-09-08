#!/usr/bin/env python3
"""site_errors — what broke in real visitors' browsers, per page.

api/v1/error-beacon.php appends one JSON line per fault to
/home/offda9/site-errors/YYYY-MM-DD.jsonl, outside the docroot. This reads them
over ssh and groups by page and message, because the useful question is never
"how many errors" but "which page is broken for whom, and since when".

The Atlas's beacon proved the shape: 87 identical TypeErrors from a single phone
session, which no visitor would ever have reported, revealed that a pinch
gesture froze the map. Every other page had that blind spot until 2026-09-08.

    python tools/site_errors.py               # last 7 days
    python tools/site_errors.py --days 30
    python tools/site_errors.py --page /atlas
    python tools/site_errors.py --local DIR   # read a local dir instead (tests)

No IP is stored, so a "session" here is one browser tab.
"""
from __future__ import annotations

import argparse
import collections
import datetime as dt
import json
import subprocess
import sys
from pathlib import Path

sys.stdout.reconfigure(encoding="utf-8", errors="replace")

SSH = ["wsl", "bash", "-lc"]
REMOTE = ("ssh -o BatchMode=yes -o ConnectTimeout=20 "
          "-i ~/.ssh/id_rsa_automation offda9@72.167.54.213")


def fetch(days: int) -> list[dict]:
    since = (dt.datetime.now(dt.timezone.utc).date() - dt.timedelta(days=days - 1)).isoformat()
    script = ('cd ~/site-errors 2>/dev/null || { echo "no ~/site-errors dir yet — '
              'nothing has failed, or nothing has deployed" >&2; exit 0; }\n'
              'for f in *.jsonl; do [ -e $f ] || continue; d=${f%.jsonl}; '
              '[[ $d < "' + since + '" ]] && continue; cat $f; done\n')
    # bytes, not text=True: Windows text-mode stdin turns LF into CRLF and the
    # remote bash dies on "done\r" (the crontab CRLF class)
    r = subprocess.run(SSH + [f"{REMOTE} bash -s"], input=script.encode("utf-8"),
                       capture_output=True, timeout=120)
    err = r.stderr.decode("utf-8", "replace").strip()
    if r.returncode != 0:
        sys.exit(f"ssh failed: {err[-300:]}")
    if err:
        print(err, file=sys.stderr)
    return parse(r.stdout.decode("utf-8", "replace"))


def parse(text: str) -> list[dict]:
    out = []
    for line in text.splitlines():
        line = line.strip()
        if line:
            try:
                out.append(json.loads(line))
            except json.JSONDecodeError:
                continue
    return out


def main() -> int:
    ap = argparse.ArgumentParser(description=__doc__.split("\n\n")[0])
    ap.add_argument("--days", type=int, default=7)
    ap.add_argument("--page", help="only this page path")
    ap.add_argument("--local", type=Path, help="read a local dir of .jsonl instead of ssh")
    a = ap.parse_args()

    rows = ([r for p in sorted(a.local.glob("*.jsonl")) for r in parse(p.read_text(encoding="utf-8"))]
            if a.local else fetch(a.days))
    if a.page:
        rows = [r for r in rows if (r.get("page") or "").startswith(a.page)]
    if not rows:
        print("[site-errors] nothing reported — no page threw in a real browser")
        return 0

    by_page = collections.Counter(r.get("page", "?") for r in rows)
    sessions = {r.get("sid") for r in rows if r.get("sid")}
    print(f"[site-errors] {len(rows)} fault(s) across {len(by_page)} page(s), "
          f"{len(sessions)} browser session(s)\n")
    for page, n in by_page.most_common():
        here = [r for r in rows if r.get("page") == page]
        vps = collections.Counter(r.get("vp", "?") for r in here)
        print(f"  {page}   {n} fault(s)  {dict(vps)}")
        grouped = collections.Counter((r.get("msg", "?"), r.get("src", ""), r.get("line", 0)) for r in here)
        for (msg, src, line), c in grouped.most_common(5):
            when = sorted(r.get("at", "") for r in here if r.get("msg") == msg)
            print(f"     x{c:<4} {msg[:88]}")
            print(f"           {src}:{line}   first {when[0][:16] if when else '?'}  last {when[-1][:16] if when else '?'}")
        print()
    return 0


if __name__ == "__main__":
    sys.exit(main())
