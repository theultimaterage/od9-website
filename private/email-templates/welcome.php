<?php
/** Welcome Email - Sent when a new user signs up */
include __DIR__ . '/partials/email-header.php';
$name = $customer_name ?? 'there';
$brand = $brand_name ?? $_brand;
?>
<h2>Welcome to <?= htmlspecialchars($brand) ?>!</h2>
<p>Hey <?= htmlspecialchars($name) ?>, thanks for signing up! We're thrilled to have you.</p>

<div style="background:#222;border-radius:8px;padding:20px;margin:20px 0">
<h3 style="color:<?= htmlspecialchars($_primary) ?>;margin:0 0 12px">Here's what you can look forward to:</h3>
<ul style="color:#ccc;line-height:2;margin:0;padding-left:20px">
<li>Exclusive updates and announcements</li>
<li>Early access to events and tickets</li>
<li>Special offers and promotions</li>
<li>Behind-the-scenes content</li>
</ul>
</div>

<?php if (!empty($getting_started_url)): ?>
<p style="margin-top:20px;text-align:center">
<a href="<?= htmlspecialchars($getting_started_url) ?>" class="btn">Get Started</a>
</p>
<?php endif; ?>

<p style="color:#888;font-size:13px;margin-top:20px">Questions? Just reply to this email - we're here to help.</p>
<?php include __DIR__ . '/partials/email-footer.php'; ?>
