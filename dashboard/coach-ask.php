<?php
/**
 * The Coach bridge (PROGRESSION_WORLD_SPEC §4, chunk 3).
 *
 * AJAX endpoint: the board's Guide panel POSTs a FIXED-menu intent id here;
 * we sign it HMAC-SHA256 with the shared MEMBER_ACTION_WEBHOOK_SECRET and
 * forward to the bot's internal POST /member/coach (same box, 127.0.0.1:5000),
 * which answers from live member state in the Archivist's voice. Read-only —
 * no credit grants — but it rides the same signed channel as board-action.php
 * so there is exactly one auth pattern between web and bot.
 *
 * Returns JSON: {ok, speaker, intent, text, gate?} straight from the bot.
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
od9_dashboard_boot();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function coach_out(int $code, array $body): void {
    http_response_code($code);
    echo json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') coach_out(405, ['ok' => false, 'error' => 'POST only']);
if (empty($_SESSION['discord_id'])) coach_out(401, ['ok' => false, 'error' => 'not signed in']);
$discordId = $_SESSION['discord_id'];

// CSRF — same session token the board's forms carry. The ask is read-only, but
// it reaches the bot and writes the coach log, so it stays protected.
$sessTok = (string)($_SESSION['csrf_token'] ?? '');
if ($sessTok === '' || !hash_equals($sessTok, (string)($_POST['csrf_token'] ?? ''))) {
    coach_out(403, ['ok' => false, 'error' => 'security check failed — refresh the page']);
}

// The intent menu is FIXED (utils/coach.py INTENTS). Whitelist here too, so
// garbage never leaves this box; the bot fail-closes regardless.
$intent = (string)($_POST['intent'] ?? '');
$MENU = ['next_move', 'the_gate', 'how_advance', 'credits', 'reviews'];
if (!in_array($intent, $MENU, true)) coach_out(400, ['ok' => false, 'error' => 'unknown intent']);

$secret = getenv('MEMBER_ACTION_WEBHOOK_SECRET');
if (!$secret) {
    error_log('[coach-ask] MEMBER_ACTION_WEBHOOK_SECRET not set — cannot sign');
    coach_out(503, ['ok' => false, 'error' => 'the Archivist is out of reach right now']);
}

$body = json_encode([
    'discord_id' => $discordId,
    'intent'     => $intent,
    'guild_id'   => defined('OD9_GUILD_ID') ? OD9_GUILD_ID : null,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

// Sign the EXACT bytes we send; the bot verifies HMAC-SHA256 over the raw body.
$sig = hash_hmac('sha256', $body, $secret);
$url = getenv('BOT_COACH_INTERNAL_URL') ?: 'http://127.0.0.1:5000/member/coach';

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $body,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'X-OD9-Signature: ' . $sig],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 20,
]);
$resp = curl_exec($ch);
$code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
$cerr = curl_error($ch);
curl_close($ch);

if ($resp === false || $code === 0) {
    error_log("[coach-ask] bot call failed: $cerr");
    coach_out(502, ['ok' => false, 'error' => 'the Archivist is out of reach — try again in a moment']);
}
$data = json_decode((string)$resp, true);
if (!is_array($data)) {
    error_log('[coach-ask] bot returned non-JSON: ' . substr((string)$resp, 0, 300));
    coach_out(502, ['ok' => false, 'error' => 'garbled answer — try again']);
}
coach_out($code === 200 ? 200 : 502, $data);
