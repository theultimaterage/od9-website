<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Progression Tiers - OD9</title>
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
.subtitle{text-align:center;color:var(--primary-blue);font-size:1.2rem;margin-bottom:1rem}
.intro{text-align:center;max-width:700px;margin:0 auto 3rem;line-height:1.8}
.tier{background:var(--carbon-dark);border-radius:12px;padding:2rem;margin-bottom:1.5rem;border-left:4px solid;display:grid;grid-template-columns:auto 1fr;gap:1.5rem;align-items:center}
.tier.observer{border-color:#808080}
.tier.theorist{border-color:#4169E1}
.tier.architect{border-color:#9932CC}
.tier.pioneer{border-color:#FFD700}
.tier-icon{width:60px;height:60px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.5rem}
.tier.observer .tier-icon{background:rgba(128,128,128,0.2);color:#808080}
.tier.theorist .tier-icon{background:rgba(65,105,225,0.2);color:#4169E1}
.tier.architect .tier-icon{background:rgba(153,50,204,0.2);color:#9932CC}
.tier.pioneer .tier-icon{background:rgba(255,215,0,0.2);color:#FFD700}
.tier h2{font-family:'Orbitron',sans-serif;font-size:1.3rem;color:#fff;margin-bottom:0.25rem}
.tier .credits{font-size:0.85rem;color:#888;margin-bottom:0.5rem}
.tier p{font-size:0.95rem;line-height:1.6}
.how-box{background:var(--carbon-dark);border-radius:12px;padding:2rem;margin-top:2rem;border:1px solid var(--primary-blue)}
.how-box h2{font-family:'Orbitron',sans-serif;color:#fff;margin-bottom:1.5rem;text-align:center}
.steps{display:grid;grid-template-columns:repeat(4,1fr);gap:1rem}
.step{text-align:center;padding:1rem}
.step-num{width:36px;height:36px;background:var(--primary-blue);color:var(--carbon);border-radius:50%;display:flex;align-items:center;justify-content:center;font-family:'Orbitron',sans-serif;font-weight:700;margin:0 auto 0.75rem}
.step h4{font-family:'Rajdhani',sans-serif;color:#fff;margin-bottom:0.25rem}
.step p{font-size:0.85rem;color:#888}
.cta{text-align:center;margin-top:2rem}
.btn{display:inline-block;background:linear-gradient(135deg,var(--primary-blue),var(--electric-blue));color:var(--carbon);padding:0.8rem 2rem;border-radius:4px;text-decoration:none;font-family:'Rajdhani',sans-serif;font-weight:700;text-transform:uppercase}
@media(max-width:768px){.tier{grid-template-columns:1fr}.steps{grid-template-columns:repeat(2,1fr)}}
</style>
</head>
<body>
<nav class="od9-nav"><div class="nav-container">
<a href="index.php" class="nav-logo"><img src="images/logos/od9-logo.png" alt="OD9"><span class="nav-logo-text">OD9</span></a>
<ul class="nav-menu">
<li><a href="index.php" class="nav-link ">Home</a></li>
<li><a href="framework.php" class="nav-link ">Framework</a></li>
<li><a href="tiers.php" class="nav-link active">Tiers</a></li>
<li><a href="da-crew.php" class="nav-link ">Da Crew</a></li>
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
<a href="tiers.php" class="active">Tiers</a>
<a href="da-crew.php" class="">Da Crew</a>
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
<h1>PROGRESSION TIERS</h1>
<p class="subtitle">Your Path to Pioneer</p>
<p class="intro">Advance by engaging with content, participating in discussions, and helping grow the community.</p>

<div class="tier observer">
<div class="tier-icon"><i class="fas fa-eye"></i></div>
<div><h2>Observer</h2><div class="credits">Entry Level</div><p>Learn the framework, understand the mission. Earn 150 credits to advance.</p></div>
</div>

<div class="tier theorist">
<div class="tier-icon"><i class="fas fa-brain"></i></div>
<div><h2>Theorist</h2><div class="credits">150 Credits</div><p>Synthesize ideas, contribute to discussions. Access Theorist channels. Earn 250 to advance.</p></div>
</div>

<div class="tier architect">
<div class="tier-icon"><i class="fas fa-drafting-compass"></i></div>
<div><h2>Architect</h2><div class="credits">250 Credits</div><p>Build solutions, create content, lead projects. Earn 400 to advance.</p></div>
</div>

<div class="tier pioneer">
<div class="tier-icon"><i class="fas fa-star"></i></div>
<div><h2>Pioneer</h2><div class="credits">400 Credits</div><p>Community leaders. Governance voting, direct input on OD9 direction.</p></div>
</div>

<div class="how-box">
<h2>How to Earn Credits</h2>
<div class="steps">
<div class="step"><div class="step-num">1</div><h4>Engage</h4><p>Complete content</p></div>
<div class="step"><div class="step-num">2</div><h4>Discuss</h4><p>Quality participation</p></div>
<div class="step"><div class="step-num">3</div><h4>Refer</h4><p>Bring members</p></div>
<div class="step"><div class="step-num">4</div><h4>Create</h4><p>Contribute projects</p></div>
</div>
</div>

<div class="cta"><a href="https://discord.gg/spgmrXVMWq" target="_blank" class="btn"><i class="fab fa-discord"></i> Start Your Journey</a></div>
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
