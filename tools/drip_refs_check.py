#!/usr/bin/env python3
"""drip_refs_check — what the mail says about the manifesto has to be true of the manifesto.

THE PROBLEM. The 33 LoveLogic weeklies and the 19 drip fragments were written against the
previous edition of the book. Every weekly carries a "What the Manifesto pushes on" section
that cites chapters by number, and the numbers were the 67-chapter numbering; the registry
(data/manifesto-map.json) renumbered the book to 52 and the directories on disk followed
(The Great Awakening is Chapter 3, The Solution Framework is Chapter 5, Scientific Institution
Analysis is Chapter 10), and the approved rewrite plan takes it to 29. One fragment quotes a
sentence "— The OD9 Manifesto" that appears nowhere in the manuscript. The bot repo's
tools/audit_drip_refs.py found the numbering class in August 2026, was never given a caller,
and read its ground truth from a table-of-contents markdown rather than the registry. On
2026-11-08 the founder: "Go ahead and wire the drip-citation audit into the od9 deploy gate."

GROUND TRUTH, all derived, none typed here:
  chapter number -> node       data/manifesto-map.json `nodes[].num` (the current numbering)
  old number     -> node       `nodes[].legacy` (the numbers each chapter absorbed)
  chapter        -> lesson(s)  `nodes[].canon[].url` (the Codex pages that publish it)
  lesson page    -> its claim  curriculum/<tier>/<slug>.php "Source — ... Chapter N" line
  quoted sentence             the manuscript itself (every .md under the Preface and Volume
                              folders of the manifesto repo; MANIFESTO_DIR overrides the path)

FINDING CLASSES (code first, like the prose gate):
  NONEXISTENT     Ch.N is neither a current number nor a number any chapter absorbed.
  RENUMBERED      Ch.N is an old number: the sentence's own evidence (a cited title, a linked
                  lesson) names the chapter that absorbed it, which now carries another number.
  MISCITED        the sentence names a title that is not Ch.N's title, and no renumbering
                  explains it.
  DEAD            the sentence links a Codex page that is not in curriculum/, or an action item
                  tells the reader to read a chapter no Codex page publishes.
  LINKABLE        a correct citation of a chapter that has a live Codex lesson, unlinked.
  UNSOURCED_QUOTE a quoted sentence the mail attributes to the manifesto that the manuscript
                  does not contain (whitespace, case and quote marks normalised).
  LESSON_SOURCE   a Codex page whose own "Source — Chapter N" line disagrees with the registry
                  about which number its chapter carries. Not mail, but the page the mail links.

A limit, stated: a bare "Ch.22" with nothing else in the sentence points at whatever chapter 22
is today, and this tool accepts it. Only a title or a linked lesson can prove the author meant
the chapter that USED to be 22.

THE BASELINE IS SHRINK-ONLY (tools/drip_refs_baseline.json), like tools/prose_check.py: a unit
may not carry more findings of a class than recorded, and a new unit must be clean. Nothing is
blocked on day one; nothing can get worse; and when a rewritten chapter is promoted and the
registry moves, the units that cited what it absorbed light up by name.

    python tools/drip_refs_check.py                 # report every finding, by class
    python tools/drip_refs_check.py --unit <label>  # one unit's findings in full
    python tools/drip_refs_check.py --baseline      # record today's counts
    python tools/drip_refs_check.py --check         # exit 0 pass, 1 worse, 2 ground truth missing
    python tools/drip_refs_check.py --selftest      # every class fires on a known-bad fixture

tools/prod-deploy.py runs --check beside the prose gate; exit 2 prints UNCHECKED, never clean.
"""
from __future__ import annotations

import argparse
import glob
import html
import json
import os
import re
import sys
import tempfile
from dataclasses import dataclass, field

if hasattr(sys.stdout, "reconfigure"):
    sys.stdout.reconfigure(encoding="utf-8", errors="replace")  # type: ignore[attr-defined]

HERE = os.path.dirname(os.path.abspath(__file__))
ROOT = os.path.dirname(HERE)
sys.path.insert(0, HERE)
import chapter_macros as cm  # noqa: E402  — {{ch:ID}} resolved before judging, as the senders do

# A hand-written chapter number in a template or a lesson label. Since 2026-11-08 every
# citation is a macro; a literal is the next stale number waiting to happen.
LITERAL_NUMBER = re.compile(r"\b(?:Ch(?:apter|\.)(?:&nbsp;|\s)*\d{1,2}|Appendix(?:&nbsp;|\s)+[A-Z])\b")
EXIT_NO_TRUTH = 2
BOARD = "https://offda9.com/curriculum"
CLASSES = ("NONEXISTENT", "RENUMBERED", "MISCITED", "DEAD", "LINKABLE", "UNSOURCED_QUOTE",
           "LESSON_SOURCE", "LITERAL", "UNKNOWN_ID")


def manifesto_dir() -> str:
    """The manuscript: MANIFESTO_DIR, else the founder's Documents folder, else the same folder
    seen from WSL (the deploy runs there), else "" (quotes cannot be checked)."""
    env = os.environ.get("MANIFESTO_DIR")
    if env:
        return env
    cands = [r"C:\Users\Rage\Documents\The OD9 Manifesto",
             os.path.join(os.path.expanduser("~"), "Documents", "The OD9 Manifesto")]
    cands += sorted(glob.glob("/mnt/c/Users/*/Documents/The OD9 Manifesto"))
    for c in cands:
        if os.path.isdir(c):
            return c
    return ""


@dataclass
class Repo:
    root: str = ROOT
    registry: str = ""
    curriculum: str = ""
    weekly_glob: str = ""
    email_glob: str = ""
    manifesto: str = field(default_factory=manifesto_dir)
    baseline: str = ""

    def __post_init__(self):
        self.registry = self.registry or os.path.join(self.root, "data", "manifesto-map.json")
        self.curriculum = self.curriculum or os.path.join(self.root, "curriculum")
        # DRIP_WEEKLY_DIR lets a migration's selftest judge a temp copy of the weeklies
        # against the live registry and manuscript (tools/migrate_weekly_citations_*.py).
        self.weekly_glob = self.weekly_glob or os.path.join(
            os.environ.get("DRIP_WEEKLY_DIR") or os.path.join(self.root, "email-templates", "lovelogic-weekly"),
            "email-week-*.md")
        self.email_glob = self.email_glob or os.path.join(self.root, "email-templates", "*.html")
        self.baseline = self.baseline or os.path.join(self.root, "tools", "drip_refs_baseline.json")


# ── ground truth ─────────────────────────────────────────────────────────────────────────────

def _norm_title(s: str) -> str:
    s = html.unescape(s or "").replace("&", "and").replace("—", " ").replace("–", " ")
    return re.sub(r"[^a-z0-9]+", " ", s.lower()).strip()


class Registry:
    """The chapter registry: current numbers, absorbed numbers, and the Codex pages per chapter."""

    def __init__(self, path: str):
        with open(path, encoding="utf-8") as fh:
            data = json.load(fh)
        self.nodes = [n for n in data["nodes"] if isinstance(n, dict) and n.get("title")]
        self.by_num: dict[str, dict] = {}
        self.by_legacy: dict[int, dict] = {}
        self.by_title: dict[str, dict] = {}
        for n in self.nodes:
            self.by_num[str(n.get("num"))] = n
            self.by_title[_norm_title(n["title"])] = n
            for old in n.get("legacy") or []:
                try:
                    self.by_legacy.setdefault(int(old), n)
                except (TypeError, ValueError):
                    continue
        # A lesson's title names its chapter too ("Responsible Transformation" is a Codex page of
        # The Solution Framework); a chapter's own title wins when the two collide.
        for n in self.nodes:
            for title, _path in self.lessons(n):
                self.by_title.setdefault(_norm_title(title), n)
        if not self.by_num:
            raise ValueError("the registry has no numbered nodes")

    @staticmethod
    def legacy_of(node: dict) -> set[int]:
        return {int(x) for x in (node.get("legacy") or []) if str(x).isdigit()}

    def lessons(self, node: dict) -> list[tuple[str, str]]:
        """[(title, relative path under curriculum/)] for the pages that publish this chapter."""
        out = []
        for c in node.get("canon") or []:
            url = c.get("url") or ""
            m = re.search(r"/curriculum/([^/]+/[^/?#]+\.php)", url)
            if m:
                out.append((c.get("title") or "", m.group(1)))
        return out

    def node_for_title(self, title: str) -> dict | None:
        key = _norm_title(title)
        if key in self.by_title:
            return self.by_title[key]
        # "Religious Institutions & Pseudoscience" cited as "Religious Institutions and Pseudoscience",
        # "Mental Health and Collective Psyche" cited for "Mental Health": prefix either way.
        for k, n in self.by_title.items():
            if key and (k.startswith(key) or key.startswith(k)) and min(len(k), len(key)) >= 12:
                return n
        return None

    def node_for_lesson(self, rel: str) -> dict | None:
        rel = rel.replace("\\", "/").lower()
        for n in self.nodes:
            for _t, path in self.lessons(n):
                if path.lower() == rel:
                    return n
        return None


SOURCE_LINE = re.compile(r"Chapter(?:&nbsp;|\s)*(\d{1,2})", re.I)


def lesson_source_findings(reg: Registry, curriculum: str) -> list[dict]:
    """LESSON_SOURCE: a Codex page's own Source line names a number the registry does not give
    its chapter. Pages the registry does not list (the preface's, drafts) are not judged."""
    out = []
    for php in sorted(glob.glob(os.path.join(curriculum, "*", "*.php"))):
        if os.path.basename(php).startswith("_"):
            continue
        rel = os.path.relpath(php, curriculum).replace("\\", "/")
        node = reg.node_for_lesson(rel)
        if node is None:
            continue
        with open(php, encoding="utf-8", errors="replace") as fh:
            txt = fh.read()
        for field_name in ("eyebrow", "source"):
            m = re.search(r'"%s"\s*=>\s*"((?:[^"\\]|\\.)*)"' % field_name, txt)
            if not m:
                continue
            line_no = txt[:m.start()].count("\n") + 1
            shown = html.unescape(re.sub(r"<[^>]+>", "", m.group(1)))[:140]
            # Since 2026-11-08 the label is a macro naming the registry node that owns this
            # lesson ({{chapter:ch4}}); the renderer puts the number in. A macro naming another
            # node is a wrong landing; a literal number is the stale number waiting to happen.
            ids = [i for k, i in cm.find(m.group(1)) if k in ("chapter", "chapter_nbsp", "ch")]
            for other in sorted({i for i in ids if i != node["id"]}):
                out.append({"unit": "curriculum/" + rel, "kind": "lesson", "line": line_no,
                            "code": "LESSON_SOURCE", "chapter": None,
                            "note": f"the {field_name} names node {other}; the registry's canon list gives this "
                                    f"page to {node['id']} (\u201c{node['title']}\u201d, {cm.label(node, True)})",
                            "text": shown})
            sm = SOURCE_LINE.search(m.group(1))
            if sm and sm.group(1) != str(node.get("num")):
                out.append({"unit": "curriculum/" + rel, "kind": "lesson", "line": line_no,
                            "code": "LESSON_SOURCE", "chapter": int(sm.group(1)),
                            "note": f"the {field_name} says Chapter {sm.group(1)}; the registry gives "
                                    f"\u201c{node['title']}\u201d the number {node.get('num')}",
                            "text": shown})
            for lit in LITERAL_NUMBER.finditer(m.group(1)):
                out.append({"unit": "curriculum/" + rel, "kind": "lesson", "line": line_no,
                            "code": "LITERAL", "chapter": None,
                            "note": f"hand-written \u201c{lit.group(0)}\u201d in the {field_name}; write "
                                    f"{{{{chapter:{node['id']}}}}} so the registry carries the number",
                            "text": shown})
    return out


# ── the manuscript, for quotes ───────────────────────────────────────────────────────────────

def _norm_quote(s: str) -> str:
    s = html.unescape(s)
    for a, b in (("\u2019", "'"), ("\u2018", "'"), ("\u201c", '"'), ("\u201d", '"'),
                 ("\u2014", "-"), ("\u2013", "-"), ("\xa0", " ")):
        s = s.replace(a, b)
    return re.sub(r"[^a-z0-9]+", " ", s.lower()).strip()


class Manuscript:
    """Every .md under the Preface and Volume folders, normalised once, for verbatim lookup."""

    def __init__(self, path: str):
        self.path = path
        self.files = 0
        parts = []
        for top in sorted(os.listdir(path)):
            if not (top.startswith("Volume") or top.startswith("Preface")):
                continue
            for root, _dirs, files in os.walk(os.path.join(path, top)):
                for f in files:
                    if f.endswith(".md"):
                        with open(os.path.join(root, f), encoding="utf-8", errors="replace") as fh:
                            parts.append(_norm_quote(fh.read()))
                        self.files += 1
        self.text = "\n".join(parts)
        if not self.files:
            raise ValueError(f"no manuscript files under {path}")

    def contains(self, sentence: str) -> bool:
        return _norm_quote(sentence) in self.text


# ── units: weeklies and fragments as blocks of text ──────────────────────────────────────────

BLOCK_TAG = re.compile(r"</?(?:p|div|br|li|ul|ol|h[1-6]|blockquote|tr|td|th|table|section|article|"
                       r"header|footer|hr)\b[^>]*>", re.I)
ANY_TAG = re.compile(r"<[^>]+>")


def html_blocks(src: str) -> list[tuple[int, str]]:
    """[(block number, text)]: a fragment's copy split at block-level tags. Line numbers mean
    nothing in mail HTML, so the block's ordinal is the address."""
    src = re.sub(r"<!--.*?-->", "", src, flags=re.S)
    src = re.sub(r"<(script|style)\b.*?</\1>", "", src, flags=re.S | re.I)
    # keep each link's target beside its text, so a lesson linked in the fragment is seen
    # as linked (until 2026-11-08 the href went with the tag and a linked Creed read as
    # LINKABLE); the judge reads /curriculum/... wherever it sits in the line
    src = re.sub(r'<a\b[^>]*href="([^"]+)"[^>]*>(.*?)</a>', r"\2 (\1)", src, flags=re.S | re.I)
    src = BLOCK_TAG.sub("\n\n", src)
    src = ANY_TAG.sub("", src)
    src = html.unescape(src).replace("\xa0", " ")
    blocks = [re.sub(r"[ \t]+", " ", b).strip() for b in re.split(r"\n\s*\n", src) if b.strip()]
    return list(enumerate(blocks, 1))


def md_blocks(src: str) -> list[tuple[int, str]]:
    """[(first line number, text)]: a weekly's paragraphs, addressed by the line they start on."""
    out = []
    line = 1
    for piece in re.split(r"(\n\s*\n)", src):
        if piece.strip():
            lead = len(piece) - len(piece.lstrip("\n"))
            out.append((line + lead, piece.strip()))
        line += piece.count("\n")
    return out


def macro_findings(label: str, kind: str, raw: str, reg_by_id: dict) -> tuple[str, list[dict]]:
    """Resolve {{ch:ID}} macros the way the senders do and judge the template's own text:
    LITERAL for a hand-written chapter number (the next stale number), UNKNOWN_ID for a macro
    naming no node (the senders refuse to mail it). Returns (resolved text, findings)."""
    out = []
    where_key = "line" if kind == "weekly" else "block"
    blocks = md_blocks(raw) if kind == "weekly" else html_blocks(raw)
    for where, block in blocks:
        for m in LITERAL_NUMBER.finditer(block):
            out.append({"unit": label, "kind": kind, where_key: where, "code": "LITERAL", "chapter": None,
                        "note": f"hand-written “{m.group(0)}”; write a macro ({{{{ch:ID}}}}) so the "
                                f"registry carries the number", "text": block[:140]})
    resolved, unknown = cm.resolve(raw, reg_by_id)
    for uid in sorted(set(unknown)):
        where = next((w for w, b in blocks if f":{uid}}}}}" in b), blocks[0][0] if blocks else 1)
        out.append({"unit": label, "kind": kind, where_key: where, "code": "UNKNOWN_ID", "chapter": None,
                    "note": f"macro names no chapter: {uid} is not a node id of the registry", "text": uid})
    return resolved, out


def units_on_disk(repo: Repo, reg_by_id: dict | None = None
                  ) -> list[tuple[str, str, list[tuple[int, str]], list[dict]]]:
    """[(label, kind, blocks, macro findings)] for every weekly and every fragment the senders
    can mail, with chapter macros resolved first, as the senders resolve them."""
    reg_by_id = reg_by_id if reg_by_id is not None else cm.load_registry(repo.registry)

    def with_ids(raw: str, resolved: str, split) -> list:
        # (where, resolved block, node ids whose macros produced numbers in it); the raw and
        # the resolved text split into the same blocks, since a macro never adds a line
        ids = {w: frozenset(i for _, i in cm.find(b)) for w, b in split(raw)}
        return [(w, b, ids.get(w, frozenset())) for w, b in split(resolved)]
    out = []
    for p in sorted(glob.glob(repo.weekly_glob)):
        with open(p, encoding="utf-8", errors="replace") as fh:
            raw = fh.read()
        resolved, pre = macro_findings(os.path.basename(p), "weekly", raw, reg_by_id)
        out.append((os.path.basename(p), "weekly", with_ids(raw, resolved, md_blocks), pre))
    for p in sorted(glob.glob(repo.email_glob)):
        with open(p, encoding="utf-8", errors="replace") as fh:
            raw = fh.read()
        resolved, pre = macro_findings(os.path.basename(p), "email", raw, reg_by_id)
        out.append((os.path.basename(p), "email", with_ids(raw, resolved, html_blocks), pre))
    return out


# ── the judge ────────────────────────────────────────────────────────────────────────────────

REF = re.compile(
    r"\*{0,2}Ch(?:apter|\.)\s*(\d{1,2})\b\*{0,2}"
    r"(?:[ \w,'’-]{0,40}\(\s*\*{0,2}([^)*\n]{3,80}?)\*{0,2}\s*\)"     # (*Title*)
    r"|\s*[,—-]\s*\*{1,2}([^*\n]{3,80}?)\*{1,2})?")                   # , *Title*
ACTION = re.compile(r"^\s*\d+\.\s+\*\*(?:[^*]*\bRead\b[^*]*)\*\*", re.I)
NOT_A_TITLE = re.compile(r"^(see|cf|e\.?g|i\.?e|p{1,2}\.|pg|vol|and|or|the one|which|that|about|"
                         r"roughly|approx|~|it's called)\b", re.I)
LESSON_URL = re.compile(r"/curriculum/([\w-]+/[\w-]+\.php)")
QUOTE = re.compile(r"[\u201c\"]([^\u201d\"\n]{20,600})[\u201d\"]")
ATTRIB_BEFORE = re.compile(r"(says|said|puts? it|writes|wrote|reads|argues|calls it|words|:)\s*\*?$", re.I)
ATTRIB_AFTER = re.compile(r"^\s*[\u2014\u2013-]\s*(The\s+)?(OD9\s+)?Manifesto\b", re.I)
MIN_QUOTE_WORDS = 6


def _cited_title(*groups: str | None) -> str:
    for g in groups:
        g = (g or "").strip().strip("*").strip()
        if g and g[0].isupper() and len(g.split()) <= 9 and not NOT_A_TITLE.match(g):
            return g
    return ""


def judge_line(line: str, reg: Registry, curriculum: str,
               macro_ids: frozenset = frozenset()) -> tuple[list[dict], int]:
    """Findings for one line (a sentence or a list item), and how many bare numbers it carried
    that nothing in the line confirmed (the stated limit: those are taken at face value).
    macro_ids: node ids whose macros produced numbers in this line's block; a number equal to
    such a node's current number came from that macro and is confirmed by it."""
    out: list[dict] = []
    bare = 0
    is_action = bool(ACTION.match(line))
    linked = [m.group(1) for m in LESSON_URL.finditer(line)]
    linked_nodes = [x for x in (reg.node_for_lesson(rel) for rel in linked) if x is not None]
    macro_nodes = [n for n in reg.nodes if n.get("id") in macro_ids] if macro_ids else []
    for rel in linked:
        if not os.path.exists(os.path.join(curriculum, *rel.split("/"))):
            out.append({"code": "DEAD", "chapter": None, "note": f"links {rel}, which is not in curriculum/"})

    for m in REF.finditer(line):
        n = int(m.group(1))
        cited = _cited_title(m.group(2), m.group(3))
        current = reg.by_num.get(str(n))
        absorbed_by = reg.by_legacy.get(n)
        # The line's own evidence of which chapter it means: a title; else the one linked lesson
        # whose chapter absorbed this number; else the one linked lesson whose chapter carries
        # this number now (a line that links two lessons and cites both by their current
        # numbers is confirmed, not bare); else the only linked lesson. Legacy before current,
        # so an old number is still caught when another linked chapter wears it now.
        evidence = reg.node_for_title(cited) if cited else None
        if evidence is None:
            # the number came from a macro naming this node: confirmed by construction,
            # ahead of the legacy rule (the resolved Ch.8 beside a link to the chapter that
            # absorbed the OLD 8 is not a stale citation; it is the registry's own number)
            from_macro = [x for x in macro_nodes if str(x.get("num")) == str(n)]
            if len(from_macro) == 1:
                evidence = from_macro[0]
        if evidence is None:
            by_legacy = [x for x in linked_nodes if n in reg.legacy_of(x)]
            by_num = [x for x in linked_nodes if str(x.get("num")) == str(n)]
            distinct = {id(x): x for x in linked_nodes}
            if len({id(x) for x in by_legacy}) == 1:
                evidence = by_legacy[0]
            elif len({id(x) for x in by_num}) == 1:
                evidence = by_num[0]
            elif len(distinct) == 1 and len(REF.findall(line)) == 1:
                # the line's only link is evidence only when the line cites one chapter:
                # "**Ch.12** and [**Ch.19**](religious...)" says nothing about Ch.12
                evidence = next(iter(distinct.values()))

        if current is None and absorbed_by is None:
            out.append({"code": "NONEXISTENT", "chapter": n,
                        "note": f"no chapter carries or ever carried the number {n}"})
            continue
        meant = current
        confirmed = evidence is not None and evidence is current
        if evidence is not None and evidence is not current:
            if n in reg.legacy_of(evidence):
                out.append({"code": "RENUMBERED", "chapter": n,
                            "note": f"\u201c{evidence['title']}\u201d was {n}; it is Chapter {evidence.get('num')} now"})
                meant = evidence
            elif cited:
                out.append({"code": "MISCITED", "chapter": n,
                            "note": f"cited \u201c{cited}\u201d, which is Chapter {evidence.get('num')}; Chapter {n} is "
                                    f"\u201c{current['title'] if current else '(no current chapter)'}\u201d"})
                meant = None
        elif current is None and absorbed_by is not None:
            out.append({"code": "RENUMBERED", "chapter": n,
                        "note": f"\u201c{absorbed_by['title']}\u201d was {n}; it is Chapter {absorbed_by.get('num')} now"})
            meant = absorbed_by
        elif cited and evidence is None and current is not None \
                and _norm_title(cited) != _norm_title(current["title"]):
            out.append({"code": "MISCITED", "chapter": n,
                        "note": f"cited \u201c{cited}\u201d; Chapter {n} is \u201c{current['title']}\u201d"})
            meant = None
        elif evidence is None and current is not None:
            bare += 1

        if meant is None:
            continue
        lessons = reg.lessons(meant)
        if lessons and confirmed and not any(path in line for _t, path in lessons):
            out.append({"code": "LINKABLE", "chapter": n,
                        "note": f"\u201c{meant['title']}\u201d has a Codex lesson: {BOARD}/{lessons[0][1]}"})
        elif not lessons and is_action:
            out.append({"code": "DEAD", "chapter": n,
                        "note": f"asks the reader to read \u201c{meant['title']}\u201d, which no Codex page publishes"})
    return out, bare


def judge_block(block: str, reg: Registry, curriculum: str,
                macro_ids: frozenset = frozenset()) -> tuple[list[dict], int]:
    """A block's lines, judged one by one (a markdown list is one block of several items).
    macro_ids: the node ids whose macros produced numbers in this block (see units_on_disk)."""
    out: list[dict] = []
    bare = 0
    for line in block.splitlines():
        found, b = judge_line(line, reg, curriculum, macro_ids)
        out += found
        bare += b
    return out, bare


def quote_findings(block: str, nxt: str, ms: Manuscript) -> list[dict]:
    out = []
    for m in QUOTE.finditer(block):
        s = m.group(1)
        if len(s.split()) < MIN_QUOTE_WORDS:
            continue
        before = block[max(0, m.start() - 160):m.start()]
        attributed = ("anifesto" in before and bool(ATTRIB_BEFORE.search(before.rstrip()))) \
            or bool(ATTRIB_AFTER.match(nxt)) \
            or bool(ATTRIB_AFTER.match(block[m.end():].lstrip()))
        if attributed and not ms.contains(s):
            out.append({"code": "UNSOURCED_QUOTE", "chapter": None,
                        "note": "attributed to the manifesto; the manuscript does not contain it",
                        "quote": s[:160]})
    return out


def judge_unit(label: str, kind: str, blocks: list[tuple[int, str]], reg: Registry, curriculum: str,
               ms: Manuscript | None) -> tuple[list[dict], int]:
    out = []
    bare = 0
    for i, item in enumerate(blocks):
        where, block = item[0], item[1]
        macro_ids = item[2] if len(item) > 2 else frozenset()
        nxt = blocks[i + 1][1] if i + 1 < len(blocks) else ""
        found, b = judge_block(block, reg, curriculum, macro_ids)
        bare += b
        if ms is not None:
            found += quote_findings(block, nxt, ms)
        for f in found:
            f.update({"unit": label, "kind": kind, "text": block[:140],
                      "line" if kind == "weekly" else "block": where})
        out += found
    return out, bare


def all_findings(repo: Repo) -> tuple[list[dict], dict]:
    """Every finding across the mail and the lesson pages, plus the ground-truth summary.
    Raises FileNotFoundError / ValueError when the registry or the manuscript is missing."""
    if not os.path.exists(repo.registry):
        raise FileNotFoundError(f"the registry is not at {repo.registry}")
    if not os.path.isdir(repo.curriculum):
        raise FileNotFoundError(f"curriculum/ is not at {repo.curriculum}")
    reg = Registry(repo.registry)
    if not repo.manifesto or not os.path.isdir(repo.manifesto):
        raise FileNotFoundError("the manuscript is not on this machine (set MANIFESTO_DIR); "
                                "quotes cannot be checked")
    ms = Manuscript(repo.manifesto)
    findings: list[dict] = []
    units = units_on_disk(repo, {n["id"]: n for n in reg.nodes if n.get("id")})
    bare = 0
    for label, kind, blocks, pre in units:
        found, b = judge_unit(label, kind, blocks, reg, repo.curriculum, ms)
        findings += pre + found
        bare += b
    findings += lesson_source_findings(reg, repo.curriculum)
    summary = {"chapters": len(reg.by_num), "absorbed_numbers": len(reg.by_legacy),
               "lessons": sum(len(reg.lessons(n)) for n in reg.nodes),
               "manuscript_files": ms.files, "units": len(units), "bare_numbers": bare}
    return findings, summary


def codes_of(findings: list[dict]) -> dict[str, dict[str, int]]:
    """unit -> {CODE: count}"""
    out: dict[str, dict[str, int]] = {}
    for f in findings:
        out.setdefault(f["unit"], {})
        out[f["unit"]][f["code"]] = out[f["unit"]].get(f["code"], 0) + 1
    return out


# ── report / baseline / check ────────────────────────────────────────────────────────────────

def _line(f: dict) -> str:
    ch = f" Ch.{f['chapter']}" if f.get("chapter") else ""
    where = f"{f['unit']}" + (f":{f['line']}" if f.get("line") else f" #{f.get('block', '')}")
    extra = f"  \u201c{f['quote']}\u201d" if f.get("quote") else ""
    return f"  {where}{ch}: {f['note']}{extra}"


def report(repo: Repo) -> int:
    try:
        findings, summary = all_findings(repo)
    except (FileNotFoundError, ValueError) as e:
        print(f"UNCHECKED: {e}")
        return EXIT_NO_TRUTH
    by: dict[str, list[dict]] = {}
    for f in findings:
        by.setdefault(f["code"], []).append(f)
    for code in CLASSES:
        rows = by.get(code, [])
        if rows:
            print(f"\n=== {code} ({len(rows)}) ===")
            for f in rows:
                print(_line(f))
    units_hit = len(codes_of(findings))
    print(f"\n{len(findings)} finding(s) in {units_hit} unit(s) of {summary['units']} mail units; "
          f"registry: {summary['chapters']} chapters, {summary['absorbed_numbers']} absorbed numbers, "
          f"{summary['lessons']} Codex pages; manuscript: {summary['manuscript_files']} files.")
    literals = sum(1 for f in findings if f["code"] == "LITERAL")
    if literals:
        print(f"{summary['bare_numbers']} bare chapter number(s) carried no title and no lesson link and were "
              f"taken at face value (the stated limit; a number alone cannot show which edition it meant).")
    else:
        print(f"{summary['bare_numbers']} citation(s) carry no title and no lesson link in their sentence; every "
              f"number in the mail is a macro, so each is the registry's current number for the chapter it names.")
    return 1 if findings else 0


def write_baseline(repo: Repo) -> int:
    try:
        findings, summary = all_findings(repo)
    except (FileNotFoundError, ValueError) as e:
        print(f"UNCHECKED: {e}")
        return EXIT_NO_TRUTH
    import datetime as dt
    codes = codes_of(findings)
    data = {"_doc": "Shrink-only baseline for tools/drip_refs_check.py --check: a unit may not carry more "
                    "findings of a class than recorded here, and a unit not listed must be clean. Rewrite the "
                    "copy, then --baseline records the smaller number. Never edit by hand to raise a count.",
            "written": dt.date.today().isoformat(), "summary": summary,
            "units": {u: dict(sorted(c.items())) for u, c in sorted(codes.items())}}
    with open(repo.baseline, "w", encoding="utf-8", newline="\n") as fh:  # LF into an LF tree, on Windows too
        json.dump(data, fh, indent=1, ensure_ascii=False)
        fh.write("\n")
    print(f"baseline written: {len(findings)} finding(s) over {len(codes)} unit(s) -> {repo.baseline}")
    return 0


def check(repo: Repo) -> int:
    try:
        findings, summary = all_findings(repo)
    except (FileNotFoundError, ValueError) as e:
        print(f"UNCHECKED: {e}")
        return EXIT_NO_TRUTH
    if not os.path.exists(repo.baseline):
        print(f"no baseline at {repo.baseline}; run --baseline first")
        return EXIT_NO_TRUTH
    with open(repo.baseline, encoding="utf-8") as fh:
        base = json.load(fh).get("units", {})
    now = codes_of(findings)
    worse, improved = [], []
    for unit in sorted(set(base) | set(now)):
        b, n = base.get(unit, {}), now.get(unit, {})
        for code in CLASSES:
            if n.get(code, 0) > b.get(code, 0):
                worse.append((unit, code, b.get(code, 0), n.get(code, 0)))
            elif n.get(code, 0) < b.get(code, 0):
                improved.append((unit, code, b.get(code, 0), n.get(code, 0)))
    for unit, code, was, is_ in improved:
        print(f"  improved: {unit} {code} {was} -> {is_} (run --baseline to record it)")
    if worse:
        print("REFUSED: a unit carries more findings than its baseline:")
        for unit, code, was, is_ in worse:
            print(f"  {unit} {code} {was} -> {is_}")
            for f in findings:
                if f["unit"] == unit and f["code"] == code:
                    print("  " + _line(f))
        return 1
    # The macros are rendered by PHP at send time and judged by Python here; the two must
    # agree byte for byte or this gate judges a text no subscriber sees.
    import chapter_macros_parity as parity
    prc = parity.run()
    if prc == 1:
        print("REFUSED: the PHP and Python chapter-macro resolvers disagree (see above)")
        return 1
    if prc == 2:
        print("UNCHECKED: php is not on PATH, so the PHP resolver could not be compared with this gate's")
        return EXIT_NO_TRUTH
    print(f"PASS: {len(findings)} finding(s) over {summary['units']} mail units, none above baseline "
          f"({len(base)} baselined units).")
    return 0


def show_unit(repo: Repo, label: str) -> int:
    try:
        findings, _ = all_findings(repo)
    except (FileNotFoundError, ValueError) as e:
        print(f"UNCHECKED: {e}")
        return EXIT_NO_TRUTH
    rows = [f for f in findings if label in f["unit"]]
    for f in rows:
        print(f"[{f['code']}]" + _line(f))
        print(f"      {f['text']}")
    print(f"{len(rows)} finding(s) matching {label!r}")
    return 0


# ── selftest ─────────────────────────────────────────────────────────────────────────────────

FIX_REGISTRY = {"nodes": [
    {"id": "ch1", "num": "1", "title": "The OD9 Creed", "legacy": [1],
     "canon": [{"title": "The OD9 Creed", "url": f"{BOARD}/observer/the-creed.php"}]},
    {"id": "ch4", "num": "3", "title": "The Great Awakening", "legacy": [4], "canon": []},
    {"id": "ch5", "num": "4", "title": "The System In Crisis", "legacy": [5],
     "canon": [{"title": "The System in Crisis", "url": f"{BOARD}/observer/the-system-in-crisis.php"}]},
    {"id": "ch22", "num": "19", "title": "Religious Institutions & Pseudoscience", "legacy": [22],
     "canon": [{"title": "Religious Institutions and Pseudoscience",
                "url": f"{BOARD}/theorist/religious-institutions.php"}]},
    {"id": "v3-energy", "num": "22", "title": "Energy, Resources & Planetary Infrastructure",
     "legacy": [26, 27], "canon": []},
]}
FIX_SENTENCE = "Coordination is not a vibe; it is infrastructure, channels and incentives and feedback loops."
FIX_WEEKLY_BAD = """---
**Week:** 99
**Subject:** Fixture
---

# Fixture

**Ch.4** of the Manifesto (*The Great Awakening*) argues the point.

[**Ch.22**](https://offda9.com/curriculum/theorist/religious-institutions.php) takes a position.

**Ch.1** of the Manifesto (*Wrong Title Entirely*) says another thing.

**Ch.4** of the Manifesto (*The System In Crisis*) says things with a title and no link.

**Ch.22** of the Manifesto says things with no title and no link; it is taken at face value.

See **Ch.99** of the Manifesto for more.

**{{ch:ch77}}** of the Manifesto is a macro naming a node the registry does not have.

Read [a gone lesson](https://offda9.com/curriculum/observer/not-here.php) too.

The Manifesto puts it like this: "A hundred talented people going different directions will always lose to fifty mediocre people moving as one."

The Manifesto says: "Coordination is not a vibe; it is infrastructure, channels and incentives and feedback loops."

Some attempts I've seen:

- *"Treat coordination as a design problem, not a moral one at all."*

## Three things to do this week
1. **Read Manifesto Ch.3.** ~30 min.
"""
FIX_WEEKLY_GOOD = """---
**Week:** 98
**Subject:** Fixture
---

[**{{ch:ch5}}**](https://offda9.com/curriculum/observer/the-system-in-crisis.php) of the Manifesto (*The System In Crisis*) argues the point.

**{{ch:ch4}}** of the Manifesto (*The Great Awakening*) argues another.

[**{{ch:ch5}}**](https://offda9.com/curriculum/observer/the-system-in-crisis.php) and [**{{ch:ch22}}**](https://offda9.com/curriculum/theorist/religious-institutions.php) of the Manifesto take this seriously.

The Manifesto says: "Coordination is not a vibe; it is infrastructure, channels and incentives and feedback loops."
"""
FIX_EMAIL_BAD = """<p>{{username}}, a thought.</p>
<div><p style="font-style: italic;">"A hundred talented people going different directions will always lose to fifty mediocre people moving as one."</p>
<p>&mdash; The OD9 Manifesto</p></div>
<p>Carry on.</p>"""
FIX_LESSON_STALE = """<?php
$lesson = [
  "title" => "Religious Institutions and Pseudoscience",
  "source" => "<b>Source &mdash;</b> The OD9 Manifesto, Volume&nbsp;2 &middot; Chapter&nbsp;22 (<em>Religious Institutions and Pseudoscience</em>)",
];
"""
FIX_LESSON_OK = """<?php
$lesson = [
  "eyebrow"   => "Volume I &middot; Foundation &nbsp;&bull;&nbsp; {{chapter:ch1}}",
  "source" => "<b>Source &mdash;</b> The OD9 Manifesto, Volume&nbsp;1 &middot; {{chapter_nbsp:ch1}} (<em>{{chtitle:ch1}}</em>)",
];
"""


def _fixture_repo(tmp: str) -> Repo:
    os.makedirs(os.path.join(tmp, "data"))
    os.makedirs(os.path.join(tmp, "tools"))
    os.makedirs(os.path.join(tmp, "curriculum", "observer"))
    os.makedirs(os.path.join(tmp, "curriculum", "theorist"))
    os.makedirs(os.path.join(tmp, "email-templates", "lovelogic-weekly"))
    os.makedirs(os.path.join(tmp, "manuscript", "Volume 1 - Fixture", "Chapter 1  Fixture"))
    with open(os.path.join(tmp, "data", "manifesto-map.json"), "w", encoding="utf-8") as fh:
        json.dump(FIX_REGISTRY, fh)
    for rel, body in (("observer/the-creed.php", FIX_LESSON_OK),
                      ("observer/the-system-in-crisis.php", FIX_LESSON_OK.replace("ch1}}", "ch5}}")),
                      ("theorist/religious-institutions.php", FIX_LESSON_STALE)):
        with open(os.path.join(tmp, "curriculum", *rel.split("/")), "w", encoding="utf-8") as fh:
            fh.write(body)
    with open(os.path.join(tmp, "manuscript", "Volume 1 - Fixture", "Chapter 1  Fixture", "s1.md"), "w",
              encoding="utf-8") as fh:
        fh.write("# Fixture\n\nSome prose. " + FIX_SENTENCE.replace("'", "\u2019") + " More prose.\n")
    with open(os.path.join(tmp, "email-templates", "lovelogic-weekly", "email-week-99-bad.md"), "w",
              encoding="utf-8") as fh:
        fh.write(FIX_WEEKLY_BAD)
    with open(os.path.join(tmp, "email-templates", "lovelogic-weekly", "email-week-98-good.md"), "w",
              encoding="utf-8") as fh:
        fh.write(FIX_WEEKLY_GOOD)
    with open(os.path.join(tmp, "email-templates", "welcome-day3.html"), "w", encoding="utf-8") as fh:
        fh.write(FIX_EMAIL_BAD)
    return Repo(root=tmp, manifesto=os.path.join(tmp, "manuscript"))


def selftest() -> int:
    import contextlib
    import io
    fails: list[str] = []

    def ok(cond: bool, what: str):
        if not cond:
            fails.append(what)

    with tempfile.TemporaryDirectory() as tmp:
        repo = _fixture_repo(tmp)
        findings, summary = all_findings(repo)
        bad = [f for f in findings if f["unit"] == "email-week-99-bad.md"]
        ok(summary["chapters"] == 5 and summary["lessons"] == 3, "registry parsed: 5 chapters, 3 lessons")
        ok(any(f["code"] == "RENUMBERED" and f["chapter"] == 4 for f in bad),
           "RENUMBERED: Ch.4 with the title The Great Awakening (now 3)")
        ok(any(f["code"] == "RENUMBERED" and f["chapter"] == 22 for f in bad),
           "RENUMBERED: Ch.22 linking the Religious Institutions lesson (now 19), by the link alone")
        ok(any(f["code"] == "MISCITED" and f["chapter"] == 1 for f in bad), "MISCITED: Ch.1 with a wrong title")
        ok(any(f["code"] == "NONEXISTENT" and f["chapter"] == 99 for f in bad), "NONEXISTENT: Ch.99")
        ok(any(f["code"] == "DEAD" and "not-here.php" in f["note"] for f in bad), "DEAD: a linked page not in curriculum/")
        ok(any(f["code"] == "DEAD" and f["chapter"] == 3 for f in bad),
           "DEAD: an action item reading a chapter no Codex page publishes")
        ok(any(f["code"] == "LINKABLE" and f["chapter"] == 4 for f in bad),
           "LINKABLE: Ch.4 confirmed by its title (The System In Crisis, which has a lesson), unlinked")
        # Two bare numbers in the fixture: the Ch.22 sentence and the "Read Manifesto Ch.3" action item.
        # The good weekly's "Ch.4 and Ch.19" line, two links and no titles, is confirmed by the
        # linked chapters' current numbers; without that rule this count would read 4.
        ok(not any(f["chapter"] == 22 and f["code"] != "RENUMBERED" for f in bad) and summary["bare_numbers"] == 2,
           f"a bare Ch.22 with no title and no link is taken at face value and counted, not flagged "
           f"(bare={summary['bare_numbers']})")
        ok(any(f["code"] == "RENUMBERED" and f["chapter"] == 4 and f.get("line") == 8 for f in bad),
           f"a weekly's finding carries the line it starts on (got "
           f"{[f.get('line') for f in bad if f['code'] == 'RENUMBERED' and f['chapter'] == 4]})")
        quotes = [f for f in bad if f["code"] == "UNSOURCED_QUOTE"]
        ok(len(quotes) == 1 and quotes[0]["quote"].startswith("A hundred talented"),
           "UNSOURCED_QUOTE: the attributed sentence the manuscript lacks, once; the present sentence "
           "and the reader's own attempts are not findings")
        ok(not any(f["code"] == "LINKABLE" and f["chapter"] in (4, 22) and "Great" in f["note"] for f in bad),
           "a RENUMBERED reference is not also LINKABLE")
        email = [f for f in findings if f["unit"] == "welcome-day3.html"]
        ok(len(email) == 1 and email[0]["code"] == "UNSOURCED_QUOTE",
           "a fragment's quote with the dash attribution in the next block is UNSOURCED_QUOTE")
        good = [f for f in findings if f["unit"] == "email-week-98-good.md"]
        ok(good == [], f"the clean weekly has no findings (got {[(f['code'], f['chapter']) for f in good]})")
        lesson = [f for f in findings if f["code"] == "LESSON_SOURCE"]
        ok(len(lesson) == 1 and lesson[0]["unit"].endswith("religious-institutions.php") and lesson[0]["chapter"] == 22,
           "LESSON_SOURCE: the page saying Chapter 22 for a chapter the registry numbers 19, and only that page")
        lits = [f for f in findings if f["code"] == "LITERAL"]
        ok(any(f["unit"] == "email-week-99-bad.md" for f in lits)
           and [f["unit"] for f in lits if f["kind"] == "lesson"] == ["curriculum/theorist/religious-institutions.php"],
           "LITERAL: hand-written numbers in the bad weekly and the stale lesson; none in the macro-written good "
           "weekly or the two macro-labelled lessons")
        ok([f["text"] for f in findings if f["code"] == "UNKNOWN_ID"] == ["ch77"]
           and not any(f["code"] == "NONEXISTENT" and f["chapter"] is None for f in findings),
           "UNKNOWN_ID: {{ch:ch77}} names no node; it is left unresolved and not judged as a number")
        ok(any(f["code"] == "RENUMBERED" for f in bad) and not any(f["unit"] == "email-week-98-good.md" for f in findings),
           "macros resolve before judging: {{ch:ch5}} reads as Ch.4 and the good weekly stays clean")
        ok(set(f["code"] for f in findings) == set(CLASSES), "every class fires on the fixture")

        # shrink-only round trip
        with contextlib.redirect_stdout(io.StringIO()):
            ok(write_baseline(repo) == 0, "baseline writes")
            ok(check(repo) == 0, "check passes at the baseline")
        p = os.path.join(tmp, "email-templates", "lovelogic-weekly", "email-week-99-bad.md")
        with open(p, encoding="utf-8") as fh:
            txt = fh.read()
        with open(p, "w", encoding="utf-8") as fh:
            fh.write(txt + "\nAlso **Ch.77** of the Manifesto.\n")
        with contextlib.redirect_stdout(io.StringIO()) as buf:
            ok(check(repo) == 1, "one more NONEXISTENT in a unit is refused")
        ok("NONEXISTENT 1 -> 2" in buf.getvalue(), "the refusal names the unit, the class and the counts")
        with open(p, "w", encoding="utf-8") as fh:
            fh.write(txt.replace("See **Ch.99** of the Manifesto for more.\n\n", ""))
        with contextlib.redirect_stdout(io.StringIO()) as buf:
            ok(check(repo) == 0, "a repaired unit passes")
        ok("improved: email-week-99-bad.md NONEXISTENT 1 -> 0" in buf.getvalue(), "the improvement is printed")
        with open(os.path.join(tmp, "email-templates", "lovelogic-weekly", "email-week-97-new.md"), "w",
                  encoding="utf-8") as fh:
            fh.write("**Ch.99** of the Manifesto.\n")
        with contextlib.redirect_stdout(io.StringIO()):
            ok(check(repo) == 1, "a new unit with a finding is refused")

        # ground truth missing is exit 2, never a pass
        os.remove(os.path.join(tmp, "data", "manifesto-map.json"))
        with contextlib.redirect_stdout(io.StringIO()):
            ok(check(repo) == EXIT_NO_TRUTH, "a missing registry is UNCHECKED (exit 2)")
        with open(os.path.join(tmp, "data", "manifesto-map.json"), "w", encoding="utf-8") as fh:
            json.dump(FIX_REGISTRY, fh)
        repo2 = Repo(root=tmp, manifesto=os.path.join(tmp, "nowhere"))
        with contextlib.redirect_stdout(io.StringIO()):
            ok(check(repo2) == EXIT_NO_TRUTH, "a missing manuscript is UNCHECKED (exit 2)")

    # unit helpers
    ok(html_blocks("<p>One</p><div><p>Two &mdash; three</p></div>") == [(1, "One"), (2, "Two — three")],
       "html blocks split on block tags")
    ok([w for w, _ in md_blocks("a\n\nb\nc\n\n\nd")] == [1, 3, 7],
       f"markdown blocks carry their first line (got {[w for w, _ in md_blocks('a' + chr(10) * 2 + 'b' + chr(10) + 'c' + chr(10) * 3 + 'd')]})")
    ok(_norm_quote("It\u2019s \u201cfine\u201d \u2014 really") == _norm_quote("It's \"fine\" - really"), "quote normalisation")
    ok(_cited_title("see below") == "" and _cited_title("The System in Crisis") == "The System in Crisis", "a parenthetical is a title only when it reads like one")

    n = 33
    if fails:
        print(f"SELFTEST FAIL ({len(fails)} of {n} checks):")
        for f in fails:
            print("  - " + f)
        return 1
    print(f"SELFTEST OK: {n} checks; every class fires on the fixture, a clean unit passes, the baseline is shrink-only.")
    return 0


def main(argv=None) -> int:
    ap = argparse.ArgumentParser(description=(__doc__ or "").split("\n")[0])
    ap.add_argument("--report", action="store_true", help="every finding by class (default)")
    ap.add_argument("--baseline", action="store_true", help="record today's counts")
    ap.add_argument("--check", action="store_true", help="exit 0 pass, 1 worse than baseline, 2 ground truth missing")
    ap.add_argument("--unit", help="one unit's findings in full (substring of the label)")
    ap.add_argument("--selftest", action="store_true")
    args = ap.parse_args(argv)
    if args.selftest:
        return selftest()
    repo = Repo()
    if args.baseline:
        return write_baseline(repo)
    if args.check:
        return check(repo)
    if args.unit:
        return show_unit(repo, args.unit)
    return report(repo)


if __name__ == "__main__":
    raise SystemExit(main())
