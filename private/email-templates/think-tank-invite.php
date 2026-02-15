<?php
/**
 * Think Tank Invite - Invitation to upcoming Think Tank session
 * 
 * Variables: $member_name, $session_topic, $session_date, $session_time, $host_name, $description
 */
include __DIR__ . '/partials/email-header.php';
$name = $member_name ?? 'Member';
?>
<h2>Think Tank Session</h2>

<p>Hey <?= htmlspecialchars($name) ?>,</p>
<p>You're invited to an upcoming Think Tank session. These collaborative analysis sessions are where the real synthesis happens - cross-concept exploration that changes how you see civilizational problems.</p>

<div class="card" style="border-left:4px solid #00BFFF">
<h3 style="margin-top:0;color:#fff"><?= htmlspecialchars($session_topic ?? 'Topic TBD') ?></h3>
<?php if (!empty($description)): ?><p style="font-size:14px"><?= htmlspecialchars($description) ?></p><?php endif; ?>
<hr class="divider" style="margin:15px 0">
<p style="margin:0 0 6px"><strong>Date:</strong> <?= htmlspecialchars($session_date ?? 'TBD') ?></p>
<p style="margin:0 0 6px"><strong>Time:</strong> <?= htmlspecialchars($session_time ?? 'TBD') ?></p>
<?php if (!empty($host_name)): ?><p style="margin:0"><strong>Hosted by:</strong> <?= htmlspecialchars($host_name) ?></p><?php endif; ?>
</div>

<div class="card" style="text-align:center;background:rgba(0,191,255,0.05)">
<p style="color:#00BFFF;font-weight:bold;margin:0 0 5px">+50 CREDITS</p>
<p style="font-size:13px;margin:0">Awarded for attending and participating</p>
</div>

<p>RSVP in the Discord. Attendance is tracked and credits are awarded automatically after the session.</p>

<p style="text-align:center;margin:25px 0"><a href="https://discord.gg/spgmrXVMWq" class="btn">RSVP in Discord</a></p>
<?php include __DIR__ . '/partials/email-footer.php'; ?>
