#!/usr/bin/env python3
"""prose_check.py -- does the site READ? The fleet's prose engine over the od9 copy, as a deploy gate.

The founder (2026-11-07), on the prose gate built for The OD9 Manifesto that morning: "apply this
skill to Lens and any kind of copy that we come up with for anything in regards to the ecosystem."
The engine (measures, profiles, refusals) is one file in the prose-craft skill,
~/.claude/skills/prose-craft/prose_check.py; this tool is what the od9 site adds: the walk over the
copy this repository ships, an extractor that turns it into the markdown the engine judges, the
report, the shrink-only baseline and the deploy gate.

Two kinds of unit, both judged with the engine's "announce" profile (the book's bar, and 120+
words that never say "you" or "we" are refused, because a web page talks to its reader):

  * a PAGE: every public .php page (the repository root, t/, callin/). Its visible copy is the text
    of its block elements after the PHP, scripts, styles, SVG and comments are removed; headings
    become markdown headings (so the engine's paragraph ties run inside a section, not across the
    whole page) and a block under 8 words (a button, a nav label, a badge) is not prose and is
    dropped. A page with under 60 words of copy (a handler, a redirect) is listed as "no copy" and
    not judged. Shared includes (nav, footer, head) are judged nowhere: they are chrome, not copy.
  * a LESSON: every Codex lesson's study layer (curriculum/<tier>/<slug>.php): the subtitle, the
    Archivist's read, and the "study" array's headings, paragraphs, callouts and lists. The canon
    blocks are verbatim manifesto text and belong to the manifesto's own gate; they are not judged
    here (tools/codex_anchors.py holds them to the book). A lesson whose study array cannot be
    parsed is reported UNCHECKED, not clean.
  * an EMAIL: every drip fragment api/drip/sender.php can send (email-templates/*.html; the shared
    chrome in includes/email_layout.php is judged nowhere), its {{placeholders}} read as "Reader".
  * a WEEKLY: every LoveLogic weekly api/drip/weekly_lovelogic_sender.php can send
    (email-templates/lovelogic-weekly/email-week-*.md): the front matter is dropped, the Subject
    becomes the title heading (so an undefined acronym or a weasel in the subject is refused), and
    the markdown body is judged as the engine reads markdown. The design samples and the archive
    under email-templates/ are sent by nothing and are not judged.

    python tools/prose_check.py --report              # every unit: measures and refusal codes
    python tools/prose_check.py --unit about.php      # one unit, every refusal spelled out
    python tools/prose_check.py --show about.php      # the markdown the engine sees (debug the extractor)
    python tools/prose_check.py --baseline            # record today's refusals per unit
    python tools/prose_check.py --check               # refuse any unit worse than its baseline, or any
                                                      #   new unit with a refusal (the deploy gate)
    python tools/prose_check.py --selftest            # extractor fixtures, a bad page refused, the
                                                      #   baseline in both directions, the walk not empty

Exit codes: 0 pass, 1 refused, 2 the engine is not installed (the deploy says UNCHECKED, not clean,
and goes on: a machine without the skill cannot know, and saying so beats failing forever).
Set PROSE_CRAFT_ENGINE to point at another copy of the engine (a checkout, a test double).
"""
from __future__ import annotations

import argparse
import html
import importlib.util
import io
import json
import os
import re
import sys
from datetime import date

if hasattr(sys.stdout, "reconfigure"):
    sys.stdout.reconfigure(encoding="utf-8", errors="replace")
HERE = os.path.dirname(os.path.abspath(__file__))
ROOT = os.path.dirname(HERE)
BASELINE = os.path.join(HERE, "prose_baseline.json")
def _engine_path() -> str:
    """The engine's path: PROSE_CRAFT_ENGINE, else this user's ~/.claude, else the Windows user's
    ~/.claude seen from WSL. The deploy runs under WSL (the skill's rule, for native rsync/ssh), where
    HOME is the Linux home and holds no ~/.claude; the first dry run said UNCHECKED for that reason."""
    import glob
    env = os.environ.get("PROSE_CRAFT_ENGINE")
    if env:
        return env
    tail = os.path.join(".claude", "skills", "prose-craft", "prose_check.py")
    home = os.path.join(os.path.expanduser("~"), tail)
    if os.path.exists(home):
        return home
    for cand in sorted(glob.glob(os.path.join("/mnt/c/Users", "*", tail))):
        return cand
    return home


ENGINE_PATH = _engine_path()
PROFILE = "announce"
PAGE_DIRS = ("", "t", "callin")             # where public pages live, relative to ROOT
LESSON_GLOB = os.path.join("curriculum", "*", "*.php")
EMAIL_GLOB = os.path.join("email-templates", "*.html")
WEEKLY_GLOB = os.path.join("email-templates", "lovelogic-weekly", "email-week-*.md")
PLACEHOLDER = re.compile(r"\{\{\s*\w+\s*\}\}")
PLACEHOLDER_READS_AS = "Reader"

# Chapter macros ({{ch:ch4}} -> "Ch.3") are resolved from the registry before judging, as the
# senders and the Codex renderer resolve them, so the engine reads what a subscriber reads.
sys.path.insert(0, HERE)
import chapter_macros as _cm  # noqa: E402
_macro_registry = None


def resolve_macros(src: str) -> str:
    global _macro_registry
    if _macro_registry is None:
        _macro_registry = _cm.load_registry(os.path.join(ROOT, "data", "manifesto-map.json"))
    return _cm.resolve(src, _macro_registry)[0]
MIN_BLOCK_WORDS = 8                        # a block shorter than this is a label, not prose
MIN_PAGE_WORDS = 60                        # a page with less copy is a handler, not a page
EXIT_NO_ENGINE = 2

_engine = None


def engine():
    """The loaded engine, or None when the prose-craft skill is not on this machine."""
    global _engine
    if _engine is not None:
        return _engine
    if not os.path.exists(ENGINE_PATH):
        return None
    spec = importlib.util.spec_from_file_location("prose_craft_engine", ENGINE_PATH)
    mod = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(mod)
    _engine = mod
    return mod


# ---------------------------------------------------------------- the extractors
PHP_BLOCK = re.compile(r"<\?(?:php|=)?.*?(?:\?>|\Z)", re.S)
DROP_ELEMENTS = re.compile(r"<(script|style|noscript|svg|template|code|pre)\b.*?</\1\s*>", re.S | re.I)
COMMENT = re.compile(r"<!--.*?-->", re.S)
HEADING_OPEN = re.compile(r"<h[1-6]\b[^>]*>", re.I)
HEADING_CLOSE = re.compile(r"</h[1-6]\s*>", re.I)
BLOCK_TAG = re.compile(r"</?(?:p|li|ul|ol|blockquote|figcaption|dd|dt|td|th|tr|summary|details|div|section|"
                       r"article|header|footer|aside|main|nav|form|fieldset|label|button|br|hr|option|select|"
                       r"textarea|table|figure|address)\b[^>]*/?>", re.I)
BUTTON_TAG = re.compile(r"<a\b[^>]*\bclass=\"[^\"]*\b(?:btn|button|cta|pill|chip|tag)\b[^\"]*\"[^>]*>", re.I)
ANY_TAG = re.compile(r"<[^>]+>")
HEADING_MARK = "\x00H\x00"


def _skip_string(src: str, k: int) -> int:
    q, k, n = src[k], k + 1, len(src)
    while k < n:
        c = src[k]
        if c == "\\":
            k += 2
            continue
        if c == q:
            return k + 1
        k += 1
    return n


def strip_php(src: str) -> str:
    """The template with every PHP block replaced by a space. A `?>` inside a PHP string, comment or
    heredoc does not end the block: sitemap.php prints '<?xml ... ?>' from a string, and the first
    extractor read the rest of its code as page copy ("const BASE", "EITHER ... OR")."""
    out, i, n = [], 0, len(src)
    while True:
        j = src.find("<?", i)
        while j >= 0 and not (src.startswith("<?php", j) or src.startswith("<?=", j)):
            j = src.find("<?", j + 2)                      # "<?xml" in the template is not PHP
        if j < 0:
            out.append(src[i:])
            break
        out.append(src[i:j])
        out.append(" ")
        k = j + (5 if src.startswith("<?php", j) else 3)
        while k < n:
            c = src[k]
            if c in "'\"":
                k = _skip_string(src, k)
            elif src.startswith("//", k) or c == "#":
                e, q = src.find("\n", k), src.find("?>", k)   # a line comment ends at a newline or at ?>
                if q >= 0 and (e < 0 or q < e):
                    k = q
                    continue
                k = n if e < 0 else e
            elif src.startswith("/*", k):
                e = src.find("*/", k + 2)
                k = n if e < 0 else e + 2
            elif src.startswith("<<<", k):
                m = re.match(r"<<<\s*['\"]?(\w+)['\"]?\r?\n", src[k:])
                if m:
                    m2 = re.compile(r"^\s*%s\b" % re.escape(m.group(1)), re.M).search(src, k + m.end())
                    k = n if not m2 else m2.end()
                else:
                    k += 3
            elif src.startswith("?>", k):
                k += 2
                break
            else:
                k += 1
        i = k
    return "".join(out)


def clean_text(s: str) -> str:
    """Tags out, entities decoded, whitespace collapsed; what a reader sees of one block."""
    s = ANY_TAG.sub("", s)                   # inline tags (em, strong, a, span) wrap words; no space is added
    s = html.unescape(s).replace("\xa0", " ")
    return re.sub(r"\s+", " ", s).strip()


def page_markdown(src: str) -> str:
    """The visible copy of a PHP page as markdown: headings as '## ', blocks as paragraphs."""
    t = strip_php(src)
    t = COMMENT.sub(" ", t)
    t = DROP_ELEMENTS.sub(" ", t)
    t = HEADING_OPEN.sub("\n\n" + HEADING_MARK, t)
    t = HEADING_CLOSE.sub("\n\n", t)
    t = BLOCK_TAG.sub("\n\n", t)
    t = BUTTON_TAG.sub("\n\n", t)            # a row of buttons is not a paragraph; each stands alone and is dropped
    out = []
    for block in re.split(r"\n\s*\n", t):
        heading = HEADING_MARK in block
        text = clean_text(block.replace(HEADING_MARK, " "))
        if not text:
            continue
        if heading:
            out.append("## " + text)
        elif len(text.split()) >= MIN_BLOCK_WORDS:
            out.append(text)
    return "\n\n".join(out)


PHP_STR = r'"((?:[^"\\]|\\.)*)"'
LESSON_KEY = re.compile(r'^\s*"(title|subtitle|archivist)"\s*=>\s*' + PHP_STR, re.M)
STUDY_BLOCK = re.compile(r'"study"\s*=>\s*\[(.*?)\n  \],', re.S)
STUDY_ITEM = re.compile(r'\["(h3|p|label|callout|ul|quote)"\s*=>\s*')
STR_LIT = re.compile(PHP_STR, re.S)


def php_unescape(s: str) -> str:
    return s.replace('\\"', '"').replace("\\'", "'").replace("\\\\", "\\")


def lesson_markdown(src: str) -> tuple:
    """(markdown, study_found) for a Codex lesson: the study layer only, never the canon."""
    keys = {m.group(1): clean_text(php_unescape(m.group(2))) for m in LESSON_KEY.finditer(src)}
    out = []
    if keys.get("title"):
        out.append("# " + keys["title"])
    for k in ("subtitle", "archivist"):
        if keys.get(k):
            out.append(keys[k])
    m = STUDY_BLOCK.search(src)
    if not m:
        return "\n\n".join(out), False
    block = m.group(1)
    for item in STUDY_ITEM.finditer(block):
        kind, start = item.group(1), item.end()
        if kind in ("h3", "p", "label", "quote"):
            lit = STR_LIT.match(block, start)
            if not lit:
                continue
            text = clean_text(php_unescape(lit.group(1)))
            if kind == "h3":
                out.append("## " + text)
            elif kind in ("p", "quote") and text:
                out.append(text)
        else:                                   # callout: [title, body]; ul: [item, item, ...]
            end = block.find("]]", start)
            region = block[start:end if end >= 0 else None]
            lits = [clean_text(php_unescape(s)) for s in STR_LIT.findall(region)]
            if kind == "callout" and lits:
                out.append("## " + lits[0])
                out += [s for s in lits[1:] if s]
            else:
                out += [s for s in lits if s]
    return "\n\n".join(out), True


def email_markdown(src: str) -> str:
    """A drip fragment's copy: the page extractor over the HTML, placeholders read as a name."""
    return page_markdown(PLACEHOLDER.sub(PLACEHOLDER_READS_AS, resolve_macros(src)))


FRONT_MATTER = re.compile(r"\A\s*---\s*\n(.*?)\n---\s*\n", re.S)
SUBJECT_LINE = re.compile(r"^\*{0,2}Subject:?\*{0,2}:?\s*(.+?)\s*$", re.M | re.I)


def weekly_markdown(src: str) -> tuple:
    """(markdown, subject) for a LoveLogic weekly: front matter dropped, the Subject as the title
    heading, placeholders read as a name. subject is "" when the front matter has none."""
    subject = ""
    m = FRONT_MATTER.match(src)
    body = src
    if m:
        body = src[m.end():]
        s = SUBJECT_LINE.search(m.group(1))
        subject = s.group(1).strip() if s else ""
    body = PLACEHOLDER.sub(PLACEHOLDER_READS_AS, resolve_macros(body)).strip()
    return (("# " + subject + "\n\n") if subject else "") + body, subject


# ---------------------------------------------------------------- the walk
def pages() -> list:
    """(label, path) for every public .php page, in a stable order."""
    out = []
    for d in PAGE_DIRS:
        folder = os.path.join(ROOT, d) if d else ROOT
        if not os.path.isdir(folder):
            continue
        for name in sorted(os.listdir(folder)):
            if name.endswith(".php") and not name.startswith("_"):
                rel = (d + "/" + name) if d else name
                out.append((rel, os.path.join(folder, name)))
    return out


def lessons() -> list:
    import glob
    return [(os.path.relpath(p, ROOT).replace("\\", "/"), p)
            for p in sorted(glob.glob(os.path.join(ROOT, LESSON_GLOB)))]


def units_on_disk() -> list:
    """(label, kind, markdown or None, note) for every unit the gate judges.
    markdown None means the unit is not judged; the note says why (no copy, UNCHECKED)."""
    out = []
    for label, path in pages():
        md = page_markdown(io.open(path, encoding="utf-8", errors="replace").read())
        words = sum(len(p.split()) for p in md.split("\n\n") if not p.startswith("## "))
        if words < MIN_PAGE_WORDS:
            out.append((label, "page", None, "no copy (%d words)" % words))
        else:
            out.append((label, "page", md, ""))
    for label, path in lessons():
        md, found = lesson_markdown(io.open(path, encoding="utf-8", errors="replace").read())
        if not found:
            out.append((label, "lesson", md or None, "UNCHECKED: no literal study array; only the title, "
                                                        "subtitle and read are judged"))
        else:
            out.append((label, "lesson", md, ""))
    import glob
    for path in sorted(glob.glob(os.path.join(ROOT, EMAIL_GLOB))):
        label = os.path.relpath(path, ROOT).replace("\\", "/")
        md = email_markdown(io.open(path, encoding="utf-8", errors="replace").read())
        out.append((label, "email", md or None, "" if md else "no copy"))
    for path in sorted(glob.glob(os.path.join(ROOT, WEEKLY_GLOB))):
        label = os.path.relpath(path, ROOT).replace("\\", "/")
        md, subject = weekly_markdown(io.open(path, encoding="utf-8", errors="replace").read())
        out.append((label, "weekly", md or None, "" if subject else "UNCHECKED: no Subject in the front matter"))
    return out


def judge(md: str) -> dict:
    eng = engine()
    return eng.judge_text(md, PROFILE)


def codes_of(refusals: list) -> dict:
    out = {}
    for r in refusals:
        c = r.split(":")[0]
        out[c] = out.get(c, 0) + 1
    return out


# ---------------------------------------------------------------- report / baseline / check
def report(rows: list | None = None) -> int:
    if engine() is None:
        print("prose_check: the prose engine is not at %s (the prose-craft skill)" % ENGINE_PATH)
        return EXIT_NO_ENGINE
    rows = rows if rows is not None else units_on_disk()
    print("%-46s %-6s %5s %5s %5s %5s %5s %4s %s" % ("unit", "kind", "words", "ease", "grade", "pass%", "nom", "you", "refusals"))
    total = judged = 0
    for label, kind, md, note in rows:
        if md is None:
            print("%-46s %-6s %s" % (label[-46:], kind, note))
            continue
        v = judge(md)
        m = v["measures"]
        judged += 1
        total += len(v["refusals"])
        print("%-46s %-6s %5d %5.0f %5.1f %5.0f %5.1f %4.0f %s%s" % (
            label[-46:], kind, m["words"], m["ease"], m["grade"], 100 * m["passive_share"], m["nominal_per_100"],
            m["address_per_1000"], ", ".join("%s x%d" % kv for kv in sorted(codes_of(v["refusals"]).items())) or "-",
            ("  [" + note + "]") if note else ""))
    print("%d unit(s) judged, %d refusal(s); %d listed and not judged" % (judged, total, len(rows) - judged))
    return 0


def write_baseline(rows: list | None = None, path: str = BASELINE) -> int:
    if engine() is None:
        print("prose_check: the prose engine is not at %s (the prose-craft skill)" % ENGINE_PATH)
        return EXIT_NO_ENGINE
    rows = rows if rows is not None else units_on_disk()
    base = {}
    for label, kind, md, note in rows:
        if md is None:
            continue
        v = judge(md)
        base[label] = {"kind": kind, "refusals": len(v["refusals"]), "codes": codes_of(v["refusals"])}
    with io.open(path, "w", encoding="utf-8", newline="\n") as fh:
        json.dump({"_doc": "Shrink-only baseline for tools/prose_check.py --check: a unit may not carry more prose "
                           "refusals than recorded here, and a unit this file never saw must carry none. Rewrite "
                           "the copy, then run --baseline to record the smaller number; never raise one by hand.",
                   "profile": PROFILE, "written": date.today().isoformat(), "units": base}, fh, indent=1)
        fh.write("\n")
    print("baseline: %d unit(s), %d refusal(s) -> %s" % (len(base), sum(v["refusals"] for v in base.values()), path))
    return 0


def check(rows: list | None = None, baseline_path: str = BASELINE) -> int:
    """Shrink-only: a unit may not carry more refusals than its baseline, and a unit the baseline
    never saw must carry none. Exit 1 on any breach, 2 without the engine; prints what can shrink."""
    if engine() is None:
        print("prose gate: UNCHECKED, not clean - the prose engine is not at %s (the prose-craft skill)" % ENGINE_PATH)
        return EXIT_NO_ENGINE
    rows = rows if rows is not None else units_on_disk()
    base = json.load(io.open(baseline_path, encoding="utf-8")).get("units", {}) if os.path.exists(baseline_path) else {}
    rc, shrink, judged, unchecked = 0, [], 0, 0
    for label, kind, md, note in rows:
        if md is None:
            continue
        if note.startswith("UNCHECKED"):
            unchecked += 1
        v = judge(md)
        judged += 1
        allowed = (base.get(label) or {}).get("refusals", 0)
        if len(v["refusals"]) > allowed:
            rc = 1
            print("REFUSED %s: %d prose refusal(s), baseline allows %d" % (label, len(v["refusals"]), allowed))
            for r in v["refusals"][:6]:
                print("    " + r[:160])
        elif len(v["refusals"]) < allowed:
            shrink.append((label, allowed, len(v["refusals"])))
    if shrink:
        print("%d unit(s) improved; run --baseline to shrink: %s" % (
            len(shrink), ", ".join("%s %d->%d" % s for s in shrink[:5])))
    if unchecked:
        print("%d lesson(s) UNCHECKED beyond their title, subtitle and read (no literal study array)" % unchecked)
    print("prose gate: %s (%d unit(s), profile %s)" % ("PASS" if rc == 0 else "REFUSED", judged, PROFILE))
    return rc


def show_unit(label: str, spell: bool) -> int:
    for ulabel, kind, md, note in units_on_disk():
        if ulabel == label or ulabel.endswith("/" + label):
            if md is None:
                print("%s: %s" % (ulabel, note))
                return 0
            if not spell:
                print(md)
                return 0
            if engine() is None:
                print("prose_check: the prose engine is not at %s" % ENGINE_PATH)
                return EXIT_NO_ENGINE
            v = judge(md)
            print("%s (%s, profile %s): %s" % (ulabel, kind, PROFILE, engine().summary_line(v["measures"])))
            for n in v["notes"]:
                print("note: " + n)
            for r in v["refusals"]:
                print("REFUSED " + r)
            print("prose: %s" % ("PASS" if not v["refusals"] else "%d refusal(s)" % len(v["refusals"])))
            return 1 if v["refusals"] else 0
    print("no unit named %r (labels are repo-relative: about.php, curriculum/observer/the-creed.php)" % label)
    return 1


# ---------------------------------------------------------------- prover
PAGE_FIXTURE = """<?php
$page_title = 'Fixture';
$secret = "never shown";
?>
<!DOCTYPE html><html><head><?php include __DIR__ . '/includes/head.php'; ?>
<style>body{color:#fff} .btn{padding:1rem}</style>
<script>console.log("not copy");</script>
</head><body>
<nav><a href="/">Home</a><a href="/join.php">Join the Discord</a></nav>
<!-- a comment that says nothing -->
<section class="hero"><span class="badge">Mission</span>
<h1>Why the room matters</h1>
<p>BODY</p>
<a class="btn btn-primary" href="/join.php">Join the Discord &rarr;</a>
</section>
<section><h2>What we do</h2>
<ul><li>Short label</li><li>We read the chapter together, then the room decides what holds and what has to go.</li></ul>
<p>Second paragraph &mdash; it carries an entity and an <em>emphasis</em>, and&nbsp;a non-breaking space.</p>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
</body></html>
"""

LESSON_FIXTURE = """<?php
declare(strict_types=1);
$lesson = [
  "title"     => "The Fixture Lesson",
  "eyebrow"   => "Volume I &middot; Chapter 0",
  "subtitle"  => "A subtitle that talks to you &mdash; the reader.",
  "archivist" => "&ldquo;The read in the Archivist&rsquo;s voice, quoted.&rdquo;",
  "canon" => [
    ["sec" => 1, "p" => "CANON TEXT THAT IS VERBATIM MANIFESTO AND NEVER JUDGED HERE.", "lead" => true],
    ["affirm" => "An affirmation, also canon."],
  ],
  "study" => [
    ["label" => "The Archivist&rsquo;s read"],
    ["h3" => "First heading"],
    ["p"  => "STUDY_ONE"],
    ["callout" => ["&#9670; A callout title", "The callout body says \\"quoted\\" words and keeps going for a while longer."]],
    ["ul" => [
      "<b>One</b> &mdash; the first list item, long enough to count as a paragraph of prose.",
      "<b>Two</b> &mdash; the second list item, also long enough to count as prose here.",
    ]],
    ["h3" => "Second heading"],
    ["p"  => "STUDY_TWO"],
  ],
  "source" => "<b>Source</b> &mdash; the fixture.",
];
require __DIR__ . '/../_codex.php';
codex_render($lesson);
"""

GOOD_PARA = ("You can read this paragraph in one breath. It names what the room does and why you would come. "
             "We meet on Sunday, we read the chapter, and the room decides what holds. Nobody has to speak the "
             "first night, and nobody is sold anything.")
BAD_PARA = ("It is believed that the implementation of the categorization of the organization was delayed by the "
            "administration, and arguably the utilization of the documentation was somewhat impacted by the "
            "consideration of the prioritization, which is to say that the facilitation of the transformation of "
            "the institution is subject to the determination of the evaluation of the situation by the "
            "administration of the organization in question at the present moment in time.")


def selftest() -> int:
    fails = []

    def case(label: str, cond: bool) -> None:
        print("  %s %s" % ("OK  " if cond else "FAIL", label))
        if not cond:
            fails.append(label)

    good_md = page_markdown(PAGE_FIXTURE.replace("BODY", GOOD_PARA))
    case("page: PHP, script, style, comment and the include lines are gone",
         all(x not in good_md for x in ("<?php", "console.log", "color:#fff", "a comment that says nothing", "head.php", "never shown")))
    case("page: headings become markdown headings", "## Why the room matters" in good_md and "## What we do" in good_md)
    case("page: nav labels, badges and buttons under %d words are dropped" % MIN_BLOCK_WORDS,
         all(x not in good_md for x in ("Home", "Mission", "Join the Discord", "Short label")))
    case("page: paragraphs and long list items are kept, entities decoded, tags stripped",
         GOOD_PARA in good_md and "the room decides what holds and what has to go." in good_md
         and "Second paragraph — it carries an entity and an emphasis, and a non-breaking space." in good_md)
    case("page: a handler with no copy is 'no copy', not judged",
         sum(len(p.split()) for p in page_markdown("<?php header('Location: /'); ?>").split("\n\n") if p) < MIN_PAGE_WORDS)
    tricky = ("<?php\n$xml = '<?xml version=\"1.0\"?>';\nconst BASE = 'x'; // EITHER this OR that, a comment\n"
              "$q = \"a ?> b\"; /* ?> in a block comment */\n$h = <<<HTML\n?> inside a heredoc\nHTML;\n?>\n<p>" + GOOD_PARA + "</p>\n")
    tricky_md = page_markdown(tricky)
    case("page: a '?>' inside a PHP string, block comment or heredoc does not end the PHP block",
         GOOD_PARA in tricky_md and all(x not in tricky_md for x in ("BASE", "EITHER", "xml", "block comment", "heredoc")))
    closed_by_comment = page_markdown("<?php // a line comment ends at ?>\n<p>" + GOOD_PARA + "</p>\n")
    case("page: a '?>' in a line comment DOES end the block (PHP's own rule), and the copy after it is read",
         GOOD_PARA in closed_by_comment)
    buttons = page_markdown('<div class="hero-cta"><a class="btn btn-primary" href="/join.php">Pull Up and Join the Community</a>'
                            '<a class="btn btn-ghost" href="/about.php">Grow Up Before We Blow Up Together</a></div>')
    case("page: a row of buttons is not glued into one paragraph", buttons == "")

    lmd, found = lesson_markdown(LESSON_FIXTURE.replace("STUDY_ONE", GOOD_PARA).replace("STUDY_TWO", GOOD_PARA))
    case("lesson: the study array is found; title, subtitle and read lead the markdown",
         found and lmd.startswith("# The Fixture Lesson\n\nA subtitle that talks to you — the reader.\n\n“The read in the Archivist’s voice, quoted.”"))
    case("lesson: h3 and callout titles become headings; p, callout body and list items are paragraphs",
         "## First heading" in lmd and "## ◆ A callout title" in lmd and "## Second heading" in lmd
         and 'The callout body says "quoted" words and keeps going for a while longer.' in lmd
         and "One — the first list item, long enough to count as a paragraph of prose." in lmd
         and "Two — the second list item, also long enough to count as prose here." in lmd and GOOD_PARA in lmd)
    case("lesson: the canon, the affirm, the label, the eyebrow and the source are NOT judged here",
         all(x not in lmd for x in ("CANON TEXT", "An affirmation", "Archivist’s read\n", "Volume I · Chapter 0", "the fixture")))
    _, found2 = lesson_markdown(LESSON_FIXTURE.replace('"study" => [', '"study" => $study,').replace("  ],\n  \"source\"", "  \"source\""))
    case("lesson: a study array that is a variable is reported not found (UNCHECKED), never clean", not found2)

    email_md = email_markdown('<div style="x">Welcome to the Movement</div>\n<h2>You\'re in, {{username}}.</h2>\n<p>'
                              + GOOD_PARA + '</p>\n<table><tr><td><a href="https://offda9.com/wake-up">WATCH THE BRIEFING</a>'
                              '</td></tr></table>\n<p>&mdash; Rage</p>')
    case("email: the fragment's copy is read, a placeholder reads as a name, the button and sign-off are dropped",
         "## You're in, Reader." in email_md and GOOD_PARA in email_md
         and all(x not in email_md for x in ("{{", "WATCH THE BRIEFING", "Welcome to the Movement", "— Rage")))
    wk = ("---\n**Week:** 1\n**Category:** Fixture\n**Subject:** What's left after OD9?\n**Send Date:** April 14, 2026\n---\n\n"
          "# What's left after OD9?\n\nHey {{FIRST_NAME}},\n\n" + GOOD_PARA + "\n\n## A section\n\n" + GOOD_PARA + "\n")
    wk_md, wk_subject = weekly_markdown(wk)
    case("weekly: the front matter is dropped, the Subject leads as the title, the body and placeholder are read",
         wk_subject == "What's left after OD9?" and wk_md.startswith("# What's left after OD9?\n\n# What's left after OD9?")
         and "Hey Reader," in wk_md and "Send Date" not in wk_md and "**Week:**" not in wk_md and wk_md.count(GOOD_PARA) == 2)
    case("weekly: a file with no Subject in its front matter is reported UNCHECKED, never clean",
         weekly_markdown("---\n**Week:** 2\n---\n\nbody\n")[1] == "")

    eng = engine()
    case("the prose engine is installed and loads", eng is not None)
    if eng is None:
        print("prose_check selftest: %d failure(s); the engine is missing so the judging checks did not run" % len(fails))
        return 1
    good = judge(good_md)
    bad = judge(page_markdown(PAGE_FIXTURE.replace("BODY", BAD_PARA + " " + BAD_PARA)))
    case("a page that reads passes the announce profile", good["refusals"] == [])
    codes = set(codes_of(bad["refusals"]))
    case("a page that does not read is refused by code (LONG, NOMINAL, WEASEL at least)", {"LONG", "NOMINAL", "WEASEL"} <= codes)
    no_address = judge("## A heading\n\n" + " ".join(["The committee met. The committee adjourned. The report was filed."] * 14))
    case("120+ words that never say you or we are refused (ADDRESS: a page talks to its reader)",
         any(r.startswith("ADDRESS") for r in no_address["refusals"]))

    import tempfile
    rows = [("good.php", "page", good_md, ""), ("bad.php", "page", page_markdown(PAGE_FIXTURE.replace("BODY", BAD_PARA)), "")]
    nbad = len(judge(rows[1][2])["refusals"])
    with tempfile.TemporaryDirectory() as td:
        bl = os.path.join(td, "baseline.json")
        buf, old = io.StringIO(), sys.stdout
        sys.stdout = buf
        try:
            json.dump({"units": {"good.php": {"refusals": 0}, "bad.php": {"refusals": nbad}}}, io.open(bl, "w"))
            rc_same = check(rows, bl)
            json.dump({"units": {"good.php": {"refusals": 0}, "bad.php": {"refusals": nbad - 1}}}, io.open(bl, "w"))
            rc_worse = check(rows, bl)
            json.dump({"units": {"good.php": {"refusals": 0}}}, io.open(bl, "w"))
            rc_new = check(rows, bl)
            rc_written = write_baseline(rows, bl)
            written = json.load(io.open(bl, encoding="utf-8"))["units"]
            rc_after = check(rows, bl)
        finally:
            sys.stdout = old
        case("check passes units that match their baseline", rc_same == 0)
        case("VACUITY: check refuses a unit worse than its baseline", rc_worse == 1)
        case("VACUITY: check refuses a new unit that carries refusals", rc_new == 1)
        case("--baseline records every judged unit with its count and codes, and --check then passes",
             rc_written == 0 and written["bad.php"]["refusals"] == nbad and written["good.php"]["refusals"] == 0
             and written["bad.php"]["codes"] and rc_after == 0)

    live = units_on_disk()
    judged_pages = [u for u in live if u[1] == "page" and u[2] is not None]
    judged_lessons = [u for u in live if u[1] == "lesson" and u[2] is not None]
    case("VACUITY: the walk finds at least 20 pages with copy (%d) and 15 lessons (%d)" % (len(judged_pages), len(judged_lessons)),
         len(judged_pages) >= 20 and len(judged_lessons) >= 15)
    case("every lesson's study array was found (none UNCHECKED)", all(not u[3] for u in judged_lessons))
    judged_emails = [u for u in live if u[1] == "email" and u[2] is not None]
    judged_weekly = [u for u in live if u[1] == "weekly" and u[2] is not None]
    case("VACUITY: the walk finds at least 15 drip fragments (%d) and 30 weeklies (%d), every weekly with a Subject"
         % (len(judged_emails), len(judged_weekly)),
         len(judged_emails) >= 15 and len(judged_weekly) >= 30 and all(not u[3] for u in judged_weekly))
    print("prose_check selftest: %d check(s), %d failure(s) (engine at %s)" % (29, len(fails), ENGINE_PATH))
    return 1 if fails else 0


def main(argv=None) -> int:
    ap = argparse.ArgumentParser(description=(__doc__ or "").split("\n")[0])
    ap.add_argument("--report", action="store_true")
    ap.add_argument("--baseline", action="store_true")
    ap.add_argument("--check", action="store_true")
    ap.add_argument("--unit", help="judge one unit and spell out every refusal")
    ap.add_argument("--show", help="print the markdown the engine sees for one unit")
    ap.add_argument("--selftest", action="store_true")
    a = ap.parse_args(argv)
    if a.selftest:
        return selftest()
    if a.report:
        return report()
    if a.baseline:
        return write_baseline()
    if a.check:
        return check()
    if a.unit:
        return show_unit(a.unit, spell=True)
    if a.show:
        return show_unit(a.show, spell=False)
    print(__doc__)
    return 0


if __name__ == "__main__":
    sys.exit(main())
