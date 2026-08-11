<?php
/**
 * OD9 board write-path bridge (Phase C Slice 3).
 *
 * Receives a member's move submission from board.php, signs it HMAC-SHA256 with
 * the shared MEMBER_ACTION_WEBHOOK_SECRET, and forwards it to the bot's internal
 * POST /member/action endpoint (same box, 127.0.0.1:5000) — which routes it
 * through helpers.grant_credits. The browser never sees the secret; the bot is
 * the only thing that grants credit. Then redirects back to the board with a
 * member-facing flash message.
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
od9_dashboard_boot();

function board_back(string $msg, string $kind = 'ok', ?int $focus = null): void {
    global $RETURN_TO, $VIEW_TIER;
    // Flash via session (Post-Redirect-Get), NOT the query string — so the URL
    // stays clean and a refresh never re-shows a stale message. The origin page
    // (board.php / bunker.php) reads + consumes $_SESSION['board_flash'].
    $_SESSION['board_flash'] = ['kind' => $kind, 'msg' => $msg];
    $page = $RETURN_TO ?: 'board.php';
    // Preserve the ZONE the member was viewing (?tier=) and, for the content move,
    // which question to land on (?focus=) — STAY on a reject, advance to the NEXT
    // question on a pass. Without this the redirect dropped both and dumped a
    // traveling member back to their home zone (founder, 2026-06-30). Bunker returns
    // aren't zoned, so tier/focus only apply to the board.
    $qs = [];
    if ($page === 'board.php' && $VIEW_TIER !== '') { $qs['tier'] = $VIEW_TIER; }
    if ($page === 'board.php' && $focus) { $qs['focus'] = $focus; }
    $url = DASHBOARD_BASE_URL . '/' . $page . ($qs ? '?' . http_build_query($qs) : '');
    header('Location: ' . $url);
    exit;
}

// The zone the member is viewing (board.php?tier=) — preserved across the redirect so a
// traveling member isn't bounced to their home zone. Validated against the tier whitelist
// (it lands in a Location header). Populated below once we know it's a POST.
$VIEW_TIER = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') board_back('Invalid request.', 'err');
if (empty($_SESSION['discord_id'])) { header('Location: ' . DASHBOARD_BASE_URL . '/index.php'); exit; }
$discordId = $_SESSION['discord_id'];

// Where board_back() returns to (whitelist) — the board by default, the Bunker
// for Song-of-the-Week actions.
$RETURN_TO = (($_POST['return_to'] ?? '') === 'bunker') ? 'bunker.php' : 'board.php';
$_vt = strtolower(trim((string)($_POST['tier'] ?? '')));
if (in_array($_vt, ['observer', 'theorist', 'architect', 'pioneer', 'benefactor'], true)) {
    $VIEW_TIER = $_vt;
}

$secret = getenv('MEMBER_ACTION_WEBHOOK_SECRET');
if (!$secret) {
    error_log('[board-action] MEMBER_ACTION_WEBHOOK_SECRET not set — cannot sign');
    board_back('The board isn\'t fully wired up yet — hang tight.', 'err');
}

$action = $_POST['action'] ?? '';

// CSRF (security audit #2): protect the value-bearing, credit-granting actions.
// notifications_read is idempotent + posted via AJAX (separate path), so it's exempt.
if (in_array($action, ['qotd_answer', 'content_read_check', 'sotw_reflect'], true)) {
    $sessTok = (string)($_SESSION['csrf_token'] ?? '');
    if ($sessTok === '' || !hash_equals($sessTok, (string)($_POST['csrf_token'] ?? ''))) {
        board_back('Security check failed — refresh the page and try again.', 'err');
    }
}
if ($action === 'qotd_answer') {
    $postId = (int)($_POST['post_id'] ?? 0);
    $text = trim((string)($_POST['text'] ?? ''));
    if (!$postId || $text === '') board_back('Your answer was empty.', 'err');
    $idem = "qotd:$postId:$discordId";
    $payload = ['post_id' => $postId, 'text' => $text];
} elseif ($action === 'content_read_check') {
    $contentId = (int)($_POST['content_id'] ?? 0);
    $nextId = (int)($_POST['next_id'] ?? 0);   // the journey item after this one (board.php)
    $reflection = trim((string)($_POST['reflection'] ?? ''));
    if (!$contentId) board_back('No content selected.', 'err');
    if (mb_strlen($reflection) < 50) board_back('Your reflection needs to be at least 50 characters.', 'err', $contentId);
    $idem = "content:$contentId:$discordId";
    $payload = ['content_id' => $contentId, 'reflection' => $reflection];
} elseif ($action === 'sotw_reflect') {
    $trackId = trim((string)($_POST['track_id'] ?? ''));
    $trackTitle = trim((string)($_POST['track_title'] ?? ''));
    $reflection = trim((string)($_POST['reflection'] ?? ''));
    if ($trackId === '') board_back('No track selected.', 'err');
    if (mb_strlen($reflection) < 50) board_back('Your reflection needs to be at least 50 characters.', 'err');
    $idem = "sotw:$trackId:$discordId";
    $payload = ['track_id' => $trackId, 'track_title' => $trackTitle, 'reflection' => $reflection];
} elseif ($action === 'notifications_read') {
    // Mark notification(s) read (AJAX). A numeric notification_id marks just that
    // one (per-item dismiss from the inbox); absent = mark all (legacy sweep).
    // Idempotent + repeatable (retryable on the bot).
    $nid = trim((string)($_POST['notification_id'] ?? ''));
    if ($nid !== '' && ctype_digit($nid)) {
        $idem = "notif_read:$discordId:$nid";
        $payload = ['notification_id' => (int)$nid];
    } else {
        $idem = "notif_read:$discordId";
        $payload = (object)[];
    }
} else {
    board_back('Unknown action.', 'err');
}

$body = json_encode([
    'discord_id'      => $discordId,
    'action'          => $action,
    'idempotency_key' => $idem,
    'guild_id'        => defined('OD9_GUILD_ID') ? OD9_GUILD_ID : null,
    'payload'         => $payload,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

// Sign the EXACT bytes we send; the bot verifies HMAC-SHA256 over the raw body.
$sig = hash_hmac('sha256', $body, $secret);
$url = getenv('BOT_INTERNAL_URL') ?: 'http://127.0.0.1:5000/member/action';

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $body,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'X-OD9-Signature: ' . $sig],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 30,
]);
$resp = curl_exec($ch);
$code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
$cerr = curl_error($ch);
curl_close($ch);

$data = (is_string($resp) && $resp !== '') ? json_decode($resp, true) : null;

// notifications_read is an AJAX (fetch) call from the dashboard — answer with a
// bare HTTP status (no redirect), so the page doesn't reload on mark-read.
if ($action === 'notifications_read') {
    http_response_code(($code === 200 && is_array($data) && !empty($data['ok'])) ? 204 : 502);
    exit;
}

if ($resp === false || $code === 0) {
    error_log("[board-action] bot call failed: $cerr");
    board_back('Couldn\'t reach the progression service — try again in a moment.', 'err');
}
if ($code !== 200 || !is_array($data) || empty($data['ok'])) {
    $err = is_array($data) ? ($data['error'] ?? 'rejected') : 'rejected';
    error_log("[board-action] bot returned $code: " . substr((string)$resp, 0, 300));
    board_back('That didn\'t go through: ' . $err, 'err');
}

$credits = (int)($data['credits'] ?? 0);
$idempotent = !empty($data['idempotent']);

if ($action === 'content_read_check') {
    // Capstone parts pass the AI PRE-SCREEN but never auto-approve — they route to a human
    // reviewer (the synthesis gate; audit 2026-07-04). Check this BEFORE the `passed` branch,
    // because a capstone pre-screen returns passed=true WITH status=pending_review and 0 credits.
    // Advance to the next item so the member keeps moving while a reviewer confirms the capstone.
    if (($data['status'] ?? '') === 'pending_review') {
        $fb = trim((string)($data['feedback'] ?? ''));
        board_back($fb !== '' ? $fb : 'Capstone reflection submitted — a reviewer will confirm it, then credit + advancement land.', 'pending', $nextId ?: null);
    }
    // PASS / already-cleared -> advance to the NEXT question. try_again / pending ->
    // STAY on this one so the member can revise (their saved draft is now persisted).
    if (!empty($data['passed'])) {
        $fb = trim((string)($data['feedback'] ?? ''));
        board_back("Passed the comprehension check — +$credits CR." . ($fb ? "  ($fb)" : ''), 'ok', $nextId ?: null);
    }
    if (($data['status'] ?? '') === 'already_completed') {
        board_back('You\'ve already cleared that one.', 'pending', $nextId ?: null);
    }
    if (($data['status'] ?? '') === 'try_again') {
        $fb = trim((string)($data['feedback'] ?? ''));
        board_back('Not yet — ' . ($fb !== '' ? $fb : 'tie it to the mission and what you\'ll do with it.') . ' Re-open the reading and give it another pass.', 'err', $contentId ?: null);
    }
    board_back('Reflection recorded — credit lands once it passes review.', 'pending', $contentId ?: null);
}

if ($action === 'sotw_reflect') {
    if (!empty($data['passed'])) {
        $fb = trim((string)($data['feedback'] ?? ''));
        board_back("The Quartermaster logged it — +$credits CR." . ($fb ? "  ($fb)" : ''), 'ok');
    }
    if (($data['status'] ?? '') === 'try_again') {
        $fb = trim((string)($data['feedback'] ?? ''));
        board_back('Not quite — ' . ($fb !== '' ? $fb : 'tell her what it\'s really saying.') . ' Run it back.', 'err');
    }
    board_back('Reflection recorded.', 'pending');
}

// qotd_answer
if ($idempotent) board_back('You\'d already answered today\'s question.', 'pending');
board_back("Answer logged — +$credits CR. Keep the streak going.", 'ok');
