<?php
/**
 * Tier Advancement - Congratulations on leveling up in ASCEND
 * 
 * Variables: $member_name, $old_tier, $new_tier, $total_credits, $next_tier, $next_credits
 */
include __DIR__ . '/partials/email-header.php';
$name = $member_name ?? 'Member';
$tierColors = ['observer'=>'observer','theorist'=>'theorist','architect'=>'architect','pioneer'=>'pioneer','benefactor'=>'benefactor'];
$tierClass = $tierColors[strtolower($new_tier ?? '')] ?? 'observer';
$descriptions = [
    'Theorist' => 'You now understand the root systems that maintain coordination failure. Your job: teach what you\'ve learned to Observers, creating a shift from learning to explaining.',
    'Architect' => 'You stop analyzing and start building. Join a Research Cell, complete milestone-based projects, and create infrastructure that outlives any individual contributor.',
    'Pioneer' => 'You\'re coordinating the coordinators. Success is measured by Architect project completion rates, community health, and sustainable infrastructure.',
    'Benefactor' => 'You\'re investing in long-term infrastructure that makes Type I advancement possible. Where Pioneers coordinate, you sustain.',
];
$desc = $descriptions[$new_tier ?? ''] ?? 'Your understanding has deepened. Keep pushing forward.';
?>
<div style="text-align:center;padding:20px 0">
<p style="font-size:13px;color:#666;letter-spacing:2px;text-transform:uppercase;margin:0 0 10px">ASCEND Protocol</p>
<h2 style="font-size:26px;margin:0 0 15px">Tier Advancement</h2>
<p>
<span class="tier-badge tier-<?= htmlspecialchars($tierColors[strtolower($old_tier ?? '')] ?? 'observer') ?>"><?= htmlspecialchars($old_tier ?? 'Observer') ?></span>
<span style="color:#00BFFF;font-size:20px;margin:0 15px">&rarr;</span>
<span class="tier-badge tier-<?= htmlspecialchars($tierClass) ?>"><?= htmlspecialchars($new_tier ?? '') ?></span>
</p>
</div>

<p>Congratulations, <?= htmlspecialchars($name) ?>. You've earned it.</p>

<p><?= htmlspecialchars($desc) ?></p>

<div class="card" style="text-align:center">
<div class="stat"><span class="stat-num"><?= (int)($total_credits ?? 0) ?></span><span class="stat-label">Total Credits</span></div>
<?php if (!empty($next_tier)): ?>
<div class="stat"><span class="stat-num"><?= (int)($next_credits ?? 0) ?></span><span class="stat-label">Next: <?= htmlspecialchars($next_tier) ?></span></div>
<?php endif; ?>
</div>

<p>This advancement wasn't given to you. You demonstrated genuine understanding through analysis, contribution, and engagement. That's what separates ASCEND from every other progression system.</p>

<p style="text-align:center;margin:25px 0"><a href="https://discord.gg/spgmrXVMWq" class="btn">Continue in Discord</a></p>
<?php include __DIR__ . '/partials/email-footer.php'; ?>
