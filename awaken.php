<?php
/**
 * awaken.php — OD9 immersive recruitment landing page ("Grow Up Before We Blow Up").
 * Standalone / no universal nav by design (full-bleed cinematic hero). Includes
 * head.php for centralized SEO/OG/schema, then overrides the nav body-padding and
 * self-hosts the design-system type (Chakra Petch / Space Grotesk / Space Mono).
 * Assets served from /videos + /images/landing; CTAs split: Enter -> site, ASCEND -> Discord.
 */
$page_title       = 'Grow Up Before We Blow Up | OD9 — Off Da Nine';
$page_description = 'The Doomsday Clock sits at 85 seconds to midnight and almost everyone is asleep. OD9 is the real world — where the awake stop describing the collapse and start building the way out toward a Type-1 civilization.';
$page_slug        = 'awaken.php';
$page_og_title    = 'Grow Up Before We Blow Up';
$page_og_image    = '/images/landing/hero-poster.jpg';
$page_og_description = 'You are not crazy. You are awake. This is where the ones who refuse to look away build the way out. Off Da Nine.';
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
    --void:#0D0D0D; --void-2:#121316; --raise:#181A1F;
    --bone:#EEF2F7; --bone-dim:#A9B2C0; --ash:#788393;
    --blue:#00BFFF; --blue-2:#00A0FF; --gold:#FFD700;
    --line:rgba(200,210,225,.12); --line-2:rgba(200,210,225,.06);
    --blue-line:rgba(0,191,255,.42); --glow:0 0 24px rgba(0,191,255,.40);
    --measure:62ch;
    --f-display:'Chakra Petch','Arial Narrow',system-ui,sans-serif;
    --f-body:'Space Grotesk',system-ui,-apple-system,'Segoe UI',Roboto,sans-serif;
    --f-mono:'Space Mono',ui-monospace,'SF Mono',Consolas,monospace;
    --page:min(1160px,100%); --pad:clamp(1.25rem,5vw,4rem);
  }
  /* immersive: no nav, so drop the od9.css fixed-header body padding */
  html,body{margin:0;padding:0}
  body{padding-top:0;background:var(--void);color:var(--bone);font-family:var(--f-body);font-size:clamp(1.02rem,.55rem + .9vw,1.2rem);line-height:1.62;overflow-x:hidden;-webkit-font-smoothing:antialiased}
  .lp *{box-sizing:border-box}
  .lp ::selection{background:var(--blue);color:#04121a}
  .lp a{color:inherit}
  .lp :focus-visible{outline:2px solid var(--blue);outline-offset:3px;border-radius:2px}
  .wrap{width:var(--page);margin-inline:auto;padding-inline:var(--pad)}

  .lp .kicker{font-family:var(--f-mono);font-size:.72rem;letter-spacing:.26em;text-transform:uppercase;color:var(--blue);margin:0}
  .lp .kicker.ash{color:var(--ash)} .lp .kicker.gold-k{color:var(--gold)}
  .lp h1,.lp h2,.lp h3{font-family:var(--f-display);font-weight:700;text-transform:uppercase;text-wrap:balance;margin:0;letter-spacing:.005em}
  .lp h1{font-size:clamp(2.9rem,1rem + 10vw,7.6rem);line-height:.94}
  .lp h2{font-size:clamp(1.9rem,1rem + 3.6vw,3.5rem);line-height:1.0}
  .lp h3{font-size:1.04rem;letter-spacing:.01em;line-height:1.18}
  .lp p{margin:0}
  .lp .lede{font-size:clamp(1.14rem,.8rem + 1.1vw,1.42rem);line-height:1.5;max-width:36ch}
  .lp .body{max-width:var(--measure);color:var(--bone-dim)}
  .lp .body strong{color:var(--bone);font-weight:600}
  .lp .blue{color:var(--blue)} .lp .gold{color:var(--gold)}

  .lp .cta-row{display:flex;flex-wrap:wrap;gap:1rem;align-items:center;margin-top:.5rem}
  .lp .btn{font-family:var(--f-mono);font-size:.8rem;font-weight:700;letter-spacing:.14em;text-transform:uppercase;text-decoration:none;display:inline-flex;align-items:center;gap:.6em;padding:.95em 1.5em;border-radius:3px;transition:transform .18s ease,background .18s ease,box-shadow .18s ease,border-color .18s ease}
  .lp .btn-primary{background:linear-gradient(135deg,var(--blue),var(--blue-2));color:#04121a;box-shadow:var(--glow)}
  .lp .btn-primary:hover{transform:translateY(-2px);box-shadow:0 0 30px rgba(0,191,255,.6)}
  .lp .arrow{transition:transform .18s ease} .lp .btn:hover .arrow{transform:translateX(4px)}
  .lp .btn-ascend{display:inline-flex;align-items:center;justify-content:center;padding:.62em 1.35em;border:1px solid var(--blue-line);border-radius:3px;background:rgba(0,191,255,.05);transition:background .18s ease,border-color .18s ease,box-shadow .18s ease}
  .lp .btn-ascend img{height:1.65em;width:auto;display:block;mix-blend-mode:screen}
  .lp .btn-ascend:hover{background:rgba(0,191,255,.12);border-color:var(--blue);box-shadow:var(--glow)}

  .lp .hero{position:relative;min-height:96svh;display:flex;align-items:center;isolation:isolate;overflow:hidden}
  .lp .hero-video{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;object-position:center;z-index:-2}
  .lp .hero-scrim{position:absolute;inset:0;z-index:-1;pointer-events:none;background:linear-gradient(90deg,rgba(13,13,13,.93) 0%,rgba(13,13,13,.74) 34%,rgba(13,13,13,.30) 62%,rgba(13,13,13,.5) 100%),linear-gradient(180deg,rgba(13,13,13,.5),rgba(13,13,13,.2) 42%,rgba(13,13,13,.9)),radial-gradient(120% 90% at 18% 26%,rgba(0,130,190,.16),transparent 58%)}
  .lp .hero .wrap{width:var(--page);padding-block:clamp(6rem,15vh,9rem)}
  .lp .hero-inner{display:flex;flex-direction:column;gap:clamp(1.4rem,3vw,2rem);max-width:21ch}
  .lp .hero h1{max-width:15ch;text-shadow:0 2px 40px rgba(0,0,0,.6)}
  .lp .hero-sub{max-width:40ch}
  .lp .brandmark{position:absolute;top:clamp(1rem,3.2vh,2.1rem);left:var(--pad);z-index:5;display:block}
  .lp .brandmark img{height:clamp(30px,4.2vw,44px);width:auto;display:block;filter:drop-shadow(0 2px 14px rgba(0,0,0,.65));opacity:.92;transition:opacity .2s ease}
  .lp .brandmark:hover img{opacity:1}

  /* ---- scroll-scrub hero (JS adds body.scrub on capable desktops ONLY:
     min-width 760px + motion ok + no data-saver. Without .scrub none of this
     applies and the hero is the original autoplay loop — mobile, no-JS, and
     reduced-motion visitors see exactly the page that shipped before). ---- */
  .lp .hero-track{position:relative}
  .lp.scrub .hero-track{height:230vh}
  .lp.scrub .hero{position:sticky;top:0;min-height:0;height:100vh;height:100svh}
  /* The loop hero is a TALL narrow column (content-driven, ~1.5 viewports at
     1440x900 — the page scrolls past it, so that layout works). The scrub
     stage clamps to one viewport and clips, which would bury the CTA — so in
     scrub mode the column is re-fit to FIT the stage: wider measure, tighter
     type, slimmer padding. Non-scrub layout is untouched. */
  .lp.scrub .hero .wrap{padding-block:clamp(2.2rem,6vh,4.5rem)}
  .lp.scrub .hero .hero-inner{max-width:min(580px,54vw);gap:clamp(.9rem,2vh,1.4rem)}
  .lp.scrub .hero h1{font-size:clamp(2.4rem,1rem + 6.8vw,5.8rem);max-width:none}
  .lp.scrub .hero .hero-sub{font-size:clamp(.98rem,.8rem + .5vw,1.18rem);max-width:46ch}
  .lp .hero h1 .w{display:inline-block;white-space:nowrap;transform-style:preserve-3d}
  .lp .hero h1 .ch{display:inline-block}
  .lp.scrub .hero h1{perspective:640px}
  .lp.scrub .hero h1 .ch{transform-origin:50% 100%;
    --k:clamp(0, calc((var(--p,0) - var(--i,0)) * 4), 1);
    transform:rotateX(calc(var(--k) * -86deg)) translateY(calc(var(--k) * .14em));
    opacity:calc(1 - var(--k) * .95)}
  .lp.scrub .hero .kicker,.lp.scrub .hero .hero-sub{opacity:calc(1 - clamp(0, var(--p,0) * 4.5, 1))}
  .lp.scrub .hero .hero-inner{opacity:calc(1 - clamp(0, (var(--p,0) - .55) * 2.8, 1))}
  .lp .hero-cue{display:none}
  .lp.scrub .hero-cue{position:absolute;left:50%;bottom:1.2rem;transform:translateX(-50%);z-index:5;
    display:flex;flex-direction:column;align-items:center;gap:.4rem;color:var(--bone-dim);
    font-family:var(--f-mono);font-weight:700;font-size:.6rem;letter-spacing:.34em;text-transform:uppercase;
    transition:opacity .4s ease}
  .lp.scrub .hero-cue.off{opacity:0}
  .lp.scrub .hero-cue .vee{width:11px;height:11px;border-right:2px solid var(--blue);border-bottom:2px solid var(--blue);
    transform:rotate(45deg);animation:awBob 1.6s ease-in-out infinite}
  @keyframes awBob{0%,100%{transform:rotate(45deg) translate(0,0);opacity:.9}50%{transform:rotate(45deg) translate(4px,4px);opacity:.45}}

  /* ---- five-zones teaser (official zone tokens from od9.css) ---- */
  .lp .zline{display:flex;flex-wrap:wrap;gap:.7rem;margin-top:2.2rem}
  .lp .zstop{flex:1 1 150px;border-left:3px solid var(--zc,var(--blue));background:var(--void-2);
    border-radius:0 4px 4px 0;padding:.8rem .9rem;display:flex;flex-direction:column;gap:.25rem}
  .lp .zstop b{font-family:var(--f-display);font-weight:700;text-transform:uppercase;font-size:.95rem;color:var(--bone);letter-spacing:.03em}
  .lp .zstop i{font-style:normal;font-family:var(--f-mono);font-size:.62rem;letter-spacing:.18em;text-transform:uppercase;color:var(--zc,var(--ash))}

  .lp .band{padding-block:clamp(4.5rem,11vh,8rem);position:relative}
  .lp .band--line{border-top:1px solid var(--line-2)}
  .lp .eyebrow-row{display:flex;align-items:center;gap:1rem;margin-bottom:1.5rem}
  .lp .eyebrow-row .rule{height:1px;background:var(--blue-line);flex:1;max-width:64px}

  .lp .stakes{border-top:1px solid var(--line-2);background:linear-gradient(180deg,rgba(0,191,255,.03),transparent 30%)}
  .lp .stakes-grid{display:grid;grid-template-columns:1fr;gap:clamp(2rem,5vw,4rem);align-items:center}
  @media(min-width:820px){.lp .stakes-grid{grid-template-columns:minmax(230px,340px) 1fr}}
  .lp .clock-frame{margin:0;aspect-ratio:1/1;border-radius:50%;overflow:hidden;position:relative;border:1px solid var(--blue-line);box-shadow:0 0 70px -12px rgba(0,191,255,.35),inset 0 0 50px rgba(0,0,0,.7)}
  .lp .clock-frame video{width:100%;height:100%;object-fit:cover;object-position:center 46%;display:block}
  .lp .stakes h2 .gold{color:var(--gold)}
  .lp .stakes .body{margin-top:1.4rem}
  .lp .src{display:block;margin-top:1rem;font-family:var(--f-mono);font-size:.68rem;letter-spacing:.14em;text-transform:uppercase;color:var(--ash)}

  .lp .turn h2{max-width:16ch}
  .lp .turn .grid{display:grid;grid-template-columns:1fr;gap:clamp(1.4rem,3vw,2.4rem);margin-top:2.2rem}
  @media(min-width:760px){.lp .turn .grid{grid-template-columns:1fr 1fr;align-items:start}}
  .lp .turn .col .kicker{margin-bottom:.7rem}

  .lp .machine-head{display:flex;flex-direction:column;gap:1rem;max-width:24ch}
  .lp .hands{display:grid;grid-template-columns:1fr;gap:1px;background:var(--line-2);border:1px solid var(--line-2);margin-top:2.6rem;border-radius:4px;overflow:hidden}
  @media(min-width:820px){.lp .hands{grid-template-columns:repeat(3,1fr)}}
  .lp .hand{background:var(--void-2);padding:clamp(1.5rem,2.5vw,2.2rem);display:flex;flex-direction:column;gap:.85rem}
  .lp .hand .tag{font-family:var(--f-mono);font-size:.68rem;letter-spacing:.18em;text-transform:uppercase;color:var(--gold)}
  .lp .hand h3{color:var(--bone)}
  .lp .hand p{color:var(--bone-dim);font-size:.97rem;line-height:1.55}
  .lp .verdict{margin-top:2.4rem;font-family:var(--f-display);text-transform:uppercase;font-size:clamp(1.3rem,.9rem + 1.6vw,2.1rem);line-height:1.08;max-width:26ch;color:var(--bone)}
  .lp .verdict .blue{color:var(--blue)}

  .lp .real{position:relative;overflow:hidden}
  .lp .real::before{content:"";position:absolute;inset:0;z-index:0;pointer-events:none;background:radial-gradient(80% 120% at 88% 8%,rgba(0,191,255,.08),transparent 55%)}
  .lp .real .wrap{position:relative;z-index:1}
  .lp .real h2{max-width:15ch} .lp .real h2 .blue{display:block}
  .lp .creed{display:flex;flex-wrap:wrap;gap:.6rem;margin-top:2rem}
  .lp .chip{font-family:var(--f-mono);font-size:.72rem;letter-spacing:.1em;text-transform:uppercase;color:var(--bone);border:1px solid var(--blue-line);border-radius:999px;padding:.5em 1em}
  .lp .real .body{margin-top:1.8rem;font-size:clamp(1.06rem,.85rem + .7vw,1.26rem);color:var(--bone);max-width:52ch}

  .lp .founder .grid{display:grid;grid-template-columns:1fr;gap:2rem}
  @media(min-width:860px){.lp .founder .grid{grid-template-columns:.85fr 1.15fr;gap:clamp(2rem,5vw,4.5rem);align-items:start}}
  .lp .portrait{position:relative;overflow:hidden;border:1px solid var(--line);border-radius:4px;margin:0;aspect-ratio:4/3;background:var(--void-2)}
  .lp .portrait img{display:block;width:100%;height:100%;object-fit:cover;object-position:62% 36%;filter:contrast(1.06) saturate(1.08)}
  .lp .portrait::after{content:"";position:absolute;inset:0;pointer-events:none;background:linear-gradient(180deg,transparent 52%,rgba(13,13,13,.72))}
  .lp .portrait figcaption{position:absolute;left:0;bottom:0;padding:.75rem 1rem;font-family:var(--f-mono);font-size:.64rem;letter-spacing:.16em;text-transform:uppercase;color:var(--bone);z-index:1}
  .lp .founder .sig{font-family:var(--f-display);text-transform:uppercase;font-size:clamp(1.6rem,1rem + 2.4vw,2.6rem);line-height:1;color:var(--bone)}
  .lp .founder .sig small{display:block;font-family:var(--f-mono);font-weight:400;font-size:.7rem;letter-spacing:.22em;color:var(--blue);margin-bottom:1rem;text-transform:uppercase}
  .lp .founder .body{margin-top:1.4rem}
  .lp .unbought{margin-top:1.6rem;font-family:var(--f-mono);font-size:.78rem;letter-spacing:.12em;text-transform:uppercase;color:var(--ash)}
  .lp .unbought b{color:var(--blue);font-weight:600}

  .lp .cohort{border-top:1px solid var(--line-2)}
  .lp .cohort .inner{display:flex;flex-direction:column;gap:1.3rem;max-width:46ch}
  .lp .count{display:flex;align-items:baseline;gap:.8rem}
  .lp .count .n{font-family:var(--f-display);font-size:clamp(3rem,1rem + 7vw,5.2rem);line-height:.85;color:var(--gold);text-shadow:0 0 30px rgba(255,215,0,.25)}
  .lp .count .lbl{font-family:var(--f-mono);font-size:.76rem;letter-spacing:.14em;text-transform:uppercase;color:var(--ash);max-width:16ch}

  .lp .guides-grid{display:grid;grid-template-columns:1fr;gap:clamp(.8rem,1.8vw,1.15rem);margin-top:2.6rem}
  @media(min-width:480px){.lp .guides-grid{grid-template-columns:repeat(2,1fr)}}
  @media(min-width:760px){.lp .guides-grid{grid-template-columns:repeat(3,1fr)}}
  @media(min-width:1020px){.lp .guides-grid{grid-template-columns:repeat(5,1fr)}}
  .lp .guide{margin:0;background:var(--void-2);border:1px solid var(--line-2);border-radius:4px;overflow:hidden;display:flex;flex-direction:column}
  .lp .guide video{display:block;width:100%;aspect-ratio:16/9;object-fit:cover;background:#000}
  .lp .guide figcaption{padding:.95rem 1rem 1.15rem;display:flex;flex-direction:column;gap:.4rem}
  .lp .guide h3{font-size:.92rem;color:var(--bone)}
  .lp .guide p{color:var(--bone-dim);font-size:.82rem;line-height:1.55}

  .lp .end{border-top:1px solid var(--blue-line);background:linear-gradient(180deg,transparent,rgba(0,191,255,.04))}
  .lp .end h2{font-size:clamp(2.4rem,1rem + 6vw,5.2rem);max-width:14ch;margin-bottom:1.6rem}
  .lp .end .body{margin-bottom:2.2rem}
  .lp .tagline-foot{font-family:var(--f-display);text-transform:uppercase;color:var(--blue);font-size:clamp(1.1rem,.9rem + 1vw,1.7rem);margin-top:4rem;text-shadow:var(--glow)}
  .lp footer{border-top:1px solid var(--line-2);padding-block:2.4rem;color:var(--ash);font-family:var(--f-mono);font-size:.7rem;letter-spacing:.14em;text-transform:uppercase;display:flex;flex-wrap:wrap;gap:.5rem 1.4rem;justify-content:space-between}

  .lp .rise{opacity:0;transform:translateY(18px);transition:opacity .7s ease,transform .7s cubic-bezier(.2,.7,.2,1)}
  .lp .rise.in{opacity:1;transform:none}
  .lp .stagger>*{opacity:0;transform:translateY(16px);transition:opacity .6s ease,transform .6s cubic-bezier(.2,.7,.2,1)}
  .lp .stagger.in>*{opacity:1;transform:none}
  .lp .stagger.in>*:nth-child(2){transition-delay:.08s}
  .lp .stagger.in>*:nth-child(3){transition-delay:.16s}
  .lp .stagger.in>*:nth-child(4){transition-delay:.24s}
  .lp .stagger.in>*:nth-child(5){transition-delay:.32s}
  @media(prefers-reduced-motion:reduce){.lp .rise,.lp .stagger>*{opacity:1;transform:none;transition:none}.lp .btn,.lp .arrow{transition:none}}
</style>
</head>
<body class="lp">
<script>
(function(){
  /* Scrub-mode gate — synchronous so the sticky hero lays out before first
     paint. Capable desktops only; everyone else keeps the autoplay loop
     untouched. MINIFY-SAFE: block comments only, statements semicolon'd. */
  try {
    var mm = window.matchMedia;
    var ok = !!mm && mm('(min-width: 760px)').matches && !mm('(prefers-reduced-motion: reduce)').matches;
    var c = navigator.connection;
    if (c && c.saveData) { ok = false; }
    if (ok) { document.body.className += ' scrub'; }
  } catch (e) {}
})();
</script>
<main>
  <div class="hero-track" id="heroTrack">
  <section class="hero">
    <video class="hero-video" id="heroVid" autoplay muted loop playsinline preload="metadata"
           poster="<?= $_bp ?>/images/landing/hero-poster.jpg"
           data-scrub-src="<?= $_bp ?>/videos/od9-hero-scrub.mp4?v=<?= @filemtime(__DIR__ . '/videos/od9-hero-scrub.mp4') ?: '1' ?>">
      <source src="<?= $_bp ?>/videos/od9-hero.mp4" type="video/mp4">
    </video>
    <div class="hero-scrim"></div>
    <a class="brandmark" href="https://offda9.com" rel="noopener" aria-label="OD9 — Off Da Nine, home"><img src="<?= $_bp ?>/images/logos/od9-logo-nav.png" alt="OD9 — Off Da Nine"></a>
    <div class="wrap">
      <div class="hero-inner stagger">
        <p class="kicker">Off Da Nine &nbsp;//&nbsp; A signal from the real world</p>
        <h1 id="heroH1" aria-label="Grow up before we blow up."><?php
          /* per-char spans so the headline can fold under scrub mode; renders
             identically when .scrub never engages */
          $aw_ci = 0;
          $aw_words = ['Grow', 'up', 'before', 'we', 'blow', 'up.'];
          foreach ($aw_words as $aw_wi => $aw_w) {
              echo '<span class="w" aria-hidden="true">';
              foreach (str_split($aw_w) as $aw_ch) {
                  printf('<span class="ch" style="--i:%.3f">%s</span>', $aw_ci * 0.012, $aw_ch);
                  $aw_ci++;
              }
              echo '</span>';
              if ($aw_wi < count($aw_words) - 1) { echo ' '; }
          }
        ?></h1>
        <p class="hero-sub lede">You've felt it your whole life — the splinter in your mind that says something is deeply, structurally <em>wrong</em> with the world, and almost nobody around you notices. You're not crazy. You're awake.</p>
        <div class="cta-row">
          <a class="btn btn-primary" href="https://offda9.com" rel="noopener">Enter the real world <span class="arrow">&rarr;</span></a>
          <a class="btn-ascend" href="https://discord.gg/spgmrXVMWq" target="_blank" rel="noopener" aria-label="Ascend — join the Discord and begin"><img src="<?= $_bp ?>/images/landing/ascend-wordmark.jpg" alt="ASCEND"></a>
        </div>
      </div>
    </div>
    <div class="hero-cue" id="heroCue" aria-hidden="true"><span>Scroll</span><span class="vee"></span></div>
  </section>
  </div>

  <section class="band stakes">
    <div class="wrap stakes-grid">
      <figure class="clock-frame rise">
        <video autoplay muted loop playsinline preload="metadata" poster="<?= $_bp ?>/images/landing/clock-poster.jpg">
          <source src="<?= $_bp ?>/videos/od9-clock.mp4" type="video/mp4">
        </video>
      </figure>
      <div class="rise">
        <div class="eyebrow-row"><span class="rule"></span><p class="kicker gold-k">The stakes</p></div>
        <h2>85 seconds <span class="gold">to midnight.</span></h2>
        <p class="body">The closest the Doomsday Clock has ever stood to the end of us — and almost everyone is asleep at the wheel. That's not hyperbole. It's the deadline we're racing.<span class="src">Bulletin of the Atomic Scientists, 2026</span></p>
      </div>
    </div>
  </section>

  <section class="band band--line turn">
    <div class="wrap">
      <div class="rise">
        <div class="eyebrow-row"><span class="rule"></span><p class="kicker ash">The two worlds</p></div>
        <h2>Most people are plugged in. A few of us aren't.</h2>
      </div>
      <div class="grid">
        <div class="col rise">
          <p class="kicker ash">Asleep</p>
          <p class="body">Comfortable. Fed a version of reality by the people profiting from the wreck. Scrolling past the fire on the way to something easier. Not evil, necessarily — just asleep, and certain the dream is the whole world.</p>
        </div>
        <div class="col rise">
          <p class="kicker">Awake</p>
          <p class="body"><strong>You.</strong> The one who can't unsee it, who's been carrying the weight of knowing mostly alone, dismissed as "too much" for saying out loud what the receipts prove. This is where that stops being lonely.</p>
        </div>
      </div>
    </div>
  </section>

  <section class="band band--line" id="machine">
    <div class="wrap">
      <div class="machine-head rise">
        <div class="eyebrow-row"><span class="rule"></span><p class="kicker">The machine</p></div>
        <h2>Nobody gets an alibi.</h2>
        <p class="body">The world isn't dying by accident, and it isn't only the villains at the top. Three hands stay on the machine — and all three are guilty.</p>
      </div>
      <div class="hands rise">
        <div class="hand"><span class="tag">The ones at the top</span><h3>They engineer it and cash in</h3><p>Power selects for the ruthless. They profit from the collapse, then buy the politicians who shield them from ever paying for it.</p></div>
        <div class="hand"><span class="tag">The ones who look away</span><h3>They enable and don't care</h3><p>Billions enrich and empower the wreckers and call it normal. Two-thirds of this country worships every Sunday and changes nothing.</p></div>
        <div class="hand"><span class="tag">The ones who bury it</span><h3>They keep everyone asleep</h3><p>A media that "covers" the receipts and never front-pages them — so the comfortable never have to look, and the messenger gets throttled.</p></div>
      </div>
      <p class="verdict rise">The only ones who come out clean are the ones who stop looking away and <span class="blue">start building.</span></p>
    </div>
  </section>

  <section class="band real">
    <div class="wrap">
      <div class="rise">
        <div class="eyebrow-row"><span class="rule"></span><p class="kicker">What this is</p></div>
        <h2>Off Da Nine is <span class="blue">the real world.</span></h2>
      </div>
      <p class="body rise">Where the ones who unplugged stop complaining about the machine and start building the way out — a community engineering humanity's path to a <strong>Type-1 civilization:</strong> one that can finally coordinate to solve what's killing us instead of pretending it isn't happening. You don't just watch here. You <strong>ASCEND</strong> — leveling from <em>seeing</em> the crisis to <em>building</em> the fix.</p>
      <div class="creed rise">
        <span class="chip">No woo</span><span class="chip">No doom</span><span class="chip">Every claim sourced</span><span class="chip">You progress, you build</span>
      </div>
    </div>
  </section>

  <?php /* Five-zones teaser — canon zone names (PROGRESSION_WORLD_SPEC §2),
           tier colors = the official od9.css --t-* tokens; links the zones
           scroll experience so the funnel reads awaken -> zones -> join. */ ?>
  <section class="band band--line zones-tease">
    <div class="wrap">
      <div class="rise">
        <div class="eyebrow-row"><span class="rule"></span><p class="kicker">The world</p></div>
        <h2>Five zones. <span class="blue">One climb.</span></h2>
        <p class="body">ASCEND renders progression as a world — every tier is a place, and the Gate at the end of each one checks real work. No shortcuts. No purchased rank.</p>
      </div>
      <div class="zline stagger">
        <span class="zstop" style="--zc:var(--t-observer)"><b>The Wake</b><i>Observer</i></span>
        <span class="zstop" style="--zc:var(--t-theorist)"><b>The Diagnostic</b><i>Theorist</i></span>
        <span class="zstop" style="--zc:var(--t-architect)"><b>The Forge</b><i>Architect</i></span>
        <span class="zstop" style="--zc:var(--t-pioneer)"><b>The Bridge</b><i>Pioneer</i></span>
        <span class="zstop" style="--zc:var(--t-benefactor)"><b>The Horizon</b><i>Benefactor</i></span>
      </div>
      <div class="rise" style="margin-top:2.2rem">
        <a class="btn btn-primary" href="<?= $_bp ?>/zones.php">Walk the Five Zones <span class="arrow">&rarr;</span></a>
      </div>
    </div>
  </section>

  <section class="band band--line founder">
    <div class="wrap">
      <div class="grid">
        <figure class="portrait rise">
          <img src="<?= $_bp ?>/images/landing/rage-founder.jpg" alt="The Ultimate Rage, founder of OD9, elevated at the edge of the city, looking out" loading="lazy">
          <figcaption>The Ultimate Rage — Auburn-Gresham, Chicago</figcaption>
        </figure>
        <div class="rise">
          <p class="sig"><small>Built by one who woke up first</small>Unbought.<br>Unbossed.</p>
          <p class="body">A rapper and builder from the <strong>South Side of Chicago</strong> who created an entire civilization-scale system — the manifesto, the community, the protocol — solo, because the people with the resources wouldn't. Not the billionaires with their escape pods. Not the influencers narrating the fire from a safe distance. Somebody had to actually build the way out. So he did — from the bottom, with nothing but the truth and a refusal to look away.</p>
          <p class="unbought">A world that worked would've funded this years ago. That it didn't is the <b>whole thesis proving itself.</b></p>
        </div>
      </div>
    </div>
  </section>

  <section class="band cohort">
    <div class="wrap">
      <div class="inner rise">
        <div class="eyebrow-row"><span class="rule"></span><p class="kicker">The founding cohort</p></div>
        <h2>The resistance that woke up before it was a movement.</h2>
        <div class="count"><span class="n">80+</span><span class="lbl">already inside — the ones who couldn't unsee it</span></div>
        <p class="body">This didn't start with a crowd. It started with the few who refused to look away — the crew that was awake before there was anything to join. Get in before the world catches up.</p>
      </div>
    </div>
  </section>

  <section class="band band--line guides">
    <div class="wrap">
      <div class="rise">
        <div class="eyebrow-row"><span class="rule"></span><p class="kicker">The guides</p></div>
        <h2>Meet your guides</h2>
      </div>
      <div class="guides-grid stagger">
        <figure class="guide">
          <video autoplay muted loop playsinline preload="metadata">
            <source src="<?= $_bp ?>/videos/hero-archivist.mp4" type="video/mp4">
          </video>
          <figcaption>
            <h3>The Archivist</h3>
            <p>Keeper of the record. He meets you in The Wake.</p>
          </figcaption>
        </figure>
        <figure class="guide">
          <video autoplay muted loop playsinline preload="metadata">
            <source src="<?= $_bp ?>/videos/hero-forgemaster.mp4" type="video/mp4">
          </video>
          <figcaption>
            <h3>The Forgemaster</h3>
            <p>The Forge. Where understanding becomes building.</p>
          </figcaption>
        </figure>
        <figure class="guide">
          <video autoplay muted loop playsinline preload="metadata">
            <source src="<?= $_bp ?>/videos/hero-navigator.mp4" type="video/mp4">
          </video>
          <figcaption>
            <h3>The Navigator</h3>
            <p>The Bridge. She charts the coordinated climb.</p>
          </figcaption>
        </figure>
        <figure class="guide">
          <video autoplay muted loop playsinline preload="metadata">
            <source src="<?= $_bp ?>/videos/hero-watcher.mp4" type="video/mp4">
          </video>
          <figcaption>
            <h3>The Watcher</h3>
            <p>Beyond the gates. He only watches — for now.</p>
          </figcaption>
        </figure>
        <figure class="guide">
          <video autoplay muted loop playsinline preload="metadata">
            <source src="<?= $_bp ?>/videos/hero-quartermaster.mp4" type="video/mp4">
          </video>
          <figcaption>
            <h3>The Quartermaster</h3>
            <p>The Bunker. She keeps the crew supplied.</p>
          </figcaption>
        </figure>
      </div>
    </div>
  </section>

  <section class="band end" id="enter">
    <div class="wrap">
      <div class="rise">
        <p class="kicker">The door</p>
        <h2>You already know if you're one of us.</h2>
        <p class="body">The receipts are on the table. The only question left is whether you're done looking away — or ready to build.</p>
        <div class="cta-row">
          <a class="btn btn-primary" href="https://offda9.com" rel="noopener">Enter the real world <span class="arrow">&rarr;</span></a>
          <a class="btn-ascend" href="https://discord.gg/spgmrXVMWq" target="_blank" rel="noopener" aria-label="Ascend — join the Discord and begin"><img src="<?= $_bp ?>/images/landing/ascend-wordmark.jpg" alt="ASCEND"></a>
        </div>
        <p class="tagline-foot">Grow up before we blow up.</p>
      </div>
    </div>
  </section>

  <footer class="wrap">
    <span>Off Da Nine — OD9</span><span>Type-1 or nothing</span><span>offda9.com</span>
  </footer>
</main>

<script>
(function(){
  if(window.matchMedia&&window.matchMedia('(prefers-reduced-motion:reduce)').matches){
    document.querySelectorAll('video').forEach(function(v){try{v.removeAttribute('autoplay');v.pause();}catch(e){}});
  }
  var els=document.querySelectorAll('.rise,.stagger');
  if(!('IntersectionObserver'in window)){els.forEach(function(e){e.classList.add('in')});return;}
  var io=new IntersectionObserver(function(en){en.forEach(function(e){if(e.isIntersecting){e.target.classList.add('in');io.unobserve(e.target);}});},{threshold:.16,rootMargin:'0px 0px -8% 0px'});
  els.forEach(function(e){io.observe(e)});
})();
(function(){
  /* Scroll-scrub hero mechanics — runs only when the synchronous gate set
     body.scrub (capable desktops). Fail-open: any scrub-video error restores
     the original autoplay loop and removes scrub mode entirely. Duration is
     read LIVE each frame (a loadedmetadata listener can attach after the
     event on fast connections — cached 0 = silently dead scrub). MINIFY-SAFE:
     block comments only; every statement semicolon-terminated. */
  var body = document.body;
  if (body.className.indexOf('scrub') === -1) { return; }
  var track = document.getElementById('heroTrack');
  var vid = document.getElementById('heroVid');
  var cue = document.getElementById('heroCue');
  var hero = track ? track.querySelector('.hero') : null;
  if (!track || !vid || !hero) { return; }
  var last = -1, unlocked = false;

  function unscrub(){
    body.className = body.className.replace(/\s*\bscrub\b/, '');
    try {
      vid.removeAttribute('src');
      vid.setAttribute('autoplay', '');
      vid.setAttribute('loop', '');
      vid.load();
      var p = vid.play();
      if (p && p.catch) { p.catch(function(){}); }
    } catch (e) {}
  }
  vid.addEventListener('error', unscrub);

  vid.removeAttribute('autoplay');
  vid.removeAttribute('loop');
  try { vid.pause(); } catch (e) {}
  vid.src = vid.getAttribute('data-scrub-src') || '';
  try { vid.load(); } catch (e) {}

  function unlock(){
    if (unlocked) { return; }
    unlocked = true;
    var p = vid.play();
    if (p && p.then) { p.then(function(){ vid.pause(); }).catch(function(){}); }
  }
  window.addEventListener('touchstart', unlock, { once: true, passive: true });
  window.addEventListener('scroll',     unlock, { once: true, passive: true });

  function getDur(){
    var d = vid.duration;
    return (typeof d === 'number' && isFinite(d) && d > 0) ? d : 0;
  }
  function prog(){
    var r = track.getBoundingClientRect();
    var total = r.height - window.innerHeight;
    var p = total > 0 ? (-r.top) / total : 0;
    return p < 0 ? 0 : (p > 1 ? 1 : p);
  }
  function frame(){
    if (body.className.indexOf('scrub') === -1) { return; }
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
      hero.style.setProperty('--p', p.toFixed(4));
      if (cue) { cue.classList.toggle('off', p > 0.04); }
    }
    window.requestAnimationFrame(frame);
  }
  window.requestAnimationFrame(frame);
})();
</script>
</body>
</html>
