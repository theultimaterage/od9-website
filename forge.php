<?php
/**
 * forge.php — the manifesto's public ledger (2026-09-08).
 *
 * The Atlas shows WHERE every chapter sits. This shows WHAT STATE it is in,
 * what changed, and what is next — because a map of a book you mostly cannot
 * read yet needs to say so out loud, with dates, rather than leave forty
 * chapters reading "awaiting its sermon" and no way to tell whether that is a
 * plan or an abandonment.
 *
 * Everything here is GENERATED from data/manifesto-map.json, which the bot's
 * tools/build_manifesto_map.py writes from the manifesto itself. Nothing on
 * this page is hand-maintained, so it cannot drift from the book the way a
 * written status page always does — including the uncomfortable parts: if
 * nothing has moved in weeks, the page says that in its own headline.
 */
declare(strict_types=1);

$mapPath = __DIR__ . '/data/manifesto-map.json';
$raw = is_readable($mapPath) ? file_get_contents($mapPath) : false;
$MAP = $raw !== false ? json_decode($raw, true) : null;
if (!is_array($MAP) || empty($MAP['nodes'])) {
    error_log('forge.php: manifesto-map.json unreadable or empty at ' . $mapPath);
    http_response_code(503);
    $page_title = 'The Forge | OD9';
    include __DIR__ . '/includes/head.php';
    echo '<body><div class="wrap"><h1>The Forge</h1><p>The ledger is briefly unavailable.</p></div>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

/* NOT $h: includes/head.php defines its own $h with a string type hint, and it is
   included AFTER this, so it silently replaces ours — then a chapter number (an int)
   throws a TypeError that PHP prints INTO the page. The page still returns 200, which
   is why a status-code check would have called this healthy. */
$esc = static fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');

$nodes     = $MAP['nodes'];
$volumes   = $MAP['volumes'] ?? [];
$schedule  = $MAP['schedule'] ?? [];
$events    = $MAP['timeline']['events'] ?? [];
$readable  = array_values(array_filter($nodes, static fn($n) => !empty($n['canon'])));
$awaiting  = array_values(array_filter($nodes, static fn($n) => empty($n['canon'])));
$onAnvil   = array_values(array_filter($nodes, static fn($n) => !empty($n['forge'])));
$sections  = array_sum(array_map(static fn($n) => count($n['sections'] ?? []), $nodes));

usort($events, static fn($a, $b) => strcmp((string)$b['at'], (string)$a['at']));
$lastAt   = $events[0]['at'] ?? null;
$daysIdle = $lastAt ? (int)floor((time() - strtotime($lastAt . ' 12:00:00 UTC')) / 86400) : null;

/* The honest headline. A ledger that only reports motion is a press release;
   the number that matters most on a stalled project is the one it would rather
   not print, so it goes at the top in the same type as everything else. */
$STALL_DAYS = 14;
$stalled = $daysIdle !== null && $daysIdle > $STALL_DAYS;

$volName = [];
foreach ($volumes as $v) { $volName[$v['vol']] = $v['name']; }

$page_title       = 'The Forge — what the manifesto is, and what it is becoming | OD9';
$page_description = 'The public ledger of the OD9 Manifesto: which chapters you can read now, which is on the anvil, what changed and when, and what is planned next. Generated from the book itself.';
$page_og_title    = 'The Forge — the OD9 Manifesto, rewritten in public';
$page_og_description = 'A book being reforged one chapter at a time, with the ledger open: what is readable, what is not, and what changed.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include __DIR__ . '/includes/head.php'; ?>
<style>
  /* css/od9.css owns the chrome but sets NO body background — every page paints
     its own ground, and a page that forgets renders white with white headings.
     Same rule atlas.php uses. */
  body{background:var(--zone-void);color:var(--chrome);font-family:'Exo 2','Segoe UI',sans-serif}
  .forge-wrap{max-width:1000px;margin:0 auto;padding:0 1.5rem 4rem}
  .forge-hero{padding:2.5rem 0 1.2rem}
  .forge-hero .tag{font-family:'Rajdhani',sans-serif;letter-spacing:3px;text-transform:uppercase;font-size:0.8rem;color:var(--zone-cyan);margin:0}
  .forge-hero h1{font-family:'Orbitron',sans-serif;font-size:clamp(2rem,5vw,3rem);margin:0.2rem 0 0.6rem;color:#fff}
  .forge-hero p{font-family:'Exo 2',sans-serif;color:var(--chrome);max-width:62ch;line-height:1.6;margin:0}
  .forge-nums{display:flex;flex-wrap:wrap;gap:1.4rem 2.4rem;margin:1.6rem 0 0;padding:1rem 0;border-top:1px solid var(--carbon-dark);border-bottom:1px solid var(--carbon-dark)}
  .forge-nums div{min-width:8rem}
  .forge-nums b{display:block;font-family:'Orbitron',sans-serif;font-size:1.7rem;color:var(--gold);line-height:1.1}
  .forge-nums span{font-family:'Rajdhani',sans-serif;letter-spacing:1.5px;text-transform:uppercase;font-size:0.72rem;color:var(--chrome)}
  .forge-stall{margin:1.4rem 0 0;padding:0.85rem 1.1rem;border-left:3px solid var(--t-pioneer);background:rgba(229,181,58,0.08);font-family:'Exo 2',sans-serif;color:#fff;line-height:1.55}
  .forge-sec{margin:2.6rem 0 0}
  .forge-sec h2{font-family:'Orbitron',sans-serif;font-size:1.15rem;color:#fff;margin:0 0 0.3rem}
  .forge-sec .lede{font-family:'Exo 2',sans-serif;color:var(--chrome);margin:0 0 1rem;max-width:64ch;line-height:1.55}
  .forge-vol{font-family:'Rajdhani',sans-serif;letter-spacing:2px;text-transform:uppercase;font-size:0.75rem;color:var(--zone-cyan);margin:1.2rem 0 0.4rem}
  .forge-list{list-style:none;margin:0;padding:0}
  .forge-list li{display:flex;gap:0.7rem;align-items:baseline;padding:0.34rem 0;border-bottom:1px solid rgba(255,255,255,0.05);font-family:'Exo 2',sans-serif}
  .forge-list .n{font-family:'Rajdhani',sans-serif;font-weight:600;color:var(--chrome);min-width:3.4rem;font-size:0.85rem}
  .forge-list a{color:var(--gold);text-decoration:none;border-bottom:1px solid rgba(255,215,0,0.35)}
  .forge-list a:hover{color:#fff;border-bottom-color:#fff}
  .forge-list .muted{color:var(--chrome);opacity:0.75}
  .forge-list .why{margin-left:auto;font-size:0.76rem;color:var(--chrome);opacity:0.7;white-space:nowrap;padding-left:1rem}
  .forge-anvil{border:1px solid var(--t-pioneer);border-radius:6px;padding:1rem 1.2rem;background:rgba(229,181,58,0.05)}
  .forge-anvil h3{font-family:'Orbitron',sans-serif;margin:0 0 0.3rem;color:var(--t-pioneer);font-size:1rem}
  .forge-anvil p{font-family:'Exo 2',sans-serif;color:var(--chrome);margin:0.3rem 0 0;line-height:1.55}
  .forge-when{font-family:'Rajdhani',sans-serif;letter-spacing:1px;color:var(--chrome);font-size:0.8rem;min-width:6.5rem}
  .forge-foot{margin:3rem 0 0;padding-top:1.2rem;border-top:1px solid var(--carbon-dark);font-family:'Exo 2',sans-serif;color:var(--chrome);font-size:0.88rem;line-height:1.6}
  .forge-foot a{color:var(--zone-cyan)}
  @media (max-width:640px){ .forge-list .why{display:none} }
</style>
</head>
<body>

<?php $current_page = 'forge'; include __DIR__ . '/includes/nav.php'; ?>

<div class="forge-wrap">
  <header class="forge-hero">
    <p class="tag">The ledger</p>
    <h1>The Forge</h1>
    <p>The OD9 Manifesto is being rewritten in public, one chapter at a time. A chapter gets
       preached live, reviewed, and then struck into canon — a lesson anyone can read. This page
       is the record of that: what you can read today, what is on the anvil, what changed, and
       what is planned. It is generated from the book itself, so it cannot flatter us.</p>

    <div class="forge-nums">
      <div><b><?= count($readable) ?></b><span>chapters readable now</span></div>
      <div><b><?= count($awaiting) ?></b><span>not yet written out</span></div>
      <div><b><?= $sections ?></b><span>sections mapped</span></div>
      <div><b><?= $daysIdle === null ? '—' : $daysIdle ?></b><span>days since the last change</span></div>
    </div>

    <?php if ($stalled): ?>
    <p class="forge-stall">
      Nothing has moved in <?= (int)$daysIdle ?> days. The last change was
      <?= $esc($lastAt) ?>. The sermons that turn a raw chapter into a readable one are the
      bottleneck, and pretending otherwise on our own ledger would be the first dishonest
      thing on this site.
    </p>
    <?php endif; ?>
  </header>

  <section class="forge-sec">
    <h2>What you can read right now</h2>
    <?php $lessonCount = array_sum(array_map(static fn($n) => count($n['canon']), $readable)); ?>
    <p class="lede">These <?= count($readable) ?> chapters have been through the forge, as
       <?= $lessonCount ?> lessons — one chapter grew into three. Each links to the manifesto's own
       words, plus the study layer around them.</p>
    <?php foreach ($volumes as $v):
      $inVol = array_values(array_filter($readable, static fn($n) => (int)$n['vol'] === (int)$v['vol']));
      if (!$inVol) continue; ?>
      <p class="forge-vol"><?= $esc($v['name']) ?></p>
      <ul class="forge-list">
        <?php foreach ($inVol as $n): ?>
          <?php /* a chapter can carry several lessons — ch7 was split into three */
                foreach ($n['canon'] as $ci => $c): ?>
          <li>
            <span class="n"><?= $ci === 0 ? $esc($n['num'] === 'P' ? 'Pref' : 'Ch ' . $n['num']) : '' ?></span>
            <a href="<?= $esc($c['url']) ?>?from=forge"><?= $esc($c['title']) ?></a>
            <?php if ($ci === 0 && !empty($n['verdict'])): ?><span class="why"><?= $esc($n['verdict']) ?></span><?php endif; ?>
          </li>
          <?php endforeach; ?>
        <?php endforeach; ?>
      </ul>
    <?php endforeach; ?>
  </section>

  <?php if ($onAnvil): ?>
  <section class="forge-sec">
    <h2>On the anvil</h2>
    <div class="forge-anvil">
      <?php foreach ($onAnvil as $n): ?>
        <h3>Chapter <?= $esc($n['num']) ?> · <?= $esc($n['title']) ?></h3>
        <?php if (!empty($n['note'])): ?><p><?= $esc($n['note']) ?></p><?php endif; ?>
        <?php
          $mine = array_values(array_filter($schedule, static fn($s) => (int)($s['ch'] ?? 0) === (int)$n['num']));
          foreach ($mine as $s): ?>
          <p>Sermon <?= $esc($s['sermon']) ?> — <em><?= $esc($s['title']) ?></em>:
             <?= $esc($s['label'] ?? $s['status'] ?? '') ?>.</p>
        <?php endforeach; ?>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <?php if ($schedule): ?>
  <section class="forge-sec">
    <h2>What is planned</h2>
    <p class="lede">The preaching order, with the honest status of each. "Attempted" and "parked"
       mean exactly what they say.</p>
    <ul class="forge-list">
      <?php foreach ($schedule as $s): ?>
      <li>
        <span class="n"><?= $esc($s['sermon']) ?></span>
        <span class="muted"><?= $esc($s['title']) ?> · chapter <?= $esc($s['ch']) ?></span>
        <span class="why"><?= $esc($s['label'] ?? $s['status'] ?? '') ?></span>
      </li>
      <?php endforeach; ?>
    </ul>
  </section>
  <?php endif; ?>

  <section class="forge-sec">
    <h2>What has changed</h2>
    <p class="lede">Every state change the book has recorded, newest first. This is the same ledger
       the Atlas replays when you press "Replay history".</p>
    <ul class="forge-list">
      <?php foreach (array_slice($events, 0, 40) as $e): ?>
      <li>
        <span class="forge-when"><?= $esc($e['at']) ?></span>
        <span class="n"><?= $esc($e['num'] === 'P' ? 'Pref' : 'Ch ' . $e['num']) ?></span>
        <span class="muted"><?= $esc($e['what'] ?: $e['title']) ?></span>
        <span class="why"><?= $esc($e['state']) ?></span>
      </li>
      <?php endforeach; ?>
    </ul>
  </section>

  <p class="forge-foot">
    Forty chapters are not published yet, and that is deliberate: the revision is repairing
    citation faults found in an audit of the original draft, and putting unrepaired text on this
    site would break the one rule the project actually has. See the same book as a map in
    <a href="atlas.php">the Atlas</a>, or read the finished chapters in
    <a href="library.php">the Library</a>.
    <br><br>
    Generated from the manifesto on <?= $esc(substr((string)($MAP['generated_at'] ?? ''), 0, 10)) ?>.
  </p>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
