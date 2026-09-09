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
  3. RATCHET — every author named anywhere in the worklists must be
     classified in the config as flagged or cleared. A newly condemned source
     therefore fails the gate until someone looks at it, which is what keeps
     the list from going stale the way a hand-maintained list always does.

THREE CITATION FORMS, and the reason this file says so out loud (2026-09-08):
the first version of this gate matched only the narrative form, "Fermi (1950)".
The corpus it guards overwhelmingly writes the parenthetical form instead —
"(Karp, 2018)", "(Kessler & Poon, 2018)" — and puts a third form in every
References section, "Sternberg, R. J., & Wagner, R. K. (1991)." Measured against
the manifesto source, the narrative-only matcher saw 3 condemned citations where
15 were present. It reported the lessons clean and it was right, but by luck
rather than by reading them: every form it could not see was a form it never
looked for. Its selftest injected the narrative form too, so the vacuity guard
proved only the half that already worked. Any new form goes in cites_in() AND in
selftest(), which fails unless every form is caught.

What it does NOT cover, deliberately: a real source attached to a false claim.
Fung & Warren (2011) is a genuine paper cited for a project that does not
exist; no citation checker can see that. Only a reader can.

    python tools/citation_gate.py                # published lessons
    python tools/citation_gate.py --source       # the manifesto source, ratcheted
    python tools/citation_gate.py --selftest     # prove it can fail, in every form
    python tools/citation_gate.py --json

--source scans the manifesto corpus itself and holds it to a ratchet in the
config: the count may fall and never rise. The lesson check answers "did a
fabrication reach the public"; the source check answers "is it still in the
book, and did a repair pass actually remove it." --update-ratchet lowers the bar
to what is measured now and REFUSES to raise it — a ratchet that can be loosened
is a number, not a gate.

Config: .claude/citation-gate.json. Worklists and source live under
MANIFESTO_DIR (default C:\\Users\\Rage\\Documents\\The OD9 Manifesto); without
them the ratchet is skipped, loudly, and the flagged check still runs.
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
NAME = r"[A-Z][A-Za-z'\u2019\-]{2,}"
# A \u2014 narrative: "Fermi (1950)", "Meadows et al. (2008)"
NARRATIVE_RE = re.compile(rf"\b({NAME})\s*(?:et al\.?)?\s*\((\d{{4}})\)")
# B \u2014 parenthetical, the form this corpus actually prefers: "(Karp, 2018)",
#     "(Kessler & Poon, 2018)", "(Fermi, 1950; Webb, 2015)", "(Rodrik, 2011, p. 4)"
PAREN_GROUP_RE = re.compile(r"\(([^()]{1,240})\)")
PAREN_CITE_RE = re.compile(
    rf"^\s*(?:e\.g\.,?\s*|see\s+|cf\.,?\s*)?({NAME})(?:\s*(?:&|and)\s*({NAME}))?"
    rf"(?:\s*et al\.?)?,\s*(\d{{4}})[a-z]?\s*(?:,\s*pp?\.?\s*[\d\u2013\-]+\s*)?$"
)
# C \u2014 reference lists: "Sternberg, R. J., & Wagner, R. K. (1991)."
#     The (?<!\d) matters: "Science, 376(6354)" is a volume and issue number, and
#     without it the gate reports two impossible dates that are really page ranges.
#     A four-digit number is only a year if it could be one \u2014 see PLAUSIBLE_YEAR.
REF_YEAR_RE = re.compile(r"(?<!\d)\((\d{4})[a-z]?\)")
REF_NAME_RE = re.compile(rf"\b({NAME}),\s+(?:[A-Z]\.\s*)+")
PLAUSIBLE_YEAR = range(1400, 3000)


def cites_in(text: str) -> list[tuple[str, int, int]]:
    """[(name, year, offset)] for every citation, in all three forms the corpus uses.

    A matcher that knows one form reports clean over the text it never read \u2014
    see the module docstring for the measurement that proved it here.
    """
    t = html.unescape(text)
    out: list[tuple[str, int, int]] = []

    for m in NARRATIVE_RE.finditer(t):
        out.append((m.group(1), int(m.group(2)), m.start()))

    for group in PAREN_GROUP_RE.finditer(t):
        base = group.start(1)
        for chunk in re.finditer(r"[^;]+", group.group(1)):
            m = PAREN_CITE_RE.match(chunk.group(0))
            if not m:
                continue
            year, off = int(m.group(3)), base + chunk.start()
            out.append((m.group(1), year, off))
            if m.group(2):          # "(Kessler & Poon, 2018)" condemns both names
                out.append((m.group(2), year, off))

    off = 0                          # line-based: a reference entry is one line
    for line in t.splitlines(keepends=True):
        year = REF_YEAR_RE.search(line)
        if year:
            for nm in REF_NAME_RE.finditer(line[:year.start()]):
                out.append((nm.group(1), int(year.group(1)), off + nm.start()))
        off += len(line)

    return sorted({c for c in out if c[1] in PLAUSIBLE_YEAR}, key=lambda c: c[2])


def lesson_files(patterns: list[str]) -> list[Path]:
    out: list[Path] = []
    for p in patterns:
        out += [Path(f) for f in glob.glob(str(ROOT / p)) if "_codex" not in f]
    return sorted(set(out))


def source_files(cfg: dict) -> list[Path]:
    """The manifesto corpus, minus the ops docs that quote fabrications on purpose.

    A DENYLIST, not an allowlist: an allowlist of volumes silently misses the
    next volume, while a new ops doc merely shows up as findings \u2014 visible, and
    fixable in one line. The worklists MUST be excluded; cataloguing a condemned
    citation is how they do their job.
    """
    src = cfg.get("source", {})
    if not MANIFESTO.is_dir():
        return []
    skip = tuple(s.replace("/", os.sep).lower() for s in src.get("exclude", []))
    out = []
    for p in MANIFESTO.rglob(src.get("glob", "**/*.md")):
        rel = str(p.relative_to(MANIFESTO)).lower()
        if not any(s in rel for s in skip):
            out.append(p)
    return sorted(out)


def run(cfg: dict, extra_text: dict[Path, str] | None = None,
        files: list[Path] | None = None, base: Path | None = None) -> dict:
    flagged = {(f["name"], int(f["year"])): f.get("why", "") for f in cfg.get("flagged", [])}
    cleared_names = {c["name"] for c in cfg.get("cleared", [])}
    flagged_names = {f["name"] for f in cfg.get("flagged", [])}
    year_now = datetime.date.today().year

    findings: list[dict] = []
    scanned = 0
    for f in (lesson_files(cfg.get("scan", [])) if files is None else files):
        text = (extra_text or {}).get(f) or f.read_text(encoding="utf-8", errors="replace")
        scanned += 1
        flat = html.unescape(text)   # cites_in reports offsets into the unescaped text
        for name, year, off in cites_in(text):
            rel = f.relative_to(base or ROOT).as_posix()
            line = flat[:off].count("\n") + 1
            if (name, year) in flagged:
                findings.append({"kind": "flagged", "file": rel, "line": line,
                                 "cite": f"{name} ({year})", "why": flagged[(name, year)]})
            elif year > year_now:
                findings.append({"kind": "impossible", "file": rel, "line": line,
                                 "cite": f"{name} ({year})",
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
    from a clean repo — so inject a condemned citation into a lesson's text in
    memory and require the gate to name it.

    Once per FORM. The first version of this selftest injected the narrative
    form only, which is exactly the form the matcher could already see; it
    passed while the gate was blind to four fifths of the corpus. A vacuity
    guard that tests the working half guards nothing.
    """
    files = lesson_files(cfg.get("scan", []))
    if not files or not cfg.get("flagged"):
        print("selftest: no lessons or no flagged list — cannot prove anything")
        return 2
    f = files[0]
    clean = f.read_text(encoding="utf-8", errors="replace")
    bad = cfg["flagged"][0]
    n, y = bad["name"], bad["year"]

    cases = [
        ("narrative      ", f"<!-- selftest {n} ({y}) -->", f"{n} ({y})"),
        ("parenthetical  ", f"<!-- selftest as shown ({n}, {y}) -->", f"{n} ({y})"),
        ("paired-paren   ", f"<!-- selftest ({n} & Nobody, {y}) -->", f"{n} ({y})"),
        ("semicolon-list ", f"<!-- selftest (Someone, 1999; {n}, {y}) -->", f"{n} ({y})"),
        ("reference-list ", f"<!-- selftest {n}, R. J. ({y}). A title. -->", f"{n} ({y})"),
        ("impossible date", "<!-- selftest Nostradamus (2099) -->", "Nostradamus (2099)"),
    ]
    ok = True
    for label, poison, expect in cases:
        got = run(cfg, {f: clean + "\n" + poison + "\n"})
        hit = any(x["cite"] == expect for x in got["findings"])
        ok &= hit
        print(f"selftest: {label} -> " + ("CAUGHT" if hit else f"MISSED  ({poison})"))

    clean_run = run(cfg, {f: clean})
    noise = [x for x in clean_run["findings"] if x["file"] == f.relative_to(ROOT).as_posix()]
    if noise:
        print(f"selftest: the unpoisoned lesson reports {len(noise)} finding(s) — "
              "either a real hit or a false positive; check before trusting a pass")

    print("selftest: " + ("every form is caught, so a clean run means something" if ok
                          else "THE GATE IS BLIND TO A FORM IT CLAIMS TO CHECK"))
    return 0 if ok else 1


def source_check(cfg: dict, as_json: bool, update: bool) -> int:
    """The manifesto source, held to a ratchet that only ever tightens."""
    files = source_files(cfg)
    if not files:
        print(f"citation-gate --source: nothing to scan under {MANIFESTO}", file=sys.stderr)
        print("  set MANIFESTO_DIR, or add a 'source' block to the config", file=sys.stderr)
        return 2
    r = run(cfg, files=files, base=MANIFESTO)
    found = len(r["findings"])
    bar = int(cfg.get("source", {}).get("ratchet", 0))

    if as_json:
        print(json.dumps({**r, "ratchet": bar}, indent=1))
        return 1 if found > bar else 0

    print(f"citation-gate --source: {r['scanned']} manifesto file(s) vs "
          f"{len(cfg.get('flagged', []))} condemned citation(s); ratchet = {bar}")
    for f in sorted(r["findings"], key=lambda x: (x["file"], x["line"])):
        print(f"  {f['kind'].upper():<11} {f['file']}:{f['line']}  {f['cite']}\n              {f['why']}")

    if update:
        if found > bar:
            print(f"\n  REFUSED: {found} finding(s) is worse than the ratchet ({bar}). "
                  "A ratchet that can be loosened is a number, not a gate.\n"
                  "  Repair the citations; the bar comes down when the corpus does.")
            return 1
        cfg.setdefault("source", {})["ratchet"] = found
        CONFIG.write_text(json.dumps(cfg, indent=2, ensure_ascii=False) + "\n", encoding="utf-8")
        print(f"\n  ratchet tightened {bar} -> {found}; it can never go back up.")
        return 0

    if found > bar:
        print(f"\n  REGRESSION: {found} condemned citation(s) in the source, ratchet is {bar}.")
        return 1
    if found:
        print(f"\n  {found} condemned citation(s) remain, at or under the ratchet ({bar}). "
              "Repair one and run --update-ratchet to lock the gain in.")
        return 0
    print("  CLEAN — the source carries no condemned citation")
    return 0


def main() -> int:
    ap = argparse.ArgumentParser(description=__doc__.split("\n\n")[0])
    ap.add_argument("--json", action="store_true")
    ap.add_argument("--selftest", action="store_true")
    ap.add_argument("--source", action="store_true",
                    help="scan the manifesto source instead of the published lessons")
    ap.add_argument("--update-ratchet", action="store_true",
                    help="with --source: lower the bar to what is measured now (never raise it)")
    a = ap.parse_args()

    if not CONFIG.is_file():
        print(f"citation-gate: no config at {CONFIG}", file=sys.stderr)
        return 2
    cfg = json.loads(CONFIG.read_text(encoding="utf-8"))

    if a.selftest:
        return selftest(cfg)
    if a.source:
        return source_check(cfg, a.json, a.update_ratchet)

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
        print(f"  {f['kind'].upper():<11} {f['file']}:{f['line']}  {f['cite']}\n              {f['why']}")
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
