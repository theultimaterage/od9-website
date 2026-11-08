#!/usr/bin/env python3
"""Turn every hand-written chapter number in the mail and on the lesson pages into a macro.

After this, a renumber of the book is a registry sync: {{ch:ch4}} renders "Ch.3" today and
whatever the registry says tomorrow (tools/chapter_macros.py, includes/chapter_macros.php).

WHICH CHAPTER DID A NUMBER MEAN? Two rules, the same two the drip gate uses, and one fact
from git. A citation with evidence in its own line (a chapter title, or a linked Codex
lesson whose chapter absorbed or carries the number) names that chapter: the gate confirmed
these today, so they are the registry's current numbers. A BARE number (no title, no link)
was written under the OLD numbering: every line of the 33 weeklies predates the 2026-09-16
renumber except the 24 evidenced phrases fixed on 2026-11-08 (git log of email-templates/:
c8082d4 2026-08-27 is the last content commit before today), and the registry's `legacy`
lists cover every old number 1..67 exactly once. So a bare N becomes the node whose legacy
carries N. A number no node owns is left as written and listed for the founder's read.

LESSON PAGES: the eyebrow's "Chapter N" / "Appendix A" and the Source line's
"Chapter&nbsp;N (<em>Title</em>)" become {{chapter:ID}} and
{{chapter_nbsp:ID}} (<em>{{chtitle:ID}}</em>), where ID is the registry node whose canon
list names this lesson; the literal number must equal that node's current number or the
page is refused (a disagreement is a finding for a human, not a silent repoint).

Exact-site edits, position-addressed, each reported; a second run finds no literal and
changes nothing. --table-json writes the rows the preview page is built from.

    python tools/migrate_citations_to_macros_2026_11_08.py              # plan
    python tools/migrate_citations_to_macros_2026_11_08.py --apply
    python tools/migrate_citations_to_macros_2026_11_08.py --selftest

Provers: --selftest (title, link and legacy rules on a fixture; a lesson's eyebrow and
Source; an unowned number left literal and listed; idempotent); then
tools/drip_refs_check.py --check (macros resolved before judging; a literal number in a
template is a finding) and tools/chapter_macros_parity.py.
"""
from __future__ import annotations

import argparse
import glob
import importlib.util
import json
import os
import re
import shutil
import sys
import tempfile

sys.stdout.reconfigure(encoding="utf-8", errors="replace")  # type: ignore[attr-defined]
HERE = os.path.dirname(os.path.abspath(__file__))
ROOT = os.path.dirname(HERE)


def _load(name: str):
    # registered in sys.modules: drip_refs_check carries a @dataclass, and dataclasses
    # resolve annotations through sys.modules[cls.__module__]
    spec = importlib.util.spec_from_file_location(name, os.path.join(HERE, name + ".py"))
    mod = importlib.util.module_from_spec(spec)  # type: ignore[arg-type]
    sys.modules[name] = mod
    spec.loader.exec_module(mod)  # type: ignore[union-attr]
    return mod


drc = _load("drip_refs_check")
cm = _load("chapter_macros")

TOKEN = re.compile(r"Ch(apter|\.)\s*(\d{1,2})\b")
LINK_SPAN = re.compile(r"\[([^\]]*)\]\(https://offda9\.com/curriculum/([\w-]+/[\w-]+\.php)\)")
EYEBROW = re.compile(r'("eyebrow"\s*=>\s*"[^"]*?)(Chapter (\d{1,2})|Appendix ([A-Z]))(")')
SOURCE_CH = re.compile(r'("source"\s*=>\s*"(?:[^"\\]|\\.)*?)(Chapter|Appendix)&nbsp;(\w+)((?:\s*\(<em>)([^<]*)(</em>\)))?')


def plan_mail(text: str, reg) -> tuple[str, list[dict]]:
    """Rewrite every chapter number in one template; rows describe each site."""
    rows: list[dict] = []
    out_lines = []
    for ln_no, line in enumerate(text.split("\n"), 1):
        # A link is evidence for the number INSIDE its text ([**Ch.4**](url)), never for
        # a number elsewhere in the sentence: "**Ch.12** and [**Ch.19**](religious...)"
        # says nothing about Ch.12. The first version of this rule borrowed the line's
        # only link for every number in the line and repointed eleven bare numbers at
        # the wrong chapter; the preview table caught it before anything shipped.
        spans = [(lm.start(1), lm.end(1), reg.node_for_lesson(lm.group(2)))
                 for lm in LINK_SPAN.finditer(line)]
        edits: list[tuple[int, int, str]] = []
        for m in drc.REF.finditer(line):
            n = int(m.group(1))
            cited = drc._cited_title(m.group(2), m.group(3))
            node, rule = None, ""
            if cited:
                node = reg.node_for_title(cited)
                rule = "title"
            if node is None:
                inside = [nd for s, e, nd in spans if s <= m.start(0) < e and nd is not None]
                if inside:
                    node, rule = inside[0], "link"
            if node is None:
                node = reg.by_legacy.get(n)
                rule = "legacy"
            tok = TOKEN.search(line, m.start(0))
            if tok is None or tok.start() > m.start(0) + 2:
                continue
            literal = tok.group(0)
            if node is None:
                rows.append({"line": ln_no, "literal": literal, "rule": "UNOWNED", "id": None,
                             "now": literal, "before": literal})
                continue
            kind = "chapter" if tok.group(1) == "apter" else "ch"
            macro = "{{%s:%s}}" % (kind, node["id"])
            edits.append((tok.start(), tok.end(), macro))
            rows.append({"line": ln_no, "literal": literal, "rule": rule, "id": node["id"],
                         "before": literal, "now": cm.render(kind, node),
                         "title": node["title"]})
        for s, e, rep in sorted(edits, reverse=True):
            line = line[:s] + rep + line[e:]
        out_lines.append(line)
    return "\n".join(out_lines), rows


def plan_lesson(text: str, rel: str, reg) -> tuple[str, list[dict], list[str]]:
    """Eyebrow + Source of one lesson file -> macros for the node that owns the lesson."""
    node = reg.node_for_lesson(rel)
    rows: list[dict] = []
    problems: list[str] = []
    if node is None:
        return text, rows, problems
    want = str(node["num"])

    def eyebrow(m):
        said = m.group(3) or m.group(4)
        if said != want:
            problems.append(f"{rel}: eyebrow says {m.group(2)} but the registry owner is {node['id']} (num {want})")
            return m.group(0)
        rows.append({"file": rel, "field": "eyebrow", "before": m.group(2), "id": node["id"],
                     "now": cm.render("chapter", node)})
        return m.group(1) + "{{chapter:%s}}" % node["id"] + m.group(5)
    text = EYEBROW.sub(eyebrow, text, count=1)

    def source(m):
        said = m.group(3)
        if said != want:
            problems.append(f"{rel}: Source says {m.group(2)} {said} but the registry owner is {node['id']} (num {want})")
            return m.group(0)
        rep = m.group(1) + "{{chapter_nbsp:%s}}" % node["id"]
        before = f"{m.group(2)}&nbsp;{said}"
        now = cm.render("chapter_nbsp", node)
        if m.group(4):
            rep += " (<em>{{chtitle:%s}}</em>)" % node["id"]
            before += f" (<em>{m.group(5)}</em>)"
            now += f" (<em>{cm.render('chtitle', node)}</em>)"
        rows.append({"file": rel, "field": "source", "before": before, "id": node["id"], "now": now})
        return rep
    text = SOURCE_CH.sub(source, text, count=1)
    return text, rows, problems


def mail_files(root: str) -> list[str]:
    return sorted(glob.glob(os.path.join(root, "email-templates", "*.html"))) + \
        sorted(glob.glob(os.path.join(root, "email-templates", "lovelogic-weekly", "email-week-*.md")))


def lesson_files(root: str) -> list[str]:
    return [p for p in sorted(glob.glob(os.path.join(root, "curriculum", "*", "*.php")))
            if not os.path.basename(p).startswith("_")]


def run(root: str, registry_path: str, apply: bool, table_json: str | None = None) -> int:
    reg = drc.Registry(registry_path)
    changes: dict[str, str] = {}
    table = {"mail": [], "lessons": [], "unowned": [], "problems": []}
    for p in mail_files(root):
        rel = os.path.relpath(p, root).replace("\\", "/")
        with open(p, encoding="utf-8", newline="") as fh:
            text = fh.read()
        new, rows = plan_mail(text, reg)
        for r in rows:
            r["file"] = rel
            (table["unowned"] if r["rule"] == "UNOWNED" else table["mail"]).append(r)
        if new != text:
            changes[p] = new
    for p in lesson_files(root):
        rel = os.path.relpath(p, root).replace("\\", "/")
        lesson_rel = rel.split("curriculum/", 1)[1]
        with open(p, encoding="utf-8", newline="") as fh:
            text = fh.read()
        new, rows, problems = plan_lesson(text, lesson_rel, reg)
        table["lessons"] += rows
        table["problems"] += problems
        if new != text:
            changes[p] = new
    by_rule: dict[str, int] = {}
    for r in table["mail"]:
        by_rule[r["rule"]] = by_rule.get(r["rule"], 0) + 1
    print(f"mail: {len(table['mail'])} citation(s) -> macros ({', '.join(f'{k} {v}' for k, v in sorted(by_rule.items()))}); "
          f"{len(table['unowned'])} left literal (no owner); lessons: {len(table['lessons'])} field(s); "
          f"{len(changes)} file(s) would change")
    for r in table["unowned"]:
        print(f"  UNOWNED {r['file']}:{r['line']} {r['literal']}")
    for pr in table["problems"]:
        print("  PROBLEM " + pr)
    if table_json:
        with open(table_json, "w", encoding="utf-8") as fh:
            json.dump(table, fh, indent=1, ensure_ascii=False)
    if table["problems"]:
        print("REFUSED: a lesson's literal number disagrees with the registry's owner; nothing written")
        return 1
    if apply:
        for p, new in changes.items():
            with open(p, "w", encoding="utf-8", newline="") as fh:
                fh.write(new)
            print("  wrote " + os.path.relpath(p, root))
    elif changes:
        print("(plan only; --apply to write)")
    return 0


FIX_REG = {"nodes": [
    {"id": "ch1", "num": "1", "title": "The OD9 Creed", "legacy": [1], "vol": 1,
     "canon": [{"title": "The OD9 Creed", "url": "https://offda9.com/curriculum/observer/the-creed.php"}]},
    {"id": "ch4", "num": "3", "title": "The Great Awakening", "legacy": [4], "vol": 1,
     "canon": [{"title": "The Twin Singularities", "url": "https://offda9.com/curriculum/observer/the-twin-singularities.php"}]},
    {"id": "ch5", "num": "4", "title": "The System In Crisis", "legacy": [5], "vol": 1, "canon": []},
    {"id": "ch11", "num": "8", "title": "Educational System Collapse", "legacy": [11], "vol": 2, "canon": []},
    {"id": "appendix-a", "num": "A", "title": "Appendix A — Methods & the Evidence Standard", "legacy": [2], "vol": 1,
     "canon": [{"title": "The Evidence Standard", "url": "https://offda9.com/curriculum/observer/the-evidence-standard.php"}]},
]}
FIX_WEEKLY = """---
**Subject:** Fixture
---

[**Ch.3**](https://offda9.com/curriculum/observer/the-twin-singularities.php) of the Manifesto (*The Great Awakening*) argues it.

**Ch.4** of the Manifesto (*The System In Crisis*) by title alone; **Ch.11** bare beside [**Ch.3**](https://offda9.com/curriculum/observer/the-twin-singularities.php), so the old numbering and not the neighbour's link; **Ch.99** nobody owns.

2. **Read [The Twin Singularities](https://offda9.com/curriculum/observer/the-twin-singularities.php)** — the lesson on Ch.3 (*The Great Awakening*).
"""
FIX_LESSON = """<?php
$lesson = [
  "title"     => "The Twin Singularities",
  "eyebrow"   => "Volume I &middot; Foundation &amp; Vision &nbsp;&bull;&nbsp; Chapter 3",
  "source" => "<b>Source &mdash;</b> The OD9 Manifesto, Volume&nbsp;1 &middot; Chapter&nbsp;3 (<em>The Great Awakening</em>), §IV: <em>The Twin Singularities</em>.",
];
"""
FIX_APPENDIX = """<?php
$lesson = [
  "eyebrow"   => "Volume I &middot; Foundation &amp; Vision &nbsp;&bull;&nbsp; Appendix A",
  "source" => "<b>Source &mdash;</b> The OD9 Manifesto, Volume&nbsp;1 &middot; Appendix&nbsp;A (<em>Methods and the Evidence Standard</em>), §III.",
];
"""
FIX_CREED = """<?php
$lesson = [
  "eyebrow"   => "Volume I &middot; Foundation &amp; Vision &nbsp;&bull;&nbsp; Chapter 1",
  "source" => "<b>Source &mdash;</b> The OD9 Manifesto, Volume&nbsp;1 &middot; Chapter&nbsp;1 §II: <em>The OD9 Creed (Formal Declaration)</em>.",
];
"""


def selftest() -> int:
    fails: list[str] = []
    tmp = tempfile.mkdtemp(prefix="macro_migrate_")
    try:
        os.makedirs(os.path.join(tmp, "email-templates", "lovelogic-weekly"))
        os.makedirs(os.path.join(tmp, "curriculum", "observer"))
        os.makedirs(os.path.join(tmp, "data"))
        regp = os.path.join(tmp, "data", "manifesto-map.json")
        with open(regp, "w", encoding="utf-8") as fh:
            json.dump(FIX_REG, fh)
        wk = os.path.join(tmp, "email-templates", "lovelogic-weekly", "email-week-01-fixture.md")
        with open(wk, "w", encoding="utf-8", newline="") as fh:
            fh.write(FIX_WEEKLY)
        for name, src in (("the-twin-singularities.php", FIX_LESSON), ("the-evidence-standard.php", FIX_APPENDIX),
                          ("the-creed.php", FIX_CREED)):
            with open(os.path.join(tmp, "curriculum", "observer", name), "w", encoding="utf-8", newline="") as fh:
                fh.write(src)
        tj = os.path.join(tmp, "table.json")
        rc = run(tmp, regp, apply=True, table_json=tj)
        with open(tj, encoding="utf-8") as fh:
            table = json.load(fh)
        rules = [(r["literal"], r["rule"], r["id"], r["now"]) for r in table["mail"]]
        want = [("Ch.3", "link", "ch4", "Ch.3"), ("Ch.4", "title", "ch5", "Ch.4"),
                ("Ch.11", "legacy", "ch11", "Ch.8"), ("Ch.3", "link", "ch4", "Ch.3"),
                ("Ch.3", "title", "ch4", "Ch.3")]
        if rc != 0 or rules != want:
            fails.append(f"mail rules wrong: rc={rc} {rules}")
        if [(r["literal"], r["line"]) for r in table["unowned"]] != [("Ch.99", 7)]:
            fails.append(f"the unowned number was not left literal and listed: {table['unowned']}")
        with open(wk, encoding="utf-8") as fh:
            text = fh.read()
        if "[**{{ch:ch4}}**](https" not in text or "**{{ch:ch5}}** of the Manifesto" not in text \
                or "**{{ch:ch11}}** bare" not in text or "**Ch.99** nobody" not in text or "lesson on {{ch:ch4}} (" not in text:
            fails.append("the weekly was not rewritten as planned:\n" + text)
        les = {r["file"].split("/")[-1] + ":" + r["field"]: (r["before"], r["now"]) for r in table["lessons"]}
        if les.get("the-twin-singularities.php:eyebrow") != ("Chapter 3", "Chapter 3") \
                or les.get("the-twin-singularities.php:source") != ("Chapter&nbsp;3 (<em>The Great Awakening</em>)",
                                                                    "Chapter&nbsp;3 (<em>The Great Awakening</em>)") \
                or les.get("the-evidence-standard.php:source") != ("Appendix&nbsp;A (<em>Methods and the Evidence Standard</em>)",
                                                                   "Appendix&nbsp;A (<em>Methods &amp; the Evidence Standard</em>)") \
                or les.get("the-creed.php:source") != ("Chapter&nbsp;1", "Chapter&nbsp;1"):
            fails.append(f"lesson fields wrong: {les}")
        with open(os.path.join(tmp, "curriculum", "observer", "the-twin-singularities.php"), encoding="utf-8") as fh:
            l1 = fh.read()
        if "&bull;&nbsp; {{chapter:ch4}}\"" not in l1 or "{{chapter_nbsp:ch4}} (<em>{{chtitle:ch4}}</em>), §IV" not in l1:
            fails.append("the lesson was not rewritten as planned:\n" + l1)
        with open(os.path.join(tmp, "curriculum", "observer", "the-creed.php"), encoding="utf-8") as fh:
            l3 = fh.read()
        if "{{chapter_nbsp:ch1}} §II: <em>The OD9 Creed (Formal Declaration)</em>" not in l3:
            fails.append("the Creed's section title must stay literal:\n" + l3)
        # resolving the rewritten weekly through the resolver gives today's numbers
        resolved, unknown = cm.resolve(text, {n["id"]: n for n in FIX_REG["nodes"]})
        if unknown or "[**Ch.3**](https" not in resolved or "**Ch.8** bare" not in resolved:
            fails.append(f"the rewritten weekly does not resolve to today's numbers: {unknown} {resolved[:200]}")
        # idempotent: a second run plans nothing new
        rc2 = run(tmp, regp, apply=True, table_json=tj)
        with open(tj, encoding="utf-8") as fh:
            table2 = json.load(fh)
        if rc2 != 0 or table2["mail"] or table2["lessons"] or [r["literal"] for r in table2["unowned"]] != ["Ch.99"]:
            fails.append(f"second run is not a no-op: {table2}")
        # a lesson whose literal disagrees with the registry owner is refused, not repointed
        bad = os.path.join(tmp, "curriculum", "observer", "the-evidence-standard.php")
        with open(bad, "w", encoding="utf-8", newline="") as fh:
            fh.write(FIX_APPENDIX.replace("Appendix A\"", "Chapter 2\"").replace("Appendix&nbsp;A", "Chapter&nbsp;2"))
        rc3 = run(tmp, regp, apply=True, table_json=tj)
        with open(bad, encoding="utf-8") as fh:
            still = fh.read()
        if rc3 != 1 or "Chapter&nbsp;2" not in still:
            fails.append(f"a disagreeing lesson was not refused: rc={rc3}")
    finally:
        shutil.rmtree(tmp, ignore_errors=True)
    n = 9
    if fails:
        print(f"SELFTEST FAIL ({len(fails)} of {n}):")
        for f in fails:
            print("  - " + f)
        return 1
    print(f"SELFTEST OK: {n} checks; title, link and legacy rules; an unowned number stays literal and is listed; "
          f"eyebrow and Source become macros (the Creed's section title stays); resolves to today's numbers; "
          f"a second run is a no-op; a lesson that disagrees with its owner is refused.")
    return 0


def main() -> int:
    ap = argparse.ArgumentParser(description=(__doc__ or "").split("\n")[0])
    ap.add_argument("--apply", action="store_true")
    ap.add_argument("--selftest", action="store_true")
    ap.add_argument("--table-json", default=None)
    a = ap.parse_args()
    if a.selftest:
        return selftest()
    return run(ROOT, os.path.join(ROOT, "data", "manifesto-map.json"), a.apply, a.table_json)


if __name__ == "__main__":
    raise SystemExit(main())
