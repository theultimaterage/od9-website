<?php
/**
 * OD9 Progress Dashboard - Public-facing receipts
 *
 * Live counters: Discord members, mailing list subscribers, founding patrons,
 * approximate MRR band. Refreshes on each request (no aggressive caching since
 * the DB queries are cheap). Visible at https://offda9.com/progress.php
 *
 * Built for the May 2026 Founding Patron campaign — "show the math" to
 * convert visitors into supporters by making the climb tangible.
 */

declare(strict_types=1);

$configPath = __DIR__ . '/../config/database.php';
if (!file_exists($configPath)) {
    $configPath = __DIR__ . '/config/database.php';
}
require_once $configPath;

const FOUNDING_CAP = 25;
const MRR_TARGET = 200;
const TIER_PRICES_CENTS = [
    'theorist'   => 500,
    'architect'  => 1500,
    'pioneer'    => 3000,
    'benefactor' => 5000,
    'founding'   => 10000,
];

function safe_count(PDO $pdo, string $sql, array $params = []): int {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    } catch (Throwable $e) {
        if (!defined('ENVIRONMENT') || ENVIRONMENT !== 'local') error_log('[progress.php] count query failed: ' . $e->getMessage());
        return 0;
    }
}

try {
    $pdo = getDatabaseConnection();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $discord_members = safe_count($pdo, "SELECT COUNT(*) FROM od9_members");
    $email_subscribers = safe_count(
        $pdo,
        "SELECT COUNT(*) FROM email_signups WHERE is_verified = 1 AND email_opt_in = 1"
    );
    $founding_filled = safe_count(
        $pdo,
        "SELECT COUNT(*) FROM od9_members WHERE LOWER(patreon_tier) = 'founding' AND is_patreon_supporter = 1"
    );

    // Compute approximate MRR by summing tier prices for active supporters
    $stmt = $pdo->query(
        "SELECT LOWER(patreon_tier) AS tier, COUNT(*) AS n
         FROM od9_members
         WHERE is_patreon_supporter = 1 AND patreon_tier IS NOT NULL AND patreon_tier != ''
         GROUP BY LOWER(patreon_tier)"
    );
    $mrr_cents = 0;
    foreach ($stmt as $row) {
        $price = TIER_PRICES_CENTS[$row['tier']] ?? 0;
        $mrr_cents += $price * (int)$row['n'];
    }
} catch (Throwable $e) {
    if (!defined('ENVIRONMENT') || ENVIRONMENT !== 'local') error_log('[progress.php] DB error: ' . $e->getMessage());
    $discord_members = $email_subscribers = $founding_filled = 0;
    $mrr_cents = 0;
}

$founding_remaining = max(0, FOUNDING_CAP - $founding_filled);
$mrr_dollars = (int)floor($mrr_cents / 100);
$mrr_pct = min(100, (int)round(($mrr_dollars / MRR_TARGET) * 100));
$updated = gmdate('Y-m-d H:i') . ' UTC';
?>
<?php
$page_title = 'OD9 Progress — Live Receipts';
$page_description = 'Live progress dashboard for the OD9 movement. Discord members, mailing list, founding patrons, and monthly recurring revenue toward the $200 annual-billing milestone.';
$page_slug = 'progress.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include __DIR__ . '/includes/head.php'; ?>
<style>
:root{--b:#00BFFF;--eb:#00A0FF;--rg:#B76E79;--gold:#FFD700;--d:#0A0A0A;--dd:#111;--c:#C0C0C0;--g:0 0 20px rgba(0,191,255,.45)}
*{margin:0;padding:0;box-sizing:border-box}
body{background:var(--d);color:var(--c);font-family:'Exo 2',sans-serif;min-height:100vh;padding:2rem 1rem 4rem}
.wrap{max-width:840px;margin:0 auto}
header{text-align:center;margin-bottom:3rem}
.logo{display:inline-block;margin-bottom:1.25rem}
.logo img{height:60px;filter:drop-shadow(var(--g))}
h1{font-family:'Orbitron',sans-serif;font-size:clamp(1.8rem,5vw,2.6rem);font-weight:900;color:#fff;letter-spacing:3px;margin-bottom:0.5rem;text-shadow:var(--g)}
.tag{font-family:'Rajdhani',sans-serif;color:var(--b);letter-spacing:4px;font-size:0.95rem;text-transform:uppercase;margin-bottom:0.75rem}
.sub{color:#888;font-size:1rem;line-height:1.6;max-width:560px;margin:0 auto}
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem;margin-bottom:2.5rem}
.card{background:var(--dd);border:1px solid #222;border-radius:12px;padding:1.5rem 1.25rem;position:relative;overflow:hidden}
.card::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,transparent,var(--b),transparent)}
.card.gold::before{background:linear-gradient(90deg,transparent,var(--gold),transparent)}
.card.rose::before{background:linear-gradient(90deg,transparent,var(--rg),transparent)}
.card-num{font-family:'Orbitron',sans-serif;font-size:2.4rem;font-weight:800;color:var(--b);line-height:1.05}
.card.gold .card-num{color:var(--gold)}
.card.rose .card-num{color:var(--rg)}
.card-label{font-size:0.75rem;color:#666;text-transform:uppercase;letter-spacing:1.5px;margin-top:0.4rem}
.card-sub{font-size:0.85rem;color:#999;margin-top:0.5rem;line-height:1.4}
.mrr-section{background:var(--dd);border:1px solid #222;border-radius:12px;padding:2rem 1.5rem;margin-bottom:2.5rem;position:relative;overflow:hidden}
.mrr-section::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,var(--b),var(--gold))}
.mrr-title{font-family:'Orbitron',sans-serif;font-size:1.2rem;color:#fff;margin-bottom:0.5rem;letter-spacing:1px}
.mrr-tag{color:#888;font-size:0.95rem;margin-bottom:1.5rem;line-height:1.55}
.mrr-bar{height:14px;background:#1a1a1a;border-radius:999px;overflow:hidden;margin-bottom:0.75rem;border:1px solid #2a2a2a}
.mrr-fill{height:100%;background:linear-gradient(90deg,var(--b),var(--gold));transition:width 0.6s ease;box-shadow:0 0 12px rgba(0,191,255,0.5)}
.mrr-stats{display:flex;justify-content:space-between;align-items:baseline;font-family:'Orbitron',sans-serif;font-size:1rem;color:#bbb}
.mrr-stats strong{color:var(--b);font-size:1.3rem}
.cta-row{display:flex;flex-wrap:wrap;gap:1rem;justify-content:center;margin:2.5rem 0 1.5rem}
.btn{padding:0.9rem 2rem;border-radius:6px;font-family:'Rajdhani',sans-serif;font-weight:700;font-size:1rem;text-transform:uppercase;letter-spacing:2px;text-decoration:none;transition:all 0.3s}
.btn-primary{background:linear-gradient(135deg,var(--b),var(--eb));color:var(--d);box-shadow:var(--g)}
.btn-primary:hover{transform:translateY(-2px)}
.btn-secondary{background:transparent;color:var(--b);border:1px solid var(--b)}
.btn-secondary:hover{background:rgba(0,191,255,0.1)}
.note{text-align:center;color:#555;font-size:0.8rem;margin-top:2rem;font-family:'Orbitron',sans-serif;letter-spacing:1px}
.note a{color:var(--b);text-decoration:none}
.bottom-links{text-align:center;margin-top:2rem}
.bottom-links a{color:#666;text-decoration:none;font-size:0.85rem;margin:0 0.75rem}
.bottom-links a:hover{color:var(--b)}
@media(max-width:600px){h1{letter-spacing:1.5px}.mrr-section{padding:1.5rem 1.25rem}}
</style>
</head>
<body>
<div class="wrap">
  <header>
    <a href="index.php" class="logo"><img src="images/logos/od9-logo.png" alt="OD9"></a>
    <p class="tag">Live receipts</p>
    <h1>OD9 by the Numbers</h1>
    <p class="sub">
      Public progress. Updated every page load. The whole point of OD9 is
      coordination infrastructure that doesn't lie about itself, so every
      number on this page is a live count from the same database the bot
      and website use.
    </p>
  </header>

  <div class="grid">
    <div class="card">
      <div class="card-num"><?= number_format($discord_members) ?></div>
      <div class="card-label">Discord Members</div>
      <div class="card-sub">Active across all five tiers</div>
    </div>
    <div class="card">
      <div class="card-num"><?= number_format($email_subscribers) ?></div>
      <div class="card-label">Mailing list</div>
      <div class="card-sub">Verified + opted in</div>
    </div>
    <div class="card rose">
      <div class="card-num"><?= $founding_filled ?>/<?= FOUNDING_CAP ?></div>
      <div class="card-label">Founding Patrons</div>
      <div class="card-sub">
        <?= $founding_remaining ?> lifetime slots remaining.
        <a href="founders.php" style="color:var(--rg)">See the ledger →</a>
      </div>
    </div>
    <div class="card gold">
      <div class="card-num">$<?= number_format($mrr_dollars) ?></div>
      <div class="card-label">Monthly recurring</div>
      <div class="card-sub">Across <?= $founding_filled ? 'all tiers' : 'all tiers (none yet)' ?></div>
    </div>
  </div>

  <div class="mrr-section">
    <h2 class="mrr-title">$<?= MRR_TARGET ?>/mo unlocks annual billing</h2>
    <p class="mrr-tag">
      Patreon's annual-membership feature requires $<?= MRR_TARGET ?>/mo for
      three consecutive months. We're pre-enrolled — Patreon flips the toggle
      automatically when we cross the threshold. Annual = 15% off for patrons,
      step-function in retention for OD9. Both sides win.
    </p>
    <div class="mrr-bar">
      <div class="mrr-fill" style="width: <?= $mrr_pct ?>%"></div>
    </div>
    <div class="mrr-stats">
      <span><strong>$<?= number_format($mrr_dollars) ?></strong> / $<?= MRR_TARGET ?> MRR</span>
      <span><?= $mrr_pct ?>%</span>
    </div>
  </div>

  <div class="cta-row">
    <a href="https://www.patreon.com/TheUltimateRage" class="btn btn-primary" target="_blank" rel="noopener">
      Become a Patron
    </a>
    <a href="support.php" class="btn btn-secondary">
      Why Support OD9?
    </a>
  </div>

  <p class="note">Updated <?= $updated ?> · Refresh for live numbers · <a href="founders.php">Founding ledger</a></p>

  <div class="bottom-links">
    <a href="index.php">Home</a>
    <a href="framework.php">Framework</a>
    <a href="tiers.php">Tiers</a>
    <a href="https://discord.gg/spgmrXVMWq" target="_blank">Discord</a>
    <a href="founders.php">Founding ledger</a>
  </div>
</div>
</body>
</html>
