<?php
/**
 * OD9 Email Subscription Verification Endpoint
 *
 * Lands at https://offda9.com/verify.php?email=X&token=Y from the link in
 * the verification email sent by subscribe.php.
 *
 * On match (email + token both present, token equals
 * email_signups.verification_token, status != 'unsubscribed'):
 *   - is_verified = 1
 *   - email_opt_in = 1
 *   - verified_at  = NOW()
 *   - verification_token = NULL  (one-time use)
 *
 * Renders a branded confirmation page either way. The weekly broadcast
 * sender filters WHERE is_verified=1 AND email_opt_in=1 AND status='active',
 * so verified subscribers start receiving the next weekly send.
 */

$configPath = __DIR__ . '/config/database.php';
if (!file_exists($configPath)) $configPath = __DIR__ . '/../config/database.php';
require_once $configPath;

$email = strtolower(trim($_GET['email'] ?? ''));
$token = trim($_GET['token'] ?? '');

$state = 'invalid';     // invalid | already | verified | error
$message = '';

if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL) || !$token || strlen($token) !== 32) {
    $state = 'invalid';
    $message = "This verification link looks malformed. Try the link in your email again, or sign up fresh on the homepage.";
} else {
    try {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare(
            "SELECT id, is_verified, status, verification_token, first_name, last_name
             FROM email_signups WHERE email = ? LIMIT 1"
        );
        $stmt->execute([$email]);
        $row = $stmt->fetch();

        if (!$row) {
            $state = 'invalid';
            $message = "We couldn't find $email in our records. If you just signed up and got this link, give it 30 seconds and try again.";
        } elseif ($row['status'] === 'unsubscribed') {
            $state = 'invalid';
            $message = "This address was previously unsubscribed. Sign up fresh on the homepage if you'd like back in.";
        } elseif ($row['is_verified'] && empty($row['verification_token'])) {
            $state = 'already';
            $message = "$email is already confirmed. You're on the list - the next weekly email arrives Monday at 9 AM CT.";
        } elseif (!hash_equals((string)$row['verification_token'], $token)) {
            $state = 'invalid';
            $message = "This verification link doesn't match what we have on record. Try the most recent confirmation email, or sign up fresh on the homepage.";
        } else {
            $up = $pdo->prepare(
                "UPDATE email_signups
                 SET is_verified = 1,
                     email_opt_in = 1,
                     verified_at = NOW(),
                     verification_token = NULL
                 WHERE id = ?"
            );
            $up->execute([$row['id']]);
            $state = 'verified';

            // Verification IS the consent event: mirror 'active' to the
            // platform audience. Upsert, so a fan whose signup-time sync was
            // missed still gets a complete platform row here (self-healing).
            require_once __DIR__ . '/includes/sp_audience_sync.php';
            od9_sp_audience_sync($email, $row['first_name'] ?? null, $row['last_name'] ?? null, 'active');

            require_once __DIR__ . '/includes/next_event.php';
            $nextRoom = od9_next_event_label();
            $message = "$email is confirmed. You're in. Next thing on the calendar: $nextRoom — pull up in the Discord, free to join. (The weekly email lands Mondays 9 AM CT.)";
        }
    } catch (PDOException $e) {
        error_log("[verify] " . $e->getMessage());
        $state = 'error';
        $message = "Something went wrong on our end. Reply to the verification email and we'll handle it manually.";
    }
}

http_response_code($state === 'verified' || $state === 'already' ? 200 : 400);
header('Content-Type: text/html; charset=utf-8');

$icon = $state === 'verified' ? '✓' : ($state === 'already' ? '✓' : '⚠');
$title = $state === 'verified' ? "Confirmed."
       : ($state === 'already'  ? "Already on the list."
       : ($state === 'error'    ? "Hmm." : "Couldn't verify."));
$safeMessage = htmlspecialchars($message, ENT_QUOTES | ENT_HTML5, 'UTF-8');
$safeTitle   = htmlspecialchars($title,   ENT_QUOTES | ENT_HTML5, 'UTF-8');

$page_title = 'OD9 - Email Confirmation';
$page_description = '';
$page_slug = 'verify.php';
$page_robots = 'noindex';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include __DIR__ . '/includes/head.php'; ?>
<style>
body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
         background: #0D0D0D; color: #fafafa; margin: 0; min-height: 100vh;
         display: flex; align-items: center; justify-content: center; }
  .card { max-width: 560px; padding: 48px 32px; text-align: center; }
  .icon { font-size: 72px; color: #00BFFF; line-height: 1; margin-bottom: 16px; }
  h1 { font-family: 'Orbitron', sans-serif; font-size: 28px; margin: 0 0 16px 0; color: #fff; letter-spacing: 1px; }
  p { font-size: 17px; line-height: 1.6; color: #ccc; margin-bottom: 24px; }
  a.btn { display: inline-block; padding: 12px 28px; background: #00BFFF; color: #0D0D0D !important;
          text-decoration: none; border-radius: 4px; font-weight: 700; letter-spacing: 1px;
          text-transform: uppercase; }
  a.btn:hover { box-shadow: 0 0 16px rgba(0, 191, 255, 0.7); }
  .links { margin-top: 32px; font-size: 14px; }
  .links a { color: #00BFFF; margin: 0 12px; text-decoration: none; }
</style>
</head>
<body>
<div class="card">
  <div class="icon"><?= $icon ?></div>
  <h1><?= $safeTitle ?></h1>
  <p><?= $safeMessage ?></p>
  <a class="btn" href="https://offda9.com/events.php">See What's On →</a>
  <div class="links">
    <a href="https://discord.gg/spgmrXVMWq">Discord</a> &middot;
    <a href="https://offda9.com">offda9.com</a> &middot;
    <a href="https://www.patreon.com/c/TheUltimateRage">Patreon</a> &middot;
    <a href="https://offda9.com/contact.php">Contact</a>
  </div>
</div>
</body>
</html>
