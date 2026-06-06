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

// Compute base path once. SCRIPT_NAME = "/od9/public/library.php" or "/library.php"
$nav_base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'), '/\\');
// Helper: prefix a root-relative path with the base
$nav_url = function(string $path) use ($nav_base): string {
    return $nav_base . '/' . ltrim($path, '/');
};

$nav_links = [
    'index'      => ['href' => $nav_url('index.php'),      'label' => 'Home'],
    'framework'  => ['href' => $nav_url('framework.php'),  'label' => 'Framework'],
    'ncz'        => ['href' => $nav_url('ncz.php'),        'label' => 'NCZ'],
    'updates'    => ['href' => $nav_url('updates.php'),    'label' => 'Updates'],
    'tiers'      => ['href' => $nav_url('tiers.php'),      'label' => 'Tiers'],
    'library'    => ['href' => $nav_url('library.php'),    'label' => 'Library'],
    'da-crew'    => ['href' => $nav_url('da-crew.php'),    'label' => 'Da Crew'],
    'music'      => ['href' => $nav_url('music.php'),      'label' => 'Music'],
    'join'       => ['href' => $nav_url('join.php'),       'label' => 'Join'],
    'downloads'  => ['href' => $nav_url('downloads.php'),  'label' => 'Downloads'],
    'support'    => ['href' => $nav_url('support.php'),    'label' => 'Support'],
];
if (!isset($current_page)) $current_page = '';
?>
<nav class="od9-nav"><div class="nav-container">
<a href="<?= $nav_url('index.php') ?>" class="nav-logo"><img src="<?= $nav_url('images/logos/od9-logo.png') ?>" alt="OD9"><span class="nav-logo-text">OD9</span></a>
<ul class="nav-menu" style="gap:0.7rem">
<?php foreach ($nav_links as $key => $link): ?>
<li><a href="<?= $link['href'] ?>" class="nav-link <?= $current_page === $key ? 'active' : '' ?>" style="font-size:0.85rem"><?= $link['label'] ?></a></li>
<?php endforeach; ?>
<li><a href="https://discord.gg/spgmrXVMWq" target="_blank" class="nav-btn"><i class="fab fa-discord"></i> Discord</a></li>
</ul>
<button class="mobile-toggle" id="hamburger"><span></span><span></span><span></span></button>
</div></nav>
<div class="mobile-menu" id="mobileMenu">
<?php foreach ($nav_links as $key => $link): ?>
<a href="<?= $link['href'] ?>" class="<?= $current_page === $key ? 'active' : '' ?>"><?= $link['label'] ?></a>
<?php endforeach; ?>
<a href="https://discord.gg/spgmrXVMWq" target="_blank" class="mobile-discord"><i class="fab fa-discord"></i> Join Discord</a>
</div>
<script>document.getElementById('hamburger').addEventListener('click',function(){this.classList.toggle('active');document.getElementById('mobileMenu').classList.toggle('active');});</script>
