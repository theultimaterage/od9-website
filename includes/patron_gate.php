<?php
/**
 * OD9 Patron Gate Helper
 *
 * Inclusion module that exposes tier-checking primitives for content
 * gating. Designed to work with the dashboard/auth/callback.php session
 * (which sets $_SESSION['bot_user']['current_tier']) so any page that
 * wants to restrict by tier can do:
 *
 *     require_once __DIR__ . '/../includes/patron_gate.php';
 *     require_min_tier('theorist');
 *
 * Tier hierarchy: observer < theorist < architect < pioneer < benefactor < founding
 *
 * If the visitor isn't logged in OR their tier is below the minimum,
 * we render a styled "you need tier X to see this" page that drops them
 * into the Discord OAuth flow.
 *
 * Used for: gating selected library docs, NCZ deep-dives, manifesto
 * chapter PDFs, eventually the Synaptiq beta surface.
 */

declare(strict_types=1);

// We expect dashboard/auth/callback.php to have called session_start()
// with the secure cookie params; if no session is active, start one.
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'lifetime' => 604800,
        'path'     => '/',
        'secure'   => strpos(__DIR__, 'xampp') === false,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

const TIER_ORDER = [
    'observer'   => 0,
    'theorist'   => 1,
    'architect'  => 2,
    'pioneer'    => 3,
    'benefactor' => 4,
    'founding'   => 5,
];

const PATREON_TIERS = ['theorist', 'architect', 'pioneer', 'benefactor', 'founding'];

function current_user(): ?array {
    return $_SESSION['bot_user'] ?? null;
}

function current_tier(): string {
    $u = current_user();
    if (!$u) return 'guest';
    $t = strtolower((string)($u['current_tier'] ?? 'observer'));
    return $t !== '' ? $t : 'observer';
}

function is_patron(): bool {
    $u = current_user();
    if (!$u) return false;
    $pt = strtolower((string)($u['patreon_tier'] ?? ''));
    return $pt !== '' && in_array($pt, PATREON_TIERS, true);
}

function tier_at_least(string $required): bool {
    $cur = current_tier();
    if ($cur === 'guest') return false;
    $needed = TIER_ORDER[strtolower($required)] ?? 999;
    $have   = TIER_ORDER[$cur] ?? 0;
    return $have >= $needed;
}

function require_min_tier(string $required, ?string $whatYouNeed = null): void {
    if (tier_at_least($required)) return;
    render_gate_page($required, $whatYouNeed);
    exit;
}

function render_gate_page(string $required, ?string $whatYouNeed = null): void {
    $tier_label = ucfirst($required);
    $is_logged_in = current_user() !== null;
    $cur_label = $is_logged_in ? ucfirst(current_tier()) : 'Not logged in';
    $note = $whatYouNeed ?: "This content is gated to {$tier_label}+ supporters.";
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Tier required — <?= htmlspecialchars($tier_label, ENT_QUOTES) ?> | OD9</title>
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@400;600;700&family=Exo+2:wght@400;600&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
:root{--b:#00BFFF;--eb:#00A0FF;--rg:#B76E79;--gold:#FFD700;--d:#0A0A0A;--dd:#111;--c:#C0C0C0;--g:0 0 20px rgba(0,191,255,.45)}
*{margin:0;padding:0;box-sizing:border-box}
body{background:var(--d);color:var(--c);font-family:'Exo 2',sans-serif;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:2rem 1rem;line-height:1.7}
.gate{max-width:520px;text-align:center;background:var(--dd);border:1px solid #2a2a2a;border-radius:14px;padding:3rem 2rem;position:relative;overflow:hidden}
.gate::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,var(--b),var(--gold))}
.gate i{font-size:3rem;color:var(--gold);margin-bottom:1.25rem}
h1{font-family:'Orbitron',sans-serif;font-size:1.6rem;color:#fff;letter-spacing:1.5px;margin-bottom:0.75rem}
p{color:#bbb;font-size:1rem;margin-bottom:1.25rem}
.status{display:inline-block;padding:0.4rem 1rem;background:#0a0a0a;border:1px solid #333;border-radius:999px;font-size:0.8rem;color:#888;font-family:'Orbitron',sans-serif;letter-spacing:1px;text-transform:uppercase;margin-bottom:1.5rem}
.btn{display:inline-block;padding:0.9rem 2rem;border-radius:6px;font-family:'Rajdhani',sans-serif;font-weight:700;font-size:0.95rem;text-transform:uppercase;letter-spacing:2px;text-decoration:none;margin:0.4rem;transition:all 0.3s}
.btn-primary{background:linear-gradient(135deg,var(--b),var(--eb));color:var(--d);box-shadow:var(--g)}
.btn-secondary{background:transparent;color:var(--b);border:1px solid var(--b)}
.btn:hover{transform:translateY(-2px)}
.back{display:block;margin-top:1.5rem;color:#666;font-size:0.85rem;text-decoration:none}
.back:hover{color:var(--b)}
</style>
</head>
<body>
<div class="gate">
  <i class="fas fa-lock"></i>
  <h1><?= htmlspecialchars($tier_label, ENT_QUOTES) ?>+ Supporters Only</h1>
  <span class="status"><?= htmlspecialchars($cur_label, ENT_QUOTES) ?></span>
  <p><?= htmlspecialchars($note, ENT_QUOTES) ?></p>
  <p style="font-size:0.95rem;color:#888">
    <?php if (!$is_logged_in): ?>
      Log in with your Discord account to verify your tier.
    <?php else: ?>
      Upgrade your Patreon support to <?= htmlspecialchars($tier_label, ENT_QUOTES) ?> tier to unlock.
    <?php endif; ?>
  </p>
  <div>
    <?php if (!$is_logged_in): ?>
      <a href="/dashboard/auth/discord.php" class="btn btn-primary"><i class="fab fa-discord"></i>&nbsp; Log In with Discord</a>
    <?php endif; ?>
    <a href="/support.php" class="btn btn-secondary">View Tiers</a>
  </div>
  <a href="javascript:history.back()" class="back">← Go back</a>
</div>
</body>
</html>
    <?php
}
