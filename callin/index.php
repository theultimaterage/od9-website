<?php
/**
 * offda9.com/callin — THE permanent call-in door for the NCZ live show.
 *
 * Every promo, the end card, every going-live announce and the bot's
 * STREAM_CALLIN_URL point here, and the URL never changes: the engine behind it
 * (VDO.Ninja in Phase 0; the self-hosted rig in Phase 1) can be swapped or
 * burned in seconds.
 *
 * Two doors, one URL (2026-09-04):
 *   OPEN   -> 302 straight into the CURRENT guest room. Zero caller friction,
 *             exactly as before: click, mic permission, in the queue.
 *   CLOSED -> the landing page: the NEXT show (Wed/Fri/Sun 4 PM CT, dark days
 *             honoured), a countdown, watch/Discord links, and — for the eager —
 *             the green room, where early callers sit invisible and inaudible
 *             until aired (CALLIN-RIG-SPEC §Phase 0).
 *
 * Who decides open/closed: the bot (cogs/callin_door.py) publishes state.json
 * beside this file — open on the Stream Center's stream-start webhook, closed
 * on YouTube's actualEndTime or after CALLIN_OPEN_HOURS, overridable with
 * /callin open|close. state.json older than 2x its valid_for_min (bot down) is
 * ignored and the calendar decides: open from 15 min before a show until 3 h
 * after, closed otherwise.
 *
 * Config: callin_secret.config.php (sibling, mode 600, gitignored via the
 * *_secret.config.php pattern — deployed by hand like pulse_secret):
 *   <?php
 *   const NCZ_CALLIN_GUEST_URL = 'https://vdo.ninja/?room=...&password=...&...';
 * Config absent or URL empty => "lines are closed" with NO room link at all —
 * the kill switch is still blanking the config. Director URL NEVER appears here.
 *
 * Spec: docs/streams/CALLIN-RIG-SPEC.md (bot repo).
 */
declare(strict_types=1);

$cfg = __DIR__ . '/callin_secret.config.php';
if (file_exists($cfg)) {
    require_once $cfg;
}
$target = defined('NCZ_CALLIN_GUEST_URL') ? (string)NCZ_CALLIN_GUEST_URL : '';
$killed = ($target === '');

// ---- the bot's door state (see cogs/callin_door.py) --------------------------
$state = null;
$stateFile = __DIR__ . '/state.json';
if (is_readable($stateFile)) {
    $raw = json_decode((string)file_get_contents($stateFile), true);
    if (is_array($raw) && !empty($raw['generated_at'])) {
        $gen = strtotime((string)$raw['generated_at']);
        $ttl = (int)($raw['valid_for_min'] ?? 30) * 60 * 2;
        if ($gen !== false && (time() - $gen) <= $ttl) {
            $state = $raw;
        }
    }
}

$tz = new DateTimeZone('America/Chicago');
$now = new DateTimeImmutable('now', $tz);
$showDays = (isset($state['show_days']) && is_array($state['show_days'])) ? $state['show_days'] : [2, 4, 6]; // Mon=0 … Sun=6
$showHour = isset($state['show_hour_ct']) ? (int)$state['show_hour_ct'] : 16;

/** Next SHOW_HOUR on a show day, from the calendar alone (no dark-day knowledge). */
function od9_next_show(DateTimeImmutable $now, array $showDays, int $hour): ?DateTimeImmutable
{
    for ($d = 0; $d <= 21; $d++) {
        $day = $now->modify('+' . $d . ' day')->setTime($hour, 0);
        $wd = ((int)$day->format('N')) - 1;           // PHP N: Mon=1..Sun=7 -> Python weekday
        if (!in_array($wd, $showDays, true)) {
            continue;
        }
        if ($day > $now) {
            return $day;
        }
    }
    return null;
}

$next = null;
$nextLabel = '';
if ($state !== null) {
    // A fresh state is authoritative, INCLUDING "no show inside three weeks"
    // (every show day dark): the bot knows /show_skip, the calendar does not.
    if (!empty($state['next_show_utc'])) {
        try {
            $next = (new DateTimeImmutable((string)$state['next_show_utc']))->setTimezone($tz);
            $nextLabel = (string)($state['next_show_ct'] ?? '');
        } catch (Exception $e) {
            $next = null;
        }
    }
} else {
    $next = od9_next_show($now, $showDays, $showHour);
}
if ($next !== null && $nextLabel === '') {
    $nextLabel = $next->format('l, M j') . ' · ' . $next->format('g:i A') . ' CT';
}

// ---- open? the bot's state wins; the calendar covers a silent bot -------------
$open = false;
if (!$killed) {
    if ($state !== null) {
        $open = !empty($state['open']);
    } else {
        $wd = ((int)$now->format('N')) - 1;
        if (in_array($wd, $showDays, true)) {
            $todayShow = $now->setTime($showHour, 0);
            $open = ($now >= $todayShow->modify('-15 minutes')) && ($now <= $todayShow->modify('+3 hours'));
        }
    }
}

if ($open) {
    header('Location: ' . $target, true, 302);
    header('Cache-Control: no-store');
    exit;
}

http_response_code(200);
header('Cache-Control: no-store');
$h = static fn(string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>NCZ — Call-In Line</title>
<style>
  :root { --red: #FF1744; --cyan: #22E6F0; --bone: #F5F1E8; --grey: #9AA0AE; }
  body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center;
         background: #000; color: #E6E7EB; font-family: Inter, system-ui, sans-serif; text-align: center; }
  .box { padding: 40px 28px 32px; border: 2px solid var(--red); box-shadow: 0 0 18px rgba(255,23,68,.6);
         max-width: 560px; margin: 24px; }
  .kick { font-size: 12px; letter-spacing: .28em; text-transform: uppercase; color: var(--grey); margin: 0 0 14px; }
  h1 { font-family: Impact, 'Bebas Neue', sans-serif; letter-spacing: .04em; text-transform: uppercase;
       font-size: 44px; margin: 0 0 10px; line-height: 1; color: var(--bone); }
  h1 span { color: var(--red); }
  p { margin: 8px 0 0; line-height: 1.6; color: var(--grey); }
  .next { margin: 18px 0 4px; font-size: 18px; color: var(--bone); }
  .next strong { color: var(--cyan); font-weight: 700; }
  .cd { font-family: Impact, 'Bebas Neue', sans-serif; font-size: 34px; letter-spacing: .06em; color: var(--bone);
        margin: 4px 0 0; min-height: 40px; }
  .cta { display: flex; flex-wrap: wrap; gap: 10px; justify-content: center; margin: 22px 0 6px; }
  .cta a { display: inline-block; padding: 12px 18px; border: 1px solid var(--cyan); color: var(--cyan);
           text-decoration: none; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; font-size: 13px; }
  .cta a.primary { background: var(--red); border-color: var(--red); color: #fff; }
  .green { margin-top: 18px; font-size: 13px; }
  a { color: var(--cyan); text-decoration: none; }
  .foot { margin-top: 16px; font-size: 12px; }
</style>
</head>
<body>
<div class="box">
  <p class="kick">The No Cap Zone · Call-In Line</p>
  <h1>Lines are <span>closed</span></h1>
<?php if ($next !== null): ?>
  <p class="next">Next show: <strong><?= $h($nextLabel) ?></strong></p>
  <p class="cd" id="cd" data-utc="<?= $h($next->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z')) ?>"></p>
  <p>The line opens the moment the show goes live. Bring your pitch — we check it in public.
     Talk · debate · agree · disagree.</p>
<?php else: ?>
  <p class="next">No show on the calendar in the next three weeks.</p>
  <p>Watch the channel for the next date. When the show goes live, this door opens.</p>
<?php endif; ?>
  <div class="cta">
    <a class="primary" href="https://www.youtube.com/@theultimaterage/live" rel="noopener">Watch live on YouTube</a>
    <a href="https://discord.gg/spgmrXVMWq" rel="noopener">Join the Discord</a>
  </div>
<?php if (!$killed): ?>
  <p class="green">Early? <a href="<?= $h($target) ?>" rel="noopener">Sit in the green room →</a>
     You're invisible and inaudible until the host puts you on air.</p>
<?php endif; ?>
  <p class="foot"><a href="https://offda9.com/">offda9.com</a></p>
</div>
<script>
(function () {
  var el = document.getElementById('cd');
  if (!el) return;
  var at = Date.parse(el.getAttribute('data-utc'));
  function tick() {
    var ms = at - Date.now();
    if (isNaN(ms)) { el.textContent = ''; return; }
    if (ms <= 0) { el.textContent = 'ANY MINUTE — REFRESH'; return; }
    var s = Math.floor(ms / 1000), d = Math.floor(s / 86400), hh = Math.floor((s % 86400) / 3600),
        mm = Math.floor((s % 3600) / 60), ss = s % 60;
    el.textContent = (d ? d + 'd ' : '') + String(hh).padStart(2, '0') + ':' + String(mm).padStart(2, '0') + ':' + String(ss).padStart(2, '0');
  }
  tick(); setInterval(tick, 1000);
})();
</script>
</body>
</html>
