<?php
/**
 * OD9 Member Area — personalized progression view.
 *
 * Reached via HMAC-signed link DMed by the bot's `/me_link` slash command:
 *   https://offda9.com/me.php?u=<discord_user_id>&t=<unix_ts>&s=<hmac_sha256>
 *
 * The HMAC secret is shared with the bot (OD9_DRIP_WEBHOOK_SECRET — same
 * secret already used by /api/discord/link_status.php for similar bot↔web
 * authenticated flows). Links expire 24h after issuance.
 *
 * Data sources (all in offda9_od9_tickets — same DB as the rest of the
 * offda9.com web app, no cross-DB grants needed):
 *   - od9_bot_users (15-min cron mirror — current_tier slug + total_credits + username)
 *   - od9_bot_dimensions (cron mirror — 5 dim scores)
 *
 * The bot's `scripts/sync_to_mysql.py` populates these tables every 15 min.
 * If a member action just happened, their data may lag by up to 15 min.
 *
 * T-INF-WEB-MEMBER-AREA-001 (activated 2026-05-09).
 */

declare(strict_types=1);
header('Content-Type: text/html; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-cache, no-store, must-revalidate');

// ------------------------------ HMAC validation -----------------------------

const LINK_MAX_AGE_SEC = 86400;  // 24 h
const HMAC_SECRET_FILE = __DIR__ . '/api/drip/webhook_secret.config.php';

$invalid_reason = null;

$discord_user_id = $_GET['u'] ?? '';
$timestamp_str = $_GET['t'] ?? '';
$signature = $_GET['s'] ?? '';

if (!preg_match('/^\d{15,25}$/', (string)$discord_user_id)) {
    $invalid_reason = 'malformed_user_id';
}
if (!ctype_digit((string)$timestamp_str)) {
    $invalid_reason = $invalid_reason ?? 'malformed_timestamp';
}
$timestamp = (int)$timestamp_str;
if ($invalid_reason === null && abs(time() - $timestamp) > LINK_MAX_AGE_SEC) {
    $invalid_reason = 'expired';
}
if ($invalid_reason === null && !preg_match('/^[a-f0-9]{64}$/i', (string)$signature)) {
    $invalid_reason = 'malformed_signature';
}

$secret = '';
if (file_exists(HMAC_SECRET_FILE)) {
    require HMAC_SECRET_FILE;  // defines OD9_WEBHOOK_SECRET
    $secret = defined('OD9_WEBHOOK_SECRET') ? OD9_WEBHOOK_SECRET : '';
}
if ($secret === '' && $invalid_reason === null) {
    error_log('[me.php] HMAC secret not configured');
    $invalid_reason = 'server_misconfigured';
}

if ($invalid_reason === null) {
    $payload = $discord_user_id . '|' . $timestamp_str;
    $expected = hash_hmac('sha256', $payload, $secret);
    if (!hash_equals($expected, $signature)) {
        $invalid_reason = 'bad_signature';
    }
}

// ------------------------------ Data fetch ----------------------------------

$tier_slug = null;
$tier_name = null;
$credits = null;
$dimensions = null;  // ['knowledge_score' => N, 'resource_score' => N, ...]
$username = null;
$achievements = [];  // [['achievement_id','name','category','rarity','icon','badge_image','is_hidden','earned'], ...]
$db_unavailable = false;

if ($invalid_reason === null) {
    $configPath = __DIR__ . '/../config/database.php';
    if (!file_exists($configPath)) {
        $configPath = __DIR__ . '/config/database.php';
    }
    require_once $configPath;
    require_once __DIR__ . '/includes/od9_sqlite.php';

    try {
        // Read the bot's LIVE data (SQLite), falling back to the od9_bot_* MySQL
        // mirror during the bake period. $ut/$dt are the source-appropriate table
        // names (fixed strings from od9_member_source — never user input).
        [$pdo, $ut, $dt] = od9_member_source();
        if (!$pdo) throw new RuntimeException('no member data source');

        // Tier slug + credits + username
        $stmt = $pdo->prepare(
            "SELECT username, current_tier, total_credits
             FROM $ut WHERE user_id = ? LIMIT 1"
        );
        $stmt->execute([$discord_user_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $username = $row['username'];
            $tier_slug = strtolower((string)($row['current_tier'] ?: 'observer'));
            $tier_name = ucfirst($tier_slug);
            $credits = (int)$row['total_credits'];
        }

        // Dimensions
        try {
            $stmt = $pdo->prepare(
                "SELECT knowledge_score, resource_score, community_score, consciousness_score, system_score
                 FROM $dt WHERE user_id = ? LIMIT 1"
            );
            $stmt->execute([$discord_user_id]);
            $r3 = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($r3) $dimensions = $r3;
        } catch (Throwable $e) { /* dimensions table missing on this env — tolerate */ }

        // Achievements — for the badge grid. Earned (LEFT JOIN hit) vs locked;
        // hidden + unearned are dropped in the render so secret badges stay secret.
        try {
            $astmt = $pdo->prepare(
                "SELECT ad.achievement_id, ad.name, ad.category, ad.rarity, ad.icon,
                        ad.badge_image, ad.is_hidden,
                        (ua.user_id IS NOT NULL) AS earned, ua.revealed AS revealed
                 FROM achievement_definitions ad
                 LEFT JOIN user_achievements ua
                   ON ua.achievement_id = ad.achievement_id
                  AND ua.guild_id = ad.guild_id AND ua.user_id = ?
                 WHERE ad.guild_id != 'global'
                 ORDER BY ad.sort_order"
            );
            $astmt->execute([$discord_user_id]);
            $achievements = $astmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) { /* achievements table absent on this env — tolerate */ }
    } catch (Throwable $e) {
        error_log('[me.php] DB query failed: ' . $e->getMessage());
        $db_unavailable = true;
    }

    if (!$db_unavailable && $tier_slug === null) {
        $invalid_reason = 'no_member_record';
    }
}

// ------------------------------ Tier visual mapping --------------------------
// Canonical tier colors (mirror of config.TIER_COLORS) — single source shared
// with profile.php / library.php / tiers.php.
require_once __DIR__ . '/includes/tiers.php';
$TIER_COLORS = $GLOBALS['OD9_TIER_COLORS'];
$tier_color = od9_tier_color($tier_slug ?? 'observer');

// ------------------------------ Output ---------------------------------------
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Your OD9 Progress</title>
<meta name="robots" content="noindex,nofollow">
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@400;600;700&family=Exo+2:wght@400;600&display=swap" rel="stylesheet">
<style>
:root{--b:#00BFFF;--gold:#FFD700;--d:#0A0A0A;--dd:#111;--c:#C0C0C0;--g:0 0 20px rgba(0,191,255,.45);--tier:<?= htmlspecialchars($tier_color, ENT_QUOTES) ?>}
*{margin:0;padding:0;box-sizing:border-box}
body{background:var(--d);color:var(--c);font-family:'Exo 2',sans-serif;min-height:100vh;padding:2rem 1rem 4rem}
.wrap{max-width:720px;margin:0 auto}
header{text-align:center;margin-bottom:2rem}
h1{font-family:'Orbitron',sans-serif;font-size:clamp(1.5rem,4vw,2rem);font-weight:900;color:#fff;letter-spacing:2px;margin-bottom:0.5rem}
.tier-badge{display:inline-flex;align-items:center;gap:0.6rem;padding:0.6rem 1.2rem;border-radius:999px;background:#1a1a1a;border:2px solid var(--tier);font-family:'Orbitron',sans-serif;font-size:1.4rem;font-weight:800;color:var(--tier);text-transform:uppercase;letter-spacing:3px;margin:1rem 0;box-shadow:0 0 20px <?= htmlspecialchars($tier_color, ENT_QUOTES) ?>40}
.username{color:#888;font-size:1rem;font-family:'Rajdhani',sans-serif;letter-spacing:1px}
.card{background:var(--dd);border:1px solid #222;border-radius:12px;padding:1.5rem;margin-bottom:1rem;position:relative;overflow:hidden}
.card::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,transparent,var(--b),transparent)}
.card h2{font-family:'Orbitron',sans-serif;font-size:0.95rem;color:#fff;margin-bottom:1rem;letter-spacing:2px;text-transform:uppercase}
.metric{display:flex;justify-content:space-between;align-items:baseline;font-family:'Orbitron',sans-serif;font-size:2rem;font-weight:800;color:var(--b)}
.metric-label{font-size:0.75rem;color:#666;text-transform:uppercase;letter-spacing:1.5px}
.dims{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:0.75rem;margin-top:0.5rem}
.dim{padding:0.6rem;background:#0d0d0d;border-radius:6px;border-left:3px solid var(--b)}
.dim-name{font-size:0.7rem;color:#888;text-transform:uppercase;letter-spacing:1px;margin-bottom:0.25rem}
.dim-bar{height:6px;background:#222;border-radius:999px;overflow:hidden;margin:0.4rem 0 0.3rem}
.dim-fill{height:100%;background:var(--b);transition:width 0.4s}
.dim-val{font-family:'Orbitron',sans-serif;color:#fff;font-size:0.95rem}
.ach-count{color:var(--b);font-family:'Orbitron',sans-serif;letter-spacing:2px;font-size:0.95rem;margin:0 0 0.5rem}
.ach-cat{color:#888;text-transform:uppercase;letter-spacing:2px;font-size:0.72rem;margin:1.3rem 0 0.7rem;border-bottom:1px solid #222;padding-bottom:0.3rem}
.badge-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(92px,1fr));gap:1rem}
.badge{display:flex;flex-direction:column;align-items:center;text-align:center;gap:0.45rem}
.badge img{width:84px;height:84px;object-fit:contain;border-radius:12px}
.badge.locked img{filter:grayscale(1) brightness(0.35);opacity:0.5}
.badge.locked{opacity:0.75}
.badge-emoji{width:84px;height:84px;display:flex;align-items:center;justify-content:center;font-size:2.4rem;background:#141414;border-radius:12px;border:1px solid #222}
.badge-sealed{background:#1a0f2e;border:1px solid #7A00FF;box-shadow:0 0 16px #7A00FF55}
.badge.sealed .badge-name{color:#b388ff}
.badge.locked .badge-emoji{filter:grayscale(1);opacity:0.45}
.badge-name{font-size:0.7rem;color:#ccc;line-height:1.2}
.badge.locked .badge-name{color:#777}
.cta{text-align:center;margin-top:2rem;color:#777;font-size:0.9rem}
.cta a{color:var(--b);text-decoration:none}
.cta a:hover{text-decoration:underline}
.topnav{display:flex;justify-content:center;gap:1.25rem;flex-wrap:wrap;margin-bottom:1.75rem;font-family:'Rajdhani',sans-serif;font-size:0.85rem;letter-spacing:1px;text-transform:uppercase}
.topnav a{color:var(--b);text-decoration:none}
.topnav a:hover{text-decoration:underline}
.dash-btn{display:inline-flex;align-items:center;gap:0.5rem;margin-top:1.25rem;padding:0.85rem 1.7rem;background:linear-gradient(135deg,#00FFF7,#00BFFF);color:#06121a;font-family:'Orbitron',sans-serif;font-weight:700;font-size:0.8rem;letter-spacing:1.5px;text-transform:uppercase;text-decoration:none;border-radius:8px;box-shadow:0 0 18px rgba(0,255,247,.4)}
.dash-btn:hover{box-shadow:0 0 26px rgba(0,255,247,.6)}
.error{text-align:center;padding:3rem 1rem;color:#ccc}
.error h2{font-family:'Orbitron',sans-serif;color:#fff;margin-bottom:1rem;letter-spacing:1px}
.error code{background:#1a1a1a;padding:0.2rem 0.5rem;border-radius:4px;color:var(--b);font-family:'Courier New',monospace}
</style>
</head>
<body>
<div class="wrap">

<nav class="topnav">
  <a href="https://offda9.com/">OD9 Home</a>
  <a href="https://offda9.com/dashboard/">Your Dashboard</a>
  <a href="https://discord.gg/spgmrXVMWq" target="_blank" rel="noopener">Discord</a>
</nav>

<?php if ($invalid_reason !== null): ?>
  <div class="error">
    <h2>Link unavailable</h2>
    <?php
      $msg = match ($invalid_reason) {
        'expired' => 'This personalized link has expired (24h max). Run <code>/me_link</code> in the OD9 Discord to get a fresh one.',
        'bad_signature', 'malformed_signature', 'malformed_user_id', 'malformed_timestamp' =>
          'This URL doesn\'t look right. Run <code>/me_link</code> in the OD9 Discord to get a valid personal link.',
        'no_member_record' => 'No member record found for this Discord ID. Make sure you\'ve joined the OD9 server, then run <code>/me_link</code> again.',
        'server_misconfigured' => 'The member-area service is temporarily unavailable. Check back soon.',
        default => 'This link couldn\'t be validated. Run <code>/me_link</code> in Discord.',
      };
      echo "<p>" . $msg . "</p>";
    ?>
  </div>
<?php elseif ($db_unavailable): ?>
  <div class="error">
    <h2>Data unavailable</h2>
    <p>We couldn't reach the progression database right now. Try again in a minute.</p>
  </div>
<?php else: ?>
  <header>
    <h1>YOUR OD9 PROGRESS</h1>
    <div class="username">@<?= htmlspecialchars($username ?? 'unknown', ENT_QUOTES) ?></div>
    <div class="tier-badge"><?= htmlspecialchars($tier_name ?? 'Observer', ENT_QUOTES) ?></div>
  </header>

  <?php if ($credits !== null): ?>
  <div class="card">
    <h2>ASCEND Credits</h2>
    <div class="metric">
      <span><?= number_format($credits) ?></span>
      <span class="metric-label">Total earned</span>
    </div>
  </div>
  <?php endif; ?>

  <?php if ($dimensions !== null): ?>
  <div class="card">
    <h2>5-Dimensional Growth</h2>
    <div class="dims">
      <?php
        $DIM_LABELS = [
          'knowledge_score'     => 'Knowledge',
          'resource_score'      => 'Resource',
          'community_score'     => 'Community',
          'consciousness_score' => 'Consciousness',
          'system_score'        => 'System',
        ];
        foreach ($DIM_LABELS as $key => $label):
          $val = (int)($dimensions[$key] ?? 0);
          $pct = max(0, min(100, $val));
      ?>
        <div class="dim">
          <div class="dim-name"><?= $label ?></div>
          <div class="dim-bar"><div class="dim-fill" style="width:<?= $pct ?>%"></div></div>
          <div class="dim-val"><?= $val ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <?php if (!empty($achievements)):
      $by_cat = []; $earned_n = 0; $total_n = 0;
      foreach ($achievements as $a) {
          if ($a['is_hidden'] && !$a['earned']) continue;   // secret badges stay secret until earned
          $by_cat[$a['category']][] = $a;
          $total_n++; if ($a['earned']) $earned_n++;
      }
      $CAT_LABELS = ['progression'=>'Progression','content'=>'Content','community'=>'Community',
                     'contribution'=>'Contribution','projects'=>'Projects','governance'=>'Governance','special'=>'Special'];
  ?>
  <div class="card">
    <h2>Achievements</h2>
    <p class="ach-count"><?= $earned_n ?> / <?= $total_n ?> unlocked</p>
    <?php foreach ($by_cat as $cat => $list): ?>
      <div class="ach-cat"><?= htmlspecialchars($CAT_LABELS[$cat] ?? ucfirst($cat)) ?></div>
      <div class="badge-grid">
        <?php foreach ($list as $a):
            $earned = (bool)$a['earned'];
            $sealed = $earned && empty($a['revealed']);   // earned but unrevealed — the Firm ceremony
            $img = ($a['badge_image'] ?? '') !== '' ? '/assets/badges/' . rawurlencode($a['badge_image']) : '';
            $cls = $sealed ? 'sealed' : ($earned ? 'earned' : 'locked');
        ?>
          <div class="badge <?= $cls ?>"
               title="<?= htmlspecialchars($sealed ? $a['name'] . ' — sealed; run /firm reveal in Discord' : ($a['name'] . ($earned ? '' : ' — locked')), ENT_QUOTES) ?>">
            <?php if ($sealed): ?>
              <div class="badge-emoji badge-sealed">&#x1F381;</div>
            <?php elseif ($img): ?>
              <img src="<?= htmlspecialchars($img, ENT_QUOTES) ?>" alt="<?= htmlspecialchars($a['name'], ENT_QUOTES) ?>"
                   loading="lazy" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
              <div class="badge-emoji" style="display:none"><?= htmlspecialchars($a['icon']) ?></div>
            <?php else: ?>
              <div class="badge-emoji"><?= htmlspecialchars($a['icon']) ?></div>
            <?php endif; ?>
            <div class="badge-name"><?= htmlspecialchars($sealed ? 'Sealed — reveal in Discord' : $a['name']) ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <div class="card">
    <h2>Tier Demonstrations</h2>
    <p style="color:#bbb;line-height:1.6;font-size:0.95rem">For the full breakdown of what milestones you've shipped + what's needed for the next tier (Architect: 1 contribution project; Pioneer: facilitate a Think Tank + complete a mentorship; Benefactor: Patreon supporter + governance proposal), run <code style="background:#1a1a1a;padding:0.15rem 0.5rem;border-radius:4px;color:var(--b);font-family:'Courier New',monospace">/my_demonstrations</code> in the OD9 Discord.</p>
  </div>

  <div class="cta">
    <a href="https://offda9.com/dashboard/" class="dash-btn">Open Your Dashboard &rarr;</a>
    <p style="margin-top:1.25rem">Want a richer view? Run <code style="background:#1a1a1a;padding:0.15rem 0.5rem;border-radius:4px;color:var(--b);font-family:'Courier New',monospace">/profile</code> in <a href="https://discord.gg/spgmrXVMWq" target="_blank" rel="noopener">the OD9 Discord</a> for the full ASCEND profile.</p>
  </div>
<?php endif; ?>

</div>
</body>
</html>
