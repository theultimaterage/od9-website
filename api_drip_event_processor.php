<?php
/**
 * OD9 Drip Event Processor (cron, every 5 min)
 *
 * Walks od9_webhook_log WHERE processed=0 and turns each event into an
 * od9_drip_enrollments row (or marks it as 'no_email_known' / 'no_sequence_match'
 * and skips). The companion email sender (api_drip_sender.php) then walks
 * the enrollments and actually sends mail.
 *
 * Cron suggested:
 *   asterisks/5 * * * *  /opt/cpanel/ea-php82/root/usr/bin/php
 *      /home/offda9/public_html/api/drip/event_processor.php
 *      >> /home/ultimaterage/od9-discord-bot/logs/drip_event.log 2>&1
 *
 * Email lookup priority (today):
 *   1. od9_members.patreon_email by discord_user_id  (Patreon-linked users)
 *   2. (future) discord_email_links table populated via /link_email Discord cmd
 *   3. otherwise mark 'no_email_known' and move on
 *
 * Sequence selection:
 *   tier_change      -> tier_<old>_to_<new>     (skip if multi-tier jump unmatched)
 *   achievement      -> achievement_first       (only first - guard with already-enrolled check)
 *   streak_milestone -> streak_<days>_day       (only 7 today; others skipped)
 *   patreon          -> patreon_welcome
 *
 * Idempotency:
 *   - Webhook rows are processed at most once (processed=1 with a result string).
 *   - od9_drip_enrollments has UNIQUE-by-(discord_user_id, sequence_name) intent
 *     enforced by a SELECT-then-INSERT guard (the schema doesn't define a unique
 *     index on those two cols, so we check before insert).
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die("CLI only.\n");
}

require_once __DIR__ . '/../../config/database.php';

const MAX_PER_RUN = 200;

$pdo = getDatabaseConnection();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$rows = $pdo->query(
    "SELECT webhook_id, event_type, discord_user_id, payload
     FROM od9_webhook_log
     WHERE processed = 0
     ORDER BY received_at ASC
     LIMIT " . MAX_PER_RUN
)->fetchAll(PDO::FETCH_ASSOC);

if (!$rows) {
    echo "[event_processor] no unprocessed webhook rows\n";
    exit(0);
}

echo "[event_processor] processing " . count($rows) . " webhook row(s)\n";
$enrolled = 0; $skipped = 0; $errored = 0;

foreach ($rows as $row) {
    $logId = (int)$row['webhook_id'];
    $eventType = $row['event_type'];
    $discordId = $row['discord_user_id'];
    $payload = json_decode($row['payload'], true) ?? [];
    $data = $payload['data'] ?? [];

    try {
        // 1. Resolve email
        $email = lookupEmail($pdo, $discordId);
        if (!$email) {
            markProcessed($pdo, $logId, 'no_email_known');
            $skipped++;
            echo "  webhook $logId: skipped (no email for discord $discordId)\n";
            continue;
        }

        // 2. Resolve target sequence
        $sequenceName = pickSequence($eventType, $data);
        if (!$sequenceName) {
            markProcessed($pdo, $logId, 'no_sequence_match');
            $skipped++;
            echo "  webhook $logId: skipped (no sequence for event=$eventType)\n";
            continue;
        }

        // 3. Already enrolled in this sequence? (idempotency guard)
        $existing = $pdo->prepare(
            "SELECT enrollment_id FROM od9_drip_enrollments
             WHERE discord_user_id = ? AND sequence_name = ?
             LIMIT 1"
        );
        $existing->execute([$discordId, $sequenceName]);
        if ($existing->fetchColumn()) {
            markProcessed($pdo, $logId, 'already_enrolled');
            $skipped++;
            echo "  webhook $logId: skipped (already enrolled in $sequenceName)\n";
            continue;
        }

        // 4. Look up step 0 to compute next_send_at
        $seq = $pdo->prepare(
            "SELECT s.sequence_id, st.delay_hours
             FROM od9_drip_sequences s
             JOIN od9_drip_steps st ON st.sequence_id = s.sequence_id AND st.step_number = 0
             WHERE s.sequence_name = ? AND s.is_active = 1 AND st.is_active = 1
             LIMIT 1"
        );
        $seq->execute([$sequenceName]);
        $seqRow = $seq->fetch(PDO::FETCH_ASSOC);
        if (!$seqRow) {
            markProcessed($pdo, $logId, 'sequence_inactive_or_missing_step0');
            $skipped++;
            echo "  webhook $logId: skipped (no active step 0 for $sequenceName)\n";
            continue;
        }

        // 5. Insert enrollment
        $delayHours = (int)$seqRow['delay_hours'];
        $ins = $pdo->prepare(
            "INSERT INTO od9_drip_enrollments
                (discord_user_id, email, sequence_name, current_step,
                 next_send_at, status, source, metadata)
             VALUES (?, ?, ?, 0,
                     DATE_ADD(NOW(), INTERVAL ? HOUR),
                     'active', 'webhook_event', ?)"
        );
        $ins->execute([
            $discordId, $email, $sequenceName, $delayHours,
            json_encode(['triggered_by' => $eventType, 'webhook_log_id' => $logId, 'event_data' => $data]),
        ]);

        markProcessed($pdo, $logId, 'enrolled:' . $sequenceName);
        $enrolled++;
        echo "  webhook $logId: enrolled $discordId ($email) in $sequenceName (next_send +{$delayHours}h)\n";

    } catch (Exception $e) {
        markProcessed($pdo, $logId, 'error:' . substr($e->getMessage(), 0, 80));
        $errored++;
        echo "  webhook $logId: ERROR " . $e->getMessage() . "\n";
    }
}

echo "[event_processor] done: enrolled=$enrolled skipped=$skipped errored=$errored\n";

// ============================================================
// Helpers
// ============================================================

function lookupEmail(PDO $pdo, string $discordId): ?string {
    // Priority 1: email_signups.discord_user_id (set when a Discord member
    // ran /link_email <email> AND clicked the verification link). This is
    // the canonical path for non-Patreon members.
    $s = $pdo->prepare(
        "SELECT email FROM email_signups
         WHERE discord_user_id = ?
           AND is_verified = 1
           AND email_opt_in = 1
           AND status = 'active'
         ORDER BY id DESC LIMIT 1"
    );
    $s->execute([$discordId]);
    $email = $s->fetchColumn();
    if ($email) return $email;

    // Priority 2: od9_members.patreon_email (set when a Patreon supporter
    // links their Discord account via the cogs/patreon_webhook.py flow).
    $s = $pdo->prepare(
        "SELECT patreon_email FROM od9_members
         WHERE discord_user_id = ? AND patreon_email IS NOT NULL AND patreon_email != ''
         LIMIT 1"
    );
    $s->execute([$discordId]);
    $email = $s->fetchColumn();
    if ($email) return $email;

    return null;
}

function pickSequence(string $eventType, array $data): ?string {
    switch ($eventType) {
        case 'tier_change':
            $old = strtolower($data['old_tier'] ?? '');
            $new = strtolower($data['new_tier'] ?? '');
            if ($old && $new) {
                return "tier_{$old}_to_{$new}";
            }
            return null;

        case 'achievement':
            // Only first-achievement triggers a drip today. The DB-level
            // 'already_enrolled' guard above ensures we only ever fire the
            // 'achievement_first' sequence once per discord user, regardless
            // of how many achievements they earn.
            return 'achievement_first';

        case 'streak_milestone':
            $days = (int)($data['streak_days'] ?? 0);
            if ($days === 7) return 'streak_7_day';
            // Other milestones (14, 30, etc.) have no sequence yet.
            return null;

        case 'patreon':
            return 'patreon_welcome';

        default:
            return null;
    }
}

function markProcessed(PDO $pdo, int $logId, string $result): void {
    $s = $pdo->prepare(
        "UPDATE od9_webhook_log
         SET processed = 1, processed_at = NOW(), result = ?
         WHERE webhook_id = ?"
    );
    $s->execute([$result, $logId]);
}
