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

    // Include only pages that point their canonical at THEMSELVES. Read just
    // the head (canonical lives in <head>) to keep this cheap.
    $head = @file_get_contents($file, false, null, 0, 8192);
    if ($head === false
        || !preg_match('~<link\s+rel=["\']canonical["\']\s+href=["\']([^"\']+)["\']~i', $head, $m)) {
        continue;
    }
    if (rtrim($m[1], '/') !== rtrim($loc, '/')) {
        continue;
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
