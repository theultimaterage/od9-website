<?php
/**
 * OD9 Universal Navigation
 * Include this at the top of every page's <body> tag.
 * Set $current_page before including to highlight the active link.
 * Example: $current_page = 'ncz'; include('includes/nav.php');
 */
$nav_links = [
    'index' => ['href' => 'index.php', 'label' => 'Home'],
    'framework' => ['href' => 'framework.php', 'label' => 'Framework'],
    'ncz' => ['href' => 'ncz.php', 'label' => 'NCZ'],
    'updates' => ['href' => 'updates.php', 'label' => 'Updates'],
    'think-tank' => ['href' => 'think-tank.php', 'label' => 'Think Tank'],
    'tiers' => ['href' => 'tiers.php', 'label' => 'Tiers'],
    'da-crew' => ['href' => 'da-crew.php', 'label' => 'Da Crew'],
    'music' => ['href' => 'music.php', 'label' => 'Music'],
    'join' => ['href' => 'join.php', 'label' => 'Join'],
    'downloads' => ['href' => 'downloads.php', 'label' => 'Downloads'],
    'support' => ['href' => 'support.php', 'label' => 'Support'],
];
if (!isset($current_page)) $current_page = '';
?>
<nav class="od9-nav"><div class="nav-container">
<a href="index.php" class="nav-logo"><img src="images/logos/od9-logo.png" alt="OD9"><span class="nav-logo-text">OD9</span></a>
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
