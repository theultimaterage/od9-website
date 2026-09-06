<?php
/**
 * THE CODEX — shared canon lesson renderer.
 * ------------------------------------------------------------
 * Each canon page is a $lesson data array + `require __DIR__/../_codex.php`
 * then `codex_render($lesson)`. Renders the full page (HUD, cover plate,
 * title, Archivist framing, canon block, study layer, source, return-CTA).
 * ?embed=1 => chrome-less (for the in-board reader modal).
 *
 * Design: scripts/web/css/codex.css. Opened from the board's Read+Check card;
 * the reflection that earns credit happens back on the board (the one chokepoint).
 *
 * $lesson shape (all text is HTML — author-controlled; entities OK):
 *   title     string
 *   eyebrow   string   e.g. "Volume I &middot; Foundation &amp; Vision"
 *   subtitle  string
 *   sigil     string   1-3 chars shown faint behind the cover plate
 *   cover     string   filename in images/board/codex/ (degrades to sigil if absent)
 *   archivist string   the in-world framing quote
 *   canon     array    ordered blocks, each ONE of:
 *       ['preamble'  => '...']                      centered italic preamble / pull-quote
 *       ['affirm'    => '...']                      gold mono affirmation line
 *       ['principle' => [n, name, verbatim, gloss]] numbered illuminated principle
 *       ['p'         => '...', 'lead'=>bool]        canon prose paragraph (serif)
 *   study     array    ordered blocks, each ONE of:
 *       ['label'   => '...']           seg eyebrow
 *       ['h3'      => '...']
 *       ['p'       => '...']
 *       ['ul'      => ['li', ...]]
 *       ['callout' => ['key','text']]
 *   source    string
 *   cta       string   (optional; defaults to the board return CTA)
 *
 * IMMUTABLE RULE: canon verbatim text (preamble/affirm/principle[2]/p) MUST match
 * the manifesto exactly. Glosses + study prose are the editable teaching layer.
 *
 * Assets use relative ../../ paths — lesson pages live at curriculum/<tier>/<slug>.php
 * (depth 2), so ../../ = web root, identical local + prod + inside the embed iframe.
 */
declare(strict_types=1);

if (!function_exists('codex_render')):
function codex_render(array $L): void {
    $embed = !empty($_GET['embed']);
    // Who is hosting the embed. The board is the default host; the Atlas passes
    // host=atlas so the closing CTA can stop promising "return to your board" —
    // there, closing lands on the Atlas card, whose onward door carries the
    // reflect-and-earn step (js/atlas.js). Anything else is treated as the board.
    $host  = ($embed && (($_GET['host'] ?? '') === 'atlas')) ? 'atlas' : 'board';
    $h  = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES);
    $mt = fn(string $rel) => @filemtime(__DIR__ . '/' . $rel) ?: '1';  // rel from curriculum/
    $cssV  = $mt('../css/codex.css');
    /* SECTION ANCHORS (2026-09-06): a canon block may name the manifesto
       section it is drawn from — ["p" => "…", "sec" => 6] — and gets
       id="sec-6". The Atlas links a section satellite straight at it; a
       section this lesson does not quote lands at the top instead, so the
       link is safe against every lesson, declared or not. */
    $anch = static function (array $b): string {
        return isset($b['sec']) ? ' id="sec-' . (int)$b['sec'] . '"' : '';
    };
    $slug = static function (string $s): string {
        $t = html_entity_decode(strip_tags($s), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $t = strtolower((string)preg_replace('/[^A-Za-z0-9]+/', '-', $t));
        return trim($t, '-') ?: 'part';
    };
    $logoV = $mt('../images/board/od9-logomark.png');
    $arcV  = $mt('../images/board/guides/archivist.jpg');
    $cover = (string)($L['cover'] ?? '');
    $covV  = $cover ? $mt('../images/board/codex/' . $cover) : '1';
    $title = (string)($L['title'] ?? 'The Codex');
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title><?= $h($title) ?> — The Codex · OD9</title>
<link rel="stylesheet" href="../../css/codex.css?v=<?= $cssV ?>">
</head>
<body class="<?= $embed ? 'embed' : '' ?><?= !empty($_GET['present']) ? ' present' : '' ?>">

<?php if (!$embed): ?>
<header class="codex-hud">
  <div class="brand"><img src="../../images/board/od9-logomark.png?v=<?= $logoV ?>" alt="OD9" onerror="this.style.display='none'"><b>OD9 // ASCEND</b></div>
  <span class="zone">// The Codex</span>
  <div class="spacer"></div>
  <a class="back" href="../../dashboard/board.php">&lsaquo; Back to your board</a>
</header>
<?php endif; ?>

<main class="codex">

  <div class="codex-cover">
    <?php if (!empty($L['sigil'])): ?><span class="sigil"><?= $h($L['sigil']) ?></span><?php endif; ?>
    <?php if ($cover): ?><img src="../../images/board/codex/<?= $h($cover) ?>?v=<?= $covV ?>" alt="" onerror="this.style.display='none'"><?php endif; ?>
    <span class="bk tl"></span><span class="bk tr"></span><span class="bk bl"></span><span class="bk br"></span>
    <span class="stamp">&#9670; The Codex &middot; Canon Record</span>
  </div>

  <?php if (!empty($L['eyebrow'])): ?><div class="codex-eyebrow"><?= $L['eyebrow'] ?></div><?php endif; ?>
  <h1 class="codex-title"><?= $h($title) ?></h1>
  <?php if (!empty($L['subtitle'])): ?><p class="codex-sub"><?= $L['subtitle'] ?></p><?php endif; ?>
  <div class="codex-rule"></div>

  <?php if (!empty($L['archivist'])): ?>
  <div class="archivist">
    <div class="port"><img src="../../images/board/guides/archivist.jpg?v=<?= $arcV ?>" alt="The Archivist" onerror="this.parentElement.style.display='none'"></div>
    <div><div class="who">The Archivist</div><div class="say"><?= $L['archivist'] ?></div></div>
  </div>
  <?php endif; ?>

  <?php if (!empty($L['canon'])): ?>
  <section class="canon" id="canon">
    <?php foreach ($L['canon'] as $blk):
      if (isset($blk['preamble'])): ?>
        <p class="canon-preamble"<?= $anch($blk) ?>><?= $blk['preamble'] ?></p>
      <?php elseif (isset($blk['affirm'])): ?>
        <div class="canon-affirm"<?= $anch($blk) ?>><?= $blk['affirm'] ?></div>
      <?php elseif (isset($blk['principle'])): [$n, $name, $text, $gloss] = $blk['principle']; ?>
        <div class="principle"<?= $anch($blk) ?>>
          <div class="n"><?= $h($n) ?></div>
          <div class="pbody">
            <div class="pname"><?= $name ?></div>
            <div class="ptext"><?= $text ?></div>
            <?php if ($gloss): ?><div class="gloss"><span class="lab">What this means for you</span><?= $gloss ?></div><?php endif; ?>
          </div>
        </div>
      <?php elseif (isset($blk['p'])): ?>
        <p class="canon-p<?= !empty($blk['lead']) ? ' lead' : '' ?>"<?= $anch($blk) ?>><?= $blk['p'] ?></p>
      <?php endif;
    endforeach; ?>
  </section>
  <?php endif; ?>

  <?php if (!empty($L['study'])): ?>
  <section class="study" id="study">
    <?php foreach ($L['study'] as $blk):
      if (isset($blk['label'])): ?>
        <div class="seg-label"><?= $blk['label'] ?></div>
      <?php elseif (isset($blk['h3'])): ?>
        <h3 id="<?= $slug($blk['h3']) ?>"><?= $blk['h3'] ?></h3>
      <?php elseif (isset($blk['p'])): ?>
        <p><?= $blk['p'] ?></p>
      <?php elseif (isset($blk['ul'])): ?>
        <ul><?php foreach ($blk['ul'] as $li): ?><li><?= $li ?></li><?php endforeach; ?></ul>
      <?php elseif (isset($blk['callout'])): [$ck, $ct] = $blk['callout']; ?>
        <div class="codex-callout"><div class="co-k"><?= $ck ?></div><p><?= $ct ?></p></div>
      <?php endif;
    endforeach; ?>
  </section>
  <?php endif; ?>

  <?php if (!empty($L['source'])): ?>
  <div class="codex-source"><?= $L['source'] ?></div>
  <?php endif; ?>

  <div class="codex-foot">
    <?php if ($embed): ?>
    <?php /* Inside the board's reader iframe a plain board.php link would load the
            board INSIDE the modal. Close the parent reader instead (same-origin) —
            __odClose also unlocks the reflection form. Fallback: break out of the
            frame to the board if the parent hook is missing. */ ?>
    <button type="button" class="cta" onclick="try{parent.__odClose()}catch(e){top.location='../../dashboard/board.php'}"><?= $host === 'atlas' ? 'Done &mdash; back to the Atlas to reflect &amp; earn &rarr;' : ($L['cta'] ?? 'Return to your board &mdash; reflect &amp; earn &rarr;') ?></button>
    <?php else: ?>
    <a class="cta" href="../../dashboard/board.php"><?= $L['cta'] ?? 'Return to your board &mdash; reflect &amp; earn &rarr;' ?></a>
    <?php endif; ?>
    <div class="tagline">Level Up or Get Left Behind</div>
  </div>

</main>

<?php /* Passive read signal (curriculum/view-beacon.php) — fires once, async via
        sendBeacon, and NEVER blocks the render. The endpoint attributes the read to
        the member if they're logged in (anonymous is a server-side no-op) and forwards
        a 0-credit lesson_view to the bot. Closes the reading blind spot the 2026-07-05
        launch redteam surfaced (content_completion sees only SUBMITTED reflections). */ ?>
<script>try{navigator.sendBeacon('../view-beacon.php?u=' + encodeURIComponent(location.pathname));}catch(e){}</script>
</body>
</html>
<?php
}
endif;
