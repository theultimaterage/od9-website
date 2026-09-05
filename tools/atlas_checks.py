#!/usr/bin/env python3
"""atlas_checks — the Atlas's behaviour, exercised in a real (headless) browser.

Every spectacle pass shipped with a throwaway Playwright script in a scratch
directory; this is those scripts, kept, so the next change to atlas.js cannot
break the arrival, find, sound, the tour, the live strip, the timeline, the
sections or the guides without somebody noticing BEFORE the deploy.
tools/prod-deploy.py runs it as a gate when the working tree touches an Atlas
file (--skip-atlas-checks bypasses; say why in the commit).

    python tools/atlas_checks.py                          # against http://localhost/od9/atlas
    python tools/atlas_checks.py --base https://offda9.com/atlas   # post-deploy, live

Needs Playwright + Chromium: the bot repo's venv has them
(C:/Users/Rage/IdeaProjects/OD9-Discord-Bot/venv/Scripts/python.exe).
Exit 0 = every check passed; 1 = a failure, listed; 2 = could not run.
"""
from __future__ import annotations

import argparse
import datetime
import json
import sys

try:
    from playwright.sync_api import sync_playwright
except ImportError:  # pragma: no cover
    print("atlas_checks: playwright is not importable in this interpreter — run with the bot venv's python")
    sys.exit(2)

UA = ("Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) "
      "Chrome/128.0.0.0 Safari/537.36")
FAILS: list[str] = []
LINES = "(sel) => Array.from(document.querySelectorAll(sel)).map(l => l.innerText.split(String.fromCharCode(10)).join(' | '))"


def check(cond: bool, what: str) -> None:
    print(("  ok   " if cond else "  FAIL ") + what)
    if not cond:
        FAILS.append(what)


def page(b, w=1440, h=900, errors=None, audio=False):
    args = ["--autoplay-policy=no-user-gesture-required"] if audio else []
    ctx = b.new_context(viewport={"width": w, "height": h}, user_agent=UA)
    pg = ctx.new_page()
    if errors is not None:
        pg.on("pageerror", lambda e: errors.append(str(e)[:200]))
    return ctx, pg


def run(base: str) -> int:
    errors: list[str] = []
    with sync_playwright() as p:
        b = p.chromium.launch(headless=True, args=["--autoplay-policy=no-user-gesture-required"])

        # ---- pass 1: arrival, find, sound, Beyond, phone ----------------------
        print("pass 1")
        ctx, pg = page(b, errors=errors)
        pg.goto(base + "?arrival=1", wait_until="load", timeout=60000)
        pg.wait_for_timeout(3000)
        check(pg.evaluate("() => document.getElementById('atlas-arrival-hint').className.includes('on')"), "arrival is running at 3 s (hint shown)")
        pg.wait_for_timeout(4000)
        check(not pg.evaluate("() => document.getElementById('atlas-arrival-hint').className.includes('on')"), "arrival finished by 7 s")
        pg.click("#atlas-find")
        pg.keyboard.type("crisis")
        pg.wait_for_timeout(300)
        hits = pg.evaluate(LINES, "#atlas-find-results li")
        check(any("System In Crisis" in x for x in hits), "find lists chapter 5 for 'crisis'")
        pg.keyboard.press("Enter")
        pg.wait_for_timeout(1500)
        check(pg.evaluate("() => location.hash") == "#ch5", "Enter flies to #ch5")
        check("Black hole star" in pg.evaluate("() => document.getElementById('atlas-card').innerText"), "the card carries the object")
        pg.keyboard.press("Escape")
        pg.click("#atlas-sound")
        pg.wait_for_timeout(800)
        check(pg.evaluate("() => window.AtlasSound && window.AtlasSound.enabled()"), "sound toggle starts audio")
        pg.click("#atlas-sound")
        pg.goto(base + "#boltzmann-brain", wait_until="load")
        pg.wait_for_timeout(2000)
        check("Boltzmann" in pg.evaluate("() => document.getElementById('atlas-card').innerText"), "Beyond deep link by sprite key")
        ctx.close()
        ctx, pg = page(b, 390, 844, errors=errors)
        pg.goto(base + "?arrival=0", wait_until="load")
        pg.wait_for_timeout(2000)
        top = pg.evaluate("() => Math.round(document.getElementById('atlas-stage').getBoundingClientRect().top)")
        check(top < 300, f"phone: the stage starts above the fold (top {top}px)")
        check(pg.evaluate("() => document.querySelectorAll('.atlas-guide').length") == 4, "phone: four guide medallions")
        ctx.close()

        # ---- the tour --------------------------------------------------------
        print("tour")
        ctx, pg = page(b, errors=errors)
        pg.goto(base + "?tour=arc2", wait_until="load")
        pg.wait_for_timeout(3200)
        cap = pg.evaluate(LINES, "#atlas-tour-cap")[0]
        check(cap.startswith("ARC 2") and "1/4" in cap, "tour deep link lands on stop 1 with a caption")
        pg.keyboard.press("ArrowRight")
        pg.wait_for_timeout(2600)
        check("2/4" in pg.evaluate(LINES, "#atlas-tour-cap")[0], "ArrowRight advances")
        pg.keyboard.press(" ")
        pg.wait_for_timeout(300)
        check(pg.evaluate("() => document.getElementById('atlas-tour-pause').getAttribute('aria-label')") == "Resume", "space pauses")
        pg.keyboard.press("Escape")
        pg.wait_for_timeout(400)
        check(pg.evaluate("() => document.getElementById('atlas-tour-cap').className") == "", "Escape leaves")
        pg.click("#atlas-tour")
        pg.wait_for_timeout(300)
        check(len(pg.evaluate(LINES, "#atlas-tour-menu li")) == 5, "the menu lists four routes + all")
        ctx.close()

        # ---- live as an event (mocked endpoint) -------------------------------
        print("live strip")
        today = datetime.date.today().isoformat()
        for state, want in (("live", "on live"), ("tonight", "on tonight"), ("quiet", "")):
            payload = {"designated": 84,
                       "door": {"open": state == "live", "reason": "stream_start" if state == "live" else "closed",
                                "video_id": None, "watch_url": "https://youtube.com/@x" if state == "live" else None,
                                "title": "OD9 is live", "opened_at": None, "next_show_utc": None, "next_show_ct": "Sunday · 4:00 PM CT"},
                       "show": None if state == "quiet" else {"day": today, "headline": "H", "pin": "P?", "tags": [], "receipt": ""},
                       "last_live": None}
            ctx, pg = page(b, errors=errors)
            pg.route("**/api/v1/atlas-live.php", lambda route, request=None, body=json.dumps(payload): route.fulfill(
                status=200, content_type="application/json", body=body))
            pg.goto(base + "?arrival=0", wait_until="load")
            pg.wait_for_timeout(1500)
            check(pg.evaluate("() => document.getElementById('atlas-live-strip').className") == want, f"live strip state '{state}' -> '{want}'")
            ctx.close()

        # ---- the timeline ----------------------------------------------------
        print("timeline")
        ctx, pg = page(b, errors=errors)
        pg.goto(base + "?arrival=0", wait_until="load")
        pg.wait_for_timeout(1800)
        check(pg.evaluate("() => !document.getElementById('atlas-timeline').hasAttribute('hidden')"), "timeline bar is shown")
        pg.evaluate("(v) => { const r = document.getElementById('atlas-tl-range'); r.value = String(v); r.dispatchEvent(new Event('input', {bubbles: true})); }", 4)
        pg.wait_for_timeout(200)
        counts = pg.evaluate("() => window.__atlas.stateCounts()")
        check(counts["canon"] == 4, f"as of day 4 (2026-06-24): 4 canon (got {counts['canon']})")
        pg.click("#atlas-tl-now")
        pg.wait_for_timeout(200)
        check(pg.evaluate("() => window.__atlas.stateCounts()")["canon"] >= 14, "now restores the canon count")
        ctx.close()

        # ---- sections --------------------------------------------------------
        print("sections")
        ctx, pg = page(b, errors=errors)
        pg.goto(base + "?arrival=0#ch5", wait_until="load")
        pg.wait_for_timeout(2500)
        secs = pg.evaluate(LINES, "#atlas-card .atlas-sections li")
        check(len(secs) == 9, f"chapter 5 lists 9 sections (got {len(secs)})")
        pg.click("#atlas-find")
        pg.keyboard.type("great filter")
        pg.wait_for_timeout(300)
        check(any(x.startswith("5.9") for x in pg.evaluate(LINES, "#atlas-find-results li")), "find matches a section title (5.9)")
        ctx.close()

        # ---- the guides ------------------------------------------------------
        print("guides")
        ctx, pg = page(b, errors=errors, audio=True)
        pg.goto(base + "?arrival=0", wait_until="load")
        pg.wait_for_timeout(1800)
        pg.click(".atlas-guide[data-guide=forgemaster]")
        pg.wait_for_timeout(1500)
        check(pg.evaluate("() => location.hash").startswith("#ch"), "the Forgemaster opens the forge chapter")
        say = pg.evaluate("() => document.getElementById('atlas-guide-say').innerText")
        check("anvil" in say, "the Forgemaster speaks in voice")
        pg.click(".atlas-guide[data-guide=navigator]")
        pg.wait_for_timeout(400)
        check(pg.evaluate("() => document.getElementById('atlas-tour-menu').className") == "open", "the Navigator opens the tour menu")
        ctx.close()
        b.close()

    check(not errors, "no page errors anywhere" + (f": {errors[:2]}" if errors else ""))
    print(("ALL PASSED" if not FAILS else f"{len(FAILS)} FAILED: " + "; ".join(FAILS)))
    return 0 if not FAILS else 1


if __name__ == "__main__":
    ap = argparse.ArgumentParser(description=__doc__.split("\n\n")[0])
    ap.add_argument("--base", default="http://localhost/od9/atlas")
    a = ap.parse_args()
    sys.exit(run(a.base))
