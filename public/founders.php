<?php
/**
 * OD9 Founding Patron Ledger
 *
 * Public-facing roll of the 25 lifetime Founding Patrons. Reads
 * od9_members rows where patreon_tier='founding' and renders a
 * numbered slot list (1..25). Empty slots show "OPEN" with a CTA.
 *
 * Mounted at https://offda9.com/founders.php
 *
 * The visual hook is the running counter: passive psychological
 * pressure for visitors to take a slot before the page fills.
 */

declare(strict_types=1);

$configPath = __DIR__ . '/../config/database.php';
if (!file_exists($configPath)) {
    $configPath = __DIR__ . '/config/database.php';
}
require_once $configPath;

const FOUNDING_CAP = 25;
const PATREON_URL  = 'https://www.patreon.com/TheUltimateRage';

try {
    $pdo = getDatabaseConnection();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $pdo->prepare(
        "SELECT username, join_date
         FROM od9_members
         WHERE LOWER(patreon_tier) = 'founding'
           AND is_patreon_supporter = 1
         ORDER BY join_date ASC
         LIMIT :cap"
    );
    $stmt->bindValue(':cap', FOUNDING_CAP, PDO::PARAM_INT);
    $stmt->execute();
    $patrons = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    if (!defined('ENVIRONMENT') || ENVIRONMENT !== 'local') {
        error_log('[founders.php] DB error: ' . $e->getMessage());
    }
    $patrons = [];
}

$filled    = count($patrons);
$remaining = max(0, FOUNDING_CAP - $filled);
$pct       = (int) round(($filled / FOUNDING_CAP) * 100);

function escape(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function patron_display(array $row): string {
    $name = trim((string)($row['username'] ?? ''));
    return $name === '' ? 'Anonymous Founder' : escape($name);
}

function joined_display(array $row): string {
    $raw = $row['join_date'] ?? null;
    if (!$raw) return '';
    $ts = strtotime((string)$raw);
    if (!$ts) return '';
    return date('F Y', $ts);
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>OD9 Founding Patrons - The First 25</title>
<link rel="canonical" href="https://offda9.com/founders.php">
<meta name="description" content="The 25 lifetime Founding Patrons of OD9 - the public record of those who funded the infrastructure when it was still being built.">
<style>
  :root {
    --bg: #0a0a0e;
    --bg-card: #15151c;
    --fg: #e8e8ea;
    --fg-dim: #8a8a92;
    --rose-gold: #b76e79;
    --bronze: #cd7f32;
    --gold: #ffd700;
    --crimson: #dc143c;
    --border: #2a2a36;
  }
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", system-ui, sans-serif;
    background: var(--bg);
    color: var(--fg);
    line-height: 1.6;
    padding: 2rem 1rem 4rem;
  }
  .container { max-width: 720px; margin: 0 auto; }
  header { text-align: center; margin-bottom: 3rem; }
  h1 {
    font-size: clamp(1.8rem, 5vw, 2.8rem);
    font-weight: 700;
    background: linear-gradient(135deg, var(--rose-gold), var(--gold));
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
    margin-bottom: 0.5rem;
  }
  .subhead { color: var(--fg-dim); font-size: 1.05rem; max-width: 540px; margin: 0 auto 1.5rem; }
  .counter {
    display: inline-flex;
    flex-direction: column;
    align-items: center;
    padding: 1.5rem 2.5rem;
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 12px;
    margin: 1rem auto;
  }
  .counter-num {
    font-size: 3rem;
    font-weight: 800;
    color: var(--rose-gold);
    line-height: 1;
  }
  .counter-label { color: var(--fg-dim); font-size: 0.9rem; margin-top: 0.4rem; text-transform: uppercase; letter-spacing: 0.08em; }
  .progress {
    height: 6px;
    background: var(--border);
    border-radius: 999px;
    overflow: hidden;
    margin: 1.2rem 0;
  }
  .progress-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--rose-gold), var(--gold));
    transition: width 0.6s ease;
  }
  .ledger { list-style: none; counter-reset: slot; }
  .slot {
    counter-increment: slot;
    display: grid;
    grid-template-columns: 3rem 1fr auto;
    align-items: center;
    gap: 1rem;
    padding: 1rem 1.25rem;
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 8px;
    margin-bottom: 0.5rem;
  }
  .slot::before {
    content: counter(slot, decimal-leading-zero);
    font-family: ui-monospace, "SF Mono", Menlo, monospace;
    font-size: 1.1rem;
    color: var(--fg-dim);
    font-weight: 600;
  }
  .slot.filled { border-color: var(--rose-gold); }
  .slot.filled::before { color: var(--rose-gold); }
  .slot.open { opacity: 0.55; }
  .slot-name { font-weight: 600; }
  .slot-meta { color: var(--fg-dim); font-size: 0.85rem; }
  .slot.open .slot-name { color: var(--fg-dim); font-weight: 400; font-style: italic; }
  .cta-block {
    margin: 3rem 0 1rem;
    padding: 2rem;
    background: linear-gradient(135deg, rgba(183, 110, 121, 0.12), rgba(255, 215, 0, 0.06));
    border: 1px solid var(--rose-gold);
    border-radius: 12px;
    text-align: center;
  }
  .cta-block h2 { font-size: 1.5rem; margin-bottom: 0.6rem; }
  .cta-block p { color: var(--fg-dim); margin-bottom: 1.5rem; }
  .cta-btn {
    display: inline-block;
    padding: 1rem 2.5rem;
    background: var(--rose-gold);
    color: var(--bg);
    text-decoration: none;
    font-weight: 700;
    border-radius: 8px;
    transition: transform 0.15s ease;
  }
  .cta-btn:hover { transform: translateY(-2px); }
  footer { text-align: center; color: var(--fg-dim); font-size: 0.85rem; margin-top: 3rem; }
  footer a { color: var(--rose-gold); text-decoration: none; }
  .closed-banner {
    background: var(--crimson);
    color: white;
    padding: 1rem;
    border-radius: 8px;
    text-align: center;
    margin-bottom: 2rem;
    font-weight: 600;
  }
</style>
</head>
<body>
<div class="container">
  <header>
    <h1>The First 25</h1>
    <p class="subhead">
      The Founding Patrons of OD9. A permanent record of those who funded
      the infrastructure to push humanity past the Great Filter while it
      was still being built.
    </p>
    <div class="counter">
      <span class="counter-num"><?= $remaining ?></span>
      <span class="counter-label">Lifetime slots remaining</span>
    </div>
    <div class="progress" aria-label="Slots filled">
      <div class="progress-fill" style="width: <?= $pct ?>%"></div>
    </div>
  </header>

  <?php if ($remaining === 0): ?>
    <div class="closed-banner">
      All 25 Founding Patron slots are filled. This tier is now closed permanently.
    </div>
  <?php endif; ?>

  <ol class="ledger">
    <?php for ($i = 0; $i < FOUNDING_CAP; $i++): ?>
      <?php if (isset($patrons[$i])): ?>
        <li class="slot filled">
          <div>
            <div class="slot-name"><?= patron_display($patrons[$i]) ?></div>
            <div class="slot-meta">Founded <?= joined_display($patrons[$i]) ?></div>
          </div>
          <div class="slot-meta">FOUNDING PATRON</div>
        </li>
      <?php else: ?>
        <li class="slot open">
          <div>
            <div class="slot-name">Open slot</div>
            <div class="slot-meta">Take this seat</div>
          </div>
          <div class="slot-meta">$100 / mo</div>
        </li>
      <?php endif; ?>
    <?php endfor; ?>
  </ol>

  <?php if ($remaining > 0): ?>
    <div class="cta-block">
      <h2>Take a slot. Lock it forever.</h2>
      <p>
        Once all 25 are filled, this tier closes. No exceptions.
        Your name stays on this page as long as offda9.com exists.
      </p>
      <a href="<?= PATREON_URL ?>" class="cta-btn" target="_blank" rel="noopener">
        Become a Founding Patron
      </a>
    </div>
  <?php endif; ?>

  <footer>
    <p>OD9 - Off Da Nine - Coordination infrastructure for Type I civilization.</p>
    <p style="margin-top: 0.5rem;">
      <a href="/">Home</a> &middot;
      <a href="/library.php">Library</a> &middot;
      <a href="<?= PATREON_URL ?>" target="_blank" rel="noopener">Patreon</a>
    </p>
  </footer>
</div>
</body>
</html>
