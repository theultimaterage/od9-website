#!/usr/bin/env python3
"""email-lint — fail the build when an OD9 email surface drifts from the
branded, CAN-SPAM-compliant shared chrome (includes/email_layout.php).

The email system mirrors the website's shared-chrome model: one layout
(od9_email_layout) supplies the logo header + unsubscribe/postal footer, every
sender routes through it, and each drip template is an inner-content FRAGMENT.
This gate keeps that true and catches the bug classes that actually shipped:

  [LAYOUT]     includes/email_layout.php must carry the logo <img> (absolute
               offda9.com/images/email src + alt), an unsubscribe link, a
               physical postal address (ZIP), and define od9_email_button.
  [SENDER]     each sender must call od9_email_layout( — i.e. route through chrome.
  [FRAGMENT]   a drip template must be a FRAGMENT (no <!DOCTYPE/<html>/<head>/
               <body> — the layout supplies those), every <img> has alt=, and it
               uses ONLY tokens the drip personalize() resolves. This catches
               {{tier}}/{{credits}}/{{achievement_name}} shipping literal.
  [DOC]        a registered standalone email (founding-patron) must have an
               unsubscribe link, a postal address, alt on imgs, and only the
               tokens ITS sender resolves (catches {{first_name}} vs
               {{first_name|Hey}} mismatches).

Exit 0 = clean, 1 = drift. Wired into deploy_templates.sh; run before any email commit.

Usage:
    python tools/email_lint.py [--quiet] [--list-ok]
"""
from __future__ import annotations
import argparse
import re
import sys
from pathlib import Path

# Windows consoles default to cp1252 -> the ✓/✗ (or any non-ASCII a template
# surfaces) raises UnicodeEncodeError and crashes the gate. Force utf-8.
if hasattr(sys.stdout, "reconfigure"):
    try:
        sys.stdout.reconfigure(encoding="utf-8", errors="replace")
    except Exception:
        pass

REPO = Path(__file__).resolve().parent.parent
LAYOUT_REL = "includes/email_layout.php"
SENDER_RELS = [
    "public/subscribe.php",
    "api/drip/sender.php",
    "api/drip/weekly_lovelogic_sender.php",
]
TPL_DIR = REPO / "email-templates"

# Tokens the DRIP engine's personalize() (api/drip/sender.php) resolves.
DRIP_TOKENS = {
    "{{username}}", "{{first_name}}", "{{FIRST_NAME}}", "{{NAME}}",
    "{{email}}", "{{EMAIL}}", "{{unsubscribe_url}}", "{{UNSUBSCRIBE_URL}}",
}
# Standalone full-doc emails: filename -> tokens ITS own sender resolves.
STANDALONE = {
    "founding-patron-launch.html": {"{{first_name|Hey}}", "{{unsubscribe_url}}"},
}

TOKEN_RX     = re.compile(r"\{\{[^{}]*\}\}")
IMG_RX       = re.compile(r"<img\b[^>]*>", re.I)
DOCCHROME_RX = re.compile(r"<!DOCTYPE|<html\b|<head\b|<body\b", re.I)
ZIP_RX       = re.compile(r"\b\d{5}(?:-\d{4})?\b")          # US ZIP / ZIP+4
UNSUB_RX     = re.compile(r"unsubscribe", re.I)
LAYOUT_CALL  = re.compile(r"od9_email_layout\s*\(")


def lint_layout(text: str) -> list[str]:
    issues = []
    imgs = IMG_RX.findall(text)
    if not any("images/email/" in i for i in imgs) and "images/email/" not in text:
        issues.append("LAYOUT  no logo <img> with an absolute offda9.com/images/email/ src")
    if not any("alt=" in i for i in imgs):
        issues.append("LAYOUT  logo <img> missing alt=")
    if not UNSUB_RX.search(text):
        issues.append("LAYOUT  no unsubscribe link")
    if not ZIP_RX.search(text):
        issues.append("LAYOUT  no physical postal address (ZIP) — CAN-SPAM")
    if "function od9_email_button" not in text:
        issues.append("LAYOUT  od9_email_button() not defined")
    return issues


def lint_sender(text: str) -> list[str]:
    if not LAYOUT_CALL.search(text):
        return ["SENDER  does not call od9_email_layout( — email bypasses the branded chrome"]
    return []


def _img_alt_issues(text: str) -> list[str]:
    return [f"IMG_NO_ALT  <img> without alt=: {i[:60]}…"
            for i in IMG_RX.findall(text) if "alt=" not in i]


def _bad_tokens(text: str, allowed: set[str]) -> list[str]:
    bad = sorted({t for t in TOKEN_RX.findall(text) if t not in allowed})
    return [f"BROKEN_TOKEN  {t} is not resolved by its sender → ships literal" for t in bad]


def lint_fragment(text: str, allowed: set[str]) -> list[str]:
    issues = []
    if DOCCHROME_RX.search(text):
        issues.append("NOT_FRAGMENT  has <!DOCTYPE/<html>/<head>/<body> — the layout supplies those")
    issues += _img_alt_issues(text)
    issues += _bad_tokens(text, allowed)
    return issues


def lint_standalone(text: str, allowed: set[str]) -> list[str]:
    issues = []
    if not UNSUB_RX.search(text):
        issues.append("DOC  no unsubscribe link")
    if not ZIP_RX.search(text):
        issues.append("DOC  no physical postal address (ZIP) — CAN-SPAM")
    issues += _img_alt_issues(text)
    issues += _bad_tokens(text, allowed)
    return issues


def main() -> int:
    ap = argparse.ArgumentParser()
    ap.add_argument("--quiet", action="store_true")
    ap.add_argument("--list-ok", action="store_true")
    args = ap.parse_args()

    surfaces: dict[str, list[str]] = {}
    ok: list[str] = []

    def record(rel: str, issues: list[str]):
        (surfaces.__setitem__(rel, issues) if issues else ok.append(rel))

    # LAYOUT
    layout = REPO / LAYOUT_REL
    if not layout.exists():
        print(f"email-lint: layout missing: {LAYOUT_REL}", file=sys.stderr)
        return 2
    record(LAYOUT_REL, lint_layout(layout.read_text(encoding="utf-8", errors="replace")))

    # SENDERS
    for rel in SENDER_RELS:
        p = REPO / rel
        if not p.exists():
            record(rel, [f"SENDER  file missing: {rel}"]); continue
        record(rel, lint_sender(p.read_text(encoding="utf-8", errors="replace")))

    # TEMPLATES (top-level *.html only; lovelogic-weekly/*.md + _archive/ are out of scope)
    for p in sorted(TPL_DIR.glob("*.html")):
        rel = f"email-templates/{p.name}"
        text = p.read_text(encoding="utf-8", errors="replace")
        if p.name in STANDALONE:
            record(rel, lint_standalone(text, STANDALONE[p.name]))
        else:
            record(rel, lint_fragment(text, DRIP_TOKENS))

    n = len(surfaces) + len(ok)
    print(f"email-lint: scanned {n} surface(s)")
    print(f"  compliant: {len(ok)}   drifted: {len(surfaces)}")
    if not args.quiet and surfaces:
        print("\n--- DRIFT (worklist) ---")
        for rel in sorted(surfaces):
            print(f"\n  {rel}")
            for i in surfaces[rel]:
                print(f"      - {i}")
    if args.list_ok and ok:
        print("\n--- compliant ---")
        for rel in sorted(ok):
            print(f"  {rel}")

    clean = not surfaces
    print("\nRESULT:", "CLEAN ✓" if clean else "DRIFT FOUND ✗")
    return 0 if clean else 1


if __name__ == "__main__":
    raise SystemExit(main())
