<!DOCTYPE html>
<html lang="en">
<head>
<?php
$page_title = 'Terms of Service - OD9';
$page_description = 'The terms governing use of OD9 services — the Discord bot, website, and integrated platforms.';
$page_slug = 'terms.php';
include('includes/head.php');
?>
<style>
body{background:var(--d);color:var(--c);font-family:'Exo 2',sans-serif;padding-top:var(--nav-height)}
.legal{max-width:820px;margin:0 auto;padding:3rem 1.5rem}
.legal h1{font-family:'Orbitron',sans-serif;color:#fff;font-size:2rem;margin-bottom:.5rem}
.legal .updated{color:#888;font-size:.9rem;margin-bottom:2rem}
.legal h2{font-family:'Orbitron',sans-serif;color:var(--b);font-size:1.15rem;margin:2rem 0 .75rem}
.legal p{line-height:1.75;color:#bdbdbd;margin-bottom:1rem}
.legal ul{margin:0 0 1rem 1.25rem}
.legal li{line-height:1.7;color:#bdbdbd;margin-bottom:.4rem}
.legal a{color:var(--b);text-decoration:none}
.legal a:hover{text-decoration:underline}
.legal strong{color:#fff}
</style>
</head>
<body>
<?php $current_page = ''; include('includes/nav.php'); ?>
<div class="legal">
<h1>Terms of Service</h1>
<p class="updated">Last Updated: January 24, 2026</p>

<h2>1. Acceptance of Terms</h2>
<p>By accessing OD9 services, including our Discord bot, website, and integrated platforms, you agree to these Terms of Service.</p>

<h2>2. Service Description</h2>
<p>OD9 provides community engagement tools including:</p>
<ul>
<li>Discord bot for community progression and notifications</li>
<li>Integration with TikTok, YouTube, Substack, and Patreon</li>
<li>Educational content and tier-based advancement system</li>
</ul>

<h2>3. User Responsibilities</h2>
<p>Users must:</p>
<ul>
<li>Provide accurate information when linking accounts</li>
<li>Comply with Discord's Terms of Service and Community Guidelines</li>
<li>Respect other community members</li>
<li>Not attempt to manipulate or exploit the progression system</li>
</ul>

<h2>4. Third-Party Services</h2>
<p>Our services integrate with third-party platforms including TikTok, YouTube, Discord, Patreon, and Substack. Your use of these platforms is subject to their respective terms of service.</p>

<h2>5. TikTok Integration</h2>
<p>We use TikTok's Display API to notify our community when new content is posted. We access only publicly available video information and do not store TikTok user credentials.</p>

<h2>6. Termination</h2>
<p>We reserve the right to terminate access for violations of these terms or disruptive behavior.</p>

<h2>7. Disclaimer</h2>
<p>Services are provided "as is" without warranties. We are not liable for service interruptions or third-party platform changes.</p>

<h2>8. Contact</h2>
<p>Questions? Join our <a href="https://discord.gg/spgmrXVMWq" target="_blank" rel="noopener">Discord</a> or reach us via our <a href="contact.php">contact page</a>.</p>
</div>
<?php include('includes/footer.php'); ?>
</body>
</html>
