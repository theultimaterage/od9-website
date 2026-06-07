<?php
/**
 * OD9 Ecosystem Map - shows the 5-entity brand family
 *
 * The strategic positioning page: OD9 (philosophy) ↔ FTB (music) ↔
 * F.R.E.S.H. (financial engine) ↔ Synaptiq (intelligence layer) ↔
 * NCZ (the show). Builds credibility through visible scope and gives
 * cold visitors context for why OD9 isn't "just a Discord."
 *
 * Mounted at https://offda9.com/ecosystem.php
 */
?>
<?php
$page_title = 'The OD9 Ecosystem | 5 entities, one mission';
$page_description = 'OD9 is the philosophical layer of a 5-entity ecosystem: F.R.E.S.H. (financial engine), FTB (music), Synaptiq (intelligence), and NCZ (show). Here\'s how they connect.';
$page_slug = 'ecosystem.php';
$page_og_title = 'The OD9 Ecosystem';
$page_og_description = '5 entities, one mission - how OD9, F.R.E.S.H., FTB, Synaptiq, and NCZ work together.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include __DIR__ . '/includes/head.php'; ?>
<style>
:root{--b:#00BFFF;--eb:#00A0FF;--rg:#B76E79;--gold:#FFD700;--purple:#A55FFF;--green:#00E89A;--orange:#FF8A3D;--d:#0A0A0A;--dd:#111;--c:#C0C0C0;--g:0 0 20px rgba(0,191,255,.45)}
*{margin:0;padding:0;box-sizing:border-box}
body{background:var(--d);color:var(--c);font-family:'Exo 2',sans-serif;min-height:100vh;padding:2rem 1rem 4rem;line-height:1.7}
.wrap{max-width:880px;margin:0 auto}
header{text-align:center;margin-bottom:3rem}
.logo{display:inline-block;margin-bottom:1.25rem}
.logo img{height:60px;filter:drop-shadow(var(--g))}
h1{font-family:'Orbitron',sans-serif;font-size:clamp(1.8rem,5vw,2.6rem);font-weight:900;color:#fff;letter-spacing:3px;margin-bottom:0.5rem;text-shadow:var(--g);line-height:1.2}
.tag{font-family:'Rajdhani',sans-serif;color:var(--b);letter-spacing:4px;font-size:0.9rem;text-transform:uppercase;margin-bottom:0.75rem}
.lede{color:#bbb;font-size:1.05rem;max-width:600px;margin:0 auto;line-height:1.6}
.flow{margin:3rem 0;text-align:center;color:#888;font-size:0.95rem;line-height:1.8}
.flow strong{color:var(--gold)}
.entities{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1.25rem;margin-bottom:3rem}
.entity{background:var(--dd);border:1px solid #222;border-radius:12px;padding:1.75rem 1.5rem;position:relative;overflow:hidden;transition:transform 0.2s ease}
.entity:hover{transform:translateY(-3px)}
.entity::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:var(--accent,var(--b))}
.entity.od9{--accent:var(--b)}
.entity.fresh{--accent:var(--green)}
.entity.ftb{--accent:var(--purple)}
.entity.synaptiq{--accent:var(--orange)}
.entity.ncz{--accent:var(--gold)}
.entity-name{font-family:'Orbitron',sans-serif;color:var(--accent,var(--b));font-size:1.25rem;font-weight:800;letter-spacing:2px;margin-bottom:0.4rem}
.entity-role{color:#999;font-size:0.85rem;text-transform:uppercase;letter-spacing:1.5px;margin-bottom:1rem}
.entity-desc{color:#bbb;font-size:0.95rem;line-height:1.6;margin-bottom:1rem}
.entity-link{display:inline-block;color:var(--accent,var(--b));text-decoration:none;font-family:'Rajdhani',sans-serif;font-weight:600;font-size:0.9rem;letter-spacing:1px;text-transform:uppercase}
.entity-link:hover{text-decoration:underline}
.diagram{background:var(--dd);border:1px solid #222;border-radius:12px;padding:2rem 1.5rem;margin-bottom:3rem;text-align:center}
.diagram svg{max-width:100%;height:auto}
.diagram-caption{color:#888;font-size:0.85rem;margin-top:1rem}
.synthesis{background:linear-gradient(135deg,rgba(0,191,255,0.08),rgba(255,215,0,0.04));border:1px solid #2a3a4a;border-radius:12px;padding:2rem 1.75rem;margin-bottom:2rem}
.synthesis h2{font-family:'Orbitron',sans-serif;color:#fff;font-size:1.2rem;letter-spacing:1px;margin-bottom:1rem}
.synthesis p{color:#bbb;margin-bottom:0.85rem}
.synthesis strong{color:var(--gold)}
.bottom-links{text-align:center;margin-top:2rem}
.bottom-links a{color:#888;text-decoration:none;font-size:0.85rem;margin:0 0.75rem}
.bottom-links a:hover{color:var(--b)}
</style>
</head>
<body>
<div class="wrap">
  <header>
    <a href="index.php" class="logo"><img width="1408" height="768" src="images/logos/od9-logo.png" alt="OD9"></a>
    <p class="tag">5 entities, one mission</p>
    <h1>The OD9 Ecosystem</h1>
    <p class="lede">
      OD9 is not just a Discord. OD9 is both the <strong style="color:var(--b)">philosophical layer</strong>
      AND <strong style="color:var(--b)">a music collective</strong> at the center of a five-entity ecosystem
      built to push humanity past coordination failure and toward Type I civilization.
      Each entity has a distinct job. Together, they compound.
    </p>
  </header>

  <div class="diagram" aria-label="Ecosystem diagram showing OD9, F.R.E.S.H., FTB, Synaptiq, and NCZ relationships">
    <svg viewBox="0 0 700 380" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Ecosystem connection diagram">
      <defs>
        <filter id="glow"><feGaussianBlur stdDeviation="2.5" result="g"/><feMerge><feMergeNode in="g"/><feMergeNode in="SourceGraphic"/></feMerge></filter>
      </defs>
      <!-- Connecting lines -->
      <line x1="350" y1="190" x2="180" y2="100" stroke="#444" stroke-width="2" stroke-dasharray="4,4"/>
      <line x1="350" y1="190" x2="520" y2="100" stroke="#444" stroke-width="2" stroke-dasharray="4,4"/>
      <line x1="350" y1="190" x2="180" y2="280" stroke="#444" stroke-width="2" stroke-dasharray="4,4"/>
      <line x1="350" y1="190" x2="520" y2="280" stroke="#444" stroke-width="2" stroke-dasharray="4,4"/>

      <!-- OD9 in center -->
      <circle cx="350" cy="190" r="58" fill="#0a0a0a" stroke="#00BFFF" stroke-width="3" filter="url(#glow)"/>
      <text x="350" y="183" text-anchor="middle" fill="#00BFFF" font-family="Orbitron, sans-serif" font-weight="900" font-size="22">OD9</text>
      <text x="350" y="202" text-anchor="middle" fill="#999" font-family="Rajdhani, sans-serif" font-size="10">PHILOSOPHY</text>
      <text x="350" y="216" text-anchor="middle" fill="#999" font-family="Rajdhani, sans-serif" font-size="10">+ MUSIC</text>

      <!-- F.R.E.S.H. top-left -->
      <circle cx="180" cy="100" r="46" fill="#0a0a0a" stroke="#00E89A" stroke-width="2.5" filter="url(#glow)"/>
      <text x="180" y="98" text-anchor="middle" fill="#00E89A" font-family="Orbitron, sans-serif" font-weight="700" font-size="14">F.R.E.S.H.</text>
      <text x="180" y="115" text-anchor="middle" fill="#888" font-family="Rajdhani, sans-serif" font-size="10">FINANCIAL ENGINE</text>

      <!-- Synaptiq top-right -->
      <circle cx="520" cy="100" r="46" fill="#0a0a0a" stroke="#FF8A3D" stroke-width="2.5" filter="url(#glow)"/>
      <text x="520" y="98" text-anchor="middle" fill="#FF8A3D" font-family="Orbitron, sans-serif" font-weight="700" font-size="14">Synaptiq</text>
      <text x="520" y="115" text-anchor="middle" fill="#888" font-family="Rajdhani, sans-serif" font-size="10">INTELLIGENCE</text>

      <!-- FTB bottom-left -->
      <circle cx="180" cy="280" r="46" fill="#0a0a0a" stroke="#A55FFF" stroke-width="2.5" filter="url(#glow)"/>
      <text x="180" y="278" text-anchor="middle" fill="#A55FFF" font-family="Orbitron, sans-serif" font-weight="700" font-size="14">FTB</text>
      <text x="180" y="295" text-anchor="middle" fill="#888" font-family="Rajdhani, sans-serif" font-size="10">MUSIC / CULTURE</text>

      <!-- NCZ bottom-right -->
      <circle cx="520" cy="280" r="46" fill="#0a0a0a" stroke="#FFD700" stroke-width="2.5" filter="url(#glow)"/>
      <text x="520" y="278" text-anchor="middle" fill="#FFD700" font-family="Orbitron, sans-serif" font-weight="700" font-size="14">NCZ</text>
      <text x="520" y="295" text-anchor="middle" fill="#888" font-family="Rajdhani, sans-serif" font-size="10">THE SHOW</text>
    </svg>
    <p class="diagram-caption">OD9 in the center: each spoke is a distinct entity that funds, feeds, or amplifies the philosophical core.</p>
  </div>

  <div class="entities">
    <div class="entity od9">
      <div class="entity-name">OD9</div>
      <div class="entity-role">Philosophy + Music · You are here</div>
      <p class="entity-desc">
        The umbrella. OD9 is two things at once: the philosophical framework
        (manifesto, ASCEND Protocol, Type I thesis, Discord community) and a
        Chicago-based hip-hop / R&amp;B / funk collective (The Ultimate Rage,
        Deezle Deez, P-Mac, L.B., Joey P., catalog spans Outrageous EP,
        Phuryous Stylez, Missing In Action, and more). Ideas and tracks ship
        from the same address.
      </p>
      <a href="framework.php" class="entity-link">→ The Framework</a>
      &nbsp;·&nbsp;
      <a href="music.php" class="entity-link">→ The Music</a>
    </div>

    <div class="entity fresh">
      <div class="entity-name">F.R.E.S.H.</div>
      <div class="entity-role">Financial engine · Brand intelligence platform</div>
      <p class="entity-desc">
        The shared platform — multi-tenant SaaS that sells brand-pipeline tools,
        agent-readiness automation, and content engines to outside operators.
        F.R.E.S.H. funds the whole ecosystem through its own market revenue.
      </p>
      <a href="https://freshthaplatform.com" target="_blank" rel="noopener" class="entity-link">→ freshthaplatform.com</a>
    </div>

    <div class="entity ftb">
      <div class="entity-name">FTB</div>
      <div class="entity-role">Music · Fresh Tha Band proof-point</div>
      <p class="entity-desc">
        Fresh Tha Band — the music project that proves the F.R.E.S.H. stack
        works on a real artist before it's sold to anyone else. FTB is the
        case study that gives F.R.E.S.H. its credibility; it sits inside the
        OD9 music orbit and shares the catalog space with the rest of the
        crew.
      </p>
      <a href="https://freshthaband.com" target="_blank" rel="noopener" class="entity-link">→ FTB site</a>
    </div>

    <div class="entity synaptiq">
      <div class="entity-name">Synaptiq</div>
      <div class="entity-role">Intelligence layer · Neuroscience-backed agency tool</div>
      <p class="entity-desc">
        The secret weapon. Neuroscience-grounded brand intelligence that makes
        OD9 messaging harder to ignore at scale. In private alpha — Pioneer
        and Founding Patron tiers get first access when the beta opens.
      </p>
      <a href="support.php" class="entity-link">→ Patron tiers</a>
    </div>

    <div class="entity ncz">
      <div class="entity-name">NCZ</div>
      <div class="entity-role">The show · No Cap Zone</div>
      <p class="entity-desc">
        The publishing arm. News analysis, manifesto unpacks, deep-dive
        interviews. NCZ takes OD9 ideas and pushes them into the mainstream
        attention cycle through long-form video on YouTube + clips everywhere
        else.
      </p>
      <a href="ncz.php" class="entity-link">→ Watch NCZ</a>
    </div>
  </div>

  <div class="synthesis">
    <h2>Why five entities, not one?</h2>
    <p>
      <strong>Compounding leverage.</strong> A single brand has linear growth.
      Five connected entities cross-promote, cross-fund, and reinforce each
      other's authority. F.R.E.S.H. revenue funds OD9 development; OD9
      manifesto chapters become NCZ episodes; NCZ episodes drive Discord
      signups; Discord members become F.R.E.S.H. customers; FTB's audience
      finds OD9 through the music; Synaptiq makes all four messages sharper.
    </p>
    <p>
      <strong>Risk decoupling.</strong> If one entity stalls, the others keep
      compounding. Patreon flagging an account doesn't kill the music. A
      label dispute doesn't kill the platform. A platform outage doesn't
      kill the philosophy.
    </p>
    <p>
      <strong>Yin-Yang flow.</strong> Money flows from F.R.E.S.H. customers →
      OD9 mission. Cultural authority flows from FTB → F.R.E.S.H. credibility.
      Insight flows from Synaptiq → all four. The arrows go both ways.
    </p>
  </div>

  <div class="bottom-links">
    <a href="index.php">Home</a>
    <a href="framework.php">Framework</a>
    <a href="tiers.php">Tiers</a>
    <a href="support.php">Why Patreon</a>
    <a href="progress.php">Live progress</a>
    <a href="https://discord.gg/spgmrXVMWq" target="_blank">Discord</a>
  </div>
</div>
</body>
</html>
