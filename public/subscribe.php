<?php
/**
 * OD9 Email Subscribe Handler — Double Opt-In Edition
 *
 * Handles signups from:
 *  - Homepage popup form (#popupSignupForm)
 *  - Homepage inline CTA form (#inlineSignupForm)
 *  - Future /wake-up landing pages
 *
 * Flow:
 *  1. Validate POST input (email + optional name).
 *  2. Insert into email_signups with is_verified=0, email_opt_in=0,
 *     verification_token=<32-hex>.
 *  3. Send a verification email containing
 *     https://offda9.com/verify.php?email=...&token=...
 *  4. The user clicks the link -> verify.php flips is_verified=1 and
 *     email_opt_in=1, and only THEN does weekly_lovelogic_sender.php pick
 *     them up (it filters WHERE is_verified=1 AND email_opt_in=1).
 *
 * Re-subscribes (email already exists in active state) skip step 2 and just
 * tell the user they're already on the list. If they previously
 * unsubscribed, we resend a fresh verification email.
 *
 * Best-effort sync to a shared_platform.email_subscribers table is kept
 * (catches PDOException quietly) for forward compatibility with the
 * cross-tenant platform.
 */

$configPath = __DIR__ . '/../config/database.php';
if (!file_exists($configPath)) $configPath = __DIR__ . '/config/database.php';
require_once $configPath;

// Unified mailer — Brevo SMTP on prod, Mailtrap sandbox on local. Resolves
// whether includes/ sits beside this file (prod public_html) or one up (repo).
$_mailLib = __DIR__ . '/includes/mail.php';
if (!file_exists($_mailLib)) $_mailLib = __DIR__ . '/../includes/mail.php';
require_once $_mailLib;

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// ---- Rate limit: 5 signups / IP / hour ----
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$rateLimitFile = sys_get_temp_dir() . '/od9_subscribe_' . md5($ip) . '.json';
$now = time();
$rateData = file_exists($rateLimitFile)
    ? array_filter(json_decode(file_get_contents($rateLimitFile), true) ?? [], fn($ts) => ($now - $ts) < 3600)
    : [];
if (count($rateData) >= 5) {
    http_response_code(429);
    echo json_encode(['success' => false, 'error' => 'Too many requests. Please try again later.']);
    exit;
}

// ---- Parse input (JSON or form) ----
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
$input = (stripos($contentType, 'application/json') !== false)
    ? (json_decode(file_get_contents('php://input'), true) ?? [])
    : $_POST;

$name = trim($input['name'] ?? '');
$email = strtolower(trim($input['email'] ?? ''));
$source = trim($input['source'] ?? 'website');
$referrer = trim($input['referrer'] ?? ($_SERVER['HTTP_REFERER'] ?? ''));
$context = trim($input['context'] ?? '');
// Optional: Discord user ID for the /link_email command path. Stored on
// email_signups.discord_user_id so event_processor.php can resolve email
// from a Discord user ID without needing a Patreon link.
$discordUserId = trim($input['discord_user_id'] ?? '');
if ($discordUserId !== '' && !preg_match('/^\d{15,25}$/', $discordUserId)) {
    $discordUserId = '';  // ignore malformed values
}

if (!$email) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Email is required.']);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Please enter a valid email address.']);
    exit;
}

$nameParts = explode(' ', $name, 2);
$firstName = $nameParts[0] ?? '';
$lastName  = $nameParts[1] ?? '';
$allowedSources = ['website', 'homepage-popup', 'homepage-inline', 'join-page', 'wake-up', 'ncz', 'discord', 'discord_link'];
if (!in_array($source, $allowedSources, true)) $source = 'website';

try {
    $pdo = getDatabaseConnection();

    $check = $pdo->prepare("SELECT id, is_verified, email_opt_in, status FROM email_signups WHERE email = ?");
    $check->execute([$email]);
    $existing = $check->fetch();

    $needsVerificationEmail = false;
    $token = bin2hex(random_bytes(16)); // 32 hex chars

    if ($existing) {
        if ($existing['is_verified'] && $existing['email_opt_in'] && $existing['status'] === 'active') {
            // Fully signed up already - but if Discord ID is new, attach it
            if ($discordUserId !== '') {
                $pdo->prepare("UPDATE email_signups SET discord_user_id = COALESCE(discord_user_id, ?) WHERE id = ?")
                    ->execute([$discordUserId, $existing['id']]);
            }
            echo json_encode([
                'success' => true,
                'message' => "You're already on the list. We see you.",
                'already_subscribed' => true,
                'needs_verification' => false,
            ]);
            exit;
        }
        // Resubscribe / verify-resend path: keep id, refresh token + first_name + source + discord_user_id
        $up = $pdo->prepare(
            "UPDATE email_signups
             SET first_name = COALESCE(NULLIF(?, ''), first_name),
                 last_name  = COALESCE(NULLIF(?, ''), last_name),
                 signup_source = ?,
                 verification_token = ?,
                 discord_user_id = COALESCE(NULLIF(?, ''), discord_user_id),
                 status = 'active',
                 is_verified = 0,
                 email_opt_in = 0
             WHERE id = ?"
        );
        $up->execute([$firstName, $lastName, $source, $token, $discordUserId, $existing['id']]);
        $needsVerificationEmail = true;
    } else {
        // Brand new
        $ins = $pdo->prepare(
            "INSERT INTO email_signups
                (email, first_name, last_name, signup_source, ip_address, referrer_url,
                 is_verified, email_opt_in, status, verification_token, discord_user_id, created_at)
             VALUES (?, ?, ?, ?, ?, ?, 0, 0, 'active', ?, NULLIF(?, ''), NOW())"
        );
        $ins->execute([$email, $firstName, $lastName, $source, $ip, $referrer, $token, $discordUserId]);
        $needsVerificationEmail = true;
    }

    // ---- Send verification email via local exim ----
    if ($needsVerificationEmail) {
        $sent = sendVerificationEmail($email, $firstName, $token);
        if (!$sent) {
            // Log but don't fail the user-facing response - they're in the DB,
            // they just don't have the email yet. Manual recovery is possible.
            error_log("[subscribe] verification email send failed for $email");
        }
    }

    // ---- Best-effort sync to shared_platform (kept from prior impl) ----
    try {
        $spConfigPath = __DIR__ . '/../config/sp-credentials.php';
        if (!file_exists($spConfigPath)) $spConfigPath = __DIR__ . '/config/sp-credentials.php';
        if (file_exists($spConfigPath)) {
            $spCreds = require $spConfigPath;
            $spPdo = new PDO($spCreds['dsn'], $spCreds['user'], $spCreds['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            $spCheck = $spPdo->prepare("SELECT id, status FROM email_subscribers WHERE site_id = 'od9' AND email = ?");
            $spCheck->execute([$email]);
            $spExisting = $spCheck->fetch();
            if (!$spExisting) {
                $customFields = !empty($context) ? json_encode(['context' => $context]) : null;
                $spIns = $spPdo->prepare(
                    "INSERT INTO email_subscribers
                        (site_id, email, first_name, last_name, status, source, custom_fields, subscribed_at)
                     VALUES ('od9', ?, ?, ?, 'pending_verification', 'signup', ?, NOW())"
                );
                $spIns->execute([$email, $firstName, $lastName, $customFields]);
            }
        }
    } catch (PDOException $e) {
        error_log("[subscribe] shared-platform sync failed: " . $e->getMessage());
    }

    // ---- Record rate limit hit ----
    $rateData[] = $now;
    file_put_contents($rateLimitFile, json_encode($rateData));

    echo json_encode([
        'success' => true,
        'message' => "Almost in. Check your email and click the link to confirm your subscription.",
        'already_subscribed' => false,
        'needs_verification' => true,
    ]);

} catch (PDOException $e) {
    error_log("[subscribe] error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Something went wrong. Please try again.']);
}

// ============================================================
// Helpers
// ============================================================

function sendVerificationEmail(string $email, string $firstName, string $token): bool {
    $name = $firstName ?: 'Friend';
    $verifyUrl = 'https://offda9.com/verify.php?email=' . urlencode($email) . '&token=' . $token;
    $safeName = htmlspecialchars($name, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $safeUrl  = htmlspecialchars($verifyUrl, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    $subject = 'Confirm your OD9 subscription';

    $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Confirm your OD9 subscription</title>
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&family=Rajdhani:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body style="margin:0;padding:0;background-color:#0D0D0D;font-family:'Rajdhani',-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#0D0D0D;">
<tr><td align="center" style="padding:40px 20px;">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width:600px;max-width:600px;background-color:#1A1A1A;border-radius:8px;border:1px solid #00BFFF;">
<tr><td style="padding:30px;text-align:center;border-bottom:2px solid #00BFFF;">
<h1 style="font-family:'Orbitron','Segoe UI',sans-serif;color:#00BFFF;margin:0;font-size:32px;letter-spacing:2px;">OD9</h1>
<p style="color:#777;margin:10px 0 0;font-size:14px;">Off Da 9 &middot; The Movement</p>
</td></tr>
<tr><td style="padding:40px 30px;">
<h2 style="font-family:'Orbitron','Segoe UI',sans-serif;color:#FFFFFF;margin:0 0 20px;font-size:24px;">One more step, {$safeName}</h2>
<p style="color:#CCCCCC;font-size:16px;line-height:1.6;margin:0 0 20px;">Click the button below to confirm you actually want OD9 weekly emails. Without this confirmation we won't send you anything &mdash; no spam, ever.</p>
<table role="presentation" cellpadding="0" cellspacing="0" style="margin:28px 0;"><tr>
<td style="background:linear-gradient(135deg,#00BFFF,#0099CC);border-radius:6px;">
<a href="{$safeUrl}" style="display:inline-block;padding:14px 32px;font-family:'Orbitron','Segoe UI',sans-serif;color:#0D0D0D;text-decoration:none;font-weight:bold;font-size:14px;letter-spacing:1px;">CONFIRM SUBSCRIPTION</a>
</td></tr></table>
<p style="color:#888;font-size:13px;line-height:1.6;word-break:break-all;margin:0 0 20px;">Or paste this link into your browser:<br><a href="{$safeUrl}" style="color:#00BFFF;text-decoration:none;">{$safeUrl}</a></p>
<p style="color:#888;font-size:14px;line-height:1.6;margin:0;">If you didn't sign up, just ignore this email &mdash; you'll never hear from us again.</p>
</td></tr>
<tr><td style="padding:20px 30px;background-color:#0D0D0D;border-top:1px solid #333;text-align:center;">
<p style="color:#666;font-size:12px;margin:0;line-height:1.6;">OD9 LLC &middot; Auburn-Gresham, Chicago<br><a href="https://offda9.com" style="color:#00BFFF;text-decoration:none;">offda9.com</a></p>
</td></tr>
</table>
</td></tr>
</table>
</body>
</html>
HTML;

    $unsub = '<https://offda9.com/unsubscribe.php?email=' . rawurlencode($email)
           . '>, <mailto:contact@offda9.com?subject=unsubscribe>';
    return od9_send_mail($email, $subject, $html, [
        'from_email'       => 'noreply@offda9.com',
        'from_name'        => 'The OD9 Movement',
        'reply_to'         => 'contact@offda9.com',
        'list_unsubscribe' => $unsub,
    ]);
}
