<?php
/**
 * LoveLogic Weekly Broadcast Email Sender
 *
 * Sends the week-N email from /email-templates/lovelogic-weekly/ to all
 * verified, opted-in subscribers in email_signups. Idempotent per
 * (recipient, week_N) via the email_log.campaign column.
 *
 * Cron (Mondays 9 AM CT == 14:00 UTC), run as offda9 so it can read the
 * mode-600 mail secrets:
 *   0 14 * * 1 sudo -u offda9 /opt/cpanel/ea-php82/root/usr/bin/php /home/offda9/public_html/api/drip/weekly_lovelogic_sender.php >> ... 2>&1
 *
 * CLI flags:
 *   --week=N           Override calculated week number (1..33)
 *   --dry-run          Print plan, don't send
 *   --test-email=ADDR  Send only to ADDR (skips subscriber list, skips idempotency log)
 *
 * The body renders through od9_email_layout() — the same branded shell the drip
 * sender uses — and ships via the unified od9_send_mail() (SMTP+DKIM on prod,
 * Mailtrap capture on local). One design source; no per-sender chrome.
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die("CLI only.\n");
}

// ---- Config ----
const LAUNCH_DATE = '2026-06-01';   // First Monday send date (reset 2026-05-25)
const TOTAL_WEEKS = 33;
const FROM_EMAIL = 'noreply@offda9.com';
const FROM_NAME  = 'The OD9 Movement';
const REPLY_TO   = 'contact@offda9.com';
// Prod docroot path; local XAMPP keeps the templates beside this script
// (sync-local mirrors scripts/web/email-templates/ -> htdocs/od9/). The
// fallback makes the sender locally testable (--test-email) instead of
// prod-only.
$_tplDir = '/home/offda9/public_html/email-templates/lovelogic-weekly/';
if (!is_dir($_tplDir)) $_tplDir = __DIR__ . '/email-templates/lovelogic-weekly/';
define('TEMPLATE_DIR', $_tplDir);

// Shared DB config (defines DB_HOST, DB_NAME, DB_USER, DB_PASS, DB_PORT constants).
// getenv() doesn't see the shell's env vars under cron — use the canonical config.
// Path fallback: prod deploys this script to api/drip/ (../../config) but local
// XAMPP keeps it at htdocs root (./config).
$_dbConfig = __DIR__ . '/../../config/database.php';
if (!file_exists($_dbConfig)) $_dbConfig = __DIR__ . '/config/database.php';
require_once $_dbConfig;

// Unified mailer (od9_send_mail): Mailtrap on local-XAMPP, SMTP+DKIM on prod.
$_mailHelper = __DIR__ . '/../../includes/mail.php';
if (!file_exists($_mailHelper)) $_mailHelper = __DIR__ . '/includes/mail.php';
require_once $_mailHelper;

// Shared branded email shell (od9_email_layout) — the one design source.
$_layoutLib = __DIR__ . '/../../includes/email_layout.php';
if (!file_exists($_layoutLib)) $_layoutLib = __DIR__ . '/includes/email_layout.php';
require_once $_layoutLib;

// ---- CLI args ----
$opts = getopt('', ['week::', 'dry-run', 'test-email::']);
$forceWeek = isset($opts['week']) && $opts['week'] !== false ? (int)$opts['week'] : null;
$dryRun    = isset($opts['dry-run']);
$testEmail = $opts['test-email'] ?? null;

// ---- Compute current week ----
$launch = new DateTime(LAUNCH_DATE);
$now    = new DateTime('now');
$daysSinceLaunch = (int)$launch->diff($now)->days * ($launch <= $now ? 1 : -1);
// No clamp (2026-07-11): min(TOTAL_WEEKS, ...) made every post-calendar Monday
// re-send week 33 forever. Past the calendar, $autoWeek exceeds TOTAL_WEEKS and
// the range check below exits cleanly (heartbeat still written by the cron
// wrapper); --week can still force a re-send of any past week.
$autoWeek = ($daysSinceLaunch >= 0)
    ? (int)floor($daysSinceLaunch / 7) + 1
    : 0;
$week = $forceWeek ?? $autoWeek;
if ($week < 1 || $week > TOTAL_WEEKS) {
    if ($forceWeek !== null) {  // operator typo on --week: loud failure
        fwrite(STDERR, "[broadcast] Forced week $week is out of range [1, " . TOTAL_WEEKS . "]\n");
        exit(1);
    }
    fwrite(STDERR, "[broadcast] Week $week is outside the calendar — complete or pre-launch; nothing to send.\n");
    exit(0);
}

// ---- Locate template ----
$pattern = sprintf('email-week-%02d-*.md', $week);
$matches = glob(TEMPLATE_DIR . $pattern);
if (empty($matches)) {
    fwrite(STDERR, "[broadcast] No template found for week $week (pattern: $pattern)\n");
    exit(2);
}
$templateFile = $matches[0];
echo "[broadcast] week=$week template=" . basename($templateFile) . "\n";

// ---- Parse + render ----
$raw = file_get_contents($templateFile);
[$meta, $body] = parseTemplate($raw);
$subject = $meta['Subject'] ?? "OD9 Weekly: Week $week";
// Render the markdown body into the shared branded shell. {{EMAIL}} in the
// layout footer's unsubscribe link is resolved per-recipient by personalize().
$html = od9_email_layout(mdToHtml($body), [
    'title'        => $subject,
    'transmission' => sprintf('%02d / %d', $week, TOTAL_WEEKS),
]);

// ---- Recipients ----
$pdo = null;
if ($testEmail) {
    $recipients = [['id' => 0, 'email' => $testEmail, 'first_name' => null, 'name' => null]];
    echo "[broadcast] TEST MODE - sending only to $testEmail (skipping db log)\n";
} else {
    $pdo = getDatabaseConnection();   // canonical connection (same as the drip sender)
    ensureCampaignColumn($pdo);
    $stmt = $pdo->query(
        "SELECT id, email, first_name, name FROM email_signups
         WHERE status = 'active' AND email_opt_in = 1 AND is_verified = 1"
    );
    $recipients = $stmt->fetchAll();
    echo "[broadcast] " . count($recipients) . " confirmed subscribers eligible\n";
}

if ($dryRun) {
    echo "[broadcast] DRY-RUN: subject={$subject}\n";
    echo "[broadcast] DRY-RUN: html_chars=" . strlen($html) . "\n";
    echo "[broadcast] DRY-RUN: recipients=" . count($recipients) . "\n";
    exit(0);
}

$campaign = sprintf('lovelogic_week_%02d', $week);
$sent = 0; $failed = 0; $skipped = 0;

foreach ($recipients as $r) {
    if (!$testEmail && wasAlreadySent($pdo, $r['email'], $campaign)) {
        $skipped++;
        continue;
    }
    $personalized = personalize($html, $r);
    // od9_send_mail supplies List-Unsubscribe-Post, X-Mailer, and Message-ID
    // itself, and handles SMTP+DKIM (prod) / Mailtrap capture (local).
    $unsub = '<https://offda9.com/unsubscribe.php?email=' . rawurlencode($r['email'])
           . '>, <mailto:contact@offda9.com?subject=unsubscribe>';
    $ok = od9_send_mail($r['email'], $subject, $personalized, [
        'from_email'       => FROM_EMAIL,
        'from_name'        => FROM_NAME,
        'reply_to'         => REPLY_TO,
        'list_unsubscribe' => $unsub,
    ]);
    if ($ok) {
        $sent++;
        if (!$testEmail) logSend($pdo, $r['email'], $subject, $campaign, 'sent', null);
    } else {
        $failed++;
        if (!$testEmail) logSend($pdo, $r['email'], $subject, $campaign, 'failed', error_get_last()['message'] ?? 'unknown');
    }
}

echo "[broadcast] sent=$sent failed=$failed skipped=$skipped (campaign=$campaign)\n";

// ============================================================
// Helpers
// ============================================================

function parseTemplate(string $raw): array {
    $meta = [];
    if (preg_match('/^---\s*\n(.*?)\n---\s*\n/s', $raw, $m)) {
        foreach (explode("\n", $m[1]) as $line) {
            if (preg_match('/^\*\*([\w\s]+):\*\*\s*(.+)$/', $line, $kv)) {
                $meta[trim($kv[1])] = trim($kv[2]);
            }
        }
        $body = preg_replace('/^---\s*\n.*?\n---\s*\n/s', '', $raw, 1);
    } else {
        $body = $raw;
    }
    return [$meta, ltrim($body)];
}

/**
 * Tiny markdown -> HTML. Handles headings, bold, italic, code, links, lists,
 * paragraphs, hr. Sufficient for the LoveLogic emails which use simple MD.
 */
function mdToHtml(string $md): string {
    $md = str_replace("\r\n", "\n", $md);
    // Escape HTML first so we don't accidentally render user MD as HTML
    $h = htmlspecialchars($md, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    // Horizontal rules
    $h = preg_replace('/^---\s*$/m', '<hr>', $h);
    // Headings (longest first)
    $h = preg_replace('/^####\s+(.+)$/m', '<h4>$1</h4>', $h);
    $h = preg_replace('/^###\s+(.+)$/m',  '<h3>$1</h3>', $h);
    $h = preg_replace('/^##\s+(.+)$/m',   '<h2>$1</h2>', $h);
    $h = preg_replace('/^#\s+(.+)$/m',    '<h1>$1</h1>', $h);
    // Bold + italic (bold first - both use *)
    $h = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $h);
    $h = preg_replace('/(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/s', '<em>$1</em>', $h);
    // Inline code
    $h = preg_replace('/`(.+?)`/', '<code>$1</code>', $h);
    // Links: [label](url)
    $h = preg_replace_callback('/\[(.+?)\]\(([^)]+)\)/', function ($m) {
        $label = $m[1];
        $url = $m[2];
        // Allow http/https/mailto only
        if (!preg_match('#^(https?:|mailto:)#i', $url)) {
            return $m[0];
        }
        return '<a href="' . $url . '">' . $label . '</a>';
    }, $h);
    // Bullet lists
    $h = preg_replace('/^[\-\*]\s+(.+)$/m', '<li>$1</li>', $h);
    $h = preg_replace('/(<li>.+<\/li>)(?:\n(<li>.+<\/li>))+/s', "<ul>\n$0\n</ul>", $h);
    // Blockquotes - lines starting with "> " (works on the escaped &gt; that
    // htmlspecialchars produced earlier in this function)
    $h = preg_replace('/^&gt;\s?(.*)$/m', '<blockquote>$1</blockquote>', $h);
    // Coalesce consecutive blockquote lines
    $h = preg_replace('/<\/blockquote>\n<blockquote>/', "\n", $h);
    // Paragraphs - wrap blocks separated by blank lines
    $blocks = preg_split('/\n{2,}/', $h);
    $blocks = array_map(function ($b) {
        $b = trim($b);
        if ($b === '') return '';
        if (preg_match('/^<(h\d|ul|ol|li|blockquote|hr|pre|table|div)/i', $b)) return $b;
        return '<p>' . str_replace("\n", '<br>', $b) . '</p>';
    }, $blocks);
    return implode("\n\n", array_filter($blocks));
}

function personalize(string $html, array $r): string {
    $name = $r['first_name'] ?? $r['name'] ?? 'Friend';
    return str_replace(
        ['{{NAME}}', '{{FIRST_NAME}}', '{{EMAIL}}'],
        [
            htmlspecialchars($name, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            htmlspecialchars($name, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            urlencode($r['email']),
        ],
        $html
    );
}

function ensureCampaignColumn(PDO $pdo): void {
    // Add a campaign column to email_log if missing - used for idempotent
    // per-campaign-per-recipient delivery checking.
    try {
        $cols = $pdo->query("SHOW COLUMNS FROM email_log LIKE 'campaign'")->fetchAll();
        if (empty($cols)) {
            $pdo->exec("ALTER TABLE email_log
                ADD COLUMN campaign VARCHAR(100) DEFAULT NULL,
                ADD INDEX idx_campaign_recipient (campaign, recipient)");
        }
    } catch (Exception $e) {
        fwrite(STDERR, "[broadcast] WARN: could not ensure campaign column: " . $e->getMessage() . "\n");
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
    try {
        $stmt = $pdo->prepare(
            "INSERT INTO email_log (recipient, subject, status, sent_at, error_message, campaign)
             VALUES (?, ?, ?, NOW(), ?, ?)"
        );
        $stmt->execute([$email, $subject, $status, $err, $campaign]);
    } catch (Exception $e) {
        fwrite(STDERR, "[broadcast] WARN: log insert failed: " . $e->getMessage() . "\n");
    }
}
