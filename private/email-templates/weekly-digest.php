<?php
/**
 * Weekly Digest - Community progress update
 * 
 * Variables: $member_name, $week_label, $new_members, $total_members,
 *            $discussions_count, $credits_earned, $tier_advancements,
 *            $active_projects, $top_contributors (array), $upcoming_events (array)
 */
include __DIR__ . '/partials/email-header.php';
$name = $member_name ?? 'Member';
?>
<p style="font-size:12px;color:#666;letter-spacing:2px;text-transform:uppercase;margin:0 0 5px">Weekly Mission Update</p>
<h2><?= htmlspecialchars($week_label ?? 'This Week at OD9') ?></h2>

<p>Hey <?= htmlspecialchars($name) ?>, here's what the community accomplished this week in advancing toward Type I civilization.</p>

<!-- Stats Row -->
<div class="card" style="text-align:center">
<div class="stat"><span class="stat-num"><?= (int)($new_members ?? 0) ?></span><span class="stat-label">New Members</span></div>
<div class="stat"><span class="stat-num"><?= (int)($discussions_count ?? 0) ?></span><span class="stat-label">Discussions</span></div>
<div class="stat"><span class="stat-num"><?= number_format((int)($credits_earned ?? 0)) ?></span><span class="stat-label">Credits Earned</span></div>
<div class="stat"><span class="stat-num"><?= (int)($tier_advancements ?? 0) ?></span><span class="stat-label">Tier Advances</span></div>
</div>

<?php if (!empty($top_contributors)): ?>
<h3>Top Contributors</h3>
<?php foreach (array_slice($top_contributors, 0, 5) as $i => $c): ?>
<div class="card" style="padding:10px 20px;display:flex;justify-content:space-between">
<span style="color:#fff"><?= ($i+1) ?>. <?= htmlspecialchars($c['name'] ?? '') ?></span>
<span style="color:#00BFFF"><?= (int)($c['credits'] ?? 0) ?> credits</span>
</div>
<?php endforeach; endif; ?>

<?php if (!empty($active_projects)): ?>
<h3>Active Projects</h3>
<?php foreach (array_slice($active_projects, 0, 3) as $proj): ?>
<div class="card">
<p style="font-weight:bold;color:#fff;margin:0 0 5px"><?= htmlspecialchars($proj['name'] ?? '') ?></p>
<p style="color:#666;margin:0;font-size:13px"><?= htmlspecialchars($proj['status'] ?? '') ?> | <?= (int)($proj['contributors'] ?? 0) ?> contributors</p>
</div>
<?php endforeach; endif; ?>

<?php if (!empty($upcoming_events)): ?>
<h3>Coming Up</h3>
<?php foreach ($upcoming_events as $evt): ?>
<div class="card" style="padding:10px 20px">
<span style="color:#fff"><?= htmlspecialchars($evt['name'] ?? '') ?></span>
<span style="float:right;color:#666;font-size:13px"><?= htmlspecialchars($evt['date'] ?? '') ?></span>
</div>
<?php endforeach; endif; ?>

<hr class="divider">

<p style="text-align:center">
<strong style="color:#fff"><?= number_format((int)($total_members ?? 0)) ?> members</strong> working to pass the Great Filter.
</p>

<p style="text-align:center;margin:20px 0"><a href="https://discord.gg/spgmrXVMWq" class="btn">Jump Into Discord</a></p>
<?php include __DIR__ . '/partials/email-footer.php'; ?>
