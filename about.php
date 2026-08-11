<?php
/**
 * OD9 - About / Mission
 * What OD9 is, why it exists (coordination failure -> the anti-church -> Type 1),
 * how the progression game works, and the invitation to join.
 * Copy source: docs/MISSION_CLARITY.md modules B + C + D + E (clean public voice).
 */
$page_title = 'About OD9 | The Anti-Church Building Toward Type 1';
$page_description = 'OD9 is a community built to fix coordination failure: we learn how reality actually works - evidence over faith - and build toward a civilization that can finally steer itself. Church done right.';
$page_slug = 'about.php';
$page_og_description = 'Learn how the world actually works - and build toward one that finally does. OD9 is the anti-church: meaning, belonging, and purpose, built on evidence.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include __DIR__ . '/includes/head.php'; ?>
<style>
body{background:var(--d);color:var(--c);font-family:'Exo 2',sans-serif;min-height:100vh;line-height:1.7}
.wrap{max-width:920px;margin:0 auto;padding:0 1.5rem}
h1,h2,h3{font-family:'Orbitron',sans-serif;color:#fff;line-height:1.25}

/* Hero (Module B) */
.about-hero{background:linear-gradient(135deg,#050505 0%,#0a0a12 55%,#050505 100%);border-bottom:1px solid #1a1a2e;padding:4.5rem 1.5rem 3.5rem;text-align:center}
.about-hero .badge{display:inline-block;font-family:'Orbitron',sans-serif;font-size:.72rem;letter-spacing:3px;text-transform:uppercase;color:var(--b);border:1px solid rgba(0,191,255,.34);padding:.35rem 1rem;border-radius:2px;text-shadow:0 0 12px rgba(0,191,255,.4);margin-bottom:1.4rem}
.about-hero h1{font-size:clamp(1.9rem,5.2vw,3.1rem);font-weight:900;text-shadow:0 0 30px rgba(0,191,255,.27);margin-bottom:1.2rem}
.about-hero p{max-width:680px;margin:0 auto;font-size:clamp(1rem,2.4vw,1.15rem);color:#cfd2d6}
.about-hero p strong{color:#fff}
.about-hero .hero-cta{margin-top:2rem;display:flex;gap:.8rem;justify-content:center;flex-wrap:wrap}

/* Buttons */
.btn{display:inline-flex;align-items:center;gap:.5rem;font-family:'Orbitron',sans-serif;font-size:.78rem;font-weight:700;letter-spacing:1.4px;text-transform:uppercase;padding:.8rem 1.5rem;border-radius:4px;text-decoration:none;transition:transform .25s,box-shadow .25s}
.btn-primary{background:linear-gradient(135deg,var(--b),var(--eb));color:#000;box-shadow:var(--g)}
.btn-primary:hover{transform:translateY(-2px)}
.btn-ghost{background:transparent;color:var(--b);border:1px solid rgba(0,191,255,.5)}
.btn-ghost:hover{background:rgba(0,191,255,.08);transform:translateY(-2px)}

/* Sections */
.about-section{padding:3.2rem 0;border-bottom:1px solid #121218}
.about-section:last-of-type{border-bottom:none}
.kicker{font-family:'Orbitron',sans-serif;font-size:.72rem;letter-spacing:3px;text-transform:uppercase;color:var(--b);margin-bottom:.8rem;display:block}
.about-section h2{font-size:clamp(1.4rem,4vw,2.1rem);font-weight:800;margin-bottom:1.5rem}
.about-section p{margin-bottom:1.15rem;font-size:1.04rem;color:#c4c7cb}
.about-section p:last-child{margin-bottom:0}
.about-section strong{color:#fff}
.lead{font-family:'Rajdhani',sans-serif;font-weight:600;font-size:clamp(1.15rem,3vw,1.5rem)!important;color:#fff!important;line-height:1.4;margin-bottom:1.4rem!important}

/* Pull-block: the Ultimate Rage Paradox callout */
.callout{background:linear-gradient(135deg,#0d0d0d,#0a0a12);border:1px solid #1a1a2e;border-left:3px solid var(--b);border-radius:6px;padding:1.6rem 1.8rem;margin:1.8rem 0}
.callout p{margin-bottom:0;color:#d6d9dd}
.callout .term{font-family:'Orbitron',sans-serif;color:var(--b);text-shadow:0 0 12px rgba(0,191,255,.4)}

/* Tier arc */
.tier-arc{display:flex;flex-wrap:wrap;align-items:center;gap:.5rem;justify-content:center;margin:1.8rem 0 1.6rem;font-family:'Orbitron',sans-serif}
.tier-arc .t{font-size:.85rem;font-weight:700;letter-spacing:1px;color:#fff;background:#121218;border:1px solid #232331;padding:.5rem .9rem;border-radius:4px}
.tier-arc .t.first{border-color:rgba(0,191,255,.45);color:var(--b)}
.tier-arc .t.last{border-color:var(--gold);color:var(--gold)}
.tier-arc .arrow{color:#444;font-size:.9rem}

/* Three-place grid + do-list */
.grid3{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem;margin-top:1.6rem}
.cell{background:#0f0f15;border:1px solid #1d1d28;border-radius:8px;padding:1.4rem}
.cell h3{font-size:1rem;letter-spacing:1px;color:var(--b);margin-bottom:.5rem;text-transform:uppercase}
.cell p{margin-bottom:0;font-size:.95rem;color:#b3b6ba}
.do-list{list-style:none;margin:1.4rem 0 0;padding:0;display:grid;gap:.9rem}
.do-list li{background:rgba(0,191,255,.04);border:1px solid #1a1a26;border-radius:8px;padding:1rem 1.2rem;font-size:1rem;color:#c4c7cb}
.do-list li strong{color:var(--b)}

/* Closing invitation CTA */
.invite{background:linear-gradient(135deg,rgba(0,191,255,.1),rgba(0,0,0,.6));border:1px solid rgba(0,191,255,.3);border-radius:16px;padding:2.6rem 2rem;text-align:center;margin:3rem 0 4rem;box-shadow:var(--g)}
.invite h2{font-size:clamp(1.5rem,4vw,2.2rem);margin-bottom:1.1rem}
.invite p{max-width:620px;margin:0 auto 1rem;color:#c4c7cb;font-size:1.05rem}
.invite .ask{font-family:'Orbitron',sans-serif;color:#fff;font-size:1.05rem;letter-spacing:1px;margin:1.4rem 0 1.8rem}
.invite .invite-cta{display:flex;gap:.8rem;justify-content:center;flex-wrap:wrap}

@media(max-width:600px){
  .about-hero{padding:3.2rem 1.2rem 2.6rem}
  .callout{padding:1.3rem 1.3rem}
  .tier-arc .arrow{display:none}
}
</style>
</head>
<body>
<?php $current_page = 'about'; include('includes/nav.php'); ?>

<!-- HERO — Module B -->
<header class="about-hero">
  <span class="badge">The Anti-Church</span>
  <h1>Learn how the world actually works &mdash; and build toward one that finally does.</h1>
  <p>The world isn't broken for lack of stuff &mdash; we have the food, the energy, and the knowledge to end most human suffering right now. It's broken because we can't <strong>coordinate</strong>. OD9 is a community built to fix that: we learn how reality actually works &mdash; evidence over faith, data over dogma &mdash; and we build, together, toward a civilization that can finally steer itself. Think of it as <strong>church done right</strong>: the belonging and the purpose, minus the make-believe.</p>
  <div class="hero-cta">
    <a class="btn btn-primary" href="https://discord.gg/spgmrXVMWq" target="_blank" rel="noopener"><i class="fab fa-discord"></i> Join the Community</a>
  </div>
</header>

<div class="wrap">

  <!-- THE WHY — Module C -->
  <section class="about-section">
    <span class="kicker">The Why</span>
    <h2>The problem isn't scarcity. It's that nobody can get us to act together.</h2>
    <p>We have enough food to feed everyone &mdash; people starve anyway. Enough homes &mdash; people sleep on the street. Enough clean energy on the table to power the planet &mdash; we keep burning the world down instead. The problem was never <em>not having enough</em>. It's that nobody can get 8 billion people pointed at the same target. That's <strong>coordination failure</strong>, and it's the disease under almost every headline you've ever doom-scrolled: climate, poverty, the AI arms race, all of it.</p>

    <p class="lead">And here's the wild part: nobody's actually in charge of the big picture.</p>
    <p>There is no one &mdash; no government, no company, no institution &mdash; whose <em>job</em> is to ask the only question that really matters: <strong>what are we all doing here, and where are we trying to go?</strong> That question got trained out of us. Religion told us to have faith instead of asking. School graded us for compliance, not curiosity. Work pays for output, not reflection. The feed rewards outrage, not thought. So billions of us can think &mdash; and almost none of us are aimed anywhere on purpose.</p>

    <p class="lead">The biggest, oldest, richest example of the failure? Organized religion.</p>
    <p>Roughly 6 billion believers. Thousands of years. Hundreds of thousands of churches, over a trillion dollars a year, a claimed direct line to a god who supposedly knows everything and can do anything. The output? 45,000+ denominations that can't agree on the book. A Pope kept alive by the secular medicine his own institution fought. Abuse settlements still rolling in by the hundreds of millions. And in all that time, across all those believers, <strong>not one shred of falsifiable evidence for a single supernatural claim.</strong></p>

    <div class="callout">
      <p>That gap &mdash; infinite claimed power, near-zero delivered result &mdash; is what we call the <span class="term">Ultimate Rage Paradox</span>. It's the clearest case study in coordination failure ever run. OD9 is the answer to it. <strong>We're the anti-church:</strong> everything religion promised &mdash; meaning, community, a shot at something bigger than yourself &mdash; built on the one thing it never had. <span class="term">Evidence.</span> And an actual intent to deliver.</p>
    </div>

    <p class="lead">So what do we do about it?</p>
    <p>We rebuild the thing that's missing &mdash; a place where humans actually ask the big question, answer it together with evidence, and <em>act</em> on it. We learn how reality works, figure out what's worth building, and build toward a civilization that can finally steer itself: <strong>Kardashev Type 1</strong> &mdash; a species that's mastered its own planet. Clean energy at scale. Manufactured scarcity ended. Coordination that works at the size of our problems.</p>
    <p>We're at about <strong>0.73</strong> on that scale today. Type 1 is reachable &mdash; <em>this century</em> reachable. And the manifesto says the sober part straight: <strong>this is not utopia. This is survival.</strong> The same wall that might explain why the universe is so quiet &mdash; why we've never heard from anyone out there &mdash; might be this exact moment: the civilizations that never learn to coordinate don't make it through it. We intend to be one that does.</p>
  </section>

  <!-- THE HOW — Module D -->
  <section class="about-section">
    <span class="kicker">The How</span>
    <h2>OD9 runs as a progression &mdash; a real game with real stakes.</h2>

    <div class="tier-arc">
      <span class="t first">Observer</span><span class="arrow">&rarr;</span>
      <span class="t">Theorist</span><span class="arrow">&rarr;</span>
      <span class="t">Architect</span><span class="arrow">&rarr;</span>
      <span class="t">Pioneer</span><span class="arrow">&rarr;</span>
      <span class="t last">Benefactor</span>
    </div>

    <p>The climb is an arc: <strong>learn the truth &rarr; understand what's broken &rarr; build solutions &rarr; lead the work &rarr; steward the mission.</strong> You start finding your footing. You end up running the thing.</p>

    <p><strong>You level up by <em>doing</em>, not lurking:</strong></p>
    <ul class="do-list">
      <li><strong>Learn</strong> &mdash; the manifesto's ideas, fed to you as you're ready (not a 67-chapter brick dropped on your head). You unlock the real text volume by volume as you climb; the complete manifesto is the reward waiting at the summit.</li>
      <li><strong>Engage</strong> &mdash; discuss, debate, answer the daily question, show up to the Live.</li>
      <li><strong>Demonstrate</strong> &mdash; at each tier you ship a <strong>capstone</strong>: proof you actually <em>get</em> it. No participation trophies. You don't level up for showing up &mdash; you level up for understanding.</li>
    </ul>

    <p style="margin-top:1.4rem">And you don't do it alone. You climb with a <strong>cohort</strong> &mdash; a small crew at your level, learning and building together &mdash; and <strong>mentors</strong> who've already walked the path. That's the part every "learn online" thing misses: this is <em>multiplayer</em>.</p>

    <p style="margin-top:1.4rem"><strong>It lives in three places that are really one:</strong></p>
    <div class="grid3">
      <div class="cell"><h3>Discord</h3><p>The arena. Where we talk, debate, build, and gather every day on the Live.</p></div>
      <div class="cell"><h3>The Website</h3><p>The world. Where you see your climb, everyone else's, and the live state of the mission.</p></div>
      <div class="cell"><h3>The Bot</h3><p>The rulebook. It runs the whole economy so every action counts, everywhere.</p></div>
    </div>

    <p style="margin-top:1.6rem">It's a game. But every level is real understanding, and every contribution is a real push on the actual mission.</p>
  </section>

  <!-- THE INVITATION — Module E (closing CTA) -->
  <section class="invite">
    <h2>Why you, why now</h2>
    <p>There are about a hundred of us. That's not a weakness &mdash; it's the <strong>ground floor.</strong> The people here now are the founders of whatever this becomes.</p>
    <p>You don't need a degree, money, or a background in any of this. You need to be <strong>curious, willing to question everything (including us), and down to build.</strong></p>
    <p>What you get: the truth without the overwhelm, a crew climbing with you, and a real shot at mattering to something bigger than yourself.</p>
    <p class="ask">The ask is simple: show up. learn. climb. build.</p>
    <div class="invite-cta">
      <a class="btn btn-primary" href="https://discord.gg/spgmrXVMWq" target="_blank" rel="noopener"><i class="fab fa-discord"></i> Join OD9 on Discord</a>
    </div>
  </section>

</div>

<?php include('includes/footer.php'); ?>
</body>
</html>
