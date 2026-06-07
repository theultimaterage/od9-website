<!DOCTYPE html>
<html lang="en">
<head>
<?php
$page_title = 'Privacy Policy - OD9';
$page_description = 'How OD9 collects, uses, and protects your data across Discord, the website, and integrated platforms.';
$page_slug = 'privacy.php';
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
<h1>Privacy Policy</h1>
<p class="updated">Last Updated: January 24, 2026</p>

<h2>1. Information We Collect</h2>
<p><strong>From Discord:</strong> User ID, username, server membership, and message activity for progression tracking.</p>
<p><strong>From TikTok:</strong> Public video metadata (titles, URLs, post dates) for notification purposes only. We do not access private user data.</p>
<p><strong>From Patreon:</strong> Email address and pledge tier for role assignment (with your consent).</p>

<h2>2. How We Use Information</h2>
<ul>
<li>Track community engagement and tier progression</li>
<li>Send notifications about new content (TikTok, YouTube, Substack)</li>
<li>Assign Discord roles based on Patreon support</li>
<li>Improve community features</li>
</ul>

<h2>3. TikTok Data Usage</h2>
<p>We use TikTok's Display API solely to:</p>
<ul>
<li>Check for new video uploads from @theultimaterage079</li>
<li>Retrieve public video information (title, thumbnail, URL)</li>
<li>Notify our Discord community of new content</li>
</ul>
<p>We do <strong>NOT</strong> collect, store, or share any TikTok user credentials or private data.</p>

<h2>4. Data Storage</h2>
<p>Data is stored securely on our servers. We retain progression data to maintain your tier status. You may request data deletion by contacting us.</p>

<h2>5. Data Sharing</h2>
<p>We do <strong>NOT</strong> sell your personal information. We only share data with:</p>
<ul>
<li>Discord (for bot functionality)</li>
<li>Third-party APIs as required for integration features</li>
</ul>

<h2>6. Your Rights</h2>
<p>You may request to view, modify, or delete your data. Contact us via Discord or our contact page.</p>

<h2>7. Children's Privacy</h2>
<p>Our services are not intended for users under 13. We do not knowingly collect data from children.</p>

<h2>8. Contact</h2>
<p>Privacy questions? Reach us via our <a href="https://discord.gg/spgmrXVMWq" target="_blank" rel="noopener">Discord</a> or our <a href="contact.php">contact page</a>.</p>
</div>
<?php include('includes/footer.php'); ?>
</body>
</html>
