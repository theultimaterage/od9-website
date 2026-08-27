<?php
/**
 * zones.php — "The Five Zones" cinematic scroll prototype (ASCEND world layer).
 *
 * PROTOTYPE of the scroll-driven treatment proposed for the OD9/ASCEND web
 * redesign: a scroll-SCRUBBED background video (the S7-S9 five-zones flythrough,
 * re-encoded intra-only so currentTime seeks land instantly), a headline that
 * folds away character-by-character with scroll, a zone waypoint rail that
 * tracks progress through the five canon zones, and staggered blur-to-sharp
 * zone cards. Scroll position IS the timeline — fully reversible.
 *
 * Canon: zone names / guides / climb lines come from docs/PROGRESSION_WORLD_SPEC.md
 * ("zones as level-maps" table). Zone accents use the metaworld palette from the
 * guide-video scripts (neon cyan #00FFF7 / electric violet #7A00FF / midnight
 * #0B0B0B); tier chip colors follow the tier canon (gray/blue/purple/gold/crimson).
 *
 * Spec rule honored (PROGRESSION_WORLD_SPEC.md §2): video is NEVER load-bearing.
 * The poster still (.zx-still) sits under the <video>; every text layer runs off
 * scroll alone; a video error adds .zx-dead and the page degrades to the still.
 * Fail-open like ztrans — content can never be trapped behind the cinematics.
 *
 * !! MINIFY-SAFE — the origin HTML minifier strips newlines from PHP output, so
 * NO `//` line comments inside the inline <script> (they would swallow the rest
 * of the collapsed line — the 2026-06-20 ztrans bug). Block comments only; every
 * statement semicolon-terminated.
 *
 * Assets: /video/zones/five-zones-scrub.{mp4,jpg} — mp4 is 8.6MB h264 intra-only
 * (-g 1) 1280x720, tracked via git-LFS like video/transitions/* (deploys must
 * ship real bytes, not LFS pointers).
 *
 * STATUS: prototype — noindex'd. Remove $page_robots when the page ships.
 */
$page_title          = 'The Five Zones | OD9 ASCEND';
$page_description    = 'Five zones. One climb. The ASCEND Protocol renders the ladder as a world — The Wake, The Diagnostic, The Forge, The Bridge, The Horizon.';
$page_slug           = 'zones.php';
$page_og_title       = 'The Five Zones — OD9 ASCEND';
$page_og_image       = '/video/zones/five-zones-scrub.jpg';
$page_og_description = 'Scroll the ascent: from waking up inside the collapse to holding the horizon. The OD9 progression world.';

/* Canon zone table — docs/PROGRESSION_WORLD_SPEC.md §2. Climb lines verbatim.
   Chip colors = the official tier-ladder tokens (od9.css --t-*, mirroring
   design-system/tokens/colors.css — gold arrives at Pioneer, "iced out"). */
$zx_zones = [
    ['tier' => 'Observer',   'zone' => 'The Wake',       'guide' => 'The Archivist',   'line' => 'Wake up to the coordination failures', 'c' => 'var(--t-observer)'],
    ['tier' => 'Theorist',   'zone' => 'The Diagnostic', 'guide' => 'The Archivist',   'line' => 'Learn to diagnose the systems',        'c' => 'var(--t-theorist)'],
    ['tier' => 'Architect',  'zone' => 'The Forge',      'guide' => 'The Forgemaster', 'line' => 'Build solutions',                      'c' => 'var(--t-architect)'],
    ['tier' => 'Pioneer',    'zone' => 'The Bridge',     'guide' => 'The Navigator',   'line' => 'Lead + connect',                       'c' => 'var(--t-pioneer)'],
    ['tier' => 'Benefactor', 'zone' => 'The Horizon',    'guide' => 'The Watcher',     'line' => 'Sustain civilization toward Type I',   'c' => 'var(--t-benefactor)'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include __DIR__ . '/includes/head.php'; ?>
<style>
  @font-face{font-family:'Chakra Petch';font-weight:700;font-style:normal;font-display:swap;src:url('<?= $_bp ?>/css/fonts/chakra-petch-700.woff2') format('woff2')}
  @font-face{font-family:'Space Grotesk';font-weight:400;font-style:normal;font-display:swap;src:url('<?= $_bp ?>/css/fonts/space-grotesk-400.woff2') format('woff2')}
  @font-face{font-family:'Space Grotesk';font-weight:600;font-style:normal;font-display:swap;src:url('<?= $_bp ?>/css/fonts/space-grotesk-600.woff2') format('woff2')}
  @font-face{font-family:'Space Mono';font-weight:400;font-style:normal;font-display:swap;src:url('<?= $_bp ?>/css/fonts/space-mono-400.woff2') format('woff2')}
  @font-face{font-family:'Space Mono';font-weight:700;font-style:normal;font-display:swap;src:url('<?= $_bp ?>/css/fonts/space-mono-700.woff2') format('woff2')}

  :root{
    /* zone palette comes from od9.css (--zone-*, --t-*; master:
       design-system/tokens/colors.css) — no local hex copies */
    --zx-void:var(--zone-void); --zx-raise:#12141A; --zx-bone:#EEF2F7; --zx-dim:#A9B2C0; --zx-ash:#788393;
    --zx-cyan:var(--zone-cyan);
    --zx-line:rgba(200,210,225,.13);
    --f-display:'Chakra Petch','Arial Narrow',system-ui,sans-serif;
    --f-body:'Space Grotesk',system-ui,-apple-system,'Segoe UI',Roboto,sans-serif;
    --f-mono:'Space Mono',ui-monospace,'SF Mono',Consolas,monospace;
    --zx-pad:clamp(1.25rem,5vw,4rem);
  }
  html{scroll-behavior:auto}
  html,body{margin:0;padding:0}
  /* immersive standalone (awaken.php pattern): no universal nav, so drop its offset */
  body{padding-top:0;background:var(--zx-void);color:var(--zx-bone);font-family:var(--f-body);line-height:1.6;overflow-x:hidden;-webkit-font-smoothing:antialiased}
  .zx *{box-sizing:border-box}
  .zx ::selection{background:var(--zx-cyan);color:#03211f}
  .zx :focus-visible{outline:2px solid var(--zx-cyan);outline-offset:3px;border-radius:2px}
  .zx a{color:inherit}

  /* ---- corner chrome ---- */
  .zx-brand{position:fixed;top:clamp(.9rem,2.6vh,1.8rem);left:var(--zx-pad);z-index:40}
  .zx-brand img{height:clamp(30px,4vw,42px);width:auto;display:block;filter:drop-shadow(0 2px 14px rgba(0,0,0,.7));opacity:.92}
  .zx-brand:hover img{opacity:1}
  .zx-skip{position:fixed;top:clamp(1rem,2.8vh,2rem);right:var(--zx-pad);z-index:40;
    font:700 .68rem/1 var(--f-mono);letter-spacing:.22em;text-transform:uppercase;text-decoration:none;
    color:var(--zx-dim);background:rgba(11,11,11,.55);border:1px solid var(--zx-line);border-radius:6px;
    padding:.62em 1em;backdrop-filter:blur(6px);transition:color .2s,border-color .2s}
  .zx-skip:hover{color:var(--zx-cyan);border-color:var(--zx-cyan)}

  /* ---- the scrub track: tall runway, sticky stage ---- */
  .zx-track{height:460vh;position:relative}
  .zx-stage{position:sticky;top:0;height:100vh;height:100svh;overflow:hidden;isolation:isolate}
  /* still-image baseline UNDER the video — video is never load-bearing */
  .zx-still{position:absolute;inset:0;z-index:-3;background:url('<?= $_bp ?>/video/zones/five-zones-scrub.jpg') center/cover no-repeat}
  .zx-vid{position:absolute;inset:0;z-index:-2;width:100%;height:100%;object-fit:cover;object-position:center}
  .zx-dead .zx-vid{display:none}
  .zx-scrim{position:absolute;inset:0;z-index:-1;pointer-events:none;background:
    linear-gradient(180deg,rgba(6,8,13,.66) 0%,rgba(6,8,13,.18) 34%,rgba(6,8,13,.22) 62%,rgba(6,8,13,.86) 100%),
    radial-gradient(120% 85% at 50% 40%,transparent 40%,rgba(4,6,10,.55) 100%)}

  /* ---- folding headline ---- */
  .zx-head{position:absolute;inset:0;display:grid;place-items:center;text-align:center;padding-inline:var(--zx-pad);perspective:700px}
  .zx-kicker{font:700 .72rem/1 var(--f-mono);letter-spacing:.34em;text-transform:uppercase;color:var(--zx-cyan);text-shadow:0 0 14px rgba(0,255,247,.55);margin:0 0 1.1rem;
    opacity:calc(1 - clamp(0, var(--p,0) * 6, 1));}
  .zx-h1{margin:0;font-family:var(--f-display);font-weight:700;text-transform:uppercase;
    font-size:clamp(2.7rem,11.5vw,9.5rem);line-height:.94;letter-spacing:.01em;color:#fff;
    text-shadow:0 2px 44px rgba(0,0,0,.65),0 0 60px rgba(0,255,247,.12)}
  .zx-h1 .w{display:inline-block;white-space:nowrap;transform-style:preserve-3d}
  .zx-h1 .ch{display:inline-block;transform-origin:50% 100%;
    --k:clamp(0, calc((var(--p,0) - var(--i)) * 4), 1);
    transform:rotateX(calc(var(--k) * -88deg)) translateY(calc(var(--k) * .16em)) scaleY(calc(1 - var(--k) * .12));
    opacity:calc(1 - var(--k) * .96)}
  .zx-sub{font:400 clamp(.98rem,1.6vw,1.18rem)/1.5 var(--f-body);color:var(--zx-dim);max-width:44ch;margin:1.3rem auto 0;
    text-shadow:0 1px 18px rgba(0,0,0,.8);opacity:calc(1 - clamp(0, var(--p,0) * 5, 1))}

  /* ---- waypoint rail (the five zones, progress-lit) ---- */
  .zx-rail{position:absolute;left:var(--zx-pad);bottom:clamp(1.2rem,5vh,2.6rem);z-index:5;display:flex;flex-direction:column;gap:.55rem}
  .zx-wp{display:flex;align-items:baseline;gap:.7rem;opacity:.34;transition:opacity .25s ease}
  .zx-wp .ix{font:700 .62rem/1 var(--f-mono);letter-spacing:.18em;color:var(--zx-ash);min-width:2.1em}
  .zx-wp .zn{font:700 clamp(.86rem,1.5vw,1.05rem)/1.1 var(--f-display);text-transform:uppercase;letter-spacing:.06em;color:var(--zx-bone)}
  .zx-wp .tl{font:400 .72rem/1.2 var(--f-mono);letter-spacing:.1em;text-transform:uppercase;color:var(--zx-ash)}
  .zx-wp .cl{display:none;font:400 .82rem/1.35 var(--f-body);color:var(--zx-dim);letter-spacing:.01em}
  .zx-wp.on{opacity:1}
  .zx-wp.on .ix{color:var(--zx-cyan)}
  .zx-wp.on .zn{color:var(--zx-cyan);text-shadow:0 0 16px rgba(0,255,247,.5)}
  .zx-wp.on .cl{display:block}
  .zx-wp.on .tl{color:var(--zx-dim)}

  /* ---- scroll cue ---- */
  .zx-cue{position:absolute;left:50%;bottom:clamp(1.1rem,4.5vh,2.2rem);transform:translateX(-50%);z-index:5;
    display:flex;flex-direction:column;align-items:center;gap:.45rem;color:var(--zx-dim);
    font:700 .62rem/1 var(--f-mono);letter-spacing:.4em;text-transform:uppercase;transition:opacity .4s ease}
  .zx-cue.off{opacity:0}
  .zx-cue .vee{width:12px;height:12px;border-right:2px solid var(--zx-cyan);border-bottom:2px solid var(--zx-cyan);
    transform:rotate(45deg);animation:zxBob 1.6s ease-in-out infinite}
  @keyframes zxBob{0%,100%{transform:rotate(45deg) translate(0,0);opacity:.9}50%{transform:rotate(45deg) translate(4px,4px);opacity:.45}}

  /* ---- the ascent panel (rises over the stage) ---- */
  .zx-rise{position:relative;z-index:10;background:var(--zx-void);border-radius:26px 26px 0 0;
    margin-top:-7vh;box-shadow:0 -30px 80px rgba(0,0,0,.75);border-top:1px solid var(--zx-line);
    padding:clamp(3.6rem,9vh,6.5rem) var(--zx-pad) clamp(3rem,8vh,5rem)}
  .zx-wrap{max-width:1160px;margin-inline:auto}
  .zx-k2{font:700 .72rem/1 var(--f-mono);letter-spacing:.3em;text-transform:uppercase;color:var(--zx-cyan);margin:0 0 .9rem}
  .zx-h2{margin:0 0 1.1rem;font-family:var(--f-display);font-weight:700;text-transform:uppercase;
    font-size:clamp(1.9rem,4.6vw,3.6rem);line-height:1;color:#fff}
  .zx-lede{max-width:62ch;color:var(--zx-dim);font-size:clamp(1rem,1.5vw,1.16rem);margin:0 0 3rem}
  .zx-lede strong{color:var(--zx-bone);font-weight:600}

  .zx-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(205px,1fr));gap:1rem;margin:0 0 3rem}
  .zx-card{--i:0;position:relative;border:1px solid var(--zx-line);border-radius:12px;padding:1.35rem 1.2rem 1.25rem;
    background:linear-gradient(180deg,var(--zx-raise),rgba(11,11,11,.6));overflow:hidden;
    opacity:0;transform:translateY(26px) scale(.96);filter:blur(14px);
    transition:opacity .7s ease,transform .7s ease,filter .7s ease;transition-delay:calc(var(--i) * 95ms)}
  .zx-card.in{opacity:1;transform:none;filter:none}
  .zx-card::before{content:"";position:absolute;inset:0 0 auto 0;height:3px;background:var(--zc,var(--zx-cyan));opacity:.85}
  .zx-card .tier{font:700 .6rem/1 var(--f-mono);letter-spacing:.24em;text-transform:uppercase;color:var(--zc,var(--zx-cyan))}
  .zx-card h3{margin:.55rem 0 .5rem;font-family:var(--f-display);font-weight:700;text-transform:uppercase;
    font-size:1.22rem;line-height:1.05;color:#fff;letter-spacing:.03em}
  .zx-card .climb{margin:0 0 .9rem;color:var(--zx-dim);font-size:.92rem;line-height:1.45}
  .zx-card .guide{margin:0;font:400 .7rem/1.3 var(--f-mono);letter-spacing:.08em;text-transform:uppercase;color:var(--zx-ash)}
  .zx-card .guide b{color:var(--zx-dim);font-weight:700}

  .zx-note{max-width:62ch;color:var(--zx-ash);font-size:.92rem;margin:0 0 2.2rem}
  .zx-cta{display:flex;flex-wrap:wrap;gap:1rem;align-items:center}
  .zx-btn{font:700 .8rem/1 var(--f-mono);letter-spacing:.14em;text-transform:uppercase;text-decoration:none;
    display:inline-flex;align-items:center;gap:.6em;padding:.95em 1.5em;border-radius:4px;
    transition:transform .18s ease,box-shadow .18s ease,background .18s ease,border-color .18s ease}
  .zx-btn-primary{background:linear-gradient(135deg,var(--zx-cyan),#00BFFF);color:#03211f;box-shadow:0 0 24px rgba(0,255,247,.35)}
  .zx-btn-primary:hover{transform:translateY(-2px);box-shadow:0 0 34px rgba(0,255,247,.55)}
  .zx-btn-ghost{border:1px solid rgba(0,255,247,.4);background:rgba(0,255,247,.05);color:var(--zx-bone)}
  .zx-btn-ghost:hover{background:rgba(0,255,247,.12);border-color:var(--zx-cyan)}

  .zx-foot{padding:2.2rem var(--zx-pad) 2.6rem;border-top:1px solid var(--zx-line);background:var(--zx-void)}
  .zx-foot .zx-wrap{display:flex;flex-wrap:wrap;gap:1rem;justify-content:space-between;align-items:center}
  .zx-foot p{margin:0;font:400 .72rem/1.4 var(--f-mono);letter-spacing:.14em;text-transform:uppercase;color:var(--zx-ash)}
  .zx-foot nav{display:flex;gap:1.4rem}
  .zx-foot a{font:700 .72rem/1 var(--f-mono);letter-spacing:.14em;text-transform:uppercase;text-decoration:none;color:var(--zx-dim);transition:color .2s}
  .zx-foot a:hover{color:var(--zx-cyan)}

  @media (max-width:720px){
    .zx-track{height:420vh}
    .zx-wp .tl{display:none}
    .zx-wp .zn{font-size:.82rem}
    .zx-wp.on .cl{display:none}
    .zx-rail{gap:.4rem;bottom:4.6rem}
  }
</style>
</head>
<body class="zx">

<a class="zx-brand" href="<?= $_bp ?>/" aria-label="OD9 home">
  <img src="<?= $_bp ?>/images/logos/od9-logo.png" alt="OD9">
</a>
<a class="zx-skip" href="#ascent">Skip &rsaquo;</a>

<div class="zx-track" id="zxTrack">
  <div class="zx-stage">
    <div class="zx-still" aria-hidden="true"></div>
    <video class="zx-vid" id="zxVid" muted playsinline preload="auto"
           poster="<?= $_bp ?>/video/zones/five-zones-scrub.jpg" aria-hidden="true">
      <source src="<?= $_bp ?>/video/zones/five-zones-scrub.mp4" type="video/mp4">
    </video>
    <div class="zx-scrim" aria-hidden="true"></div>

    <div class="zx-head" id="zxHead">
      <div>
        <p class="zx-kicker">OD9 // ASCEND Protocol</p>
        <h1 class="zx-h1" aria-label="The Five Zones"><?php
          /* split per character so each glyph can fold on scroll; words stay unbreakable */
          $zx_ci = 0;
          $zx_words = ['THE', 'FIVE', 'ZONES'];
          foreach ($zx_words as $zx_wi => $zx_w) {
              echo '<span class="w" aria-hidden="true">';
              foreach (str_split($zx_w) as $zx_ch) {
                  printf('<span class="ch" style="--i:%.3f">%s</span>', $zx_ci * 0.018, $zx_ch);
                  $zx_ci++;
              }
              echo '</span>';
              if ($zx_wi < count($zx_words) - 1) { echo "\n          "; }
          }
        ?></h1>
        <p class="zx-sub">Every tier is a place. Scroll to climb from the collapse everyone can see to the horizon almost no one can hold.</p>
      </div>
    </div>

    <div class="zx-rail" aria-hidden="true">
      <?php foreach ($zx_zones as $zx_i => $zx_z): ?>
      <div class="zx-wp<?= $zx_i === 0 ? ' on' : '' ?>">
        <span class="ix">0<?= $zx_i + 1 ?></span>
        <span>
          <span class="zn"><?= htmlspecialchars($zx_z['zone']) ?></span>
          <span class="tl"><?= htmlspecialchars($zx_z['tier']) ?></span>
          <span class="cl"><?= htmlspecialchars($zx_z['line']) ?></span>
        </span>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="zx-cue" id="zxCue"><span>Scroll to ascend</span><span class="vee"></span></div>
  </div>
</div>

<section class="zx-rise" id="ascent">
  <div class="zx-wrap">
    <p class="zx-k2">The Ascent</p>
    <h2 class="zx-h2">Five zones. One climb.</h2>
    <p class="zx-lede">The ASCEND Protocol renders progression as a world: <strong>each tier is a zone</strong>, each zone a level-map of work that actually matters, ending at a Gate. No shortcuts, no purchased rank &mdash; <strong>credits, dimensions, demonstrations</strong>. The climb is the point.</p>

    <div class="zx-grid">
      <?php foreach ($zx_zones as $zx_i => $zx_z): ?>
      <article class="zx-card" style="--i:<?= $zx_i ?>;--zc:<?= $zx_z['c'] ?>">
        <span class="tier"><?= htmlspecialchars($zx_z['tier']) ?></span>
        <h3><?= htmlspecialchars($zx_z['zone']) ?></h3>
        <p class="climb"><?= htmlspecialchars($zx_z['line']) ?>.</p>
        <p class="guide">Guide &mdash; <b><?= htmlspecialchars($zx_z['guide']) ?></b></p>
      </article>
      <?php endforeach; ?>
    </div>

    <p class="zx-note">Everyone spawns in The Wake. Where you go from there is demonstrated, never bought &mdash; the Gate at the end of every zone checks the work, not the wallet.</p>

    <div class="zx-cta">
      <a class="zx-btn zx-btn-primary" href="<?= $_bp ?>/tiers.php">See the full ladder <span aria-hidden="true">&rarr;</span></a>
      <a class="zx-btn zx-btn-ghost" href="<?= $_bp ?>/join.php">Enter The Wake</a>
    </div>
  </div>
</section>

<footer class="zx-foot">
  <div class="zx-wrap">
    <p>Off Da Nine &mdash; grow up before we blow up.</p>
    <nav>
      <a href="<?= $_bp ?>/">Home</a>
      <a href="<?= $_bp ?>/tiers.php">The Ladder</a>
      <a href="<?= $_bp ?>/join.php">Join</a>
    </nav>
  </div>
</footer>

<script>
(function(){
  /* MINIFY-SAFE: block comments only, every statement semicolon-terminated. */
  var track = document.getElementById('zxTrack');
  var vid   = document.getElementById('zxVid');
  var head  = document.getElementById('zxHead');
  var cue   = document.getElementById('zxCue');
  if (!track || !vid) { return; }
  var wps = [].slice.call(document.querySelectorAll('.zx-wp'));
  var shown = 0, last = -1, unlocked = false;

  /* Read duration LIVE each frame — never cache it via a loadedmetadata
     listener. With preload=auto + faststart the metadata can land BEFORE this
     script attaches listeners (it does on fast connections), and a cached value
     set by a listener that never fires stays 0 forever = scrub silently dead. */
  function getDur(){
    var d = vid.duration;
    return (typeof d === 'number' && isFinite(d) && d > 0) ? d : 0;
  }
  /* fail open: video error -> hide it, the .zx-still baseline carries the page.
     A missing file errors on the LAST <source>, not the <video> (ztrans lesson);
     and an error that fired BEFORE attach must be caught by inspecting state. */
  function dead(){ document.documentElement.classList.add('zx-dead'); }
  vid.addEventListener('error', dead);
  var ls = vid.querySelector('source:last-of-type');
  if (ls) { ls.onerror = dead; }
  if (vid.error || vid.networkState === 3) { dead(); }

  /* iOS/Safari decode unlock on the first gesture; harmless elsewhere */
  function unlock(){
    if (unlocked) { return; }
    unlocked = true;
    var p = vid.play();
    if (p && p.then) { p.then(function(){ vid.pause(); }).catch(function(){}); }
  }
  window.addEventListener('touchstart', unlock, { once: true, passive: true });
  window.addEventListener('scroll',     unlock, { once: true, passive: true });

  function prog(){
    var r = track.getBoundingClientRect();
    var total = r.height - window.innerHeight;
    var p = total > 0 ? (-r.top) / total : 0;
    return p < 0 ? 0 : (p > 1 ? 1 : p);
  }

  function frame(){
    var p = prog();
    if (Math.abs(p - last) > 0.0004) {
      last = p;
      var dur = getDur();
      if (dur > 0 && vid.readyState > 1) {
        var t = p * Math.max(0, dur - 0.05);
        if (Math.abs((vid.currentTime || 0) - t) > 0.02) {
          try { vid.currentTime = t; } catch (e) {}
        }
      }
      if (head) { head.style.setProperty('--p', p.toFixed(4)); }
      if (cue)  { cue.classList.toggle('off', p > 0.04); }
      var ix = Math.min(4, Math.floor(p * 5));
      if (ix !== shown) {
        shown = ix;
        var i;
        for (i = 0; i < wps.length; i++) { wps[i].classList.toggle('on', i === ix); }
      }
    }
    window.requestAnimationFrame(frame);
  }
  window.requestAnimationFrame(frame);

  /* staggered blur-to-sharp reveal for the zone cards */
  var cards = [].slice.call(document.querySelectorAll('.zx-card'));
  if ('IntersectionObserver' in window) {
    var io = new IntersectionObserver(function(es){
      es.forEach(function(en){
        if (en.isIntersecting) { en.target.classList.add('in'); io.unobserve(en.target); }
      });
    }, { threshold: 0.22 });
    cards.forEach(function(c){ io.observe(c); });
  } else {
    cards.forEach(function(c){ c.classList.add('in'); });
  }
})();
</script>
</body>
</html>
