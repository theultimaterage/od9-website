<?php
/**
 * Content Drop - New content/music release notification
 * 
 * Variables: $member_name, $content_title, $content_type (music/video/article/resource),
 *            $content_url, $content_description, $credits_available, $artist_name
 */
include __DIR__ . '/partials/email-header.php';
$name = $member_name ?? 'Member';
$typeIcons = ['music' => '&#127925;', 'video' => '&#127909;', 'article' => '&#128196;', 'resource' => '&#128218;'];
$icon = $typeIcons[$content_type ?? 'resource'] ?? '&#9889;';
?>
<div style="text-align:center;padding:10px 0">
<p style="font-size:36px;margin:0"><?= $icon ?></p>
<p style="font-size:12px;color:#666;letter-spacing:2px;text-transform:uppercase;margin:10px 0 5px">New <?= htmlspecialchars(ucfirst($content_type ?? 'Content')) ?> Drop</p>
</div>

<h2 style="text-align:center"><?= htmlspecialchars($content_title ?? 'New Release') ?></h2>

<?php if (!empty($artist_name)): ?>
<p style="text-align:center;color:#666;margin-top:-10px">by <?= htmlspecialchars($artist_name) ?></p>
<?php endif; ?>

<?php if (!empty($content_description)): ?>
<div class="card">
<p style="margin:0"><?= htmlspecialchars($content_description) ?></p>
</div>
<?php endif; ?>

<?php if (!empty($credits_available)): ?>
<div class="card" style="text-align:center;background:rgba(0,191,255,0.05)">
<p style="color:#00BFFF;font-weight:bold;margin:0 0 5px">+<?= (int)$credits_available ?> CREDITS</p>
<p style="font-size:13px;margin:0">Engage with this content and submit a reflection to earn credits</p>
</div>
<?php endif; ?>

<p>This content supports OD9's mission. Engage critically, submit your reflection, and earn credits toward advancement.</p>

<?php if (!empty($content_url)): ?>
<p style="text-align:center;margin:25px 0"><a href="<?= htmlspecialchars($content_url) ?>" class="btn"><?= htmlspecialchars(ucfirst($content_type ?? 'content')) === 'Music' ? 'Listen Now' : 'Check It Out' ?></a></p>
<?php endif; ?>
<?php include __DIR__ . '/partials/email-footer.php'; ?>
