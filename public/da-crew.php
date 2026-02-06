<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Da Crew - OD9 Music Collective</title>
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
.container{max-width:1100px;margin:0 auto;padding:2rem}
h1{font-family:'Orbitron',sans-serif;font-size:3rem;color:#fff;text-align:center;margin-bottom:0.5rem;text-shadow:var(--glow)}
.subtitle{text-align:center;color:var(--primary-blue);font-size:1.3rem;margin-bottom:3rem;font-family:'Rajdhani',sans-serif;letter-spacing:3px}
.intro{background:var(--carbon-dark);border:1px solid var(--primary-blue);border-radius:12px;padding:2.5rem;margin-bottom:3rem;text-align:center}
.intro p{font-size:1.15rem;line-height:1.9;max-width:800px;margin:0 auto}
h2{font-family:'Orbitron',sans-serif;font-size:1.8rem;color:#fff;margin:3rem 0 1.5rem;text-align:center;text-shadow:var(--glow)}
.members-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1.5rem;margin-bottom:3rem}
.member-card{background:var(--carbon-dark);border:1px solid #333;border-radius:12px;overflow:hidden;transition:all 0.3s}
.member-card:hover{border-color:var(--primary-blue);transform:translateY(-5px);box-shadow:var(--glow)}
.member-card.founder{border-color:var(--primary-blue)}
.member-photo{width:100%;height:220px;background:linear-gradient(135deg,var(--carbon-dark),#222);overflow:hidden}
.member-photo img{width:100%;height:100%;object-fit:cover;object-position:center top;transition:transform 0.3s}
.member-card:hover .member-photo img{transform:scale(1.05)}
.member-info{padding:1.25rem;text-align:center}
.member-info h3{font-family:'Orbitron',sans-serif;font-size:1.2rem;color:var(--primary-blue);margin-bottom:0.3rem}
.member-info .role{font-family:'Rajdhani',sans-serif;color:var(--neon-blue);text-transform:uppercase;letter-spacing:2px;font-size:0.85rem;margin-bottom:0.75rem}
.member-card.founder .role{color:#FFD700}
.social-links{display:flex;justify-content:center;gap:0.5rem;flex-wrap:wrap}
.social-links a{color:#888;font-size:1.1rem;transition:color 0.3s;padding:0.25rem}
.social-links a:hover{color:var(--primary-blue)}
.cta-section{text-align:center;margin:3rem 0;padding:2rem;background:linear-gradient(135deg,rgba(0,191,255,0.1),transparent);border-radius:12px;border:1px solid var(--primary-blue)}
.cta-section h3{font-family:'Orbitron',sans-serif;color:#fff;margin-bottom:1rem}
.btn{display:inline-block;background:linear-gradient(135deg,var(--primary-blue),var(--electric-blue));color:var(--carbon);padding:0.8rem 2rem;border-radius:4px;text-decoration:none;font-family:'Rajdhani',sans-serif;font-weight:700;text-transform:uppercase;letter-spacing:2px;transition:all 0.3s}
.btn:hover{transform:translateY(-3px);box-shadow:var(--glow)}
.music-section{margin:3rem 0}
.ep-promo{display:grid;grid-template-columns:1fr 1fr;gap:2rem;align-items:center;background:var(--carbon-dark);border-radius:12px;padding:2rem;border:1px solid var(--primary-blue);margin-bottom:2rem}
.ep-promo video{width:100%;border-radius:8px;box-shadow:var(--glow)}
.ep-info{text-align:center}
.ep-info h3{font-family:'Orbitron',sans-serif;font-size:2rem;color:#fff;margin-bottom:0.5rem;text-shadow:var(--glow)}
.ep-info .coming-soon-badge{display:inline-block;background:linear-gradient(135deg,var(--primary-blue),var(--electric-blue));color:var(--carbon);padding:0.5rem 1.5rem;border-radius:20px;font-family:'Rajdhani',sans-serif;font-weight:700;text-transform:uppercase;letter-spacing:2px;margin-top:1rem;animation:pulse 2s infinite}
@keyframes pulse{0%,100%{box-shadow:0 0 10px rgba(0,191,255,0.5)}50%{box-shadow:0 0 25px rgba(0,191,255,0.8)}}
.music-videos{display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:1.5rem}
.video-card{background:var(--carbon-dark);border:1px solid #333;border-radius:12px;overflow:hidden;transition:all 0.3s}
.video-card:hover{border-color:var(--primary-blue);transform:translateY(-5px);box-shadow:var(--glow)}
.video-thumb{width:100%;aspect-ratio:16/9;object-fit:cover;display:block}
.video-info{padding:1.25rem;text-align:center}
.video-info h4{font-family:'Orbitron',sans-serif;font-size:1.3rem;color:#fff;margin-bottom:0.5rem}
.video-info .status{font-family:'Rajdhani',sans-serif;color:var(--neon-blue);text-transform:uppercase;letter-spacing:2px;font-size:0.9rem}
@media(max-width:768px){.ep-promo{grid-template-columns:1fr}}
</style>
</head>
<body>
<nav class="od9-nav"><div class="nav-container">
<a href="index.php" class="nav-logo"><img src="images/logos/od9-logo.png" alt="OD9"><span class="nav-logo-text">OD9</span></a>
<ul class="nav-menu">
<li><a href="index.php" class="nav-link ">Home</a></li>
<li><a href="framework.php" class="nav-link ">Framework</a></li>
<li><a href="tiers.php" class="nav-link ">Tiers</a></li>
<li><a href="da-crew.php" class="nav-link active">Da Crew</a></li>
<li><a href="music.php" class="nav-link ">Music</a></li>
<li><a href="resources.php" class="nav-link ">Resources</a></li>
<li><a href="support.php" class="nav-link ">Support</a></li>
<li><a href="contact.php" class="nav-link ">Contact</a></li>
<li><a href="https://discord.gg/spgmrXVMWq" target="_blank" class="nav-btn"><i class="fab fa-discord"></i> Discord</a></li>
</ul>
<button class="mobile-toggle" id="hamburger"><span></span><span></span><span></span></button>
</div></nav>
<div class="mobile-menu" id="mobileMenu">
<a href="index.php" class="">Home</a>
<a href="framework.php" class="">Framework</a>
<a href="tiers.php" class="">Tiers</a>
<a href="da-crew.php" class="active">Da Crew</a>
<a href="music.php" class="">Music</a>
<a href="resources.php" class="">Resources</a>
<a href="support.php" class="">Support</a>
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
<h1>DA CREW</h1>
<p class="subtitle">OD9 Music Collective</p>

<div class="intro">
<p>OD9 isn't just a movement for human advancement - it's also a music collective bringing raw, unfiltered sound from the streets of Chicago. We blend futuristic vision with authentic expression, creating music that hits different.</p>
</div>

<h2>THE ROSTER</h2>
<div class="members-grid">

<div class="member-card founder">
<div class="member-photo"><img src="images/crew/rage.png" alt="The Ultimate Rage"></div>
<div class="member-info">
<h3>The Ultimate Rage</h3>
<div class="role">Co-Founder</div>
<div class="social-links">
<a href="https://instagram.com/theultimaterage" target="_blank" title="Instagram"><i class="fab fa-instagram"></i></a>
<a href="https://tiktok.com/@theultimaterage079" target="_blank" title="TikTok"><i class="fab fa-tiktok"></i></a>
<a href="https://youtube.com/@theultimaterage" target="_blank" title="YouTube"><i class="fab fa-youtube"></i></a>
<a href="https://open.spotify.com/artist/0QvH8H7obaMerk1UkfFGaD" target="_blank" title="Spotify"><i class="fab fa-spotify"></i></a>
<a href="https://soundcloud.com/theultimaterage" target="_blank" title="SoundCloud"><i class="fab fa-soundcloud"></i></a>
<a href="https://facebook.com/theultimaterage" target="_blank" title="Facebook"><i class="fab fa-facebook"></i></a>
<a href="https://twitter.com/theultimat63157" target="_blank" title="X/Twitter"><i class="fab fa-x-twitter"></i></a>
<a href="https://theultimaterage.substack.com" target="_blank" title="Substack"><i class="fas fa-newspaper"></i></a>
</div>
</div>
</div>

<div class="member-card founder">
<div class="member-photo"><img src="images/crew/deez.jpg" alt="Deezle Deez"></div>
<div class="member-info">
<h3>Deezle Deez</h3>
<div class="role">Co-Founder</div>
<div class="social-links">
<a href="https://instagram.com/deezle_deez" target="_blank" title="Instagram"><i class="fab fa-instagram"></i></a>
<a href="https://facebook.com/deezle.deez" target="_blank" title="Facebook"><i class="fab fa-facebook"></i></a>
</div>
</div>
</div>

<div class="member-card">
<div class="member-photo"><img src="images/crew/p-mac.png" alt="P-Mac"></div>
<div class="member-info">
<h3>P-Mac</h3>
<div class="role">Artist</div>
<div class="social-links">
<a href="https://instagram.com/perryjay22" target="_blank" title="Instagram"><i class="fab fa-instagram"></i></a>
<a href="https://facebook.com/perryjayjayr" target="_blank" title="Facebook"><i class="fab fa-facebook"></i></a>
</div>
</div>
</div>

<div class="member-card">
<div class="member-photo"><img src="images/crew/lb.jpg" alt="L.B."></div>
<div class="member-info">
<h3>L.B.</h3>
<div class="role">Artist</div>
<div class="social-links">
<a href="https://instagram.com/iambilla" target="_blank" title="Instagram"><i class="fab fa-instagram"></i></a>
<a href="https://tiktok.com/@iambilla079" target="_blank" title="TikTok"><i class="fab fa-tiktok"></i></a>
<a href="https://facebook.com/lb079" target="_blank" title="Facebook"><i class="fab fa-facebook"></i></a>
</div>
</div>
</div>

<div class="member-card">
<div class="member-photo"><img src="images/crew/joey-p.jpg" alt="Joey P."></div>
<div class="member-info">
<h3>Joey P.</h3>
<div class="role">Artist</div>
<div class="social-links">
<a href="https://instagram.com/jpacius_sm" target="_blank" title="Instagram"><i class="fab fa-instagram"></i></a>
<a href="https://youtube.com/@joeyp." target="_blank" title="YouTube"><i class="fab fa-youtube"></i></a>
<a href="https://open.spotify.com/artist/2a1dff70K0i5OZXBkYQOzZ" target="_blank" title="Spotify"><i class="fab fa-spotify"></i></a>
<a href="https://facebook.com/young.joseph.307539" target="_blank" title="Facebook"><i class="fab fa-facebook"></i></a>
</div>
</div>
</div>

</div>

<h2>MUSIC</h2>
<div class="music-section">
<div class="ep-promo">
<video autoplay loop muted playsinline>
<source src="images/music/supreme-elevation-promo.mp4" type="video/mp4">
</video>
<div class="ep-info">
<h3>SUPREME ELEVATION</h3>
<p style="color:#888;margin-bottom:1rem">The debut EP from Da Crew</p>
<span class="coming-soon-badge">Coming Soon</span>
</div>
</div>

<h3 style="font-family:'Orbitron',sans-serif;color:#fff;text-align:center;margin:2rem 0 1.5rem">MUSIC VIDEOS</h3>
<div class="music-videos">
<div class="video-card">
<img src="images/music/supreme-screenshot.png" alt="Supreme" class="video-thumb">
<div class="video-info">
<h4>SUPREME</h4>
<div class="status">Music Video Coming Soon</div>
</div>
</div>

<div class="video-card">
<img src="images/music/hwul-screenshot.png" alt="HWUL" class="video-thumb">
<div class="video-info">
<h4>HWUL</h4>
<div class="status">Music Video Coming Soon</div>
</div>
</div>
</div>
</div>

<div class="cta-section">
<h3>CONNECT WITH DA CREW</h3>
<p style="margin-bottom:1.5rem;color:#888">Join the Discord to stay updated on new music and events</p>
<a href="https://discord.gg/spgmrXVMWq" target="_blank" class="btn"><i class="fab fa-discord" style="margin-right:0.5rem"></i> Join Discord</a>
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
