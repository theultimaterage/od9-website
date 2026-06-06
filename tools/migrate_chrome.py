#!/usr/bin/env python3
"""migrate_chrome.py - move an offda9.com page onto the shared chrome.

Rewrites ONLY the <head>: replaces the hand-rolled boilerplate meta + the
shared nav <style> block (now provided by css/od9.css via includes/head.php)
with an includes/head.php include, while PRESERVING the page's identity:
its <title>/description (passed to head.php as $page_* vars), its JSON-LD,
its page-specific <style>, and its own body/html CSS rule (od9.css owns
:root/reset/nav but NOT body, which is per-page). The <body> (which already
includes nav.php + footer.php) is left byte-for-byte intact.

Safety:
  - only auto-migrates when the shared CSS lives in its OWN <style> block (the
    one containing `.od9-nav`); a page whose shared + page CSS are mixed into
    one block is FLAGGED for manual handling, never mangled.
  - dry-run by default; --apply writes; --all does every drifted page.
  - idempotent: a page already on head.php is skipped.

Verify after with: python tools/web_template_lint.py
"""
from __future__ import annotations
import argparse
import re
import subprocess
import sys
from pathlib import Path

if hasattr(sys.stdout, "reconfigure"):
    try:
        sys.stdout.reconfigure(encoding="utf-8", errors="replace")
    except Exception:
        pass

REPO = Path(__file__).resolve().parent.parent
HEAD_INCLUDE = "<?php include __DIR__ . '/includes/head.php'; ?>"
DEFAULT_OG_IMG = "/images/logos/od9-logo.png"

RX_HEAD = re.compile(r"(?is)<head\b[^>]*>(.*?)</head>")
RX_TITLE = re.compile(r"(?is)<title\b[^>]*>(.*?)</title>")
RX_DESC = re.compile(r'(?is)<meta\s+name=["\']description["\']\s+content=["\'](.*?)["\']\s*/?>')
RX_OGT = re.compile(r'(?is)<meta\s+property=["\']og:title["\']\s+content=["\'](.*?)["\']\s*/?>')
RX_OGD = re.compile(r'(?is)<meta\s+property=["\']og:description["\']\s+content=["\'](.*?)["\']\s*/?>')
RX_OGI = re.compile(r'(?is)<meta\s+property=["\']og:image["\']\s+content=["\'](.*?)["\']\s*/?>')
RX_STYLE = re.compile(r"(?is)<style\b[^>]*>.*?</style>")
RX_JSONLD = re.compile(r'(?is)<script[^>]*type=["\']application/ld\+json["\'][^>]*>.*?</script>')
RX_SCRIPT = re.compile(r"(?is)<script\b[^>]*>.*?</script>")
RX_ROBOTS = re.compile(r'(?is)<meta\s+name=["\']robots["\']\s+content=["\'](.*?)["\']')
RX_BODYRULE = re.compile(r"(?is)(?<![\.\w])(?:body|html)\s*\{[^{}]*\}")
RX_PAGE_SEL = re.compile(r"\.(container|hero|wrapper|card|grid|panel|section|btn|"
                         r"contact-|music-|crew-|form-|social-|tier-|feature)[\s{.:]", re.I)


def php_str(s: str) -> str:
    return "'" + (s or "").strip().replace("\\", "\\\\").replace("'", "\\'") + "'"


def strip_shared_css(css: str) -> str:
    """Walk CSS rule-by-rule; drop the shared chrome rules (od9.css owns them)
    while keeping page-specific rules + body/html. Handles one level of @media
    nesting so a page's own responsive rules survive but the nav @media goes."""
    out, i, n = [], 0, len(css)
    while i < n:
        brace = css.find("{", i)
        if brace == -1:
            out.append(css[i:]); break
        selector = css[i:brace].strip()
        depth, j = 0, brace
        while j < n:
            if css[j] == "{":
                depth += 1
            elif css[j] == "}":
                depth -= 1
                if depth == 0:
                    break
            j += 1
        block = css[brace:j + 1]
        rule = css[i:j + 1]
        sel = selector
        shared = (sel in ("*", ":root")
                  or re.search(r"\.(od9-nav|nav-[\w-]*|mobile-[\w-]*)\b", sel) is not None)
        nav_media = sel.startswith("@media") and re.search(r"\.nav-menu|\.mobile-toggle|\.od9-nav", block)
        if not (shared or nav_media):
            out.append(rule)
        i = j + 1
    return "".join(out).strip()


def migrate(text: str, slug: str) -> tuple[str | None, str]:
    if "includes/head.php" in text:
        return None, "already on head.php (skip)"
    m = RX_HEAD.search(text)
    if not m:
        return None, "no <head> found"
    head_inner = m.group(1)

    styles = RX_STYLE.findall(head_inner)
    inner_css = "\n".join(re.sub(r"(?is)^\s*<style[^>]*>|</style>\s*$", "", s).strip() for s in styles)
    # A page that COPIED the shared nav CSS inline gets those rules stripped
    # (od9.css owns them). A standalone page (no inline .od9-nav) owns its FULL
    # CSS -- its own :root tokens / reset / body -- so keep it verbatim; only the
    # boilerplate <head> meta is swapped for head.php. head.php loads od9.css
    # BEFORE this page <style>, so on any token/reset overlap the page still wins.
    has_inline_nav = any(".od9-nav" in s for s in styles)
    page_css = strip_shared_css(inner_css) if has_inline_nav else inner_css

    def grab(rx):
        mm = rx.search(head_inner)
        return mm.group(1).strip() if mm else ""

    title, desc = grab(RX_TITLE), grab(RX_DESC)
    ogt, ogd, ogi = grab(RX_OGT), grab(RX_OGD), grab(RX_OGI)
    if any("<?" in (v or "") for v in (title, desc, ogt, ogd)):
        return None, "dynamic <head> meta (PHP-generated title/desc) - manual"
    scripts = RX_SCRIPT.findall(head_inner)
    robots = grab(RX_ROBOTS)

    lines = ["<?php",
             f"$page_title = {php_str(title)};",
             f"$page_description = {php_str(desc)};",
             f"$page_slug = {php_str(slug)};"]
    if "noindex" in robots.lower():
        lines.append(f"$page_robots = {php_str(robots)};")
    if ogt and ogt != title:
        lines.append(f"$page_og_title = {php_str(ogt)};")
    if ogd and ogd != desc:
        lines.append(f"$page_og_description = {php_str(ogd)};")
    if ogi and ogi.replace("https://offda9.com", "") not in ("", DEFAULT_OG_IMG):
        lines.append(f"$page_og_image = {php_str(ogi)};")
    lines.append("?>")
    preamble = "\n".join(lines)

    parts = ["\n" + HEAD_INCLUDE]
    parts += ["\n" + s for s in scripts]   # preserve page-specific head scripts (chart.js, JSON-LD, ...)
    if page_css:
        parts.append("\n<style>\n" + page_css + "\n</style>")
    new_inner = "".join(parts) + "\n"

    head_open = m.group(0)[: m.group(0).index(">") + 1]
    new_head = head_open + new_inner + "</head>"
    # Insert the $page_* preamble right before <!DOCTYPE>/<html> (i.e. AFTER any
    # existing top-of-file PHP) so a leading declare(strict_types=1)/namespace
    # stays the first statement, and the page's own logic runs untouched.
    prefix = text[:m.start()]
    pos = prefix.lower().find("<!doctype")
    if pos == -1:
        pos = prefix.lower().find("<html")
    if pos == -1:
        pos = len(prefix)
    new_text = prefix[:pos] + preamble + "\n" + prefix[pos:] + new_head + text[m.end():]
    return new_text, "migrated"


def drifted_pages() -> list[Path]:
    out = subprocess.run([sys.executable, str(REPO / "tools" / "web_template_lint.py")],
                         capture_output=True, text=True).stdout
    pages = []
    for line in out.splitlines():
        s = line.strip()
        if s and s.endswith(".php") and " " not in s:
            cand = REPO / "public" / s
            if cand.exists():
                pages.append(cand)
    return pages


def main() -> int:
    ap = argparse.ArgumentParser()
    ap.add_argument("--file", help="single page (path relative to repo or absolute)")
    ap.add_argument("--all", action="store_true", help="every drifted page from the linter")
    ap.add_argument("--apply", action="store_true", help="write changes (default: dry-run preview)")
    args = ap.parse_args()

    if args.file:
        p = Path(args.file)
        targets = [p if p.is_absolute() else (REPO / args.file)]
    elif args.all:
        targets = drifted_pages()
    else:
        ap.error("pass --file <page> or --all")

    migrated = skipped = 0
    for p in targets:
        new, status = migrate(p.read_text(encoding="utf-8-sig", errors="replace"), p.name)
        if new is None:
            print(f"  SKIP  {p.relative_to(REPO)}  ({status})")
            skipped += 1
            continue
        if args.apply:
            p.write_text(new, encoding="utf-8")
            print(f"  DONE  {p.relative_to(REPO)}")
        else:
            print(f"\n===== {p.relative_to(REPO)} (DRY-RUN, first 46 lines) =====")
            print("\n".join(new.splitlines()[:46]))
        migrated += 1
    print(f"\nmigrate_chrome: {migrated} migrated, {skipped} skipped/flagged"
          + ("" if args.apply else "  (dry-run - add --apply to write)"))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
