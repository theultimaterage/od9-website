<?php
/**
 * LoveLogic Weekly Broadcast Email Sender
 *
 * Sends the week-N email from /email-templates/lovelogic-weekly/ to all
 * verified, opted-in subscribers in email_signups. Idempotent per
 * (recipient, week_N) via the email_log.campaign column.
 *
 * Cron (Mondays 9 AM CT == 14:00 UTC):
 *   0 14 * * 1 /usr/bin/php /home/offda9/public_html/api/drip/weekly_lovelogic_sender.php >> /home/offda9/logs/lovelogic_sender.log 2>&1
 *
 * CLI flags:
 *   --week=N           Override calculated week number (1..33)
 *   --dry-run          Print plan, don't send
 *   --test-email=ADDR  Send only to ADDR (skips subscriber list, skips idempotency log)
 *
 * Local exim handles delivery via PHP mail(). SPF/DKIM/DMARC are configured for
 * offda9.com so deliverability should be solid out of the gate.
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die("CLI only.\n");
}

// Unified mailer — Brevo SMTP (port 2525) on prod, Mailtrap sandbox locally.
// Replaces the old raw mail()/exim path so the weekly broadcast is
// Brevo-DKIM-signed (was going out unsigned via GoDaddy → spam).
$_mailLib = __DIR__ . '/../../includes/mail.php';
if (!file_exists($_mailLib)) $_mailLib = __DIR__ . '/../../../includes/mail.php';
require_once $_mailLib;

// ---- Config ----
const LAUNCH_DATE = '2026-04-14';   // Monday week-1 send date per _EMAIL_CAMPAIGN_SUMMARY.md
const TOTAL_WEEKS = 33;
const FROM_EMAIL = 'noreply@offda9.com';
const FROM_NAME  = 'The OD9 Movement';
const REPLY_TO   = 'contact@offda9.com';
const TEMPLATE_DIR = '/home/offda9/public_html/email-templates/lovelogic-weekly/';

$DB_HOST = getenv('OD9_DB_HOST') ?: 'localhost';
$DB_NAME = getenv('OD9_DB_NAME') ?: 'offda9_od9_tickets';
$DB_USER = getenv('OD9_DB_USER') ?: 'offda9_od9admin';
$DB_PASS = getenv('OD9_DB_PASS') ?: '';

// ---- CLI args ----
$opts = getopt('', ['week::', 'dry-run', 'test-email::']);
$forceWeek = isset($opts['week']) && $opts['week'] !== false ? (int)$opts['week'] : null;
$dryRun    = isset($opts['dry-run']);
$testEmail = $opts['test-email'] ?? null;

// ---- Compute current week ----
$launch = new DateTime(LAUNCH_DATE);
$now    = new DateTime('now');
$daysSinceLaunch = (int)$launch->diff($now)->days * ($launch <= $now ? 1 : -1);
$autoWeek = ($daysSinceLaunch >= 0)
    ? min(TOTAL_WEEKS, (int)floor($daysSinceLaunch / 7) + 1)
    : 0;
$week = $forceWeek ?? $autoWeek;
if ($week < 1 || $week > TOTAL_WEEKS) {
    fwrite(STDERR, "[broadcast] Week $week is out of range [1, " . TOTAL_WEEKS . "]\n");
    exit(1);
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
$html    = wrapEmailHtml(mdToHtml($body), $subject, $week);

// ---- Recipients ----
$pdo = null;
if ($testEmail) {
    $recipients = [['id' => 0, 'email' => $testEmail, 'first_name' => null, 'name' => null]];
    echo "[broadcast] TEST MODE - sending only to $testEmail (skipping db log)\n";
} else {
    $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
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
    // Send via the unified Brevo-signed mailer (Brevo sets Return-Path +
    // DKIM-signs, keeping SPF/DKIM/DMARC aligned at recipients).
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

function wrapEmailHtml(string $bodyHtml, string $subject, int $week): string {
    $safeSubject = htmlspecialchars($subject, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{$safeSubject}</title>
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&family=Rajdhani:wght@400;500;600&display=swap" rel="stylesheet">
<style>
  /* Rich element styling for the markdown body. Structural bg/text colors are
     ALSO set inline below, so a client that strips this block still renders
     light-on-dark (never light-on-white). */
  body { margin:0; padding:0; background:#0D0D0D;
         font-family:'Rajdhani',-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;
         color:#CCCCCC; line-height:1.6; }
  .body h1,.body h2,.body h3 { font-family:'Orbitron','Segoe UI',sans-serif; line-height:1.25; }
  .body h1 { font-size:24px; color:#00BFFF; }
  .body h2 { font-size:20px; margin-top:32px; color:#00BFFF; }
  .body h3 { font-size:16px; color:#FFFFFF; }
  .body a { color:#00BFFF; text-decoration:none; }
  .body strong { color:#FFFFFF; }
  .body hr { border:0; border-top:1px solid #333; margin:24px 0; }
  .body code { background:#0D0D0D; color:#00BFFF; padding:2px 6px; border-radius:3px; font-size:90%; }
  .body ul { padding-left:24px; }
  .body blockquote { border-left:3px solid #00BFFF; padding:8px 16px; margin:16px 0;
                     background:#13202B; color:#E0E0E0; font-style:italic; }
  .footer a { color:#00BFFF; text-decoration:none; }
</style>
</head>
<body style="margin:0;padding:0;background:#0D0D0D;font-family:'Rajdhani',-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#0D0D0D;">
<tr><td align="center" style="padding:40px 20px;">
<table role="presentation" width="640" cellpadding="0" cellspacing="0" style="width:640px;max-width:640px;background:#1A1A1A;border:1px solid #00BFFF;border-radius:8px;">
<tr><td style="padding:24px 30px;text-align:center;border-bottom:2px solid #00BFFF;">
<h1 style="font-family:'Orbitron','Segoe UI',sans-serif;color:#00BFFF;margin:0;font-size:28px;letter-spacing:2px;">OD9</h1>
<p style="color:#777;margin:6px 0 0;font-size:13px;">LoveLogic &middot; Week {$week} of 33</p>
</td></tr>
<tr><td class="body" style="padding:36px 30px;background:#1A1A1A;color:#CCCCCC;font-size:16px;line-height:1.6;">
{$bodyHtml}
</td></tr>
<tr><td class="footer" style="padding:20px 30px;background:#0D0D0D;border-top:1px solid #333;text-align:center;font-size:12px;color:#666;line-height:1.6;">
You're receiving this because you signed up for OD9 weekly emails at <a href="https://offda9.com" style="color:#00BFFF;text-decoration:none;">offda9.com</a>.<br>
<a href="https://offda9.com/unsubscribe.php?email={{EMAIL}}" style="color:#00BFFF;text-decoration:none;">Unsubscribe</a>
&nbsp;&middot;&nbsp; OD9 LLC, Auburn-Gresham, Chicago
</td></tr>
</table>
</td></tr>
</table>
</body>
</html>
HTML;
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
