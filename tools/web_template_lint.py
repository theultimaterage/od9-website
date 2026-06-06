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


def find_dupes(repo: Path) -> dict[str, list[str]]:
    dupes = {}
    for name in ("nav.php", "head.php", "footer.php"):
        hits = [str(p.relative_to(repo)).replace("\\", "/")
                for p in repo.rglob(name)
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
        issues = lint_page(p.read_text(encoding="utf-8", errors="replace"))
        (drifted.__setitem__(rel, issues) if issues else ok.append(rel))

    dupes = find_dupes(repo)

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
