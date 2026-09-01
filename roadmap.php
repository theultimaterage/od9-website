<?php
/**
 * OD9 Live Roadmap — public read view of the v0.2 trigger system.
 *
 * Reads the bot's live SQLite roadmap_triggers directly (the od9_roadmap_triggers
 * MySQL mirror was retired 2026-06-28).
 *
 * URL params:
 *   ?id=T-COM-100   — single trigger detail view
 *   ?domain=COM     — filter list view to one domain
 *   (default)       — full list view, all domains
 */
declare(strict_types=1);

$configPath = __DIR__ . '/../config/database.php';
if (!file_exists($configPath)) {
    $configPath = __DIR__ . '/config/database.php';
}
require_once $configPath;

$DOMAINS = [
    'COM' => 'Community',
    'REV' => 'Revenue',
    'INF' => 'Infrastructure',
    'RES' => 'Research',
    'CON' => 'Content',
    'CRD' => 'Credibility',
];

$STATUS_COLORS = [
    'Active'     => 'gold',
    'Approved'   => 'blue',
    'Activated'  => 'green',
    'Superseded' => 'gray',
    'Retired'    => 'gray',
];



$detail_id = isset($_GET['id']) ? trim((string)$_GET['id']) : '';
$domain_filter = isset($_GET['domain']) ? strtoupper(trim((string)$_GET['domain'])) : '';
if ($domain_filter && !isset($DOMAINS[$domain_filter])) $domain_filter = '';

$detail_trigger = null;
$active = [];
$approved = [];
$activated = [];
$stats = [
    'active' => 0, 'approved' => 0, 'activated' => 0,
    'superseded' => 0, 'retired' => 0,
    'from_seed' => 0, 'from_community' => 0, 'from_founder' => 0,
    'total' => 0,
];

try {
    // Live SQLite read — the roadmap_triggers MySQL mirror was retired 2026-06-28.
    require_once __DIR__ . '/includes/od9_read.php';
    if (!od9_read_healthy()) throw new RuntimeException('live roadmap DB unavailable');

    // The domain filter used to be string-concatenated into the SQL. Through the
    // read seam the caller cannot compose SQL at all, so each shape is its own
    // registered query — a whitelist the caller can assemble is not a whitelist.
    if ($detail_id !== '') {
        $rows = od9_read('roadmap_detail', ['trigger_id' => $detail_id]) ?? [];
        $detail_trigger = $rows[0] ?? null;
    } else {
        $suffix = $domain_filter ? '_domain' : '';
        $args   = $domain_filter ? ['domain' => $domain_filter] : [];
        $active    = od9_read('roadmap_active' . $suffix, $args) ?? [];
        $approved  = od9_read('roadmap_approved' . $suffix, $args) ?? [];
        $activated = od9_read('roadmap_activated' . $suffix, $args) ?? [];
    }

    foreach (array_keys($STATUS_COLORS) as $status) {
        $r = od9_read('roadmap_count_by_status', ['status' => $status]);
        $stats[strtolower($status)] = (int)($r['n'] ?? 0);
    }
    $stats['total'] = array_sum([
        $stats['active'], $stats['approved'], $stats['activated'],
        $stats['superseded'], $stats['retired'],
    ]);
    foreach (['seed', 'community', 'founder'] as $src) {
        $r = od9_read('roadmap_count_by_source', ['source' => $src]);
        $stats['from_' . $src] = (int)($r['n'] ?? 0);
    }
} catch (Throwable $e) {
    if (!defined('ENVIRONMENT') || ENVIRONMENT !== 'local') {
        error_log('[roadmap.php] connection failed: ' . $e->getMessage());
    }
}

$updated = gmdate('Y-m-d H:i') . ' UTC';
$current_page = 'roadmap';

$page_title       = $detail_trigger
    ? $detail_trigger['trigger_id'] . ': ' . $detail_trigger['title'] . ' — OD9 Roadmap'
    : 'OD9 Roadmap — Live Trigger Registry';
$page_description = 'Live community-governed roadmap for OD9 — active triggers, queued approvals, and recently activated milestones, maintained by member proposals + consciousness-weighted voting.';
$page_slug        = 'roadmap.php';

function trigger_card(array $t, array $DOMAINS): string {
    $tid = htmlspecialchars($t['trigger_id']);
    $title = htmlspecialchars($t['title']);
    $domain = htmlspecialchars($t['domain']);
    $domain_label = htmlspecialchars($DOMAINS[$t['domain']] ?? $t['domain']);
    $cond = htmlspecialchars(mb_strimwidth((string)$t['condition_text'], 0, 200, '…'));
    $status = htmlspecialchars($t['status']);
    return <<<HTML
<a href="roadmap.php?id={$tid}" class="trigger-row">
  <div class="trigger-meta">
    <span class="domain-badge dom-{$domain}">{$domain}</span>
    <span class="trigger-id">{$tid}</span>
    <span class="trigger-status status-{$status}">{$status}</span>
  </div>
  <div class="trigger-title">{$title}</div>
  <div class="trigger-cond">{$cond}</div>
</a>
HTML;
}

function detail_block(string $label, ?string $value): string {
    if (!$value) return '';
    $safe = nl2br(htmlspecialchars($value));
    return "<div class='detail-block'><div class='detail-label'>{$label}</div><div class='detail-value'>{$safe}</div></div>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include __DIR__ . '/includes/head.php'; ?>
<link rel="stylesheet" href="/css/roadmap.css">
</head>
<body>

<?php include __DIR__ . '/includes/nav.php'; ?>

<div class="wrap">
  <header class="roadmap-hero">
    <p class="tag">Live community-governed roadmap</p>
    <h1><?= $detail_trigger ? 'Trigger Detail' : 'OD9 Roadmap' ?></h1>
    <p class="sub">
      Each trigger is a conditional move: when X benchmark is hit, here's
      what becomes possible, what it requires, and what it unlocks. Architect+
      members propose new triggers via Discord; Theorist+ members vote with
      consciousness-weighted ballots; approved triggers join the registry below.
      <strong style="color:var(--b)">No promises — only conditional moves.</strong>
      These triggers build coordination capacity aimed at the north stars on the
      <a href="progress.php#filter" style="color:var(--b)">State of the Filter scorecard</a> —
      what a civilization that survives actually achieves.
    </p>
  </header>

<?php if ($detail_trigger): ?>
  <div class="detail-wrap">
    <a href="roadmap.php" class="detail-back">← All triggers</a>
    <div class="detail-id">
      <span class="domain-badge dom-<?= htmlspecialchars($detail_trigger['domain']) ?>"><?= htmlspecialchars($detail_trigger['domain']) ?></span>
      <?= htmlspecialchars($detail_trigger['trigger_id']) ?>
      <span class="trigger-status status-<?= htmlspecialchars($detail_trigger['status']) ?>" style="float:right"><?= htmlspecialchars($detail_trigger['status']) ?></span>
    </div>
    <div class="detail-title"><?= htmlspecialchars($detail_trigger['title']) ?></div>
    <div class="detail-meta-row">
      <div class="detail-meta-item"><strong>Domain:</strong> <?= htmlspecialchars($DOMAINS[$detail_trigger['domain']] ?? $detail_trigger['domain']) ?></div>
      <div class="detail-meta-item"><strong>Source:</strong> <?= htmlspecialchars($detail_trigger['source']) ?></div>
      <?php if ($detail_trigger['proposed_at']): ?>
        <div class="detail-meta-item"><strong>Proposed:</strong> <?= htmlspecialchars(date('Y-m-d', strtotime($detail_trigger['proposed_at']))) ?></div>
      <?php endif; ?>
      <?php if ($detail_trigger['activated_at']): ?>
        <div class="detail-meta-item"><strong>Activated:</strong> <?= htmlspecialchars(date('Y-m-d', strtotime($detail_trigger['activated_at']))) ?></div>
      <?php endif; ?>
    </div>

    <?= detail_block('Condition (when this fires)', $detail_trigger['condition_text']) ?>
    <?= detail_block('Action (what becomes possible)', $detail_trigger['action_text']) ?>
    <?= detail_block('Effectiveness rationale', $detail_trigger['effectiveness_rationale']) ?>
    <?= detail_block('Prerequisites', $detail_trigger['prerequisites']) ?>
    <?= detail_block('Unlocks (downstream triggers)', $detail_trigger['unlocks']) ?>
    <?= detail_block('Risk if skipped', $detail_trigger['risk_if_skipped']) ?>
    <?= detail_block('Activation retrospective', $detail_trigger['retro_text']) ?>
    <?php if ($detail_trigger['superseded_by']): ?>
      <?= detail_block('Superseded by', $detail_trigger['superseded_by']) ?>
    <?php endif; ?>
    <?php if ($detail_trigger['retired_reason']): ?>
      <?= detail_block('Retired reason', $detail_trigger['retired_reason']) ?>
    <?php endif; ?>
  </div>

<?php else: ?>

  <div class="stats">
    <div class="stat-card"><div class="stat-num gold"><?= (int)$stats['active'] ?></div><div class="stat-label">Active</div></div>
    <div class="stat-card"><div class="stat-num blue"><?= (int)$stats['approved'] ?></div><div class="stat-label">Approved</div></div>
    <div class="stat-card"><div class="stat-num green"><?= (int)$stats['activated'] ?></div><div class="stat-label">Activated</div></div>
    <div class="stat-card"><div class="stat-num gray"><?= (int)$stats['superseded'] + (int)$stats['retired'] ?></div><div class="stat-label">Retired/Superseded</div></div>
    <div class="stat-card"><div class="stat-num"><?= (int)$stats['total'] ?></div><div class="stat-label">Total triggers</div></div>
  </div>

  <div class="filter-chips">
    <a href="roadmap.php" class="chip <?= $domain_filter === '' ? 'active' : '' ?>">All</a>
    <?php foreach ($DOMAINS as $code => $label): ?>
      <a href="roadmap.php?domain=<?= htmlspecialchars($code) ?>" class="chip <?= $domain_filter === $code ? 'active' : '' ?>"><?= htmlspecialchars($code) ?> · <?= htmlspecialchars($label) ?></a>
    <?php endforeach; ?>
  </div>

  <div class="section">
    <h2 class="section-title">🟧 Active <span class="count">currently working toward (<?= count($active) ?>)</span></h2>
    <?php if (empty($active)): ?>
      <div class="empty-section">No active triggers in this view.</div>
    <?php else: ?>
      <?php foreach ($active as $t) echo trigger_card($t, $DOMAINS); ?>
    <?php endif; ?>
  </div>

  <div class="section">
    <h2 class="section-title">✅ Recently Activated <span class="count">last 20 (<?= count($activated) ?>)</span></h2>
    <?php if (empty($activated)): ?>
      <div class="empty-section">No activated triggers in this view yet.</div>
    <?php else: ?>
      <?php foreach ($activated as $t) echo trigger_card($t, $DOMAINS); ?>
    <?php endif; ?>
  </div>

  <div class="section">
    <h2 class="section-title">🟦 Approved Queue <span class="count">approved + waiting (<?= count($approved) ?>)</span></h2>
    <?php if (empty($approved)): ?>
      <div class="empty-section">No queued approvals in this view.</div>
    <?php else: ?>
      <?php foreach ($approved as $t) echo trigger_card($t, $DOMAINS); ?>
    <?php endif; ?>
  </div>

  <div class="section" style="text-align:center;background:var(--dd);border:1px solid #222;border-radius:8px;padding:1.5rem;margin-top:1.5rem">
    <h3 style="font-family:'Orbitron',sans-serif;color:#fff;letter-spacing:2px;font-size:1rem;margin-bottom:0.75rem">Want to suggest a trigger?</h3>
    <p style="color:#888;font-size:0.9rem;line-height:1.55;max-width:540px;margin:0 auto 1rem">
      Roadmap proposals come from members who've earned <strong style="color:var(--b)">Architect tier</strong> in the OD9 Discord —
      typically 30-60 days of substantive participation. Until you reach Architect, your contribution shapes proposals through
      comments, endorsements, and votes (Theorist tier).
    </p>
    <a href="https://discord.gg/spgmrXVMWq" class="chip" style="border-color:var(--b);color:var(--b);padding:0.6rem 1.5rem;font-size:0.9rem">Join Discord →</a>
  </div>
<?php endif; ?>

  <p class="note">
    Sources: Seed <?= (int)$stats['from_seed'] ?> · Community <?= (int)$stats['from_community'] ?> · Founder <?= (int)$stats['from_founder'] ?>
    · Updated <?= $updated ?>
  </p>

  <div class="bottom-links">
    <a href="index.php">Home</a>
    <a href="progress.php">Progress</a>
    <a href="founders.php">Founding ledger</a>
    <a href="why-patreon.php">Why Patreon</a>
    <a href="https://discord.gg/spgmrXVMWq" target="_blank">Discord</a>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
