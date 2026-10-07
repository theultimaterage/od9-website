"""No page on the site may scroll sideways on a phone. A pre-deploy gate.

    python tests/check_phone_overflow.py                 # every page in the LOCAL sitemap
    python tests/check_phone_overflow.py --selftest      # prove it can find a wide page

WHY. The 2026-10-07 audit found eight live pages wider than a 390px phone, four of
them by about 1,000px (a 60px-tall logo whose width attribute said 1408 and whose
CSS never reset the width). Nothing caught it: every other gate reads source, and
this only shows in a rendered page. The page list comes from the site's own
sitemap (sitemap.php), so a new page is covered without editing this file.

Runs on Windows Python with Playwright, against local XAMPP (the deploy's
local_base_url). Exit 0 clean, 1 a page overflows, 2 it could not check.
"""
import re
import sys
import urllib.request

BASE = "http://localhost/od9"
WIDTH, TOLERANCE = 390, 2


def overflow(page, url: str) -> int:
    page.goto(url, wait_until="load", timeout=60000)
    return page.evaluate("document.documentElement.scrollWidth") - WIDTH


def pages() -> list:
    xml = urllib.request.urlopen(BASE + "/sitemap.xml", timeout=30).read().decode("utf-8", "replace")
    paths = [re.sub(r"^https?://[^/]+", "", u) for u in re.findall(r"<loc>([^<]+)</loc>", xml)]
    return [BASE + (p or "/") for p in paths]


def main() -> int:
    try:
        from playwright.sync_api import sync_playwright
    except ImportError:
        print("phone-overflow: playwright is not installed; cannot check")
        return 2
    selftest = "--selftest" in sys.argv
    with sync_playwright() as p:
        browser = p.chromium.launch()
        page = browser.new_page(viewport={"width": WIDTH, "height": 844}, is_mobile=True, has_touch=True)
        if selftest:
            wide = overflow(page, "data:text/html,<meta name=viewport content='width=device-width'><div style='width:1400px'>x</div>")
            fits = overflow(page, "data:text/html,<meta name=viewport content='width=device-width'><p>fits</p>")
            browser.close()
            ok = wide > TOLERANCE and fits <= TOLERANCE
            print(f"SELFTEST {'OK' if ok else 'FAILED'}: a 1400px page overflows by {wide}px, a plain page by {fits}px")
            return 0 if ok else 1
        try:
            urls = pages()
        except Exception as e:
            print(f"phone-overflow: cannot read {BASE}/sitemap.xml ({type(e).__name__}); is local Apache up?")
            return 2
        if len(urls) < 10:
            print(f"phone-overflow: the sitemap lists only {len(urls)} pages; refusing to call that a check")
            return 2
        bad = []
        for url in urls:
            px = overflow(page, url)
            if px > TOLERANCE:
                bad.append((url, px))
        browser.close()
    for url, px in bad:
        print(f"  WIDER THAN A PHONE by {px}px: {url}")
    print(f"phone-overflow: {len(urls)} pages at {WIDTH}px, {len(bad)} overflow")
    return 1 if bad else 0


if __name__ == "__main__":
    sys.exit(main())
