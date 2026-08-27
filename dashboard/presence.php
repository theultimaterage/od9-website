<?php
/**
 * World presence feed (PROGRESSION_WORLD_SPEC §5) — who is climbing this zone.
 *
 * Session-gated JSON for the board's token layer. Position is DERIVED from
 * progress: each opted-in member of the zone's tier sits at their first
 * required, not-yet-approved node (module_id); members with every requirement
 * approved cluster at the Gate. Consent comes from od9_profile_visibility
 * .presence (web MySQL, migration 007): hidden members simply do not exist
 * here, and the payload NEVER carries discord ids — an 'anon' token is
 * {node, mode} only; 'visible' adds the username. Nothing else leaves.
 *
 * Modules gate the surface too: only nodes with presence_visible=1 may show
 * tokens (SPEC §3), so a node can be made private by config, not code.
 *
 * Poll target (~25s from board.php). One MySQL read + one SQLite read per
 * call; both fail-open to an empty room, never an error page.
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
od9_dashboard_boot();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (empty($_SESSION['discord_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false]);
    exit;
}
$me = (string) $_SESSION['discord_id'];

$TIERS = ['observer', 'theorist', 'architect', 'pioneer', 'benefactor'];
$zone = strtolower(trim((string) ($_GET['zone'] ?? '')));
if (!in_array($zone, $TIERS, true)) {
    echo json_encode(['ok' => true, 'tokens' => [], 'gate' => 0]);
    exit;
}

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/profile_visibility.php';
$optins = od9_presence_optins();
unset($optins[$me]);   // you are never your own crowd
if (!$optins) {
    echo json_encode(['ok' => true, 'tokens' => [], 'gate' => 0]);
    exit;
}

$tokens = [];
$gateCount = 0;
try {
    $bot = new PDO('sqlite:' . OD9_BOT_DB_PATH, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $ids = array_keys($optins);
    $ph = implode(',', array_fill(0, count($ids), '?'));
    // Current node per member: first required content node (stored order) with
    // no approved completion — the same "first todo" shape the board uses.
    // presence_visible=1 only (SPEC §3: nodes opt IN to showing tokens).
    $rows = $bot->prepare(
        "SELECT u.user_id, u.username,
                (SELECT m.module_id FROM modules m
                  WHERE m.zone IN (?, 'global') AND m.active = 1
                    AND m.presence_visible = 1
                    AND m.is_required = 1 AND m.content_id IS NOT NULL
                    AND NOT EXISTS (
                        SELECT 1 FROM content_completion cc
                         WHERE cc.content_id = m.content_id
                           AND cc.user_id = u.user_id
                           AND cc.review_status = 'approved')
                  ORDER BY m.position, m.module_id LIMIT 1) AS at_node
           FROM users u
          WHERE LOWER(u.current_tier) = ? AND u.user_id IN ($ph)"
    );
    $rows->execute(array_merge([$zone, $zone], $ids));
    foreach ($rows as $r) {
        $mode = $optins[(string) $r['user_id']] ?? 'hidden';
        if ($mode === 'hidden') continue;
        if ($r['at_node'] === null) {
            // every requirement approved -> they stand at the Gate
            $gateCount++;
            continue;
        }
        $t = ['node' => (string) $r['at_node'], 'mode' => $mode];
        if ($mode === 'visible') {
            $t['name'] = (string) $r['username'];
        }
        $tokens[] = $t;
    }
} catch (Throwable $e) {
    error_log('[presence] read failed: ' . $e->getMessage());
    echo json_encode(['ok' => true, 'tokens' => [], 'gate' => 0]);
    exit;
}

echo json_encode(['ok' => true, 'tokens' => $tokens, 'gate' => $gateCount],
                 JSON_UNESCAPED_UNICODE);
