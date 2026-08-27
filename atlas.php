<?php
/**
 * atlas.php — The Atlas: the OD9 Manifesto as a living, zoomable map.
 *
 * Slice 1 of docs/MANIFESTO-ATLAS-SPEC.md (founder-approved 2026-08-20:
 * name "The Atlas", structure PUBLIC). Renders the DECIDED 52-chapter
 * skeleton; merged chapters carry their legacy lineage; chapter states
 * (raw / in-the-forge / preached / canon) derive from the masterplan ledger
 * + the published Codex lessons; the live beacon reads the designated
 * lesson through api/v1/atlas-live.php.
 *
 * Data: data/manifesto-map.json — a BUILD ARTIFACT written only by
 * tools/build_manifesto_map.py (guarded by tests/test_manifesto_map.py).
 * Inlined server-side below, so no .json URL is ever fetched (the CF WAF
 * 403s *.json paths). The generator refuses to emit JSON containing "</",
 * which is what makes the inline <script> block safe.
 *
 * !! MINIFY-SAFE: the origin HTML minifier strips newlines from PHP output.
 * The only inline <script> here is the JSON data island (no JS logic —
 * all behavior lives in js/atlas.js, which the minifier never touches).
 *
 * Palette: zone/tier tokens from css/od9.css ONLY (founder-locked
 * 2026-08-12) — no page-local hex for world colors.
 */
$page_title       = 'The Atlas | The Living Map of the OD9 Manifesto';
$page_description = 'The whole OD9 Manifesto on one zoomable map — watch the book being reforged one sermon at a time. Chapters light up as they are preached and become canon lessons.';
$page_slug        = 'atlas.php';
$page_og_title    = 'The Atlas — the living map of the OD9 Manifesto';
$page_og_description = 'Their book is frozen. Ours is a living map. Zoom the corpus, follow the routes, read the canon.';

$atlas_map_path = __DIR__ . '/data/manifesto-map.json';
$atlas_map_raw  = is_readable($atlas_map_path) ? file_get_contents($atlas_map_path) : '';
$atlas_map      = $atlas_map_raw !== '' ? json_decode($atlas_map_raw, true) : null;

/* Social share card — shot FROM the living map itself via ?og=1 (the card
   evolves as stars ignite; re-shoot + redeploy after each sermon week).
   Absolute URL + mtime version so crawlers and the CF edge always pull the
   current card. */
$atlas_og_file  = __DIR__ . '/images/og/atlas-og.png';
if (is_readable($atlas_og_file)) {
    $page_og_image = 'https://offda9.com/images/og/atlas-og.png?v=' . (@filemtime($atlas_og_file) ?: 1);
}
$atlas_og_mode  = isset($_GET['og']);
?>
<?php include __DIR__ . '/includes/head.php'; ?>
<style>
  body{background:var(--zone-void);color:var(--chrome);font-family:'Exo 2','Segoe UI',sans-serif}
  .atlas-hero{max-width:1200px;margin:0 auto;padding:1.6rem 1.5rem 0.8rem}
  .atlas-eyebrow{font-family:'Rajdhani',sans-serif;font-weight:600;letter-spacing:3px;text-transform:uppercase;font-size:0.8rem;color:var(--zone-cyan)}
  .atlas-hero h1{font-family:'Orbitron',sans-serif;font-size:clamp(1.7rem,4.5vw,2.6rem);color:#fff;margin:0.25rem 0 0.35rem}
  .atlas-dek{color:var(--chrome);max-width:46rem;font-size:1rem;line-height:1.55}
  .atlas-legend{display:flex;flex-wrap:wrap;gap:0.6rem 1.1rem;align-items:center;max-width:1200px;margin:0.7rem auto 0.5rem;padding:0 1.5rem;font-family:'Rajdhani',sans-serif;font-size:0.8rem;letter-spacing:1px;color:var(--chrome)}
  .atlas-dot{display:inline-block;width:11px;height:11px;border-radius:50%;margin-right:0.35rem;vertical-align:-1px}
  .atlas-dot-raw{background:var(--t-observer);opacity:0.55}
  .atlas-dot-forge{background:var(--t-pioneer);box-shadow:0 0 8px var(--t-pioneer)}
  .atlas-dot-preached{background:var(--zone-cyan);box-shadow:0 0 8px var(--zone-cyan)}
  .atlas-dot-canon{background:var(--gold);box-shadow:0 0 9px var(--gold)}
  .atlas-legend .atlas-hint{margin-left:auto;opacity:0.6;letter-spacing:0.5px;font-family:'Exo 2',sans-serif;text-transform:none}
  #atlas-live-chip{display:none;font-family:'Rajdhani',sans-serif;font-weight:700;letter-spacing:2px;color:var(--t-pioneer);border:1px solid var(--t-pioneer);border-radius:3px;padding:0.15rem 0.6rem;cursor:pointer;background:none;font-size:0.78rem}
  #atlas-live-chip.on{display:inline-block;animation:atlasLivePulse 2.2s ease-in-out infinite}
  @keyframes atlasLivePulse{0%,100%{box-shadow:0 0 4px var(--t-pioneer)}50%{box-shadow:0 0 14px var(--t-pioneer)}}
  #atlas-stage{position:relative;max-width:1200px;margin:0 auto 2.5rem;height:calc(100vh - var(--nav-height) - 210px);min-height:420px;border:1px solid var(--carbon-dark);border-radius:8px;overflow:hidden;background:var(--zone-void)}
  #atlas-canvas{position:absolute;inset:0;touch-action:none;cursor:grab}
  .atlas-zoom{position:absolute;right:12px;bottom:12px;display:flex;flex-direction:column;gap:6px;z-index:3}
  .atlas-zoom button{width:38px;height:38px;border-radius:6px;border:1px solid var(--zone-violet);background:rgba(10,10,10,0.8);color:var(--zone-cyan);font-size:1.15rem;font-family:'Rajdhani',sans-serif;cursor:pointer}
  .atlas-zoom button:hover,.atlas-zoom button:focus-visible{border-color:var(--zone-cyan);outline:none;box-shadow:0 0 8px rgba(0,255,247,0.4)}
  #atlas-card{position:absolute;top:0;right:0;bottom:0;width:min(390px,92%);background:rgba(10,10,10,0.94);border-left:1px solid var(--zone-violet);padding:1.4rem 1.5rem;overflow-y:auto;transform:translateX(102%);transition:transform 0.25s ease;z-index:4}
  #atlas-card.open{transform:none}
  @media (prefers-reduced-motion: reduce){#atlas-card{transition:none}}
  .atlas-card-close{position:absolute;top:8px;right:12px;background:none;border:none;color:var(--chrome);font-size:1.6rem;cursor:pointer;line-height:1}
  .atlas-card-close:hover{color:var(--zone-cyan)}
  .atlas-card-eyebrow{font-family:'Rajdhani',sans-serif;letter-spacing:2px;font-size:0.78rem;color:var(--zone-cyan);text-transform:uppercase}
  #atlas-card h2{font-family:'Orbitron',sans-serif;color:#fff;font-size:1.15rem;margin:0.3rem 0 0.6rem;line-height:1.3}
  .atlas-chips{display:flex;flex-wrap:wrap;gap:0.4rem;margin-bottom:0.7rem}
  .atlas-chip{font-family:'Rajdhani',sans-serif;font-weight:700;font-size:0.68rem;letter-spacing:1.5px;padding:0.14rem 0.5rem;border-radius:3px;border:1px solid}
  .atlas-chip-raw{color:var(--t-observer);border-color:var(--t-observer)}
  .atlas-chip-preached{color:var(--zone-cyan);border-color:var(--zone-cyan)}
  .atlas-chip-canon{color:var(--gold);border-color:var(--gold)}
  .atlas-chip-forge,.atlas-chip-live{color:var(--t-pioneer);border-color:var(--t-pioneer)}
  .atlas-tier{color:var(--t-theorist)}
  .atlas-lineage,.atlas-note{color:var(--t-pioneer);font-size:0.85rem;line-height:1.5;margin:0 0 0.6rem}
  .atlas-note{color:var(--chrome);opacity:0.85}
  #atlas-card ul{margin:0 0 0.8rem 1.1rem;color:var(--chrome);font-size:0.9rem;line-height:1.55}
  #atlas-card ul li{margin-bottom:0.3rem}
  .atlas-canon-label{font-family:'Rajdhani',sans-serif;letter-spacing:2px;font-size:0.75rem;color:var(--gold);text-transform:uppercase;margin-bottom:0.3rem}
  .atlas-canon-link{display:block;color:var(--zone-cyan);text-decoration:none;font-size:0.92rem;padding:0.22rem 0;border-bottom:1px solid rgba(122,0,255,0.25)}
  .atlas-canon-link:hover{color:var(--gold)}
  .atlas-await{color:var(--t-observer);font-size:0.88rem;font-style:italic}
  .atlas-route{margin-top:0.8rem;font-family:'Rajdhani',sans-serif;letter-spacing:1px;font-size:0.8rem;color:var(--zone-violet)}
  #atlas-reader{position:absolute;inset:0;z-index:6}
  #atlas-reader[hidden]{display:none}
  .ar-backdrop{position:absolute;inset:0;background:rgba(4,4,8,0.82)}
  .ar-panel{position:absolute;inset:3% 4%;background:var(--zone-void);border:1px solid var(--zone-violet);border-radius:8px;box-shadow:0 0 40px rgba(122,0,255,0.35);overflow:hidden}
  .ar-panel iframe{position:absolute;inset:0;width:100%;height:100%;border:0;background:var(--zone-void)}
  .ar-x{position:absolute;top:6px;right:10px;z-index:2;background:rgba(10,10,10,0.85);border:1px solid var(--zone-violet);border-radius:4px;color:var(--chrome);font-size:1.5rem;line-height:1;padding:0.1rem 0.55rem;cursor:pointer}
  .ar-x:hover{color:var(--zone-cyan);border-color:var(--zone-cyan)}
  .ar-tab{position:absolute;top:10px;left:12px;z-index:2;background:rgba(10,10,10,0.85);border:1px solid var(--zone-violet);border-radius:4px;color:var(--zone-cyan);font-family:'Rajdhani',sans-serif;font-weight:700;letter-spacing:2px;font-size:0.72rem;padding:0.3rem 0.7rem;pointer-events:none}
  @media (max-width:700px){.ar-panel{inset:2% 2%}}
  .atlas-fallback{max-width:900px;margin:0 auto 3rem;padding:0 1.5rem;color:var(--chrome)}
  .atlas-fallback h2{font-family:'Orbitron',sans-serif;color:#fff;font-size:1.1rem;margin:1.4rem 0 0.5rem}
  .atlas-fallback ol{margin-left:1.3rem;line-height:1.7}
  .atlas-fallback a{color:var(--zone-cyan)}
  @media (max-width:700px){
    #atlas-stage{height:calc(100vh - var(--nav-height) - 250px)}
    #atlas-card{top:auto;left:0;right:0;width:100%;max-height:62%;border-left:none;border-top:1px solid var(--zone-violet);transform:translateY(103%)}
    #atlas-card.open{transform:none}
  }
</style>
<?php $current_page = 'atlas'; include('includes/nav.php'); ?>

<header class="atlas-hero">
  <div class="atlas-eyebrow">The Living Map</div>
  <h1>THE ATLAS</h1>
  <p class="atlas-dek">The whole manifesto on one map — six volumes, fifty-two chapters, four preached routes. Their book is frozen; this one is visibly being reforged, one Sunday at a time. Gold is canon you can read right now.</p>
</header>

<div class="atlas-legend">
  <span><span class="atlas-dot atlas-dot-raw"></span>RAW</span>
  <span><span class="atlas-dot atlas-dot-forge"></span>IN THE FORGE</span>
  <span><span class="atlas-dot atlas-dot-preached"></span>PREACHED</span>
  <span><span class="atlas-dot atlas-dot-canon"></span>CANON</span>
  <button id="atlas-live-chip" type="button">&#9679; LIVE</button>
  <span class="atlas-hint">drag to pan · scroll or pinch to zoom · tap a chapter</span>
</div>

<?php if ($atlas_og_mode): ?>
<style>
  /* og capture mode: the stage IS the frame — fixed fullscreen over all chrome */
  #atlas-stage{position:fixed;inset:0;z-index:99999;height:100vh;max-width:none;margin:0;border:none;border-radius:0}
  .atlas-zoom{display:none}
  #atlas-stage::after{content:"";position:absolute;left:0;bottom:0;width:62%;height:48%;background:radial-gradient(ellipse at 10% 90%, rgba(10,10,10,0.92), rgba(10,10,10,0.55) 48%, transparent 74%);z-index:4;pointer-events:none}
  .atlas-og-lockup{position:absolute;left:34px;bottom:34px;z-index:5;pointer-events:none}
  .atlas-og-lockup .e{font-family:'Rajdhani',sans-serif;font-weight:600;letter-spacing:4px;font-size:15px;color:var(--zone-cyan);text-transform:uppercase}
  .atlas-og-lockup h2{font-family:'Orbitron',sans-serif;font-size:58px;color:#fff;margin:2px 0 6px;text-shadow:0 0 24px rgba(0,255,247,0.35)}
  .atlas-og-lockup .s{font-family:'Exo 2',sans-serif;font-size:19px;color:var(--chrome)}
  .atlas-og-lockup .u{display:inline-block;margin-top:12px;font-family:'Rajdhani',sans-serif;font-weight:700;letter-spacing:3px;font-size:15px;color:var(--gold);border:1px solid var(--gold);border-radius:3px;padding:3px 12px}
</style>
<?php endif; ?>
<div id="atlas-stage" role="application" aria-label="Zoomable map of the OD9 Manifesto" data-plates-v="<?= @max(array_map('filemtime', glob(__DIR__ . '/images/atlas/plates/*.webp') ?: [])) ?: 1 ?>">
  <canvas id="atlas-canvas"></canvas>
<?php if ($atlas_og_mode): ?>
  <div class="atlas-og-lockup">
    <div class="e">The Living Map of the OD9 Manifesto</div>
    <h2>THE ATLAS</h2>
    <div class="s">Watch the book being reforged — one sermon at a time.</div>
    <div class="u">OFFDA9.COM/ATLAS</div>
  </div>
<?php endif; ?>
  <div class="atlas-zoom">
    <button id="atlas-zoom-in" type="button" aria-label="Zoom in">+</button>
    <button id="atlas-zoom-out" type="button" aria-label="Zoom out">&minus;</button>
    <button id="atlas-zoom-reset" type="button" aria-label="Reset view">&#8634;</button>
  </div>
  <aside id="atlas-card" aria-live="polite"></aside>
  <?php /* Codex reader host — same embed contract as the board (?embed=1 +
           window.__odClose defined by js/atlas.js). Close returns to the map:
           the Atlas is a full host of the reader, not a hand-off to it. */ ?>
  <div id="atlas-reader" hidden>
    <div class="ar-backdrop" onclick="if(window.__odClose)window.__odClose()"></div>
    <div class="ar-panel">
      <button class="ar-x" type="button" aria-label="Back to the Atlas" onclick="if(window.__odClose)window.__odClose()">&times;</button>
      <div class="ar-tab">&larr; BACK TO THE ATLAS</div>
      <iframe id="atlas-reader-frame" src="about:blank" title="Codex reader"></iframe>
    </div>
  </div>
</div>

<?php if ($atlas_map_raw !== ''): ?>
<script id="atlas-data" type="application/json"><?= $atlas_map_raw ?></script>
<?php /* mtime-versioned so every deploy busts the CF asset cache (2026-08-21:
         an unversioned URL served the previous build for minutes post-deploy) */ ?>
<script src="js/atlas.js?v=<?= @filemtime(__DIR__ . '/js/atlas.js') ?: 1 ?>" defer></script>
<?php endif; ?>

<?php if (is_array($atlas_map)): ?>
<noscript>
<div class="atlas-fallback">
<?php foreach ($atlas_map['volumes'] as $vol): ?>
  <h2><?= htmlspecialchars($vol['name']) ?></h2>
  <ol>
  <?php foreach ($atlas_map['nodes'] as $n): if ($n['vol'] !== $vol['vol']) continue; ?>
    <li><?= htmlspecialchars($n['title']) ?>
      <?php if (!empty($n['canon'])): foreach ($n['canon'] as $c): ?>
        — <a href="<?= htmlspecialchars($c['url']) ?>"><?= htmlspecialchars($c['title']) ?></a>
      <?php endforeach; endif; ?>
    </li>
  <?php endforeach; ?>
  </ol>
<?php endforeach; ?>
</div>
</noscript>
<?php else: ?>
<div class="atlas-fallback"><p>The Atlas is being charted — check back shortly.</p></div>
<?php endif; ?>

<?php include('includes/footer.php'); ?>
