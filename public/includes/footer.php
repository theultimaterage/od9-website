<?php
/**
 * OD9 Universal Footer
 * Include this once before </body> on every page.
 * Mirrors nav structure. Cyberpunk aesthetic. Mission-driven.
 */
?>
<style>
.od9-footer{background:linear-gradient(180deg,#0a0a0a 0%,#050505 100%);border-top:2px solid #00BFFF;margin-top:4rem;padding:0;font-family:'Exo 2','Rajdhani',sans-serif;position:relative}
.od9-footer::before{content:'';position:absolute;top:0;left:0;width:100%;height:2px;background:linear-gradient(90deg,transparent,#00BFFF,transparent);box-shadow:0 0 20px rgba(0,191,255,0.4)}
.footer-inner{max-width:1100px;margin:0 auto;padding:3rem 2rem 2rem}
.footer-grid{display:grid;grid-template-columns:1.4fr 1fr 1fr;gap:2.5rem;margin-bottom:2.5rem}
.footer-col h4{font-family:'Orbitron',sans-serif;color:#fff;font-size:0.85rem;letter-spacing:2px;text-transform:uppercase;margin-bottom:1rem;position:relative;padding-bottom:0.5rem}
.footer-col h4::after{content:'';position:absolute;bottom:0;left:0;width:30px;height:2px;background:#00BFFF}
.footer-col p{color:#777;font-size:0.9rem;line-height:1.7}
.footer-links{list-style:none;padding:0;margin:0}
.footer-links li{margin-bottom:0.4rem}
.footer-links a{color:#888;text-decoration:none;font-size:0.9rem;transition:color 0.2s,padding-left 0.2s}
.footer-links a:hover{color:#00BFFF;padding-left:4px}
.footer-socials{display:flex;gap:1rem;margin-top:1.2rem}
.footer-socials a{color:#555;font-size:1.4rem;transition:all 0.3s;text-decoration:none}
.footer-socials a:hover{color:#00BFFF;transform:translateY(-2px);text-shadow:0 0 10px rgba(0,191,255,0.5)}
.footer-divider{border:none;border-top:1px solid #1a1a1a;margin:0 0 1.5rem}
.footer-bottom{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem}
.footer-copy{color:#444;font-size:0.8rem}
.footer-copy a{color:#555;text-decoration:none}
.footer-copy a:hover{color:#00BFFF}
.footer-tagline{color:#333;font-size:0.75rem;font-family:'Orbitron',sans-serif;letter-spacing:2px;text-transform:uppercase}
@media(max-width:768px){
.footer-grid{grid-template-columns:1fr;gap:2rem;text-align:center}
.footer-col h4::after{left:50%;transform:translateX(-50%)}
.footer-socials{justify-content:center}
.footer-bottom{flex-direction:column;text-align:center}
}
</style>
<footer class="od9-footer">
<div class="footer-inner">

<div class="footer-grid">
<!-- Column 1: Mission -->
<div class="footer-col">
<h4>OD9 - Off Da Nine</h4>
<p>A coordination framework disguised as a brand. We build culture that encodes solutions &mdash; music, media, and community infrastructure designed to push humanity past its current failure modes.</p>
<div class="footer-socials">
<a href="https://discord.gg/spgmrXVMWq" target="_blank" title="Discord"><i class="fab fa-discord"></i></a>
<a href="https://youtube.com/@theultimaterage" target="_blank" title="YouTube"><i class="fab fa-youtube"></i></a>
<a href="https://www.twitch.tv/theultimaterage" target="_blank" title="Twitch"><i class="fab fa-twitch"></i></a>
<a href="https://instagram.com/theultimaterage" target="_blank" title="Instagram"><i class="fab fa-instagram"></i></a>
<a href="https://open.spotify.com/artist/0QvH8H7obaMerk1UkfFGaD" target="_blank" title="Spotify"><i class="fab fa-spotify"></i></a>
</div>
</div>

<!-- Column 2: Navigate -->
<div class="footer-col">
<h4>Navigate</h4>
<ul class="footer-links">
<li><a href="index.php">Home</a></li>
<li><a href="framework.php">Framework</a></li>
<li><a href="ncz.php">No Cap Zone</a></li>
<li><a href="think-tank.php">Think Tank</a></li>
<li><a href="tiers.php">Tier System</a></li>
<li><a href="da-crew.php">Da Crew</a></li>
<li><a href="music.php">Music</a></li>
</ul>
</div>

<!-- Column 3: Get Involved -->
<div class="footer-col">
<h4>Get Involved</h4>
<ul class="footer-links">
<li><a href="join.php">Join the Movement</a></li>
<li><a href="support.php">Support OD9</a></li>
<li><a href="updates.php">Dev Updates</a></li>
<li><a href="downloads.php">Downloads</a></li>
<li><a href="resources.php">Resources</a></li>
<li><a href="contact.php">Contact</a></li>
<li><a href="https://discord.gg/spgmrXVMWq" target="_blank">Discord Server &rarr;</a></li>
</ul>
</div>
</div>

<hr class="footer-divider">
<div class="footer-bottom">
<span class="footer-copy">&copy; <?= date('Y') ?> OD9 LLC. All rights reserved. | <a href="contact.php">contact@offda9.com</a></span>
<span class="footer-tagline">85 Seconds to Midnight</span>
</div>

</div>
</footer>
