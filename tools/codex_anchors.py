#!/usr/bin/env python3
"""codex_anchors — the Atlas's section landings, counted and checked against the manifesto.

The Atlas lists a chapter's sections as satellites. Clicking one and then opening
the canon lesson links at `#sec-N`. A lesson earns that landing by declaring, on
the canon block, the section the passage was drawn from:

    ["sec" => 8, "p" => "These diverse apocalyptic frameworks share…"]

N IS THE SECTION'S POSITION IN THE ATLAS'S OWN LIST, which is not always the
manifesto's §N: chapter 22's files skip §6, so its Doomsday section is file §9
and map position 8. Do not copy the number out of a lesson's source line — let
this tool derive it, because it resolves by TITLE against data/manifesto-map.json,
which is the authority the map itself uses.

    python tools/codex_anchors.py             # coverage per lesson
    python tools/codex_anchors.py --verify    # every declared anchor must match the manifesto (exit 1 if not)
    python tools/codex_anchors.py --suggest   # derive placements for undeclared passages
    python tools/codex_anchors.py --apply     # …and write them into the lesson files

A lesson that declares nothing still works: the link lands at the top of the
page. So partial coverage is safe — the risk is that it stays partial silently,
which the default report answers with a number.

Verify and suggest need the manifesto (MANIFESTO_DIR, default
C:\\Users\\Rage\\Documents\\The OD9 Manifesto); without it they skip, loudly.
"""
from __future__ import annotations

import argparse
import html
import json
import os
import re
import sys
from pathlib import Path
from urllib.parse import urlparse

sys.stdout.reconfigure(encoding="utf-8", errors="replace")

ROOT = Path(__file__).resolve().parents[1]
MAP = ROOT / "data" / "manifesto-map.json"
MANIFESTO = Path(os.environ.get("MANIFESTO_DIR", r"C:\Users\Rage\Documents\The OD9 Manifesto"))
SEC_RE = re.compile(r'["\']sec["\']\s*=>\s*(\d+)')
CANON_RE = re.compile(r'"canon"\s*=>\s*\[(.*?)\n  \],', re.S)
BLOCK_RE = re.compile(r'\[(?:"sec"\s*=>\s*(\d+),\s*)?"p"\s*=>\s*"(.*?)"(?:,\s*"lead"\s*=>\s*true)?\]', re.S)
FILE_SEC_RE = re.compile(r"chapter(\d+)-section(\d+)\s*-\s*(.+)\.md$", re.I)
PREFACE_SEC_RE = re.compile(r"preface-section(\d+)\s*-\s*(.+)\.md$", re.I)


def norm(s: str) -> str:
    """Plain comparable text: no tags, no entities, ASCII punctuation, one space."""
    s = re.sub(r"<[^>]+>", " ", s)
    s = html.unescape(s)
    for a, b in (("\u2019", "'"), ("\u2018", "'"), ("\u201c", '"'), ("\u201d", '"'),
                 ("\u2014", "-"), ("\u2013", "-"), ("\u2026", "..."), ("\u00a0", " ")):
        s = s.replace(a, b)
    return re.sub(r"\s+", " ", s).strip().lower()


def lesson_path(url: str) -> Path | None:
    p = ROOT / urlparse(url).path.lstrip("/")
    return p if p.suffix == ".php" and p.is_file() else None


def load_manifesto() -> list[tuple[str, str]]:
    """[(section title, normalised body)] across the whole manifesto."""
    out: list[tuple[str, str]] = []
    if not MANIFESTO.is_dir():
        return out
    for p in MANIFESTO.glob("Volume */Chapter *  */Markdown Files/*.md"):
        m = FILE_SEC_RE.match(p.name)
        if m:
            out.append((re.sub(r"\s+", " ", m.group(3)).strip(),
                        norm(p.read_text(encoding="utf-8", errors="replace"))))
    pre = MANIFESTO / "Preface - The Paradox of Transformation" / "Markdown Files"
    if pre.is_dir():
        for p in pre.glob("*.md"):
            m = PREFACE_SEC_RE.match(p.name)
            if m:
                out.append((re.sub(r"\s+", " ", m.group(2)).strip(),
                            norm(p.read_text(encoding="utf-8", errors="replace"))))
    return out


def probes(t: str) -> list[str]:
    """Distinctive slices, the middle first: edges get re-punctuated when a
    passage is lifted into a lesson, the middle survives verbatim."""
    if len(t) < 60:
        return [t]
    mid = len(t) // 2
    return [t[mid - 40:mid + 40], t[20:100], t[-90:-10]]


def place(passage: str, sections: list[tuple[str, str]], titles: dict[str, int]) -> int | None:
    """The 1-based map position of the section containing this passage, or None
    when the source does not answer it unambiguously."""
    hits: set[str] = set()
    for pr in probes(passage):
        for title, body in sections:
            if pr and pr in body:
                hits.add(title)
        if hits:
            break
    idxs = {titles[t.lower()] for t in hits if t.lower() in titles}
    return (idxs.pop() + 1) if len(idxs) == 1 else None


def main() -> int:
    ap = argparse.ArgumentParser(description=__doc__.split("\n\n")[0])
    ap.add_argument("--missing", action="store_true", help="list only the lessons that declare nothing")
    ap.add_argument("--strict", action="store_true", help="exit 1 when a lesson declares nothing")
    ap.add_argument("--verify", action="store_true", help="check every declared anchor against the manifesto")
    ap.add_argument("--suggest", action="store_true", help="derive placements for undeclared passages")
    ap.add_argument("--apply", action="store_true", help="write the derived placements into the lessons")
    a = ap.parse_args()
    if a.apply:
        a.suggest = True

    if not MAP.is_file():
        sys.exit(f"no map at {MAP} — run the bot's tools/build_manifesto_map.py first")
    nodes = json.loads(MAP.read_text(encoding="utf-8")).get("nodes", [])

    sections: list[tuple[str, str]] = []
    if a.verify or a.suggest:
        sections = load_manifesto()
        if not sections:
            print(f"[codex-anchors] manifesto not readable at {MANIFESTO} — set MANIFESTO_DIR.")
            print("[codex-anchors] verify/suggest need the source text; skipping those modes.")
            a.verify = a.suggest = a.apply = False
        else:
            print(f"[codex-anchors] {len(sections)} manifesto section file(s) loaded\n")

    rows, bare, wrong, placed, unplaced = [], [], [], 0, 0
    for n in nodes:
        secs = [re.sub(r"\s+", " ", s).strip() for s in (n.get("sections") or [])]
        titles = {t.lower(): i for i, t in enumerate(secs)}
        for c in n.get("canon") or []:
            f = lesson_path(c.get("url", ""))
            if f is None:
                continue
            src = f.read_text(encoding="utf-8", errors="replace")
            declared = sorted({int(x) for x in SEC_RE.findall(src)})
            rows.append((n["id"], len(secs), declared, f.relative_to(ROOT).as_posix()))
            if not declared:
                bare.append(rows[-1])

            if not (a.verify or a.suggest) or not secs:
                continue
            blk = CANON_RE.search(src)
            if not blk:
                continue
            edits = []
            for m in BLOCK_RE.finditer(blk.group(1)):
                says = int(m.group(1)) if m.group(1) else None
                truth = place(norm(m.group(2)), sections, titles)
                head = norm(m.group(2))[:54]
                if says and a.verify:
                    if truth is None:
                        print(f"  ?  {n['id']:<11} §{says} declared, source cannot confirm   <- {head}…")
                    elif truth != says:
                        print(f"  X  {n['id']:<11} §{says} declared, source says §{truth} "
                              f"({secs[truth - 1][:34]})   <- {head}…")
                        wrong.append((n["id"], f.name, says, truth))
                elif not says and a.suggest:
                    if truth:
                        print(f"  +  {n['id']:<11} §{truth:<3} {secs[truth - 1][:40]:<42} <- {head}…")
                        edits.append((m.group(0), truth)); placed += 1
                    else:
                        print(f"  -  {n['id']:<11} cannot be placed from the source         <- {head}…")
                        unplaced += 1
            if a.apply and edits:
                raw = f.read_bytes(); crlf = b"\r\n" in raw
                s = raw.decode("utf-8")
                if crlf:
                    s = s.replace("\r\n", "\n")
                wrote = 0
                for old, sec in edits:
                    new = old.replace('["p" =>', '["sec" => %d, "p" =>' % sec, 1)
                    if s.count(old) == 1:
                        s = s.replace(old, new); wrote += 1
                if wrote:
                    f.write_bytes((s.replace("\n", "\r\n") if crlf else s).encode("utf-8"))
                    print(f"     -> wrote {wrote} declaration(s) into {f.name}")

    if not (a.verify or a.suggest):
        for cid, nsec, d, rel in (bare if a.missing else rows):
            mark = "—" if not d else ",".join(f"§{x}" for x in d)
            print(f"  {cid:<12} {nsec:>2} section(s)   anchors {mark:<22} {rel}"
                  + ("  <- declares none" if not d else ""))

    print(f"\n[codex-anchors] {len(rows) - len(bare)}/{len(rows)} lesson(s) declare at least one anchor.")
    if a.suggest:
        print(f"[codex-anchors] placed {placed}, could not place {unplaced}"
              + (" (written)" if a.apply else " (dry run — pass --apply to write)"))
    if a.verify:
        print(f"[codex-anchors] verify: {len(wrong)} declared anchor(s) disagree with the manifesto.")
        if wrong:
            print("[codex-anchors] N is the section's POSITION in the Atlas list, not the manifesto's §N.")
    if a.strict and bare:
        return 1
    return 1 if (a.verify and wrong) else 0


if __name__ == "__main__":
    sys.exit(main())
