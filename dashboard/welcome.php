<?php
/**
 * OD9 web-first onboarding front door (Phase C Slice 4).
 *
 * The in-world welcome: The Wake backdrop + the Archivist, the new member's first
 * scene. Logged out -> "Connect Discord" (sets auth_redirect so callback.php
 * brings them back here). Logged in -> an explicit, plain-language data opt-in
 * (consent, default PRIVATE) -> their board. OD9 runs on consent, not
 * surveillance — that screen is the brand promise in action.
 *
 * Flow: Discord welcome DM -> here -> register -> consent -> board.php.
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
od9_dashboard_boot();

$BASE = DASHBOARD_BASE_URL;
$loggedIn = !empty($_SESSION['discord_id']);
$discordId = $_SESSION['discord_id'] ?? null;

// Logged-in consent submit -> persist visibility (web-owned) -> enter the board.
if ($loggedIn && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['consent'] ?? '') === '1') {
    try {
        require_once __DIR__ . '/includes/db.php';
        require_once __DIR__ . '/includes/profile_visibility.php';
        od9_set_profile_public($discordId, isset($_POST['share_presence']));
    } catch (Throwable $e) {
        error_log('[welcome] consent save failed: ' . $e->getMessage());
    }
    header('Location: ' . $BASE . '/board.php?welcome=1');
    exit;
}

// Logged-out: stash where to land after OAuth (callback.php honors auth_redirect).
if (!$loggedIn) {
    $_SESSION['auth_redirect'] = $BASE . '/welcome.php';
}

// Current visibility, to pre-check the toggle.
$currentPublic = false;
if ($loggedIn) {
    try {
        require_once __DIR__ . '/includes/db.php';
        require_once __DIR__ . '/includes/profile_visibility.php';
        $currentPublic = od9_is_profile_public($discordId);
    } catch (Throwable $e) { /* default private */ }
}

$IMG = (($_SERVER['SERVER_NAME'] ?? '') === 'localhost' || strpos(__DIR__, 'xampp') !== false) ? '/od9/public/images/board' : '/images/board';
$h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>Welcome to OD9 — The Wake</title>
<link rel="stylesheet" href="/css/board.css">
<style>
  .welcome { max-width: 720px; margin: 0 auto; padding: 8vh 26px 60px; }
  .welcome h1 { font-family: var(--font-display); font-weight: 700; font-size: clamp(30px, 5vw, 48px); color: var(--diamond); line-height: 1.08; margin: 14px 0 14px; }
  .welcome h1 .g { background: var(--grad-diamond); -webkit-background-clip: text; background-clip: text; color: transparent; }
  .welcome .lede { color: var(--text-muted); font-size: 16px; line-height: 1.6; max-width: 56ch; }
  .welcome .cutscene { position: relative; margin: 26px 0; border: 1px solid var(--line-strong); aspect-ratio: 16/9; overflow: hidden; background: #07090A; }
  .welcome .cutscene img { width: 100%; height: 100%; object-fit: cover; opacity: .9; }
  .welcome .cutscene .bk { position: absolute; width: 22px; height: 22px; border: 2px solid var(--world); box-shadow: var(--glow-cyan); }
  .welcome .cutscene .bk.tl{top:-1px;left:-1px;border-right:0;border-bottom:0}.welcome .cutscene .bk.tr{top:-1px;right:-1px;border-left:0;border-bottom:0}.welcome .cutscene .bk.bl{bottom:-1px;left:-1px;border-right:0;border-top:0}.welcome .cutscene .bk.br{bottom:-1px;right:-1px;border-left:0;border-top:0}
  .welcome .cutscene .vo { position: absolute; left: 16px; right: 16px; bottom: 14px; font-family: var(--font-display); font-style: italic; font-size: clamp(14px,2.2vw,18px); color: var(--diamond); text-shadow: 0 2px 12px rgba(0,0,0,.9); }
  .welcome .cutscene .who { position: absolute; left: 16px; top: 14px; font-family: var(--font-mono); font-size: 10px; letter-spacing: .16em; color: var(--world); text-transform: uppercase; background: rgba(11,11,11,.7); border: 1px solid var(--line-strong); padding: 4px 9px; }
  .welcome .big-cta { display: inline-flex; align-items: center; gap: 10px; margin-top: 8px; padding: 14px 26px; background: #5865F2; color: #fff; font-family: var(--font-display); font-weight: 700; letter-spacing: .04em; text-transform: uppercase; text-decoration: none; border-radius: var(--r-md); box-shadow: 0 0 22px rgba(88,101,242,.4); }
  .welcome .big-cta.gold { background: var(--grad-gold); color: var(--text-on-gold); box-shadow: var(--glow-gold); border: none; cursor: pointer; font-size: 14px; }
  .consent { border: 1px solid var(--border-hud); background: rgba(13,13,13,.82); border-radius: var(--r-lg); padding: 20px 22px; box-shadow: var(--glow-gold); backdrop-filter: blur(8px); margin-top: 8px; }
  .consent h3 { font-family: var(--font-display); font-weight: 600; font-size: 18px; color: var(--diamond); margin-bottom: 8px; }
  .consent p { color: var(--text-muted); font-size: 13.5px; line-height: 1.6; margin-bottom: 14px; }
  .consent label.choice { display: flex; gap: 11px; align-items: flex-start; font-size: 14px; color: var(--text-body); cursor: pointer; padding: 12px 14px; border: 1px solid var(--border-strong); border-radius: var(--r-md); background: var(--surface-inset); }
  .consent label.choice input { margin-top: 3px; accent-color: var(--gold); }
  .consent label.choice .sub { display: block; color: var(--text-faint); font-size: 12px; margin-top: 3px; }
  .consent .row { display: flex; justify-content: space-between; align-items: center; margin-top: 16px; }
  .consent .hint { font-family: var(--font-mono); font-size: 10px; color: var(--ink-faint); letter-spacing: .04em; }
</style>
</head>
<body>
<div class="board" style="background: linear-gradient(180deg, rgba(10,10,10,.4), rgba(10,10,10,.6) 45%, rgba(10,10,10,.94)), url('<?= $h($IMG) ?>/wake.jpg') center 40% / cover no-repeat;">
  <header class="hud">
    <div class="brand"><img src="<?= $h($IMG) ?>/od9-logomark.png" alt="OD9" onerror="this.style.display='none'"><b>OD9 // ASCEND</b></div>
    <span class="zone">// The Wake</span>
    <div class="spacer"></div>
  </header>

  <div class="welcome">
    <span class="ws-eyebrow">You are here — The Wake</span>
    <h1>Most people are <span class="g">asleep</span> in the wreckage.<br>You don't have to be.</h1>
    <p class="lede">OD9 is a coordination system for the people who'd rather build the future than doomscroll the collapse. This is where you wake up — and start climbing toward a Kardashev Type&nbsp;I civilization, one verified contribution at a time.</p>

    <div class="cutscene">
      <span class="bk tl"></span><span class="bk tr"></span><span class="bk bl"></span><span class="bk br"></span>
      <img src="<?= $h($IMG) ?>/guides/archivist.jpg?v=<?= @filemtime(__DIR__ . '/../images/board/guides/archivist.jpg') ?: '1' ?>" alt="The Archivist" onerror="this.style.display='none'">
      <span class="who">Your Guide — The Archivist</span>
      <span class="vo">&ldquo;You're awake now. Good. Let me show you what they kept from you.&rdquo;</span>
    </div>

    <?php if (!$loggedIn): ?>
      <a class="big-cta" href="auth/discord.php"><span style="font-size:18px">&#9670;</span> Connect Discord &amp; begin</a>
      <p class="hint" style="font-family:var(--font-mono);font-size:11px;color:var(--ink-faint);margin-top:14px">Already a member? Connecting just links your account — one click.</p>
    <?php else: ?>
      <form class="consent" method="post">
        <input type="hidden" name="consent" value="1">
        <h3>Before you enter — your data, your call.</h3>
        <p>OD9 runs on <b>consent, not surveillance</b> — that's the whole point. Choose what you share. You can change it anytime in Settings.</p>
        <label class="choice">
          <input type="checkbox" name="share_presence" <?= $currentPublic ? 'checked' : '' ?>>
          <span>Show me on the board to other members.<span class="sub">Your token appears on the zone map so others can see they're climbing alongside you. Off = you're hidden. Default: off.</span></span>
        </label>
        <div class="row"><span class="hint">You can change this anytime.</span><button class="big-cta gold" type="submit">Enter your board &rarr;</button></div>
      </form>
    <?php endif; ?>
  </div>

  <div class="tagline">Level Up or Get Left Behind</div>
</div>
</body>
</html>
