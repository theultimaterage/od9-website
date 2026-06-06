<?php
/**
 * OD9 Drip System - Webhook Endpoint (minimal v2, 2026-05-01)
 *
 * Receives HMAC-SHA256-signed events from the Discord bot and logs them to
 * od9_webhook_log. Downstream processing (drip enrollment, etc.) is
 * intentionally NOT done here yet - log first, build the rest of the
 * pipeline once we have data flowing.
 *
 * Supported events (the bot's utils/od9_drip_webhook.py emits these three):
 *   - tier_change       payload.data = { old_tier, new_tier, username }
 *   - achievement       payload.data = { achievement_id, name, rarity, ... }
 *   - streak_milestone  payload.data = { streak_days, milestone_label, multiplier }
 *
 * Signature contract (must match utils/od9_drip_webhook.py exactly):
 *   - bot signs the raw POST body with hmac_sha256(secret, body) hex digest
 *   - bot sends signature in X-Webhook-Signature header
 *   - shared secret loaded from sibling webhook_secret.config.php
 *     (gitignored, mode 600, owned by offda9 user)
 *
 * Replaced an earlier version (Orion, Apr 2026) that had four bugs that
 * silently broke the entire pipeline: hardcoded fallback secret nobody
 * overrode, INSERT into nonexistent columns, wrong header name, and
 * discord_user_id read from the wrong place in the payload.
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// ---- Config ----
$_secretFile = __DIR__ . '/webhook_secret.config.php';
if (!file_exists($_secretFile)) {
    http_response_code(500);
    echo json_encode(['error' => 'Server not configured']);
    error_log('[od9_webhook] webhook_secret.config.php missing');
    exit;
}
require_once $_secretFile;
if (!defined('OD9_WEBHOOK_SECRET') || OD9_WEBHOOK_SECRET === '') {
    http_response_code(500);
    echo json_encode(['error' => 'Server not configured']);
    error_log('[od9_webhook] OD9_WEBHOOK_SECRET not defined');
    exit;
}

$DB_HOST = 'localhost';
$DB_NAME = 'offda9_od9_tickets';
$DB_USER = 'offda9_od9admin';
$DB_PASS = 'Xk9mPvR3nWq7sYd';   // TODO move to a config file alongside the secret

// ---- Method gate ----
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// ---- Read body once (php://input is a one-shot stream) ----
$rawPayload = file_get_contents('php://input');
if ($rawPayload === false || $rawPayload === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Empty payload']);
    exit;
}

// ---- Signature check ----
$receivedSig = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? '';
$expectedSig = hash_hmac('sha256', $rawPayload, OD9_WEBHOOK_SECRET);
if ($receivedSig === '' || !hash_equals($expectedSig, $receivedSig)) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid signature']);
    exit;
}

// ---- Parse + validate ----
$payload = json_decode($rawPayload, true);
if (json_last_error() !== JSON_ERROR_NONE || !is_array($payload)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

$eventType = $payload['event'] ?? null;
$discordUserId = $payload['user_id'] ?? ($payload['data']['discord_user_id'] ?? null);

if (!$eventType || !$discordUserId) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields',
                      'need' => ['event', 'user_id']]);
    exit;
}

$ALLOWED_EVENTS = ['tier_change', 'achievement', 'streak_milestone', 'patreon'];
if (!in_array($eventType, $ALLOWED_EVENTS, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Unknown event type', 'event' => $eventType]);
    exit;
}

// ---- Log to od9_webhook_log ----
try {
    $pdo = new PDO(
        "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
        $DB_USER, $DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
         PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );

    $stmt = $pdo->prepare(
        "INSERT INTO od9_webhook_log
            (event_type, discord_user_id, payload, processed, received_at)
         VALUES (?, ?, ?, 0, NOW())"
    );
    $stmt->execute([$eventType, (string)$discordUserId, $rawPayload]);
    $logId = (int)$pdo->lastInsertId();

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'webhook_log_id' => $logId,
        'event' => $eventType,
        'note' => 'Logged. Downstream drip enrollment is not yet wired up.',
    ]);
} catch (PDOException $e) {
    error_log('[od9_webhook] DB error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
