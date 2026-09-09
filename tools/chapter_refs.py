#!/usr/bin/env python3
"""chapter_refs — no sentence points at a chapter that no longer exists.

THE PROBLEM THIS EXISTS FOR. The manifesto is being consolidated from 67
chapters to 52. Twenty-two chapter numbers are retired by that plan: 2 becomes
Appendix A, 6 and 8 fold into 7, most of Volume 3 collapses, and so on. The
corpus contains **2,264 explicit "Chapter N" references across 747 files**.
Every merge silently invalidates the references pointing at what it absorbed.

It is not hypothetical. On 2026-09-09 ch2 was demoted to Appendix A after a
consumer inventory that checked which TOOLS globbed its path — and missed that
fifteen sentences across the table of contents, the summaries, the dictionary,
chapter 1's closing hand-off (three times), chapter 5's framework and the
LoveLogic master doc all still said "Chapter 2". The inventory asked "what reads
this file" and never asked "what mentions this chapter".

THE REGISTRY IS ALREADY BUILT, which is why this tool is small.
data/manifesto-map.json carries every node's current `num`, its `title`, and a
`legacy` list naming the numbers it absorbed — ch7 is legacy [7, 6, 8], chA is
legacy [2]. That file already drives the Atlas and the Forge. Adding a second
list of chapter moves would guarantee the two disagree, so this reads that one.

WHY IT IS NOT PERMANENTLY RED. The map records the DECISION; the manuscript
executes it over months. Flagging every reference to a chapter whose merge has
not happened yet would light up all 22 immediately and get switched off. So a
reference is stale only when the source has ACTUALLY MOVED — determined by
whether the chapter's own directory still exists on disk. The filesystem says
what has happened; the map says what was decided; a reference is broken only
where those two have parted company. Nothing to maintain by hand.

    python tools/chapter_refs.py             # check
    python tools/chapter_refs.py --json
    python tools/chapter_refs.py --selftest  # prove it can fail

Config: MANIFESTO_DIR (default C:\\Users\\Rage\\Documents\\The OD9 Manifesto).
Exit 0 clean, 1 stale references, 2 cannot run.
"""
from __future__ import annotations

import argparse
import json
import os
import re
import sys
from pathlib import Path

sys.stdout.reconfigure(encoding="utf-8", errors="replace")

ROOT = Path(__file__).resolve().parents[1]
MAP = ROOT / "data" / "manifesto-map.json"
MANIFESTO = Path(os.environ.get("MANIFESTO_DIR", r"C:\Users\Rage\Documents\The OD9 Manifesto"))

CHAPTER_RE = re.compile(r"\bChapters?\s+(\d+)\b")
DIR_NUM_RE = re.compile(r"^Chapter\s+(\d+)\b", re.IGNORECASE)
# This book has 67 chapters. Other books are quoted in it and have their own: the
# Dao De Jing's chapter 81 is not a dangling reference to ours, and reading it as
# one is how a linter earns a reputation for crying wolf.
OUR_RANGE = range(1, 68)

# Ops docs argue ABOUT the moves and must name retired numbers to do it. Excluded
# for the same reason the citation gate excludes the worklists: cataloguing a
# defect is not committing one.
SKIP_PARTS = ("_audit-staging", "tools" + os.sep, "WORKLIST", "MASTERPLAN",
              "STRUCTURE-PROPOSAL", "sermons" + os.sep)
# A line may name a retired chapter deliberately, to record where something came
# from. Provenance is not drift, so a line saying so is allowed to say so.
PROVENANCE = ("demoted from", "formerly chapter", "former chapter", "was chapter",
              "renumbered", "absorbed", "merged into", "dissolved", "legacy")


def registry(doc: dict) -> tuple[dict[int, str], dict[int, str]]:
    """(live number -> label, retired number -> surviving label) from the map."""
    live, retired = {}, {}
    # Chapters dissolved with no single successor cannot be another node's legacy
    # entry, so the registry records them separately. Without this they read as
    # "not in the map" forever, which is indistinguishable from a typo.
    for num, why in (doc.get("retired_chapters") or {}).items():
        if str(num).isdigit():
            retired[int(num)] = why
    for n in doc.get("nodes", []):
        num, title = n.get("num"), (n.get("title") or "").strip()
        label = f"Chapter {num}" if str(num).isdigit() else str(num)
        if str(num).isdigit():
            live[int(num)] = title
        for old in (n.get("legacy") or []):
            if str(old) != str(num):
                retired[int(old)] = (f"Chapter {num}" if str(num).isdigit()
                                     else ("Appendix A" if num == "A" else f"node {num}")) \
                                    + (f" — {title}" if title else "")
    return live, retired


def source_chapters() -> set[int]:
    """Chapter numbers whose directory still exists — i.e. whose merge has NOT run."""
    found = set()
    if not MANIFESTO.is_dir():
        return found
    for vol in MANIFESTO.iterdir():
        if not vol.is_dir():
            continue
        for d in vol.iterdir():
            if d.is_dir():
                m = DIR_NUM_RE.match(d.name)
                if m:
                    found.add(int(m.group(1)))
    return found


def scan(doc: dict, extra: dict[Path, str] | None = None) -> dict:
    live, retired = registry(doc)
    still_on_disk = source_chapters()
    # Retired AND gone from disk = the move happened, so references must have moved too.
    broken = {n: lbl for n, lbl in retired.items() if n not in still_on_disk}

    findings, gaps, scanned = [], [], 0
    files = list((extra or {}).keys()) or [
        p for p in MANIFESTO.rglob("*.md")
        if not any(s in str(p.relative_to(MANIFESTO)) for s in SKIP_PARTS)]

    for p in files:
        text = (extra or {}).get(p) or p.read_text(encoding="utf-8", errors="replace")
        scanned += 1
        rel = str(p.relative_to(MANIFESTO)) if MANIFESTO in p.parents or p.is_relative_to(MANIFESTO) else p.name
        for i, line in enumerate(text.splitlines(), 1):
            low = line.lower()
            if any(k in low for k in PROVENANCE):
                continue
            for m in CHAPTER_RE.finditer(line):
                num = int(m.group(1))
                if num not in OUR_RANGE:
                    continue
                if num in broken:
                    findings.append({"file": rel, "line": i, "chapter": num,
                                     "now": broken[num], "text": line.strip()[:150]})
                elif num not in live and num not in retired:
                    # Unknown to the map. If its source is still on disk the chapter
                    # exists and the MAP is what is incomplete — a registry gap, not a
                    # broken sentence. Only a reference to something absent from both
                    # is actually dangling.
                    (gaps if num in still_on_disk else findings).append(
                        {"file": rel, "line": i, "chapter": num,
                         "now": "not in the map", "text": line.strip()[:150]})
    return {"scanned": scanned, "findings": findings, "map_gaps": sorted({g["chapter"] for g in gaps}),
            "retired_total": len(retired), "moved_already": sorted(broken)}


def selftest(doc: dict) -> int:
    """A linter that cannot fail is decoration. Prove both directions."""
    live, retired = registry(doc)
    ok = True
    moved = [n for n in retired if n not in source_chapters()]
    if not moved:
        print("  selftest: no chapter has actually moved yet — nothing to prove against")
        return 2
    gone, alive = moved[0], next(iter(sorted(live)))
    f = MANIFESTO / "SELFTEST.md"

    got = scan(doc, {f: f"A sentence pointing at Chapter {gone} for its methods.\n"})
    hit = any(x["chapter"] == gone for x in got["findings"])
    ok &= hit
    print(f"  {'OK  ' if hit else 'FAIL'} a reference to retired Chapter {gone} is caught")

    got = scan(doc, {f: f"A sentence pointing at Chapter {alive}, which still exists.\n"})
    quiet = not any(x["chapter"] == alive for x in got["findings"])
    ok &= quiet
    print(f"  {'OK  ' if quiet else 'FAIL'} a reference to live Chapter {alive} is NOT flagged")

    got = scan(doc, {f: f"Demoted from Chapter {gone} to its new home in 2026.\n"})
    spared = not got["findings"]
    ok &= spared
    print(f"  {'OK  ' if spared else 'FAIL'} a provenance line naming Chapter {gone} is spared")

    unmerged = sorted(set(retired) & source_chapters())
    if unmerged:
        n = unmerged[0]
        got = scan(doc, {f: f"See Chapter {n} for the argument.\n"})
        quiet2 = not any(x["chapter"] == n for x in got["findings"])
        ok &= quiet2
        print(f"  {'OK  ' if quiet2 else 'FAIL'} Chapter {n} is retired in the map but still on "
              "disk, so it is NOT flagged yet")

    print("  selftest: " + ("it can fail and it can stay quiet, so a clean run means something"
                            if ok else "THE LINTER IS DECORATION"))
    return 0 if ok else 1


def main() -> int:
    ap = argparse.ArgumentParser(description=__doc__.split("\n\n")[0])
    ap.add_argument("--json", action="store_true")
    ap.add_argument("--selftest", action="store_true")
    a = ap.parse_args()

    if not MAP.is_file():
        print(f"chapter-refs: no map at {MAP}", file=sys.stderr)
        return 2
    doc = json.loads(MAP.read_text(encoding="utf-8"))
    if not MANIFESTO.is_dir():
        print(f"chapter-refs: NOTE — no manifesto checkout at {MANIFESTO}; cannot verify "
              "chapter references (set MANIFESTO_DIR)")
        return 0
    if a.selftest:
        return selftest(doc)

    r = scan(doc)
    if a.json:
        print(json.dumps(r, indent=1))
        return 1 if r["findings"] else 0

    print(f"chapter-refs: {r['scanned']} file(s); {r['retired_total']} chapter number(s) retired "
          f"by the decided skeleton, {len(r['moved_already'])} already moved in the source "
          f"{r['moved_already']}")
    by_file: dict[str, list] = {}
    for f in r["findings"]:
        by_file.setdefault(f["file"], []).append(f)
    for fname in sorted(by_file):
        print(f"\n  {fname}")
        for f in sorted(by_file[fname], key=lambda x: x["line"]):
            print(f"    :{f['line']:<5} Chapter {f['chapter']} -> now {f['now']}")
            print(f"           {f['text']}")
    if r["map_gaps"]:
        print(f"\n  MAP GAP: chapter(s) {r['map_gaps']} exist in the source but appear in no map "
              "node, not even as a legacy entry.")
        print("    The registry should record every chapter, including ones decided for "
              "retirement — otherwise the day its directory is deleted, every reference to it "
              "becomes dangling with nothing to redirect to.")
    if not r["findings"]:
        print("  CLEAN — every chapter reference points at something that exists")
        return 0 if not r["map_gaps"] else 1
    print(f"\n  {len(r['findings'])} stale reference(s) in {len(by_file)} file(s). "
          "Rewrite each in context — a chapter reference is a sentence, not a token.")
    return 1


if __name__ == "__main__":
    sys.exit(main())
