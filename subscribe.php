<?php
/**
 * OD9 Email Subscribe Handler — Double Opt-In Edition
 *
 * Handles signups from:
 *  - the popup every page carries (includes/email-popup.php, #popupSignupForm)
 *  - join.php (#joinPageForm) and wake-up.php (#wakeUpForm)
 *  - the Discord bot's /link_email and /subscribe_email (cogs/email_link.py in the bot)
 *
 * In front of every one of them, in this order (2026-11-28, piece 4 of the form checks;
 * includes/od9_form_guard.php says why each exists):
 *  1. the form guard, FormGuard::admit(): the trap, a page rendered in this session, a
 *     person's wait, the page's script proof and, while its keys are set, Cloudflare's
 *     human check. The bot carries a signed pass (od9_form_caller()), which skips
 *     Cloudflare's question and nothing else: it loads join.php and waits like a browser.
 *  2. what was typed is validated;
 *  3. the brake, SignupBrake::admit(): no new signup while the day's go unconfirmed;
 *  4. the hourly limit per visitor, od9_form_limit();
 * and only then the write and the mail. A refusal is told as one, in JSON:
 *  403 {"success":false,"error":<the guard's sentence>,"refused":<reason>}, 503 when the
 *  brake is on, 429 over the limit. tests/test_public_forms_use_the_guard.php holds the order.
 * Until 2026-11-28 only an hourly limit, counted per connection in temporary files, stood
 * in front of this handler, and every signup mails the address typed in.
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
 * Every subscription state change is mirrored (best-effort, never blocking
 * the fan-facing flow) to the F.R.E.S.H. platform's email_subscribers table
 * through includes/sp_audience_sync.php — signup as 'pending', verification
 * as 'active', unsubscribe as 'unsubscribed'.
 */

$configPath = __DIR__ . '/../config/database.php';
if (!file_exists($configPath)) $configPath = __DIR__ . '/config/database.php';
require_once $configPath;

// Unified mailer — Brevo/SMTP on prod, file sink (logs/mail-outbox/) on local.
// Resolves whether includes/ sits beside this file (prod public_html) or one up (repo).
$_mailLib = __DIR__ . '/includes/mail.php';
if (!file_exists($_mailLib)) $_mailLib = __DIR__ . '/../includes/mail.php';
require_once $_mailLib;

// Shared branded email chrome (same beside-or-up resolution as the mailer).
$_layoutLib = __DIR__ . '/includes/email_layout.php';
if (!file_exists($_layoutLib)) $_layoutLib = __DIR__ . '/../includes/email_layout.php';
require_once $_layoutLib;

header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/includes/od9_form_guard.php';   // includes/ is beside this file on the server and here
od9_form_guard_session();

/** A refusal, told as one: the guard's own sentence and its reason, then stop. */
function od9_subscribe_refuse(int $status, FormGuardRefused $e): void
{
    http_response_code($status);
    echo json_encode(['success' => false, 'error' => $e->getMessage(), 'refused' => $e->reason]);
    exit;
}

// ---- Parse input (JSON or form). The raw body is what a signed caller signed. ----
$rawBody = (string) file_get_contents('php://input');
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
$input = (stripos($contentType, 'application/json') !== false)
    ? (json_decode($rawBody, true) ?? [])
    : $_POST;
if (!is_array($input)) $input = [];

// ---- 1. The form guard (the bot's signed pass skips only Cloudflare's question) ----
try {
    FormGuard::admit('od9-subscribe', $_SESSION, $input, $_SERVER, null,
        od9_form_caller([OD9_FORM_CALLER_BOT], $rawBody, $_SERVER));
} catch (FormGuardRefused $e) { // silent-failure:ignore — FormGuard::admit() logged it before the throw; its sentence goes back to the visitor
    od9_subscribe_refuse(403, $e);
}

$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';   // the visitor: mod_remoteip restores it from Cloudflare's header
$text = static fn($v): string => is_string($v) ? trim($v) : '';   // an array or a number sent in a field is no text

// ---- 2. What was typed ----
$name = $text($input['name'] ?? '');
$email = strtolower($text($input['email'] ?? ''));
$source = $text($input['source'] ?? 'website');
$referrer = $text($input['referrer'] ?? ($_SERVER['HTTP_REFERER'] ?? ''));
$context = $text($input['context'] ?? '');
// Optional: Discord user ID for the /link_email command path. Stored on
// email_signups.discord_user_id so event_processor.php can resolve email
// from a Discord user ID without needing a Patreon link.
$discordUserId = $text($input['discord_user_id'] ?? '');
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

    // ---- 3. The brake: before the per-visitor limit, so a visitor turned away by it is told the truth ----
    try {
        SignupBrake::admit('od9-subscribe', $pdo, $_SERVER);
    } catch (FormGuardRefused $e) { // silent-failure:ignore — SignupBrake::admit() logged it before the throw; its sentence goes back to the visitor
        od9_subscribe_refuse(503, $e);
    }

    // ---- 4. The hourly limit per visitor, counted for a signup about to be stored ----
    try {
        od9_form_limit('od9-subscribe', $_SERVER);
    } catch (FormGuardRefused $e) { // silent-failure:ignore — od9_form_limit() logged it before the throw; its sentence goes back to the visitor
        od9_subscribe_refuse(429, $e);
    }

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

    // ---- Best-effort sync to the F.R.E.S.H. platform audience mirror ----
    // One transport for all three state-changing flows (subscribe/verify/
    // unsubscribe): includes/sp_audience_sync.php. An unverified signup
    // mirrors as 'pending' — the platform's send paths select only
    // status='active', so a fan becomes platform-mailable ONLY through
    // verify.php's 'active' upsert. (The old inline block here hardcoded
    // 'pending_verification', a value the platform enum never contained,
    // and silently lost 12 of 13 signups.)
    require_once __DIR__ . '/includes/sp_audience_sync.php';
    od9_sp_audience_sync($email, $firstName, $lastName, 'pending',
        !empty($context) ? ['context' => $context] : null);

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

    $inner = '<div style="font-family:\'Rajdhani\',Arial,sans-serif;font-size:12px;font-weight:700;letter-spacing:.26em;color:#00BFFF;text-transform:uppercase;">Account Activation</div>'
        . '<h1 style="font-family:\'Orbitron\',\'Arial Black\',Arial,sans-serif;font-weight:700;font-size:27px;line-height:1.18;letter-spacing:.01em;color:#FFFFFF;text-transform:uppercase;margin:13px 0 18px;">Confirm your email</h1>'
        . '<p style="font-family:\'Rajdhani\',Arial,sans-serif;font-size:16px;font-weight:500;line-height:1.62;color:#C0C0C0;margin:0 0 26px;">One more step, ' . $safeName . '. Confirm this address to activate your OD9 subscription and start receiving transmissions from the movement &mdash; the climb up the Kardashev scale through STEAM.</p>'
        . od9_email_button('Confirm Subscription', $verifyUrl)
        . '<p style="font-family:\'Rajdhani\',Arial,sans-serif;font-size:13px;font-weight:500;line-height:1.6;color:#888888;margin:24px 0 0;">This link expires in 24 hours. If the button doesn&rsquo;t work, paste this into your browser:<br><span style="color:#999999;word-break:break-all;">' . $safeUrl . '</span></p>'
        . '<p style="font-family:\'Rajdhani\',Arial,sans-serif;font-size:13px;font-weight:500;line-height:1.6;color:#888888;margin:14px 0 0;">Didn&rsquo;t sign up? Ignore this email &mdash; nothing happens without your tap.</p>';

    $html = od9_email_layout($inner, [
        'title'           => $subject,
        'preheader'       => 'One tap to confirm your OD9 subscription.',
        'unsubscribe_url' => 'https://offda9.com/unsubscribe.php?email=' . rawurlencode($email),
    ]);

    $unsub = '<https://offda9.com/unsubscribe.php?email=' . rawurlencode($email)
           . '>, <mailto:contact@offda9.com?subject=unsubscribe>';
    return od9_send_mail($email, $subject, $html, [
        'from_email'       => 'noreply@offda9.com',
        'from_name'        => 'The OD9 Movement',
        'reply_to'         => 'contact@offda9.com',
        'list_unsubscribe' => $unsub,
    ]);
}
