<?php /** OD9 Refund Notification */ include __DIR__ . '/partials/email-header.php'; ?>
<h2>Refund Processed</h2>
<p>Hey <?= htmlspecialchars($customer_name ?? 'there') ?>,</p>
<p>Your refund for order <strong>#<?= htmlspecialchars($order_id ?? '') ?></strong> has been processed.</p>
<div class="card">
<p style="margin:0 0 8px"><strong>Order:</strong> #<?= htmlspecialchars($order_id ?? '') ?></p>
<p style="margin:0 0 8px"><strong>Refund:</strong> <span style="color:#48bb78;font-size:18px">$<?= number_format((float)($refund_amount ?? 0), 2) ?></span></p>
<?php if (!empty($refund_reason)): ?><p style="margin:0"><strong>Reason:</strong> <?= htmlspecialchars($refund_reason) ?></p><?php endif; ?>
</div>
<p>Should appear on your original payment method within 5-10 business days.</p>
<?php include __DIR__ . '/partials/email-footer.php'; ?>
