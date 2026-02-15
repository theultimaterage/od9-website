<?php
/**
 * Contribution Recognized - Community acknowledged your work
 * 
 * Variables: $member_name, $contribution_type, $contribution_title,
 *            $credits_earned, $total_credits, $current_tier, $recognized_by,
 *            $dimension (knowledge/community/teaching/technical/creative)
 */
include __DIR__ . '/partials/email-header.php';
$name = $member_name ?? 'Member';
$dimensionLabels = [
    'knowledge' => 'Knowledge Dimension',
    'community' => 'Community Building',
    'teaching' => 'Teaching & Mentorship',
    'technical' => 'Technical Infrastructure',
    'creative' => 'Creative Expression',
];
$dimLabel = $dimensionLabels[$dimension ?? ''] ?? 'Your Growth';
?>
<div style="text-align:center;padding:10px 0">
<p style="font-size:12px;color:#666;letter-spacing:2px;text-transform:uppercase;margin:0 0 5px">Contribution Recognized</p>
<h2 style="margin:0 0 5px">+<?= (int)($credits_earned ?? 0) ?> Credits</h2>
<p style="color:#666;font-size:14px;margin:0"><?= htmlspecialchars($dimLabel) ?></p>
</div>

<p>Hey <?= htmlspecialchars($name) ?>,</p>

<p>Your contribution has been recognized by the community:</p>

<div class="card" style="border-left:4px solid #00BFFF">
<p style="margin:0 0 8px"><strong style="color:#fff"><?= htmlspecialchars(ucfirst($contribution_type ?? 'Contribution')) ?></strong></p>
<?php if (!empty($contribution_title)): ?>
<p style="margin:0 0 8px;color:#fff">"<?= htmlspecialchars($contribution_title) ?>"</p>
<?php endif; ?>
<?php if (!empty($recognized_by)): ?>
<p style="margin:0;color:#666;font-size:13px">Recognized by <?= htmlspecialchars($recognized_by) ?></p>
<?php endif; ?>
</div>

<div class="card" style="text-align:center">
<div class="stat"><span class="stat-num"><?= (int)($total_credits ?? 0) ?></span><span class="stat-label">Total Credits</span></div>
<div class="stat"><span class="stat-num tier-badge tier-<?= htmlspecialchars(strtolower($current_tier ?? 'observer')) ?>"><?= htmlspecialchars($current_tier ?? 'Observer') ?></span><span class="stat-label">Current Tier</span></div>
</div>

<p>Every contribution moves the mission forward. Whether it's knowledge, community building, teaching, or creative work, your unique path through ASCEND matters.</p>

<p style="text-align:center;margin:25px 0"><a href="https://discord.gg/spgmrXVMWq" class="btn">View Your Profile</a></p>
<?php include __DIR__ . '/partials/email-footer.php'; ?>
