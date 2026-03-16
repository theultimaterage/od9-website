<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>OD9 Merch | Store Staging Page</title>
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@400;600;700&family=Exo+2:wght@400;600&display=swap" rel="stylesheet">
<meta name="description" content="OD9 merch staging page for NCZ drops, OD9 tees, badges, and digital goods.">
<style>
:root{--b:#00BFFF;--d:#0D0D0D;--dd:#1A1A1A;--c:#C0C0C0}*{margin:0;padding:0;box-sizing:border-box}body{background:var(--d);color:var(--c);font-family:'Exo 2',sans-serif;padding-top:80px}.container{max-width:1100px;margin:0 auto;padding:2rem}.hero,.card{background:var(--dd);border:1px solid #2a2a2a;border-radius:16px;padding:2rem;margin:2rem 0 1.25rem}h1,h2,h3{font-family:'Orbitron',sans-serif;color:#fff}p,li{line-height:1.75;color:#bdbdbd}.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:1.25rem}.btn{display:inline-block;background:linear-gradient(135deg,var(--b),#00A0FF);color:#0D0D0D;padding:.8rem 1.3rem;border-radius:5px;text-decoration:none;font-family:'Rajdhani',sans-serif;font-weight:700;letter-spacing:1px;text-transform:uppercase;margin-top:1rem}
.od9-nav{position:fixed;top:0;left:0;width:100%;height:80px;background:linear-gradient(180deg,rgba(13,13,13,.98),rgba(26,26,26,.95));backdrop-filter:blur(20px);border-bottom:2px solid var(--b);box-shadow:var(--g);z-index:9999}.nav-container{max-width:1200px;margin:0 auto;padding:0 2rem;height:100%;display:flex;justify-content:space-between;align-items:center}.nav-logo{display:flex;align-items:center;text-decoration:none}.nav-logo img{height:50px;margin-right:.75rem;filter:drop-shadow(var(--g))}.nav-logo-text{font-family:'Orbitron',sans-serif;font-size:1.5rem;font-weight:700;color:var(--b);letter-spacing:3px;text-shadow:var(--g)}.nav-menu{display:flex;list-style:none;gap:1.2rem;align-items:center}.nav-link{color:var(--c);text-decoration:none;font-family:'Rajdhani',sans-serif;font-size:1rem;font-weight:600;letter-spacing:1px;text-transform:uppercase;transition:.3s;padding:.5rem 0;position:relative}.nav-link::after{content:'';position:absolute;bottom:0;left:50%;width:0;height:2px;background:var(--b);transition:.3s;transform:translateX(-50%)}.nav-link:hover,.nav-link.active{color:var(--b)}.nav-link:hover::after,.nav-link.active::after{width:100%}.nav-btn{background:linear-gradient(135deg,var(--b),#00A0FF);color:#0D0D0D;padding:.6rem 1.2rem;border-radius:4px;text-decoration:none;font-family:'Rajdhani',sans-serif;font-weight:700;display:flex;align-items:center;gap:.5rem;box-shadow:var(--g)}.mobile-toggle{display:none;background:none;border:none;cursor:pointer;padding:.5rem;z-index:10001}.mobile-toggle span{display:block;width:25px;height:3px;background:var(--b);margin:5px 0;border-radius:2px;transition:.3s}.mobile-toggle.active span:nth-child(1){transform:rotate(45deg) translate(5px,5px)}.mobile-toggle.active span:nth-child(2){opacity:0}.mobile-toggle.active span:nth-child(3){transform:rotate(-45deg) translate(7px,-6px)}.mobile-menu{display:none;position:fixed;top:80px;left:0;width:100%;background:linear-gradient(180deg,rgba(13,13,13,.98),rgba(26,26,26,.98));padding:1rem 0;border-bottom:2px solid var(--b);box-shadow:var(--g);z-index:9998}.mobile-menu.active{display:block}.mobile-menu a{display:block;color:var(--c);text-decoration:none;font-family:'Rajdhani',sans-serif;font-size:1.1rem;font-weight:600;letter-spacing:1px;text-transform:uppercase;padding:1rem 2rem;border-bottom:1px solid #222}.mobile-menu a.active,.mobile-menu a:hover{color:var(--b);background:rgba(0,191,255,.1)}.mobile-menu .mobile-discord{background:linear-gradient(135deg,var(--b),#00A0FF);color:#0D0D0D;margin:1rem;border-radius:4px;text-align:center;border-bottom:none}@media(max-width:900px){.nav-menu{display:none}.mobile-toggle{display:block}}
</style>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
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