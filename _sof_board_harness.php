<?php
// LOCAL-ONLY visual harness for the board's State of the Filter + Live Roadmap panels.
// Not in the repo — never deployed. Renders the real include against the real board.css.
require_once __DIR__ . '/includes/filter-scorecard-data.php';
$RM_ACTIVE = [
    ['trigger_id' => 'T-COM-100', 'domain' => 'COM', 'title' => 'Community at 100 active members', 'condition_text' => '100+ Discord members with ≥1 credit-earning action in past 30 days.'],
    ['trigger_id' => 'T-REV-100', 'domain' => 'REV', 'title' => 'First dollar of monthly recurring revenue', 'condition_text' => '≥1 Patreon supporter at any tier OR first paid offering sold.'],
    ['trigger_id' => 'T-RES-001', 'domain' => 'RES', 'title' => 'First-wave research-partnership outreach', 'condition_text' => 'Research statement current (v0.3+), research@offda9.com live, one-page abstract ready.'],
    ['trigger_id' => 'T-INF-STREAM-NOTIFY-START-001', 'domain' => 'INF', 'title' => 'Instant live-start notification (OBS -> Discord)', 'condition_text' => 'The F.R.E.S.H. Stream Center plugin posts a signed stream-start webhook the instant OBS starts streaming.'],
    ['trigger_id' => 'T-CON-001', 'domain' => 'CON', 'title' => 'Curriculum spans all 5 tiers', 'condition_text' => 'Architect, Pioneer, and Benefactor tiers each have gated required content + capstones live.'],
];
$RM_QUEUED = 31;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>SoF board harness</title>
<link rel="stylesheet" href="/od9/css/board.css">
<style>body{background:#0a0a0a;min-height:100vh;padding:40px 0}</style>
</head>
<body>
<div class="board" style="background:#0a0a0a">
<?php include __DIR__ . '/includes/filter-scorecard-board.php'; ?>
</div>
</body>
</html>
