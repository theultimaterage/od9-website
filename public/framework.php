<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>The Framework - OD9</title>
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
.container{max-width:900px;margin:0 auto;padding:2rem}
h1{font-family:'Orbitron',sans-serif;font-size:2.5rem;color:#fff;text-align:center;margin-bottom:0.5rem;text-shadow:var(--glow)}
.subtitle{text-align:center;color:var(--primary-blue);font-size:1.2rem;margin-bottom:3rem}
.section{background:var(--carbon-dark);border-radius:12px;padding:2rem;margin-bottom:2rem;border-left:4px solid var(--primary-blue)}
.section.problem{border-left-color:#FF4444}
.section.approach{border-left-color:#00FF88}
.section.why{border-left-color:#FFD700}
.section.action{border-left-color:#9932CC}
.section h2{font-family:'Orbitron',sans-serif;font-size:1.4rem;color:var(--primary-blue);margin-bottom:1rem}
.section.problem h2{color:#FF4444}
.section.approach h2{color:#00FF88}
.section.why h2{color:#FFD700}
.section.action h2{color:#9932CC}
.section p{line-height:1.9;margin-bottom:1rem;color:#bbb}
.section p strong{color:#fff}
.section ul{padding-left:0;margin:1rem 0;list-style:none}
.section li{margin-bottom:0.75rem;line-height:1.7;padding-left:1.5rem;position:relative;color:#aaa}
.section li::before{content:'';position:absolute;left:0;top:0.6rem;width:8px;height:8px;background:var(--primary-blue);border-radius:50%}
.section.problem li::before{background:#FF4444}
.section.approach li::before{background:#00FF88}
.section.why li::before{background:#FFD700}
.big-question{font-family:'Rajdhani',sans-serif;font-size:1.3rem;color:var(--primary-blue);text-align:center;margin:1.5rem 0;font-weight:600}

.subsystem{background:rgba(0,255,136,0.05);border:1px solid #333;border-radius:8px;padding:1.5rem;margin:1.5rem 0}
.subsystem h3{font-family:'Orbitron',sans-serif;font-size:1.1rem;color:#00FF88;margin-bottom:0.75rem}
.subsystem p{margin-bottom:0.75rem}
.subsystem ul{margin:0.75rem 0}

.tier-flow{display:flex;align-items:center;justify-content:center;flex-wrap:wrap;gap:0.5rem;margin:1rem 0;font-family:'Rajdhani',sans-serif;font-weight:600}
.tier-flow span{padding:0.5rem 1rem;border-radius:4px;font-size:0.95rem}
.tier-flow .observer{background:rgba(128,128,128,0.2);color:#808080}
.tier-flow .theorist{background:rgba(65,105,225,0.2);color:#4169E1}
.tier-flow .architect{background:rgba(153,50,204,0.2);color:#9932CC}
.tier-flow .pioneer{background:rgba(255,215,0,0.2);color:#FFD700}
.tier-flow .benefactor{background:rgba(0,255,136,0.2);color:#00FF88}
.tier-flow .arrow{color:#444;font-size:1.2rem}

.compare-grid{display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin:1.5rem 0}
.compare-box{padding:1.25rem;border-radius:8px}
.compare-box.fail{background:rgba(255,68,68,0.1);border:1px solid rgba(255,68,68,0.3)}
.compare-box.win{background:rgba(0,255,136,0.1);border:1px solid rgba(0,255,136,0.3)}
.compare-box h4{font-family:'Orbitron',sans-serif;font-size:0.95rem;margin-bottom:0.75rem}
.compare-box.fail h4{color:#FF4444}
.compare-box.win h4{color:#00FF88}
.compare-box ul{margin:0;font-size:0.95rem}

.action-steps{counter-reset:step}
.action-step{display:flex;align-items:flex-start;gap:1rem;margin-bottom:1rem;padding:1rem;background:rgba(153,50,204,0.1);border-radius:8px}
.action-step::before{counter-increment:step;content:counter(step);font-family:'Orbitron',sans-serif;font-size:1.5rem;font-weight:700;color:#9932CC;min-width:2rem}
.action-step p{margin:0}
.action-step strong{color:#fff}

.closing{text-align:center;font-family:'Rajdhani',sans-serif;font-size:1.2rem;color:#aaa;margin:2rem 0;line-height:1.8}
.closing strong{color:var(--primary-blue)}

.cta{text-align:center;margin:3rem 0;padding:2rem;background:linear-gradient(135deg,rgba(0,191,255,0.1),transparent);border-radius:12px;border:1px solid var(--primary-blue)}
.cta h3{font-family:'Orbitron',sans-serif;color:#fff;margin-bottom:1rem}
.btn{display:inline-block;background:linear-gradient(135deg,var(--primary-blue),var(--electric-blue));color:var(--carbon);padding:0.8rem 2rem;border-radius:4px;text-decoration:none;font-family:'Rajdhani',sans-serif;font-weight:700;text-transform:uppercase;letter-spacing:2px;transition:all 0.3s}
.btn:hover{transform:translateY(-3px);box-shadow:var(--glow)}

@media(max-width:600px){
.compare-grid{grid-template-columns:1fr}
.tier-flow{flex-direction:column}
.tier-flow .arrow{transform:rotate(90deg)}
}
</style>
</head>
<body>
<nav class="od9-nav"><div class="nav-container">
<a href="index.php" class="nav-logo"><img src="images/logos/od9-logo.png" alt="OD9"><span class="nav-logo-text">OD9</span></a>
<ul class="nav-menu">
<li><a href="index.php" class="nav-link ">Home</a></li>
<li><a href="framework.php" class="nav-link active">Framework</a></li>
<li><a href="tiers.php" class="nav-link ">Tiers</a></li>
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
<a href="framework.php" class="active">Framework</a>
<a href="tiers.php" class="">Tiers</a>
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
<h1>THE FRAMEWORK</h1>
<p class="subtitle">Understanding OD9's Mission</p>

<div class="section problem">
<h2><i class="fas fa-exclamation-triangle"></i> The Problem: Why Coordination Fails</h2>
<p>We have the knowledge and resources to solve climate change, poverty, and existential risks. What we lack is the ability to <strong>coordinate at scale</strong>.</p>
<p class="big-question">Why can't 8 billion intelligent humans work together on planetary problems?</p>
<p><strong>Four systems maintain coordination failure:</strong></p>
<ul>
<li><strong>Theological Programming</strong> creates tribalism and prevents truth-seeking</li>
<li><strong>Economic Extraction</strong> optimizes for short-term profit over long-term survival</li>
<li><strong>Information Control</strong> keeps populations reactive instead of strategic</li>
<li><strong>Cognitive Impairment</strong> makes long-term thinking neurologically difficult</li>
</ul>
<p>These aren't bugs. They're <strong>features</strong> of systems designed to extract resources, not solve problems.</p>
</div>

<div class="section approach">
<h2><i class="fas fa-flask"></i> OD9's Approach: Test, Learn, Build</h2>
<p>We're not selling solutions. We're building infrastructure to discover what actually works through three interconnected systems:</p>

<div class="subsystem">
<h3>1. Education Through Progression <span style="font-size:0.7rem;color:#00FF88;margin-left:0.5rem">(ACTIVE)</span></h3>
<p>Discord community with a structured five-tier learning system powered by the <strong>ASCEND Protocol</strong>:</p>
<div class="tier-flow">
<span class="observer">Observer</span>
<span class="arrow">&rarr;</span>
<span class="theorist">Theorist</span>
<span class="arrow">&rarr;</span>
<span class="architect">Architect</span>
<span class="arrow">&rarr;</span>
<span class="pioneer">Pioneer</span>
<span class="arrow">&rarr;</span>
<span class="benefactor">Benefactor</span>
</div>
<ul>
<li><strong>Observer</strong> &rarr; Recognize coordination failures in current events</li>
<li><strong>Theorist</strong> &rarr; Understand root causes (not just symptoms)</li>
<li><strong>Architect</strong> &rarr; Design alternative coordination mechanisms</li>
<li><strong>Pioneer</strong> &rarr; Implement and test solutions</li>
<li><strong>Benefactor</strong> &rarr; Sustain and scale the mission long-term</li>
</ul>
<p style="margin-top:1.25rem;color:#00FF88;font-family:'Rajdhani',sans-serif;font-size:1.05rem;font-weight:600;">The ASCEND Protocol</p>
<p>Advancement isn't passive. You don't just read and click "done." The ASCEND Protocol requires you to <strong>demonstrate understanding</strong> at every step:</p>
<ul>
<li><strong>Reflective Comprehension</strong> - After engaging with content, you write a reflection on what you learned and how it connects to the mission. A higher-tier member reviews it before you earn credits.</li>
<li><strong>Peer Evaluation</strong> - Higher-tier members evaluate lower-tier discussion posts using a structured rubric. Teaching others deepens your own understanding.</li>
<li><strong>Discussion-Driven Growth</strong> - Submit analysis, synthesis, and original insights for community evaluation. Quality matters more than quantity.</li>
<li><strong>Multi-Dimensional Value</strong> - The system recognizes different types of contribution: knowledge, community building, teaching, and more. Your path is unique to your strengths.</li>
</ul>
<p>This is education that actually changes how you think, not just what you know. And it's only the beginning - the full ASCEND system goes much deeper.</p>
</div>

<div class="subsystem">
<h3>2. Simulation Through Metaworld <span style="font-size:0.7rem;color:#FFD700;margin-left:0.5rem">(FUTURE GOAL)</span></h3>
<p><strong>This is what we're building toward.</strong> We don't have Metaworld yet - we need community support to make it real. Once built, it will:</p>
<ul>
<li>Provide a virtual environment to experiment with economic rules</li>
<li>Show what happens when incentives align differently</li>
<li>Measure which governance structures prevent capture</li>
<li>Allow us to iterate fast, fail safely, learn continuously</li>
</ul>
<p>If it doesn't work in simulation with 100 people, it won't work at planetary scale with billions. <strong>Help us build it.</strong></p>
</div>

<div class="subsystem">
<h3>3. Culture Shift Through Art</h3>
<p>Music and content that create consciousness shifts:</p>
<ul>
<li>Reach people emotionally, not just intellectually</li>
<li>Make coordination theory accessible, not academic</li>
<li>Build culture around long-term thinking</li>
<li>Create identity beyond consumption</li>
</ul>
<p>Logic alone doesn't change behavior. <strong>Culture does.</strong></p>
</div>
</div>

<div class="section why">
<h2><i class="fas fa-lightbulb"></i> Why This Might Actually Work</h2>
<div class="compare-grid">
<div class="compare-box fail">
<h4>Traditional Approaches Fail</h4>
<ul>
<li>Activism demands change without testing alternatives</li>
<li>Academia studies problems without building solutions</li>
<li>Startups optimize for profit, not coordination</li>
<li>Politics operates on 2-4 year cycles, not generational timeframes</li>
</ul>
</div>
<div class="compare-box win">
<h4>OD9 Is Different</h4>
<ul>
<li><strong>Testable</strong> - Metaworld produces measurable results</li>
<li><strong>Transparent</strong> - All resources, decisions, and progress visible</li>
<li><strong>Iterative</strong> - Build, test, learn, improve, repeat</li>
<li><strong>Non-extractive</strong> - Free progression path, paid support funds infrastructure</li>
</ul>
</div>
</div>
<p>We're building the research platform that could prove better coordination is possible. Then making that research freely available.</p>
</div>

<div class="section action">
<h2><i class="fas fa-rocket"></i> What You'll Actually Do</h2>
<div class="action-steps">
<div class="action-step">
<p><strong>Join Discord</strong> &rarr; Start as Observer, learn why coordination fails</p>
</div>
<div class="action-step">
<p><strong>Progress through tiers</strong> &rarr; Analyze, design, build coordination solutions</p>
</div>
<div class="action-step">
<p><strong>Test in Metaworld</strong> &rarr; When built, see if your ideas work before real-world deployment</p>
</div>
<div class="action-step">
<p><strong>Contribute to research</strong> &rarr; Help discover what mechanisms actually solve coordination problems</p>
</div>
<div class="action-step">
<p><strong>Support the mission</strong> &rarr; <a href="support.php" style="color:#9932CC">Donate</a> to help fund the infrastructure we need to build</p>
</div>
</div>
</div>

<p class="closing">This isn't about believing. It's about <strong>testing, measuring, and proving</strong> what works.<br>The Great Filter kills civilizations that can't coordinate. We're building the tools to pass it.</p>

<div class="cta">
<h3>Ready to Begin?</h3>
<a href="https://discord.gg/spgmrXVMWq" target="_blank" class="btn"><i class="fab fa-discord"></i> Join Discord</a>
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
<style>
/* Email Signup Popup */
.email-popup-overlay{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.8);z-index:99999;justify-content:center;align-items:center}
.email-popup-overlay.active{display:flex}
.email-popup{background:var(--carbon-dark);border:2px solid var(--primary-blue);border-radius:16px;padding:2.5rem;max-width:450px;width:90%;position:relative;box-shadow:var(--glow);animation:popIn 0.3s ease}
@keyframes popIn{from{transform:scale(0.8);opacity:0}to{transform:scale(1);opacity:1}}
.email-popup-close{position:absolute;top:15px;right:15px;background:none;border:none;color:#666;font-size:1.5rem;cursor:pointer;transition:color 0.3s}
.email-popup-close:hover{color:var(--primary-blue)}
.email-popup h3{font-family:'Orbitron',sans-serif;font-size:1.5rem;color:#fff;text-align:center;margin-bottom:0.5rem}
.email-popup .tagline{text-align:center;color:var(--primary-blue);font-family:'Rajdhani',sans-serif;margin-bottom:1.5rem}
.email-popup p{text-align:center;color:#888;font-size:0.95rem;margin-bottom:1.5rem;line-height:1.6}
.email-signup-form{display:flex;flex-direction:column;gap:1rem}
.email-signup-form input{padding:0.75rem 1rem;background:var(--carbon);border:1px solid #444;border-radius:4px;color:#fff;font-family:'Exo 2',sans-serif;font-size:1rem}
.email-signup-form input:focus{outline:none;border-color:var(--primary-blue)}
.email-signup-form button{background:linear-gradient(135deg,var(--primary-blue),var(--electric-blue));color:var(--carbon);padding:0.8rem;border:none;border-radius:4px;font-family:'Rajdhani',sans-serif;font-weight:700;text-transform:uppercase;letter-spacing:2px;cursor:pointer;transition:all 0.3s}
.email-signup-form button:hover{box-shadow:var(--glow)}
.email-popup .privacy-note{text-align:center;color:#555;font-size:0.75rem;margin-top:1rem}
</style>

<!-- Email Signup Popup -->
<div class="email-popup-overlay" id="emailPopup">
<div class="email-popup">
<button class="email-popup-close" onclick="closeEmailPopup()">&times;</button>
<h3>JOIN THE MOVEMENT</h3>
<p class="tagline">Stay Updated on OD9</p>
<p>Get exclusive updates, new music drops, and content directly in your inbox. Be the first to know about community events and releases.</p>
<form class="email-signup-form" action="subscribe.php" method="POST">
<input type="text" name="name" placeholder="Your Name" required>
<input type="email" name="email" placeholder="Your Email" required>
<button type="submit"><i class="fas fa-bolt" style="margin-right:0.5rem"></i> Subscribe</button>
</form>
<p class="privacy-note">We respect your privacy. Unsubscribe anytime.</p>
</div>
</div>

<script>
function closeEmailPopup() {
    document.getElementById('emailPopup').classList.remove('active');
    localStorage.setItem('emailPopupClosed', 'true');
}

// Show popup after 5 seconds if not already closed
setTimeout(function() {
    if (!localStorage.getItem('emailPopupClosed')) {
        document.getElementById('emailPopup').classList.add('active');
    }
}, 5000);

// Close on overlay click
document.getElementById('emailPopup').addEventListener('click', function(e) {
    if (e.target === this) closeEmailPopup();
});
</script>
</body>
</html>
