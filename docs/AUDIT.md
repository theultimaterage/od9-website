# OD9 website (offda9.com) — Gap Audit (2026-10-07)

Founder ask: "do you see any more flaws/gaps/holes and/or opportunities for
improvement/enhancement/optimization for the website?" Method: the session-start gate (the
repo's own docs, its deploy gates in a dry run that same afternoon), the fleet sentinel's last
sweep, then a read-only Playwright sweep of every page in the live sitemap (29 pages) at desktop
and phone width, with every same-site link (97) checked, plus header and asset reads. No review
agents: the questions were about the rendered site, answered by measuring. Every finding was
verified with a fetch, a render or a read. As of 2026-10-07 (afternoon CDT). First audit kept in
this repo; the February inventory is OD9_AUDIT_INVENTORY.md (stale, finding 7).

## Verdict

The site is healthy where it counts: no script errors on any page, every page has a title,
description, share image and one main heading, and all 97 same-site links resolve. Its weak
spot was phones: eight pages scrolled sideways, four by about 1,000px. That is fixed and gated
locally (5070bc8) and goes live on the founder's go. What remains is weight (the Music page loads
18 MB), missing security headers, and three credential backups sitting in the live dashboard
folder, each a small change with an obvious fix.

## Needs the founder

1. **Deploy the phone fix** (5070bc8): the deploy now refuses any page wider than a phone.
2. **Move the three config backups out of the webroot** (finding 2): a production change.
3. **Security headers** (finding 3): the safe four now; a content security policy later, page by page.
4. **Music page weight** (finding 4): compress the cover, stop the promo video autoloading.

## Findings and status

| # | Sev | Finding | Status |
|---|-----|---------|--------|
| 1 | P1 | 8 of 29 pages wider than a 390px phone: ecosystem, insights, progress, wake-up by ~1,034px (logo set to 60px tall, its width="1408" never reset); atlas 77px (timeline label nowrap); research 42px (status chips nowrap); music 39px | FIXED 5070bc8 (local; deploy on go) + gate tests/check_phone_overflow.py |
| 2 | P2 | Three `dashboard/includes/config.php.bak-*` files (2026-06-30, 09-14) in the live webroot, 7 secret-shaped lines each, mode 644. The root .htaccess answers 403 for them, so they are not served, but credentials in a webroot depend on one rule | OPEN (founder #2) |
| 3 | P2 | No security headers on any page: no Strict-Transport-Security, X-Content-Type-Options, X-Frame-Options / frame-ancestors, Referrer-Policy | OPEN (founder #3) |
| 4 | P2 | Music page loads 18 MB: an autoplaying 3.2 MB promo video, a 2,048x2,048 2.9 MB cover JPEG (prod-only file), several SoundCloud players | OPEN (founder #4) |
| 5 | P3 | Support (4.8 MB) and NCZ (4.6 MB) load YouTube's full player (~3.7 MB of script) before anyone presses play; a click-to-load preview cuts that to one thumbnail | OPEN |
| 6 | P3 | images/logos/od9-logo.png is 949 KB (1408x768) and is shown 60px tall on four pages and as the default share image | OPEN |
| 7 | P3 | AGENTS.md and OD9_AUDIT_INVENTORY.md describe the February layout (public/, private/admin) the August restructure removed; the sentinel also reports the inventory differs from prod. Agents reading them are misled | OPEN |
| 8 | P3 | deploy-coverage warns agent-card.php is deployed but nothing links to it (possible orphan) | OPEN |

Checked and fine, recorded so nobody re-derives them: 0 script errors on 29 pages; title,
description, og:image and exactly one h1 on all 29; 97 of 97 same-site links answer 200; the
sentinel's failed control probe (status 0) was transient, the path answers 404 now;
dashboard/includes/config.php executes and prints nothing (0 bytes); the docroot matches git
(drift gate clean, 1012 live / 954 tracked with every difference accounted for).

## Shipped this session (all pushed, each with its prover)

| Commit | What |
|--------|------|
| 3a60f3d | The sectioned nav (tests/test_nav_sections.php, a deploy gate) |
| b0d6d44 | Old nav files retired; merch.php on the shared head |
| 5070bc8 | No page wider than a phone, with tests/check_phone_overflow.py as a deploy gate (not deployed yet) |

## Cross-repo notes

- NCZ-Content-Pipelines: Threads switched on in the LAW 14 draw (e6bec54).
- The fleet sentinel (~/.claude/prod-sentinel) already reports finding 2 daily; nothing acts on it.

## How this was verified

- Live sweep: tools in the session scratchpad (od9_site_sweep.py), 29 pages, desktop 1440px and
  phone 390px, Playwright Chromium, against https://offda9.com through Cloudflare.
- Phone fix: tests/check_phone_overflow.py on local XAMPP, 29 pages, 0 overflow; its selftest
  passes; with ecosystem.php's fix removed it reported 1,034px, so it can fail.
- Backups: `ls` on the server (offda9 account) and a 403 from https://offda9.com for each path.
- Headers: `curl -I https://offda9.com/` returns only server and cf-cache-status headers.
