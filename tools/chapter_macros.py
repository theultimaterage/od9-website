#!/usr/bin/env python3
"""Chapter macros: a citation names a chapter by its registry id, never by a number.

    {{ch:ch4}}            -> Ch.3            (short, the weeklies' form)
    {{chapter:ch4}}       -> Chapter 3       (long, the lesson pages' eyebrow)
    {{chapter_nbsp:ch4}}  -> Chapter&nbsp;3  (long, non-breaking, the lesson pages' Source line)
    {{chtitle:ch4}}       -> The Great Awakening   (HTML-escaped: & < > only, like PHP ENT_NOQUOTES)

The id is a node id of data/manifesto-map.json (ch4, ch22, v3-space, appendix-a, preface).
Node ids encode the number a chapter had when the id was minted and never move; `num`
is what the chapter is called today. So a renumber is a registry sync and every citation
follows it with no edit. A non-numeric num renders as a name: A -> "Appendix A",
P -> "Preface". A title that begins with its own label ("Appendix A — Methods & ...")
loses that prefix in {{chtitle}}, so "Appendix A (Methods & the Evidence Standard)" reads
once, not twice.

This is the Python half; includes/chapter_macros.php is the PHP half the senders and the
Codex renderer use at render time. They must agree byte for byte, and
tools/chapter_macros_parity.py proves they do over a fixture and over every template.
The drip gate (tools/drip_refs_check.py) and the prose gate (tools/prose_check.py) resolve
through this module before judging, so they judge what a subscriber or a reader sees.

    python tools/chapter_macros.py --selftest
    python tools/chapter_macros.py FILE      # print FILE resolved (unknown ids left as written, listed on stderr)

Provers: --selftest; tools/chapter_macros_parity.py (PHP == Python).
"""
from __future__ import annotations

import html
import json
import os
import re
import sys

if hasattr(sys.stdout, "reconfigure"):  # not when a selftest has redirected stdout to a StringIO
    sys.stdout.reconfigure(encoding="utf-8", errors="replace")  # type: ignore[attr-defined]

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
REGISTRY = os.path.join(ROOT, "data", "manifesto-map.json")

MACRO = re.compile(r"\{\{(ch|chapter|chapter_nbsp|chtitle):([A-Za-z0-9_-]+)\}\}")
KINDS = ("ch", "chapter", "chapter_nbsp", "chtitle")
NAMED = {"A": "Appendix A", "P": "Preface"}
LABEL_PREFIX = re.compile(r"^(Appendix\s+[A-Z]|Preface)\s*[—–-]\s*")


def load_registry(path: str = REGISTRY) -> dict[str, dict]:
    """{node id: node} from the website registry."""
    with open(path, encoding="utf-8") as fh:
        data = json.load(fh)
    nodes = data["nodes"] if isinstance(data, dict) else data
    return {str(n["id"]): n for n in nodes}


def label(node: dict, long: bool, nbsp: bool = False) -> str:
    num = str(node.get("num", ""))
    sep = "&nbsp;" if nbsp else " "
    if num.isdigit():
        return ("Chapter" + sep + num) if long else ("Ch." + num)
    name = NAMED.get(num, num)
    return name.replace(" ", sep) if nbsp else name


def title(node: dict) -> str:
    return html.escape(LABEL_PREFIX.sub("", str(node.get("title", ""))).strip(), quote=False)


def render(kind: str, node: dict) -> str:
    if kind == "ch":
        return label(node, long=False)
    if kind == "chapter":
        return label(node, long=True)
    if kind == "chapter_nbsp":
        return label(node, long=True, nbsp=True)
    return title(node)


def find(text: str) -> list[tuple[str, str]]:
    """Every (kind, id) macro in the text, in order."""
    return [(m.group(1), m.group(2)) for m in MACRO.finditer(text)]


def resolve(text: str, reg: dict[str, dict]) -> tuple[str, list[str]]:
    """Resolve every macro; an unknown id is left exactly as written and returned."""
    unknown: list[str] = []

    def sub(m: re.Match) -> str:
        node = reg.get(m.group(2))
        if node is None:
            unknown.append(m.group(2))
            return m.group(0)
        return render(m.group(1), node)
    return MACRO.sub(sub, text), unknown


def selftest() -> int:
    reg = {
        "ch4": {"id": "ch4", "num": "3", "title": "The Great Awakening"},
        "ch22": {"id": "ch22", "num": "19", "title": "Religious Institutions & Pseudoscience"},
        "appendix-a": {"id": "appendix-a", "num": "A", "title": "Appendix A — Methods & the Evidence Standard"},
        "preface": {"id": "preface", "num": "P", "title": "The Paradox of Transformation"},
    }
    cases = [
        ("[**{{ch:ch4}}**](u) of the Manifesto", "[**Ch.3**](u) of the Manifesto"),
        ("Vision &bull; {{chapter:ch4}}", "Vision &bull; Chapter 3"),
        ("Volume&nbsp;1 &middot; {{chapter_nbsp:ch4}} (<em>{{chtitle:ch4}}</em>)",
         "Volume&nbsp;1 &middot; Chapter&nbsp;3 (<em>The Great Awakening</em>)"),
        ("{{chtitle:ch22}}", "Religious Institutions &amp; Pseudoscience"),
        ("{{ch:appendix-a}} / {{chapter:appendix-a}} / {{chapter_nbsp:appendix-a}} / {{chtitle:appendix-a}}",
         "Appendix A / Appendix A / Appendix&nbsp;A / Methods &amp; the Evidence Standard"),
        ("{{chapter:preface}} ({{chtitle:preface}})", "Preface (The Paradox of Transformation)"),
        ("{{ch:ch4}} and {{ch:ch4}}", "Ch.3 and Ch.3"),
        ("no macro here, Ch.4 stays", "no macro here, Ch.4 stays"),
    ]
    fails = []
    for src, want in cases:
        got, unk = resolve(src, reg)
        if got != want or unk:
            fails.append(f"{src!r} -> {got!r} (want {want!r}; unknown {unk})")
    got, unk = resolve("{{ch:ch99}} and {{chtitle:nope}} and {{ch:ch4}}", reg)
    if got != "{{ch:ch99}} and {{chtitle:nope}} and Ch.3" or unk != ["ch99", "nope"]:
        fails.append(f"unknown ids are left as written and listed: {got!r} {unk}")
    if find("a {{ch:ch4}} b {{chtitle:ch22}}") != [("ch", "ch4"), ("chtitle", "ch22")]:
        fails.append("find() does not list macros in order")
    if resolve("{{CH:ch4}} {{ch:ch 4}} {{bogus:ch4}}", reg)[0] != "{{CH:ch4}} {{ch:ch 4}} {{bogus:ch4}}":
        fails.append("a malformed macro must be left alone, never half-resolved")
    # the live registry: every node id resolves in every kind, and ids are unique
    live = load_registry()
    if len(live) < 50 or any(not render(k, n) for n in live.values() for k in KINDS):
        fails.append("the live registry does not resolve every node in every kind")
    n = 12
    if fails:
        print(f"SELFTEST FAIL ({len(fails)} of {n}):")
        for f in fails:
            print("  - " + f)
        return 1
    print(f"SELFTEST OK: {n} checks; four kinds, named nums, label prefix stripped, titles escaped, "
          f"unknown ids left as written, malformed macros untouched, the live registry resolves.")
    return 0


def main(argv: list[str]) -> int:
    if "--selftest" in argv:
        return selftest()
    if len(argv) != 1:
        print(__doc__)
        return 2
    with open(argv[0], encoding="utf-8", newline="") as fh:
        text = fh.read()
    out, unknown = resolve(text, load_registry())
    sys.stdout.write(out)
    if unknown:
        sys.stderr.write("unknown chapter id(s): " + ", ".join(unknown) + "\n")
        return 1
    return 0


if __name__ == "__main__":
    raise SystemExit(main(sys.argv[1:]))
