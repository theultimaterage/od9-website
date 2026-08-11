<?php
$page_title = 'Join OD9 | Community + Newsletter';
$page_description = 'Join the OD9 movement through Discord, the ASCEND path, and the future NCZ newsletter.';
$page_slug = 'join.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include __DIR__ . '/includes/head.php'; ?>
<style>
body{background:var(--d);background-image:linear-gradient(45deg,#111 25%,transparent 25%),linear-gradient(-45deg,#111 25%,transparent 25%),linear-gradient(45deg,transparent 75%,#111 75%),linear-gradient(-45deg,transparent 75%,#111 75%);background-size:4px 4px;color:var(--c);font-family:'Exo 2',sans-serif}.container{max-width:1100px;margin:0 auto;padding:2rem}.hero,.card{background:var(--dd);border:1px solid #2a2a2a;border-radius:16px;padding:2rem;margin:2rem 0 1.25rem}.hero{background:linear-gradient(135deg,rgba(0,191,255,.13),rgba(0,0,0,.72));box-shadow:var(--g)}h1,h2,h3{font-family:'Orbitron',sans-serif;color:#fff}p,li{line-height:1.75;color:#bdbdbd}.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1.25rem}.btn{display:inline-block;background:linear-gradient(135deg,var(--b),#00A0FF);color:#0D0D0D;padding:.8rem 1.3rem;border-radius:5px;text-decoration:none;font-family:'Rajdhani',sans-serif;font-weight:700;letter-spacing:1px;text-transform:uppercase;margin:.45rem .4rem 0 0}.btn.alt{background:transparent;color:var(--b);border:1px solid var(--b)}form{display:grid;gap:.9rem;margin-top:1rem}input,textarea{width:100%;padding:.9rem 1rem;border-radius:10px;border:1px solid #333;background:#111;color:#fff;font:inherit}label{font-weight:600}.small{color:#8f8f8f;font-size:.95rem}
</style>
</head>
<body>
<?php $current_page = 'join'; include('includes/nav.php'); ?>
<div class="container">
<section class="hero"><h1>JOIN THE MOVEMENT</h1>
<p>This isn't a fan club. This is Ground Zero for a paradigm shift. We're building the tools, the systems, and the community infrastructure that the rest of the world is going to need in about 85 seconds.</p>
<p>If you want to be part of what's being built here instead of just watching from the outside, this is your entry point.</p>
<a class="btn" href="https://discord.gg/spgmrXVMWq" target="_blank">Enter Discord</a><a class="btn alt" href="support.php">Support the mission</a></section>
<section class="grid">
<article class="card"><h2>THE DISCORD</h2>
<p>This is where the real conversations happen. Think Tank sessions, NCZ post-show breakdowns, community coordination, and the ASCEND progression system that tracks your actual contribution to the movement.</p>
<ul><li>Think Tank discussions (Mon/Wed/Thu/Sat)</li><li>NCZ post-stream analysis</li><li>ASCEND tier progression</li><li>Movie nights, game nights, real community</li></ul></article>
<article class="card"><h2>THE ASCEND PATH</h2>
<p>Five tiers of growth: Observer, Theorist, Architect, Pioneer, Benefactor. You don't buy your way up. You earn it through analysis, contribution, and participation. This is the coordination infrastructure the Manifesto describes, and you're looking at the prototype.</p></article>
<article class="card"><h2>THE NEWSLETTER</h2>
<p>We're building an email list because algorithms can't censor what lands in your inbox. The <strong>85 Seconds Briefing</strong> will deliver NCZ highlights, Think Tank recaps, Manifesto insights, and behind-the-scenes progress on what we're building. No spam. No fluff. Just signal.</p></article>
</section>
<section class="card"><h2>GET THE 85 SECONDS BRIEFING</h2>
<p>Enter your info below. When the newsletter goes live, you'll be first in line.</p>
<form id="joinPageForm"><div><label>Name</label><input type="text" name="name" placeholder="Your name" required></div><div><label>Email</label><input type="email" name="email" placeholder="you@example.com" required></div><div><label>What brought you here?</label><textarea name="context" rows="3" placeholder="NCZ, Think Tank, the Manifesto, music, all of it..."></textarea></div><input type="hidden" name="source" value="join-page"><button type="submit" class="btn"><i class="fas fa-bolt" style="margin-right:0.5rem"></i> Get on the List</button><p id="joinFormMsg" style="display:none;margin-top:0.75rem;font-size:0.95rem"></p></form>
<p class="small">Your info stays private. No spam. Unsubscribe anytime.</p></section>
</div>
<script>
(function() {
    var form = document.getElementById('joinPageForm');
    var msg = document.getElementById('joinFormMsg');
    if (!form || !msg) return;

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        var btn = form.querySelector('button[type="submit"]');
        var origText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right:0.5rem"></i> Joining...';
        btn.disabled = true;
        msg.style.display = 'none';

        var formData = new FormData(form);
        var data = {};
        formData.forEach(function(val, key) { data[key] = val; });

        fetch('subscribe.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            msg.style.display = 'block';
            if (res.success) {
                msg.style.color = '#00BFFF';
                msg.textContent = res.message;
                form.reset();
            } else {
                msg.style.color = '#ff4444';
                msg.textContent = res.error || 'Something went wrong. Try again.';
            }
        })
        .catch(function() {
            msg.style.display = 'block';
            msg.style.color = '#ff4444';
            msg.textContent = 'Network error. Please try again.';
        })
        .finally(function() {
            btn.innerHTML = origText;
            btn.disabled = false;
        });
    });
})();
</script>
<?php include('includes/footer.php'); ?>
</body>
</html>