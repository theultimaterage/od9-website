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

/* Chapter-object registry (what each sprite IS + which chapter it lands on),
   written by tools/build_atlas_sprites.py from tools/atlas_objects.py. The
   renderer holds no mapping of its own, so the two cannot drift. Absent or
   unreadable => atlas.js falls back to plain glow-dots, never an error. */
$atlas_obj_path = __DIR__ . '/data/atlas-objects.json';
$atlas_obj_raw  = is_readable($atlas_obj_path) ? file_get_contents($atlas_obj_path) : '';

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
  /* find (2026-09-04): chapters, objects, ideas — "/" focuses it, arrows move, Enter flies */
  .atlas-find{position:relative;margin-left:1rem;display:flex;align-items:center}
  .atlas-find input{width:210px;max-width:44vw;background:rgba(10,10,10,0.8);border:1px solid var(--zone-violet);border-radius:4px;color:#fff;font-family:'Exo 2',sans-serif;font-size:0.85rem;padding:0.32rem 0.6rem;letter-spacing:0;text-transform:none}
  .atlas-find input::placeholder{color:var(--chrome);opacity:0.55}
  .atlas-find input:focus{outline:none;border-color:var(--zone-cyan);box-shadow:0 0 8px rgba(0,255,247,0.35)}
  .atlas-find-results{position:absolute;top:calc(100% + 4px);right:0;min-width:300px;max-width:92vw;background:rgba(10,10,10,0.96);border:1px solid var(--zone-violet);border-radius:6px;z-index:20;list-style:none;margin:0;padding:0.3rem 0;display:none;text-transform:none;letter-spacing:0}
  .atlas-find-results.open{display:block}
  .atlas-find-results li{padding:0.4rem 0.8rem;cursor:pointer;font-family:'Exo 2',sans-serif;font-size:0.88rem;color:var(--chrome);display:flex;gap:0.6rem;align-items:baseline}
  .atlas-find-results li b{font-family:'Rajdhani',sans-serif;color:var(--zone-cyan);font-weight:600;min-width:2.2rem}
  .atlas-find-results li.active,.atlas-find-results li:hover{background:rgba(122,0,255,0.25);color:#fff}
  .atlas-find-results li small{margin-left:auto;opacity:0.6;font-size:0.72rem;white-space:nowrap}
  #atlas-live-chip{display:none;font-family:'Rajdhani',sans-serif;font-weight:700;letter-spacing:2px;color:var(--t-pioneer);border:1px solid var(--t-pioneer);border-radius:3px;padding:0.15rem 0.6rem;cursor:pointer;background:none;font-size:0.78rem}
  /* the tour (2026-09-04): a guided flight along a preached route */
  .atlas-tour-wrap{position:relative}
  #atlas-tour{font-family:'Rajdhani',sans-serif;font-weight:700;letter-spacing:2px;color:var(--zone-cyan);border:1px solid var(--zone-cyan);border-radius:3px;padding:0.15rem 0.6rem;cursor:pointer;background:none;font-size:0.78rem;text-transform:uppercase}
  #atlas-tour:hover,#atlas-tour:focus-visible{outline:none;box-shadow:0 0 8px rgba(0,255,247,0.4)}
  #atlas-tour-menu{position:absolute;top:calc(100% + 6px);left:0;min-width:300px;background:rgba(10,10,10,0.96);border:1px solid var(--zone-violet);border-radius:6px;z-index:21;list-style:none;margin:0;padding:0.3rem 0;display:none;text-transform:none;letter-spacing:0}
  #atlas-tour-menu.open{display:block}
  #atlas-tour-menu li{padding:0.45rem 0.85rem;cursor:pointer;font-family:'Exo 2',sans-serif;font-size:0.88rem;color:var(--chrome);display:flex;gap:0.6rem;align-items:baseline}
  #atlas-tour-menu li b{font-family:'Rajdhani',sans-serif;color:var(--zone-cyan);font-weight:600;min-width:3.2rem}
  #atlas-tour-menu li small{margin-left:auto;opacity:0.6;font-size:0.72rem;white-space:nowrap}
  #atlas-tour-menu li:hover,#atlas-tour-menu li.active{background:rgba(122,0,255,0.25);color:#fff}
  #atlas-tour-cap{position:absolute;left:16px;bottom:16px;z-index:4;max-width:min(460px,70%);background:rgba(10,10,10,0.82);border:1px solid var(--zone-violet);border-left:3px solid var(--zone-cyan);border-radius:6px;padding:0.8rem 1rem 0.7rem;opacity:0;transform:translateY(8px);transition:opacity 0.35s,transform 0.35s;pointer-events:none}
  #atlas-tour-cap.on{opacity:1;transform:none;pointer-events:auto}
  #atlas-tour-cap .e{font-family:'Rajdhani',sans-serif;letter-spacing:2px;font-size:0.74rem;color:var(--zone-cyan);text-transform:uppercase}
  #atlas-tour-cap h3{font-family:'Orbitron',sans-serif;color:#fff;font-size:1.02rem;margin:0.25rem 0 0.35rem;line-height:1.3;cursor:pointer}
  #atlas-tour-cap h3:hover{color:var(--zone-cyan)}
  #atlas-tour-cap p{margin:0;color:var(--chrome);font-size:0.92rem;line-height:1.5;min-height:1.5em}
  #atlas-tour-cap p .cur{display:inline-block;width:0.5em;border-bottom:2px solid var(--zone-cyan);margin-left:2px;animation:atlasLivePulse 1s ease-in-out infinite}
  #atlas-tour-cap .ctl{display:flex;gap:0.4rem;align-items:center;margin-top:0.6rem}
  #atlas-tour-cap .ctl button{background:none;border:1px solid var(--zone-violet);color:var(--zone-cyan);border-radius:4px;width:34px;height:30px;cursor:pointer;font-size:0.95rem}
  #atlas-tour-cap .ctl button:hover,#atlas-tour-cap .ctl button:focus-visible{border-color:var(--zone-cyan);outline:none}
  #atlas-tour-cap .ctl .hint{margin-left:auto;font-family:'Rajdhani',sans-serif;letter-spacing:1px;font-size:0.7rem;color:var(--chrome);opacity:0.6;text-transform:uppercase}
  #atlas-tour-cap.done h3{cursor:default}
  #atlas-tour-cap.done h3:hover{color:#fff}
  #atlas-tour-cap .again{display:inline-block;margin-top:0.5rem;font-family:'Rajdhani',sans-serif;font-weight:700;letter-spacing:2px;font-size:0.76rem;color:var(--gold);text-decoration:none;text-transform:uppercase}
  @media (prefers-reduced-motion: reduce){#atlas-tour-cap{transition:none} #atlas-tour-cap p .cur{animation:none}}
  #atlas-live-chip.on{display:inline-block;animation:atlasLivePulse 2.2s ease-in-out infinite}
  @keyframes atlasLivePulse{0%,100%{box-shadow:0 0 4px var(--t-pioneer)}50%{box-shadow:0 0 14px var(--t-pioneer)}}
  #atlas-stage{position:relative;max-width:1200px;margin:0 auto 2.5rem;height:calc(100vh - var(--nav-height) - 210px);min-height:420px;border:1px solid var(--carbon-dark);border-radius:8px;overflow:hidden;background:var(--zone-void)}
  #atlas-canvas{position:absolute;inset:0;touch-action:none;cursor:grab}
  .atlas-zoom{position:absolute;right:12px;bottom:12px;display:flex;flex-direction:column;gap:6px;z-index:3}
  .atlas-zoom button{width:38px;height:38px;border-radius:6px;border:1px solid var(--zone-violet);background:rgba(10,10,10,0.8);color:var(--zone-cyan);font-size:1.15rem;font-family:'Rajdhani',sans-serif;cursor:pointer}
  .atlas-zoom button:hover,.atlas-zoom button:focus-visible{border-color:var(--zone-cyan);outline:none;box-shadow:0 0 8px rgba(0,255,247,0.4)}
  #atlas-stage.has-card .atlas-zoom{right:calc(min(390px, 92%) + 12px)}   /* the open card never buries the controls */
  .atlas-zoom button.on{border-color:var(--gold);color:var(--gold);box-shadow:0 0 10px rgba(255,215,0,0.35)}
  .atlas-zoom button.armed{animation:atlasLivePulse 2.2s ease-in-out infinite}
  /* live as an event (2026-09-04): the strip the box already knows how to fill */
  #atlas-live-strip{position:absolute;top:12px;left:12px;right:64px;max-width:640px;z-index:3;background:rgba(10,10,10,0.86);border:1px solid var(--zone-violet);border-left:3px solid var(--zone-cyan);border-radius:6px;padding:0.55rem 2.2rem 0.55rem 0.9rem;display:none;font-family:'Exo 2',sans-serif;font-size:0.9rem;color:var(--chrome);line-height:1.45}
  #atlas-live-strip.on{display:block}
  #atlas-live-strip.live{border-left-color:var(--t-pioneer)}
  #atlas-live-strip.tonight{border-left-color:var(--gold)}
  #atlas-live-strip .e{font-family:'Rajdhani',sans-serif;font-weight:700;letter-spacing:2px;font-size:0.74rem;text-transform:uppercase;color:var(--zone-cyan)}
  #atlas-live-strip.live .e{color:var(--t-pioneer);animation:atlasLivePulse 2.2s ease-in-out infinite}
  #atlas-live-strip.tonight .e{color:var(--gold)}
  #atlas-live-strip b{color:#fff;font-family:'Orbitron',sans-serif;font-size:0.92rem;letter-spacing:0.5px}
  #atlas-live-strip .pin{margin-top:0.25rem;color:var(--chrome)}
  #atlas-live-strip .pin span{font-family:'Rajdhani',sans-serif;letter-spacing:1.5px;font-size:0.72rem;color:var(--zone-cyan);text-transform:uppercase;margin-right:0.4rem}
  #atlas-live-strip a{color:var(--zone-cyan);text-decoration:none;font-family:'Rajdhani',sans-serif;font-weight:700;letter-spacing:1.5px;font-size:0.76rem;text-transform:uppercase;margin-right:0.9rem}
  #atlas-live-strip a:hover{text-decoration:underline}
  #atlas-live-strip .x{position:absolute;top:4px;right:8px;background:none;border:none;color:var(--chrome);font-size:1.2rem;cursor:pointer;line-height:1}
  /* the timeline (2026-09-04): scrub the ledger — the living-book proof */
  .atlas-timeline{max-width:1200px;margin:-1.7rem auto 2.5rem;padding:0.55rem 1.5rem 0;display:flex;gap:0.8rem;align-items:center;font-family:'Rajdhani',sans-serif;letter-spacing:1px;font-size:0.8rem;color:var(--chrome);text-transform:uppercase}
  .atlas-timeline[hidden]{display:none}
  .atlas-timeline button{background:none;border:1px solid var(--zone-violet);color:var(--zone-cyan);border-radius:4px;height:30px;min-width:36px;padding:0 0.6rem;cursor:pointer;font-family:'Rajdhani',sans-serif;font-weight:700;letter-spacing:1.5px;font-size:0.76rem;text-transform:uppercase}
  .atlas-timeline button:hover,.atlas-timeline button:focus-visible{border-color:var(--zone-cyan);outline:none;box-shadow:0 0 8px rgba(0,255,247,0.35)}
  .atlas-timeline input[type=range]{flex:1;min-width:120px;accent-color:var(--zone-cyan);cursor:pointer}
  .atlas-timeline .tl-label{display:flex;gap:0.8rem;align-items:baseline;white-space:nowrap}
  .atlas-timeline .tl-label #atlas-tl-date{color:#fff;font-weight:700;letter-spacing:2px;min-width:8.5rem}
  .atlas-timeline.past #atlas-tl-date{color:var(--gold)}
  .atlas-timeline .tl-label #atlas-tl-stat{opacity:0.75}
  @media (max-width:700px){.atlas-timeline{flex-wrap:wrap;margin-top:-1rem;padding:0.4rem 1rem 0} .atlas-timeline .tl-label{width:100%;justify-content:space-between}}
  /* the guides (2026-09-05): four presences at the map's edge, each owning a layer */
  .atlas-guides{position:absolute;left:12px;top:50%;transform:translateY(-50%);display:flex;flex-direction:column;gap:10px;z-index:3}
  .atlas-guide{width:46px;height:46px;border-radius:50%;border:1px solid var(--zone-violet);background:rgba(10,10,10,0.75);padding:0;cursor:pointer;overflow:hidden;position:relative;transition:box-shadow .2s,border-color .2s;color:var(--zone-cyan);font-family:'Orbitron',sans-serif;font-size:1rem}
  .atlas-guide img{width:100%;height:100%;object-fit:cover;display:block;border-radius:50%}
  .atlas-guide.noimg img{display:none}
  .atlas-guide.noimg::after{content:attr(data-initial);position:absolute;inset:0;display:flex;align-items:center;justify-content:center}
  .atlas-guide:hover,.atlas-guide:focus-visible,.atlas-guide.on{border-color:var(--zone-cyan);box-shadow:0 0 12px rgba(0,255,247,0.45);outline:none}
  .atlas-guide.forge{border-color:var(--gold)}
  #atlas-guide-say{position:absolute;left:70px;top:50%;transform:translate(-6px,-50%);z-index:4;max-width:330px;background:rgba(10,10,10,0.9);border:1px solid var(--zone-violet);border-left:3px solid var(--zone-cyan);border-radius:6px;padding:0.6rem 0.85rem;opacity:0;transition:opacity .25s,transform .25s;pointer-events:none;font-family:'Exo 2',sans-serif;font-size:0.9rem;color:var(--chrome);line-height:1.45}
  #atlas-guide-say.on{opacity:1;transform:translate(0,-50%);pointer-events:auto}
  #atlas-guide-say .who{font-family:'Rajdhani',sans-serif;letter-spacing:2px;font-size:0.72rem;text-transform:uppercase;color:var(--zone-cyan);margin-bottom:0.2rem}
  #atlas-guide-say .go{display:inline-block;margin-top:0.4rem;font-family:'Rajdhani',sans-serif;font-weight:700;letter-spacing:1.5px;font-size:0.74rem;color:var(--gold);text-transform:uppercase;cursor:pointer;background:none;border:none;padding:0}
  #atlas-guide-say .go:hover{text-decoration:underline}
  @media (max-width:700px){
    .atlas-guides{top:auto;bottom:14px;left:10px;transform:none;flex-direction:row;gap:8px}
    .atlas-guide{width:40px;height:40px}
    #atlas-guide-say{left:10px;right:10px;top:auto;bottom:62px;max-width:none;transform:translateY(6px)}
    #atlas-guide-say.on{transform:none}
  }
  #atlas-arrival-hint{position:absolute;left:50%;bottom:16px;transform:translateX(-50%);z-index:3;font-family:'Rajdhani',sans-serif;letter-spacing:2px;font-size:0.78rem;text-transform:uppercase;color:var(--chrome);opacity:0;transition:opacity 0.6s;pointer-events:none;background:rgba(10,10,10,0.55);padding:0.25rem 0.7rem;border-radius:3px}
  #atlas-arrival-hint.on{opacity:0.85}
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
  /* sections (2026-09-05): the chapter's own sections — click one, it lights on the map */
  .atlas-sections{margin:0.3rem 0 0.9rem 1.2rem;padding:0;color:var(--chrome);font-size:0.84rem;line-height:1.45}
  .atlas-sections li{margin-bottom:0.2rem;cursor:pointer;padding-left:0.2rem}
  .atlas-sections li:hover{color:#fff}
  .atlas-sections li.hi{color:var(--zone-cyan);text-shadow:0 0 8px rgba(0,255,247,0.5)}
  #atlas-card ul li{margin-bottom:0.3rem}
  .atlas-canon-label{font-family:'Rajdhani',sans-serif;letter-spacing:2px;font-size:0.75rem;color:var(--gold);text-transform:uppercase;margin-bottom:0.3rem}
  .atlas-canon-link{display:block;color:var(--zone-cyan);text-decoration:none;font-size:0.92rem;padding:0.22rem 0;border-bottom:1px solid rgba(122,0,255,0.25)}
  .atlas-canon-link:hover{color:var(--gold)}
  .atlas-onward{margin:0.9rem 0 0.4rem;padding:0.7rem 0.75rem;border:1px solid rgba(255,215,0,0.35);border-radius:3px;background:rgba(255,215,0,0.04)}
  .atlas-onward.is-hot{border-color:var(--gold);box-shadow:0 0 14px rgba(255,215,0,0.28)}
  .atlas-onward-label{font-family:'Rajdhani',sans-serif;letter-spacing:2px;font-size:0.75rem;color:var(--gold);text-transform:uppercase;margin-bottom:0.35rem}
  .atlas-onward-link{display:block;color:var(--chrome);text-decoration:none;font-size:0.88rem;padding:0.2rem 0}
  .atlas-onward-link.is-primary{color:var(--gold);font-weight:600;font-size:0.95rem}
  .atlas-onward-link:hover{color:var(--zone-cyan)}
  .atlas-chip-beyond{color:var(--zone-violet);border-color:var(--zone-violet)}
  .atlas-object{border:1px solid rgba(122,0,255,0.3);border-radius:3px;padding:0.6rem 0.7rem;margin:0 0 0.8rem;background:rgba(122,0,255,0.05)}
  .atlas-object-head{display:flex;align-items:baseline;gap:0.55rem;flex-wrap:wrap;margin-bottom:0.3rem}
  .atlas-object-name{font-family:'Rajdhani',sans-serif;font-weight:700;font-size:0.98rem;color:var(--chrome)}
  .atlas-object-kind{font-family:'Rajdhani',sans-serif;letter-spacing:1.4px;font-size:0.68rem;text-transform:uppercase;color:var(--zone-cyan)}
  .atlas-object-kind.is-speculative{color:var(--t-pioneer)}
  .atlas-object-blurb{margin:0;font-size:0.85rem;line-height:1.5;color:var(--t-observer)}
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
    /* PHONE FIRST (2026-09-04): the map above the fold. The title collapses to
       one line, the dek goes, the legend is one row, and the stage takes the
       screen — a Short viewer lands on stars, not a paragraph. */
    .atlas-hero{padding:0.7rem 1rem 0.3rem}
    .atlas-hero h1{font-size:1.25rem;margin:0.1rem 0 0}
    .atlas-eyebrow{font-size:0.66rem;letter-spacing:2px}
    .atlas-dek{display:none}
    .atlas-legend{margin:0.35rem auto 0.35rem;padding:0 1rem;gap:0.4rem 0.7rem;font-size:0.7rem}
    .atlas-legend .atlas-hint{display:none}
    #atlas-tour-cap{left:0;right:0;bottom:0;max-width:none;border-radius:10px 10px 0 0;border-left:1px solid var(--zone-violet);border-top:3px solid var(--zone-cyan)}
    #atlas-tour-cap .ctl .hint{display:none}
    .atlas-find{margin-left:0;width:100%}
    .atlas-find input{width:100%;max-width:none}
    #atlas-stage{height:calc(100vh - var(--nav-height) - 128px);min-height:480px;margin:0 0 1.5rem;border-radius:0;border-left:none;border-right:none}
    .atlas-zoom,#atlas-stage.has-card .atlas-zoom{top:12px;bottom:auto;right:10px}
    #atlas-live-strip{left:8px;right:58px;max-width:none;font-size:0.84rem;padding-right:1.8rem}
    #atlas-card{top:auto;left:0;right:0;width:100%;max-height:62%;border-left:none;border-top:1px solid var(--zone-violet);border-radius:14px 14px 0 0;transform:translateY(103%)}
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
  <span class="atlas-tour-wrap">
    <button id="atlas-tour" type="button" aria-haspopup="listbox" aria-expanded="false">&#9654; Take the tour</button>
    <ul id="atlas-tour-menu" role="listbox" aria-label="Routes"></ul>
  </span>
  <span class="atlas-hint">drag to pan · scroll or pinch to zoom · tap a chapter</span>
  <div class="atlas-find">
    <input id="atlas-find" type="search" placeholder="Find a chapter, an object, an idea…" aria-label="Find on the map" autocomplete="off">
    <ul id="atlas-find-results" class="atlas-find-results" role="listbox"></ul>
  </div>
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
<?php /* the sound layer's file list, server-side: Cloudflare answers browser fetches of *.json on this
         site with its block page (state.json, 2026-09-04), so the manifest rides the page instead */
      $atlas_audio_files = array_map('basename', glob(__DIR__ . '/audio/atlas/*.mp3') ?: []);
      $atlas_audio_mt = array_map('filemtime', glob(__DIR__ . '/audio/atlas/*.mp3') ?: []);
      $atlas_audio_v = $atlas_audio_mt ? max($atlas_audio_mt) : 1;   /* max([]) throws in PHP 8 */ ?>
<div id="atlas-stage" role="application" aria-label="Zoomable map of the OD9 Manifesto" data-audio="<?= htmlspecialchars(implode(',', $atlas_audio_files)) ?>" data-audio-v="<?= (int)$atlas_audio_v ?>" data-plates-v="<?= @max(array_map('filemtime', glob(__DIR__ . '/images/atlas/plates/*.webp') ?: [])) ?: 1 ?>" data-sprites-v="<?= @max(array_map('filemtime', glob(__DIR__ . '/images/atlas/sprites/*.webp') ?: [])) ?: 1 ?>">
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
    <button id="atlas-sound" type="button" aria-label="Sound" aria-pressed="false" title="Sound off — click for the ambient bed and object tones">&#9834;</button>
  </div>
  <div id="atlas-arrival-hint" aria-hidden="true">tap to skip</div>
  <div id="atlas-live-strip" aria-live="polite"></div>
  <div class="atlas-guides" role="group" aria-label="The guides">
    <button type="button" class="atlas-guide" data-guide="navigator" data-initial="N" aria-label="The Navigator — the tour"><img src="images/atlas/guides/navigator.webp?v=<?= @filemtime(__DIR__ . '/images/atlas/guides/navigator.webp') ?: 1 ?>" alt="" onerror="this.parentNode.classList.add('noimg')"></button>
    <button type="button" class="atlas-guide" data-guide="archivist" data-initial="A" aria-label="The Archivist — the timeline"><img src="images/atlas/guides/archivist.webp?v=<?= @filemtime(__DIR__ . '/images/atlas/guides/archivist.webp') ?: 1 ?>" alt="" onerror="this.parentNode.classList.add('noimg')"></button>
    <button type="button" class="atlas-guide" data-guide="forgemaster" data-initial="F" aria-label="The Forgemaster — the forge"><img src="images/atlas/guides/forgemaster.webp?v=<?= @filemtime(__DIR__ . '/images/atlas/guides/forgemaster.webp') ?: 1 ?>" alt="" onerror="this.parentNode.classList.add('noimg')"></button>
    <button type="button" class="atlas-guide" data-guide="quartermaster" data-initial="Q" aria-label="The Quartermaster — sound"><img src="images/atlas/guides/quartermaster.webp?v=<?= @filemtime(__DIR__ . '/images/atlas/guides/quartermaster.webp') ?: 1 ?>" alt="" onerror="this.parentNode.classList.add('noimg')"></button>
  </div>
  <div id="atlas-guide-say" aria-live="polite"></div>
  <div id="atlas-tour-cap" aria-live="polite"></div>
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
<div id="atlas-timeline" class="atlas-timeline" hidden>
  <button type="button" id="atlas-tl-play" aria-label="Play the forge from the first publish to now">&#9654; Forge</button>
  <input type="range" id="atlas-tl-range" min="0" max="100" value="100" step="1" aria-label="As of date">
  <div class="tl-label"><span id="atlas-tl-date">now</span><span id="atlas-tl-stat"></span></div>
  <button type="button" id="atlas-tl-now">now</button>
</div>

<?php if ($atlas_map_raw !== ''): ?>
<script id="atlas-data" type="application/json"><?= $atlas_map_raw ?></script>
<?php if ($atlas_obj_raw !== ''): ?>
<script id="atlas-objects" type="application/json"><?= $atlas_obj_raw ?></script>
<?php endif; ?>
<?php /* mtime-versioned so every deploy busts the CF asset cache (2026-08-21:
         an unversioned URL served the previous build for minutes post-deploy) */ ?>
<script src="js/atlas-sound.js?v=<?= @filemtime(__DIR__ . '/js/atlas-sound.js') ?: 1 ?>" defer></script>
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
