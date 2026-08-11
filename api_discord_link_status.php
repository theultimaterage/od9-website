<?php
/**
 * OD9 Discord Link Status (read-only)
 *
 * Returns the current email_signups link state for a given Discord user_id.
 * HMAC-signed by the bot to prevent random enumeration of which Discord
 * accounts are linked.
 *
 * Method: POST
 * URL:    /api/discord/link_status.php  (deployed at offda9.com)
 * Body:   {"discord_user_id": "123..."}
 * Headers:
 *   X-Webhook-Signature: hex(hmac_sha256(OD9_DRIP_WEBHOOK_SECRET, body))
 *
 * Response:
 *   200 {"linked": false}                                  - no row at all
 *   200 {"linked": true, "verified": false, "email": "..."}- row exists, awaiting verify
 *   200 {"linked": true, "verified": true, "email": "..."} - fully active subscriber
 *   401 {"error": "Invalid signature"}
 *   400 {"error": "Bad request"}
 *
 * Why HMAC: only the bot has the shared secret, so only the bot can issue
 * status queries. Without this, anyone who could POST to the endpoint
 * could enumerate "is Discord ID X subscribed?" - a privacy leak.
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// Reuse the drip webhook secret - same secret pool, same trust boundary.
$_secretFile = __DIR__ . '/../drip/webhook_secret.config.php';
if (!file_exists($_secretFile)) {
    http_response_code(500);
    echo json_encode(['error' => 'Server not configured']);
    exit;
}
require_once $_secretFile;
if (!defined('OD9_WEBHOOK_SECRET') || OD9_WEBHOOK_SECRET === '') {
    http_response_code(500);
    echo json_encode(['error' => 'Server not configured']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$rawBody = file_get_contents('php://input');
if ($rawBody === false || $rawBody === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Empty body']);
    exit;
}

// HMAC verify
$receivedSig = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? '';
$expectedSig = hash_hmac('sha256', $rawBody, OD9_WEBHOOK_SECRET);
if ($receivedSig === '' || !hash_equals($expectedSig, $receivedSig)) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid signature']);
    exit;
}

$payload = json_decode($rawBody, true);
$discordId = $payload['discord_user_id'] ?? null;
if (!$discordId || !preg_match('/^\d{15,25}$/', (string)$discordId)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid discord_user_id']);
    exit;
}

require_once __DIR__ . '/../../config/database.php';
try {
    $pdo = getDatabaseConnection();
    $stmt = $pdo->prepare(
        "SELECT email, is_verified, email_opt_in, status
         FROM email_signups
         WHERE discord_user_id = ?
         ORDER BY id DESC LIMIT 1"
    );
    $stmt->execute([(string)$discordId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode(['linked' => false]);
        exit;
    }

    echo json_encode([
        'linked'     => true,
        'verified'   => (bool)((int)$row['is_verified'] === 1 && (int)$row['email_opt_in'] === 1 && $row['status'] === 'active'),
        'email'      => $row['email'],
        'opted_in'   => (bool)((int)$row['email_opt_in'] === 1),
        'status'     => $row['status'],
    ]);
} catch (PDOException $e) {
    error_log('[link_status] DB error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
