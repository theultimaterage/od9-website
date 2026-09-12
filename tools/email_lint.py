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
    python tools/email_lint.py --selftest   # prove every rule can see its own positive
"""
from __future__ import annotations
import argparse
import re
import sys
import tempfile
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
    "subscribe.php",              # moved from public/ in the public->root restructure; the lint
                                  # reported it "missing" on every run until 2026-09-12
    "api/drip/sender.php",
    "api/drip/weekly_lovelogic_sender.php",
]
TPL_REL = "email-templates"
TPL_DIR = REPO / TPL_REL

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


class LayoutMissing(FileNotFoundError):
    """includes/email_layout.php is absent — no surface can be judged against it."""


def scan(repo: Path) -> tuple[dict[str, list[str]], list[str]]:
    """Lint every email surface under `repo` (a checkout root, or a fixture tree
    of the same shape). Returns (drifted surface -> issues, compliant surfaces).
    Raises LayoutMissing when includes/email_layout.php is absent. Never prints,
    so --selftest can run it against known-positive / known-negative trees."""
    surfaces: dict[str, list[str]] = {}
    ok: list[str] = []

    def record(rel: str, issues: list[str]):
        (surfaces.__setitem__(rel, issues) if issues else ok.append(rel))

    # LAYOUT
    layout = repo / LAYOUT_REL
    if not layout.exists():
        raise LayoutMissing(LAYOUT_REL)
    record(LAYOUT_REL, lint_layout(layout.read_text(encoding="utf-8", errors="replace")))

    # SENDERS
    for rel in SENDER_RELS:
        p = repo / rel
        if not p.exists():
            record(rel, [f"SENDER  file missing: {rel}"]); continue
        record(rel, lint_sender(p.read_text(encoding="utf-8", errors="replace")))

    # TEMPLATES (top-level *.html only; lovelogic-weekly/*.md + _archive/ are out of scope)
    for p in sorted((repo / TPL_REL).glob("*.html")):
        rel = f"{TPL_REL}/{p.name}"
        text = p.read_text(encoding="utf-8", errors="replace")
        if p.name in STANDALONE:
            record(rel, lint_standalone(text, STANDALONE[p.name]))
        else:
            record(rel, lint_fragment(text, DRIP_TOKENS))
    return surfaces, ok


# ---------------------------------------------------------------------------
# --selftest — a scanner that prints "0 findings" is indistinguishable from a
# broken one. Build a KNOWN-POSITIVE tree (every rule planted once, in each
# input form scan() actually reads) and a KNOWN-NEGATIVE tree of the same
# shape, run scan() on both, and assert each label fires / stays silent.
# Exit 0 = every case passed, 2 = at least one rule cannot see its own positive.
# Fixtures copy the SHAPE of the real files (layout functions, the drip
# sender's personalize() token set, fragment templates), never their content.
# ---------------------------------------------------------------------------
_FX_LOGO   = ('<img src="https://offda9.com/images/email/od9-lettermark.png" '
              'width="440" height="148" alt="OD9">')
_FX_UNSUB  = '<a href="{{unsubscribe_url}}">Unsubscribe</a>'
_FX_POSTAL = 'Off Da 9 Ent. LLC &middot; 1 Example Ave. &middot; Chicago, IL 60620-5308'

# Layout in the real shape: od9_email_button + od9_email_layout, logo under
# images/email/ with alt, unsubscribe link, CAN-SPAM postal address with ZIP.
_FX_LAYOUT_OK = (
    "<?php\n"
    "function od9_email_button(string $label, string $url): string {\n"
    "    return '<a href=\"' . $url . '\">' . $label . '</a>';\n"
    "}\n"
    "function od9_email_layout(string $bodyHtml, array $opts = []): string {\n"
    "    return '<!DOCTYPE html><html><body>" + _FX_LOGO + "' . $bodyHtml\n"
    "         . '<p>" + _FX_UNSUB + " &middot; " + _FX_POSTAL + "</p></body></html>';\n"
    "}\n"
)
# Every LAYOUT sub-check violated at once: logo not under images/email/ and
# without alt, no unsubscribe link, no ZIP, no od9_email_button().
_FX_LAYOUT_BAD = (
    "<?php\n"
    "function od9_email_layout(string $bodyHtml, array $opts = []): string {\n"
    "    return '<html><body><img src=\"https://offda9.com/images/other/mark.png\">'\n"
    "         . $bodyHtml . '<p>Chicago, IL</p></body></html>';\n"
    "}\n"
)
# Drip sender in the real shape: personalize() resolves exactly DRIP_TOKENS and
# the fragment is wrapped by od9_email_layout( before it is personalized.
_FX_SENDER_OK = (
    "<?php\n"
    "require_once __DIR__ . '/../../includes/email_layout.php';\n"
    "function personalize(string $html, array $en): string {\n"
    "    return str_replace(\n"
    "        ['{{username}}', '{{first_name}}', '{{FIRST_NAME}}', '{{NAME}}', "
    "'{{email}}', '{{EMAIL}}', '{{unsubscribe_url}}', '{{UNSUBSCRIBE_URL}}'],\n"
    "        [$en['username'], $en['first_name'], $en['first_name'], $en['first_name'], "
    "$en['email'], $en['email'], $en['unsub'], $en['unsub']],\n"
    "        $html);\n"
    "}\n"
    "$html = personalize(od9_email_layout($fragment, ['title' => $step['subject']]), $en);\n"
)
# A sender that mails the raw fragment. It even NAMES the layout in a comment —
# the gate must demand a real call, not a mention.
_FX_SENDER_BAD = (
    "<?php\n"
    "// TODO: route through od9_email_layout like the other senders\n"
    "$html = personalize($fragment, $en);\n"
    "mail($en['email'], $step['subject'], $html);\n"
)
# Clean drip fragment: allowlisted tokens only, no doc chrome, alt on the <img>.
_FX_DRIP_OK = (
    "<p>Hi {{first_name}} ({{username}}) — welcome to the 9.</p>\n"
    '<img src="https://offda9.com/images/email/welcome.png" width="64" alt="Welcome">\n'
    '<p><a href="{{unsubscribe_url}}">Unsubscribe</a></p>\n'
)
# Clean standalone full-doc email: its own sender's tokens, unsubscribe + ZIP.
_FX_STANDALONE_OK = (
    "<!DOCTYPE html>\n<html><body>\n" + _FX_LOGO + "\n"
    "<p>{{first_name|Hey}}, the founding patron door is open.</p>\n"
    "<p>" + _FX_UNSUB + " &middot; " + _FX_POSTAL + "</p>\n</body></html>\n"
)


def _write(root: Path, rel: str, text: str) -> None:
    p = root / rel
    p.parent.mkdir(parents=True, exist_ok=True)
    p.write_text(text, encoding="utf-8")


def _build_positive(root: Path) -> None:
    _write(root, LAYOUT_REL, _FX_LAYOUT_BAD)
    # subscribe.php deliberately absent -> the SENDER "file missing" form
    _write(root, "api/drip/sender.php", _FX_SENDER_BAD)
    _write(root, "api/drip/weekly_lovelogic_sender.php", _FX_SENDER_OK)
    _write(root, f"{TPL_REL}/not-fragment.html",
           "<!DOCTYPE html>\n<html><body><p>Hi {{username}}</p></body></html>\n")
    _write(root, f"{TPL_REL}/img-no-alt.html",
           '<p>Hi {{first_name}}</p>\n<img src="https://offda9.com/images/email/streak.png" width="64">\n')
    _write(root, f"{TPL_REL}/broken-tier.html",
           "<p>{{first_name}}, you are now {{tier}} with {{credits}} credits.</p>\n")
    _write(root, f"{TPL_REL}/first-name-fallback.html",
           "<p>{{first_name|Hey}}, this drip borrowed the standalone sender's fallback form.</p>\n")
    _write(root, f"{TPL_REL}/clean-drip.html", _FX_DRIP_OK)
    # The registered standalone with every DOC-class fault plus the token mismatch
    # ({{first_name}} where its sender only resolves {{first_name|Hey}}).
    _write(root, f"{TPL_REL}/founding-patron-launch.html",
           "<!DOCTYPE html>\n<html><body>\n"
           '<img src="https://offda9.com/images/email/patron.png">\n'
           "<p>{{first_name}}, the door is open.</p>\n"
           "<p>Off Da 9 Ent. LLC &middot; Chicago, IL</p>\n</body></html>\n")
    # Out-of-scope forms the scanner must NOT read (they look maximally broken).
    _write(root, f"{TPL_REL}/lovelogic-weekly/2026-09-07.md", "# weekly\n<!DOCTYPE html> {{tier}}\n")
    _write(root, f"{TPL_REL}/_archive-academic-20260501/old.html", "<!DOCTYPE html> {{tier}}\n")


def _build_negative(root: Path) -> None:
    _write(root, LAYOUT_REL, _FX_LAYOUT_OK)
    for rel in SENDER_RELS:
        _write(root, rel, _FX_SENDER_OK)
    _write(root, f"{TPL_REL}/welcome-day0.html", _FX_DRIP_OK)
    _write(root, f"{TPL_REL}/founding-patron-launch.html", _FX_STANDALONE_OK)


def selftest() -> int:
    print("email-lint --selftest")
    cases: list[tuple[str, bool]] = []

    def case(name: str, passed: bool) -> None:
        cases.append((name, bool(passed)))

    def fires(found: dict[str, list[str]], rel: str, label: str, needle: str = "") -> bool:
        return any(i.startswith(label + "  ") and needle in i for i in found.get(rel, []))

    with tempfile.TemporaryDirectory(prefix="email-lint-selftest-") as td:
        pos, neg = Path(td) / "positive", Path(td) / "negative"
        _build_positive(pos)
        _build_negative(neg)

        try:
            found, ok = scan(pos)
        except Exception as e:  # a crash is a FAIL line, not a traceback
            print(f"  FAIL positive tree: scan() raised {type(e).__name__}: {e}")
            found, ok = {}, []
        L, SA = LAYOUT_REL, f"{TPL_REL}/founding-patron-launch.html"
        case("LAYOUT: logo <img> not under images/email/ fires", fires(found, L, "LAYOUT", "no logo"))
        case("LAYOUT: logo <img> without alt= fires", fires(found, L, "LAYOUT", "missing alt"))
        case("LAYOUT: no unsubscribe link fires", fires(found, L, "LAYOUT", "no unsubscribe"))
        case("LAYOUT: no postal address (ZIP) fires", fires(found, L, "LAYOUT", "ZIP"))
        case("LAYOUT: od9_email_button() not defined fires", fires(found, L, "LAYOUT", "od9_email_button"))
        case("SENDER: sender that only mentions od9_email_layout in a comment fires",
             fires(found, "api/drip/sender.php", "SENDER", "does not call"))
        case("SENDER: registered sender file absent fires (subscribe.php)",
             fires(found, "subscribe.php", "SENDER", "file missing"))
        case("SENDER: sender wrapping via od9_email_layout( stays silent",
             "api/drip/weekly_lovelogic_sender.php" in ok)
        case("NOT_FRAGMENT: drip template carrying <!DOCTYPE>/<html>/<body> fires",
             fires(found, f"{TPL_REL}/not-fragment.html", "NOT_FRAGMENT"))
        case("IMG_NO_ALT: drip <img> without alt= fires",
             fires(found, f"{TPL_REL}/img-no-alt.html", "IMG_NO_ALT"))
        case("BROKEN_TOKEN: {{tier}} unresolved by the drip sender fires",
             fires(found, f"{TPL_REL}/broken-tier.html", "BROKEN_TOKEN", "{{tier}}"))
        case("BROKEN_TOKEN: {{credits}} unresolved by the drip sender fires",
             fires(found, f"{TPL_REL}/broken-tier.html", "BROKEN_TOKEN", "{{credits}}"))
        case("BROKEN_TOKEN: {{first_name|Hey}} in a DRIP template (sender resolves {{first_name}}) fires",
             fires(found, f"{TPL_REL}/first-name-fallback.html", "BROKEN_TOKEN", "{{first_name|Hey}}"))
        case("BROKEN_TOKEN: {{first_name}} in the STANDALONE (its sender resolves {{first_name|Hey}}) fires",
             fires(found, SA, "BROKEN_TOKEN", "{{first_name}}"))
        case("DOC: standalone without an unsubscribe link fires", fires(found, SA, "DOC", "unsubscribe"))
        case("DOC: standalone without a postal address (ZIP) fires", fires(found, SA, "DOC", "ZIP"))
        case("IMG_NO_ALT: standalone <img> without alt= fires", fires(found, SA, "IMG_NO_ALT"))
        case("allowlisted drip tokens ({{first_name}}, {{username}}, {{unsubscribe_url}}) stay silent",
             f"{TPL_REL}/clean-drip.html" in ok)
        case("out-of-scope forms (lovelogic-weekly/*.md, _archive*/) are not scanned",
             not any("lovelogic-weekly" in r or "_archive" in r for r in [*found, *ok]))
        case(f"positive tree vacuity guard: 8 drifted + 2 compliant (got {len(found)} + {len(ok)})",
             len(found) == 8 and len(ok) == 2)

        try:
            found, ok = scan(neg)
        except Exception as e:
            print(f"  FAIL negative tree: scan() raised {type(e).__name__}: {e}")
            found, ok = {"<crash>": [str(e)]}, []
        case(f"negative tree: clean layout + 3 senders + 2 templates -> zero findings (got {len(found)})",
             found == {} and len(ok) == 6)

        try:
            scan(Path(td) / "no-such-checkout")
            missing = False
        except LayoutMissing:
            missing = True
        case("missing includes/email_layout.php raises LayoutMissing (the exit-2 path)", missing)

    fails = [n for n, p in cases if not p]
    for name, passed in cases:
        print(("  OK   " if passed else "  FAIL ") + name)
    print(f"\nSELFTEST: {'PASS' if not fails else 'FAIL'} ({len(cases) - len(fails)}/{len(cases)} cases)")
    return 0 if not fails else 2


def main() -> int:
    ap = argparse.ArgumentParser()
    ap.add_argument("--quiet", action="store_true")
    ap.add_argument("--list-ok", action="store_true")
    ap.add_argument("--selftest", action="store_true",
                    help="prove every rule can see its own positive (exit 0 pass / 2 fail)")
    args = ap.parse_args()
    if args.selftest:
        return selftest()

    try:
        surfaces, ok = scan(REPO)
    except LayoutMissing:
        print(f"email-lint: layout missing: {LAYOUT_REL}", file=sys.stderr)
        return 2

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
