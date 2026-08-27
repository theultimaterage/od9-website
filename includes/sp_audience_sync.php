<?php
/**
 * OD9 → F.R.E.S.H. platform audience sync — the ONE transport.
 *
 * Every flow that changes a fan's subscription state (subscribe.php,
 * verify.php, unsubscribe.php) mirrors it here into the platform's
 * email_subscribers table. Before 2026-08-27 each flow hand-rolled its own
 * SQL (or, for verify/unsubscribe, synced nothing), and subscribe.php's
 * INSERT hardcoded status 'pending_verification' — a value valid in NEITHER
 * schema — while sp-credentials.php pointed at the pre-extraction database.
 * 12 of 13 signups were silently lost to that pair of defects.
 *
 * Status vocabulary (platform enum, migration 082):
 *   'pending'      — signed up, not yet verified. The platform's send paths
 *                    (CampaignEngine::resolveAudience, sequence processor)
 *                    select ONLY status='active', so a pending fan can never
 *                    be mailed by the platform.
 *   'active'       — verified + opted in (verify.php only).
 *   'unsubscribed' — opted out.
 * 'bounced'/'complained' are platform-owned states (bounce webhooks) and are
 * deliberately NOT syncable from here — the whitelist below is the gate that
 * makes another invalid-enum write impossible.
 *
 * Best-effort contract: this must never break the fan-facing flow. Failures
 * are caught (\Throwable — an \Error must not kill a signup after the fan is
 * in od9's own DB) and logged with the [sp-sync] prefix. Silent-skip is not
 * allowed: a missing credentials file logs loudly too. Drift is additionally
 * caught by the prod ops check, which compares od9's email_signups count to
 * the platform mirror's.
 */

function od9_sp_audience_sync(
    string $email,
    ?string $firstName,
    ?string $lastName,
    string $status,
    ?array $customFields = null
): void {
    static $syncable = ['pending', 'active', 'unsubscribed'];
    try {
        if (!in_array($status, $syncable, true)) {
            error_log("[sp-sync] refused status '{$status}' for {$email} — syncable: " . implode(',', $syncable));
            return;
        }

        // Outside-webroot location preferred; docroot config/ is the live
        // fallback on prod. Missing config logs loudly — a silent skip is how
        // the old sync hid a dead DSN for months.
        $paths = [__DIR__ . '/../../config/sp-credentials.php', __DIR__ . '/../config/sp-credentials.php'];
        $spCreds = null;
        foreach ($paths as $p) {
            if (file_exists($p)) { $spCreds = require $p; break; }
        }
        if (!is_array($spCreds) || empty($spCreds['dsn'])) {
            error_log("[sp-sync] sp-credentials.php missing/invalid (checked " . implode(', ', $paths) . ") — '{$status}' for {$email} NOT synced");
            return;
        }

        $pdo = new PDO($spCreds['dsn'], $spCreds['user'], $spCreds['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5, // a hung platform DB must not stall the fan-facing request
        ]);

        // ODKU assignment order is load-bearing: SET clauses run left to
        // right, and `status` (unqualified) means the OLD stored value until
        // the moment it is reassigned — so the date logic reads the old state
        // and `status` is assigned LAST. The status IF() keeps a verified
        // ('active') row from being demoted by a late/duplicate 'pending'.
        $sql = "INSERT INTO email_subscribers
                    (site_id, email, first_name, last_name, status, source, custom_fields, subscribed_at, unsubscribed_at)
                VALUES
                    ('od9', :email, NULLIF(:first_name, ''), NULLIF(:last_name, ''), :status, 'signup', :custom_fields,
                     NOW(), IF(:status_b = 'unsubscribed', NOW(), NULL))
                ON DUPLICATE KEY UPDATE
                    first_name      = COALESCE(NULLIF(VALUES(first_name), ''), first_name),
                    last_name       = COALESCE(NULLIF(VALUES(last_name), ''), last_name),
                    custom_fields   = COALESCE(VALUES(custom_fields), custom_fields),
                    subscribed_at   = IF(VALUES(status) = 'pending' AND status = 'unsubscribed', NOW(), subscribed_at),
                    unsubscribed_at = CASE WHEN VALUES(status) = 'unsubscribed' THEN NOW()
                                           WHEN status = 'unsubscribed' THEN NULL
                                           ELSE unsubscribed_at END,
                    status          = IF(VALUES(status) = 'pending' AND status = 'active', status, VALUES(status))";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':email'         => $email,
            ':first_name'    => $firstName ?? '',
            ':last_name'     => $lastName ?? '',
            ':status'        => $status,
            ':status_b'      => $status,
            ':custom_fields' => $customFields !== null ? json_encode($customFields) : null,
        ]);
    } catch (\Throwable $e) {
        error_log("[sp-sync] '{$status}' sync failed for {$email}: " . $e->getMessage());
    }
}
