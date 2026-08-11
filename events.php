<?php
/**
 * OD9 - Event Schedule
 * Member-facing surface of the official event board (Event Nights program,
 * founder decision 2026-07-18: "always some shit crackin', not just willy-nilly").
 * Reads the SAME community_events table the bot's pinned board + /events use
 * (web = client, bot = brain — see cogs/events_schedule.py). The Weekly Rhythm
 * is static; "Locked In" is live from the bot's SQLite via includes/od9_sqlite.php.
 */
$page_title = 'OD9 Event Schedule | Off Da Nine';
$page_description = 'The official OD9 event schedule: the daily live + Encore Drop, Think Tank every day at 6 PM CT, Freestyle Fridays, Saturday Event Nights, and every dated event locked in.';
$page_slug = 'events.php';
$page_og_description = 'Daily lives, Think Tank at 6 PM CT, Freestyle Fridays, Saturday Event Nights - the official OD9 schedule, straight from the bot.';

/* ---- Live "Locked In" events from the bot's SQLite ------------------------
 * The community_events table lands with the next bot deploy, so it may not
 * exist on prod yet; ANY failure here degrades to the static Weekly Rhythm
 * only — never a fatal. No guild_id filter, deliberately: the bot DB is
 * single-guild in practice and the web-side guild constant has drifted from
 * the real guild id before — filtering by the constant matched zero rows
 * (see the note in dashboard/index.php). */
$events = [];
try {
    require_once __DIR__ . '/includes/od9_sqlite.php';
    [$botDb] = od9_member_source();
    if ($botDb) {
        $stmt = $botDb->query(
            "SELECT event_date, title, event_type, notes FROM community_events
             WHERE event_date >= date('now')
             ORDER BY event_date ASC, event_id ASC LIMIT 30"
        );
        $events = $stmt ? $stmt->fetchAll() : [];
    }
} catch (Throwable $e) {
    error_log('[events.php] events read failed: ' . $e->getMessage());
    $events = [];
}

/* Mirrors TYPE_EMOJI in cogs/events_schedule.py — keep the two in sync. */
$typeEmoji = [
    'fight' => '🥊', 'sports' => '🏈', 'movie' => '🎬',
    'awards' => '🏆', 'freestyle' => '🎤', 'game' => '🎮', 'special' => '🎉',
];

/* Group by month, preserving the query's chronological order. UTC dates to
 * match the query's date('now'), so the highlight window can't disagree
 * with what the query returned. */
$weekCutoff = gmdate('Y-m-d', time() + 7 * 86400);
$months = [];
foreach ($events as $ev) {
    $key = substr((string)$ev['event_date'], 0, 7);
    if (!isset($months[$key])) {
        $m = DateTime::createFromFormat('Y-m-d', $key . '-01');
        $months[$key] = ['label' => $m ? $m->format('F Y') : $key, 'events' => []];
    }
    $months[$key]['events'][] = $ev;
}

$esc = static fn(string $s): string => htmlspecialchars($s, ENT_QUOTES);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include __DIR__ . '/includes/head.php'; ?>
<style>
body{background:var(--d);color:var(--c);font-family:'Exo 2',sans-serif;min-height:100vh;line-height:1.7}
.wrap{max-width:920px;margin:0 auto;padding:0 1.5rem}
h1,h2,h3{font-family:'Orbitron',sans-serif;color:#fff;line-height:1.25}

/* Hero */
.ev-hero{background:linear-gradient(135deg,#050505 0%,#0a0a12 55%,#050505 100%);border-bottom:1px solid #1a1a2e;padding:4.5rem 1.5rem 3.5rem;text-align:center}
.ev-hero .badge{display:inline-block;font-family:'Orbitron',sans-serif;font-size:.72rem;letter-spacing:3px;text-transform:uppercase;color:var(--b);border:1px solid rgba(0,191,255,.34);padding:.35rem 1rem;border-radius:2px;text-shadow:0 0 12px rgba(0,191,255,.4);margin-bottom:1.4rem}
.ev-hero h1{font-size:clamp(1.9rem,5.2vw,3rem);font-weight:900;text-shadow:0 0 30px rgba(0,191,255,.27);margin-bottom:1rem}
.ev-hero p{max-width:640px;margin:0 auto;font-family:'Rajdhani',sans-serif;font-weight:600;font-size:clamp(1.1rem,2.8vw,1.35rem);color:#cfd2d6}

/* Sections */
.ev-section{padding:3.2rem 0;border-bottom:1px solid #121218}
.ev-section:last-of-type{border-bottom:none}
.kicker{font-family:'Orbitron',sans-serif;font-size:.72rem;letter-spacing:3px;text-transform:uppercase;color:var(--b);margin-bottom:.8rem;display:block}
.ev-section h2{font-size:clamp(1.4rem,4vw,2rem);font-weight:800;margin-bottom:1.5rem}
.section-note{color:#8a8f94;font-size:.95rem;margin:-.8rem 0 1.4rem}

/* Weekly rhythm */
.rhythm-list{list-style:none;margin:0;padding:0;display:grid;gap:.9rem}
.rhythm-list li{display:flex;align-items:flex-start;gap:1rem;background:rgba(0,191,255,.04);border:1px solid #1a1a26;border-radius:8px;padding:1rem 1.2rem}
.r-emoji{font-size:1.4rem;line-height:1.5;flex:0 0 auto}
.r-when{font-family:'Rajdhani',sans-serif;font-weight:700;font-size:1.05rem;color:#fff;letter-spacing:.5px}
.r-what{color:#b3b6ba;margin-left:.3rem}

/* Locked In */
.month-label{font-size:.8rem;letter-spacing:3px;text-transform:uppercase;color:var(--gold);border-bottom:1px solid #1d1d28;padding-bottom:.5rem;margin:2.2rem 0 1rem}
.month-label:first-of-type{margin-top:0}
.ev-list{list-style:none;margin:0;padding:0;display:grid;gap:.7rem}
.ev-row{display:flex;flex-wrap:wrap;align-items:center;gap:.9rem;background:#0f0f15;border:1px solid #1d1d28;border-left:3px solid #1d1d28;border-radius:8px;padding:.9rem 1.1rem}
.ev-row.soon{border-color:rgba(0,191,255,.35);border-left-color:var(--b);background:rgba(0,191,255,.055)}
.ev-emoji{font-size:1.35rem;flex:0 0 auto}
.ev-date{flex:0 0 auto;min-width:4.8rem;text-align:center}
.ev-dow{display:block;font-family:'Orbitron',sans-serif;font-size:.62rem;letter-spacing:2px;color:var(--b)}
.ev-day{display:block;font-family:'Rajdhani',sans-serif;font-weight:700;font-size:1.02rem;color:#fff}
.ev-body{flex:1;min-width:220px}
.ev-title{display:block;font-family:'Rajdhani',sans-serif;font-weight:700;font-size:1.08rem;color:#fff;letter-spacing:.3px}
.ev-notes{display:block;color:#8a8f94;font-size:.92rem}
.ev-soon-tag{flex:0 0 auto;font-family:'Orbitron',sans-serif;font-size:.6rem;letter-spacing:2px;text-transform:uppercase;color:var(--b);border:1px solid rgba(0,191,255,.45);border-radius:2px;padding:.25rem .55rem;text-shadow:0 0 10px rgba(0,191,255,.4)}
.ev-empty{background:#0f0f15;border:1px dashed #232331;border-radius:8px;padding:1.6rem 1.4rem;text-align:center;color:#9aa0a6}
.ev-empty strong{color:#fff}

/* CTA */
.btn{display:inline-flex;align-items:center;gap:.5rem;font-family:'Orbitron',sans-serif;font-size:.78rem;font-weight:700;letter-spacing:1.4px;text-transform:uppercase;padding:.8rem 1.5rem;border-radius:4px;text-decoration:none;transition:transform .25s,box-shadow .25s}
.btn-primary{background:linear-gradient(135deg,var(--b),var(--eb));color:#000;box-shadow:var(--g)}
.btn-primary:hover{transform:translateY(-2px)}
.invite{background:linear-gradient(135deg,rgba(0,191,255,.1),rgba(0,0,0,.6));border:1px solid rgba(0,191,255,.3);border-radius:16px;padding:2.4rem 2rem;text-align:center;margin:3rem 0 4rem;box-shadow:var(--g)}
.invite h2{font-size:clamp(1.4rem,4vw,2rem);margin-bottom:.8rem}
.invite p{max-width:560px;margin:0 auto 1.6rem;color:#c4c7cb;font-size:1.02rem}

@media(max-width:600px){
  .ev-hero{padding:3.2rem 1.2rem 2.6rem}
  .rhythm-list li{padding:.9rem 1rem}
  .ev-row{gap:.7rem;padding:.8rem .9rem}
  .ev-date{text-align:left;min-width:4.2rem}
}
</style>
</head>
<body>
<?php $current_page = 'events'; include('includes/nav.php'); ?>

<header class="ev-hero">
  <span class="badge">Event Nights</span>
  <h1>📅 The OD9 Event Schedule</h1>
  <p>Always some shit crackin'. Pull up.</p>
</header>

<div class="wrap">

  <!-- THE WEEKLY RHYTHM (static — the standing cadence) -->
  <section class="ev-section">
    <span class="kicker">Every Week</span>
    <h2>The Weekly Rhythm</h2>
    <ul class="rhythm-list">
      <li><span class="r-emoji">🎙️</span><div><span class="r-when">Daily</span><span class="r-what">&mdash; the live + the Encore Drop</span></div></li>
      <li><span class="r-emoji">🧠</span><div><span class="r-when">Daily 6 PM CT</span><span class="r-what">&mdash; Think Tank + tonight's lesson (roll call at 8 PM)</span></div></li>
      <li><span class="r-emoji">🎤</span><div><span class="r-when">Fridays</span><span class="r-what">&mdash; Freestyle Friday</span></div></li>
      <li><span class="r-emoji">🍿</span><div><span class="r-when">Saturdays</span><span class="r-what">&mdash; Event Night (fights / movies / specials)</span></div></li>
      <li><span class="r-emoji">🎯</span><div><span class="r-when">Weekly</span><span class="r-what">&mdash; Formation mission</span></div></li>
    </ul>
  </section>

  <!-- LOCKED IN (live from the bot's community_events table) -->
  <section class="ev-section">
    <span class="kicker">The Official Board</span>
    <h2>Locked In</h2>
    <?php if ($months): ?>
      <p class="section-note">Dated events, straight from the bot. Rows lit up in blue go down within the next 7 days.</p>
      <?php foreach ($months as $month): ?>
        <h3 class="month-label"><?= $esc($month['label']) ?></h3>
        <ul class="ev-list">
          <?php foreach ($month['events'] as $ev):
              $date  = (string)$ev['event_date'];
              $d     = DateTime::createFromFormat('Y-m-d', $date);
              $emoji = $typeEmoji[$ev['event_type'] ?? 'special'] ?? '🎉';
              $soon  = $date <= $weekCutoff;
          ?>
          <li class="ev-row<?= $soon ? ' soon' : '' ?>">
            <span class="ev-emoji"><?= $emoji ?></span>
            <div class="ev-date">
              <span class="ev-dow"><?= $d ? $esc(strtoupper($d->format('D'))) : '' ?></span>
              <span class="ev-day"><?= $d ? $esc($d->format('M j')) : $esc($date) ?></span>
            </div>
            <div class="ev-body">
              <span class="ev-title"><?= $esc((string)$ev['title']) ?></span>
              <?php if (!empty($ev['notes'])): ?><span class="ev-notes"><?= $esc((string)$ev['notes']) ?></span><?php endif; ?>
            </div>
            <?php if ($soon): ?><span class="ev-soon-tag">This Week</span><?php endif; ?>
          </li>
          <?php endforeach; ?>
        </ul>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="ev-empty"><strong>Nothing dated on the board yet</strong> &mdash; drop your ideas in the chat. The Weekly Rhythm runs regardless.</div>
    <?php endif; ?>
  </section>

  <!-- CTA -->
  <section class="invite">
    <h2>Pull up.</h2>
    <p>Every one of these goes down on the Discord &mdash; the live, the Think Tank, the fights, all of it. Free to join.</p>
    <a class="btn btn-primary" href="https://discord.gg/spgmrXVMWq" target="_blank" rel="noopener"><i class="fab fa-discord"></i> Pull Up &mdash; Join the Discord</a>
  </section>

</div>

<?php include('includes/footer.php'); ?>
</body>
</html>
