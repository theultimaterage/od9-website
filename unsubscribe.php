<?php
/**
 * OD9 One-Click Unsubscribe Handler
 *
 * Honors RFC 8058 List-Unsubscribe-Post for one-click unsubscribe.
 * Both GET (link click in email) and POST (mail-client one-click) are supported.
 *
 * Sets email_signups.status = 'unsubscribed' and email_signups.email_opt_in = 0
 * so the weekly broadcast sender skips this address. Existing email_log
 * history is preserved.
 *
 * URL: https://offda9.com/unsubscribe.php?email=<email>[&token=<token>]
 *
 * Token is optional. If email_signups.verification_token is set we require it
 * (prevents random unsubscribes by URL-guessing); otherwise we accept the
 * email alone (legacy subscribers who never verified).
 */

// Use the central hardened connection (ERRMODE_EXCEPTION + EMULATE_PREPARES=false),
// same as verify.php — don't hand-roll a divergent PDO (security-audit consistency).
$configPath = __DIR__ . '/config/database.php';
if (!file_exists($configPath)) $configPath = __DIR__ . '/../config/database.php';
require_once $configPath;

$email = trim($_REQUEST['email'] ?? '');
$token = trim($_REQUEST['token'] ?? '');
$ok = false;
$message = '';

if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
    try {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare("SELECT id, verification_token, status FROM email_signups WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            $message = "We couldn't find $email in our list - it may already be removed.";
        } elseif ($row['status'] === 'unsubscribed') {
            $message = "$email was already unsubscribed.";
            $ok = true;
        } elseif (!empty($row['verification_token']) && $token !== $row['verification_token']) {
            $message = "Invalid unsubscribe link. Please reply to the most recent OD9 email and we'll unsubscribe you manually.";
        } else {
            $up = $pdo->prepare("UPDATE email_signups SET status = 'unsubscribed', email_opt_in = 0 WHERE id = ?");
            $up->execute([$row['id']]);
            $message = "$email has been unsubscribed. We're sorry to see you go.";
            $ok = true;
        }
    } catch (Exception $e) {
        $message = "Sorry - something went wrong on our end. Please reply to the most recent OD9 email and we'll handle it manually.";
        error_log("[unsubscribe] " . $e->getMessage());
    }
} else {
    $message = "Please provide a valid email address.";
}

// One-click POST: respond 200 OK with no body (per RFC 8058)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['List-Unsubscribe'])) {
    http_response_code(200);
    exit;
}

// Otherwise render a confirmation page
header('Content-Type: text/html; charset=utf-8');
$safeMessage = htmlspecialchars($message, ENT_QUOTES | ENT_HTML5, 'UTF-8');
$icon = $ok ? '✓' : '⚠';
$bg = $ok ? '#0D0D0D' : '#0D0D0D';
$page_title = 'OD9 - Unsubscribe';
$page_description = '';
$page_slug = 'unsubscribe.php';
$page_robots = 'noindex';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include __DIR__ . '/includes/head.php'; ?>
<style>
body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
         background: <?= $bg ?>; color: #fafafa; margin: 0; min-height: 100vh;
         display: flex; align-items: center; justify-content: center; }
  .card { max-width: 520px; padding: 48px 32px; text-align: center; }
  .icon { font-size: 64px; color: #00BFFF; margin-bottom: 16px; }
  p { font-size: 18px; line-height: 1.5; }
  a { color: #00BFFF; }
</style>
</head>
<body>
<div class="card">
  <div class="icon"><?= $icon ?></div>
  <p><?= $safeMessage ?></p>
  <p><a href="https://offda9.com">← Back to offda9.com</a></p>
</div>
</body>
</html>
