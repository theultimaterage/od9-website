<?php
/**
 * The Bunker — OD9 rewards vault, fronted by the Quartermaster.
 *
 * Slice A: the keeper + Song of the Week (display). The SotW is picked weekly
 * from music_catalog (the SoundCloud catalog) and carries the Quartermaster's
 * decode-note + a vibe-reaction. Read-here, write-through-the-bot — the reflect-
 * for-credit loop (Slice B) and the tier-unlock vault (Slice D) come next.
 *
 * Reads: bot SQLite (member tier, like board.php) + offda9 MySQL (song_of_week
 * JOIN music_catalog, outer-first config like music.php/patron_gate). Both reads
 * are guarded — a bad query blanks its own widget, never the page.
 *
 * Design: css/board.css (shared iced-out tokens) + css/bunker.css.
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

// ---- Member tier (bot SQLite, guarded) ----
$tier = 'observer';
try {
    $bot = new PDO('sqlite:' . OD9_BOT_DB_PATH, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $st = $bot->prepare("SELECT current_tier FROM users WHERE user_id = ? LIMIT 1");
    $st->execute([$discordId]);
    $tier = strtolower((string)($st->fetch()['current_tier'] ?? 'observer')) ?: 'observer';
} catch (Throwable $e) {
    error_log('[bunker] bot DB read failed: ' . $e->getMessage());
}

// ---- Bunker drops (data-driven exclusives; same bot SQLite, guarded) ----
// Populated by the bot's /bunker add — a drop is a file + one command, no edit here.
$drops = [];
if (isset($bot) && $bot instanceof PDO) {
    try {
        $drops = $bot->query(
            "SELECT slug, title, artist, blurb, has_cover FROM bunker_tracks WHERE active = 1 ORDER BY id DESC"
        )->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        error_log('[bunker] bunker_tracks read failed: ' . $e->getMessage());
    }
}

// Archive features — rotated-out drops (archived=1), audio in library/bunker-archive/,
// streamed via bunker-audio.php?vault=1. Both gated by per-guild toggles in
// bunker_settings (default OFF), so the archive stays dark until the founder flips one.
$throwback = null;   // one random archived track — a "From the Vault" teaser
$mixtape   = [];     // all archived tracks — the released collection
if (isset($bot) && $bot instanceof PDO) {
    try {
        $set = $bot->query(
            "SELECT throwback_enabled, mixtape_released FROM bunker_settings LIMIT 1"
        )->fetch(PDO::FETCH_ASSOC) ?: [];
        if (!empty($set['throwback_enabled'])) {
            $throwback = $bot->query(
                "SELECT slug, title, artist, blurb, has_cover FROM bunker_tracks WHERE archived = 1 ORDER BY RANDOM() LIMIT 1"
            )->fetch(PDO::FETCH_ASSOC) ?: null;
        }
        if (!empty($set['mixtape_released'])) {
            $mixtape = $bot->query(
                "SELECT slug, title, artist, has_cover FROM bunker_tracks WHERE archived = 1 ORDER BY id DESC"
            )->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }
    } catch (Throwable $e) {
        error_log('[bunker] archive read failed: ' . $e->getMessage());
    }
}

// ---- Song of the Week (offda9 MySQL, outer-first config, guarded) ----
$sotw = null;
foreach ([__DIR__ . '/../config/database.php', __DIR__ . '/config/database.php'] as $__cfg) {
    if (is_file($__cfg)) { require_once $__cfg; break; }
}
if (function_exists('getDatabaseConnection')) {
    try {
        $db = getDatabaseConnection();
        $sotw = $db->query(
            "SELECT s.note, s.reaction, s.week_start, m.platform, m.track_id, m.title, m.artist, m.permalink_url, m.artwork_url
             FROM song_of_week s
             JOIN music_catalog m ON m.track_id = s.track_id
             ORDER BY s.week_start DESC LIMIT 1"
        )->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (Throwable $e) {
        error_log('[bunker] song_of_week read failed: ' . $e->getMessage());
    }
}

// Flash from a just-submitted reflection (board-action.php sets it; PRG).
$flash = ''; $flashKind = 'ok';
if (!empty($_SESSION['board_flash']) && is_array($_SESSION['board_flash'])) {
    $flash = (string)($_SESSION['board_flash']['msg'] ?? '');
    $flashKind = (string)($_SESSION['board_flash']['kind'] ?? 'ok');
    unset($_SESSION['board_flash']);
}

$isLocal = (($_SERVER['SERVER_NAME'] ?? '') === 'localhost') || strpos(__DIR__, 'xampp') !== false;
$IMG = $isLocal ? '/od9/public/images' : '/images';
$h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES);
$cssv = fn($p) => @filemtime(__DIR__ . '/../css/' . $p) ?: '1';

// The Quartermaster's keeper greeting (static for now; pooled later). Her voice:
// dry, deadpan, prepper, "you didn't hear it from me."
$qmGreet = "Surface world's still on fire — smart of you to keep your supplies stocked. "
         . "Everything in here you earned; I just keep the lights on and the vault locked. "
         . "Grab what's yours. You didn't hear it from me.";

// ---- Lyrics: read server-side from /lyrics (that dir denies direct HTTP; the
// Bunker is member-gated, so rendering them here IS the audience's access path). ----
$LYRICS_DIR = __DIR__ . '/../lyrics';
$readLyrics = function (string $rel) use ($LYRICS_DIR): ?string {
    $base = realpath($LYRICS_DIR);
    $real = realpath($LYRICS_DIR . '/' . $rel);   // $rel is code-supplied, not user input
    if ($base === false || $real === false
        || strncmp($real, $base . DIRECTORY_SEPARATOR, strlen($base) + 1) !== 0) return null;
    $txt = @file_get_contents($real);
    return ($txt !== false && trim($txt) !== '') ? $txt : null;
};
// Format raw lyrics into labeled sections (Intro / Verse / Chorus / Pre-Chorus /
// Hook / Bridge / Refrain / Outro / Interlude…). A header line is JUST the label
// (optionally a number / "(x2)" / brackets) — real lyric lines never match.
$fmtLyrics = function (string $raw): string {
    $secRe = '/^\s*\[?\s*((?:pre[-\s]?)?(?:intro|verse|chorus|hook|bridge|refrain|outro|interlude|breakdown|drop|skit))\b[^\]\n]*\]?\s*:?\s*$/i';
    $out = ''; $buf = []; $label = null;
    $flush = function () use (&$out, &$buf, &$label) {
        $body = trim(implode("\n", $buf)); $buf = [];
        if ($body === '' && $label === null) return;
        $out .= '<div class="ly-sec">';
        if ($label !== null) {
            // split a trailing "(…)" off the label into a quieter credit ("Chorus (Joey P.)").
            $lab = $label; $by = null;
            if (preg_match('/^(.*?)\s*\(([^)]*)\)\s*$/', $lab, $mm)) { $lab = trim($mm[1]); $by = trim($mm[2]); }
            $out .= '<span class="ly-label">' . htmlspecialchars($lab, ENT_QUOTES) . '</span>';
            if ($by !== '' && $by !== null) $out .= '<span class="ly-by">' . htmlspecialchars($by, ENT_QUOTES) . '</span>';
        }
        if ($body !== '') $out .= '<div class="ly-lines">' . htmlspecialchars($body, ENT_QUOTES) . '</div>';
        $out .= '</div>';
    };
    foreach (preg_split('/\r\n|\r|\n/', $raw) as $ln) {
        if (preg_match($secRe, $ln)) { $flush(); $label = trim(preg_replace('/\s+/', ' ', trim($ln, " \t[]:"))); }
        else { $buf[] = $ln; }
    }
    $flush();
    return $out !== '' ? $out
        : '<div class="ly-sec"><div class="ly-lines">' . htmlspecialchars(trim($raw), ENT_QUOTES) . '</div></div>';
};
// SotW lyrics: match the current track's title to the sotw index (like the picker).
$sotwLyrics = null;
if ($sotw && is_file($LYRICS_DIR . '/sotw/index.json')) {
    $idx = json_decode((string)@file_get_contents($LYRICS_DIR . '/sotw/index.json'), true);
    $t = strtolower((string)($sotw['title'] ?? ''));
    foreach (($idx['entries'] ?? []) as $e) {
        $m = strtolower(trim((string)($e['match'] ?? '')));
        if ($m !== '' && strpos($t, $m) !== false) { $sotwLyrics = $readLyrics('sotw/' . ($e['file'] ?? '')); break; }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>The Bunker · OD9</title>
<link rel="stylesheet" href="/css/board.css?v=<?= $cssv('board.css') ?>">
<link rel="stylesheet" href="/css/bunker.css?v=<?= $cssv('bunker.css') ?>">
<link rel="stylesheet" href="/js/lib/driver.css?v=<?= @filemtime(__DIR__ . '/../js/lib/driver.css') ?: '1' ?>">
<link rel="stylesheet" href="/css/tour.css?v=<?= @filemtime(__DIR__ . '/../css/tour.css') ?: '1' ?>">
<?php od9_ztrans_head(); ?>
</head>
<body data-tour="bunker" data-tour-csrf="<?= htmlspecialchars(od9_csrf_token(), ENT_QUOTES) ?>">
<?php od9_ztrans_body(); ?>
<div class="board bunker-bg" style="background:linear-gradient(180deg,rgba(8,9,11,.80),rgba(8,9,11,.92) 55%,rgba(8,9,11,.98)),url('<?= $h($IMG) ?>/bunker/bunker-bg.jpg') center/cover no-repeat fixed;">

  <header class="hud">
    <div class="brand"><img src="<?= $h($IMG) ?>/board/od9-logomark.png" alt="OD9" onerror="this.style.display='none'"><b>OD9 // THE BUNKER</b></div>
    <span class="zone">// The Bunker</span>
    <div class="spacer"></div>
    <div class="tier-badge"><span class="k">Current Tier</span><span class="v"><?= $h(ucfirst($tier)) ?></span></div>
    <a class="exit od9-tour-beacon" href="#" id="od9-tour-replay" title="Tour The Bunker"><span class="tc-new">&#9654; Tour This Zone</span><span class="tc-seen">&#9432; Tour</span></a>
    <a class="exit ztrans-link" data-ztrans="gate" href="<?= DASHBOARD_BASE_URL ?>/board.php">The Map &rsaquo;</a>
    <a class="exit ztrans-link" data-ztrans="<?= in_array($tier, ['architect','pioneer','benefactor'], true) ? 'hq-door-k1' : 'hq-door' ?>" href="<?= DASHBOARD_BASE_URL ?>/index.php">Dashboard &rsaquo;</a>
  </header>

  <!-- Quartermaster — the keeper -->
  <section class="qm-hero">
    <div class="qm-portrait bracketed">
      <video class="qm-video" controls preload="metadata" playsinline poster="<?= $h($IMG) ?>/bunker/quartermaster-hero.jpg">
        <source src="<?= $h($IMG) ?>/bunker/quartermaster.mp4" type="video/mp4">
      </video>
      <div class="qm-tag">Keeper — The Quartermaster</div>
    </div>
    <div class="qm-intro">
      <div class="qm-eyebrow">The Bunker — Rewards Vault</div>
      <h1>Welcome to The Bunker</h1>
      <p class="qm-greet">&ldquo;<?= $h($qmGreet) ?>&rdquo;</p>
    </div>
  </section>

  <?php if ($flash): ?><div class="flash <?= $h($flashKind) ?>" style="max-width:1000px;margin:0 26px 14px"><?= $h($flash) ?></div><?php endif; ?>

  <!-- Song of the Week -->
  <section class="sotw">
    <div class="sotw-eyebrow">Song of the Week — the Quartermaster cracked the vault</div>
    <?php if ($sotw): ?>
    <div class="sotw-card bracketed">
      <div class="sotw-art"><?php if (!empty($sotw['artwork_url'])): ?><img src="<?= $h($sotw['artwork_url']) ?>" alt="" loading="lazy"><?php endif; ?></div>
      <div class="sotw-body">
        <div class="sotw-track"><?= $h($sotw['title']) ?></div>
        <div class="sotw-artist"><?= $h($sotw['artist'] ?: 'The Ultimate Rage') ?></div>
        <?php
        if (($sotw['platform'] ?? '') === 'spotify') {
            // Spotify static embed (compact player); track_id is the Spotify track ID.
            $player = 'https://open.spotify.com/embed/track/' . rawurlencode($sotw['track_id']) . '?theme=0';
            $playerHeight = 152;
            $playerAllow = 'autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture';
        } else {
            $player = 'https://w.soundcloud.com/player/?url=' . urlencode($sotw['permalink_url'])
                . '&color=%23D4AF37&auto_play=false&hide_related=true&show_comments=false&show_user=true&show_reposts=false&show_teaser=false';
            $playerHeight = 120;
            $playerAllow = 'autoplay';
        }
        ?>
        <iframe class="sotw-player" width="100%" height="<?= (int)$playerHeight ?>" scrolling="no" frameborder="no" allow="<?= $h($playerAllow) ?>" loading="lazy" title="<?= $h($sotw['title']) ?>" src="<?= $h($player) ?>"></iframe>
        <?php if (!empty($sotw['note'])): ?>
        <div class="qm-note"><span class="qm-label">The Quartermaster's read</span><p><?= $h($sotw['note']) ?></p></div>
        <?php endif; ?>
        <?php if (!empty($sotw['reaction'])): ?>
        <div class="qm-react">&ldquo;<?= $h($sotw['reaction']) ?>&rdquo; <span class="qm-sig">— the Quartermaster</span></div>
        <?php endif; ?>
        <?php if ($sotwLyrics !== null): ?>
        <details class="lyrics"><summary>&#9836;&nbsp; Lyrics</summary><div class="lyrics-body"><?= $fmtLyrics($sotwLyrics) ?></div></details>
        <?php endif; ?>
        <div class="sotw-actions">
          <a class="cta ghost" href="https://discord.gg/spgmrXVMWq" target="_blank" rel="noopener"><i class="fab fa-discord"></i> Take it to #music-decoded &rsaquo;</a>
        </div>
        <details class="move sotw-reflect" style="margin-top:14px">
          <summary class="move-head">
            <div class="ic world">&#9670;</div>
            <div class="move-body"><div class="type">Reflect &middot; Consciousness</div><div class="title">What did you take from it?</div><div class="meta">Listen, then tie it to the mission or what it made you think — it's evaluated; credit lands on a real pass.</div></div>
            <div class="reward"><span class="cr">+8 CR</span><span class="cta ghost">Reflect</span></div>
          </summary>
          <form class="entry" method="post" action="board-action.php">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(od9_csrf_token(), ENT_QUOTES) ?>">
            <input type="hidden" name="action" value="sotw_reflect">
            <input type="hidden" name="return_to" value="bunker">
            <input type="hidden" name="track_id" value="<?= $h($sotw['track_id']) ?>">
            <input type="hidden" name="track_title" value="<?= $h($sotw['title']) ?>">
            <textarea name="reflection" required minlength="50" placeholder="What's it actually saying? What'd it land for you?"></textarea>
            <div class="row"><span class="hint">Min 50 chars &middot; evaluated for substance</span><button class="cta" type="submit">Submit reflection</button></div>
          </form>
        </details>
      </div>
    </div>
    <?php else: ?>
    <div class="sotw-empty">The Quartermaster's between picks. &ldquo;Check back — I don't crack the vault for just anybody.&rdquo;</div>
    <?php endif; ?>
  </section>

  <!-- Bunker Drops — data-driven exclusives (bot /bunker add). Newest first. -->
  <?php foreach ($drops as $d):
    $dslug = preg_replace('/[^a-z0-9._-]/', '', (string)($d['slug'] ?? ''));   // defense-in-depth vs the audio allow-list
    if ($dslug === '') continue;
  ?>
  <section class="vault-exclusive">
    <div class="vx-eyebrow">Bunker Exclusive &mdash; you can't stream this anywhere else</div>
    <div class="vx-card bracketed">
      <?php if (!empty($d['has_cover'])): ?>
      <div class="vx-cover"><img src="<?= $h($IMG) ?>/bunker/<?= $h($dslug) ?>-cover.jpg" alt="<?= $h($d['title']) ?>" loading="lazy" onerror="this.style.display='none'"></div>
      <?php endif; ?>
      <div class="vx-body">
        <div class="vx-title"><?= $h($d['title']) ?></div>
        <div class="vx-artist"><?= $h(($d['artist'] ?? '') ?: 'The Ultimate Rage') ?></div>
        <p class="vx-blurb"><?= $h(($d['blurb'] ?? '') ?: 'Cut for the bunker, not the feed.') ?> <span class="qm-sig">You didn't hear it from me.</span></p>
        <audio class="vx-player" controls preload="none" src="/bunker-audio.php?file=<?= rawurlencode($dslug . '.mp3') ?>" style="width:100%;margin-top:.6rem"></audio>
        <?php $lyr = $readLyrics('bunker/' . $dslug . '.txt'); if ($lyr !== null): ?>
        <details class="lyrics" style="margin-top:.6rem"><summary>&#9836;&nbsp; Lyrics</summary><div class="lyrics-body"><?= $fmtLyrics($lyr) ?></div></details>
        <?php endif; ?>
      </div>
    </div>
  </section>
  <?php endforeach; ?>

  <!-- From the Vault — a random archived track resurfaced (throwback toggle). Audio
       via /bunker-audio.php?vault=1; lyrics stay at lyrics/bunker/<slug>.txt. -->
  <?php if ($throwback):
    $tslug = preg_replace('/[^a-z0-9._-]/', '', (string)($throwback['slug'] ?? ''));
    if ($tslug !== ''): ?>
  <section class="vault-exclusive">
    <div class="vx-eyebrow">🕰️ From the Vault &mdash; back for a minute. Catch it while it's up.</div>
    <div class="vx-card bracketed">
      <?php if (!empty($throwback['has_cover'])): ?>
      <div class="vx-cover"><img src="<?= $h($IMG) ?>/bunker/<?= $h($tslug) ?>-cover.jpg" alt="<?= $h($throwback['title']) ?>" loading="lazy" onerror="this.style.display='none'"></div>
      <?php endif; ?>
      <div class="vx-body">
        <div class="vx-title"><?= $h($throwback['title']) ?></div>
        <div class="vx-artist"><?= $h(($throwback['artist'] ?? '') ?: 'The Ultimate Rage') ?></div>
        <p class="vx-blurb"><?= $h(($throwback['blurb'] ?? '') ?: 'Pulled from the archive — gone again soon.') ?> <span class="qm-sig">You didn't hear it from me.</span></p>
        <audio class="vx-player" controls preload="none" src="/bunker-audio.php?vault=1&amp;file=<?= rawurlencode($tslug . '.mp3') ?>" style="width:100%;margin-top:.6rem"></audio>
        <?php $tlyr = $readLyrics('bunker/' . $tslug . '.txt'); if ($tlyr !== null): ?>
        <details class="lyrics" style="margin-top:.6rem"><summary>&#9836;&nbsp; Lyrics</summary><div class="lyrics-body"><?= $fmtLyrics($tlyr) ?></div></details>
        <?php endif; ?>
      </div>
    </div>
  </section>
  <?php endif; endif; ?>

  <!-- The Mixtape — the released archive of past drops (mixtape toggle). -->
  <?php if (!empty($mixtape)): ?>
  <section class="vault-exclusive">
    <div class="vx-eyebrow">📼 The Mixtape &mdash; every drop that rotated out, one place. The vault's open.</div>
    <div class="vx-card bracketed">
      <div class="vx-body">
        <div class="vx-title">The Bunker Mixtape</div>
        <div class="vx-artist">The Ultimate Rage &middot; <?= count($mixtape) ?> track<?= count($mixtape) === 1 ? '' : 's' ?></div>
        <p class="vx-blurb">The drops that cycled out of the Bunker, collected. Yours because you're in. <span class="qm-sig">You didn't hear it from me.</span></p>
        <ol class="vx-tracks">
          <?php foreach ($mixtape as $mi => $m):
            $mslug = preg_replace('/[^a-z0-9._-]/', '', (string)($m['slug'] ?? ''));
            if ($mslug === '') continue;
            $mlyr = $readLyrics('bunker/' . $mslug . '.txt');
          ?>
          <li class="vx-track">
            <div class="vx-trk-head"><span class="vx-num"><?= sprintf('%02d', $mi + 1) ?></span><span class="vx-trk-title"><?= $h($m['title']) ?></span></div>
            <audio class="vx-player" controls preload="none" src="/bunker-audio.php?vault=1&amp;file=<?= rawurlencode($mslug . '.mp3') ?>"></audio>
            <?php if ($mlyr !== null): ?>
            <div class="vx-trk-extra"><details class="lyrics"><summary>&#9836;&nbsp; Lyrics</summary><div class="lyrics-body"><?= $fmtLyrics($mlyr) ?></div></details></div>
            <?php endif; ?>
          </li>
          <?php endforeach; ?>
        </ol>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- Bunker Exclusive — Phuryous Stylez Vol. 1 (members-only; audio via /bunker-audio.php) -->
  <?php
  $phuryous = [
    [1, "Phuryous Stylez Intro",     "01-phuryous-stylez-intro.mp3"],
    [2, "Demons",                    "02-demons.mp3"],
    [3, "Got The Heart",             "03-got-the-heart.mp3"],
    [4, "The Secret Recipe",         "04-the-secret-recipe.mp3"],
    [5, "Lyrical Eazy",              "05-lyrical-eazy.mp3"],
    [6, "Evil Ways",                 "06-evil-ways.mp3"],
    [7, "Mike and Scottie",          "07-mike-and-scottie.mp3"],
    [8, "You See Me",                "08-you-see-me.mp3"],
    [9, "Phuryous Stylez Interlude", "09-phuryous-stylez-interlude.mp3"],
    [10, "Turncoats",                "10-turncoats.mp3"],
    [11, "In Moe",                   "11-in-moe.mp3"],
    [12, "You Broke My Heart",       "12-you-broke-my-heart.mp3"],
    [13, "MVP",                      "13-mvp.mp3"],
    [14, "Don't Miss",               "14-dont-miss.mp3"],
    [15, "Smurk Carter",             "15-smurk-carter.mp3"],
    [16, "Oprah and Gayle",          "16-oprah-and-gayle.mp3"],
    [17, "First Person Shooter",     "17-first-person-shooter.mp3"],
  ];
  ?>
  <section class="vault-exclusive">
    <div class="vx-eyebrow">Bunker Exclusive &mdash; you can't stream this anywhere else</div>
    <div class="vx-card bracketed">
      <div class="vx-cover"><img src="<?= $h($IMG) ?>/bunker/phuryous-cover.jpg" alt="Phuryous Stylez Vol. 1" loading="lazy" onerror="this.style.display='none'"></div>
      <div class="vx-body">
        <div class="vx-title">Phuryous Stylez Vol. 1</div>
        <div class="vx-artist">The Ultimate Rage &middot; <?= count($phuryous) ?> tracks</div>
        <p class="vx-blurb">Zero commercial compromise &mdash; forged for the bunker, not the algorithm. You're in, so the vault's open. <span class="qm-sig">You didn't hear it from me.</span></p>
        <ol class="vx-tracks">
          <?php foreach ($phuryous as [$num, $title, $fileName]):
            $lyr  = $readLyrics('phuryous/' . sprintf('%02d', $num) . '.txt');
            $ytid = ($num === 15) ? '3gR8WDaPy68' : null;   // Smurk Carter lyric video
          ?>
          <li class="vx-track">
            <div class="vx-trk-head"><span class="vx-num"><?= sprintf('%02d', $num) ?></span><span class="vx-trk-title"><?= $h($title) ?></span></div>
            <audio class="vx-player" controls preload="none" src="/bunker-audio.php?file=<?= rawurlencode($fileName) ?>"></audio>
            <?php if ($lyr !== null || $ytid): ?>
            <div class="vx-trk-extra">
              <?php if ($lyr !== null): ?><details class="lyrics"><summary>&#9836;&nbsp; Lyrics</summary><div class="lyrics-body"><?= $fmtLyrics($lyr) ?></div></details><?php endif; ?>
              <?php if ($ytid): ?><details class="lyrics"><summary>&#9654;&nbsp; Lyric Video</summary><div class="lyric-vid-wrap"><iframe src="https://www.youtube.com/embed/<?= $h($ytid) ?>?rel=0" title="<?= $h($title) ?> — Lyric Video" frameborder="0" allow="accelerometer; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen loading="lazy"></iframe></div><a class="lyric-vid-link" href="https://youtu.be/<?= $h($ytid) ?>" target="_blank" rel="noopener">Watch on YouTube &rsaquo;</a></details><?php endif; ?>
            </div>
            <?php endif; ?>
          </li>
          <?php endforeach; ?>
        </ol>
      </div>
    </div>
  </section>

  <div class="tagline">Stocked for the Long Haul</div>
</div>
<script src="/js/lib/driver.js.iife.js?v=<?= @filemtime(__DIR__ . '/../js/lib/driver.js.iife.js') ?: '1' ?>"></script>
<script src="/dashboard/tour.js?v=<?= @filemtime(__DIR__ . '/tour.js') ?: '1' ?>"></script>
</body>
</html>
