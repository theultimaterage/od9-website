<?php
/**
 * State of the Filter — renderer.
 *
 * Usage:  require_once __DIR__ . '/filter-scorecard-data.php';
 *         include __DIR__ . '/filter-scorecard.php';
 *
 * Compact by design (the founder's call, 2026-07-04): a one-line headline strip —
 * Doomsday Clock · Kardashev status · N/10 passing — that expands (<details>, zero JS,
 * origin-minifier-safe) into the full scorecard. Each metric row expands again into the
 * verified number, what passing looks like, and the primary source link.
 */
$sof_status_label = ['failing' => 'FAILING', 'at_risk' => 'AT RISK', 'unmeasured' => 'UNMEASURED'];
$sof_trend_label  = ['worsening' => 'worsening', 'improving' => 'improving', 'stalled' => 'stalled', 'mixed' => 'mixed', 'unknown' => 'unknown'];
?>
<style>
.sof{background:#111;border:1px solid #222;border-radius:12px;margin-bottom:2.5rem;position:relative;overflow:hidden}
.sof::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,#E24632,#FFD700,#00BFFF)}
.sof-head{padding:1.6rem 1.5rem 1.2rem}
.sof-title{font-family:'Orbitron',sans-serif;font-size:1.2rem;color:#fff;letter-spacing:1px;margin-bottom:0.4rem}
.sof-tag{color:#888;font-size:0.95rem;line-height:1.55;margin-bottom:1.1rem}
.sof-strip{display:flex;flex-wrap:wrap;gap:0.6rem}
.sof-chip{display:inline-flex;align-items:center;gap:0.45rem;padding:0.4rem 0.85rem;border-radius:999px;border:1px solid #2a2a2a;background:#0c0c0c;font-family:'Orbitron',sans-serif;font-size:0.8rem;letter-spacing:1px;color:#ccc;white-space:nowrap}
.sof-chip .dot{width:7px;height:7px;border-radius:50%;display:inline-block}
.sof-chip.clock .dot{background:#FF1744;box-shadow:0 0 6px rgba(255,23,68,.7)}
.sof-chip.k .dot{background:#00BFFF;box-shadow:0 0 6px rgba(0,191,255,.6)}
.sof-chip.score .dot{background:#E24632;box-shadow:0 0 6px rgba(226,70,50,.6)}
.sof-chip.score strong{color:#E24632}
.sof>details{border-top:1px solid #1d1d1d}
.sof>details>summary{list-style:none;cursor:pointer;padding:0.95rem 1.5rem;font-family:'Rajdhani',sans-serif;font-weight:700;letter-spacing:2px;text-transform:uppercase;font-size:0.85rem;color:#00BFFF;display:flex;align-items:center;gap:0.6rem}
.sof>details>summary::-webkit-details-marker{display:none}
.sof>details>summary::after{content:'+';margin-left:auto;font-family:'Orbitron',sans-serif;color:#555;font-size:1rem}
.sof>details[open]>summary::after{content:'\2212'}
.sof-rows{padding:0 1rem 1.25rem}
.sof-row{border:1px solid #1e1e1e;border-radius:8px;background:#0c0c0c;margin-bottom:0.55rem}
.sof-row>summary{list-style:none;cursor:pointer;display:flex;align-items:center;gap:0.8rem;padding:0.8rem 1rem;flex-wrap:wrap}
.sof-row>summary::-webkit-details-marker{display:none}
.sof-badge{font-family:'Orbitron',sans-serif;font-size:0.62rem;letter-spacing:1.5px;padding:0.22rem 0.55rem;border-radius:4px;flex:none}
.sof-badge.failing{color:#E24632;border:1px solid rgba(226,70,50,.45);background:rgba(226,70,50,.08)}
.sof-badge.at_risk{color:#FFD700;border:1px solid rgba(255,215,0,.4);background:rgba(255,215,0,.07)}
.sof-badge.unmeasured{color:#b388ff;border:1px solid rgba(179,136,255,.4);background:rgba(179,136,255,.07)}
.sof-row-title{font-family:'Rajdhani',sans-serif;font-weight:700;color:#eee;font-size:1rem;letter-spacing:0.5px}
.sof-row-head{margin-left:auto;font-family:'Orbitron',sans-serif;font-size:0.78rem;color:#999;text-align:right}
.sof-trend{font-size:0.7rem;color:#666;letter-spacing:1px;text-transform:uppercase}
.sof-trend.improving{color:#2ECC71}
.sof-trend.worsening{color:#E24632}
.sof-body{padding:0.2rem 1rem 1rem;color:#aaa;font-size:0.92rem;line-height:1.65}
.sof-pass{margin-top:0.6rem;padding:0.6rem 0.8rem;border-left:2px solid #00BFFF;background:rgba(0,191,255,.05);color:#bbb;font-size:0.88rem}
.sof-pass b{color:#00BFFF;font-family:'Rajdhani',sans-serif;letter-spacing:1px;text-transform:uppercase;font-size:0.75rem}
.sof-src{margin-top:0.55rem;font-size:0.78rem;color:#666}
.sof-src a{color:#00BFFF;text-decoration:none}
.sof-foot{padding:0.9rem 1.5rem 1.4rem;border-top:1px solid #1d1d1d;color:#777;font-size:0.82rem;line-height:1.6}
.sof-foot a{color:#00BFFF;text-decoration:none}
@media(max-width:600px){.sof-row-head{margin-left:0;text-align:left;width:100%}}
</style>
<section class="sof" id="filter">
  <div class="sof-head">
    <h2 class="sof-title">State of the Filter</h2>
    <p class="sof-tag">
      Humanity by the numbers. Becoming a Type I civilization isn't a tech metric — it's a
      coordination metric. These are the outcomes a society optimized to pass the Great Filter
      would be clearing. Every number is verified against its primary source; the receipts are one tap away.
    </p>
    <div class="sof-strip">
      <span class="sof-chip clock"><span class="dot"></span><?= htmlspecialchars($SOF_CLOCK) ?></span>
      <span class="sof-chip k"><span class="dot"></span>Kardashev <?= htmlspecialchars($SOF_KARDASHEV) ?></span>
      <span class="sof-chip score"><span class="dot"></span><strong><?= (int)$SOF_PASSING ?> / <?= (int)$SOF_TOTAL ?></strong>&nbsp;passing</span>
    </div>
  </div>
  <details>
    <summary>Open the scorecard</summary>
    <div class="sof-rows">
      <?php foreach ($SOF_METRICS as $m): ?>
      <details class="sof-row">
        <summary>
          <span class="sof-badge <?= htmlspecialchars($m['status']) ?>"><?= $sof_status_label[$m['status']] ?? strtoupper($m['status']) ?></span>
          <span class="sof-row-title"><?= htmlspecialchars($m['title']) ?></span>
          <span class="sof-row-head"><?= htmlspecialchars($m['headline']) ?><br>
            <span class="sof-trend <?= htmlspecialchars($m['trend']) ?>">trend: <?= $sof_trend_label[$m['trend']] ?? $m['trend'] ?></span>
          </span>
        </summary>
        <div class="sof-body">
          <?= htmlspecialchars($m['detail']) ?>
          <div class="sof-pass"><b>What passing looks like:</b> <?= htmlspecialchars($m['passing']) ?></div>
          <div class="sof-src">Source: <a href="<?= htmlspecialchars($m['source_url']) ?>" target="_blank" rel="noopener"><?= htmlspecialchars($m['source']) ?></a></div>
        </div>
      </details>
      <?php endforeach; ?>
    </div>
  </details>
  <div class="sof-foot">
    The scorecard tracks <strong style="color:#ccc">outcomes</strong>. The <em>mechanisms</em> — how humanity
    gets there — are for the community to reason about: proposed, debated, and voted on inside OD9
    (see the <a href="roadmap.php">live roadmap</a>). Every number above was verified against its primary
    source on <?= htmlspecialchars($SOF_VERIFIED) ?>. Think one is wrong or stale? Challenge it in the
    Discord — catching us is the system working.
  </div>
</section>
