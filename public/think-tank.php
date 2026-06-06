<?php
$page_title = 'Think Tank Archive | OD9';
$page_description = 'Think Tank archive with OD9 session summaries, topic logs, and future gated recordings.';
$page_slug = 'think-tank.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include __DIR__ . '/includes/head.php'; ?>
<style>
body{background:var(--d);background-image:linear-gradient(45deg,#111 25%,transparent 25%),linear-gradient(-45deg,#111 25%,transparent 25%),linear-gradient(45deg,transparent 75%,#111 75%),linear-gradient(-45deg,transparent 75%,#111 75%);background-size:4px 4px;color:var(--c);font-family:'Exo 2',sans-serif;padding-top:80px}.container{max-width:1100px;margin:0 auto;padding:2rem}.hero,.card{background:var(--dd);border:1px solid #2a2a2a;border-radius:16px;padding:2rem;margin-bottom:1.25rem}.hero{margin-top:2rem;background:linear-gradient(135deg,rgba(0,191,255,.13),rgba(0,0,0,.72));box-shadow:var(--g)}h1,h2,h3{font-family:'Orbitron',sans-serif;color:#fff}p,li{line-height:1.75;color:#bdbdbd}.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1.25rem}.pill{display:inline-block;padding:.45rem .8rem;border:1px solid rgba(0,191,255,.4);border-radius:999px;margin:.3rem .35rem 0 0}.btn{display:inline-block;background:linear-gradient(135deg,var(--b),#00A0FF);color:#0D0D0D;padding:.8rem 1.3rem;border-radius:5px;text-decoration:none;font-family:'Rajdhani',sans-serif;font-weight:700;letter-spacing:1px;text-transform:uppercase;margin-top:1rem}.btn.alt{background:transparent;color:var(--b);border:1px solid var(--b)}
</style>
</head>
<body>
<?php $current_page = 'think-tank'; include('includes/nav.php'); ?>
<div class="container">
<section class="hero"><h1>THE THINK TANK</h1>
<p>This is where the show stops being content and starts being coordination. The Think Tank is where we break down what actually matters, analyze what everyone else is too comfortable to question, and build the intellectual infrastructure for what comes next.</p>
<div><span class="pill">Live discussions</span><span class="pill">Four Lenses analysis</span><span class="pill">Movie nights</span><span class="pill">Community-driven topics</span></div></section>
<section class="grid">
<article class="card"><h2>HOW IT WORKS</h2>
<p>After NCZ streams, we move to Discord for live Think Tank sessions. We watch content, dissect news, analyze media through the Four Lenses (Facts, Media Literacy, Antitheist Take, OD9 Perspective), and have real conversations.</p>
<ul><li>Mon/Wed: Post-stream Think Tanks</li><li>Thursday ~8pm CT: Topic Night (Discord exclusive)</li><li>Saturday ~7pm CT: Game Night or Movie Night</li></ul></article>
<article class="card"><h2>SESSION ARCHIVE</h2>
<p>Session recaps and key takeaways will be published here as the archive builds. Full recordings and detailed notes will be available through Patreon for those who want to go deeper.</p>
<p><strong>Coming soon:</strong> Real session recaps from our first revival Think Tanks.</p></article>
<article class="card"><h2>GO DEEPER</h2>
<p>Patreon members get full Think Tank recordings, resource lists, and detailed analysis notes that don't make it into the public summary.</p>
<a class="btn" href="https://www.patreon.com/c/TheUltimateRage" target="_blank">Unlock full archives</a>
<a class="btn alt" href="join.php">Join the community</a></article>
</section></div>
<?php include('includes/footer.php'); ?>
</body>
</html>