<?php
/**
 * OD9 Drip System - Email Processor (Cron Job)
 *
 * Processes due drip emails:
 * 1. Finds enrollments where next_send_at <= NOW() and status = 'active'
 * 2. Loads the appropriate email template
 * 3. Replaces placeholders with user data
 * 4. Sends email via configured method
 * 5. Logs result and updates enrollment
 *
 * Cron: Run every 5-15 minutes
 * Example: * /5 * * * * /usr/bin/php /path/to/processor.php >> /var/log/od9-drip.log 2>&1
 *
 * @author Orion
 * @version 1.0.0
 */

// Only run from CLI
if (php_sapi_name() !== 'cli' && !defined('ALLOW_WEB_RUN')) {
    die("This script must be run from command line\n");
}

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Configuration
$isLocal = strpos(__DIR__, 'xampp') !== false;

if ($isLocal) {
    define('DRIP_DB_HOST', 'localhost');
    define('DRIP_DB_NAME', 'od9_tickets');
    define('DRIP_DB_USER', 'root');
    define('DRIP_DB_PASS', '');
} else {
    define('DRIP_DB_HOST', 'localhost');
    define('DRIP_DB_NAME', 'offda9_od9_tickets');
    define('DRIP_DB_USER', 'offda9_od9admin');
    define('DRIP_DB_PASS', 'Xk9mPvR3nWq7sYd');
}

// Email configuration
define('SMTP_ENABLED', !$isLocal); // Use SMTP in production
define('SMTP_HOST', getenv('SMTP_HOST') ?: 'smtp.gmail.com');
define('SMTP_PORT', (int)(getenv('SMTP_PORT') ?: 587));
define('SMTP_USER', getenv('SMTP_USER') ?: '');
define('SMTP_PASS', getenv('SMTP_PASS') ?: '');
define('FROM_EMAIL', 'noreply@offda9.com');
define('FROM_NAME', 'The OD9 Movement');
define('REPLY_TO', 'info@offda9.com');

// Template directory
define('TEMPLATE_DIR', dirname(__DIR__, 2) . '/email-templates/');

// Process limits
define('MAX_EMAILS_PER_RUN', 50);
define('LOCK_FILE', sys_get_temp_dir() . '/od9_drip_processor.lock');

/**
 * Get database connection
 */
function getDripDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', DRIP_DB_HOST, DRIP_DB_NAME);
        $pdo = new PDO($dsn, DRIP_DB_USER, DRIP_DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]);
    }
    return $pdo;
}

/**
 * Log message
 */
function dripLog(string $level, string $message, array $context = []): void {
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
    echo "[{$timestamp}] [{$level}] {$message}{$contextStr}\n";
}

/**
 * Get member data from od9_members
 */
function getMemberData(string $discordUserId): ?array {
    $pdo = getDripDB();

    // First try od9_members table
    $stmt = $pdo->prepare("SELECT * FROM od9_members WHERE discord_user_id = ? LIMIT 1");
    $stmt->execute([$discordUserId]);
    $member = $stmt->fetch();

    if ($member) {
        return $member;
    }

    // Fallback: try customers table if od9_members doesn't have the user
    $stmt = $pdo->prepare("SELECT id, email, first_name, last_name, 'observer' as tier_name
        FROM customers WHERE discord_user_id = ? LIMIT 1");
    $stmt->execute([$discordUserId]);
    return $stmt->fetch() ?: null;
}

/**
 * Load email template
 */
function loadTemplate(string $templateName): ?string {
    $templatePath = TEMPLATE_DIR . $templateName;

    if (!file_exists($templatePath)) {
        dripLog('WARNING', "Template not found: {$templateName}");
        return null;
    }

    return file_get_contents($templatePath);
}

/**
 * Replace placeholders in template
 */
function processTemplate(string $template, array $member, array $extraData = []): string {
    // Build replacements
    $replacements = [
        // Member data
        '{{FIRST_NAME}}' => $member['first_name'] ?? 'Friend',
        '{{LAST_NAME}}' => $member['last_name'] ?? '',
        '{{FULL_NAME}}' => trim(($member['first_name'] ?? '') . ' ' . ($member['last_name'] ?? '')) ?: 'Friend',
        '{{EMAIL}}' => $member['email'] ?? '',
        '{{DISCORD_ID}}' => $member['discord_user_id'] ?? '',
        '{{DISCORD_USERNAME}}' => $member['discord_username'] ?? $member['discord_user_id'] ?? '',
        '{{TIER}}' => ucfirst($member['tier_name'] ?? 'Observer'),
        '{{TIER_NAME}}' => ucfirst($member['tier_name'] ?? 'Observer'),
        '{{CREDITS}}' => number_format($member['credits'] ?? 0),
        '{{JOINED_DATE}}' => isset($member['created_at']) ? date('F j, Y', strtotime($member['created_at'])) : date('F j, Y'),

        // OD9 branding
        '{{BRAND_NAME}}' => 'The OD9 Movement',
        '{{BRAND_COLOR_PRIMARY}}' => '#00BFFF',
        '{{BRAND_COLOR_SECONDARY}}' => '#0D0D0D',
        '{{WEBSITE_URL}}' => 'https://offda9.com',
        '{{DISCORD_INVITE}}' => 'https://discord.gg/od9',
        '{{UNSUBSCRIBE_URL}}' => 'https://offda9.com/unsubscribe?id=' . ($member['discord_user_id'] ?? ''),

        // Dynamic data
        '{{CURRENT_YEAR}}' => date('Y'),
        '{{CURRENT_DATE}}' => date('F j, Y'),
    ];

    // Merge extra data
    foreach ($extraData as $key => $value) {
        $replacements['{{' . strtoupper($key) . '}}'] = $value;
    }

    return str_replace(array_keys($replacements), array_values($replacements), $template);
}

/**
 * Send email via PHP mail() or SMTP
 */
function sendEmail(string $to, string $subject, string $htmlBody): bool {
    if (SMTP_ENABLED && !empty(SMTP_USER)) {
        return sendSmtpEmail($to, $subject, $htmlBody);
    }

    return sendPhpMail($to, $subject, $htmlBody);
}

/**
 * Send via PHP mail()
 */
function sendPhpMail(string $to, string $subject, string $htmlBody): bool {
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=utf-8',
        'From: ' . FROM_NAME . ' <' . FROM_EMAIL . '>',
        'Reply-To: ' . REPLY_TO,
        'X-Mailer: OD9-Drip-System/1.0'
    ];

    return mail($to, $subject, $htmlBody, implode("\r\n", $headers));
}

/**
 * Send via SMTP (using sockets - no external dependencies)
 */
function sendSmtpEmail(string $to, string $subject, string $htmlBody): bool {
    $socket = @fsockopen(SMTP_HOST, SMTP_PORT, $errno, $errstr, 30);
    if (!$socket) {
        dripLog('ERROR', "SMTP connection failed: {$errstr}");
        return false;
    }

    try {
        // Read greeting
        fgets($socket, 1024);

        // EHLO
        fwrite($socket, "EHLO " . gethostname() . "\r\n");
        while ($line = fgets($socket, 1024)) {
            if (substr($line, 3, 1) === ' ') break;
        }

        // STARTTLS
        fwrite($socket, "STARTTLS\r\n");
        fgets($socket, 1024);
        stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);

        // EHLO again after TLS
        fwrite($socket, "EHLO " . gethostname() . "\r\n");
        while ($line = fgets($socket, 1024)) {
            if (substr($line, 3, 1) === ' ') break;
        }

        // AUTH
        fwrite($socket, "AUTH LOGIN\r\n");
        fgets($socket, 1024);
        fwrite($socket, base64_encode(SMTP_USER) . "\r\n");
        fgets($socket, 1024);
        fwrite($socket, base64_encode(SMTP_PASS) . "\r\n");
        $response = fgets($socket, 1024);
        if (substr($response, 0, 3) !== '235') {
            dripLog('ERROR', "SMTP auth failed: {$response}");
            return false;
        }

        // MAIL FROM
        fwrite($socket, "MAIL FROM:<" . FROM_EMAIL . ">\r\n");
        fgets($socket, 1024);

        // RCPT TO
        fwrite($socket, "RCPT TO:<{$to}>\r\n");
        fgets($socket, 1024);

        // DATA
        fwrite($socket, "DATA\r\n");
        fgets($socket, 1024);

        // Email content
        $message = "From: " . FROM_NAME . " <" . FROM_EMAIL . ">\r\n";
        $message .= "To: {$to}\r\n";
        $message .= "Subject: {$subject}\r\n";
        $message .= "Reply-To: " . REPLY_TO . "\r\n";
        $message .= "MIME-Version: 1.0\r\n";
        $message .= "Content-Type: text/html; charset=utf-8\r\n";
        $message .= "X-Mailer: OD9-Drip-System/1.0\r\n";
        $message .= "\r\n";
        $message .= $htmlBody;
        $message .= "\r\n.\r\n";

        fwrite($socket, $message);
        $response = fgets($socket, 1024);

        // QUIT
        fwrite($socket, "QUIT\r\n");
        fclose($socket);

        return substr($response, 0, 3) === '250';

    } catch (Exception $e) {
        dripLog('ERROR', "SMTP error: " . $e->getMessage());
        if ($socket) fclose($socket);
        return false;
    }
}

/**
 * Log email send attempt
 */
function logDripEmail(int $enrollmentId, int $stepId, string $email, string $subject, string $status, ?string $error = null): void {
    $pdo = getDripDB();
    $stmt = $pdo->prepare("INSERT INTO od9_drip_log
        (enrollment_id, step_id, email_to, subject, status, error_message)
        VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$enrollmentId, $stepId, $email, $subject, $status, $error]);
}

/**
 * Update enrollment after sending
 */
function updateEnrollment(int $enrollmentId, int $currentStep, int $totalSteps, int $nextDelayHours): void {
    $pdo = getDripDB();

    if ($currentStep >= $totalSteps) {
        // Sequence completed
        $stmt = $pdo->prepare("UPDATE od9_drip_enrollments
            SET status = 'completed', current_step = ?, completed_at = NOW(), next_send_at = NULL
            WHERE id = ?");
        $stmt->execute([$currentStep, $enrollmentId]);
    } else {
        // Move to next step
        $stmt = $pdo->prepare("UPDATE od9_drip_enrollments
            SET current_step = ?, next_send_at = DATE_ADD(NOW(), INTERVAL ? HOUR)
            WHERE id = ?");
        $stmt->execute([$currentStep + 1, $nextDelayHours, $enrollmentId]);
    }
}

/**
 * Get due enrollments
 */
function getDueEnrollments(): array {
    $pdo = getDripDB();
    $stmt = $pdo->prepare("
        SELECT
            e.id as enrollment_id,
            e.discord_user_id,
            e.sequence_id,
            e.current_step,
            s.slug as sequence_slug,
            s.name as sequence_name,
            st.id as step_id,
            st.template_name,
            st.subject,
            st.delay_hours,
            (SELECT COUNT(*) FROM od9_drip_steps WHERE sequence_id = e.sequence_id) as total_steps,
            (SELECT delay_hours FROM od9_drip_steps WHERE sequence_id = e.sequence_id AND step_number = e.current_step + 1) as next_delay_hours
        FROM od9_drip_enrollments e
        JOIN od9_drip_sequences s ON e.sequence_id = s.id
        JOIN od9_drip_steps st ON st.sequence_id = e.sequence_id AND st.step_number = e.current_step
        WHERE e.status = 'active'
          AND e.next_send_at <= NOW()
          AND st.is_active = 1
        ORDER BY e.next_send_at ASC
        LIMIT ?
    ");
    $stmt->execute([MAX_EMAILS_PER_RUN]);
    return $stmt->fetchAll();
}

/**
 * Acquire process lock
 */
function acquireLock(): bool {
    if (file_exists(LOCK_FILE)) {
        $lockAge = time() - filemtime(LOCK_FILE);
        if ($lockAge < 300) { // 5 minute lock timeout
            return false;
        }
        // Stale lock, remove it
        unlink(LOCK_FILE);
    }
    return file_put_contents(LOCK_FILE, getmypid()) !== false;
}

/**
 * Release process lock
 */
function releaseLock(): void {
    if (file_exists(LOCK_FILE)) {
        unlink(LOCK_FILE);
    }
}

// ============================================================
// MAIN EXECUTION
// ============================================================

dripLog('INFO', 'OD9 Drip Processor starting...');

// Acquire lock
if (!acquireLock()) {
    dripLog('WARNING', 'Another instance is already running. Exiting.');
    exit(0);
}

try {
    $pdo = getDripDB();

    // Get due enrollments
    $enrollments = getDueEnrollments();
    $count = count($enrollments);

    if ($count === 0) {
        dripLog('INFO', 'No emails due for processing.');
        releaseLock();
        exit(0);
    }

    dripLog('INFO', "Processing {$count} due email(s)...");

    $sent = 0;
    $failed = 0;
    $skipped = 0;

    foreach ($enrollments as $enrollment) {
        $enrollmentId = $enrollment['enrollment_id'];
        $discordUserId = $enrollment['discord_user_id'];
        $templateName = $enrollment['template_name'];
        $subject = $enrollment['subject'];
        $currentStep = $enrollment['current_step'];
        $totalSteps = $enrollment['total_steps'];
        $nextDelayHours = $enrollment['next_delay_hours'] ?? 24;
        $stepId = $enrollment['step_id'];

        dripLog('INFO', "Processing enrollment #{$enrollmentId}", [
            'user' => $discordUserId,
            'sequence' => $enrollment['sequence_slug'],
            'step' => "{$currentStep}/{$totalSteps}"
        ]);

        // Get member data
        $member = getMemberData($discordUserId);
        if (!$member || empty($member['email'])) {
            dripLog('WARNING', "No email found for user {$discordUserId}, skipping");
            logDripEmail($enrollmentId, $stepId, '', $subject, 'skipped', 'No email address found');
            updateEnrollment($enrollmentId, $currentStep, $totalSteps, $nextDelayHours);
            $skipped++;
            continue;
        }

        $email = $member['email'];

        // Load template
        $template = loadTemplate($templateName);
        if (!$template) {
            dripLog('ERROR', "Template not found: {$templateName}");
            logDripEmail($enrollmentId, $stepId, $email, $subject, 'failed', 'Template not found: ' . $templateName);
            $failed++;
            continue;
        }

        // Process template
        $htmlBody = processTemplate($template, $member, [
            'sequence_name' => $enrollment['sequence_name'],
            'step_number' => $currentStep,
            'total_steps' => $totalSteps
        ]);

        // Process subject placeholders too
        $processedSubject = processTemplate($subject, $member);

        // Send email
        $success = sendEmail($email, $processedSubject, $htmlBody);

        if ($success) {
            dripLog('INFO', "Email sent to {$email}", ['subject' => $processedSubject]);
            logDripEmail($enrollmentId, $stepId, $email, $processedSubject, 'sent');
            updateEnrollment($enrollmentId, $currentStep, $totalSteps, $nextDelayHours);
            $sent++;
        } else {
            dripLog('ERROR', "Failed to send email to {$email}");
            logDripEmail($enrollmentId, $stepId, $email, $processedSubject, 'failed', 'Email send failed');
            $failed++;
        }

        // Small delay between sends to avoid rate limits
        usleep(100000); // 100ms
    }

    dripLog('INFO', "Processing complete", [
        'sent' => $sent,
        'failed' => $failed,
        'skipped' => $skipped,
        'total' => $count
    ]);

} catch (PDOException $e) {
    dripLog('ERROR', "Database error: " . $e->getMessage());
    exit(1);
} catch (Exception $e) {
    dripLog('ERROR', "Unexpected error: " . $e->getMessage());
    exit(1);
} finally {
    releaseLock();
}

exit(0);
