<?php
/**
 * Guided-tour state — cross-device chapter completion (docs/GUIDED_TOUR_SPEC.md §2).
 *
 * GET  -> {"chapters":{"board":"done",...}} for the logged-in member.
 * POST (csrf_token + chapter) -> merge one chapter completion. Union-of-done
 * only — a chapter can be added, never removed (never-demote invariant).
 *
 * Storage: web MySQL (od9_tickets), NOT the bot SQLite (web reads that live but
 * strictly read-only — see memory web-dashboard-ground-truth). Table is created
 * on first use (idempotent) so no manual prod migration step exists to forget;
 * migrations/006_tour_state.sql is the canonical schema record.
 *
 * Fail-open by contract: any error returns an empty/ok JSON — the tour must
 * never break a page. Client falls back to localStorage-only.
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
od9_dashboard_boot();

header('Content-Type: application/json');
header('Cache-Control: no-store');

$VALID_CHAPTERS = ['board', 'dashboard', 'bunker', 'settings'];

if (empty($_SESSION['discord_id'])) {
    http_response_code(401);
    echo json_encode(['chapters' => (object)[]]);
    exit;
}
$discordId = (string)$_SESSION['discord_id'];

$pdo = null;
try {
    $pdo = getDatabaseConnection();
    $pdo->exec("CREATE TABLE IF NOT EXISTS od9_tour_state (
        discord_id VARCHAR(32) NOT NULL PRIMARY KEY,
        chapters TEXT NOT NULL,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Throwable $e) {
    error_log('[tour-state] db unavailable: ' . $e->getMessage());
    echo json_encode(['chapters' => (object)[]]);
    exit;
}

$read = function () use ($pdo, $discordId, $VALID_CHAPTERS): array {
    try {
        $st = $pdo->prepare('SELECT chapters FROM od9_tour_state WHERE discord_id = ?');
        $st->execute([$discordId]);
        $raw = $st->fetchColumn();
        $decoded = $raw ? json_decode((string)$raw, true) : [];
        if (!is_array($decoded)) { $decoded = []; }
        return array_intersect_key($decoded, array_flip($VALID_CHAPTERS));
    } catch (Throwable $e) {
        error_log('[tour-state] read failed: ' . $e->getMessage());
        return [];
    }
};

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $token = (string)($_POST['csrf_token'] ?? '');
    if ($token === '' || !hash_equals(od9_csrf_token(), $token)) {
        http_response_code(403);
        echo json_encode(['chapters' => (object)$read()]);
        exit;
    }
    $chapter = strtolower(trim((string)($_POST['chapter'] ?? '')));
    if (in_array($chapter, $VALID_CHAPTERS, true)) {
        $chapters = $read();
        $chapters[$chapter] = 'done';
        try {
            $st = $pdo->prepare('INSERT INTO od9_tour_state (discord_id, chapters) VALUES (?, ?)
                                 ON DUPLICATE KEY UPDATE chapters = VALUES(chapters)');
            $st->execute([$discordId, json_encode($chapters)]);
        } catch (Throwable $e) {
            error_log('[tour-state] write failed: ' . $e->getMessage());
        }
    }
    echo json_encode(['chapters' => (object)$read()]);
    exit;
}

echo json_encode(['chapters' => (object)$read()]);
