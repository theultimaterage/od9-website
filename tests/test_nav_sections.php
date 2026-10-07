<?php
/**
 * The universal nav (includes/nav.php) after it became section menus (2026-10-07).
 * Standalone, no PHPUnit:
 *
 *   php tests/test_nav_sections.php        (exit 0 = all pass)
 *
 * Grouping links into menus has one silent way to fail: a page that lands in no
 * section simply disappears from the site's navigation, and nothing errors. So
 * every page in $nav_links must be reachable exactly once on desktop (a section
 * or a top link; Home is the logo) and once in the phone panel, the current page
 * must be marked in both, and its section button must show as active.
 */
declare(strict_types=1);
chdir(dirname(__DIR__));

$pass = 0;
$fail = 0;
function ok(bool $cond, string $what): void
{
    global $pass, $fail;
    if ($cond) { $pass++; echo "PASS {$what}\n"; } else { $fail++; echo "FAIL {$what}\n"; }
}

function render(string $current): array
{
    $_SERVER['SCRIPT_NAME'] = '/forge.php';
    $current_page = $current;
    ob_start();
    include 'includes/nav.php';
    $html = ob_get_clean();
    return [$html, $nav_links, $nav_sections, $nav_top];
}

[$html, $links, $sections, $top] = render('forge');

$placed = array_merge($top, ...array_map(fn($s) => $s['items'], array_values($sections)));
$expected = array_values(array_diff(array_keys($links), ['index']));
sort($expected);
$sortedPlaced = $placed;
sort($sortedPlaced);
ok($sortedPlaced === $expected, 'every page except Home sits in exactly one section or top link');
ok(count($placed) === count(array_unique($placed)), 'no page is listed twice');

[$desktop, $phone] = explode('<div class="mobile-menu"', $html, 2) + [1 => ''];
foreach ($links as $key => $link) {
    $href = 'href="' . $link['href'] . '"';
    if ($key !== 'index') {
        ok(substr_count($desktop, $href) === 1, "desktop links {$link['label']} once");
    }
    ok(substr_count($phone, $href) === 1, "phone panel links {$link['label']} once");
}
ok(str_contains($desktop, 'href="/index.php" class="nav-logo"'), 'the logo is the way home on desktop');
ok(substr_count($desktop, 'aria-current="page"') === 1 && str_contains($desktop, 'href="/forge.php" class="active" aria-current="page"'),
   'the current page is marked once on desktop');
ok((bool)preg_match('/nav-group-btn active" aria-expanded="false" aria-controls="nav-sec-learn"/', $desktop),
   "the current page's section button shows as active");
ok(substr_count($desktop, 'nav-group-btn active') === 1, 'no other section shows as active');
ok(str_contains($phone, 'href="/forge.php" class="active"'), 'the current page is marked in the phone panel');
ok(substr_count($html, 'discord.gg/') >= 3, 'Discord: the button, the Community menu and the phone panel');
foreach (array_keys($sections) as $sid) {
    ok(str_contains($desktop, 'aria-controls="nav-sec-' . $sid . '"') && str_contains($desktop, 'id="nav-sec-' . $sid . '"'),
       "section {$sid} has a button and the menu it controls");
}

echo "\n{$pass} passed, {$fail} failed\n";
exit($fail === 0 ? 0 : 1);
