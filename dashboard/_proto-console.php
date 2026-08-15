<?php
/**
 * PROTOTYPE — Board Redesign "Console" (docs/BOARD_REDESIGN_SPEC.md).
 * Static mockup: NO auth, NO DB, NO write path. Exists purely so the design
 * can be judged before board.php is touched. Delete after P1 ships.
 * Data below is a snapshot of a real Observer board, hardcoded.
 */
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Board — Console prototype</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Chakra+Petch:wght@400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
<style>
:root{
  /* ---- tokens: unchanged from board.css (ASCEND_BRAND §3.1) ---- */
  --cyan:#00FFF7; --violet:#7A00FF; --violet-soft:#9B5CFF;
  --gold:#D4AF37; --gold-bright:#FFD700; --gold-hi:#F6E27A; --ice:#AADDFF; --diamond:#F0F0F5;
  --void:#0A0A0A; --panel:#101417; --panel-2:#161C20; --panel-3:#1C242A;
  --line:rgba(201,210,214,.10); --line-strong:rgba(201,210,214,.20);
  --ink:#E8EEF0; --ink-dim:#9AA6AB; --ink-faint:#5F6A70;
  --t-observer:#8A9499; --text-on-gold:#0A0A0A;
  --font-display:"Chakra Petch",system-ui,sans-serif;
  --font-body:"Space Grotesk",system-ui,sans-serif;
  --font-mono:"Space Mono",ui-monospace,monospace;
  /* ---- NEW: spacing (Fibonacci) + type (√φ ≈ 1.272) ---- */
  --sp-1:4px; --sp-2:8px; --sp-3:13px; --sp-4:21px; --sp-5:34px; --sp-6:55px; --sp-7:89px;
  --fs-micro:10px; --fs-label:12.5px; --fs-body:16px; --fs-lead:20px;
  --fs-h3:26px; --fs-h2:33px; --fs-h1:42px;
  --r-lg:14px; --r-md:8px; --r-pill:999px;
  --shadow-md:0 8px 28px rgba(0,0,0,.5);
}
*{box-sizing:border-box;margin:0;padding:0}
body{
  background:var(--void);              /* FLAT. no photo under type. */
  color:var(--ink); font-family:var(--font-body); font-size:var(--fs-body);
  line-height:1.5; -webkit-font-smoothing:antialiased;
}

/* ============ HUD — one rail, five destinations behind the overflow ======= */
.hud{
  display:flex; align-items:center; gap:var(--sp-4);
  padding:var(--sp-3) var(--sp-5); border-bottom:1px solid var(--line-strong);
  background:var(--panel);
}
/* Brand lockup (ASCEND_BRAND §5): the real marks, OD9 left / ASCEND right,
   locked to EQUAL CAP-HEIGHT — both masters are 120px tall, so one height
   value aligns them. Never retype these as text. */
.hud-brand{display:flex;align-items:center;gap:var(--sp-3);flex-shrink:0}
.hud-mk{width:auto;display:block}
.hud-sep{font-family:var(--font-display);font-weight:700;font-size:var(--fs-label);color:var(--ink-faint);letter-spacing:.1em}
/* Zone name is set to the MARKS' optical height, not a type-scale step: the
   marks are 26px images whose letterforms fill the frame, so matching means
   cap-height 26px => font-size ~26/0.72. Locked to --hud-mk-h so the three
   lockup elements can never drift apart. */
.hud{--hud-mk-h:26px}
.hud-mk{height:var(--hud-mk-h)}
.hud .zone{
  font-family:var(--font-display);font-weight:700;
  font-size:calc(var(--hud-mk-h) / 0.72);line-height:1;
  letter-spacing:.08em;text-transform:uppercase;color:var(--cyan);
}
.hud .spacer{flex:1;min-width:var(--sp-4)}
.hud .hud-brand,.hud .zone{flex-shrink:0}   /* nowrap + shrink = self-overlap */
.chip{
  display:inline-flex;align-items:center;gap:var(--sp-2);
  font-family:var(--font-mono);font-size:var(--fs-label);letter-spacing:.1em;text-transform:uppercase;
  border:1px solid var(--line-strong);border-radius:var(--r-pill);padding:var(--sp-1) var(--sp-3);
  color:var(--ink-dim);
}
.chip .dot{width:8px;height:8px;border-radius:50%;background:var(--t-observer)}
.meter-wrap{display:flex;flex-direction:column;gap:var(--sp-1);min-width:210px}
.meter-top{display:flex;justify-content:space-between;font-family:var(--font-mono);font-size:var(--fs-label);color:var(--ink-dim)}
.meter-top b{color:var(--gold)}
.meter{height:4px;background:var(--panel-3);border-radius:var(--r-pill);overflow:hidden}
.meter i{display:block;height:100%;width:100%;background:linear-gradient(90deg,var(--gold-hi),var(--gold))}
.more{
  font-family:var(--font-mono);font-size:var(--fs-lead);color:var(--ink-dim);
  border:1px solid var(--line-strong);border-radius:var(--r-md);
  padding:0 var(--sp-3);line-height:1.6;cursor:pointer;background:none;
}

/* ============ the φ grid: 61.8 / 38.2 ==================================== */
.wrap{
  display:grid; grid-template-columns:61.8fr 38.2fr; gap:var(--sp-4);
  padding:var(--sp-5); max-width:1680px; margin:0 auto; align-items:start;
}
.col{display:flex;flex-direction:column;gap:var(--sp-4)}
.panel{background:var(--panel);border:1px solid var(--line-strong);border-radius:var(--r-lg);padding:var(--sp-4)}
.panel.raised{background:var(--panel-2);box-shadow:var(--shadow-md)}
.eyebrow{
  font-family:var(--font-mono);font-size:var(--fs-label);letter-spacing:.16em;
  text-transform:uppercase;color:var(--ink-dim);margin-bottom:var(--sp-3);
}

/* ---- world viewport: art is FRAMED, never behind text ---- */
.viewport{position:relative;border:1px solid var(--line-strong);border-radius:var(--r-lg);overflow:hidden;aspect-ratio:16/9;background:#000}
.viewport img{width:100%;height:100%;object-fit:cover;display:block}
.viewport .vtag{
  position:absolute;left:var(--sp-4);top:var(--sp-4);
  font-family:var(--font-mono);font-size:var(--fs-label);letter-spacing:.16em;text-transform:uppercase;
  color:var(--diamond);background:rgba(10,10,10,.72);border:1px solid var(--line-strong);
  padding:var(--sp-1) var(--sp-3);border-radius:var(--r-pill);
}
.viewport .vname{
  position:absolute;left:var(--sp-4);bottom:var(--sp-4);
  font-family:var(--font-display);font-weight:700;font-size:var(--fs-h1);line-height:1.05;
  color:var(--diamond);text-shadow:0 2px 24px rgba(0,0,0,.9);
}

/* ---- progress rail: replaces the helix ---- */
.rail{display:flex;align-items:flex-start;gap:0;margin-top:var(--sp-2)}
.stop{flex:1;display:flex;flex-direction:column;align-items:center;gap:var(--sp-2);position:relative}
.stop::before{content:"";position:absolute;top:11px;left:-50%;width:100%;height:2px;background:var(--panel-3)}
.stop:first-child::before{display:none}
.stop.done::before,.stop.current::before{background:var(--cyan)}
.stop-dot{width:22px;height:22px;border-radius:50%;border:2px solid var(--ink-faint);background:var(--panel);z-index:1}
.stop.done .stop-dot{background:var(--cyan);border-color:var(--cyan)}
.stop.current .stop-dot{border-color:var(--gold);background:var(--gold);box-shadow:0 0 0 5px rgba(212,175,55,.18)}
.stop.pending .stop-dot{background:linear-gradient(90deg,var(--ink-dim) 50%,transparent 50%);border-color:var(--ink-dim)}
.stop.gate .stop-dot{border-radius:3px;transform:rotate(45deg);border-color:var(--violet-soft);background:var(--panel)}
.stop .n{font-family:var(--font-mono);font-size:var(--fs-label);color:var(--ink-dim)}
.stop .lbl{font-size:var(--fs-label);text-align:center;color:var(--ink-dim);line-height:1.25;max-width:12ch}
.stop.current .n,.stop.current .lbl{color:var(--gold)}
.stop.done .lbl{color:var(--ink)}

/* ---- the move card: the hero of the secondary column ---- */
.move{border-color:var(--gold)}                       /* gold border = "this is the thing" */
.move .kicker{font-family:var(--font-mono);font-size:var(--fs-label);letter-spacing:.16em;text-transform:uppercase;color:var(--gold);margin-bottom:var(--sp-3)}
.move h2{font-family:var(--font-display);font-weight:700;font-size:var(--fs-h2);line-height:1.15;color:var(--diamond);margin-bottom:var(--sp-3)}
.move .deck{font-size:var(--fs-lead);color:var(--ink-dim);margin-bottom:var(--sp-4);line-height:1.45}
.row{display:flex;align-items:center;gap:var(--sp-3)}
.cta{
  flex:1;display:inline-flex;align-items:center;justify-content:center;gap:var(--sp-2);
  background:linear-gradient(180deg,var(--gold-hi),var(--gold));color:var(--text-on-gold); /* 9.4:1 */
  font-family:var(--font-display);font-weight:700;font-size:var(--fs-body);letter-spacing:.04em;
  text-transform:uppercase;border:none;border-radius:var(--r-md);padding:var(--sp-3) var(--sp-4);cursor:pointer;
}
.cr{font-family:var(--font-mono);font-size:var(--fs-body);color:var(--gold);white-space:nowrap}

/* ---- reflection ---- */
.entry textarea{
  width:100%;min-height:96px;resize:vertical;background:var(--panel-3);color:var(--ink);
  border:1px solid var(--line-strong);border-radius:var(--r-md);padding:var(--sp-3);
  font-family:var(--font-body);font-size:var(--fs-body);
}
.entry textarea::placeholder{color:var(--ink-faint)}
.entry .foot{display:flex;align-items:center;justify-content:space-between;margin-top:var(--sp-3)}
.lockline{font-family:var(--font-mono);font-size:var(--fs-label);color:var(--ink-dim)}
.ghost{background:none;border:1px solid var(--line-strong);color:var(--ink-dim);border-radius:var(--r-md);padding:var(--sp-2) var(--sp-4);font-family:var(--font-display);font-size:var(--fs-label);letter-spacing:.06em;text-transform:uppercase;cursor:pointer}

/* ---- gate: violet is a FRAME, never a label (2.9:1) ----
   NOTE: scoped to .panel.gate — an unscoped .gate also matched the rail's
   .stop.gate and painted a violet box behind the diamond (caught by the
   render check, which is exactly why §11 exists). */
.panel.gate{border-color:var(--violet-soft);box-shadow:0 0 26px rgba(122,0,255,.16) inset}
.panel.gate .eyebrow{color:var(--ice)}
.greq{display:flex;align-items:center;gap:var(--sp-3);padding:var(--sp-3) 0;border-top:1px solid var(--line)}
.greq:first-of-type{border-top:none}
.greq .k{flex:1;font-family:var(--font-mono);font-size:var(--fs-label);letter-spacing:.1em;text-transform:uppercase;color:var(--ink-dim)}
.greq .v{font-family:var(--font-mono);font-size:var(--fs-body);color:var(--ink)}
.greq .m{width:84px;height:4px;background:var(--panel-3);border-radius:var(--r-pill);overflow:hidden}
.greq .m i{display:block;height:100%;background:var(--cyan)}
.greq.miss .m i{background:var(--gold)}
.panel.gate .foot{margin-top:var(--sp-3);font-size:var(--fs-label);color:var(--ink-dim)}

/* ===================== BROADCAST breakpoint (spec §8) =====================
   The secondary column IS the presenter column: the board simply does not
   draw there, so the facecam occludes NOTHING. Type x1.5; micro sizes and
   --ink-faint are forbidden; flat fills only (gradients band under stream
   compression). Triggered here by ?broadcast=1 for the mockup. */
body.broadcast{
  --fs-label:19px; --fs-body:24px; --fs-lead:30px;
  --fs-h3:39px; --fs-h2:50px; --fs-h1:63px;
}
body.broadcast .wrap{grid-template-columns:61.8fr 38.2fr;padding-bottom:89px}
/* fit corrections found by rendering (see spec §6.1/§8):
   1. flex items must not shrink below content or the HUD overlaps itself
   2. a 16:9 viewport + 7 labeled stops exceeds the 662px safe height
   3. the rail WINDOWS to 5 stops — marks never shrink to fit */
body.broadcast .hud{padding:var(--sp-2) var(--sp-4)}
body.broadcast .hud .hud-brand,body.broadcast .hud .zone,body.broadcast .chip{flex-shrink:0}
body.broadcast .hud{--hud-mk-h:38px}   /* x1.5; zone name follows automatically */
body.broadcast .meter-wrap{min-width:330px}  /* fixed min-widths don't scale with type */
body.broadcast .meter-top{gap:var(--sp-4)}
body.broadcast .viewport{aspect-ratio:21/9}
body.broadcast .rail .stop:nth-child(4),
body.broadcast .rail .stop:nth-child(5){display:none}   /* window around current */
body.broadcast .stop .lbl{max-width:16ch}
body.broadcast .col.presenter{
  border:2px dashed rgba(255,255,255,.22);border-radius:var(--r-lg);
  min-height:520px;display:flex;align-items:center;justify-content:center;
  font-family:var(--font-mono);font-size:var(--fs-label);letter-spacing:.16em;
  text-transform:uppercase;color:var(--ink-dim);text-align:center;
}
body.broadcast .cta{background:var(--gold)}       /* flat: no gradient banding */
body.broadcast .meter i{background:var(--gold)}
</style>
</head>
<?php $bc = !empty($_GET['broadcast']); ?>
<body class="<?= $bc ? 'broadcast' : '' ?>">

<header class="hud">
  <span class="hud-brand">
    <img class="hud-mk" src="/od9/images/board/od9-logomark.png" alt="OD9">
    <span class="hud-sep">//</span>
    <img class="hud-mk" src="/od9/images/board/ascend-wordmark.png" alt="ASCEND">
  </span>
  <span class="hud-sep">//</span>
  <span class="zone">The Wake</span>
  <span class="spacer"></span>
  <span class="chip"><span class="dot"></span> Observer</span>
  <div class="meter-wrap">
    <div class="meter-top"><span>To Theorist</span><span><b>2,093</b> / 150 CR</span></div>
    <div class="meter"><i></i></div>
  </div>
  <button class="more" title="FAQ · Tour · World Map · The Bunker · Dashboard">&ctdot;</button>
</header>

<main class="wrap">

  <!-- PRIMARY 61.8% — the world and where you stand in it -->
  <div class="col">
    <section class="panel">
      <div class="eyebrow">Zone 1 of 5 &middot; your position</div>
      <div class="viewport">
        <img src="/od9/images/board/wake.jpg" alt="The Wake">
        <span class="vtag">&#9670; The Archivist</span>
        <span class="vname">The Wake</span>
      </div>
      <div class="rail">
        <div class="stop done"><span class="stop-dot"></span><span class="n">1</span><span class="lbl">The Creed</span></div>
        <div class="stop done"><span class="stop-dot"></span><span class="n">2</span><span class="lbl">Evidence Standard</span></div>
        <div class="stop current"><span class="stop-dot"></span><span class="n">3</span><span class="lbl">Love as Infrastructure</span></div>
        <div class="stop"><span class="stop-dot"></span><span class="n">4</span><span class="lbl">Solution Framework</span></div>
        <div class="stop"><span class="stop-dot"></span><span class="n">5</span><span class="lbl">Vision to Action</span></div>
        <div class="stop pending"><span class="stop-dot"></span><span class="n">6</span><span class="lbl">Capstone</span></div>
        <div class="stop gate"><span class="stop-dot"></span><span class="n">&nbsp;</span><span class="lbl">Gate</span></div>
      </div>
    </section>
  </div>

  <!-- SECONDARY 38.2% — the act (on broadcast this becomes the presenter column) -->
  <?php if ($bc): ?>
  <div class="col presenter">PRESENTER COLUMN<br>&mdash;<br>facecam fills this 38.2%<br>nothing is occluded</div>
  <?php else: ?>
  <div class="col">
    <section class="panel raised move">
      <div class="kicker">&#9670; Your move &middot; Knowledge</div>
      <h2>Love as Infrastructure</h2>
      <p class="deck">Not a feeling you wait for &mdash; the coordination layer civilization runs on, and a skill you can train.</p>
      <div class="row">
        <button class="cta">Read it</button>
        <span class="cr">+10 CR</span>
      </div>
    </section>

    <section class="panel entry">
      <div class="eyebrow">Your reflection</div>
      <textarea placeholder="Open the material first, then reflect&hellip;"></textarea>
      <div class="foot">
        <span class="lockline">&#128274; Opens once you've read it</span>
        <button class="ghost">Submit</button>
      </div>
    </section>

    <section class="panel gate">
      <div class="eyebrow">The Gate &middot; Theorist</div>
      <div class="greq"><span class="k">Credits</span><span class="m"><i style="width:100%"></i></span><span class="v">2,093 / 150</span></div>
      <div class="greq miss"><span class="k">Knowledge</span><span class="m"><i style="width:100%"></i></span><span class="v">46 / 30</span></div>
      <div class="greq miss"><span class="k">Consciousness</span><span class="m"><i style="width:30%"></i></span><span class="v">3 / 10</span></div>
      <div class="greq"><span class="k">Capstone</span><span class="m"><i style="width:60%"></i></span><span class="v">In review</span></div>
      <div class="foot">One dimension short. Consciousness is earned from evaluated reflection &mdash; it can't be farmed.</div>
    </section>
  </div>
  <?php endif; ?>

</main>
</body>
</html>
