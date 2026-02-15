<?php /** OD9 Password Reset */ include __DIR__ . '/partials/email-header.php'; ?>
<h2>Password Reset</h2>
<p>Hey <?= htmlspecialchars($customer_name ?? 'there') ?>,</p>
<p>We received a request to reset your password. Click below to set a new one:</p>
<p style="text-align:center;margin:25px 0"><a href="<?= htmlspecialchars($reset_link ?? '#') ?>" class="btn">Reset Password</a></p>
<p style="color:#666;font-size:13px">This link expires in <?= htmlspecialchars($expires_in ?? '1 hour') ?>.</p>
<p style="color:#666;font-size:13px">Didn't request this? Safely ignore this email.</p>
<div class="card" style="padding:10px 15px"><p style="color:#444;font-size:11px;margin:0;word-break:break-all"><?= htmlspecialchars($reset_link ?? '') ?></p></div>
<?php include __DIR__ . '/partials/email-footer.php'; ?>
