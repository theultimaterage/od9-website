<?php
/**
 * Guide-video inspection gallery — every registered guide clip on one page with a
 * player + the LIVE file's upload date/size, auto-flagged rebuilt / OLD (pre-Jun
 * rebuild cohort) / MISSING. Watch + triage what needs replacing in one scroll.
 *
 * noindex + unlisted (URL only): offda9.com/guide-gallery.php
 * Shares guide-registry.php with the DM player (guide.php).
 */
require __DIR__ . '/guide-registry.php';

$DIR = __DIR__ . '/guide-videos/';
$OLD_BEFORE = strtotime('2026-06-01');   // anything older is the pre-rebuild cohort
$h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES);

$rows = [];
$n_ok = $n_old = $n_missing = 0;
foreach ($VIDEO_MAP as $key => [$file, $char, $title, $desc]) {
    $path = $DIR . $file;
    $exists = is_file($path);
    $mtime = $exists ? filemtime($path) : 0;
    $size = $exists ? filesize($path) : 0;
    if (!$exists) { $status = 'missing'; $n_missing++; }
    elseif ($mtime < $OLD_BEFORE) { $status = 'old'; $n_old++; }
    else { $status = 'ok'; $n_ok++; }
    $rows[] = compact('key', 'file', 'char', 'title', 'desc', 'exists', 'mtime', 'size', 'status');
}
$order = ['The Archivist', 'The Forgemaster', 'The Navigator', 'The Watcher', 'All Guides'];
usort($rows, fn($a, $b) => (array_search($a['char'], $order) <=> array_search($b['char'], $order)) ?: strcmp($a['key'], $b['key']));
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>Guide Video Gallery — OD9</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{background:#07090d;color:#e6edf3;font-family:system-ui,'Segoe UI',sans-serif;padding:24px}
h1{font:700 22px/1.2 ui-monospace,monospace;letter-spacing:.04em;margin-bottom:6px}
.sub{color:#8b97a6;font-size:13px;margin-bottom:16px}
.sum{display:flex;gap:14px;flex-wrap:wrap;margin-bottom:14px;font-size:13px}
.sum b{font-size:18px}
.pill{padding:2px 9px;border-radius:999px;font:600 11px/1.6 ui-monospace,monospace}
.pill.ok{background:rgba(60,200,120,.16);color:#5fe39b;border:1px solid rgba(60,200,120,.4)}
.pill.old{background:rgba(230,170,40,.16);color:#f0c24a;border:1px solid rgba(230,170,40,.45)}
.pill.missing{background:rgba(230,70,80,.16);color:#ff7b86;border:1px solid rgba(230,70,80,.5)}
.filter{display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap}
.filter button{cursor:pointer;background:#11151c;color:#c2ccd6;border:1px solid #232a34;border-radius:7px;padding:7px 14px;font:600 12px ui-monospace,monospace}
.filter button.on{background:#1c2530;color:#fff;border-color:#3a4858}
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:18px}
.card{background:#0d1117;border:1px solid #1c232c;border-radius:12px;overflow:hidden}
.card.old{border-color:rgba(230,170,40,.4)}
.card.missing{border-color:rgba(230,70,80,.45)}
.card video{width:100%;display:block;background:#000;aspect-ratio:16/9}
.missing-box{aspect-ratio:16/9;display:grid;place-items:center;background:#150d10;color:#ff7b86;font:600 13px ui-monospace,monospace;text-align:center;padding:12px}
.meta{padding:12px 14px}
.meta .top{display:flex;justify-content:space-between;align-items:center;gap:8px;margin-bottom:6px}
.char{font:700 10px/1 ui-monospace,monospace;letter-spacing:.12em;text-transform:uppercase}
.meta h3{font-size:14px;margin:2px 0 6px;color:#fff}
.meta code{font-size:11px;color:#8b97a6;word-break:break-all}
.meta .file{display:block;margin-top:3px}
</style></head><body>
<h1>🎬 Guide Video Gallery</h1>
<div class="sub">Every registered guide clip + its live file state. Watch, then flag what needs replacing. Source: <code>guide-registry.php</code> · files: <code>/guide-videos/</code></div>
<div class="sum">
  <span><b><?= count($rows) ?></b> registered</span>
  <span class="pill ok"><?= $n_ok ?> rebuilt</span>
  <span class="pill old"><?= $n_old ?> OLD (pre-Jun)</span>
  <span class="pill missing"><?= $n_missing ?> MISSING</span>
</div>
<div class="filter">
  <button data-f="all" class="on">All (<?= count($rows) ?>)</button>
  <button data-f="old">Needs replacing (<?= $n_old ?>)</button>
  <button data-f="missing">Missing (<?= $n_missing ?>)</button>
  <button data-f="ok">Rebuilt (<?= $n_ok ?>)</button>
</div>
<div class="grid">
<?php foreach ($rows as $r): ?>
  <div class="card <?= $r['status'] ?>" data-status="<?= $r['status'] ?>">
    <?php if ($r['exists']): ?>
      <video controls preload="metadata"><source src="/guide-videos/<?= $h($r['file']) ?>?v=<?= $r['mtime'] ?>" type="video/mp4"></video>
    <?php else: ?>
      <div class="missing-box">⚠ MISSING<br>not uploaded</div>
    <?php endif; ?>
    <div class="meta">
      <div class="top">
        <span class="char" style="color:<?= $CHARACTER_COLORS[$r['char']] ?? '#8b97a6' ?>"><?= $h($r['char']) ?></span>
        <span class="pill <?= $r['status'] ?>"><?= $r['status'] === 'missing' ? 'MISSING' : (($r['status'] === 'old' ? 'OLD · ' : 'rebuilt · ') . date('M j', $r['mtime']) . ' · ' . round($r['size'] / 1048576, 1) . 'MB') ?></span>
      </div>
      <h3><?= $h($r['title']) ?></h3>
      <code><?= $h($r['key']) ?></code>
      <code class="file"><?= $h($r['file']) ?></code>
    </div>
  </div>
<?php endforeach; ?>
</div>
<script>
(function(){
  var btns = document.querySelectorAll('.filter button');
  var cards = document.querySelectorAll('.card');
  btns.forEach(function(b){
    b.addEventListener('click', function(){
      var f = b.getAttribute('data-f');
      btns.forEach(function(x){ x.classList.remove('on'); });
      b.classList.add('on');
      cards.forEach(function(c){
        c.style.display = (f === 'all' || c.getAttribute('data-status') === f) ? '' : 'none';
      });
    });
  });
})();
</script>
</body></html>
