---
name: new-page
description: Scaffold a new offda9.com page already wired to the shared chrome (includes/head.php + nav.php + footer.php + css/od9.css) with its content styles in a linked stylesheet — never inline chrome, never content classes with no CSS home. Prevents the drift classes web-template-lint catches (INLINE_CHROME, HANDROLLED_HEAD, NAV_NO_CSS, UNSTYLED) at creation time. Use when adding any new public PHP page under public/.
---

# new-page

Creating an OD9 page by hand keeps re-introducing the same rendering bugs:
inline chrome CSS, a hand-rolled `<head>`, a nav with no `od9.css`, or content
classes that live in no stylesheet (the unstyled-settings.php bug). This skill
scaffolds a page that is correct by construction, then proves it with the lint.

## Steps

1. **Gather inputs** (ask the user if not given):
   - `slug` — file name without `.php` (e.g. `roadmap`)
   - `title` / `description` — for `<title>` + meta (head.php emits these)
   - `area` — `standalone` (lives at `public/<slug>.php`) or `dashboard`
     (lives at `public/dashboard/<slug>.php`, member-area styling)
   - whether it needs **page-specific styles** (custom layout/components)

2. **Create the PHP file** from the matching template below. Rules that are
   NON-NEGOTIABLE (they are exactly what web-template-lint enforces):
   - Use `includes/head.php` for `<head>` — never hand-roll `<title>`/`<meta>`.
   - Use `includes/nav.php` for the nav and `includes/footer.php` for the footer
     — never inline `.od9-nav`/`.od9-footer` CSS or copy the markup.
   - Use `includes/env.php` for any env/base-path logic — never inline
     `strpos(__DIR__,'xampp')`.
   - **Content styles go in a LINKED stylesheet, not inline chrome.** Member-area
     pages link `/css/dashboard.css`; other pages that need custom styling get a
     new `public/css/<slug>.css` linked in `<head>`. Every content class the page
     uses MUST be defined in a stylesheet it loads.

3. **If the page needs custom styles**, create `public/css/<slug>.css` (or extend
   `css/dashboard.css` for member-area pages) with the page's content classes.
   Do NOT put `.od9-nav`/footer/chrome rules there — those live in `od9.css`.

4. **Lint it**: run `python tools/web_template_lint.py`. It MUST report
   `CLEAN ✓`. Fix any flagged drift before finishing. (Render the page too if it
   has logic — `php -l` does not catch a stray `?>` in a comment that leaks
   source; only rendering does.)

5. If the page is public-facing content, remember it is deployed via
   `tools/deploy.py --files <slug>.php` (and the stylesheet if new). `config.php`
   / `db.php` are `never_deploy`.

## Template — standalone page (`public/<slug>.php`)

```php
<?php
$page_title       = '<TITLE> - OD9';
$page_description = '<DESCRIPTION>';
$page_slug        = '<slug>.php';
require_once __DIR__ . '/includes/env.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include __DIR__ . '/includes/head.php'; ?>
<?php // Only if this page has custom styles — create public/css/<slug>.css: ?>
<!-- <link rel="stylesheet" href="/css/<slug>.css"> -->
</head>
<body>
<?php include __DIR__ . '/includes/nav.php'; ?>

<main class="<slug>-container">
    <!-- page content; classes here must be defined in od9.css or css/<slug>.css -->
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
```

## Template — dashboard / member-area page (`public/dashboard/<slug>.php`)

```php
<?php
$page_title       = '<TITLE> - OD9 Dashboard';
$page_description = '<DESCRIPTION>';
$page_slug        = '<slug>.php';
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/../includes/env.php';
// ... page logic / data here ...
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php $nav_base = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/dashboard/<slug>.php')), '/\\'); include __DIR__ . '/../includes/head.php'; ?>
<link rel="stylesheet" href="/css/dashboard.css">
</head>
<body>
<?php
$nav_base = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/dashboard/<slug>.php')), '/\\');
include __DIR__ . '/../includes/nav.php';
?>

<div class="<slug>-container">
    <!-- content; member-area component classes live in css/dashboard.css -->
</div>
</body>
</html>
```

Member-area content classes (`.settings-card`, `.stat-card`, `.toggle-switch`,
`.activity-list`, etc.) already exist in `css/dashboard.css` — reuse them, and
add any new ones THERE, not inline.
