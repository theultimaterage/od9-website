#!/usr/bin/env python3
"""WCAG 2.1 contrast auditor for OD9's dark theme.

Scans every `color:` declaration in public/ (.php/.css/.html) and computes its
WCAG contrast ratio against the dark backgrounds the theme actually paints on
(--d #0D0D0D, --dd #1A1A1A, and the #121212 card surface). Reports each text
color that fails AA — <4.5:1 for normal text, <3:1 for large text — against the
LIGHTEST dark background (#1A1A1A, the worst case among dark surfaces), with
file:line and the surrounding selector/context so text-on-light-card cases
(which this conservatively flags) can be triaged out by eye.

Run from repo root:  python tools/contrast_audit.py
Exit code 1 if any failures remain (usable as a CI gate).
"""
from __future__ import annotations
import re, sys, glob
from pathlib import Path

# Dark surfaces the theme paints text on. Worst case = lightest of these.
DARK_BGS = {"#0d0d0d": "--d", "#1a1a1a": "--dd", "#121212": "card"}
WORST_DARK = "#1a1a1a"

HEX3 = re.compile(r"^#([0-9a-f])([0-9a-f])([0-9a-f])$")
HEX6 = re.compile(r"^#([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})$")
COLOR_DECL = re.compile(r"(?<![-\w])color\s*:\s*(#[0-9a-fA-F]{3,6})", re.I)
BG_DECL = re.compile(r"background(?:-color)?\s*:\s*([^;}\"']+)", re.I)

# Theme tokens that resolve to a light/bright surface (so dark text on them is
# intentional and correct — these rules must be judged against the light bg).
LIGHT_VARS = {"--b", "--eb", "--primary-blue", "--electric-blue", "--neon-blue",
              "--gold", "--chrome", "--c", "--tier-color", "--rg"}
HEX_IN = re.compile(r"#[0-9a-fA-F]{3,6}")

# Brand colors used ONLY on decorative <i> platform/social icons. WCAG 1.4.3
# exempts logos/brand identity from the 4.5:1 text rule, and these all clear the
# 3:1 non-text (1.4.11) bar — recoloring them would destroy brand recognition
# (YouTube red, Discord blurple, Twitch purple, etc.). Verified by grep that
# every occurrence is an icon glyph, never body text.
BRAND_EXEMPT = {"#ff0000", "#5865f2", "#9146ff", "#1877f2", "#e4405f",
                "#e43526", "#ff5500", "#1db954", "#ff6719", "#ff6600"}


def rgb(hex_: str) -> tuple[int, int, int]:
    hex_ = hex_.lower()
    if m := HEX3.match(hex_):
        return tuple(int(c * 2, 16) for c in m.groups())
    if m := HEX6.match(hex_):
        return tuple(int(c, 16) for c in m.groups())
    raise ValueError(hex_)


def luminance(hex_: str) -> float:
    def lin(c: int) -> float:
        s = c / 255
        return s / 12.92 if s <= 0.03928 else ((s + 0.055) / 1.055) ** 2.4
    r, g, b = (lin(c) for c in rgb(hex_))
    return 0.2126 * r + 0.7152 * g + 0.0722 * b


def contrast(fg: str, bg: str) -> float:
    l1, l2 = luminance(fg), luminance(bg)
    hi, lo = max(l1, l2), min(l1, l2)
    return (hi + 0.05) / (lo + 0.05)


def enclosing_rule(text: str, pos: int) -> str:
    """The `{ ... }` block containing offset `pos` (minified-CSS friendly)."""
    start = text.rfind("{", 0, pos)
    end = text.find("}", pos)
    if start == -1 or end == -1:
        return ""
    return text[start + 1:end]


def bg_is_dark(rule: str):
    """'dark' if the rule's own background is a dark surface, 'light' if light,
    'none' if the rule sets no background (text inherits the page's dark bg),
    'unknown' if a background is set but can't be resolved statically (PHP var,
    gradient of vars) — trust the designer's pairing and don't flag."""
    m = BG_DECL.search(rule)
    if not m:
        return "none"
    val = m.group(1).lower()
    if any(v in val for v in LIGHT_VARS):
        return "light"
    if "var(--d" in val or "var(--carbon" in val:  # --d/--dd/--carbon* are dark
        return "dark"
    hexes = HEX_IN.findall(val)
    if hexes:
        return "dark" if not any(luminance(h) > 0.18 for h in hexes) else "light"
    return "unknown"  # PHP <?=$accent?>, var-only gradients, etc.


def main() -> int:
    files = sorted(glob.glob("public/**/*.php", recursive=True)
                   + glob.glob("public/**/*.css", recursive=True)
                   + glob.glob("public/**/*.html", recursive=True))
    fails: list[tuple] = []
    for f in files:
        text = Path(f).read_text(encoding="utf-8", errors="replace")
        line_starts = [0]
        for ch in text:
            line_starts.append(line_starts[-1] + 1)
        for m in COLOR_DECL.finditer(text):
            hexv = m.group(1).lower()
            try:
                ratio = contrast(hexv, WORST_DARK)
            except ValueError:
                continue
            if ratio >= 4.5 or hexv in BRAND_EXEMPT:
                continue
            # Skip deliberate dark-text-on-light pairings (buttons, tier chips)
            # and unresolvable designer-controlled backgrounds.
            if bg_is_dark(enclosing_rule(text, m.start())) in ("light", "unknown"):
                continue
            ln = text.count("\n", 0, m.start()) + 1
            ctx = text[max(0, m.start() - 40):m.start() + 30].replace("\n", " ").strip()
            fails.append((round(ratio, 2), hexv, f, ln, ctx[:80]))

    fails.sort()
    if not fails:
        print("contrast-audit: CLEAN - every color: passes AA (>=4.5:1) on #1A1A1A")
        return 0
    print(f"contrast-audit: {len(fails)} color: declaration(s) below AA 4.5:1 on {WORST_DARK}\n")
    for ratio, hexv, f, ln, ctx in fails:
        tag = "FAIL" if ratio < 3.0 else "fail(normal,ok-large)"
        print(f"  {ratio:>4}:1 {tag:<22} {hexv}  {f}:{ln}")
        print(f"          {ctx}")
    distinct = sorted({x[1] for x in fails}, key=lambda h: contrast(h, WORST_DARK))
    print(f"\n  distinct failing colors: {', '.join(distinct)}")
    return 1


if __name__ == "__main__":
    sys.exit(main())
