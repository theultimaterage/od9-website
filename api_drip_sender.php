<?php
/**
 * OD9 Drip Email Sender (cron, every 5 min)
 *
 * Walks od9_drip_enrollments WHERE status='active' AND next_send_at <= NOW(),
 * sends the current step's template via local exim, logs to od9_drip_log,
 * and advances current_step (or marks the enrollment 'completed' if no
 * further steps exist in the sequence).
 *
 * Cron suggested:
 *   asterisks/5 * * * *  /opt/cpanel/ea-php82/root/usr/bin/php
 *      /home/offda9/public_html/api/drip/sender.php
 *      >> /home/ultimaterage/od9-discord-bot/logs/drip_sender.log 2>&1
 *
 * Templates live at /home/offda9/public_html/email-templates/<template_name>.html
 * (these are the per-event HTML files Orion drafted: tier-theorist.html,
 * achievement-first.html, streak-7day.html, patreon-welcome.html, welcome-day0..14.html).
 *
 * Idempotency: each od9_drip_log row records a single send attempt. We don't
 * re-send a step we already sent; advancement happens on send-success only.
 *
 * Uses mail() with -f noreply@offda9.com so envelope-from aligns with the
 * From: header domain - keeps DMARC happy. Same approach as the
 * weekly_lovelogic_sender.
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die("CLI only.\n");
}

require_once __DIR__ . '/../../config/database.php';

// Unified mailer — Brevo SMTP (port 2525) on prod, Mailtrap sandbox locally.
// Replaces the old raw mail()/exim path so drip mail is Brevo-DKIM-signed.
$_mailLib = __DIR__ . '/../../includes/mail.php';
if (!file_exists($_mailLib)) $_mailLib = __DIR__ . '/../../../includes/mail.php';
require_once $_mailLib;

// Shared branded email chrome — wraps each drip template fragment.
$_layoutLib = __DIR__ . '/../../includes/email_layout.php';
if (!file_exists($_layoutLib)) $_layoutLib = __DIR__ . '/../../../includes/email_layout.php';
require_once $_layoutLib;

const FROM_EMAIL = 'noreply@offda9.com';
const FROM_NAME  = 'The OD9 Movement';
const REPLY_TO   = 'contact@offda9.com';
const TEMPLATE_DIR = '/home/offda9/public_html/email-templates/';
const MAX_PER_RUN = 50;

$pdo = getDatabaseConnection();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$rows = $pdo->query(
    "SELECT en.enrollment_id, en.discord_user_id, en.email, en.sequence_name, en.current_step,
            es.first_name
     FROM od9_drip_enrollments en
     LEFT JOIN email_signups es ON es.email = en.email
     WHERE en.status = 'active'
       AND en.next_send_at IS NOT NULL
       AND en.next_send_at <= NOW()
     ORDER BY en.next_send_at ASC
     LIMIT " . MAX_PER_RUN
)->fetchAll(PDO::FETCH_ASSOC);

if (!$rows) {
    echo "[drip_sender] nothing due\n";
    exit(0);
}

echo "[drip_sender] processing " . count($rows) . " due enrollment(s)\n";
$sent = 0; $failed = 0; $completed = 0;

foreach ($rows as $en) {
    $enrId = (int)$en['enrollment_id'];
    try {
        // Look up the current step
        $st = $pdo->prepare(
            "SELECT st.step_id, st.template_name, st.subject, st.delay_hours,
                    s.sequence_id,
                    (SELECT COUNT(*) FROM od9_drip_steps WHERE sequence_id = s.sequence_id AND is_active = 1) AS total_steps,
                    (SELECT delay_hours FROM od9_drip_steps
                     WHERE sequence_id = s.sequence_id AND step_number = ? + 1 AND is_active = 1) AS next_delay
             FROM od9_drip_sequences s
             JOIN od9_drip_steps st ON st.sequence_id = s.sequence_id AND st.step_number = ?
             WHERE s.sequence_name = ? AND s.is_active = 1 AND st.is_active = 1
             LIMIT 1"
        );
        $st->execute([(int)$en['current_step'], (int)$en['current_step'], $en['sequence_name']]);
        $step = $st->fetch(PDO::FETCH_ASSOC);

        if (!$step) {
            // No step at this position - mark enrollment completed
            $u = $pdo->prepare("UPDATE od9_drip_enrollments SET status = 'completed', next_send_at = NULL WHERE enrollment_id = ?");
            $u->execute([$enrId]);
            $completed++;
            echo "  enrollment $enrId: marked completed (no step at position {$en['current_step']})\n";
            continue;
        }

        // Render template
        $templatePath = TEMPLATE_DIR . $step['template_name'] . '.html';
        if (!file_exists($templatePath)) {
            logSendAttempt($pdo, $en, $step, 'failed', "template not found: {$step['template_name']}.html");
            $failed++;
            echo "  enrollment $enrId: FAIL - template not found ({$step['template_name']}.html)\n";
            continue;
        }
        // Each template is now an inner-content fragment; the shared layout
        // supplies the head/logo/footer. personalize() then resolves tokens in
        // both the fragment and the layout footer ({{username}}, {{EMAIL}}, ...).
        $fragment = file_get_contents($templatePath);
        $html = personalize(od9_email_layout($fragment, ['title' => $step['subject']]), $en);

        // Send via the unified Brevo-signed mailer.
        $unsub = '<https://offda9.com/unsubscribe.php?email=' . rawurlencode($en['email'])
               . '>, <mailto:contact@offda9.com?subject=unsubscribe>';
        $ok = od9_send_mail($en['email'], $step['subject'], $html, [
            'from_email'       => FROM_EMAIL,
            'from_name'        => FROM_NAME,
            'reply_to'         => REPLY_TO,
            'list_unsubscribe' => $unsub,
        ]);

        if ($ok) {
            logSendAttempt($pdo, $en, $step, 'sent', null);
            // Advance: if a next step exists set next_send_at, else mark completed
            if ($step['next_delay'] !== null) {
                $u = $pdo->prepare(
                    "UPDATE od9_drip_enrollments
                     SET current_step = current_step + 1,
                         last_sent_at = NOW(),
                         next_send_at = DATE_ADD(NOW(), INTERVAL ? HOUR)
                     WHERE enrollment_id = ?"
                );
                $u->execute([(int)$step['next_delay'], $enrId]);
            } else {
                $u = $pdo->prepare(
                    "UPDATE od9_drip_enrollments
                     SET current_step = current_step + 1,
                         last_sent_at = NOW(),
                         next_send_at = NULL,
                         status = 'completed'
                     WHERE enrollment_id = ?"
                );
                $u->execute([$enrId]);
                $completed++;
            }
            $sent++;
            echo "  enrollment $enrId: sent {$step['template_name']} to {$en['email']}\n";
        } else {
            logSendAttempt($pdo, $en, $step, 'failed', error_get_last()['message'] ?? 'mail() returned false');
            $failed++;
            echo "  enrollment $enrId: FAIL - mail() returned false\n";
        }
    } catch (Exception $e) {
        echo "  enrollment $enrId: EXCEPTION " . $e->getMessage() . "\n";
        $failed++;
    }
}

echo "[drip_sender] done: sent=$sent failed=$failed completed=$completed\n";

// ============================================================
// Helpers
// ============================================================

function personalize(string $html, array $en): string {
    // Greet by real first name (from email_signups via the sender's JOIN), with a
    // friendly fallback — NEVER the email-prefix, which reads robotic/spammy.
    $first = trim((string)($en['first_name'] ?? ''));
    $name = $first !== '' ? $first : 'Friend';
    $unsubUrl = 'https://offda9.com/unsubscribe.php?email=' . urlencode($en['email']);
    return str_replace(
        ['{{username}}', '{{first_name}}', '{{FIRST_NAME}}', '{{NAME}}', '{{email}}', '{{EMAIL}}', '{{unsubscribe_url}}', '{{UNSUBSCRIBE_URL}}'],
        [
            htmlspecialchars($name, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            htmlspecialchars($name, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            htmlspecialchars($name, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            htmlspecialchars($name, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            htmlspecialchars($en['email'], ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            htmlspecialchars($en['email'], ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            $unsubUrl,
            $unsubUrl,
        ],
        $html
    );
}

function logSendAttempt(PDO $pdo, array $en, array $step, string $status, ?string $err): void {
    try {
        $s = $pdo->prepare(
            "INSERT INTO od9_drip_log
                (enrollment_id, discord_user_id, email, sequence_name, step_number,
                 template_name, subject, status, sent_at, error_message)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)"
        );
        $s->execute([
            (int)$en['enrollment_id'],
            $en['discord_user_id'],
            $en['email'],
            $en['sequence_name'],
            (int)$en['current_step'],
            $step['template_name'],
            $step['subject'],
            $status,
            $err,
        ]);
    } catch (Exception $e) {
        fwrite(STDERR, "[drip_sender] WARN: log insert failed: " . $e->getMessage() . "\n");
    }
}
