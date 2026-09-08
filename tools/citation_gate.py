#!/usr/bin/env python3
"""citation_gate — no lesson ships a citation the manifesto's own worklists condemn.

The Codex lessons quote the manifesto VERBATIM. The manifesto's repair worklists
say plainly that chapters 1-8 still contain fabricated and impossible citations,
and that Volume 2 carries re-dated ones — and the Volume 2 worklist names the
exposure itself: "7 canon lessons (offda9.com/curriculum/theorist/) quote Vol 2
passages VERBATIM." Nothing checked whether a condemned citation had already
been published. On 2026-09-08 it had not, and this exists so that stays true.

Three checks:

  1. FLAGGED — a citation the worklists condemn must not appear in any lesson.
     Matched on name AND year, because the year is what makes most of them
     false: Meadows (2022) is a dead-author re-date, Meadows (2008) is
     Thinking in Systems, published posthumously and entirely real. A rule
     that only knew the death year would condemn the real book, and a gate
     that cries wolf gets switched off.
  2. IMPOSSIBLE — any citation dated in the future.
  3. RATCHET — every Author (YYYY) named anywhere in the worklists must be
     classified in the config as flagged or cleared. A newly condemned source
     therefore fails the gate until someone looks at it, which is what keeps
     the list from going stale the way a hand-maintained list always does.

What it does NOT cover, deliberately: a real source attached to a false claim.
Fung & Warren (2011) is a genuine paper cited for a project that does not
exist; no citation checker can see that. Only a reader can.

    python tools/citation_gate.py                # check
    python tools/citation_gate.py --selftest     # prove it can fail
    python tools/citation_gate.py --json

Config: .claude/citation-gate.json. Worklists live under MANIFESTO_DIR
(default C:\\Users\\Rage\\Documents\\The OD9 Manifesto); without them the
ratchet is skipped, loudly, and the flagged check still runs.
Exit 0 clean, 1 findings, 2 cannot run.
"""
from __future__ import annotations

import argparse
import datetime
import glob
import html
import json
import os
import re
import sys
from pathlib import Path

sys.stdout.reconfigure(encoding="utf-8", errors="replace")

ROOT = Path(__file__).resolve().parents[1]
CONFIG = ROOT / ".claude" / "citation-gate.json"
MANIFESTO = Path(os.environ.get("MANIFESTO_DIR", r"C:\Users\Rage\Documents\The OD9 Manifesto"))
CITE_RE = re.compile(r"\b([A-Z][A-Za-z'\u2019\-]{2,})\s*(?:et al\.?)?\s*\((\d{4})\)")


def cites_in(text: str) -> list[tuple[str, int, int]]:
    """[(name, year, offset)] for every Author (YYYY) in the text, entities decoded."""
    t = html.unescape(text)
    return [(m.group(1), int(m.group(2)), m.start()) for m in CITE_RE.finditer(t)]


def lesson_files(patterns: list[str]) -> list[Path]:
    out: list[Path] = []
    for p in patterns:
        out += [Path(f) for f in glob.glob(str(ROOT / p)) if "_codex" not in f]
    return sorted(set(out))


def run(cfg: dict, extra_text: dict[Path, str] | None = None) -> dict:
    flagged = {(f["name"], int(f["year"])): f.get("why", "") for f in cfg.get("flagged", [])}
    cleared_names = {c["name"] for c in cfg.get("cleared", [])}
    flagged_names = {f["name"] for f in cfg.get("flagged", [])}
    year_now = datetime.date.today().year

    findings: list[dict] = []
    scanned = 0
    for f in lesson_files(cfg.get("scan", [])):
        text = (extra_text or {}).get(f) or f.read_text(encoding="utf-8", errors="replace")
        scanned += 1
        for name, year, off in cites_in(text):
            rel = f.relative_to(ROOT).as_posix()
            if (name, year) in flagged:
                findings.append({"kind": "flagged", "file": rel, "cite": f"{name} ({year})",
                                 "why": flagged[(name, year)]})
            elif year > year_now:
                findings.append({"kind": "impossible", "file": rel, "cite": f"{name} ({year})",
                                 "why": f"dated {year}, which has not happened"})

    unclassified: list[str] = []
    worklists = [MANIFESTO / w for w in cfg.get("worklists", [])]
    present = [w for w in worklists if w.is_file()]
    if present:
        seen: set[str] = set()
        for w in present:
            for name, _year, _off in cites_in(w.read_text(encoding="utf-8", errors="replace")):
                seen.add(name)
        unclassified = sorted(n for n in seen if n not in flagged_names and n not in cleared_names)

    return {"scanned": scanned, "findings": findings, "unclassified": unclassified,
            "worklists_read": [w.name for w in present],
            "worklists_missing": [w.name for w in worklists if not w.is_file()]}


def selftest(cfg: dict) -> int:
    """VACUITY GUARD. A gate that has never caught anything is indistinguishable
    from a clean repo. Inject a condemned citation into a lesson's text in
    memory and require the gate to name it."""
    files = lesson_files(cfg.get("scan", []))
    if not files or not cfg.get("flagged"):
        print("selftest: no lessons or no flagged list — cannot prove anything")
        return 2
    f = files[0]
    bad = cfg["flagged"][0]
    poisoned = f.read_text(encoding="utf-8", errors="replace") + \
        f"\n<!-- selftest {bad['name']} ({bad['year']}) -->\n"
    got = run(cfg, {f: poisoned})
    hit = [x for x in got["findings"] if x["cite"] == f"{bad['name']} ({bad['year']})"]
    print(f"selftest: injected {bad['name']} ({bad['year']}) into {f.name} -> "
          + ("CAUGHT" if hit else "MISSED"))
    future = run(cfg, {f: f.read_text(encoding="utf-8", errors="replace")
                       + "\n<!-- selftest Nostradamus (2099) -->\n"})
    hit2 = [x for x in future["findings"] if x["kind"] == "impossible"]
    print("selftest: injected an impossible date -> " + ("CAUGHT" if hit2 else "MISSED"))
    ok = bool(hit) and bool(hit2)
    print("selftest: " + ("the gate can fail, so a clean run means something" if ok
                          else "THE GATE CANNOT FAIL — it is decoration"))
    return 0 if ok else 1


def main() -> int:
    ap = argparse.ArgumentParser(description=__doc__.split("\n\n")[0])
    ap.add_argument("--json", action="store_true")
    ap.add_argument("--selftest", action="store_true")
    a = ap.parse_args()

    if not CONFIG.is_file():
        print(f"citation-gate: no config at {CONFIG}", file=sys.stderr)
        return 2
    cfg = json.loads(CONFIG.read_text(encoding="utf-8"))

    if a.selftest:
        return selftest(cfg)

    r = run(cfg)
    if a.json:
        print(json.dumps(r, indent=1))
        return 1 if (r["findings"] or r["unclassified"]) else 0

    print(f"citation-gate: {r['scanned']} published lesson(s) vs "
          f"{len(cfg.get('flagged', []))} condemned citation(s)")
    if r["worklists_missing"]:
        print(f"  NOTE: worklist(s) not readable ({', '.join(r['worklists_missing'])}) — "
              "the ratchet is skipped; set MANIFESTO_DIR to enable it")
    for f in r["findings"]:
        print(f"  {f['kind'].upper():<11} {f['file']}  {f['cite']}\n              {f['why']}")
    for n in r["unclassified"]:
        print(f"  UNCLASSIFIED a worklist names {n!r} and .claude/citation-gate.json does not say "
              "whether it is fabricated or real — classify it")
    if not r["findings"] and not r["unclassified"]:
        print("  CLEAN — nothing published carries a condemned citation")
        return 0
    print(f"\n  {len(r['findings'])} finding(s), {len(r['unclassified'])} unclassified")
    return 1


if __name__ == "__main__":
    sys.exit(main())
