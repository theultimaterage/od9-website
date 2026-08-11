<?php
/**
 * OD9 universal <head> partial (task #12, 2026-06-05).
 *
 * Set $page_* vars before including; everything else (charset, fonts, shared
 * CSS, favicon, preconnects) is constant. Centralizes canonical + Open Graph
 * so per-page meta can't drift — the root cause of the SEO drift we fixed.
 * Output goes INSIDE <head>; the page still provides <!doctype>/<html>/<head>
 * and may add a page-specific <style> AFTER this include (it wins on conflicts).
 *
 *   $page_title       = 'Support OD9 | Fund the Movement';
 *   $page_description = '...';
 *   $page_slug        = 'support.php';     // '' or 'index.php' => canonical .../
 *   $page_og_image    = '/images/...';     // optional (defaults to the logo)
 *   $page_robots      = 'noindex';         // optional (defaults index,follow)
 *   include __DIR__ . '/head.php';
 */
$_base      = 'https://offda9.com';
$_slug      = $page_slug ?? '';
$_canonical = ($_slug === '' || $_slug === 'index.php') ? $_base . '/' : $_base . '/' . ltrim($_slug, '/');
$_title     = $page_title ?? 'OD9 - Off Da Nine';
$_desc      = $page_description ?? '';
$_robots    = $page_robots ?? 'index, follow';
$_ogimg     = $page_og_image ?? '/images/logos/od9-logo.png';
if (strncmp($_ogimg, 'http', 4) !== 0) { $_ogimg = $_base . $_ogimg; }
// Base path so local XAMPP (site under /od9/public/) and prod (site at /)
// both resolve same-origin assets. Mirrors the helper in includes/nav.php.
$_bp = $nav_base ?? rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'), '/\\');
$h = static fn(string $s): string => htmlspecialchars($s, ENT_QUOTES);
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $h($_title) ?></title>
<meta name="description" content="<?= $h($_desc) ?>">
<meta name="robots" content="<?= $h($_robots) ?>">
<link rel="canonical" href="<?= $h($_canonical) ?>">
<meta property="og:type" content="website">
<meta property="og:url" content="<?= $h($_canonical) ?>">
<meta property="og:title" content="<?= $h($page_og_title ?? $_title) ?>">
<meta property="og:description" content="<?= $h($page_og_description ?? $_desc) ?>">
<meta property="og:image" content="<?= $h($_ogimg) ?>">
<meta property="og:site_name" content="OD9 - Off Da Nine">
<meta name="twitter:card" content="summary_large_image">
<meta name="theme-color" content="#00BFFF">
<link rel="icon" type="image/png" href="<?= $_bp ?>/images/logos/od9-logo.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@400;600;700&family=Exo+2:wght@400;600&display=swap" rel="stylesheet">
<!-- Self-hosted Font Awesome subset (only the ~85 icons the site uses, ~14 KB
     vs ~342 KB from the cdnjs kit). Regenerate via tools/build_fa_subset.py. -->
<link rel="stylesheet" href="<?= $_bp ?>/css/od9-fa.css">
<link rel="stylesheet" href="<?= $_bp ?>/css/od9.css?v=<?= @filemtime(__DIR__ . '/../css/od9.css') ?: '1' ?>">
<?php include __DIR__ . '/seo_schema.php'; // Organization + WebSite JSON-LD (after page meta) ?>
