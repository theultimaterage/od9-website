<?php
/**
 * guide_talk bridge (SPEC §4 deep mode) — the board's freeform lane to the
 * Archivist. Session + CSRF gated, signs {discord_id, question} HMAC-SHA256
 * and forwards to the bot's POST /member/guide; the tutor there enforces the
 * daily cap, the shared cache, and the answers-from-the-manifesto-only
 * contract. Read-only sibling of coach-ask.php: same secret, same channel,
 * the browser never sees either.
 *
 * Returns the bot's JSON verbatim: {ok, speaker, text, citations[], cached?,
 * capped?}.
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
od9_dashboard_boot();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function guide_out(int $code, array $body): void {
    http_response_code($code);
    echo json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') guide_out(405, ['ok' => false, 'error' => 'POST only']);
if (empty($_SESSION['discord_id'])) guide_out(401, ['ok' => false, 'error' => 'not signed in']);
$discordId = $_SESSION['discord_id'];

$sessTok = (string)($_SESSION['csrf_token'] ?? '');
if ($sessTok === '' || !hash_equals($sessTok, (string)($_POST['csrf_token'] ?? ''))) {
    guide_out(403, ['ok' => false, 'error' => 'security check failed — refresh the page']);
}

$question = trim((string)($_POST['question'] ?? ''));
if ($question === '' || mb_strlen($question) > 500) {
    guide_out(400, ['ok' => false, 'error' => 'ask one question, up to 500 characters']);
}

$secret = getenv('MEMBER_ACTION_WEBHOOK_SECRET');
if (!$secret) {
    error_log('[guide-ask] MEMBER_ACTION_WEBHOOK_SECRET not set — cannot sign');
    guide_out(503, ['ok' => false, 'error' => 'the Archivist is out of reach right now']);
}

$body = json_encode([
    'discord_id' => $discordId,
    'question'   => $question,
    'guild_id'   => defined('OD9_GUILD_ID') ? OD9_GUILD_ID : null,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

$sig = hash_hmac('sha256', $body, $secret);
$url = getenv('BOT_GUIDE_INTERNAL_URL') ?: 'http://127.0.0.1:5000/member/guide';

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $body,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'X-OD9-Signature: ' . $sig],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 45,
]);
$resp = curl_exec($ch);
$code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
$cerr = curl_error($ch);
curl_close($ch);

if ($resp === false || $code === 0) {
    error_log("[guide-ask] bot call failed: $cerr");
    guide_out(502, ['ok' => false, 'error' => 'the Archivist is out of reach — try again in a moment']);
}
$data = json_decode((string)$resp, true);
if (!is_array($data)) {
    error_log('[guide-ask] bot returned non-JSON: ' . substr((string)$resp, 0, 300));
    guide_out(502, ['ok' => false, 'error' => 'garbled answer — try again']);
}
guide_out($code === 200 ? 200 : 502, $data);
