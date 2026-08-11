<?php
$page_title = '85 Seconds to Midnight | OD9';
$page_description = 'Download the 85 Seconds to Midnight briefing. Data, frameworks, and a clear path forward for why the systems around us are failing.';
$page_slug = 'wake-up.php';
$page_og_description = 'The systems are failing. The data proves it. Here\'s what we\'re doing about it. Free briefing.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include __DIR__ . '/includes/head.php'; ?>
<style>
:root{--b:#00BFFF;--eb:#00A0FF;--d:#0A0A0A;--dd:#111;--c:#C0C0C0;--g:0 0 20px rgba(0,191,255,.45)}
*{margin:0;padding:0;box-sizing:border-box}
body{background:var(--d);color:var(--c);font-family:'Exo 2',sans-serif;min-height:100vh;display:flex;flex-direction:column;align-items:center;padding-top:calc(var(--nav-height) + 1rem)}

.landing{max-width:680px;margin:0 auto;padding:2rem 1.5rem 3rem;width:100%}

.logo-row{text-align:center;margin-bottom:2rem;padding-top:1.5rem}
.logo-row img{height:60px;filter:drop-shadow(var(--g))}
.logo-row span{display:block;font-family:'Orbitron',sans-serif;font-size:0.85rem;color:var(--b);letter-spacing:3px;margin-top:0.5rem;text-shadow:var(--g)}

.hero-title{font-family:'Orbitron',sans-serif;font-size:clamp(1.8rem,5vw,2.6rem);font-weight:900;color:#fff;text-align:center;line-height:1.2;margin-bottom:0.75rem}
.hero-title .clock{color:var(--b);text-shadow:var(--g)}

.subtitle{text-align:center;color:#888;font-size:1.05rem;line-height:1.7;margin-bottom:2.5rem;max-width:560px;margin-left:auto;margin-right:auto}

.proof-bar{display:flex;justify-content:center;gap:2rem;flex-wrap:wrap;margin-bottom:2.5rem;padding:1rem 0;border-top:1px solid #1a1a1a;border-bottom:1px solid #1a1a1a}
.proof-item{text-align:center}
.proof-num{font-family:'Orbitron',sans-serif;font-size:1.4rem;font-weight:700;color:var(--b)}
.proof-label{font-size:0.75rem;color:#888;text-transform:uppercase;letter-spacing:1px}

.bullets{list-style:none;padding:0;margin:0 0 2.5rem}
.bullets li{display:flex;align-items:flex-start;gap:0.75rem;margin-bottom:1rem;color:#bbb;font-size:0.95rem;line-height:1.6}
.bullets li i{color:var(--b);margin-top:0.25rem;flex-shrink:0;font-size:0.85rem}

.form-card{background:var(--dd);border:1px solid #222;border-radius:12px;padding:2rem;position:relative;overflow:hidden}
.form-card::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,transparent,var(--b),transparent)}
.form-card h2{font-family:'Orbitron',sans-serif;font-size:1.1rem;color:#fff;text-align:center;margin-bottom:0.5rem;letter-spacing:1px}
.form-card .form-sub{text-align:center;color:#888;font-size:0.85rem;margin-bottom:1.5rem}

.form-fields{display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;margin-bottom:0.75rem}
.form-fields input{padding:0.8rem 1rem;background:#0a0a0a;border:1px solid #333;border-radius:6px;color:#fff;font-family:'Exo 2',sans-serif;font-size:0.95rem;width:100%}
.form-fields input:focus{outline:none;border-color:var(--b);box-shadow:0 0 8px rgba(0,191,255,0.2)}
.email-row{margin-bottom:1rem}
.email-row input{width:100%;padding:0.8rem 1rem;background:#0a0a0a;border:1px solid #333;border-radius:6px;color:#fff;font-family:'Exo 2',sans-serif;font-size:0.95rem}
.email-row input:focus{outline:none;border-color:var(--b);box-shadow:0 0 8px rgba(0,191,255,0.2)}

.submit-btn{width:100%;padding:0.9rem;background:linear-gradient(135deg,var(--b),var(--eb));color:var(--d);border:none;border-radius:6px;font-family:'Rajdhani',sans-serif;font-weight:700;font-size:1.05rem;text-transform:uppercase;letter-spacing:2px;cursor:pointer;transition:all 0.3s}
.submit-btn:hover{box-shadow:var(--g);transform:translateY(-1px)}
.submit-btn:disabled{opacity:0.6;cursor:not-allowed;transform:none}

.form-msg{display:none;margin-top:0.75rem;font-size:0.9rem;text-align:center;line-height:1.5}
.privacy{text-align:center;color:#949494;font-size:0.75rem;margin-top:1rem}

.source-strip{text-align:center;margin-top:2.5rem;padding-top:1.5rem;border-top:1px solid #1a1a1a}
.source-strip p{color:#888;font-size:0.8rem;line-height:1.6;max-width:500px;margin:0 auto}
.source-strip .names{color:#888;font-style:italic}

.bottom-links{text-align:center;margin-top:2rem;padding-top:1rem}
.bottom-links a{color:#888;text-decoration:none;font-size:0.8rem;margin:0 0.75rem;transition:color 0.2s}
.bottom-links a:hover{color:var(--b)}

@media(max-width:500px){
    .form-fields{grid-template-columns:1fr}
    .proof-bar{gap:1.25rem}
    .hero-title{font-size:1.6rem}
}
</style>
</head>
<body>
<?php $current_page = ''; include('includes/nav.php'); ?>
<div class="landing">

    <div class="logo-row">
        <a href="index.php"><img width="1408" height="768" src="images/logos/od9-logo.png" alt="OD9"></a>
        <span>offda9.com</span>
    </div>

    <h1 class="hero-title"><span class="clock">85 Seconds</span> to Midnight</h1>
    <p class="subtitle">The systems around us are failing. Not metaphorically. Measurably. The Doomsday Clock is the closest it's ever been, and the institutions we depend on are the reason why. Here's the data, the framework, and what we're doing about it.</p>

    <div class="proof-bar">
        <div class="proof-item">
            <div class="proof-num">6 / 9</div>
            <div class="proof-label">Planetary boundaries crossed</div>
        </div>
        <div class="proof-item">
            <div class="proof-num">52+</div>
            <div class="proof-label">Chapters sourced</div>
        </div>
        <div class="proof-item">
            <div class="proof-num">100s</div>
            <div class="proof-label">Academic citations</div>
        </div>
    </div>

    <ul class="bullets">
        <li><i class="fas fa-crosshairs"></i> The <strong>paperclip maximizer</strong> framework: why every institution around you is misaligned, and what that actually means</li>
        <li><i class="fas fa-virus"></i> The virus analogy: how communities without purpose consume themselves to death</li>
        <li><i class="fas fa-dna"></i> Why transhumanism is both the greatest gift and the greatest threat to humanity</li>
        <li><i class="fas fa-ship"></i> The <strong>Thesean method</strong> for rebuilding broken systems without burning everything down</li>
        <li><i class="fas fa-chart-line"></i> Sourced from the Manifesto of Theseus - citing Oxford, Princeton, MIT, the CDC, IPCC, and more</li>
        <li><i class="fas fa-fist-raised"></i> What you can do about it <strong>right now</strong></li>
    </ul>

    <div class="form-card">
        <h2>Download the Free Briefing</h2>
        <p class="form-sub">8 pages. All sourced. No fluff. Just signal.</p>
        <form id="wakeUpForm">
            <div class="form-fields">
                <input type="text" name="name" placeholder="Your Name" required>
                <input type="hidden" name="source" value="wake-up">
            </div>
            <div class="email-row">
                <input type="email" name="email" placeholder="Your Email" required>
            </div>
            <button type="submit" class="submit-btn"><i class="fas fa-bolt" style="margin-right:0.5rem"></i> Send Me the Briefing</button>
            <p class="form-msg" id="wakeUpMsg"></p>
        </form>
        <p class="privacy"><i class="fas fa-lock" style="margin-right:0.25rem"></i> No spam. No selling your data. Unsubscribe anytime.</p>
    </div>

    <div class="source-strip">
        <p>This briefing draws from research published by:</p>
        <p class="names">Bulletin of the Atomic Scientists, CDC, IPCC, Oxford Future of Humanity Institute, Princeton, MIT, Stockholm Resilience Centre, and the 6-volume Manifesto of Theseus</p>
    </div>

    <div class="form-card" style="margin-top:2.5rem;border-color:rgba(0,191,255,0.3)">
        <h2 style="color:var(--b);margin-bottom:1rem;font-size:1rem">85 seconds is the diagnosis. OD9 is the response.</h2>
        <p style="color:#aaa;font-size:0.95rem;line-height:1.7;margin-bottom:1.25rem;text-align:left">
            The Doomsday Clock is at 85 seconds because <strong style="color:var(--b)">coordination</strong> failed — institutions, incentives, and information systems all optimized away from the long-term human interest. OD9 exists to build the infrastructure for the next round of coordination, before the clock runs down.
        </p>
        <p style="color:#aaa;font-size:0.95rem;line-height:1.7;margin-bottom:1.25rem;text-align:left">
            The briefing tells you what's broken. What you do next decides whether you're part of the diagnosis or part of the response.
        </p>
        <div style="display:flex;gap:0.75rem;flex-wrap:wrap;justify-content:center">
            <a href="framework.php" style="display:inline-block;padding:0.7rem 1.5rem;background:linear-gradient(135deg,var(--b),var(--eb));color:var(--d);text-decoration:none;font-family:'Rajdhani',sans-serif;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;border-radius:6px;font-size:0.9rem">Read the Framework</a>
            <a href="https://discord.gg/spgmrXVMWq" target="_blank" style="display:inline-block;padding:0.7rem 1.5rem;background:transparent;color:var(--b);border:1px solid var(--b);text-decoration:none;font-family:'Rajdhani',sans-serif;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;border-radius:6px;font-size:0.9rem">Join Discord</a>
        </div>
    </div>

    <div class="bottom-links">
        <a href="index.php">Home</a>
        <a href="framework.php">Framework</a>
        <a href="join.php">Join</a>
        <a href="https://discord.gg/spgmrXVMWq" target="_blank">Discord</a>
        <a href="support.php">Support</a>
    </div>

</div>

<script>
(function() {
    var form = document.getElementById('wakeUpForm');
    var msg = document.getElementById('wakeUpMsg');
    if (!form || !msg) return;

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        var btn = form.querySelector('button[type="submit"]');
        var origText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right:0.5rem"></i> Processing...';
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
                if (res.already_subscribed) {
                    msg.textContent = "You're already on the list. Check your inbox for the briefing.";
                } else {
                    msg.innerHTML = "<strong>You're in.</strong> The briefing is on its way to your inbox. Over the next 7 days, we'll unpack the most important ideas - one per day.";
                }
                form.querySelector('button[type="submit"]').innerHTML = '<i class="fas fa-check" style="margin-right:0.5rem"></i> You\'re In';
                form.querySelector('button[type="submit"]').style.background = '#1a8a4a';
            } else {
                msg.style.color = '#ff4444';
                msg.textContent = res.error || 'Something went wrong. Try again.';
                btn.innerHTML = origText;
                btn.disabled = false;
            }
        })
        .catch(function() {
            msg.style.display = 'block';
            msg.style.color = '#ff4444';
            msg.textContent = 'Network error. Please try again.';
            btn.innerHTML = origText;
            btn.disabled = false;
        });
    });
})();
</script>
<?php include('includes/footer.php'); ?>
</body>
</html>
