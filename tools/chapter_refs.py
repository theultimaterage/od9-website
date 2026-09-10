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

# EVERY form a reference takes, not the one that comes to mind first.
#
# This was `\bChapters?\s+(\d+)\b` and therefore could not see `Ch.53` — the form
# the LoveLogic corpus uses almost exclusively, under `CITATIONS: [Ch.53, Ch.6]`
# lines beneath most Q&A entries. The cost was not hypothetical: the ch47 merge
# found 46 instances of `Ch.47` that an inventory searching "chapter 47" had
# missed, wrote "a consumer inventory must search every form a reference takes"
# into the merge manifest, and left the LINTER matching one form. On 2026-09-10
# the live LoveLogic master still carried six `Ch.30` references to a chapter
# retired into ch23 months earlier, and this tool called the corpus CLEAN.
#
# A lesson written in prose and not encoded in the tool is the same defect the
# ecosystem rule names: the rule existed, the gate did not enforce it.
#
# Case-sensitive `Ch` on purpose. Lowercase `chapter53` appears inside every
# section FILENAME (`chapter53-section4 - ...`), and those are paths, not
# sentences; matching them would indict the SUMMARIES' own file listing.
CHAPTER_RE = re.compile(r"\bCh(?:apters?\s+|\.\s*|)(\d+)\b")
DIR_NUM_RE = re.compile(r"^Chapter\s+(\d+)\b", re.IGNORECASE)

# A reference can name a chapter that exists AND a section it no longer has.
# `Ch.7's 'Community Implementation'` resolves -- ch7 is live -- but the
# rewrite collapsed ch7's fifteen sections into six with new titles and folded
# ch8 in, so the quoted section is gone. The number was repointed by the merge
# campaign; the quoted title was not, because nothing was checking it. On
# 2026-09-10 the LoveLogic master carried 97 such quotations, of which roughly
# half named a section the chapter no longer has. The map's per-node `sections`
# list is the oracle, so this is checkable without semantics.
SECTION_QUOTE_RE = re.compile(
    r"\bCh(?:apters?\s+|\.\s*|)(\d+)['’]s\s+['‘“]([^'’”]{6,90})['’”]")
SECTIONS_BASELINE = ROOT / "tools" / "chapter_refs_sections_baseline.json"
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
#
# The allowlist has to speak every form the PATTERN does, or widening the pattern
# turns correct provenance into findings. "absorbs the former Ch. 6" matched
# neither "former chapter" nor "absorbed" and was indicted the moment `Ch.N`
# became visible — the index lines in the SUMMARIES that exist precisely to
# record these merges.
PROVENANCE = ("demoted from", "formerly chapter", "formerly ch.", "former chapter",
              "former ch.", "former ch ", "was chapter", "was ch.", "renumbered",
              "absorbs", "absorbed", "merged into", "dissolved", "legacy")

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


def sections_index(doc: dict) -> dict[int, tuple[str, set[str]]]:
    """{chapter number -> (lowercased chapter title, {lowercased section titles})},
    keyed by EVERY number a node answers to, so a quotation credited to retired
    ch8 is judged against ch7's sections -- where the words would be if the
    section survived the fold."""
    out: dict[int, tuple[str, set[str]]] = {}
    for n in doc.get("nodes", []):
        secs = {s.strip().lower() for s in (n.get("sections") or []) if s}
        title = (n.get("title") or "").strip().lower()
        for old in (n.get("legacy") or []):
            out[int(old)] = (title, secs)
    return out


def stale_sections(line: str, sidx: dict[int, tuple[str, set[str]]]) -> list[tuple[int, str]]:
    """(chapter, quoted title) for every `Ch.N's 'Title'` on the line where
    Title is neither one of Chapter N's sections nor Chapter N's own title.

    Substring in either direction, case-insensitive: LoveLogic quotes
    'Knowledge Transfer Systems' against a section titled 'What We Know About
    Transferring Knowledge' -- and that is a miss, correctly, because the
    quoted words are not the section's name. Only what the map can vouch for
    is accepted; a paraphrase is a finding, and a finding is a sentence to
    rewrite, not a token to swap."""
    out = []
    for m in SECTION_QUOTE_RE.finditer(line):
        num = int(m.group(1))
        if num not in OUR_RANGE or num not in sidx:
            continue
        quoted = m.group(2).strip().lower()
        title, secs = sidx[num]
        if title and (quoted in title or title in quoted):
            continue
        if any(quoted in s or s in quoted for s in secs):
            continue
        out.append((num, m.group(2).strip()))
    return out


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
            # A title can bind on BOTH sides at once, and in a LIST it always
            # does: "Ch.21 Environmental Degradation, Ch.5 compounding crises"
            # binds the title to 21 as a trailing form (correct) and to 5 as a
            # leading form (spurious, because the comma is an item separator and
            # not a binding). Indicting the second one is the wolf-crying
            # regression in a new shape, so: a title that is correctly bound
            # somewhere on this line is not misdirected anywhere on it.
            t = best[0]
            correct_num = best[1]
            correctly_bound = False
            for m2 in CHAPTER_RE.finditer(line):
                if int(m2.group(1)) != correct_num:
                    continue
                b2 = low.rfind(t, 0, m2.start())
                if b2 != -1 and all(c in LEAD for c in low[b2 + len(t):m2.start()]):
                    correctly_bound = True
                    break
                a2 = low.find(t, m2.end())
                if a2 != -1 and all(c in TRAIL for c in low[m2.end():a2]):
                    correctly_bound = True
                    break
            if not correctly_bound:
                out.append((t, cited, correct_num))
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
    sidx = sections_index(doc)
    findings, gaps, wrong, stale, scanned = [], [], [], [], 0
    # .json as well as .md. The manuscript is markdown, but it is not the only
    # thing that cites chapters: pdf1_foundation_data.json is the LoveLogic
    # compendium's question bank and carries 394 `Ch.N` citations straight into
    # a published PDF. Scanning only *.md made the largest single consumer in
    # the corpus invisible — the same defect as matching only one reference
    # FORM, one level up: the pattern missed a spelling, the glob missed a file
    # type. (tools/ is skipped, so this tool's own fixtures stay out of it.)
    files = list((extra or {}).keys()) or [
        p for pat in ("*.md", "*.json") for p in MANIFESTO.rglob(pat)
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
            for num, quoted in stale_sections(line, sidx):
                stale.append({"file": rel, "line": i, "chapter": num,
                              "now": f"quotes a section “{quoted}” that Chapter {num} "
                                     f"no longer has",
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
            "stale_sections": stale,
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

    # EVERY reference form, because for months this caught only one of them and
    # reported CLEAN over six live `Ch.30` references to a retired chapter.
    for label, ref in (("Ch. N", f"Ch. {gone}"), ("Ch.N", f"Ch.{gone}"), ("ChN", f"Ch{gone}")):
        got = scan(doc, {f: f"CITATIONS: [{ref}, Ch.1]\n"})
        seen = any(x["chapter"] == gone for x in got["findings"])
        ok &= seen
        print(f"  {'OK  ' if seen else 'FAIL'} the abbreviated form '{label}' is caught too")

    # ...and the forms that must stay quiet, or the widened pattern indicts the
    # SUMMARIES' own file listing and every lowercase mention in ops prose.
    for label, text in (("section filename", f"--- chapter{gone}-section4 - Something.md ---"),
                        ("lowercase ch", f"see ch{gone} for the argument"),
                        ("no separator", f"Chapter{gone} is not a reference form")):
        got = scan(doc, {f: text + "\n"})
        quiet3 = not any(x["chapter"] == gone for x in got["findings"])
        ok &= quiet3
        print(f"  {'OK  ' if quiet3 else 'FAIL'} '{label}' is NOT treated as a reference")

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

        # A LIST binds each title to its own number and, by the comma, to the
        # next one as well. The correct binding wins: "Ch.N1 Title1, Ch.N2 x"
        # must be silent, or every evidence list in the corpus is a finding.
        got = scan(doc, {f: f"- Evidence: Ch.{n1} {t1}, Ch.{n2} compounding crises\n"})
        quiet5 = not got.get("misdirected")
        ok &= quiet5
        print(f"  {'OK  ' if quiet5 else 'FAIL'} a title correctly bound to its OWN number is not "
              "indicted by the next item in the list")
    else:
        print("  WARN the map has too few titled chapters to prove the misdirection rule")
        ok = False

    # the stale-section rule: a quoted section the chapter no longer has fires;
    # one it does have, and the chapter's own title, stay quiet
    sidx = sections_index(doc)
    with_secs = [(n, t, s) for n, (t, s) in sidx.items() if s and n in live]
    if with_secs:
        n, t, s = with_secs[0]
        real = sorted(s)[0]
        got = scan(doc, {f: f"Ch.{n}'s 'Zebra Quantum Gardening Protocols' covers it.\n"})
        caught = any(x["chapter"] == n for x in got.get("stale_sections", []))
        ok &= caught
        print(f"  {'OK  ' if caught else 'FAIL'} a quoted section Chapter {n} does not have is caught")
        got = scan(doc, {f: f"Ch.{n}'s '{real}' covers it.\n"})
        quiet6 = not got.get("stale_sections")
        ok &= quiet6
        print(f"  {'OK  ' if quiet6 else 'FAIL'} a quoted section Chapter {n} DOES have is silent")
        if t:
            got = scan(doc, {f: f"Ch.{n}'s '{t}' covers it.\n"})
            quiet7 = not got.get("stale_sections")
            ok &= quiet7
            print(f"  {'OK  ' if quiet7 else 'FAIL'} the chapter's own title in quotes is silent")
    else:
        print("  WARN the map carries no sections to prove the stale-section rule")
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
    ap.add_argument("--ratchet-sections", action="store_true",
                    help="record the current stale-section count as the ceiling (after a repair pass)")
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

    # STALE SECTIONS ride a RATCHET, not a zero: on 2026-09-10 there were dozens
    # of them, every one a sentence in the LoveLogic corpus that needs rewriting
    # by hand, and a gate that goes red for weeks over known work gets switched
    # off. So the count may only go DOWN. `--ratchet-sections` records the
    # current count as the new ceiling after a repair pass; a commit that adds
    # one fails.
    stale = r["stale_sections"]
    baseline = None
    if SECTIONS_BASELINE.is_file():
        try:
            baseline = int(json.loads(SECTIONS_BASELINE.read_text(encoding="utf-8"))["stale_sections"])
        except (ValueError, KeyError, TypeError):
            baseline = None
    if a.ratchet_sections:
        SECTIONS_BASELINE.write_text(json.dumps({"stale_sections": len(stale)}, indent=2) + "\n",
                                     encoding="utf-8")
        print(f"\n  stale-section baseline written: {len(stale)}")
        baseline = len(stale)
    sections_over = baseline is not None and len(stale) > baseline
    if stale:
        print(f"\n  STALE SECTIONS: {len(stale)} quotation(s) name a section their chapter no "
              f"longer has (ratchet {baseline if baseline is not None else 'unset'}). The "
              f"chapter number resolves; the quoted title did not survive its rewrite.")
        for s in stale[:12]:
            print(f"    {s['file']}:{s['line']}  Chapter {s['chapter']} {s['now']}")
        if len(stale) > 12:
            print(f"    ... and {len(stale) - 12} more (--json for all)")
        if sections_over:
            print(f"  RATCHET BROKEN: {len(stale)} > {baseline}. A new stale section quotation "
                  "was introduced; rewrite it, do not raise the ceiling.")
        elif baseline is None:
            print("  (no baseline yet — run with --ratchet-sections to set the ceiling)")

    if not r["findings"] and not r["misdirected"] and not sections_over:
        print("  CLEAN — every chapter reference points at something that exists, every "
              "reference naming a title agrees with it, and no new stale section quotation "
              "was introduced")
        return 0 if not r["map_gaps"] else 1
    if not r["findings"]:
        return 1
    print(f"\n  {len(r['findings'])} stale reference(s) in {len(by_file)} file(s). "
          "Rewrite each in context — a chapter reference is a sentence, not a token.")
    return 1


if __name__ == "__main__":
    sys.exit(main())
