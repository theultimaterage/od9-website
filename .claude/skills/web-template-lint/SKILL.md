---
name: web-template-lint
description: Guardrail that fails when any offda9.com public page drifts from the shared chrome (includes/head.php + includes/nav.php + includes/footer.php + css/od9.css). Catches inline nav/footer CSS, hand-rolled <head>/<title>, non-canonical nav includes, nav-rendered-without-od9.css (the guide.php "huge logo" bug), and duplicate chrome files. This is the gate whose absence let the universal nav drift across 26/31 pages. Run before any web commit and inside deploy.
---

# web-template-lint

The "universal nav/header/footer" only stays universal if **every** page uses
the shared includes instead of carrying its own copy. This guardrail enforces
that. Run it:

```bash
python tools/web_template_lint.py            # scan public/, exit 1 on drift
python tools/web_template_lint.py --list-ok  # also list compliant pages
```

It reports a per-page worklist of violations:
- **INLINE_CHROME** — page carries its own `.od9-nav`/`.od9-footer` CSS (must live in `css/od9.css`)
- **HANDROLLED_HEAD** — page hand-rolls `<head>`/`<title>` instead of `includes/head.php`
- **BAD_NAV** — renders a nav but not via `includes/nav.php`
- **NAV_NO_CSS** — renders the nav but never loads `od9.css` (unstyled)
- **OWN_ENV** — reimplements env/base-path inline (use `includes/env.php`)
- **DUP_CHROME** — more than one `nav.php`/`head.php`/`footer.php` exists
- **UNSTYLED** — page uses ≥3 content classes (e.g. `.settings-card`, `.toggle-switch`) defined in **no** stylesheet it loads. This is the class of bug where a migration strips a page's content CSS and `od9.css` never replaces it, leaving the content unstyled (the 2026-06-06 settings.php regression). Put page content styles in a linked stylesheet (`css/dashboard.css` for member-area pages, or a per-page `css/<slug>.css`) — never rely on classes with no CSS home. The threshold (`UNSTYLED_MIN`) keeps it to substantially-unstyled pages, not 1-2 dynamic/straggler classes.

To scaffold a NEW page already wired correctly (so it never trips these), use the **`new-page`** skill.

**Wire it in** (so drift cannot ship): a pre-commit hook, and as a gate at the
top of `tools/deploy.py`. Exit 0 = clean, 1 = drift found.
