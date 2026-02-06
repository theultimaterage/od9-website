<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Support OD9 - Help Build the Future</title>
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@400;600;700&family=Exo+2:wght@400;600&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
:root{--primary-blue:#00BFFF;--electric-blue:#00A0FF;--neon-blue:#00D4FF;--chrome:#C0C0C0;--carbon:#0D0D0D;--carbon-dark:#1A1A1A;--glow:0 0 20px rgba(0,191,255,0.5);--nav-height:80px}
*{margin:0;padding:0;box-sizing:border-box}
body{background:var(--carbon);background-image:linear-gradient(45deg,#111 25%,transparent 25%),linear-gradient(-45deg,#111 25%,transparent 25%),linear-gradient(45deg,transparent 75%,#111 75%),linear-gradient(-45deg,transparent 75%,#111 75%);background-size:4px 4px;color:var(--chrome);font-family:'Exo 2',sans-serif;padding-top:var(--nav-height);min-height:100vh}
.od9-nav{position:fixed;top:0;left:0;width:100%;height:var(--nav-height);background:linear-gradient(180deg,rgba(13,13,13,0.98) 0%,rgba(26,26,26,0.95) 100%);backdrop-filter:blur(20px);border-bottom:2px solid var(--primary-blue);box-shadow:var(--glow);z-index:9999}
.nav-container{max-width:1200px;margin:0 auto;padding:0 2rem;height:100%;display:flex;justify-content:space-between;align-items:center}
.nav-logo{display:flex;align-items:center;text-decoration:none}
.nav-logo img{height:50px;margin-right:0.75rem;filter:drop-shadow(var(--glow))}
.nav-logo-text{font-family:'Orbitron',sans-serif;font-size:1.5rem;font-weight:700;color:var(--primary-blue);letter-spacing:3px;text-shadow:var(--glow)}
.nav-menu{display:flex;list-style:none;gap:1.5rem;align-items:center}
.nav-link{color:var(--chrome);text-decoration:none;font-family:'Rajdhani',sans-serif;font-size:1rem;font-weight:600;letter-spacing:1px;text-transform:uppercase;transition:all 0.3s;padding:0.5rem 0;position:relative}
.nav-link::after{content:'';position:absolute;bottom:0;left:50%;width:0;height:2px;background:var(--primary-blue);transition:all 0.3s;transform:translateX(-50%)}
.nav-link:hover,.nav-link.active{color:var(--primary-blue)}
.nav-link:hover::after,.nav-link.active::after{width:100%}
.nav-btn{background:linear-gradient(135deg,var(--primary-blue),var(--electric-blue));color:var(--carbon);padding:0.6rem 1.2rem;border-radius:4px;text-decoration:none;font-family:'Rajdhani',sans-serif;font-weight:700;font-size:0.9rem;text-transform:uppercase;display:flex;align-items:center;gap:0.5rem;transition:all 0.3s;box-shadow:var(--glow)}
.nav-btn:hover{transform:translateY(-2px)}
.mobile-toggle{display:none;background:none;border:none;cursor:pointer;padding:0.5rem;z-index:10001}
.mobile-toggle span{display:block;width:25px;height:3px;background:var(--primary-blue);margin:5px 0;border-radius:2px;transition:all 0.3s}
.mobile-toggle.active span:nth-child(1){transform:rotate(45deg) translate(5px,5px)}
.mobile-toggle.active span:nth-child(2){opacity:0}
.mobile-toggle.active span:nth-child(3){transform:rotate(-45deg) translate(7px,-6px)}
.mobile-menu{display:none;position:fixed;top:var(--nav-height);left:0;width:100%;background:linear-gradient(180deg,rgba(13,13,13,0.98) 0%,rgba(26,26,26,0.98) 100%);backdrop-filter:blur(20px);padding:1rem 0;border-bottom:2px solid var(--primary-blue);box-shadow:var(--glow);z-index:9998}
.mobile-menu.active{display:block}
.mobile-menu a{display:block;color:var(--chrome);text-decoration:none;font-family:'Rajdhani',sans-serif;font-size:1.1rem;font-weight:600;letter-spacing:1px;text-transform:uppercase;padding:1rem 2rem;transition:all 0.3s;border-bottom:1px solid #222}
.mobile-menu a:hover,.mobile-menu a.active{color:var(--primary-blue);background:rgba(0,191,255,0.1)}
.mobile-menu a:last-child{border-bottom:none}
.mobile-menu .mobile-discord{background:linear-gradient(135deg,var(--primary-blue),var(--electric-blue));color:var(--carbon);margin:1rem;border-radius:4px;text-align:center;border-bottom:none}
@media(max-width:900px){.nav-menu{display:none}.mobile-toggle{display:block}}
</style>
<style>
.container{max-width:1000px;margin:0 auto;padding:2rem}
h1{font-family:'Orbitron',sans-serif;font-size:2.5rem;color:#fff;text-align:center;margin-bottom:0.5rem;text-shadow:var(--glow)}
.subtitle{text-align:center;color:var(--primary-blue);font-size:1.2rem;margin-bottom:3rem}
.mission-box{background:var(--carbon-dark);border:1px solid var(--primary-blue);border-radius:8px;padding:2rem;margin-bottom:3rem;text-align:center}
.mission-box p{font-size:1.1rem;line-height:1.8;margin-bottom:1rem}
.stats{display:flex;justify-content:center;gap:2rem;flex-wrap:wrap;margin:2rem 0}
.stat{text-align:center}
.stat-value{font-family:'Orbitron',sans-serif;font-size:1.8rem;color:var(--primary-blue);text-shadow:var(--glow)}
.stat-label{font-size:0.9rem;color:var(--chrome)}
h2{font-family:'Orbitron',sans-serif;font-size:1.5rem;color:#fff;margin:2rem 0 1rem;text-align:center}
.payment-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:2rem;margin-bottom:3rem}
.payment-card{background:var(--carbon-dark);border:1px solid #333;border-radius:12px;padding:2rem;text-align:center;transition:all 0.3s}
.payment-card:hover{border-color:var(--primary-blue);transform:translateY(-5px);box-shadow:var(--glow)}
.payment-card i{font-size:2.5rem;margin-bottom:1rem}
.payment-card.cashapp i{color:#00D632}
.payment-card.paypal i{color:#003087}
.payment-card.zelle i{color:#6D1ED4}
.payment-card h3{font-family:'Rajdhani',sans-serif;font-size:1.4rem;color:#fff;margin-bottom:0.5rem}
.payment-card .handle{font-family:'Orbitron',sans-serif;font-size:1rem;color:var(--neon-blue);margin-bottom:1rem}
.payment-card .qr-code{background:#fff;padding:10px;border-radius:8px;display:inline-block;margin:1rem 0}
.payment-card .qr-code img{width:180px;height:180px;display:block}
.other-support{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1.5rem;margin:2rem 0}
.support-card{background:var(--carbon-dark);border:1px solid #333;border-radius:8px;padding:1.5rem;text-align:center;transition:all 0.3s}
.support-card:hover{border-color:var(--primary-blue);box-shadow:var(--glow)}
.support-card i{font-size:2rem;color:var(--primary-blue);margin-bottom:0.75rem}
.support-card.patreon i{color:#FF424D}
.support-card h3{font-family:'Rajdhani',sans-serif;font-size:1.1rem;color:#fff;margin-bottom:0.5rem}
.support-card p{font-size:0.9rem;color:#888}
.btn{display:inline-block;background:linear-gradient(135deg,var(--primary-blue),var(--electric-blue));color:var(--carbon);padding:0.6rem 1.5rem;border-radius:4px;text-decoration:none;font-family:'Rajdhani',sans-serif;font-weight:700;text-transform:uppercase;letter-spacing:1px;transition:all 0.3s;margin-top:0.5rem;font-size:0.9rem}
.btn:hover{transform:translateY(-2px);box-shadow:var(--glow)}
.btn.patreon{background:linear-gradient(135deg,#FF424D,#FF6B6B)}
.tiktok-section{text-align:center;margin:3rem 0;background:var(--carbon-dark);border-radius:12px;padding:2rem}
.tiktok-section img{max-width:280px;border-radius:12px;border:2px solid var(--primary-blue);box-shadow:var(--glow)}
.why-section{background:var(--carbon-dark);border-radius:8px;padding:2rem;margin:2rem 0}
.why-section ul{list-style:none;padding:0;display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1rem}
.why-section li{padding:1rem;background:rgba(0,191,255,0.05);border-radius:8px;display:flex;align-items:center;gap:1rem}
.why-section li i{color:var(--primary-blue);font-size:1.3rem}
</style>
</head>
<body>
<nav class="od9-nav"><div class="nav-container">
<a href="index.php" class="nav-logo"><img src="images/logos/od9-logo.png" alt="OD9"><span class="nav-logo-text">OD9</span></a>
<ul class="nav-menu">
<li><a href="index.php" class="nav-link ">Home</a></li>
<li><a href="framework.php" class="nav-link ">Framework</a></li>
<li><a href="tiers.php" class="nav-link ">Tiers</a></li>
<li><a href="da-crew.php" class="nav-link ">Da Crew</a></li>
<li><a href="music.php" class="nav-link ">Music</a></li>
<li><a href="resources.php" class="nav-link ">Resources</a></li>
<li><a href="support.php" class="nav-link active">Support</a></li>
<li><a href="contact.php" class="nav-link ">Contact</a></li>
<li><a href="https://discord.gg/spgmrXVMWq" target="_blank" class="nav-btn"><i class="fab fa-discord"></i> Discord</a></li>
</ul>
<button class="mobile-toggle" id="hamburger"><span></span><span></span><span></span></button>
</div></nav>
<div class="mobile-menu" id="mobileMenu">
<a href="index.php" class="">Home</a>
<a href="framework.php" class="">Framework</a>
<a href="tiers.php" class="">Tiers</a>
<a href="da-crew.php" class="">Da Crew</a>
<a href="music.php" class="">Music</a>
<a href="resources.php" class="">Resources</a>
<a href="support.php" class="active">Support</a>
<a href="contact.php" class="">Contact</a>
<a href="https://discord.gg/spgmrXVMWq" target="_blank" class="mobile-discord"><i class="fab fa-discord"></i> Join Discord</a>
</div>
<script>
document.getElementById("hamburger").addEventListener("click", function() {
    this.classList.toggle("active");
    document.getElementById("mobileMenu").classList.toggle("active");
});
</script>
<div class="container">

<h1>SUPPORT OD9</h1>
<p class="subtitle">Help Build the Future of Human Advancement</p>

<div class="mission-box">
<p>OD9 is building tools to advance humanity toward Type I civilization through STEAM education and community engagement. Every contribution directly funds development, content creation, and infrastructure.</p>
<div class="stats">
<div class="stat"><div class="stat-value">Top 5%</div><div class="stat-label">TikTok LIVE Creator</div></div>
<div class="stat"><div class="stat-value">2.6M</div><div class="stat-label">Likes in 2025</div></div>
<div class="stat"><div class="stat-value">207</div><div class="stat-label">Live Streams</div></div>
<div class="stat"><div class="stat-value">20K</div><div class="stat-label">Diamonds Earned</div></div>
</div>
</div>

<h2>SCAN TO SUPPORT</h2>
<div class="payment-grid">
<div class="payment-card cashapp">
<i class="fab fa-square-cash-app"></i>
<h3>Cash App</h3>
<div class="handle">$TheUltimateRage</div>
<div class="qr-code">
<img src="images/payment/cashapp-qr.jpg" alt="Cash App QR Code">
</div>
</div>

<div class="payment-card paypal">
<i class="fab fa-paypal"></i>
<h3>PayPal</h3>
<div class="handle">@TheUltimateRage</div>
<div class="qr-code">
<img src="images/payment/paypal-qr.jpg" alt="PayPal QR Code">
</div>
</div>

<div class="payment-card zelle">
<i class="fas fa-bolt"></i>
<h3>Zelle</h3>
<div class="handle">Scan to Pay</div>
<div class="qr-code">
<img src="images/payment/zelle-qr.jpg" alt="Zelle QR Code">
</div>
</div>
</div>

<h2>OTHER WAYS TO SUPPORT</h2>
<div class="other-support">
<div class="support-card patreon">
<i class="fab fa-patreon"></i>
<h3>Patreon</h3>
<p>Monthly support + exclusive perks</p>
<a href="https://www.patreon.com/c/TheUltimateRage" target="_blank" class="btn patreon">Join Patreon</a>
</div>

<div class="support-card">
<i class="fab fa-tiktok"></i>
<h3>TikTok</h3>
<p>Follow & send gifts during Lives</p>
<a href="https://tiktok.com/@theultimaterage079" target="_blank" class="btn">Follow</a>
</div>

<div class="support-card">
<i class="fab fa-discord"></i>
<h3>Discord</h3>
<p>Join the community (free!)</p>
<a href="https://discord.gg/spgmrXVMWq" target="_blank" class="btn">Join</a>
</div>

<div class="support-card">
<i class="fab fa-youtube"></i>
<h3>YouTube</h3>
<p>Subscribe & watch</p>
<a href="https://youtube.com/@theultimaterage" target="_blank" class="btn">Subscribe</a>
</div>
</div>

<div class="tiktok-section">
<h2>PRIME CREATOR STATUS</h2>
<p style="margin-bottom:1rem;color:#888">TikTok verified - Top 5% of all LIVE creators in 2025</p>
<img src="images/tiktok-wrapped-2025.jpg" alt="TikTok 2025 Wrapped - Prime Creator">
</div>

<div class="why-section">
<h2>WHAT YOUR SUPPORT FUNDS</h2>
<ul>
<li><i class="fas fa-robot"></i> Discord bot development & AI tools</li>
<li><i class="fas fa-server"></i> Server hosting & infrastructure</li>
<li><i class="fas fa-code"></i> Claude Max Plan for faster development</li>
<li><i class="fas fa-video"></i> Content creation equipment</li>
<li><i class="fas fa-graduation-cap"></i> STEAM education resources</li>
<li><i class="fas fa-rocket"></i> Building tools to advance humanity</li>
</ul>
</div>

</div>
<footer style="text-align:center;margin-top:3rem;padding:2rem;border-top:1px solid #333">
<div style="margin-bottom:1rem">
<a href="index.php" style="color:#00BFFF;text-decoration:none;margin:0 0.75rem">Home</a>
<a href="framework.php" style="color:#00BFFF;text-decoration:none;margin:0 0.75rem">Framework</a>
<a href="tiers.php" style="color:#00BFFF;text-decoration:none;margin:0 0.75rem">Tiers</a>
<a href="da-crew.php" style="color:#00BFFF;text-decoration:none;margin:0 0.75rem">Da Crew</a>
<a href="music.php" style="color:#00BFFF;text-decoration:none;margin:0 0.75rem">Music</a>
<a href="resources.php" style="color:#00BFFF;text-decoration:none;margin:0 0.75rem">Resources</a>
<a href="support.php" style="color:#00BFFF;text-decoration:none;margin:0 0.75rem">Support</a>
<a href="contact.php" style="color:#00BFFF;text-decoration:none;margin:0 0.75rem">Contact</a>
</div>
<div style="margin-bottom:1rem">
<a href="https://discord.gg/spgmrXVMWq" target="_blank" style="color:#666;margin:0 0.5rem;font-size:1.3rem"><i class="fab fa-discord"></i></a>
<a href="https://tiktok.com/@theultimaterage079" target="_blank" style="color:#666;margin:0 0.5rem;font-size:1.3rem"><i class="fab fa-tiktok"></i></a>
<a href="https://youtube.com/@theultimaterage" target="_blank" style="color:#666;margin:0 0.5rem;font-size:1.3rem"><i class="fab fa-youtube"></i></a>
<a href="https://www.patreon.com/c/TheUltimateRage" target="_blank" style="color:#666;margin:0 0.5rem;font-size:1.3rem"><i class="fab fa-patreon"></i></a>
</div>
<p style="color:#555;font-size:0.85rem">&copy; 2026 OD9 LLC. All rights reserved.</p>
<p style="color:#444;font-size:0.75rem;margin-top:0.5rem"><a href="terms.php" style="color:#444">Terms</a> | <a href="privacy.php" style="color:#444">Privacy</a></p>
</footer>
</body>
</html>
