<?php
/**
 * OD9 Universal Navigation
 * Include this at the top of every page's <body> tag.
 * Set $current_page before including to highlight the active link.
 * Example: $current_page = 'ncz'; include('includes/nav.php');
 *
 * BASE PATH AWARENESS (added 2026-04-23):
 * Nav links auto-resolve against the install root so the same nav works in:
 *   - Production: site at root - links like "/index.php"
 *   - Local XAMPP: site at /od9/public/ - links like "/od9/public/index.php"
 * Detection: dirname() of SCRIPT_NAME gives the install dir prefix.
 */

// Compute base path once. SCRIPT_NAME = "/od9/public/library.php" or "/library.php".
// A caller in a subdirectory (e.g. /dashboard/) can pre-set $nav_base to the
// site root so the menu links don't inherit the subdir prefix.
$nav_base = $nav_base ?? rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'), '/\\');
// Helper: prefix a root-relative path with the base
$nav_url = function(string $path) use ($nav_base): string {
    return $nav_base . '/' . ltrim($path, '/');
};

// Every page the nav reaches. Grouped below into sections (founder, 2026-10-07:
// fifteen links in one row had outgrown the header). Belonging-first order is
// kept: the people section (Community) comes before the content (Learn).
$nav_links = [
    'index'      => ['href' => $nav_url('index.php'),      'label' => 'Home'],
    'events'     => ['href' => $nav_url('events.php'),     'label' => 'Events'],
    'da-crew'    => ['href' => $nav_url('da-crew.php'),    'label' => 'Da Crew'],
    'music'      => ['href' => $nav_url('music.php'),      'label' => 'Music'],
    'ncz'        => ['href' => $nav_url('ncz.php'),        'label' => 'NCZ'],
    'about'      => ['href' => $nav_url('about.php'),      'label' => 'About'],
    'framework'  => ['href' => $nav_url('framework.php'),  'label' => 'Framework'],
    'atlas'      => ['href' => $nav_url('atlas.php'),      'label' => 'Atlas'],
    'forge'      => ['href' => $nav_url('forge.php'),      'label' => 'Forge'],
    'roadmap'    => ['href' => $nav_url('roadmap.php'),    'label' => 'Roadmap'],
    'tiers'      => ['href' => $nav_url('tiers.php'),      'label' => 'Tiers'],
    'library'    => ['href' => $nav_url('library.php'),    'label' => 'Library'],
    'join'       => ['href' => $nav_url('join.php'),       'label' => 'Join'],
    'downloads'  => ['href' => $nav_url('downloads.php'),  'label' => 'Downloads'],
    'support'    => ['href' => $nav_url('support.php'),    'label' => 'Support'],
];
if (!isset($current_page)) $current_page = '';

// The sections. Desktop shows them as menus that open on hover or click; the
// phone panel shows the same sections as labeled groups. Home is the logo.
// A page missing from every section is unreachable from the nav, so every key
// of $nav_links except 'index' must appear exactly once (tests/test_nav_sections.php).
$nav_sections = [
    'community' => ['label' => 'Community',   'items' => ['events', 'da-crew', 'music', 'ncz']],
    'learn'     => ['label' => 'Learn',       'items' => ['framework', 'atlas', 'forge', 'library', 'roadmap']],
    'backing'   => ['label' => 'Support OD9', 'items' => ['tiers', 'join', 'support', 'downloads']],
];
$nav_top = ['about'];   // plain links beside the menus
$nav_discord = 'https://discord.gg/spgmrXVMWq';
?>
<?php include __DIR__ . '/topbar.php'; ?>
<nav class="od9-nav" aria-label="Main"><div class="nav-container">
<a href="<?= $nav_url('index.php') ?>" class="nav-logo" aria-label="OD9 home"><img src="<?= $nav_url('images/logos/od9-logo-nav-hd.png') ?>" alt="OD9" width="66" height="36"><span class="nav-logo-text">OD9</span></a>
<ul class="nav-menu">
<?php foreach ($nav_sections as $sid => $section): $here = in_array($current_page, $section['items'], true); ?>
<li class="nav-group">
<button type="button" class="nav-link nav-group-btn<?= $here ? ' active' : '' ?>" aria-expanded="false" aria-controls="nav-sec-<?= $sid ?>"><?= $section['label'] ?><span class="nav-caret" aria-hidden="true"></span></button>
<ul class="nav-dropdown" id="nav-sec-<?= $sid ?>">
<?php foreach ($section['items'] as $key): $link = $nav_links[$key]; ?>
<li><a href="<?= $link['href'] ?>"<?= $current_page === $key ? ' class="active" aria-current="page"' : '' ?>><?= $link['label'] ?></a></li>
<?php endforeach; ?>
<?php if ($sid === 'community'): ?><li><a href="<?= $nav_discord ?>" target="_blank" rel="noopener">Discord</a></li><?php endif; ?>
</ul>
</li>
<?php endforeach; ?>
<?php foreach ($nav_top as $key): $link = $nav_links[$key]; ?>
<li><a href="<?= $link['href'] ?>" class="nav-link<?= $current_page === $key ? ' active' : '' ?>"<?= $current_page === $key ? ' aria-current="page"' : '' ?>><?= $link['label'] ?></a></li>
<?php endforeach; ?>
<li><a href="<?= $nav_discord ?>" target="_blank" rel="noopener" class="nav-btn"><i class="fab fa-discord"></i> Discord</a></li>
</ul>
<button type="button" class="mobile-toggle" id="hamburger" aria-label="Menu" aria-expanded="false" aria-controls="mobileMenu"><span></span><span></span><span></span></button>
</div></nav>
<div class="mobile-menu" id="mobileMenu">
<a href="<?= $nav_url('index.php') ?>"<?= $current_page === 'index' ? ' class="active"' : '' ?>>Home</a>
<?php foreach ($nav_sections as $sid => $section): ?>
<div class="mobile-section"><div class="mobile-section-label"><?= $section['label'] ?></div>
<?php foreach ($section['items'] as $key): $link = $nav_links[$key]; ?>
<a href="<?= $link['href'] ?>"<?= $current_page === $key ? ' class="active"' : '' ?>><?= $link['label'] ?></a>
<?php endforeach; ?>
</div>
<?php endforeach; ?>
<?php foreach ($nav_top as $key): $link = $nav_links[$key]; ?>
<a href="<?= $link['href'] ?>"<?= $current_page === $key ? ' class="active"' : '' ?>><?= $link['label'] ?></a>
<?php endforeach; ?>
<a href="<?= $nav_discord ?>" target="_blank" rel="noopener" class="mobile-discord"><i class="fab fa-discord"></i> Join Discord</a>
</div>
<script>
(function () {
  var burger = document.getElementById('hamburger'), panel = document.getElementById('mobileMenu');
  burger.addEventListener('click', function () {
    var open = panel.classList.toggle('active');
    burger.classList.toggle('active', open);
    burger.setAttribute('aria-expanded', open ? 'true' : 'false');
  });
  // Section menus: click toggles (touch and keyboard); hover opens via CSS.
  var groups = Array.prototype.slice.call(document.querySelectorAll('.od9-nav .nav-group'));
  function closeAll(except) {
    groups.forEach(function (g) {
      if (g !== except) { g.classList.remove('open'); g.querySelector('.nav-group-btn').setAttribute('aria-expanded', 'false'); }
    });
  }
  groups.forEach(function (g) {
    var btn = g.querySelector('.nav-group-btn');
    btn.addEventListener('click', function (e) {
      e.stopPropagation();
      var open = !g.classList.contains('open');
      closeAll(g);
      g.classList.toggle('open', open);
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  });
  document.addEventListener('click', function () { closeAll(null); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeAll(null); });
})();
</script>
<?php include __DIR__ . '/email-popup.php'; ?>
