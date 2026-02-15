<?php /** OD9 Event Reminder */ include __DIR__ . '/partials/email-header.php'; ?>
<h2>Reminder: <?= htmlspecialchars($event_name ?? 'Upcoming Event') ?></h2>
<p>Hey <?= htmlspecialchars($customer_name ?? 'there') ?>, this is your reminder.</p>
<div class="card" style="border-left:4px solid #00BFFF">
<p style="font-weight:bold;color:#fff;margin:0 0 8px;font-size:18px"><?= htmlspecialchars($event_name ?? '') ?></p>
<?php if (!empty($event_date)): ?><p style="margin:0 0 4px"><strong>Date:</strong> <?= htmlspecialchars($event_date) ?></p><?php endif; ?>
<?php if (!empty($event_time)): ?><p style="margin:0 0 4px"><strong>Time:</strong> <?= htmlspecialchars($event_time) ?></p><?php endif; ?>
<?php if (!empty($venue_name)): ?><p style="margin:0"><strong>Venue:</strong> <?= htmlspecialchars($venue_name) ?></p><?php endif; ?>
</div>
<p>Have your tickets ready. See you there.</p>
<?php include __DIR__ . '/partials/email-footer.php'; ?>
