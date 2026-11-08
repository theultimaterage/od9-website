#!/usr/bin/env python3
"""Move the LoveLogic weeklies' chapter citations onto the registry's numbering (2026-11-08).

The registry (data/manifesto-map.json) renumbered Volume 1 in September: The Great Awakening
4 -> 3, The System In Crisis 5 -> 4, A Labor of Love 3 -> 2, The Solution Framework 7 -> 5
(absorbing 6 and 8), Media and Information Crisis 9 -> 6, Mental Health 10 -> 7, Scientific
Institution Analysis 13 -> 10, Economic Dysfunction 16 -> 13, Religious Institutions 22 -> 19,
and the old Chapter 2 (Theoretical Foundations and Methodology) became Appendix A, Methods and
the Evidence Standard. tools/drip_refs_check.py flagged 25 RENUMBERED citations in 20 weeklies:
every one carried a title or a lesson link that names the chapter, so the edition it meant is
known. These are those 25, as 24 exact old -> new pairs (one action item carries two numbers).

Bare numbers with no title and no link (Ch.11, Ch.12, Ch.18, Ch.25, Ch.40 ...) are NOT touched:
a number alone cannot show which edition it meant, and the gate counts them as its stated limit.

Rules, same as tools/migrate_lesson_sources_2026_11_08.py: each old string must occur exactly
once in its file or the run aborts before writing anything; a file already carrying the new
string and not the old is "already current", so a second run is a no-op; the week's file is
found by glob and an ambiguous glob is refused (the 2026-08-17 duplicate-week-27 lesson).

    python tools/migrate_weekly_citations_2026_11_08.py            # plan only
    python tools/migrate_weekly_citations_2026_11_08.py --apply
    python tools/migrate_weekly_citations_2026_11_08.py --selftest # temp copy; pairs vs registry

Provers: --selftest (every pair applies exactly once on a copy, the second run changes
nothing, every old -> new number agrees with the registry's legacy map, and the drip gate
finds 0 RENUMBERED on the migrated copy); tools/drip_refs_check.py --check in prod-deploy.
"""
from __future__ import annotations

import argparse
import glob
import json
import os
import re
import shutil
import subprocess
import sys
import tempfile

sys.stdout.reconfigure(encoding="utf-8", errors="replace")  # type: ignore[attr-defined]

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
WEEKLY = os.path.join(ROOT, "email-templates", "lovelogic-weekly")
REGISTRY = os.path.join(ROOT, "data", "manifesto-map.json")
CUR = "https://offda9.com/curriculum/"

# (week, old, new). The old string carries the link or the title so it names one line.
PAIRS: list[tuple[str, str, str]] = [
    ("02", f"[**Ch.4**]({CUR}observer/the-twin-singularities.php) of the Manifesto runs",
           f"[**Ch.3**]({CUR}observer/the-twin-singularities.php) of the Manifesto runs"),
    # Each number gets its title: on this line new Ch.4 (The System in Crisis) wears the number
    # The Great Awakening used to have, and the gate reads a bare pair of numbers by the links.
    ("02", "the published lessons on Ch.4 and Ch.5.",
           "the published lessons on Ch.3 (*The Great Awakening*) and Ch.4 (*The System in Crisis*)."),
    ("03", f"[**Ch.2** of the Manifesto]({CUR}observer/the-evidence-standard.php) (*Theoretical Foundations and Methodology*)",
           f"[**Appendix A** of the Manifesto]({CUR}observer/the-evidence-standard.php) (*Methods and the Evidence Standard*)"),
    ("03", "**Ch.6** (*Responsible Transformation Frameworks*)",
           f"[**Ch.5**]({CUR}observer/responsible-transformation.php) (*Responsible Transformation Frameworks*)"),
    ("04", f"[**Ch.5**]({CUR}observer/the-system-in-crisis.php) of the Manifesto (*The System in Crisis*)",
           f"[**Ch.4**]({CUR}observer/the-system-in-crisis.php) of the Manifesto (*The System in Crisis*)"),
    ("05", f"[**Ch.7** of the Manifesto]({CUR}observer/the-solution-framework.php)",
           f"[**Ch.5** of the Manifesto]({CUR}observer/the-solution-framework.php)"),
    ("06", f"[**Ch.7**]({CUR}observer/the-solution-framework.php) and",
           f"[**Ch.5**]({CUR}observer/the-solution-framework.php) and"),
    ("06", f"[**Ch.16**]({CUR}theorist/economic-dysfunction.php) of the Manifesto",
           f"[**Ch.13**]({CUR}theorist/economic-dysfunction.php) of the Manifesto"),
    ("07", f"[**Ch.7**]({CUR}observer/the-solution-framework.php) (*The Solution Framework*)",
           f"[**Ch.5**]({CUR}observer/the-solution-framework.php) (*The Solution Framework*)"),
    ("07", f"[**Ch.13**]({CUR}theorist/scientific-institutions.php) (*Scientific Institution Analysis*)",
           f"[**Ch.10**]({CUR}theorist/scientific-institutions.php) (*Scientific Institution Analysis*)"),
    ("08", f"[**Ch.9** of the Manifesto]({CUR}theorist/the-information-crisis.php)",
           f"[**Ch.6** of the Manifesto]({CUR}theorist/the-information-crisis.php)"),
    ("09", f"[**Ch.13** of the Manifesto]({CUR}theorist/scientific-institutions.php)",
           f"[**Ch.10** of the Manifesto]({CUR}theorist/scientific-institutions.php)"),
    ("10", f"[**Ch.22**]({CUR}theorist/religious-institutions.php)",
           f"[**Ch.19**]({CUR}theorist/religious-institutions.php)"),
    ("11", f"[**Ch.8**]({CUR}observer/from-vision-to-action.php)",
           f"[**Ch.5**]({CUR}observer/from-vision-to-action.php)"),
    ("12", f"[**Ch.16**]({CUR}theorist/economic-dysfunction.php)",
           f"[**Ch.13**]({CUR}theorist/economic-dysfunction.php)"),
    ("13", f"[**Ch.22**]({CUR}theorist/religious-institutions.php)",
           f"[**Ch.19**]({CUR}theorist/religious-institutions.php)"),
    ("16", f"[**Ch.10**]({CUR}theorist/the-collective-psyche.php)",
           f"[**Ch.7**]({CUR}theorist/the-collective-psyche.php)"),
    ("18", f"[**Ch.22**]({CUR}theorist/religious-institutions.php)",
           f"[**Ch.19**]({CUR}theorist/religious-institutions.php)"),
    ("20", f"[**Ch.6**]({CUR}observer/responsible-transformation.php)",
           f"[**Ch.5**]({CUR}observer/responsible-transformation.php)"),
    ("25", f"[**Ch.6**]({CUR}observer/responsible-transformation.php)",
           f"[**Ch.5**]({CUR}observer/responsible-transformation.php)"),
    ("26", f"[**Ch.5**]({CUR}observer/the-system-in-crisis.php) (*The System in Crisis*)",
           f"[**Ch.4**]({CUR}observer/the-system-in-crisis.php) (*The System in Crisis*)"),
    ("27", f"[**Ch.7** of the Manifesto]({CUR}observer/the-solution-framework.php)",
           f"[**Ch.5** of the Manifesto]({CUR}observer/the-solution-framework.php)"),
    ("28", f"[**Ch.5** of the Manifesto]({CUR}observer/the-system-in-crisis.php)",
           f"[**Ch.4** of the Manifesto]({CUR}observer/the-system-in-crisis.php)"),
    ("29", f"[**Ch.3**]({CUR}observer/love-as-infrastructure.php)",
           f"[**Ch.2**]({CUR}observer/love-as-infrastructure.php)"),
]

CH = re.compile(r"Ch\.(\d{1,2})")


def week_file(weekly_dir: str, week: str) -> str:
    hits = sorted(glob.glob(os.path.join(weekly_dir, f"email-week-{week}-*.md")))
    if len(hits) != 1:
        raise SystemExit(f"ABORT: week {week} matches {len(hits)} template(s), need exactly one: {hits}")
    return hits[0]


def plan(weekly_dir: str) -> tuple[dict[str, str], list[str]]:
    """Return {path: new_text} for files that change, and the per-pair verdict lines.

    Raises SystemExit on any pair that is neither 'already current' nor exactly-once."""
    texts: dict[str, str] = {}
    verdicts: list[str] = []
    for week, old, new in PAIRS:
        path = week_file(weekly_dir, week)
        text = texts.get(path)
        if text is None:
            with open(path, encoding="utf-8", newline="") as fh:
                text = fh.read()
        n_old, n_new = text.count(old), text.count(new)
        if n_new == 1 and n_old == 0:
            verdicts.append(f"  current  week {week}: {new[:60]}")
            texts[path] = text
            continue
        if n_old != 1:
            raise SystemExit(f"ABORT: week {week}: old string occurs {n_old} time(s), need exactly 1:\n    {old}")
        if n_new:
            raise SystemExit(f"ABORT: week {week}: both old and new present:\n    {old}\n    {new}")
        texts[path] = text.replace(old, new, 1)
        verdicts.append(f"  change   week {week}: {old[:60]} -> {new[:60]}")
    changed = {}
    for path, new_text in texts.items():
        with open(path, encoding="utf-8", newline="") as fh:
            if fh.read() != new_text:
                changed[path] = new_text
    return changed, verdicts


def run(weekly_dir: str, apply: bool) -> int:
    changed, verdicts = plan(weekly_dir)
    print("\n".join(verdicts))
    print(f"{len(PAIRS)} pair(s); {len(changed)} file(s) would change" + ("" if apply else " (plan only; --apply to write)"))
    if apply:
        for path, text in changed.items():
            with open(path, "w", encoding="utf-8", newline="") as fh:
                fh.write(text)
            print(f"  wrote {os.path.relpath(path, ROOT)}")
    return 0


def pairs_agree_with_registry(registry_path: str) -> list[str]:
    """Every old -> new number in PAIRS must be what the registry's legacy map says."""
    with open(registry_path, encoding="utf-8") as fh:
        reg = json.load(fh)
    nodes = reg["nodes"] if isinstance(reg, dict) and "nodes" in reg else reg
    by_legacy: dict[int, str] = {}
    for node in nodes:
        for old in node.get("legacy") or []:
            by_legacy[int(old)] = str(node["num"])
    problems = []
    for week, old, new in PAIRS:
        olds = [int(x) for x in CH.findall(old)]
        news = CH.findall(new)
        if "Appendix A" in new:
            news = ["A"] + news
        if len(olds) != len(news):
            problems.append(f"week {week}: {len(olds)} old number(s) vs {len(news)} new")
            continue
        for o, n in zip(olds, news):
            want = by_legacy.get(o)
            if want != n:
                problems.append(f"week {week}: Ch.{o} -> {n} but the registry says {want}")
    return problems


def selftest() -> int:
    fails = 0

    def check(name: str, ok: bool, detail: str = "") -> None:
        nonlocal fails
        print(f"  {'PASS' if ok else 'FAIL'}  {name}" + (f": {detail}" if detail and not ok else ""))
        fails += 0 if ok else 1

    probs = pairs_agree_with_registry(REGISTRY)
    check("every pair's new number is the registry's for the old one", not probs, "; ".join(probs))

    tmp = tempfile.mkdtemp(prefix="weekly_migrate_")
    try:
        copy = os.path.join(tmp, "lovelogic-weekly")
        shutil.copytree(WEEKLY, copy)
        changed, verdicts = plan(copy)
        n_change = sum(1 for v in verdicts if v.strip().startswith("change"))
        check("24 pairs planned as changes on the live copy (0 already current)", n_change == len(PAIRS) and len(PAIRS) == 24,
              f"{n_change} change(s) of {len(PAIRS)}")
        check("20 files change", len(changed) == 20, f"{len(changed)}")
        run(copy, apply=True)
        changed2, verdicts2 = plan(copy)
        check("second run is a no-op", not changed2 and all(v.strip().startswith("current") for v in verdicts2),
              f"{len(changed2)} file(s) would change again")
        # No old-numbered link survives for the eight lessons the pairs touch.
        stale = []
        old_links = {
            "the-twin-singularities.php": {"4"}, "the-system-in-crisis.php": {"5"}, "love-as-infrastructure.php": {"3"},
            "the-solution-framework.php": {"7"}, "responsible-transformation.php": {"6"}, "from-vision-to-action.php": {"8"},
            "the-information-crisis.php": {"9"}, "the-collective-psyche.php": {"10"}, "scientific-institutions.php": {"13"},
            "economic-dysfunction.php": {"16"}, "religious-institutions.php": {"22"}, "the-evidence-standard.php": {"2"},
        }
        link_re = re.compile(r"\[\*\*Ch\.(\d{1,2})\*\*(?: of the Manifesto)?\]\([^)]*/([a-z-]+\.php)\)")
        for path in glob.glob(os.path.join(copy, "*.md")):
            with open(path, encoding="utf-8") as fh:
                for num, slug in link_re.findall(fh.read()):
                    if num in old_links.get(slug, set()):
                        stale.append(f"{os.path.basename(path)}: Ch.{num} -> {slug}")
        check("no linked lesson is still cited by its old number", not stale, "; ".join(stale))
        # The drip gate itself, on the migrated copy: 0 RENUMBERED.
        gate = os.path.join(ROOT, "tools", "drip_refs_check.py")
        env = {**os.environ, "PYTHONIOENCODING": "utf-8", "PYTHONUTF8": "1", "DRIP_WEEKLY_DIR": copy}
        res = subprocess.run([sys.executable, gate, "--report"], capture_output=True, text=True,
                             encoding="utf-8", errors="replace", env=env, cwd=ROOT)
        out = res.stdout + res.stderr
        m = re.search(r"=== RENUMBERED \((\d+)\)", out)
        renumbered = int(m.group(1)) if m else 0
        check("drip gate reports 0 RENUMBERED on the migrated copy", res.returncode in (0, 1) and renumbered == 0
              and "DRIP_WEEKLY_DIR" in open(gate, encoding="utf-8").read(),
              f"rc={res.returncode} renumbered={renumbered}; tail: {out.strip()[-300:]}")
    finally:
        shutil.rmtree(tmp, ignore_errors=True)
    print(f"selftest: {fails} failure(s)")
    return 1 if fails else 0


def main() -> int:
    ap = argparse.ArgumentParser(description=(__doc__ or "").split("\n")[0])
    ap.add_argument("--apply", action="store_true")
    ap.add_argument("--selftest", action="store_true")
    a = ap.parse_args()
    if a.selftest:
        return selftest()
    return run(WEEKLY, a.apply)


if __name__ == "__main__":
    raise SystemExit(main())
