<?php /** OD9 Order Confirmation */ include __DIR__ . '/partials/email-header.php'; ?>
<h2>Order Confirmed</h2>
<p>Hey <?= htmlspecialchars($customer_name ?? 'there') ?>, your order <strong>#<?= htmlspecialchars($order_number ?? '') ?></strong> is confirmed.</p>
<?php if (!empty($tickets_by_event)): ?>
<h3>Your Tickets</h3>
<?php foreach ($tickets_by_event as $evt): ?>
<div class="card">
<p style="font-weight:bold;color:#fff;margin:0 0 5px"><?= htmlspecialchars($evt['event_name'] ?? '') ?></p>
<p style="color:#666;margin:0;font-size:13px"><?= htmlspecialchars($evt['event_date'] ?? '') ?> | <?= htmlspecialchars($evt['venue'] ?? '') ?></p>
<p style="color:#666;margin:5px 0 0;font-size:13px"><?= count($evt['tickets'] ?? []) ?> ticket(s)</p>
</div>
<?php endforeach; endif; ?>
<?php if (!empty($products)): ?>
<h3>Your Items</h3>
<?php foreach ($products as $p): ?>
<div class="card" style="padding:12px 20px">
<span style="color:#fff"><?= htmlspecialchars($p['name'] ?? '') ?></span>
<span style="float:right;color:#00BFFF">$<?= number_format((float)($p['price'] ?? 0), 2) ?></span>
</div>
<?php endforeach; ?>
<p style="text-align:right;font-size:18px;color:#fff;margin-top:10px"><strong>Total: $<?= number_format((float)($total ?? 0), 2) ?></strong></p>
<?php endif; ?>
<?php if (!empty($receipt_url)): ?><p style="margin-top:20px"><a href="<?= htmlspecialchars($receipt_url) ?>" class="btn">View Receipt</a></p><?php endif; ?>
<?php include __DIR__ . '/partials/email-footer.php'; ?>
