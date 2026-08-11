<?php
/**
 * State of the Filter + Live Roadmap — ASCEND board renderer (dashboard/board.php).
 *
 * The board's "World State" panel, upgraded: same compact marquee tiles (the founder's
 * no-clutter rule — Clock / CO₂ / Kardashev, exactly what was there before), now driven
 * by the canonical data (includes/filter-scorecard-data.php — one source, two surfaces,
 * no drift) plus a zero-JS <details> expanding to the full 10-outcome scorecard.
 *
 * Expects: filter-scorecard-data.php already required ($SOF_* in scope).
 * Optional: $RM_ACTIVE (array of Active roadmap_triggers rows: trigger_id, domain,
 * title, condition_text) + $RM_QUEUED (int) — when set, renders the Live Roadmap
 * panel (the conditional moves the community is working toward) under the scorecard.
 * Styles: .sofb-* / .rmap-* in css/board.css.
 */
$sofb_badge = ['failing' => 'FAIL', 'at_risk' => 'RISK', 'unmeasured' => 'N/M'];
$hh = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES);
?>
  <div class="worldstate" id="filter">
    <div class="ws-eyebrow">State of the Filter — the gap we're closing<span class="sofb-count"><?= (int)$SOF_PASSING ?>/<?= (int)$SOF_TOTAL ?> passing</span></div>
    <div class="ws"><span class="wk">Doomsday Clock</span><span class="wv">85s <small>to midnight</small></span><span class="opt">optimal: <b>&infin;</b></span></div>
    <div class="ws"><span class="wk">Atmospheric CO&#8322;</span><span class="wv"><?= $hh($SOF_CO2_PPM) ?> <small>ppm peak</small></span><span class="opt">optimal: <b>&le;350</b></span></div>
    <div class="ws kard"><span class="wk">Humanity &middot; Kardashev</span><span class="wv">~0.73 <small>&rarr; Type I</small></span><span class="opt">optimal: <b>1.0+</b></span></div>
    <details class="sofb-x">
      <summary>Full scorecard &mdash; <?= (int)$SOF_TOTAL ?> outcomes a civilization that survives would be clearing &middot; <?= (int)$SOF_PASSING ?> passing &middot; every number source-verified <?= $hh($SOF_VERIFIED) ?></summary>
      <div class="sofb-rows">
        <?php foreach ($SOF_METRICS as $m): ?>
        <div class="sofb-row st-<?= $hh($m['status']) ?>">
          <span class="sofb-badge"><?= $sofb_badge[$m['status']] ?? '?' ?></span>
          <span class="sofb-t"><?= $hh($m['title']) ?><small class="tr-<?= $hh($m['trend']) ?>"><?= $hh($m['trend']) ?></small></span>
          <span class="sofb-v"><?= $hh($m['headline']) ?></span>
          <span class="sofb-o">optimal: <b><?= $m['optimal'] /* trusted authored HTML entities */ ?></b></span>
        </div>
        <?php endforeach; ?>
        <div class="sofb-foot">Outcomes on the map &middot; the <em>mechanisms</em> are yours to debate &middot; receipts + sources on the <a href="/progress.php#filter" target="_blank" rel="noopener">public scorecard &rsaquo;</a></div>
      </div>
    </details>
  </div>

<?php if (isset($RM_ACTIVE)): ?>
  <div class="rmap">
    <div class="ws-eyebrow">Live Roadmap — conditional moves &middot; no promises</div>
    <?php if ($RM_ACTIVE): foreach ($RM_ACTIVE as $t): ?>
    <a class="rmap-row" href="/roadmap.php?id=<?= $hh($t['trigger_id']) ?>" target="_blank" rel="noopener">
      <span class="rmap-dom dom-<?= $hh($t['domain']) ?>"><?= $hh($t['domain']) ?></span>
      <span class="rmap-t"><?= $hh($t['title']) ?></span>
      <span class="rmap-c"><?= $hh(mb_strimwidth((string)$t['condition_text'], 0, 96, '…')) ?></span>
    </a>
    <?php endforeach; else: ?>
    <div class="rmap-none">No active moves right now — see the full registry.</div>
    <?php endif; ?>
    <div class="rmap-foot"><?= (int)($RM_QUEUED ?? 0) ?> more approved in the queue &middot; propose at Architect, vote at Theorist+ &middot; <a href="/roadmap.php" target="_blank" rel="noopener">full roadmap &rsaquo;</a></div>
  </div>
<?php endif; ?>
