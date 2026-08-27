<?php
/**
 * OD9 World Map — the zoomed-out ascent (PROGRESSION_WORLD_SPEC.md §2).
 *
 * All five zones as one climb, with the member's real position lit from the
 * bot's SQLite (same guarded read-only pattern as board.php / index.php).
 * Scroll drives the five-zones flythrough (the scroll-scrub treatment shipped
 * on zones.php) — one station per zone: WALKED (below your tier, opens the
 * Zone Navigator read-only view), YOU ARE HERE (live gate progress + guide VO),
 * or SEALED (locked teaser — the Gate checks the work, no preview ahead).
 *
 * Global HUD (§6/§7, the honest subset buildable today): the Kardashev gauge
 * (humanity ~0.73, stated as Sagan's debated energy-only interpolation, cited)
 * and the Doomsday Clock (annual metric — manual-refresh class per spec §6:
 * "no clean API → cache + scheduled manual refresh with the source date
 * shown"). Values live in $WORLD_STATE below with as-of + source on every
 * number; the full live-fetcher panel is Phase 2. Guardrail (§7): the two
 * scales are never conflated — OD9 does not claim to move the planetary number.
 *
 * Spec rules honored: video is NEVER load-bearing (poster-still baseline,
 * .zx-dead fail-open); overlay runs off scroll alone; zone tokens come from
 * od9.css (the --zone- and --t- token sets, founder-locked 2026-08-12) — no
 * page-local palette. Guide portraits load from images/board/guides/ (the
 * canonical, repo-tracked location board.php also uses) with ?v=filemtime.
 * Shared ZONES/GATE/TIER_ORDER constants: includes/world_consts.php (also
 * consumed by board.php — one definition, zero drift).
 *
 * !! MINIFY-SAFE: NO `//` comments inside the inline <script> (the origin
 * minifier collapses newlines — the 2026-06-20 ztrans bug). Block comments
 * only; every statement semicolon-terminated.
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/ztrans.php';
od9_dashboard_boot();

if (empty($_SESSION['discord_id'])) {
    header('Location: ' . DASHBOARD_BASE_URL . '/index.php');
    exit;
}
$discordId = $_SESSION['discord_id'];

require_once __DIR__ . '/includes/world_consts.php';

// ---- Live member state (guarded reads: a failed query blanks its widget,
//      never the page — the established dashboard safe-read pattern). ----
$bot = null;
try {
    $bot = new PDO('sqlite:' . OD9_BOT_DB_PATH, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Throwable $e) {
    error_log('[world.php] bot DB open failed: ' . $e->getMessage());
}
$q = function (string $sql, array $p = []) use ($bot): array {
    if (!$bot) return [];
    try { $s = $bot->prepare($sql); $s->execute($p); return $s->fetchAll(); }
    catch (Throwable $e) { error_log('[world.php] query failed: ' . $e->getMessage()); return []; }
};

$user = $q("SELECT username, current_tier, total_credits FROM users WHERE user_id = ? LIMIT 1", [$discordId])[0] ?? null;
$memberTier = strtolower($user['current_tier'] ?? 'observer');
if (!isset(ZONES[$memberTier])) $memberTier = 'observer';
$memberIdx = (int)array_search($memberTier, TIER_ORDER, true);
$credits   = (int)($user['total_credits'] ?? 0);
$username  = $user['username'] ?? 'Member';

$dimRow = $q("SELECT knowledge_score, resource_score, community_score,
                     consciousness_score, system_score
              FROM user_dimensions WHERE user_id = ?", [$discordId])[0] ?? [];
$memberDims = [];
foreach (array_keys(DIMS) as $d) {
    $memberDims[$d] = (float)($dimRow[$d . '_score'] ?? 0);
}

// Gate progress for the member's CURRENT transition (null at the summit).
// Requirements come LIVE from the bot's tier_gate_requirements projection —
// the hand-mirror died in chunk 4 (see world_consts.php od9_gate_tables).
[$GATES, $NEXTS] = od9_gate_tables($bot);
$gate = $GATES[$memberTier] ?? null;
$nextTier = $NEXTS[$memberTier] ?? null;
$gateCreditPct = $gate ? min(100, (int)round($credits / max(1, $gate['credits']) * 100)) : 100;

// ---- World State — the honest, annual-cadence subset (spec §6 manual-refresh
//      class: value + optimal + as-of + source + methodology caveat on every
//      number). Update on each January revision; NEVER show without the date. ----
$WORLD_STATE = [
    [
        'label'   => 'Doomsday Clock',
        'value'   => '85 seconds',
        'optimal' => '&infin; from midnight',
        'as_of'   => 'Jan 2026 setting',
        'src'     => 'https://thebulletin.org/doomsday-clock/',
        'note'    => 'Bulletin of the Atomic Scientists — a judgment-based index of existential risk, revised each January. Reported straight; the gap to optimal is the point.',
        'pct'     => 8,
    ],
    [
        'label'   => 'Kardashev scale',
        'value'   => '&asymp; 0.73',
        'optimal' => '1.0 &mdash; Type I',
        'as_of'   => "Sagan interpolation",
        'src'     => 'https://en.wikipedia.org/wiki/Kardashev_scale',
        'note'    => "Energy-based interpolation K = (log10 P - 6)/10 — debated, energy-only. Humanity's number, not ours: OD9 works toward participating in the climb and never claims to move this figure.",
        'pct'     => 73,
    ],
];

// ---- Env-aware bases (local XAMPP mirror serves under /od9). ----
$isLocalReq = (($_SERVER['SERVER_NAME'] ?? '') === 'localhost') || strpos(__DIR__, 'xampp') !== false;
$BP  = $isLocalReq ? '/od9' : '';
$IMG = $BP . '/images/board';
$VID = $BP . '/video/zones';
$h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES);

// Tier-aware HQ-door clip (same threshold as the dashboard bg swap).
$hqClip = in_array($memberTier, ['architect', 'pioneer', 'benefactor'], true) ? 'hq-door-k1' : 'hq-door';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>World Map — The Ascent · OD9</title>
<link rel="stylesheet" href="<?= $BP ?>/css/od9.css?v=<?= @filemtime(__DIR__ . '/../css/od9.css') ?: '1' ?>">
<?php od9_ztrans_head(); ?>
<style>
  @font-face{font-family:'Chakra Petch';font-weight:700;font-style:normal;font-display:swap;src:url('<?= $BP ?>/css/fonts/chakra-petch-700.woff2') format('woff2')}
  @font-face{font-family:'Space Grotesk';font-weight:400;font-style:normal;font-display:swap;src:url('<?= $BP ?>/css/fonts/space-grotesk-400.woff2') format('woff2')}
  @font-face{font-family:'Space Grotesk';font-weight:600;font-style:normal;font-display:swap;src:url('<?= $BP ?>/css/fonts/space-grotesk-600.woff2') format('woff2')}
  @font-face{font-family:'Space Mono';font-weight:400;font-style:normal;font-display:swap;src:url('<?= $BP ?>/css/fonts/space-mono-400.woff2') format('woff2')}
  @font-face{font-family:'Space Mono';font-weight:700;font-style:normal;font-display:swap;src:url('<?= $BP ?>/css/fonts/space-mono-700.woff2') format('woff2')}

  :root{
    /* palette = official zone tokens from od9.css; fallbacks only guard a
       failed stylesheet load, values identical by contract */
    --wm-cyan:var(--zone-cyan,#00FFF7); --wm-void:var(--zone-void,#0A0A0A);
    --wm-bone:#EEF2F7; --wm-dim:#A9B2C0; --wm-ash:#788393;
    --wm-line:rgba(200,210,225,.14);
    --f-display:'Chakra Petch','Arial Narrow',system-ui,sans-serif;
    --f-body:'Space Grotesk',system-ui,sans-serif;
    --f-mono:'Space Mono',ui-monospace,Consolas,monospace;
    --wm-pad:clamp(1.1rem,4vw,3rem);
  }
  html,body{margin:0;padding:0}
  body{padding-top:0;background:var(--wm-void);color:var(--wm-bone);font-family:var(--f-body);line-height:1.55;overflow-x:hidden;-webkit-font-smoothing:antialiased}
  .wm *{box-sizing:border-box}
  .wm ::selection{background:var(--wm-cyan);color:#03211f}
  .wm a{color:inherit}
  .wm :focus-visible{outline:2px solid var(--wm-cyan);outline-offset:3px;border-radius:2px}

  /* ---- corner chrome ---- */
  .wm-brand{position:fixed;top:1rem;left:var(--wm-pad);z-index:40;display:flex;align-items:baseline;gap:.6rem;text-decoration:none}
  .wm-brand .t{font:700 .78rem/1 var(--f-mono);letter-spacing:.26em;color:var(--wm-cyan);text-shadow:0 0 12px rgba(0,255,247,.5)}
  .wm-brand .s{font:400 .66rem/1 var(--f-mono);letter-spacing:.18em;color:var(--wm-ash);text-transform:uppercase}
  .wm-skip{position:fixed;top:1rem;right:var(--wm-pad);z-index:40;
    font:700 .66rem/1 var(--f-mono);letter-spacing:.2em;text-transform:uppercase;text-decoration:none;
    color:var(--wm-dim);background:rgba(11,11,11,.55);border:1px solid var(--wm-line);border-radius:6px;
    padding:.6em 1em;backdrop-filter:blur(6px);transition:color .2s,border-color .2s}
  .wm-skip:hover{color:var(--wm-cyan);border-color:var(--wm-cyan)}

  /* ---- world-state HUD (Kardashev + Doomsday — honest meters) ---- */
  .wm-hud{position:fixed;right:var(--wm-pad);bottom:1.1rem;z-index:40;display:flex;flex-direction:column;gap:.5rem;width:min(240px,38vw);transition:opacity .35s ease}
  .wm-hud.off{opacity:0;pointer-events:none}
  .wm-meter{background:rgba(8,10,12,.72);border:1px solid var(--wm-line);border-radius:8px;padding:.55rem .7rem;backdrop-filter:blur(6px)}
  .wm-meter .row{display:flex;justify-content:space-between;align-items:baseline;gap:.5rem}
  .wm-meter .k{font:700 .58rem/1.2 var(--f-mono);letter-spacing:.14em;text-transform:uppercase;color:var(--wm-ash)}
  .wm-meter .v{font:700 .8rem/1.2 var(--f-display);color:var(--wm-cyan)}
  .wm-meter .bar{height:3px;background:rgba(200,210,225,.12);border-radius:2px;margin:.4rem 0 .3rem;overflow:hidden}
  .wm-meter .bar i{display:block;height:100%;background:linear-gradient(90deg,var(--wm-cyan),var(--zone-violet,#7A00FF));border-radius:2px}
  .wm-meter .opt{font:400 .56rem/1.4 var(--f-mono);letter-spacing:.08em;color:var(--wm-ash)}
  .wm-meter .opt a{color:var(--wm-dim);text-decoration:underline dotted}

  /* ---- scrub track + stage ---- */
  .wm-track{height:520vh;position:relative}
  .wm-stage{position:sticky;top:0;height:100vh;height:100svh;overflow:hidden;isolation:isolate}
  .wm-still{position:absolute;inset:0;z-index:-3;background:url('<?= $VID ?>/five-zones-scrub.jpg') center/cover no-repeat}
  .wm-vid{position:absolute;inset:0;z-index:-2;width:100%;height:100%;object-fit:cover}
  .zx-dead .wm-vid{display:none}
  .wm-scrim{position:absolute;inset:0;z-index:-1;pointer-events:none;background:
    linear-gradient(90deg,rgba(6,8,13,.78) 0%,rgba(6,8,13,.42) 44%,rgba(6,8,13,.18) 70%,rgba(6,8,13,.5) 100%),
    linear-gradient(180deg,rgba(6,8,13,.6),rgba(6,8,13,.15) 30%,rgba(6,8,13,.82) 92%)}

  /* ---- headline (folds away) ---- */
  .wm-head{position:absolute;inset:0;display:grid;place-items:center;text-align:center;padding-inline:var(--wm-pad);perspective:700px;pointer-events:none}
  .wm-kicker{font:700 .7rem/1 var(--f-mono);letter-spacing:.32em;text-transform:uppercase;color:var(--wm-cyan);text-shadow:0 0 14px rgba(0,255,247,.55);margin:0 0 1rem;
    opacity:calc(1 - clamp(0, var(--p,0) * 7, 1))}
  .wm-h1{margin:0;font-family:var(--f-display);font-weight:700;text-transform:uppercase;
    font-size:clamp(2.6rem,10.5vw,8.5rem);line-height:.94;color:#fff;
    text-shadow:0 2px 44px rgba(0,0,0,.65),0 0 60px rgba(0,255,247,.12)}
  .wm-h1 .w{display:inline-block;white-space:nowrap;transform-style:preserve-3d}
  .wm-h1 .ch{display:inline-block;transform-origin:50% 100%;
    --k2:clamp(0, calc((var(--p,0) - var(--i)) * 5), 1);
    transform:rotateX(calc(var(--k2) * -88deg)) translateY(calc(var(--k2) * .16em));
    opacity:calc(1 - var(--k2) * .96)}
  .wm-sub{font:400 clamp(.95rem,1.5vw,1.1rem)/1.5 var(--f-body);color:var(--wm-dim);max-width:46ch;margin:1.2rem auto 0;
    opacity:calc(1 - clamp(0, var(--p,0) * 6, 1))}
  .wm-sub b{color:var(--wm-cyan);font-weight:600}

  /* ---- zone stations (overlay cards, one .on at a time) ---- */
  .wm-station{position:absolute;left:var(--wm-pad);bottom:clamp(4.2rem,10vh,6rem);z-index:5;
    width:min(430px,86vw);opacity:0;transform:translateY(14px);pointer-events:none;
    transition:opacity .32s ease,transform .32s ease}
  .wm-station.on{opacity:1;transform:none;pointer-events:auto}
  .wm-card{background:linear-gradient(180deg,rgba(13,16,20,.92),rgba(9,10,12,.88));
    border:1px solid var(--wm-line);border-left:3px solid var(--zc,var(--wm-cyan));
    border-radius:12px;padding:1.05rem 1.15rem;backdrop-filter:blur(8px)}
  .wm-card .top{display:flex;justify-content:space-between;align-items:baseline;gap:.8rem}
  .wm-card .tier{font:700 .6rem/1 var(--f-mono);letter-spacing:.22em;text-transform:uppercase;color:var(--zc,var(--wm-cyan))}
  .wm-card .state{font:700 .56rem/1 var(--f-mono);letter-spacing:.16em;text-transform:uppercase;padding:.35em .6em;border-radius:4px}
  .wm-card .state.walked{color:#3FB950;border:1px solid rgba(63,185,80,.4);background:rgba(63,185,80,.08)}
  .wm-card .state.here{color:#04211f;background:var(--wm-cyan);box-shadow:0 0 14px rgba(0,255,247,.55);animation:wmPulse 2.2s ease-in-out infinite}
  .wm-card .state.sealed{color:var(--wm-ash);border:1px solid var(--wm-line)}
  @keyframes wmPulse{0%,100%{box-shadow:0 0 10px rgba(0,255,247,.45)}50%{box-shadow:0 0 22px rgba(0,255,247,.8)}}
  .wm-card h2{margin:.5rem 0 .15rem;font-family:var(--f-display);font-weight:700;text-transform:uppercase;font-size:1.6rem;line-height:1;color:#fff}
  .wm-card .climb{margin:0 0 .7rem;color:var(--wm-dim);font-size:.88rem}
  .wm-card .vo{display:flex;gap:.7rem;align-items:center;margin:.65rem 0 .75rem}
  .wm-card .vo img{width:44px;height:44px;border-radius:50%;object-fit:cover;object-position:center 22%;border:2px solid var(--zc,var(--wm-cyan));flex:none}
  .wm-card .vo p{margin:0;font-size:.84rem;color:var(--wm-bone);font-style:italic;line-height:1.4}
  .wm-card .vo .g{display:block;font:700 .56rem/1.6 var(--f-mono);letter-spacing:.16em;text-transform:uppercase;color:var(--wm-ash);font-style:normal}
  .wm-gate{margin:.6rem 0 .75rem}
  .wm-gate .gk{display:flex;justify-content:space-between;font:700 .58rem/1.6 var(--f-mono);letter-spacing:.12em;text-transform:uppercase;color:var(--wm-ash)}
  .wm-gate .gk b{color:var(--wm-bone)}
  .wm-gate .bar{height:4px;background:rgba(200,210,225,.12);border-radius:2px;overflow:hidden;margin:.2rem 0 .5rem}
  .wm-gate .bar i{display:block;height:100%;background:linear-gradient(90deg,var(--wm-cyan),var(--zone-violet,#7A00FF))}
  .wm-gate .dims{display:grid;grid-template-columns:1fr 1fr;gap:.3rem .8rem}
  .wm-gate .dim .gk{font-size:.54rem}
  .wm-card .cta{display:inline-flex;align-items:center;gap:.5em;margin-top:.25rem;
    font:700 .68rem/1 var(--f-mono);letter-spacing:.16em;text-transform:uppercase;text-decoration:none;
    padding:.7em 1.1em;border-radius:5px}
  .wm-card .cta.go{background:linear-gradient(135deg,var(--wm-cyan),#00BFFF);color:#04211f;box-shadow:0 0 18px rgba(0,255,247,.35)}
  .wm-card .cta.view{border:1px solid var(--wm-line);color:var(--wm-dim)}
  .wm-card .cta.view:hover{color:var(--wm-cyan);border-color:var(--wm-cyan)}
  .wm-card .lockline{margin:.4rem 0 0;font:400 .74rem/1.5 var(--f-mono);color:var(--wm-ash)}

  /* ---- ladder rail (right side, all five, progress-lit) ---- */
  .wm-rail{position:absolute;right:var(--wm-pad);top:50%;transform:translateY(-50%);z-index:5;display:flex;flex-direction:column;gap:.85rem;text-align:right}
  .wm-wp{display:flex;align-items:center;justify-content:flex-end;gap:.6rem;opacity:.35;transition:opacity .25s}
  .wm-wp .zn{font:700 .72rem/1.15 var(--f-display);text-transform:uppercase;letter-spacing:.08em;color:var(--wm-bone)}
  .wm-wp .dot{width:9px;height:9px;border-radius:50%;border:2px solid var(--zc,var(--wm-cyan));flex:none}
  .wm-wp.done .dot{background:var(--zc,var(--wm-cyan))}
  .wm-wp.me .dot{background:var(--wm-cyan);border-color:var(--wm-cyan);box-shadow:0 0 12px rgba(0,255,247,.8)}
  .wm-wp.on{opacity:1}
  .wm-wp.on .zn{color:var(--wm-cyan);text-shadow:0 0 14px rgba(0,255,247,.5)}
  .wm-cue{position:absolute;left:50%;bottom:1.1rem;transform:translateX(-50%);z-index:5;
    display:flex;flex-direction:column;align-items:center;gap:.4rem;color:var(--wm-dim);
    font:700 .6rem/1 var(--f-mono);letter-spacing:.38em;text-transform:uppercase;transition:opacity .4s}
  .wm-cue.off{opacity:0}
  .wm-cue .vee{width:11px;height:11px;border-right:2px solid var(--wm-cyan);border-bottom:2px solid var(--wm-cyan);transform:rotate(45deg);animation:wmBob 1.6s ease-in-out infinite}
  @keyframes wmBob{0%,100%{transform:rotate(45deg) translate(0,0);opacity:.9}50%{transform:rotate(45deg) translate(4px,4px);opacity:.45}}

  /* ---- exits panel (after the track — the no-dead-ends hub) ---- */
  .wm-exits{position:relative;z-index:10;background:var(--wm-void);border-radius:24px 24px 0 0;
    margin-top:-6vh;border-top:1px solid var(--wm-line);box-shadow:0 -28px 70px rgba(0,0,0,.7);
    padding:clamp(2.8rem,7vh,4.5rem) var(--wm-pad) clamp(2.4rem,6vh,3.6rem)}
  .wm-wrap{max-width:1100px;margin-inline:auto}
  .wm-exits .k2{font:700 .68rem/1 var(--f-mono);letter-spacing:.28em;text-transform:uppercase;color:var(--wm-cyan);margin:0 0 .8rem}
  .wm-exits h3{margin:0 0 1.6rem;font-family:var(--f-display);font-weight:700;text-transform:uppercase;font-size:clamp(1.6rem,3.6vw,2.6rem);line-height:1;color:#fff}
  .wm-lgrid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:.8rem;margin-bottom:2rem}
  .wm-lcard{display:block;text-decoration:none;border:1px solid var(--wm-line);border-top:3px solid var(--zc,var(--wm-cyan));border-radius:10px;
    padding:.9rem 1rem;background:linear-gradient(180deg,#12141A,rgba(11,11,11,.6));transition:transform .18s,border-color .18s}
  .wm-lcard:hover{transform:translateY(-2px);border-color:var(--zc,var(--wm-cyan))}
  .wm-lcard.lock{opacity:.55;pointer-events:none}
  .wm-lcard .tier{font:700 .56rem/1 var(--f-mono);letter-spacing:.2em;text-transform:uppercase;color:var(--zc,var(--wm-cyan))}
  .wm-lcard h4{margin:.4rem 0 .2rem;font-family:var(--f-display);font-weight:700;text-transform:uppercase;font-size:1.02rem;color:#fff}
  .wm-lcard .st{font:400 .62rem/1.5 var(--f-mono);letter-spacing:.1em;text-transform:uppercase;color:var(--wm-ash)}
  .wm-exit-row{display:flex;flex-wrap:wrap;gap:.9rem}
  .wm-btn{font:700 .72rem/1 var(--f-mono);letter-spacing:.14em;text-transform:uppercase;text-decoration:none;
    display:inline-flex;align-items:center;gap:.5em;padding:.85em 1.3em;border-radius:5px;transition:transform .18s,box-shadow .18s}
  .wm-btn.p{background:linear-gradient(135deg,var(--wm-cyan),#00BFFF);color:#04211f;box-shadow:0 0 20px rgba(0,255,247,.3)}
  .wm-btn.p:hover{transform:translateY(-2px)}
  .wm-btn.g{border:1px solid var(--wm-line);color:var(--wm-dim)}
  .wm-btn.g:hover{color:var(--wm-cyan);border-color:var(--wm-cyan)}

  @media (max-width:760px){
    .wm-track{height:480vh}
    .wm-rail{display:none}
    .wm-hud{width:min(200px,52vw);bottom:.8rem}
    .wm-station{bottom:5rem;width:min(400px,92vw)}
    .wm-card h2{font-size:1.3rem}
    .wm-gate .dims{grid-template-columns:1fr}
  }
</style>
</head>
<body class="wm">
<?php od9_ztrans_body(); ?>

<a class="wm-brand ztrans-link" data-ztrans="<?= $h($hqClip) ?>" href="<?= $BP ?>/dashboard/index.php">
  <span class="t">OD9 // WORLD MAP</span><span class="s">&larr; HQ</span>
</a>
<a class="wm-skip" href="#wm-exits">Skip &rsaquo;</a>

<div class="wm-hud" aria-label="World state — honest meters, sourced">
  <?php foreach ($WORLD_STATE as $ws): ?>
  <div class="wm-meter" title="<?= $h($ws['note']) ?>">
    <div class="row"><span class="k"><?= $ws['label'] ?></span><span class="v"><?= $ws['value'] ?></span></div>
    <div class="bar"><i style="width:<?= (int)$ws['pct'] ?>%"></i></div>
    <div class="opt">optimal: <?= $ws['optimal'] ?> &middot; <a href="<?= $h($ws['src']) ?>" target="_blank" rel="noopener"><?= $h($ws['as_of']) ?></a></div>
  </div>
  <?php endforeach; ?>
</div>

<div class="wm-track" id="wmTrack">
  <div class="wm-stage">
    <div class="wm-still" aria-hidden="true"></div>
    <video class="wm-vid" id="wmVid" muted playsinline preload="auto"
           poster="<?= $VID ?>/five-zones-scrub.jpg" aria-hidden="true">
      <source src="<?= $VID ?>/five-zones-scrub.mp4" type="video/mp4">
    </video>
    <div class="wm-scrim" aria-hidden="true"></div>

    <div class="wm-head" id="wmHead">
      <div>
        <p class="wm-kicker">OD9 // ASCEND Protocol // World Map</p>
        <h1 class="wm-h1" aria-label="The Ascent"><?php
          $wm_ci = 0;
          foreach (['THE', 'ASCENT'] as $wm_wi => $wm_w) {
              echo '<span class="w" aria-hidden="true">';
              foreach (str_split($wm_w) as $wm_ch) {
                  printf('<span class="ch" style="--i:%.3f">%s</span>', $wm_ci * 0.02, $wm_ch);
                  $wm_ci++;
              }
              echo '</span>';
              if ($wm_wi === 0) { echo ' '; }
          }
        ?></h1>
        <p class="wm-sub"><b><?= $h($username) ?></b> &middot; <?= $h(ucfirst($memberTier)) ?> &middot; standing in <b><?= $h(ZONES[$memberTier]['zone']) ?></b>. Scroll the climb.</p>
      </div>
    </div>

    <?php foreach (TIER_ORDER as $i => $t): $z = ZONES[$t];
        $state = $i < $memberIdx ? 'walked' : ($i === $memberIdx ? 'here' : 'sealed'); ?>
    <div class="wm-station<?= $i === 0 ? ' on' : '' ?>" id="wmSt<?= $i ?>" style="--zc:var(--t-<?= $t ?>)">
      <div class="wm-card">
        <div class="top">
          <span class="tier"><?= $h(ucfirst($t)) ?> &middot; Zone 0<?= $i + 1 ?></span>
          <?php if ($state === 'walked'): ?><span class="state walked">Walked</span>
          <?php elseif ($state === 'here'): ?><span class="state here">You are here</span>
          <?php else: ?><span class="state sealed">Sealed</span><?php endif; ?>
        </div>
        <h2><?= $h($z['zone']) ?></h2>
        <?php if ($state === 'here'): ?>
          <div class="vo">
            <?php /* canonical portrait path = images/board/guides/ (board.php
                     line ~457 idiom, ?v=filemtime included — CF caches image
                     404s for 31 days, so unversioned probes/urls poison it) */ ?>
            <img src="<?= $IMG ?>/guides/<?= $h($z['guide_img']) ?>?v=<?= @filemtime(__DIR__ . '/../images/board/guides/' . $z['guide_img']) ?: '1' ?>" alt="" onerror="this.style.display='none'">
            <p><span class="g"><?= $h($z['guide']) ?></span>&ldquo;<?= $h($z['vo']) ?>&rdquo;</p>
          </div>
          <?php if ($gate && $nextTier): ?>
          <div class="wm-gate">
            <div class="gk"><span>Gate &rarr; <?= $h($nextTier) ?></span><b><?= number_format($credits) ?> / <?= $gate['credits'] ?> CR</b></div>
            <div class="bar"><i style="width:<?= $gateCreditPct ?>%"></i></div>
            <div class="dims">
              <?php foreach ($gate['dims'] as $dk => $need):
                  $have = $memberDims[$dk] ?? 0;
                  $pct = min(100, (int)round($have / max(1, $need) * 100)); ?>
              <div class="dim">
                <div class="gk"><span><?= $h(DIMS[$dk]['label']) ?></span><b><?= (int)$have ?>/<?= $need ?></b></div>
                <div class="bar"><i style="width:<?= $pct ?>%"></i></div>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
          <a class="cta go ztrans-link" data-ztrans="gate" href="<?= $BP ?>/dashboard/board.php">Enter <?= $h($z['zone']) ?> &rarr;</a>
          <?php else: ?>
          <p class="lockline">Summit tier. Nothing above &mdash; only what you sustain. The Horizon holds as long as you do.</p>
          <a class="cta go ztrans-link" data-ztrans="gate" href="<?= $BP ?>/dashboard/board.php">Walk The Horizon &rarr;</a>
          <?php endif; ?>
        <?php elseif ($state === 'walked'): ?>
          <p class="climb"><?= $h($z['guide']) ?> signed off. This ground is yours &mdash; revisit it any time.</p>
          <a class="cta view ztrans-link" data-ztrans="gate" href="<?= $BP ?>/dashboard/board.php?tier=<?= $h($t) ?>">Revisit &middot; read-only &rsaquo;</a>
        <?php else: ?>
          <p class="climb"><?= $h(ucfirst(TIER_ORDER[$i] ?? '')) ?> ground.</p>
          <p class="lockline">Sealed. The Gate checks the work &mdash; credits, dimensions, demonstrations. No previews, no purchases.</p>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>

    <div class="wm-rail" aria-hidden="true">
      <?php foreach (TIER_ORDER as $i => $t): ?>
      <div class="wm-wp<?= $i === 0 ? ' on' : '' ?><?= $i < $memberIdx ? ' done' : '' ?><?= $i === $memberIdx ? ' me' : '' ?>" style="--zc:var(--t-<?= $t ?>)">
        <span class="zn"><?= $h(ZONES[$t]['zone']) ?></span><span class="dot"></span>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="wm-cue" id="wmCue"><span>Scroll the ascent</span><span class="vee"></span></div>
  </div>
</div>

<section class="wm-exits" id="wm-exits">
  <div class="wm-wrap">
    <p class="k2">The Ladder</p>
    <h3>Five zones. Your position: <?= $h(ZONES[$memberTier]['zone']) ?>.</h3>
    <div class="wm-lgrid">
      <?php foreach (TIER_ORDER as $i => $t): $z = ZONES[$t];
          $walked = $i < $memberIdx; $here = $i === $memberIdx; ?>
      <?php if ($here): ?>
      <a class="wm-lcard ztrans-link" data-ztrans="gate" href="<?= $BP ?>/dashboard/board.php" style="--zc:var(--t-<?= $t ?>)">
      <?php elseif ($walked): ?>
      <a class="wm-lcard ztrans-link" data-ztrans="gate" href="<?= $BP ?>/dashboard/board.php?tier=<?= $h($t) ?>" style="--zc:var(--t-<?= $t ?>)">
      <?php else: ?>
      <a class="wm-lcard lock" href="#" aria-disabled="true" style="--zc:var(--t-<?= $t ?>)">
      <?php endif; ?>
        <span class="tier"><?= $h(ucfirst($t)) ?></span>
        <h4><?= $h($z['zone']) ?></h4>
        <span class="st"><?= $here ? 'You are here' : ($walked ? 'Walked &middot; revisit' : 'Sealed') ?></span>
      </a>
      <?php endforeach; ?>
    </div>
    <div class="wm-exit-row">
      <a class="wm-btn p ztrans-link" data-ztrans="gate" href="<?= $BP ?>/dashboard/board.php">Your Board &rarr;</a>
      <a class="wm-btn g ztrans-link" data-ztrans="<?= $h($hqClip) ?>" href="<?= $BP ?>/dashboard/index.php">HQ Dashboard</a>
      <a class="wm-btn g ztrans-link" data-ztrans="hatch" href="<?= $BP ?>/dashboard/bunker.php">The Bunker</a>
    </div>
  </div>
</section>

<script>
(function(){
  /* MINIFY-SAFE: block comments only; every statement semicolon-terminated. */
  var track = document.getElementById('wmTrack');
  var vid   = document.getElementById('wmVid');
  var head  = document.getElementById('wmHead');
  var cue   = document.getElementById('wmCue');
  var hud   = document.querySelector('.wm-hud');
  if (!track || !vid) { return; }
  var stations = [];
  var i;
  for (i = 0; i < 5; i++) { stations.push(document.getElementById('wmSt' + i)); }
  var wps = [].slice.call(document.querySelectorAll('.wm-wp'));
  var shown = 0, last = -1, unlocked = false;

  /* duration read LIVE each frame — a loadedmetadata listener can attach AFTER
     the event fired (preload=auto + faststart) and a cached 0 kills the scrub */
  function getDur(){
    var d = vid.duration;
    return (typeof d === 'number' && isFinite(d) && d > 0) ? d : 0;
  }
  function dead(){ document.documentElement.classList.add('zx-dead'); }
  vid.addEventListener('error', dead);
  var ls = vid.querySelector('source:last-of-type');
  if (ls) { ls.onerror = dead; }
  if (vid.error || vid.networkState === 3) { dead(); }

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
      if (hud)  { hud.classList.toggle('off', p > 0.985); }
      var ix = Math.min(4, Math.floor(p * 5));
      if (ix !== shown) {
        shown = ix;
        var j;
        for (j = 0; j < 5; j++) {
          if (stations[j]) { stations[j].classList.toggle('on', j === ix); }
          if (wps[j])      { wps[j].classList.toggle('on', j === ix); }
        }
      }
    }
    window.requestAnimationFrame(frame);
  }
  window.requestAnimationFrame(frame);
})();
</script>
</body>
</html>
