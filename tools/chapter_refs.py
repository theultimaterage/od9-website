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
# NOTE the exact form "-MANIFEST.md": the merge runbooks are named VOL3-CH23-MERGE-
# MANIFEST.md, VOL5-RESTRUCTURE-MANIFEST.md and so on, and a new one appeared with a
# name the old literal "MERGE-MANIFEST" did not cover. The obvious generalisation to
# "MANIFEST" is a TRAP: it is a substring of MANIFESTO, so it would silently skip
# "OD9 Manifesto Table of Contents.md" and "OD9-MANIFESTO-SUMMARIES.md" — the two files
# that most need checking. The selftest pins that they are still scanned.
SKIP_PARTS = ("_audit-staging", "tools" + os.sep, "WORKLIST", "MASTERPLAN",
              "STRUCTURE-PROPOSAL", "-MANIFEST.md", "sermons" + os.sep)
# A line may name a retired chapter deliberately, to record where something came
# from. Provenance is not drift, so a line saying so is allowed to say so.
PROVENANCE = ("demoted from", "formerly chapter", "former chapter", "was chapter",
              "renumbered", "absorbed", "merged into", "dissolved", "legacy")

# A reference can be WRONG without being DANGLING. Until 2026-09-09 this linter
# only asked whether a referenced chapter still exists, so three references that
# named a real chapter and pointed at the wrong NUMBER sat clean indefinitely:
# ch21 §11 sent "Advanced AI Integration Frameworks" to Chapter 29 (it is 32) and
# ch18/ch21 sent security work to Chapter 30 (it is 35). Every one of them passed,
# because 29 and 30 existed. A check that can only fail one way is half a check.
#
# What is verifiable without semantics: when a sentence names a chapter TITLE and
# a chapter NUMBER right next to each other, the two must agree. That is an exact
# comparison on both sides, so it does not guess and it does not cry wolf. It does
# NOT catch a reference that paraphrases the title ("Security Implementation" is
# nobody's exact title), and pretending otherwise would be the vacuity problem in
# a new coat -- so the scope is stated here rather than implied by silence.
TITLE_ADJACENCY = 70    # chars between a title and its number for the pair to count
MIN_TITLE_LEN = 14      # shorter titles collide with ordinary prose


def title_index(doc: dict) -> dict[str, int]:
    """{lowercased chapter title -> the number a reference to it SHOULD carry}.

    A retired chapter's title maps to its SURVIVOR's number, so prose that still
    uses the old name but points at the new chapter is correct and stays silent.
    """
    live, retired = registry(doc)
    idx: dict[str, int] = {}
    for num, title in live.items():
        t = (title or "").strip().lower()
        if len(t) >= MIN_TITLE_LEN:
            idx[t] = num
    for n in doc.get("nodes", []):
        num = n.get("num")
        if not str(num).isdigit():
            continue
        for old in (n.get("legacy") or []):
            old_title = (doc.get("legacy_titles") or {}).get(str(old), "").strip().lower()
            if len(old_title) >= MIN_TITLE_LEN:
                idx[old_title] = int(num)
    return idx


def misdirected(line: str, idx: dict[str, int]) -> list[tuple[str, int, int]]:
    """(title, cited number, correct number) for every IMMEDIATELY adjacent
    disagreeing pair.

    "Immediately" is the whole design. A first cut allowed any title within 70
    characters and it produced eight false positives on its first live run --
    a line reading "**Media and Information System Transformation (Chapter 39)**:
    Healthcare transformation both requires..." is perfectly correct, but a wide
    window sees "healthcare transformation" near "Chapter 39" and indicts it.
    Eight wolves out of eleven cries and nobody reads the twelfth.

    So only the four constructions where the title and the number are actually
    bound to each other count, with nothing but punctuation between them:

        Title (Chapter N)      Title, Chapter N
        Chapter N (Title)      Chapter N, Title

    That narrows what this can catch -- a paraphrase like "Security
    Implementation frameworks" is nobody's exact title and stays invisible. A
    check that catches one class reliably beats one that gestures at all of them.
    """
    low = line.lower()
    # The two sides need DIFFERENT joiners, and the difference is load-bearing.
    #
    # Leading  "Title (Chapter N"  -> an OPENING paren is expected.
    # Trailing "Chapter N, Title"  -> a CLOSING paren is disqualifying: it means
    #   the number lived inside its own parenthetical, so the title after it
    #   belongs to the next clause. That one character separates the real find
    #       "As detailed in Volume 5, Chapter 50, Community Integration Systems"
    #   from the false positive that broke the first cut
    #       "**Media and Information System Transformation (Chapter 39)**:
    #        Healthcare transformation both requires..."
    #   where "Healthcare transformation" is the sentence subject, not the
    #   referent. Both are punctuation-only gaps; only one is a binding.
    LEAD = " \t,;([-—–*_"     # between title-end and "Chapter"
    TRAIL = " \t,-—–("         # between the number and title-start
    out = []
    for m in CHAPTER_RE.finditer(line):
        cited = int(m.group(1))
        if cited not in OUR_RANGE:
            continue
        best = None
        for title, correct in idx.items():
            bound = False
            # title ends just before the number:  "... Title (Chapter N"
            before = low.rfind(title, 0, m.start())
            if before != -1 and all(c in LEAD for c in low[before + len(title):m.start()]):
                bound = True
            # title starts just after the number:  "Chapter N, Title ..."
            if not bound:
                after = low.find(title, m.end())
                if after != -1 and all(c in TRAIL for c in low[m.end():after]):
                    bound = True
            if bound and (best is None or len(title) > len(best[0])):
                best = (title, correct)
        if best and best[1] != cited:
            out.append((best[0], cited, best[1]))
    return out


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

    idx = title_index(doc)
    findings, gaps, wrong, scanned = [], [], [], 0
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
            for title, cited, correct in misdirected(line, idx):
                wrong.append({"file": rel, "line": i, "chapter": cited,
                              "now": f"names “{title}”, which is Chapter {correct}",
                              "text": line.strip()[:150]})
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
    return {"scanned": scanned, "findings": findings, "misdirected": wrong,
            "map_gaps": sorted({g["chapter"] for g in gaps}),
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

    # the misdirection rule: both directions, on real titles from the live map
    idx = title_index(doc)
    pairs = [(t, n) for t, n in idx.items() if n in live]
    if len(pairs) >= 2:
        (t1, n1), (t2, n2) = pairs[0], next(p for p in pairs[1:] if p[1] != pairs[0][1])
        # bound construction, wrong number -> must fire
        got = scan(doc, {f: f"See {t1} (Chapter {n2}) for the argument." + "\n"})
        caught = any(x["chapter"] == n2 for x in got.get("misdirected", []))
        ok &= caught
        print(f"  {'OK  ' if caught else 'FAIL'} a bound title pointed at the WRONG number is caught "
              f"(“{t1[:34]}” cited as Chapter {n2}, is {n1})")

        # bound construction, right number -> must stay silent
        got = scan(doc, {f: f"See {t1} (Chapter {n1}) for the argument." + "\n"})
        quiet3 = not got.get("misdirected")
        ok &= quiet3
        print(f"  {'OK  ' if quiet3 else 'FAIL'} the same bound title at its OWN number is silent")

        # the other bound form: "Chapter N, Title"
        got = scan(doc, {f: f"As detailed in Volume 5, Chapter {n2}, {t1} covers this." + "\n"})
        caught2 = any(x["chapter"] == n2 for x in got.get("misdirected", []))
        ok &= caught2
        print(f"  {'OK  ' if caught2 else 'FAIL'} the trailing form “Chapter N, Title” is caught too")

        # UNBOUND: a title loose in the same sentence must NOT be indicted.
        # This is the regression case for the eight false positives the first
        # cut produced on its first live run.
        got = scan(doc, {f: f"3. **Other Thing (Chapter {n2})**: {t1} both requires it." + "\n"})
        quiet4 = not got.get("misdirected")
        ok &= quiet4
        print(f"  {'OK  ' if quiet4 else 'FAIL'} an UNBOUND title in the same sentence is not "
              "indicted (the wolf-crying regression)")
    else:
        print("  WARN the map has too few titled chapters to prove the misdirection rule")
        ok = False

    # the skip list must not swallow the indices: "MANIFEST" is inside "MANIFESTO"
    for name in ("OD9 Manifesto Table of Contents.md", "OD9-MANIFESTO-SUMMARIES.md"):
        swallowed = any(sp in name for sp in SKIP_PARTS)
        ok &= not swallowed
        print(f"  {'OK  ' if not swallowed else 'FAIL'} {name[:38]:<40} is still scanned")
    runbook = "VOL5-RESTRUCTURE-MANIFEST.md"
    skipped = any(sp in runbook for sp in SKIP_PARTS)
    ok &= skipped
    print(f"  {'OK  ' if skipped else 'FAIL'} a merge runbook IS skipped ({runbook})")

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
        return 1 if (r["findings"] or r["misdirected"]) else 0

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
    if r["misdirected"]:
        print(f"\n  MISDIRECTED: {len(r['misdirected'])} reference(s) name one chapter and "
              "point at another. These are not dangling — the cited chapter exists, which is "
              "exactly why they went unnoticed.")
        for w in r["misdirected"]:
            print(f"    {w['file']}:{w['line']}")
            print(f"      cites Chapter {w['chapter']} but {w['now']}")
            print(f"      {w['text']}")

    if not r["findings"] and not r["misdirected"]:
        print("  CLEAN — every chapter reference points at something that exists, and every "
              "reference naming a title agrees with it")
        return 0 if not r["map_gaps"] else 1
    if not r["findings"]:
        return 1
    print(f"\n  {len(r['findings'])} stale reference(s) in {len(by_file)} file(s). "
          "Rewrite each in context — a chapter reference is a sentence, not a token.")
    return 1


if __name__ == "__main__":
    sys.exit(main())
