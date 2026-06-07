#!/usr/bin/env python3
"""web-template-lint — fail the build when a public page drifts from the
shared chrome (the single source of truth: includes/head.php + includes/nav.php
+ includes/footer.php + css/od9.css).

The "universal nav" only stays universal if every page USES it. This catches the
exact classes that produced the 2026-06-06 nav fiasco:

  [INLINE_CHROME]  a page carries its OWN .od9-nav / .od9-footer CSS inline
                   (must live in od9.css, or it drifts — 17/31 pages did this)
  [HANDROLLED_HEAD]a page hand-rolls <title>/<meta> instead of head.php
                   (meta/canonical drift — the original SEO bug)
  [BAD_NAV]        a page renders a nav but does NOT include includes/nav.php
                   (an inline copy or a non-canonical include like
                   dashboard/includes/nav.php that still lists "Think Tank")
  [NAV_NO_CSS]     a page renders the nav but never loads od9.css and doesn't
                   use head.php (unstyled nav -> the guide.php "huge logo" bug)
  [OWN_ENV]        a page re-implements env/base-path detection inline
                   (use includes/env.php)
  [DUP_CHROME]     more than one nav.php / head.php / footer.php exists

Exit 0 = clean, 1 = drift found. Wire into a pre-commit hook and deploy.py.

Usage:
    python tools/web_template_lint.py [--root public] [--quiet] [--list-ok]
"""
from __future__ import annotations
import argparse
import re
import sys
from pathlib import Path

# Windows consoles default to cp1252, which can't encode the ✓/✗ in the RESULT
# line (or any non-ASCII a scanned page surfaces) -> UnicodeEncodeError crashes
# the gate. Force utf-8 with replacement so the linter never dies on its output.
if hasattr(sys.stdout, "reconfigure"):
    try:
        sys.stdout.reconfigure(encoding="utf-8", errors="replace")
    except Exception:
        pass

# --- canonical convention (overridable via .claude/web-template-lint-config.json later) ---
EXEMPT_FILES = {
    "includes/head.php", "includes/nav.php", "includes/footer.php",
}
# directories whose .php are endpoints/partials, not rendered pages
EXEMPT_DIR_PARTS = (
    "/api/", "dashboard/api/", "dashboard/auth/", "dashboard/includes/",
    "/includes/", "/library/",
)

RX = {
    "inline_chrome": re.compile(r"\.od9-nav\s*\{|\.nav-link\s*\{|\.nav-menu\s*\{|"
                                r"\.od9-footer\s*\{|\.footer-grid\s*\{|\.nav-logo\s*\{", re.I),
    "title":         re.compile(r"<title\b", re.I),
    "uses_head":     re.compile(r"include[^;\n]*includes/head\.php", re.I),
    "uses_nav":      re.compile(r"include[^;\n]*includes/nav\.php", re.I),
    "renders_nav":   re.compile(r"od9-nav|includes/nav\.php", re.I),
    "loads_css":     re.compile(r"css/od9\.css", re.I),
    "own_env":       re.compile(r"strpos\(\s*__DIR__\s*,\s*['\"]xampp|"
                                r"SERVER_NAME'\]\s*(\?\?|==).{0,30}localhost", re.I),
}


def is_exempt(rel: str) -> bool:
    r = "/" + rel.replace("\\", "/")
    if rel.replace("\\", "/") in EXEMPT_FILES:
        return True
    return any(part in r for part in EXEMPT_DIR_PARTS)


def lint_page(text: str) -> list[str]:
    issues = []
    uses_head = bool(RX["uses_head"].search(text))
    renders_nav = bool(RX["renders_nav"].search(text))
    if RX["inline_chrome"].search(text):
        issues.append("INLINE_CHROME  carries its own nav/footer CSS (belongs in od9.css)")
    if RX["title"].search(text) and not uses_head:
        issues.append("HANDROLLED_HEAD  own <head>/<title> (use includes/head.php)")
    if renders_nav and not RX["uses_nav"].search(text):
        issues.append("BAD_NAV  renders a nav but not via includes/nav.php")
    if renders_nav and not (RX["loads_css"].search(text) or uses_head):
        issues.append("NAV_NO_CSS  renders nav but never loads od9.css (unstyled)")
    if RX["own_env"].search(text):
        issues.append("OWN_ENV  reimplements env/base-path (use includes/env.php)")
    return issues


RX_DIR_INCLUDE = re.compile(
    r"(?:include|require)(?:_once)?\s*\(?\s*__DIR__\s*\.\s*['\"]([^'\"]+\.php)['\"]")


def check_includes(page: Path, text: str) -> list[str]:
    """Resolve every `include/require __DIR__ . '<rel>'` of a shared chrome file
    and flag it when the target doesn't exist. Catches a wrong relative depth
    (./includes vs ../includes) that the string-match in lint_page misses -- the
    2026-06-06 profile.php bug 'used head.php' per the regex but pointed at a
    nonexistent dashboard/includes/head.php, so it rendered with no od9.css."""
    issues = []
    for rel in RX_DIR_INCLUDE.findall(text):
        if not re.search(r"(?:head|nav|footer|env)\.php$", rel):
            continue
        target = (page.parent / rel.lstrip("/\\")).resolve()
        if not target.exists():
            issues.append(f"BROKEN_INCLUDE  __DIR__ . '{rel}' -> missing {target.name} (wrong path?)")
    return issues


# --- [UNSTYLED] content-class check -------------------------------------------
# The gap that let settings.php ship unstyled (2026-06-06): the shared-chrome
# migration stripped a page's page-specific content CSS, and od9.css never
# defined those classes, so the content rendered with no styling. lint_page only
# knows about chrome. This check gathers the classes a page actually USES and the
# classes DEFINED in every stylesheet it loads (od9.css via head.php, any
# <link>ed local .css, inline <style>) and flags content classes with no CSS home.
RX_CLASS_ATTR = re.compile(r'class\s*=\s*"([^"]*)"')
RX_LINK_CSS   = re.compile(r'<link[^>]+href=["\']([^"\']+\.css)[^"\']*["\']', re.I)
RX_STYLE_BLK  = re.compile(r'<style\b[^>]*>(.*?)</style>', re.I | re.S)
RX_CSS_CLASS  = re.compile(r'\.([A-Za-z_][\w-]*)')
RX_PHP_TAG    = re.compile(r'<\?.*?\?>', re.S)
# FontAwesome + JS-toggled state classes that legitimately have no static CSS.
SAFE_CLASSES  = {"fa", "fas", "fab", "far", "fal", "fad", "active", "hidden", "copied"}
SAFE_PREFIXES = ("fa-",)
# Only flag a SUBSTANTIALLY unstyled page (the settings.php case had ~15 undefined
# content classes), not 1-2 dynamic/straggler classes — keeps the gate high-signal
# and false-positive-free. Tune here if needed.
UNSTYLED_MIN  = 3


def _classes_used(text: str) -> set[str]:
    used = set()
    for attr in RX_CLASS_ATTR.findall(text):
        attr = RX_PHP_TAG.sub(" ", attr)  # drop dynamic class="<?= ... ?>" parts
        for tok in attr.split():
            if tok and "<" not in tok and "?" not in tok:
                used.add(tok)
    return used


def _classes_defined(text: str, root: Path, uses_head: bool) -> set[str]:
    defined = set()
    for blk in RX_STYLE_BLK.findall(text):
        defined |= set(RX_CSS_CLASS.findall(blk))
    css = set()
    if uses_head or RX["loads_css"].search(text):
        css.add("css/od9.css")  # head.php links od9.css
    for href in RX_LINK_CSS.findall(text):
        h = href.split("?")[0]
        if not h.startswith("http"):
            css.add(h.lstrip("/"))
    for rel in css:
        cand = root / rel
        if not cand.exists():
            cand = root / "css" / Path(rel).name  # fall back to basename under css/
        if cand.exists():
            defined |= set(RX_CSS_CLASS.findall(cand.read_text(encoding="utf-8", errors="replace")))
    return defined


def check_unstyled(text: str, root: Path) -> list[str]:
    used = _classes_used(text)
    if not used:
        return []
    defined = _classes_defined(text, root, bool(RX["uses_head"].search(text)))
    unknown = sorted(c for c in used
                     if c not in defined and c not in SAFE_CLASSES
                     and not c.endswith("-")  # dynamic remnant: class="tier-<?= $t ?>"
                     and not any(c.startswith(p) for p in SAFE_PREFIXES))
    if len(unknown) >= UNSTYLED_MIN:
        shown = ", ".join(unknown[:10]) + (" ..." if len(unknown) > 10 else "")
        return [f"UNSTYLED  {len(unknown)} content classes defined in no loaded stylesheet: {shown}"]
    return []


def find_dupes(tree: Path) -> dict[str, list[str]]:
    """Duplicate chrome WITHIN the deployed web root (public/). Untracked
    repo-root siblings (includes/, config/) are a separate housekeeping concern,
    not drift the deployed site can see, so they're intentionally out of scope."""
    dupes = {}
    for name in ("nav.php", "head.php", "footer.php"):
        hits = [str(p.relative_to(tree)).replace("\\", "/")
                for p in tree.rglob(name)
                if "node_modules" not in str(p) and ".git" not in str(p)]
        if len(hits) > 1:
            dupes[name] = hits
    return dupes


def main() -> int:
    ap = argparse.ArgumentParser()
    ap.add_argument("--root", default="public", help="web root to scan")
    ap.add_argument("--quiet", action="store_true")
    ap.add_argument("--list-ok", action="store_true", help="also list compliant pages")
    args = ap.parse_args()

    repo = Path(__file__).resolve().parent.parent
    root = (repo / args.root).resolve()
    if not root.exists():
        print(f"web-template-lint: root not found: {root}", file=sys.stderr)
        return 2

    pages = sorted(p for p in root.rglob("*.php"))
    drifted, ok = {}, []
    for p in pages:
        rel = str(p.relative_to(root)).replace("\\", "/")
        if is_exempt(rel):
            continue
        text = p.read_text(encoding="utf-8", errors="replace")
        issues = lint_page(text) + check_includes(p, text) + check_unstyled(text, root)
        (drifted.__setitem__(rel, issues) if issues else ok.append(rel))

    dupes = find_dupes(root)

    n_pages = len(drifted) + len(ok)
    print(f"web-template-lint: scanned {n_pages} page(s) under {args.root}/")
    print(f"  compliant: {len(ok)}   drifted: {len(drifted)}   duplicate-chrome files: {len(dupes)}")
    if not args.quiet and drifted:
        print("\n--- DRIFT (migration worklist) ---")
        for rel in sorted(drifted):
            print(f"\n  {rel}")
            for i in drifted[rel]:
                print(f"      - {i}")
    if dupes:
        print("\n--- DUPLICATE CHROME (should be ONE each) ---")
        for name, hits in dupes.items():
            print(f"  {name}:")
            for h in hits:
                print(f"      {h}")
    if args.list_ok and ok:
        print("\n--- compliant ---")
        for rel in ok:
            print(f"  {rel}")

    clean = not drifted and not dupes
    print("\nRESULT:", "CLEAN ✓" if clean else "DRIFT FOUND ✗")
    return 0 if clean else 1


if __name__ == "__main__":
    raise SystemExit(main())
