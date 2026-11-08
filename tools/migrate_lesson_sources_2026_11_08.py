#!/usr/bin/env python3
"""One-time migration (2026-11-08): the Codex lesson pages say the chapter number the registry gives.

WHY. data/manifesto-map.json renumbered the book (Volume 1 consolidation, 2026-09-09: The Great
Awakening 4 -> 3, The System In Crisis 5 -> 4, The Solution Framework absorbed 6 and 8 and is 5;
Volume 2 shifted by three; Theoretical Foundations became Appendix A) and the chapter folders on
disk followed. Fourteen lesson pages kept the old number in two visible places, the eyebrow
("Volume I · Foundation & Vision • Chapter 4") and the Source line ("Chapter 4 (The Great
Awakening), §IV ..."), and two pages' study text pointed readers at the old numbers too.
tools/drip_refs_check.py (LESSON_SOURCE) found all fourteen on its first run; the founder:
"Go ahead and fix the 14 lesson Source lines next."

HOW. Every replacement is authored here as an exact (old, new) pair and must match exactly once
in its file; one miss aborts the whole run before anything is written. Dry run prints the diff;
--apply writes. Section numbers (§II, §VIII ...) are untouched: the sections kept their places
inside the chapters that moved. Titles are untouched except Appendix A's, whose old title
("Theoretical Foundations & Methodology") no longer exists on disk. Header comments that record
the 2026-09 consolidation in that day's numbering get one dated line saying so.

    python tools/migrate_lesson_sources_2026_11_08.py          # dry run: unified diff
    python tools/migrate_lesson_sources_2026_11_08.py --apply
    python tools/migrate_lesson_sources_2026_11_08.py --selftest

Provers: --selftest (every pair applies to a fixture copy exactly once and a second run is a
no-op); after --apply, tools/drip_refs_check.py --check reports LESSON_SOURCE 14 -> 0.
"""
from __future__ import annotations

import argparse
import difflib
import os
import shutil
import sys
import tempfile

if hasattr(sys.stdout, "reconfigure"):
    sys.stdout.reconfigure(encoding="utf-8", errors="replace")  # type: ignore[attr-defined]

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
CURRICULUM = os.path.join(ROOT, "curriculum")

# (page, old chapter number, new chapter number); the eyebrow and the Source line move together.
RENUMBER = [
    ("observer/from-vision-to-action.php", 7, 5),
    ("observer/love-as-infrastructure.php", 3, 2),
    ("observer/responsible-transformation.php", 7, 5),
    ("observer/the-solution-framework.php", 7, 5),
    ("observer/the-system-in-crisis.php", 5, 4),
    ("observer/the-twin-singularities.php", 4, 3),
    ("theorist/economic-dysfunction.php", 16, 13),
    ("theorist/environmental-degradation.php", 21, 18),
    ("theorist/political-corruption.php", 19, 16),
    ("theorist/religious-institutions.php", 22, 19),
    ("theorist/scientific-institutions.php", 13, 10),
    ("theorist/the-collective-psyche.php", 10, 7),
    ("theorist/the-information-crisis.php", 9, 6),
]

HISTORY_NOTE = (" * 2026-11-08: the registry renumbered Volume 1 after that consolidation; the numbers in\n"
                " * the note above are the 2026-09 numbering, and this lesson's chapter is Chapter 5 now.\n")


def pairs() -> list[tuple[str, str, str]]:
    out: list[tuple[str, str, str]] = []
    for page, old, new in RENUMBER:
        vol = "1" if page.startswith("observer/") else "2"
        out.append((page, f"&bull;&nbsp; Chapter {old}\"", f"&bull;&nbsp; Chapter {new}\""))
        out.append((page, f"Volume&nbsp;{vol} &middot; Chapter&nbsp;{old} (", f"Volume&nbsp;{vol} &middot; Chapter&nbsp;{new} ("))
    # Theoretical Foundations & Methodology became Appendix A; its sections kept their numbers.
    out += [
        ("observer/the-evidence-standard.php", "&bull;&nbsp; Chapter 2\"", "&bull;&nbsp; Appendix A\""),
        ("observer/the-evidence-standard.php",
         "Volume&nbsp;1 &middot; Chapter&nbsp;2 (<em>Theoretical Foundations &amp; Methodology</em>)",
         "Volume&nbsp;1 &middot; Appendix&nbsp;A (<em>Methods and the Evidence Standard</em>)"),
    ]
    # Study text that pointed at the old numbers.
    out += [
        ("observer/the-solution-framework.php",
         "(which is exactly the crisis of Chapter&nbsp;5).\"",
         "(which is exactly the crisis of Chapter&nbsp;4).\""),
        ("observer/the-solution-framework.php",
         "Three chapters, one argument: Chapter&nbsp;5 named the disease (coordination failure), Chapter&nbsp;6 set the "
         "guardrails (capability isn&rsquo;t wisdom; upgrade, don&rsquo;t demolish), and this is the answer that fits "
         "inside them.",
         "Two chapters, one argument: Chapter&nbsp;4 named the disease (coordination failure), this chapter&rsquo;s "
         "opening section set the guardrails (capability isn&rsquo;t wisdom; upgrade, don&rsquo;t demolish), and the "
         "architecture is the answer that fits inside them."),
        ("observer/the-solution-framework.php",
         "precisely the responsible path Chapter&nbsp;6 demanded. What remains is turning the framework into a "
         "roadmap: that&rsquo;s the next chapter.",
         "precisely the responsible path those guardrails demand. What remains is turning the framework into a "
         "roadmap: that&rsquo;s the chapter&rsquo;s closing sections."),
        ("observer/responsible-transformation.php",
         "Read the rest of Chapter&nbsp;7 against that standard, and hold this one to it too.",
         "Read the rest of Chapter&nbsp;5 against that standard, and hold this one to it too."),
    ]
    # The RE-AUTHORED notes describe the 2026-09 consolidation in that day's numbering; say so once.
    out += [
        ("observer/from-vision-to-action.php",
         " * 2026-09-09 Volume 1 consolidation folded into Chapter 7 (\"ch8's implementation\n",
         " * 2026-09-09 Volume 1 consolidation folded into Chapter 7 (\"ch8's implementation\n" + HISTORY_NOTE),
        ("observer/responsible-transformation.php",
         " * 2026-09-09 Volume 1 consolidation folded into Chapter 7 as §II — \"ethics and\n",
         " * 2026-09-09 Volume 1 consolidation folded into Chapter 7 as §II — \"ethics and\n" + HISTORY_NOTE),
    ]
    return out


def plan(curriculum: str) -> tuple[dict[str, tuple[str, str]], list[str]]:
    """{path: (old_text, new_text)} for every file that changes, and the errors that abort."""
    texts: dict[str, str] = {}
    originals: dict[str, str] = {}
    errors: list[str] = []
    for page, old, new in pairs():
        path = os.path.join(curriculum, *page.split("/"))
        if path not in texts:
            if not os.path.exists(path):
                errors.append(f"{page}: not found")
                continue
            with open(path, encoding="utf-8", newline="") as fh:
                originals[path] = texts[path] = fh.read()
        # The pages are CRLF on this Windows checkout; a pair is written with "\n" and takes the file's ending.
        nl = "\r\n" if "\r\n" in texts[path] else "\n"
        old, new = old.replace("\n", nl), new.replace("\n", nl)
        n = texts[path].count(old)
        if new in texts[path] and (n == 0 or new.startswith(old)):
            continue  # already current (a note pair keeps its old text as the prefix of the new)
        if new in texts[path]:
            errors.append(f"{page}: both the old and the new text are present for {old[:60]!r}")
        elif n == 1:
            texts[path] = texts[path].replace(old, new, 1)
        elif n == 0:
            errors.append(f"{page}: no match for {old[:80]!r}")
        else:
            errors.append(f"{page}: {n} matches (must be 1) for {old[:60]!r}")
    changes = {p: (originals[p], texts[p]) for p in texts if originals[p] != texts[p]}
    return changes, errors


def run(curriculum: str, apply: bool) -> int:
    changes, errors = plan(curriculum)
    if errors:
        print("ABORTED, nothing written. Fix these first:")
        for e in errors:
            print("  " + e)
        return 1
    for path, (old, new) in sorted(changes.items()):
        rel = os.path.relpath(path, curriculum).replace(os.sep, "/")
        if apply:
            with open(path, "w", encoding="utf-8", newline="") as fh:
                fh.write(new)
        else:
            sys.stdout.writelines(difflib.unified_diff(old.splitlines(True), new.splitlines(True),
                                                       f"a/{rel}", f"b/{rel}"))
    print(f"\n{'APPLIED' if apply else 'DRY RUN'}: {len(changes)} file(s) change"
          f"{'' if apply else '; re-run with --apply to write'}.")
    return 0


def selftest() -> int:
    with tempfile.TemporaryDirectory() as tmp:
        fx = os.path.join(tmp, "curriculum")
        shutil.copytree(CURRICULUM, fx)
        changes, errors = plan(fx)
        if errors:
            print("SELFTEST FAIL: the live pages do not match the authored pairs:\n  " + "\n  ".join(errors))
            return 1
        want = {p for p, _o, _n in pairs()}
        got = {os.path.relpath(p, fx).replace(os.sep, "/") for p in changes}
        if got != want:
            print(f"SELFTEST FAIL: files changed {sorted(got ^ want)} differ from the authored set")
            return 1
        import contextlib, io
        with contextlib.redirect_stdout(io.StringIO()):
            rc = run(fx, apply=True)
        changes2, errors2 = plan(fx)
        if rc != 0 or errors2 or changes2:
            print(f"SELFTEST FAIL: a second run is not a no-op (rc={rc}, errors={errors2}, changes={len(changes2)})")
            return 1
        import re
        stale = []
        for page, _old, new in RENUMBER:
            with open(os.path.join(fx, *page.split("/")), encoding="utf-8") as fh:
                txt = fh.read()
            m = re.search(r'"source"\s*=>\s*"[^"]*?Chapter&nbsp;(\d+)', txt)
            if not m or int(m.group(1)) != new:
                stale.append(page)
        if stale:
            print(f"SELFTEST FAIL: Source line not at the new number in {stale}")
            return 1
    print(f"SELFTEST OK: {len(pairs())} pairs each match exactly once across {len(want)} pages, a second run is a "
          f"no-op, every Source line lands on the registry's number.")
    return 0


def main() -> int:
    ap = argparse.ArgumentParser(description=(__doc__ or "").split("\n")[0])
    ap.add_argument("--apply", action="store_true")
    ap.add_argument("--selftest", action="store_true")
    a = ap.parse_args()
    if a.selftest:
        return selftest()
    return run(CURRICULUM, a.apply)


if __name__ == "__main__":
    raise SystemExit(main())
