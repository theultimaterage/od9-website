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

Hermetic: every page here swallows the beacon (api/v1/atlas-ping.php -> 204
in the browser), so a check run never writes a line to the live event log —
the first live run did, and the day's numbers began with twelve synthetic
sessions. Against an https base one un-routed POST (sid "probecheck",
props.probe = true, which tools/atlas_stats.py ignores) proves the real
endpoint answers 204 through Cloudflare.
Exit 0 = every check passed; 1 = a failure, listed; 2 = could not run.
"""
from __future__ import annotations

import argparse
import datetime
import json
import re
import sys
import zoneinfo

# Windows consoles default to cp1252; the checks print the page's own glyphs (the chip's diamond)
# and the deploy gate runs this without PYTHONIOENCODING — one crash on print aborted a deploy (2026-09-06).
sys.stdout.reconfigure(encoding="utf-8", errors="replace")

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
    # hermetic: the beacon never reaches the live log from a check (the beacon
    # section registers its own capturing route on top of this one; later wins)
    pg.route("**/api/v1/atlas-ping.php", lambda route, request=None: route.fulfill(status=204))
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
        # the beyond field's constellation: the data has to REACH the page, and
        # the brain has to stay off it — it is an accident, not something built
        fig = pg.evaluate("() => (JSON.parse(document.getElementById('atlas-objects').textContent).beyondFigure || [])")
        check(len(fig) >= 5, f"the beyond field ships a constellation figure ({len(fig)} segment(s))")
        check(all("beyond-boltzmann" not in seg for seg in fig), "the Boltzmann brain is joined to nothing")
        check(any(seg[0] == "beyond-mckendree" for seg in fig), "the figure starts where ch67's trail lands")
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
        check(len(pg.evaluate(LINES, "#atlas-tour-menu li[role=option]")) == 5, "the menu lists four routes + all")
        check("guided flight" in pg.evaluate(LINES, "#atlas-tour-menu li.lead")[0], "the menu says what a tour is")
        ctx.close()

        # ---- /tour, the short link an end card is read off a screen ----------
        print("tour short link")
        site = base.rsplit("/atlas", 1)[0]
        ctx, pg = page(b, errors=errors)
        pg.goto(site + "/tour", wait_until="load")
        pg.wait_for_timeout(3200)
        # rstrip the hash: the Atlas writes an empty "#" of its own once it routes
        landed = pg.url.rstrip("#")
        check(landed.endswith("/atlas?tour=arc1"), f"/tour lands on the Atlas running arc 1 (got {landed})")
        # ...[0] only when there is one. When the redirect is what broke, this page is a
        # 404 with no caption at all, and an IndexError here would abort the run and take
        # every check after it down with the one that already reported the real fault.
        caps = pg.evaluate(LINES, "#atlas-tour-cap")
        cap = caps[0] if caps else ""
        check(cap.startswith("ARC 1") and "1/6" in cap, "the flight is already under way on arrival")
        ctx.close()

        # A printed link outlives an arc rename. That must degrade to the ordinary
        # opening, not to a still map — the arrival guard reads tour STATE for
        # exactly this case, and reading the query string instead looked identical
        # until the day the spec named nothing.
        ctx, pg = page(b, errors=errors)
        pg.goto(base + "?tour=nosucharc", wait_until="load")
        pg.wait_for_timeout(3000)
        check(pg.evaluate("() => document.getElementById('atlas-tour-cap').className") == "",
              "an arc that no longer exists starts no tour")
        check(pg.evaluate("() => document.getElementById('atlas-arrival-hint').className.includes('on')")
              or pg.evaluate("() => !!window.sessionStorage.getItem('atlas.arrived')"),
              "and the visitor gets the arrival instead of a dead map")
        ctx.close()


        # ---- live as an event (mocked endpoint) -------------------------------
        print("live strip")
        for state, want in (("live", "on live"), ("tonight", "on tonight"), ("quiet", "")):
            # the page decides "tonight" on Chicago's calendar; take the date fresh per state, in that zone
            # (a run that started at 23:59 once built the fixture on yesterday's date and failed at 00:00)
            today = datetime.datetime.now(zoneinfo.ZoneInfo("America/Chicago")).date().isoformat()
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
        check("Replay history" in pg.evaluate("() => document.getElementById('atlas-tl-play').textContent"), "the replay button says what it does")
        check("reforged" in pg.evaluate("() => document.querySelector('.atlas-legend [title*=forge]').title"), "the forge chip defines the forge")
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

        # ---- your constellation (mocked endpoint) -----------------------------
        print("constellation")
        for who, fx, want_chip, want_card in (
                ("member", {"signed_in": True, "name": "T", "member": True, "tier": "observer", "credits": 10,
                            "read": [{"id": "ch5", "at": "2026-07-02"}, {"id": "ch1", "at": "2026-07-01"}],
                            "read_total": 2, "canon_total": 14}, "2 OF 14", "In your constellation"),
                ("visitor", {"signed_in": False, "login": "/dashboard/auth/discord.php?return=/atlas"}, "SIGN IN", "Sign in with Discord")):
            ctx, pg = page(b, errors=errors)
            pg.route("**/api/v1/atlas-me.php", lambda route, request=None, body=json.dumps(fx): route.fulfill(
                status=200, content_type="application/json", body=body))
            pg.goto(base + "?arrival=0#ch5", wait_until="load")
            pg.wait_for_timeout(2200)
            chip = pg.evaluate("() => document.getElementById('atlas-me-chip').textContent")
            check(want_chip in chip, f"{who}: the chip reads '{chip.strip()}'")
            check(want_card in pg.evaluate("() => document.getElementById('atlas-card').innerText"), f"{who}: the chapter card says '{want_card}'")
            if who == "visitor":
                check("discord.php" in pg.evaluate("() => document.getElementById('atlas-me-chip').getAttribute('href')"), "visitor: the chip is the sign-in door")
            else:
                check(pg.evaluate("() => window.__atlas.me().read_total") == 2, "member: the page holds the constellation")
            ctx.close()

        # ---- section anchors: the third zoom level is a door -----------------
        print("section anchors")
        ctx, pg = page(b, errors=errors)
        pg.goto(base + "?arrival=0#ch5", wait_until="load")
        pg.wait_for_timeout(2000)
        pg.click("#atlas-find")
        pg.keyboard.type("media and information")
        pg.wait_for_timeout(400)
        pg.keyboard.press("Enter")
        pg.wait_for_timeout(1800)
        href = pg.evaluate("() => { const a = document.querySelector('#atlas-card [data-lesson]'); return a ? a.getAttribute('href') : ''; }")
        check(href.endswith("#sec-6"), f"a lit section links at its anchor (href ends {href[-10:]!r})")
        pg.click("#atlas-card [data-lesson]")
        pg.wait_for_timeout(2000)
        src = pg.evaluate("() => document.getElementById('atlas-reader-frame').src")
        check("embed=1" in src and src.endswith("#sec-6"), "the reader opens at the section, embed query first")
        # the canon URL is absolute to production, so the iframe is NOT this base.
        # Ask THIS origin for the lesson instead, or the check would only ever
        # describe prod and would pass before the anchors were deployed.
        lesson = base.rsplit("/atlas", 1)[0] + "/curriculum/observer/the-system-in-crisis.php?embed=1&host=atlas"
        body = pg.request.get(lesson).text()
        m = re.search(r'id="sec-6">(.{0,60})', body)
        check(bool(m), "the lesson on this origin carries the anchor")
        check(bool(m) and "Information failure" in m.group(1), "the anchor is the passage that quotes that section")
        check(pg.evaluate("""() => { const a = document.querySelector('#atlas-card a[href*="discord.php"]'); return !a || !a.hasAttribute('data-lesson'); }"""),
              "the sign-in door is not treated as a lesson (it cannot render in the reader)")
        ctx.close()

        # ---- keyboard travel -------------------------------------------------
        print("keyboard")
        ctx, pg = page(b, errors=errors)
        pg.goto(base + "?arrival=0#ch5", wait_until="load")
        pg.wait_for_timeout(2000)
        check(pg.evaluate("() => document.getElementById('atlas-canvas').getAttribute('tabindex')") == "0", "the map can be reached by tab")
        pg.focus("#atlas-canvas")
        pg.keyboard.press("ArrowRight")
        pg.wait_for_timeout(900)
        moved = pg.evaluate("() => location.hash")
        check(moved not in ("", "#ch5"), f"ArrowRight travels off chapter 5 (to {moved or 'nowhere'})")
        # textContent, not innerText: the key line is uppercased by CSS, and the
        # check is about the words being there, not how they are cased on screen
        chip = pg.evaluate("() => document.getElementById('atlas-kb').textContent")
        check("Enter opens" in chip and "arrows travel" in chip, "the cursor chip names the keys")
        check(pg.evaluate("() => document.getElementById('atlas-kb').className") == "on", "the cursor chip is shown")
        pg.keyboard.press("Enter")
        pg.wait_for_timeout(700)
        check(pg.evaluate("() => document.getElementById('atlas-card').className").find("open") >= 0, "Enter opens the chapter")
        pg.keyboard.press("ArrowDown")
        pg.wait_for_timeout(1100)
        after = pg.evaluate("""() => ({hash: location.hash, card: document.getElementById('atlas-card').innerText.slice(0, 400),
                                       chip: document.getElementById('atlas-kb').textContent})""")
        title = after["chip"].split("Enter opens")[0].split(after["chip"][:2])[-1]
        check(after["card"].count("CHAPTER") + after["card"].count("PREFACE") > 0 and title[-12:].strip() in after["card"],
              "an open card follows the cursor (%s)" % title.strip()[:40])
        pg.keyboard.press("Escape")
        pg.wait_for_timeout(400)
        check(pg.evaluate("() => document.getElementById('atlas-kb').className") == "", "Escape puts the cursor away")
        ctx.close()

        # ---- pinch release (the beacon found this one in the wild) ------------
        print("pinch")
        ctx, pg = page(b, 390, 844, errors=errors)
        pg.goto(base + "?arrival=0", wait_until="load")
        pg.wait_for_timeout(1500)
        perr: list = []
        pg.on("pageerror", lambda e: perr.append(str(e)[:160]))
        pg.evaluate("""() => {
          const c = document.querySelector("#atlas-stage canvas");
          const ev = (t, id, x, y) => c.dispatchEvent(new PointerEvent(t, {pointerId: id, clientX: x, clientY: y, bubbles: true, pointerType: "touch"}));
          c.setPointerCapture = () => {};                 /* synthetic ids cannot be captured */
          ev("pointerdown", 1, 100, 300);
          ev("pointerdown", 2, 200, 400);                 /* pinch begins */
          ev("pointermove", 2, 210, 410);
          ev("pointerup",   1, 100, 300);                 /* one finger leaves, one stays */
          for (let i = 0; i < 6; i++) ev("pointermove", 2, 220 + i * 8, 420 + i * 4);
          ev("pointerup",   2, 260, 440);
        }""")
        pg.wait_for_timeout(400)
        check(not perr, "pinch: releasing one finger of a pinch does not throw" + (f" ({perr[0]})" if perr else ""))
        ctx.close()

        # ---- the beacon ------------------------------------------------------
        print("beacon")
        sent: list = []
        ctx, pg = page(b, errors=errors)
        pg.route("**/api/v1/atlas-ping.php", lambda route, request=None: (sent.append(json.loads(route.request.post_data or "{}")), route.fulfill(status=204)))
        pg.goto(base + "?arrival=0#ch5", wait_until="load")
        pg.wait_for_timeout(1800)
        pg.click("#atlas-sound")
        pg.wait_for_timeout(600)
        names = [e.get("event") for e in sent]
        check("arrive" in names and any(e.get("event") == "arrive" and e["props"].get("from") == "hash" for e in sent), "beacon: arrive(from=hash) on a deep link")
        check("card" in names, "beacon: card on the chapter card")
        check("sound" in names, "beacon: sound on the toggle")
        check(all(len(e.get("sid", "")) >= 8 for e in sent), "beacon: every event carries the session id")
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

        # ---- the real endpoint (live only) ------------------------------------
        if base.startswith("https://"):
            print("probe")
            ctx, pg = page(b, errors=errors)
            pg.goto(base + "?arrival=0", wait_until="load")
            pg.wait_for_timeout(800)
            pg.unroute("**/api/v1/atlas-ping.php")          # this one request goes to the real endpoint
            st = pg.evaluate("""() => fetch("api/v1/atlas-ping.php", {method: "POST", headers: {"Content-Type": "application/json"},
                body: JSON.stringify({sid: "probecheck", event: "arrive", vp: "desktop", props: {probe: true, from: "direct"}})}).then(r => r.status)""")
            check(st == 204, f"probe: the live endpoint accepts a real POST through Cloudflare (got {st})")
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
