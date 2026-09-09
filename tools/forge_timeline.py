#!/usr/bin/env python3
"""forge_timeline — the Forge's work log, derived from the manifesto's git history.

WHY THIS IS NOT A SKILL. The Forge (forge.php) is generated from
data/manifesto-map.json, so the page cannot drift from the data. But the data's
timeline was hand-maintained, and on 2026-09-08 that showed: the largest
citation-integrity pass the corpus has had -- 56 condemned citations repaired to
zero across four volumes -- landed while the public ledger went on saying
"Nothing has moved in 17 days." A skill to update it would have been one more
thing to remember, which is the same failure class as the hand-written repo list
/gate-parity had to replace and the masterplan progress log that sat at "still
zero" for weeks. The manifesto's git log is already a complete, timestamped,
un-forgettable record. Derive from it and nobody has to remember anything.

WHAT IT DELIBERATELY DOES NOT TOUCH. `timeline.events` is the CHAPTER-STATE
ledger: js/atlas-timeline.js replays it to reconstruct each node's state at a
date (it reads e.state as canon/preached/forge), and forge.php measures
"days since the last change" from its newest entry. Feeding commits into it
would break the replay with states it does not understand AND permanently
silence the stall headline -- a docs commit would reset the clock on a project
whose honest signal is that chapters are NOT moving. That headline is the
single most valuable thing on the page. So work goes in `timeline.work`, a
separate array, and the two clocks stay separate:

    timeline.events -> chapters reaching canon/forge. Drives the stall warning.
    timeline.work   -> commits against the book. Shows the maintenance is real.

Both are true at once, and printing both is the point: the corpus is being
repaired while the chapters still are not moving.

    python tools/forge_timeline.py            # regenerate into the map
    python tools/forge_timeline.py --check    # exit 1 if the map is stale
    python tools/forge_timeline.py --selftest # prove it can detect staleness

A commit controls its own entry with a trailer:
    Forge: <the sentence the public ledger should carry>
    Forge: skip

Config: MANIFESTO_DIR (default C:\\Users\\Rage\\Documents\\The OD9 Manifesto).
Exit 0 clean, 1 stale/findings, 2 cannot run.
"""
from __future__ import annotations

import argparse
import json
import os
import re
import subprocess
import sys
from pathlib import Path

sys.stdout.reconfigure(encoding="utf-8", errors="replace")

ROOT = Path(__file__).resolve().parents[1]
MAP = ROOT / "data" / "manifesto-map.json"
MANIFESTO = Path(os.environ.get("MANIFESTO_DIR", r"C:\Users\Rage\Documents\The OD9 Manifesto"))

# A commit counts as work on the book when it touches the book. Ops docs
# (worklists, the masterplan, tools/) are how we talk ABOUT the work; they
# reach the ledger only when a commit opts in with a Forge: trailer.
CONTENT_DIRS = ("volume ", "preface", "sermons/")
CH_RE = re.compile(r"[Cc]hapter[ -]?(\d+)")
TRAILER_RE = re.compile(r"^Forge:[ \t]*(.+?)[ \t]*$", re.MULTILINE)
# "purge(persubj): person-as-subject studies treated" is an engineering record, not
# public copy. The type survives as a tag the page can show; the sentence has to
# read as a sentence, per the house rule that every line decodes for a reader.
# Handles compound prefixes too -- this corpus has "purge(ch27 s11) + feat(gate): ..."
# and a single-prefix rule leaves that whole mess sitting in the public sentence.
CONVENTIONAL_RE = re.compile(
    r"^(?P<type>[a-z]+)(?:\([^)]*\))?"
    r"(?:\s*\+\s*[a-z]+(?:\([^)]*\))?)*!?:\s*(?P<rest>.+)$")
SEP = "\x1e"          # record separator; commit messages contain newlines
FIELD = "\x1f"


def git(*args: str) -> tuple[int, str]:
    p = subprocess.run(["git", "-C", str(MANIFESTO), *args],
                       capture_output=True, text=True, encoding="utf-8",
                       errors="replace", timeout=120)
    return p.returncode, p.stdout


def scope_of(paths: list[str]) -> str:
    """A human label for what a commit touched: 'Ch 1-4, 7', 'Vols 2-4', 'sermons'.

    Chapter numbers, compressed to ranges. A commit spanning many volumes says so
    instead of listing twenty chapters, because a ledger line has to be readable.
    """
    chs, vols, other = set(), set(), set()
    for p in paths:
        low = p.lower()
        if low.startswith("volume "):
            try:
                vols.add(int(p.split(" ", 2)[1].rstrip(" -")))
            except (ValueError, IndexError):
                pass
            m = CH_RE.search(p)
            if m:
                chs.add(int(m.group(1)))
        elif low.startswith("preface"):
            other.add("Preface")
        elif low.startswith("sermons/"):
            other.add("sermons")

    if len(vols) > 2 and len(chs) > 6:
        return "Vols " + "-".join(str(v) for v in (min(vols), max(vols)))
    if chs:
        runs, ordered = [], sorted(chs)
        start = prev = ordered[0]
        for c in ordered[1:]:
            if c == prev + 1:
                prev = c
                continue
            runs.append((start, prev))
            start = prev = c
        runs.append((start, prev))
        label = ", ".join(str(a) if a == b else f"{a}-{b}" for a, b in runs)
        # Short enough to sit in a fixed column beside the sentence. A label that
        # overflows pushes every row out of alignment, so a sprawling list of
        # chapters collapses to its span rather than being truncated mid-number.
        return ("Ch " + label) if len(label) <= 12 else f"Ch {ordered[0]}-{ordered[-1]}"
    return ", ".join(sorted(other)) or "the book"


def derive() -> list[dict]:
    rc, out = git("log", "--no-merges", "--date=short",
                  f"--format={SEP}%h{FIELD}%ad{FIELD}%s{FIELD}%b{FIELD}", "--name-only")
    if rc:
        return []
    work: list[dict] = []
    for rec in out.split(SEP):
        if not rec.strip():
            continue
        parts = rec.split(FIELD)
        if len(parts) < 4:
            continue
        sha, date, subject, body = parts[0].strip(), parts[1].strip(), parts[2].strip(), parts[3]
        paths = [ln.strip() for ln in parts[-1].splitlines() if ln.strip()] if len(parts) > 4 else []
        if not paths:                       # --name-only trails the body in the last field
            paths = [ln.strip() for ln in body.splitlines() if "/" in ln and ln.strip()]

        trailer = TRAILER_RE.search(body)
        said = trailer.group(1).strip() if trailer else None
        if said and said.lower() == "skip":
            continue

        touches_book = any(p.lower().startswith(CONTENT_DIRS) for p in paths)
        if not touches_book and not said:
            continue

        kind = ""
        text = said or subject
        if not said and (m := CONVENTIONAL_RE.match(text)):
            kind, text = m.group("type"), m.group("rest")
        text = text[:1].upper() + text[1:] if text else text

        work.append({
            "at": date,
            "sha": sha,
            "scope": scope_of(paths),
            "kind": kind,
            "what": text,
            "src": "git",
        })
    work.sort(key=lambda e: (e["at"], e["sha"]), reverse=True)
    return work


def load_map() -> dict:
    return json.loads(MAP.read_text(encoding="utf-8"))


def apply(doc: dict, work: list[dict]) -> dict:
    doc.setdefault("timeline", {})["work"] = work
    return doc


def write(doc: dict) -> None:
    MAP.write_text(json.dumps(doc, indent=1, ensure_ascii=False) + "\n", encoding="utf-8")


def selftest() -> int:
    """A generator nobody checks is a generator that stops matching its source.
    Prove the staleness check can FAIL, and that scope labelling is not a constant."""
    ok = True
    cases = [
        (["Volume 1 - Foundation and Vision/Chapter 1  X/Markdown Files/a.md",
          "Volume 1 - Foundation and Vision/Chapter 2  Y/Markdown Files/b.md"], "Ch 1-2"),
        (["Volume 1 - Foundation and Vision/Chapter 1  X/a.md",
          "Volume 1 - Foundation and Vision/Chapter 4  X/a.md"], "Ch 1, 4"),
        (["sermons/001.md"], "sermons"),
    ]
    for paths, want in cases:
        got = scope_of(paths)
        good = got == want
        ok &= good
        print(f"  {'OK  ' if good else 'FAIL'} scope {paths[0][:34]!r}... -> {got!r} (want {want!r})")

    if not MANIFESTO.is_dir():
        print("  SKIP staleness case — no manifesto checkout to derive from")
        return 0 if ok else 1

    work = derive()
    if not work:
        print("  FAIL derived zero work events from a repo that has commits")
        return 1
    print(f"  OK   derived {len(work)} work event(s) from git")

    doc = load_map()
    fresh = json.loads(json.dumps(doc))
    apply(fresh, work)
    stale = json.loads(json.dumps(fresh))
    stale["timeline"]["work"] = stale["timeline"]["work"][1:]      # drop the newest
    detected = stale["timeline"]["work"] != work
    ok &= detected
    print(f"  {'OK  ' if detected else 'FAIL'} a map missing the newest commit reads STALE")

    unchanged = fresh["timeline"]["work"] == work
    ok &= unchanged
    print(f"  {'OK  ' if unchanged else 'FAIL'} a regenerated map reads current")

    print("  selftest: " + ("the check can fail, so a pass means something" if ok
                            else "THE CHECK CANNOT FAIL — it is decoration"))
    return 0 if ok else 1


def main() -> int:
    ap = argparse.ArgumentParser(description=__doc__.split("\n\n")[0])
    ap.add_argument("--check", action="store_true", help="exit 1 if the map is stale")
    ap.add_argument("--selftest", action="store_true")
    a = ap.parse_args()

    if not MAP.is_file():
        print(f"forge-timeline: no map at {MAP}", file=sys.stderr)
        return 2
    if a.selftest:
        return selftest()

    if not MANIFESTO.is_dir():
        # Loud skip, never a silent pass: a machine without the manifesto checkout
        # cannot know whether the ledger is current, and saying so is the honest
        # answer. It does not block, or a laptop without the book could never deploy.
        print(f"forge-timeline: NOTE — no manifesto checkout at {MANIFESTO}; "
              "cannot verify the work log is current (set MANIFESTO_DIR)")
        return 0

    work = derive()
    doc = load_map()
    current = doc.get("timeline", {}).get("work", [])

    if a.check:
        if current == work:
            print(f"forge-timeline: work log current ({len(work)} entries, "
                  f"newest {work[0]['at'] if work else '—'})")
            return 0
        missing = [e for e in work if e not in current]
        print(f"forge-timeline: STALE — the ledger is missing {len(missing)} commit(s)")
        for e in missing[:8]:
            print(f"  {e['at']}  {e['sha']}  {e['scope']:<12} {e['what'][:70]}")
        print("  The Forge publishes this. Run: python tools/forge_timeline.py")
        return 1

    apply(doc, work)
    write(doc)
    print(f"forge-timeline: wrote {len(work)} work entries "
          f"({work[0]['at'] if work else '—'} newest) into {MAP.name}")
    return 0


if __name__ == "__main__":
    sys.exit(main())
