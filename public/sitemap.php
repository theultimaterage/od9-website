<?php
/**
 * OD9 dynamic sitemap - generated from the filesystem, self-maintaining.
 *
 * Lists every page in this directory that DECLARES ITSELF CANONICAL (contains
 * <link rel="canonical" href="<its own URL>">). Real public pages
 * self-canonicalize; debug/handler/admin scripts, redirect stubs, and gated
 * utilities don't - so they're auto-excluded with no allowlist to maintain.
 * lastmod = file mtime. Served at /sitemap.xml via the .htaccess rewrite.
 *
 * 2026-06-05: switched from glob+denylist to the self-canonical test after a
 * deploy revealed prod's docroot also holds check_db.php / check-admin.php /
 * _mintest.php / contact-handler.php etc. A denylist can't know about files
 * that only exist on prod; the canonical test does the right thing for any
 * file, present or future.
 */
declare(strict_types=1);

header('Content-Type: application/xml; charset=utf-8');

const BASE = 'https://offda9.com';

// Belt-and-suspenders: hide anything that DOES self-canonicalize but still
// shouldn't be advertised. (Empty today - the canonical test handles the rest.)
$deny = ['sitemap.php'];

$priority = [
    'index.php'     => '1.0',
    'framework.php' => '0.9',
    'ecosystem.php' => '0.9',
    'support.php'   => '0.9',
    'tiers.php'     => '0.8',
    'music.php'     => '0.8',
    'ncz.php'       => '0.8',
    'library.php'   => '0.7',
    'da-crew.php'   => '0.7',
    'founders.php'  => '0.7',
    'progress.php'  => '0.7',
];

$files = glob(__DIR__ . '/*.php') ?: [];
sort($files);

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($files as $file) {
    $name = basename($file);
    if (in_array($name, $deny, true)) {
        continue;
    }
    $loc = ($name === 'index.php') ? BASE . '/' : BASE . '/' . $name;

    // A page is canonical-for-itself if it EITHER carries a literal
    // <link rel="canonical"> (legacy/static pages) OR includes head.php, which
    // emits the canonical from $page_slug at runtime (the shared-chrome pages).
    // The old static-source test only saw literal tags, so after the 2026-06
    // head.php migration it collapsed the sitemap to a single page. Skip
    // noindex pages either way.
    $head = @file_get_contents($file, false, null, 0, 8192);
    if ($head === false || stripos($head, 'noindex') !== false) {
        continue;
    }
    if (preg_match('~<link\s+rel=["\']canonical["\']\s+href=["\']([^"\']+)["\']~i', $head, $m)) {
        $canonical = $m[1];                                  // literal canonical
    } elseif (strpos($head, 'includes/head.php') !== false) {
        $slug = preg_match('~\$page_slug\s*=\s*[\'"]([^\'"]*)[\'"]~', $head, $sm) ? $sm[1] : $name;
        $canonical = ($slug === '' || $slug === 'index.php') ? BASE . '/' : BASE . '/' . ltrim($slug, '/');
    } else {
        continue;                                            // not a self-canonical page
    }
    if (rtrim($canonical, '/') !== rtrim($loc, '/')) {
        continue;                                            // canonicalizes elsewhere (merged/redirect)
    }

    $lastmod = date('Y-m-d', (int) filemtime($file));
    $prio    = $priority[$name] ?? '0.6';
    echo "  <url>\n";
    echo '    <loc>' . htmlspecialchars($loc, ENT_XML1) . "</loc>\n";
    echo "    <lastmod>{$lastmod}</lastmod>\n";
    echo "    <priority>{$prio}</priority>\n";
    echo "  </url>\n";
}
echo '</urlset>' . "\n";
