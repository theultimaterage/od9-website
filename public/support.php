<?php
/**
 * OD9 - Support Hub
 * Merged conversion hub (absorbed why-patreon.php on 2026-06-05): mission case
 * + $200/mo leverage logic + founding-slot scarcity + ASCEND tier grid +
 * direct-support rails. why-patreon.php now 301-redirects here.
 */
declare(strict_types=1);

$configPath = __DIR__ . '/../config/database.php';
if (!file_exists($configPath)) {
    $configPath = __DIR__ . '/config/database.php';
}
@require_once $configPath;

const FOUNDING_CAP = 25;
$founding_remaining = FOUNDING_CAP;
try {
    if (function_exists('getDatabaseConnection')) {
        $pdo = getDatabaseConnection();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt = $pdo->query(
            "SELECT COUNT(*) FROM od9_members
             WHERE LOWER(patreon_tier) = 'founding' AND is_patreon_supporter = 1"
        );
        $founding_filled = (int)$stmt->fetchColumn();
        $founding_remaining = max(0, FOUNDING_CAP - $founding_filled);
    }
} catch (Throwable $e) {
    if (!defined('ENVIRONMENT') || ENVIRONMENT !== 'local') error_log('[support.php] founding count failed: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Support OD9 | Fund the Movement</title>
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@400;600;700&family=Exo+2:wght@400;600&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<meta name="description" content="Support OD9, The No Cap Zone, and the ASCEND ecosystem. Fund content production, Discord infrastructure, and the independent media stack.">
<meta name="robots" content="index, follow">
<link rel="canonical" href="https://offda9.com/support.php">
<meta property="og:title" content="Support OD9 | Fund the Movement">
<meta property="og:description" content="Back the independent media and community stack behind OD9 and The No Cap Zone.">
<meta property="og:image" content="https://offda9.com/images/logos/od9-logo.png">
<meta property="og:site_name" content="OD9 - Off Da Nine">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="Support OD9 | Fund the Movement">
<meta name="twitter:description" content="Fund NCZ content production, Discord community systems, and OD9 development.">
<meta name="theme-color" content="#00BFFF">
<link rel="icon" type="image/png" href="/images/logos/od9-logo.png">
<style>
:root{--b:#00BFFF;--eb:#00A0FF;--c:#C0C0C0;--d:#0D0D0D;--dd:#1A1A1A;--g:0 0 20px rgba(0,191,255,.45)}
*{margin:0;padding:0;box-sizing:border-box}body{background:var(--d);background-image:linear-gradient(45deg,#111 25%,transparent 25%),linear-gradient(-45deg,#111 25%,transparent 25%),linear-gradient(45deg,transparent 75%,#111 75%),linear-gradient(-45deg,transparent 75%,#111 75%);background-size:4px 4px;color:var(--c);font-family:'Exo 2',sans-serif;padding-top:80px;min-height:100vh}
.od9-nav{position:fixed;top:0;left:0;width:100%;height:80px;background:linear-gradient(180deg,rgba(13,13,13,.98),rgba(26,26,26,.95));backdrop-filter:blur(20px);border-bottom:2px solid var(--b);box-shadow:var(--g);z-index:9999}.nav-container{max-width:1200px;margin:0 auto;padding:0 2rem;height:100%;display:flex;justify-content:space-between;align-items:center}.nav-logo{display:flex;align-items:center;text-decoration:none}.nav-logo img{height:50px;margin-right:.75rem;filter:drop-shadow(var(--g))}.nav-logo-text{font-family:'Orbitron',sans-serif;font-size:1.5rem;font-weight:700;color:var(--b);letter-spacing:3px;text-shadow:var(--g)}.nav-menu{display:flex;list-style:none;gap:1.2rem;align-items:center}.nav-link{color:var(--c);text-decoration:none;font-family:'Rajdhani',sans-serif;font-size:1rem;font-weight:600;letter-spacing:1px;text-transform:uppercase;transition:.3s;padding:.5rem 0;position:relative}.nav-link::after{content:'';position:absolute;bottom:0;left:50%;width:0;height:2px;background:var(--b);transition:.3s;transform:translateX(-50%)}.nav-link:hover,.nav-link.active{color:var(--b)}.nav-link:hover::after,.nav-link.active::after{width:100%}.nav-btn{background:linear-gradient(135deg,var(--b),var(--eb));color:var(--d);padding:.6rem 1.2rem;border-radius:4px;text-decoration:none;font-family:'Rajdhani',sans-serif;font-weight:700;font-size:.9rem;text-transform:uppercase;display:flex;align-items:center;gap:.5rem;box-shadow:var(--g)}.mobile-toggle{display:none;background:none;border:none;cursor:pointer;padding:.5rem;z-index:10001}.mobile-toggle span{display:block;width:25px;height:3px;background:var(--b);margin:5px 0;border-radius:2px;transition:.3s}.mobile-toggle.active span:nth-child(1){transform:rotate(45deg) translate(5px,5px)}.mobile-toggle.active span:nth-child(2){opacity:0}.mobile-toggle.active span:nth-child(3){transform:rotate(-45deg) translate(7px,-6px)}.mobile-menu{display:none;position:fixed;top:80px;left:0;width:100%;background:linear-gradient(180deg,rgba(13,13,13,.98),rgba(26,26,26,.98));backdrop-filter:blur(20px);padding:1rem 0;border-bottom:2px solid var(--b);box-shadow:var(--g);z-index:9998}.mobile-menu.active{display:block}.mobile-menu a{display:block;color:var(--c);text-decoration:none;font-family:'Rajdhani',sans-serif;font-size:1.1rem;font-weight:600;letter-spacing:1px;text-transform:uppercase;padding:1rem 2rem;border-bottom:1px solid #222}.mobile-menu a:hover,.mobile-menu a.active{color:var(--b);background:rgba(0,191,255,.1)}.mobile-menu .mobile-discord{background:linear-gradient(135deg,var(--b),var(--eb));color:var(--d);margin:1rem;border-radius:4px;text-align:center;border-bottom:none}@media(max-width:900px){.nav-menu{display:none}.mobile-toggle{display:block}}
.container{max-width:1180px;margin:0 auto;padding:2rem}.hero,.section{background:var(--dd);border:1px solid #2a2a2a;border-radius:16px;padding:2rem;margin-bottom:1.5rem}.hero{background:linear-gradient(135deg,rgba(0,191,255,.12),rgba(0,0,0,.7));border:1px solid rgba(0,191,255,.35);border-radius:18px;padding:3rem 2rem;text-align:center;box-shadow:var(--g)}h1,h2,h3{font-family:'Orbitron',sans-serif;color:#fff}h1{font-size:2.8rem;margin-bottom:.75rem}.hero p{max-width:850px;margin:0 auto 1rem;line-height:1.8;font-size:1.06rem}.pill-row{display:flex;gap:.8rem;justify-content:center;flex-wrap:wrap;margin-top:1rem}.pill{padding:.55rem .9rem;border:1px solid rgba(0,191,255,.4);border-radius:999px;background:rgba(255,255,255,.03);font-size:.95rem}.btn{display:inline-block;background:linear-gradient(135deg,var(--b),var(--eb));color:var(--d);padding:.8rem 1.4rem;border-radius:5px;text-decoration:none;font-family:'Rajdhani',sans-serif;font-weight:700;text-transform:uppercase;letter-spacing:1px;transition:.3s;margin:.4rem}.btn:hover{transform:translateY(-2px);box-shadow:var(--g)}.btn.alt{background:transparent;color:var(--b);border:1px solid var(--b)}.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:1.25rem}.card{background:#121212;border:1px solid #2a2a2a;border-radius:14px;padding:1.5rem}.card i{font-size:2rem;color:var(--b);margin-bottom:.9rem}.card h3{margin-bottom:.55rem;font-size:1.15rem}.card p{line-height:1.7;color:#b8b8b8}.card img{width:100%;border-radius:12px;margin-bottom:1rem;border:1px solid rgba(0,191,255,.15)}.qr-card{text-align:center}.qr-card img{background:#fff;padding:12px;border-radius:10px;display:block;margin:1rem auto;width:220px;height:220px;object-fit:cover}ul.fund{list-style:none;display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:1rem;margin-top:1rem}ul.fund li{padding:1rem;background:rgba(0,191,255,.05);border-radius:10px}.embed-wrap{position:relative;width:100%;padding-top:56.25%;border-radius:12px;overflow:hidden;background:#000;margin-bottom:1rem}.embed-wrap iframe{position:absolute;top:0;left:0;width:100%;height:100%;border:0}.footer{margin-top:3rem;padding:2rem 0;border-top:1px solid #333;text-align:center}.footer a{color:#00BFFF;text-decoration:none;margin:0 .6rem}.socials a{color:#777;margin:0 .4rem;font-size:1.2rem}@media(max-width:640px){h1{font-size:2.15rem}.container{padding:1.25rem}.hero,.section{padding:1.5rem}.qr-card img{width:180px;height:180px}}
</style>
<style>
.leverage strong{color:var(--b)}.leverage em{color:#FFD700;font-style:normal;font-weight:600}
.scarcity{background:linear-gradient(135deg,rgba(183,110,121,.15),rgba(255,215,0,.06));border:1px solid #B76E79;text-align:center}
.scarcity-num{font-family:'Orbitron',sans-serif;font-size:3rem;font-weight:800;color:#B76E79;line-height:1;margin-bottom:.5rem}
.scarcity-label{color:#bbb;text-transform:uppercase;font-size:.85rem;letter-spacing:1.5px;margin-bottom:1rem}
.tier-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:.75rem;margin-top:1rem}
.tier-mini{background:#121212;border:1px solid #2a2a2a;border-radius:10px;padding:1.1rem}
.tier-mini-name{font-family:'Orbitron',sans-serif;color:var(--b);font-size:.95rem;letter-spacing:1px;margin-bottom:.35rem}
.tier-mini-price{color:#888;font-size:.85rem;margin-bottom:.4rem}
.tier-mini-line{color:#aaa;font-size:.85rem;line-height:1.5}
</style>
</head>
<body>
<?php $current_page = 'support'; include('includes/nav.php'); ?>

<div class="container">

<section class="hero">
<h1>SUPPORT OD9</h1>
<p>Everything you see here - the show, the website, the Discord, the ASCEND system, the content pipeline, the entire independent media stack - is built and funded independently. No corporate sponsors. No ad networks calling shots. No platform holding the keys.</p>
<p>Your support keeps it that way.</p>
<div class="pill-row">
<span class="pill">NCZ production</span>
<span class="pill">ASCEND + Discord</span>
<span class="pill">Server infrastructure</span>
<span class="pill">Independent media</span>
</div>
<div style="margin-top:1.25rem">
<a class="btn" href="https://www.patreon.com/c/TheUltimateRage" target="_blank">Back on Patreon</a>
<a class="btn alt" href="join.php">Join the Community</a>
</div>
</section>

<section class="section leverage">
<h2>THE LEVERAGE POINT: $200/MO</h2>
<p>Patreon gates annual-billing eligibility behind <strong>$200/month for three consecutive months</strong>. OD9 is pre-enrolled &mdash; Patreon flips the toggle automatically the moment we cross it.</p>
<p>Annual billing converts trickle-revenue into <em>cash-on-hand</em>: 12 months of committed runway instead of 12 monthly decisions to support. <strong>Higher retention. Lower churn. A step-function in OD9's runway.</strong></p>
<p><strong>Six anchor patrons get us across the line.</strong> One Founding + one Benefactor + two Pioneers + two Architects = $240/mo. Hold that for 90 days and annual billing unlocks for everyone.</p>
<p style="margin-top:1rem"><a href="progress.php" style="color:#FFD700;text-decoration:none">&rarr; See the live MRR counter on the progress page</a></p>
</section>

<section class="section scarcity">
<div class="scarcity-num"><?= $founding_remaining ?></div>
<div class="scarcity-label">Lifetime Founding Patron slots remaining</div>
<p style="max-width:520px;margin:0 auto 1rem">The Founding Patron tier is capped at <strong>25 lifetime slots</strong>. When the counter hits zero, the tier closes forever &mdash; and your name stays on the public OD9 founding ledger as long as offda9.com exists.</p>
<a class="btn alt" href="founders.php">See the Ledger</a>
</section>

<section class="section">
<h2>THE 5 ASCEND TIERS</h2>
<p style="margin-bottom:.5rem">Every tier compounds your access and your voice &mdash; and tiers are also <a href="tiers.php" style="color:var(--b)">earnable through contribution</a>, so you don't have to buy your way up.</p>
<div class="tier-grid">
<div class="tier-mini"><div class="tier-mini-name">Observer</div><div class="tier-mini-price">Free</div><div class="tier-mini-line">Public entry. Earn ASCEND credits through analysis and participation.</div></div>
<div class="tier-mini"><div class="tier-mini-name">Theorist</div><div class="tier-mini-price">$5/mo</div><div class="tier-mini-line">Skip the Observer grind. 50 credits/mo. 1.2&times; multiplier on every contribution.</div></div>
<div class="tier-mini"><div class="tier-mini-name">Architect</div><div class="tier-mini-price">$15/mo</div><div class="tier-mini-line">Full chapter frameworks. Think Tank recordings. Synaptiq alpha when it ships.</div></div>
<div class="tier-mini"><div class="tier-mini-name">Pioneer</div><div class="tier-mini-price">$30/mo</div><div class="tier-mini-line">NCZ topic vote. Governance weight. Synaptiq beta priority. 150 credits/mo.</div></div>
<div class="tier-mini"><div class="tier-mini-name">Benefactor</div><div class="tier-mini-price">$50/mo</div><div class="tier-mini-line">Monthly 30-min 1-on-1. Roadmap influence. $25 F.R.E.S.H. credit. 200 credits/mo.</div></div>
<div class="tier-mini" style="border-color:#B76E79"><div class="tier-mini-name" style="color:#B76E79">Founding Patron</div><div class="tier-mini-price">$100/mo &middot; Capped 25</div><div class="tier-mini-line">Public ledger entry. First access to Synaptiq, F.R.E.S.H., metaworld. 250 credits/mo.</div></div>
</div>
</section>

<section class="section">
<h2>SEE WHAT YOU'RE SUPPORTING</h2>
<div class="grid">
<div class="card">
<div class="embed-wrap">
<iframe src="https://www.youtube.com/embed?listType=user_uploads&list=theultimaterage" title="Latest NCZ content" allowfullscreen></iframe>
</div>
<h3>THE NO CAP ZONE</h3>
<p>This is what your support produces. Live streams, auto-generated clips, news critiques through the Four Lenses, and the content that drives the movement forward.</p>
<a class="btn" href="ncz.php">Explore NCZ</a>
</div>
<div class="card">
<img src="images/downloads/od9-wallpaper.png" alt="OD9 brand identity">
<h3>THE BRAND</h3>
<p>Diamond chrome on carbon fiber. The OD9 visual identity, the NCZ show format, the ASCEND progression system - all of it designed and built from scratch.</p>
<a class="btn alt" href="downloads.php">Free Downloads</a>
</div>
</div>
</section>

<section class="section">
<h2>WHAT YOUR SUPPORT FUNDS</h2>
<ul class="fund">
<li><strong>NCZ stream production</strong> - Show prep, recording, clips, overlays, thumbnails, and multi-platform distribution across YouTube, Substack, and Twitch.</li>
<li><strong>The OD9 website</strong> - This site, the NCZ content hub, the Think Tank archive, the downloads page, and every page you're browsing right now.</li>
<li><strong>Discord + ASCEND</strong> - Bot development, the 5-tier progression system, Think Tank sessions, game nights, movie nights, and community infrastructure.</li>
<li><strong>Independent distribution</strong> - Email newsletter, owned archives, cross-platform posting. Building reach that no algorithm can take away.</li>
<li><strong>Hosting + dev tools</strong> - Servers, storage, AI tooling, automation systems, and the content pipeline that turns one stream into dozens of posts.</li>
<li><strong>The bigger picture</strong> - The F.R.E.S.H. platform, Synaptiq audio production tools, and the coordination infrastructure described in the OD9 Manifesto.</li>
</ul>
</section>

<section class="section">
<h2>WHY NOT AN AD-SUPPORTED MODEL?</h2>
<p>Because the whole point of OD9 is to demonstrate <em style="color:#FFD700;font-style:normal;font-weight:600">coordination infrastructure that doesn't betray its mission</em>. Ads optimize for attention extraction &mdash; the same paperclip-maximizer pattern OD9 exists to push back against.</p>
<p>Patreon and direct support are the cleanest alternatives: <strong style="color:var(--b)">funded directly by the people who choose to fund it</strong>, with transparent payouts and no algorithm deciding what gets surfaced. The F.R.E.S.H. platform is being built so OD9 can eventually run on infrastructure we own.</p>
</section>

<section class="section">
<h2>DIRECT SUPPORT</h2>
<p style="text-align:center;margin-bottom:1.5rem">No middleman. No platform cut. Scan and send directly.</p>
<div class="grid">
<div class="card qr-card">
<i class="fab fa-square-cash-app" style="color:#00D632"></i>
<h3>Cash App</h3>
<p>$TheUltimateRage</p>
<img src="images/payment/cashapp-qr.jpg" alt="Cash App QR Code">
</div>
<div class="card qr-card">
<i class="fab fa-paypal" style="color:#003087"></i>
<h3>PayPal</h3>
<p>@TheUltimateRage</p>
<img src="images/payment/paypal-qr.jpg" alt="PayPal QR Code">
</div>
<div class="card qr-card">
<i class="fas fa-bolt" style="color:#6D1ED4"></i>
<h3>Zelle</h3>
<p>Scan to support directly</p>
<img src="images/payment/zelle-qr.jpg" alt="Zelle QR Code">
</div>
</div>
</section>

<section class="section">
<h2>OTHER WAYS TO SUPPORT</h2>
<div class="grid">
<div class="card">
<i class="fab fa-patreon" style="color:#FF424D"></i>
<h3>Patreon</h3>
<p>Monthly support with tiered access. Exclusive content, Think Tank recordings, and direct involvement in the direction of the movement.</p>
<a href="https://www.patreon.com/c/TheUltimateRage" target="_blank" class="btn" style="background:linear-gradient(135deg,#FF424D,#FF6B6B)">Join Patreon</a>
</div>
<div class="card">
<i class="fab fa-twitch"></i>
<h3>Twitch</h3>
<p>Follow, subscribe, and catch NCZ live. Every sub and bit goes directly toward keeping the stream running.</p>
<a href="https://www.twitch.tv/theultimaterage" target="_blank" class="btn">Follow on Twitch</a>
</div>
<div class="card">
<i class="fab fa-discord"></i>
<h3>Discord</h3>
<p>Free to join. This is where the Think Tank sessions happen, where ASCEND progression is tracked, and where the community actually lives.</p>
<a href="https://discord.gg/spgmrXVMWq" target="_blank" class="btn">Join Discord</a>
</div>
<div class="card">
<i class="fab fa-youtube"></i>
<h3>YouTube</h3>
<p>Subscribe, watch, and engage. Every view, like, and comment pushes NCZ closer to full monetization and wider reach.</p>
<a href="https://youtube.com/@theultimaterage" target="_blank" class="btn">Subscribe</a>
</div>
</div>
</section>

</div>

<?php include('includes/footer.php'); ?>
</body>
</html>