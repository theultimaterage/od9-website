<!DOCTYPE html>
<html lang="en">
<head>
<?php
$page_title = 'OD9 Merch | Store Staging Page';
$page_description = 'OD9 merch staging page for NCZ drops, OD9 tees, badges, and digital goods.';
$page_slug = 'merch.php';
include('includes/head.php');
?>
<style>
body{background:var(--d);color:var(--c);font-family:'Exo 2',sans-serif;padding-top:80px}
.container{max-width:1100px;margin:0 auto;padding:2rem}
.hero,.card{background:var(--dd);border:1px solid #2a2a2a;border-radius:16px;padding:2rem;margin:2rem 0 1.25rem}
h1,h2,h3{font-family:'Orbitron',sans-serif;color:#fff}
p,li{line-height:1.75;color:#bdbdbd}
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:1.25rem}
.btn{display:inline-block;background:linear-gradient(135deg,var(--b),#00A0FF);color:#0D0D0D;padding:.8rem 1.3rem;border-radius:5px;text-decoration:none;font-family:'Rajdhani',sans-serif;font-weight:700;letter-spacing:1px;text-transform:uppercase;margin-top:1rem}
</style>
</head>
<body>
<?php $current_page = 'merch'; include('includes/nav.php'); ?>
<div class="container">
<section class="hero"><h1>MERCH</h1>
<p>Rep the movement. NCZ gear, OD9 branded items, and digital goods are in the works. When the store drops, this is where you'll find it.</p></section>
<section class="grid">
<article class="card"><h2>COMING SOON</h2>
<ul><li>85 Seconds to Midnight tees</li><li>NCZ logo stickers</li><li>OD9 logo tees</li><li>ASCEND tier badges</li><li>Phone wallpaper packs</li></ul></article>
<article class="card"><h2>DIGITAL GOODS</h2>
<p>Wallpapers, branded visuals, and supporter packs are coming first since they ship instantly and cost nothing to produce. Check the downloads page for what's available now.</p>
<a class="btn" href="downloads.php">See downloads</a></article>
<article class="card"><h2>STORE INTEGRATION</h2>
<p>The print-on-demand storefront is being set up. When it's live, merch will be available directly through this page with zero inventory risk.</p>
<a class="btn alt" href="support.php">Support the mission now</a></article>
</section></div>
<?php include('includes/footer.php'); ?>
</body>
</html>
