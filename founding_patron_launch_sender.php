<?php
/**
 * Founding Patron Launch Broadcast Sender (one-shot)
 *
 * Sends the founding-patron-launch.html template to all verified +
 * opted-in subscribers in email_signups. Idempotent per recipient via
 * the email_log.campaign column.
 *
 * USAGE (manual / one-shot):
 *   php founding_patron_launch_sender.php --dry-run
 *   php founding_patron_launch_sender.php --test-email=you@example.com
 *   php founding_patron_launch_sender.php --send
 *
 * The --send flag is required for actual delivery as a sanity guard
 * (default is dry-run if no flag given). Patterned after
 * weekly_lovelogic_sender.php; reuses its column-migration + idempotency
 * approach so the two senders share the same email_log shape.
 */

declare(strict_types=1);

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die("CLI only.\n");
}

$configPath = __DIR__ . '/../config/database.php';
if (!file_exists($configPath)) $configPath = __DIR__ . '/config/database.php';
if (!file_exists($configPath)) $configPath = __DIR__ . '/../../config/database.php';
require_once $configPath;

// Mail wrapper: routes through Mailtrap on local-XAMPP, exim on prod.
$_mailHelper = __DIR__ . '/../includes/mail.php';
if (!file_exists($_mailHelper)) $_mailHelper = __DIR__ . '/includes/mail.php';
if (!file_exists($_mailHelper)) $_mailHelper = __DIR__ . '/../../includes/mail.php';
require_once $_mailHelper;

const CAMPAIGN     = 'founding_patron_launch_2026_05';
const FROM_EMAIL   = 'noreply@offda9.com';
const FROM_NAME    = 'The Ultimate Rage';
const REPLY_TO     = 'contact@offda9.com';
const SUBJECT      = '25 lifetime slots. Then this tier closes forever.';
const RATE_DELAY_MS = 200;  // ~5 emails/sec, gentle on local exim

// email-templates/ lives at __DIR__/../email-templates/ on local XAMPP and
// __DIR__/email-templates/ on production cPanel - try both.
$template = __DIR__ . '/../email-templates/founding-patron-launch.html';
if (!file_exists($template)) {
    $template = __DIR__ . '/email-templates/founding-patron-launch.html';
}
define('TEMPLATE', $template);

// ---- CLI args ----
$opts = getopt('', ['dry-run', 'send', 'test-email::']);
$dryRun    = isset($opts['dry-run']) || (!isset($opts['send']) && !isset($opts['test-email']));
$send      = isset($opts['send']);
$testEmail = $opts['test-email'] ?? null;

if (!file_exists(TEMPLATE)) {
    fwrite(STDERR, "[launch] template missing: " . TEMPLATE . "\n");
    exit(2);
}
$htmlRaw = file_get_contents(TEMPLATE);

$pdo = getDatabaseConnection();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

ensureCampaignColumn($pdo);

// ---- Recipients ----
if ($testEmail) {
    $recipients = [['id' => 0, 'email' => $testEmail, 'first_name' => 'Tester']];
    echo "[launch] TEST MODE - sending only to {$testEmail}\n";
} else {
    $stmt = $pdo->query(
        "SELECT id, email, first_name
         FROM email_signups
         WHERE is_verified = 1 AND email_opt_in = 1
         ORDER BY id ASC"
    );
    $recipients = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if (!$recipients) {
    echo "[launch] no recipients found\n";
    exit(0);
}

echo "[launch] mode=" . ($dryRun ? 'DRY-RUN' : ($send ? 'SEND' : 'TEST'))
   . " recipients=" . count($recipients) . " campaign=" . CAMPAIGN . "\n";

if ($dryRun && !$testEmail) {
    foreach (array_slice($recipients, 0, 10) as $r) {
        echo "  would send -> {$r['email']}\n";
    }
    if (count($recipients) > 10) echo "  ... and " . (count($recipients) - 10) . " more\n";
    echo "[launch] DRY-RUN complete. Re-run with --send to actually deliver.\n";
    exit(0);
}

// ---- Send loop ----
$sent = 0; $skipped = 0; $errored = 0;

foreach ($recipients as $r) {
    $email = trim((string)$r['email']);
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $skipped++;
        continue;
    }

    if (!$testEmail && wasAlreadySent($pdo, $email, CAMPAIGN)) {
        $skipped++;
        continue;
    }

    $firstName = trim((string)($r['first_name'] ?? ''));
    $unsub = 'https://offda9.com/unsubscribe.php?email=' . urlencode($email);

    $body = str_replace(
        ['{{first_name|Hey}}', '{{unsubscribe_url}}'],
        [
            $firstName !== '' ? htmlspecialchars($firstName, ENT_QUOTES) : 'Hey',
            htmlspecialchars($unsub, ENT_QUOTES),
        ],
        $htmlRaw
    );

    // This template is already a full standalone designed HTML email (not a
    // fragment), so it is NOT wrapped in od9_email_layout. The unified mailer
    // adds List-Unsubscribe-Post + X-Mailer + Message-ID itself.
    $ok = od9_send_mail($email, SUBJECT, $body, [
        'from_email'       => FROM_EMAIL,
        'from_name'        => FROM_NAME,
        'reply_to'         => REPLY_TO,
        'list_unsubscribe' => '<' . $unsub . '>, <mailto:contact@offda9.com?subject=unsubscribe>',
    ]);
    if ($ok) {
        $sent++;
        if (!$testEmail) {
            logSend($pdo, $email, SUBJECT, CAMPAIGN, 'sent', null);
        }
        echo "  sent -> {$email}\n";
    } else {
        $errored++;
        $err = error_get_last()['message'] ?? 'mail() returned false';
        if (!$testEmail) {
            logSend($pdo, $email, SUBJECT, CAMPAIGN, 'failed', $err);
        }
        echo "  FAILED -> {$email} ({$err})\n";
    }

    usleep(RATE_DELAY_MS * 1000);
}

echo "[launch] done: sent={$sent} skipped={$skipped} errored={$errored}\n";
exit($errored > 0 ? 1 : 0);


// ---------------------------------------------------------------------------
// Helpers (shape-compatible with weekly_lovelogic_sender.php)
// ---------------------------------------------------------------------------

function ensureCampaignColumn(PDO $pdo): void {
    try {
        $cols = $pdo->query("SHOW COLUMNS FROM email_log LIKE 'campaign'")->fetchAll();
        if (!$cols) {
            $pdo->exec(
                "ALTER TABLE email_log
                 ADD COLUMN campaign VARCHAR(100) DEFAULT NULL,
                 ADD INDEX idx_campaign_recipient (campaign, recipient)"
            );
        }
    } catch (Throwable $e) {
        fwrite(STDERR, "[launch] WARN: could not ensure campaign column: " . $e->getMessage() . "\n");
    }
}

function wasAlreadySent(PDO $pdo, string $email, string $campaign): bool {
    $stmt = $pdo->prepare(
        "SELECT 1 FROM email_log
         WHERE recipient = ? AND campaign = ? AND status = 'sent' LIMIT 1"
    );
    $stmt->execute([$email, $campaign]);
    return (bool)$stmt->fetchColumn();
}

function logSend(PDO $pdo, string $email, string $subject, string $campaign, string $status, ?string $err): void {
    $stmt = $pdo->prepare(
        "INSERT INTO email_log (recipient, subject, status, campaign, sent_at, error_message, attempts)
         VALUES (?, ?, ?, ?, NOW(), ?, 1)"
    );
    $stmt->execute([$email, $subject, $status, $campaign, $err]);
}
