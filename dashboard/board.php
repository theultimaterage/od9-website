<?php
/**
 * OD9 Progression Board — the live "iced-out world" zone-map (Phase C Slice 3).
 *
 * The interactive home of the ASCEND Protocol: reads the member's real
 * progression from the bot's SQLite (same source as index.php) and renders the
 * current zone with their actual "moves this turn." Submitting a move posts to
 * board-action.php, which signs + forwards to the bot's /member/action endpoint
 * (the one credit chokepoint). Read-here, write-through-the-bot — no parallel
 * economy on the web side.
 *
 * Design: scripts/web/css/board.css (ported from design-system/ui_kits/board).
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/ztrans.php';
od9_dashboard_boot();

if (empty($_SESSION['discord_id'])) {
    header('Location: ' . DASHBOARD_BASE_URL . '/index.php');
    exit;
}
$discordId = $_SESSION['discord_id'];

// ---- Zone table + gate requirements: SHARED with world.php via
//      includes/world_consts.php (one definition, zero drift). ----
require_once __DIR__ . '/includes/world_consts.php';

// ---- Read live progression from the bot DB (guarded; a bad query blanks its
//      own widget, never the page — same safe-read pattern as index.php). ----
$bot = null;
try {
    $bot = new PDO('sqlite:' . OD9_BOT_DB_PATH, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Throwable $e) {
    error_log('[board.php] bot DB open failed: ' . $e->getMessage());
}
$q = function (string $sql, array $p = []) use ($bot): array {
    if (!$bot) return [];
    try { $s = $bot->prepare($sql); $s->execute($p); return $s->fetchAll(); }
    catch (Throwable $e) { error_log('[board.php] query failed: ' . $e->getMessage()); return []; }
};

$user = $q("SELECT username, current_tier, total_credits FROM users WHERE user_id = ? LIMIT 1", [$discordId])[0] ?? null;
$memberTier = strtolower($user['current_tier'] ?? 'observer');
if (!isset(ZONES[$memberTier])) $memberTier = 'observer';
$credits = (int)($user['total_credits'] ?? 0);

// Zone Navigator (T-WEB-ZONE-NAV-001): a member may VIEW any zone at or below their
// tier — their own zone is interactive; lower zones are read-only reference. Future
// zones are locked teasers (you can't see ahead of what you haven't earned). A
// Benefactor (top tier) can thus walk every zone — which is how the founder / top-tier
// members preview the lower boards. ?tier= selects the viewed zone; an invalid or
// ahead-of-tier value silently falls back to the member's own zone.
$TIER_ORDER = TIER_ORDER; // shared const (includes/world_consts.php)
$memberIdx  = array_search($memberTier, $TIER_ORDER, true);
$reqIdx     = array_search(strtolower($_GET['tier'] ?? ''), $TIER_ORDER, true);
$tier       = ($reqIdx !== false && $reqIdx <= $memberIdx) ? $TIER_ORDER[$reqIdx] : $memberTier;
$isPreview  = ($tier !== $memberTier);   // viewing a zone other than your own
$isAdmin    = defined('OD9_ADMIN_DISCORD_IDS') && in_array((string)$discordId, array_map('strval', OD9_ADMIN_DISCORD_IDS), true);
$readOnly   = $isPreview && !$isAdmin;    // members are read-only off-tier; admins stay fully interactive on any zone
// PRESENT MODE (?present=1): the board as a BROADCAST surface for the 4 PM
// sermon (docs/SERMON_PIPELINE_SPEC.md) — used ONLY by the OBS browser source.
// Purely additive: it adds a body class that board.css uses to drop dashboard
// furniture and keep live content out of the facecam / chyron zones. A member
// following along on their own machine never carries the flag, so their board
// is byte-identical to before.
$present    = !empty($_GET['present']);
$zone = ZONES[$tier];

$dimRow = $q("SELECT knowledge_score, resource_score, community_score, consciousness_score, system_score FROM user_dimensions WHERE user_id = ?", [$discordId])[0] ?? [];
$dim = fn(string $d) => (float)($dimRow[$d . '_score'] ?? 0);

// Gate requirements LIVE from the bot's tier_gate_requirements projection —
// the world_consts hand-mirror died in chunk 4 (od9_gate_tables fail-opens to
// null and the gate panel blanks itself, never the page).
[$GATES, $NEXTS] = od9_gate_tables($bot);
$gate = $GATES[$tier] ?? null;
$nextTier = $NEXTS[$tier] ?? null;
$gateCreditPct = $gate ? min(100, $gate['credits'] ? round($credits / $gate['credits'] * 100) : 0) : 0;

// Today's QOTD (latest post + its question) + whether this member already answered.
$qrow = $q("SELECT p.post_id, qq.question_text FROM qotd_posts p JOIN qotd_questions qq ON p.question_id = qq.question_id ORDER BY p.posted_at DESC LIMIT 1")[0] ?? null;
$qotdAnswered = false;
if ($qrow) {
    $qotdAnswered = (bool)($q("SELECT 1 FROM qotd_responses WHERE post_id = ? AND user_id = ? LIMIT 1", [$qrow['post_id'], $discordId])[0] ?? false);
}

// Every tier doc the member still owes — each carrying its OWN content_id so a
// reflection can never mis-file (the Defect-B fix; the old board auto-picked ONE
// "next" doc and bound the reflection to it, so a member reading doc X could submit
// against doc Y). Includes never-started AND rejected (rejected shows reviewer
// feedback + resubmits in place); excludes approved + pending_review (nothing to do).
// Required items sort first; optional-depth (is_required=0) trails as "go deeper".
// T-PROG-CONTENT-GATE-001.
$contentTodo = $q(
    "SELECT cl.content_id, cl.title, cl.credit_value, cl.url, cl.content_type,
            COALESCE(cl.is_required, 1) AS is_required,
            cc.review_status, cc.review_notes
       FROM content_library cl
       LEFT JOIN content_completion cc
              ON cc.content_id = cl.content_id AND cc.user_id = ?
      WHERE LOWER(COALESCE(cl.tier_requirement, '')) IN (?, '')
        AND (cc.review_status IS NULL OR cc.review_status NOT IN ('approved', 'pending_review'))
      ORDER BY COALESCE(cl.is_required, 1) DESC,
               COALESCE(cl.display_order, 999999), cl.content_id",
    [$discordId, $tier]
);
// Required-content gate progress (drives the gate panel's content lock). Mirrors
// db.tier_content_status: only is_required=1 docs count toward advancement.
$reqTotal = (int)($q(
    "SELECT COUNT(*) c FROM content_library
      WHERE LOWER(COALESCE(tier_requirement,'')) IN (?, '') AND COALESCE(is_required,1) = 1",
    [$tier])[0]['c'] ?? 0);
$reqDone = (int)($q(
    "SELECT COUNT(DISTINCT cc.content_id) c FROM content_completion cc
       JOIN content_library cl ON cl.content_id = cc.content_id
      WHERE cc.user_id = ? AND LOWER(COALESCE(cl.tier_requirement,'')) IN (?, '')
        AND COALESCE(cl.is_required,1) = 1 AND cc.review_status = 'approved'",
    [$discordId, $tier])[0]['c'] ?? 0);
// Required docs (e.g. capstone parts) submitted but awaiting a human reviewer. Not "done"
// (not approved) yet not "todo" — surface them so the board never claims "cleared" while a
// capstone sits in the review queue (audit 2026-07-04).
$reqPending = (int)($q(
    "SELECT COUNT(DISTINCT cc.content_id) c FROM content_completion cc
       JOIN content_library cl ON cl.content_id = cc.content_id
      WHERE cc.user_id = ? AND LOWER(COALESCE(cl.tier_requirement,'')) IN (?, '')
        AND COALESCE(cl.is_required,1) = 1 AND cc.review_status = 'pending_review'",
    [$discordId, $tier])[0]['c'] ?? 0);

// ── DNA-HELIX JOURNEY (T-WEB-BOARD-HELIX-001) ──────────────────────────────────
// Every doc of this tier as a journey node on the double helix (the two strands =
// the Twin Singularities; base-pair rungs bridge tasks). Each node keeps its OWN
// content_id, and the dock binds the reflection to the FOCUSED node's id — so the
// mis-file class stays fixed. Focus = ?focus=<id> (a node click) or the first
// not-yet-approved REQUIRED doc.
// The journey is served from the MODULE CATALOG (PROGRESSION_WORLD_SPEC §3,
// migration 062): stored positions, stable slugs, one row per node. Content
// facts (title/url/type/credits) still come from content_library — the grant
// path reads it, so display and grant can never disagree. FAIL-OPEN: zero
// module rows (pre-062 DB, or a rollback) falls back to the legacy derived
// query below, which migration-seeding was proven sequence-identical to.
$journeyRows = $q(
    "SELECT m.content_id, COALESCE(cl.title, m.title) AS title,
            COALESCE(cl.credit_value, m.reward_credits) AS credit_value,
            cl.url, cl.content_type,
            m.is_required, m.module_id, m.module_type, m.media_key,
            m.reward_credits,
            cc.review_status, cc.review_notes,
            mc.completed_at AS module_completed_at
       FROM modules m
       LEFT JOIN content_library cl ON cl.content_id = m.content_id
       LEFT JOIN content_completion cc ON cc.content_id = m.content_id AND cc.user_id = ?
       LEFT JOIN module_completions mc ON mc.module_id = m.module_id AND mc.user_id = ?
      WHERE m.zone IN (?, 'global') AND m.active = 1
      ORDER BY m.position, m.module_id",
    [$discordId, $discordId, $tier]
);
if (!count($journeyRows)) {
    $journeyRows = $q(
        "SELECT cl.content_id, cl.title, cl.credit_value, cl.url, cl.content_type,
                COALESCE(cl.is_required,1) AS is_required,
                cc.review_status, cc.review_notes
           FROM content_library cl
           LEFT JOIN content_completion cc ON cc.content_id = cl.content_id AND cc.user_id = ?
          WHERE LOWER(COALESCE(cl.tier_requirement,'')) IN (?, '')
          ORDER BY COALESCE(cl.is_required,1) DESC, COALESCE(cl.display_order,999999), cl.content_id",
        [$discordId, $tier]
    );
}
$firstTodoId = 0;
foreach ($journeyRows as $jr) {
    // Default focus = first required doc that's actually actionable (never-started or rejected).
    // Skip pending_review too: a capstone awaiting human review is submitted, not "your move" —
    // don't bounce the member back onto a re-submit form for it (audit 2026-07-04).
    // PURE modules (content_id NULL — watch, qotd) can never be the content focus:
    // (int)NULL is 0, which made focus=0 "match" them and rendered a module as a
    // read+check quest (chunk-5 driver catch; latent since the watch node).
    if ($jr['content_id'] !== null
        && (int)$jr['is_required'] === 1
        && !in_array((string)($jr['review_status'] ?? ''), ['approved', 'pending_review'], true)
        && $firstTodoId === 0) {
        $firstTodoId = (int)$jr['content_id'];
    }
}
$focusId = (int)($_GET['focus'] ?? 0);
$focusOk = false;
// An explicit ?focus= sticks for ANY item in this tier's journey — including one that's
// already approved — so the post-accept redirect can land on "the very next question even
// if I already completed it" (founder, 2026-06-30). Default (no ?focus=) still picks the
// first not-yet-approved required doc.
foreach ($journeyRows as $jr) { if ($jr['content_id'] !== null && (int)$jr['content_id'] === $focusId) { $focusOk = true; break; } }
if (!$focusOk) { $focusId = $firstTodoId; }
// The journey item immediately AFTER the focus — board-action.php redirects here on a
// PASS (advance to the next question). Last item: stay on it.
$nextFocusId = 0; $seenFocus = false;
foreach ($journeyRows as $jr) {
    if ($jr['content_id'] === null) { continue; }   /* pure modules can't be a content focus */
    if ($seenFocus) { $nextFocusId = (int)$jr['content_id']; break; }
    if ((int)$jr['content_id'] === $focusId) { $seenFocus = true; }
}
if ($nextFocusId === 0) { $nextFocusId = $focusId; }

// Watch-module focus (?wmod=<module_id>) — pure modules have no content_id, so
// cutscene nodes get their own focus channel; a valid wmod shows the watch
// panel in the dock instead of the reflection form. MUST be derived BEFORE the
// journey-node loop below, which reads $wmodId to mark the 'current' watch node
// (it was declared after the loop until 2026-08-13 — an undefined-variable
// warning on every watch row, caught by the chunk-4 driver screenshot).
$wmodId = preg_replace('/[^a-z0-9._\-]/i', '', (string)($_GET['wmod'] ?? ''));

// Helix geometry — diagonal bottom-left (12,72) -> top-right (84,30) into the Gate.
$x0 = 12; $y0 = 72; $x1 = 84; $y1 = 30; $amp = 7.0; $perpX = 0.144; $perpY = 0.990;
$jN = count($journeyRows); $twists = max(3.0, round($jN / 5));
$journey = [];
foreach ($journeyRows as $i => $jr) {
    $st = (string)($jr['review_status'] ?? ''); $cidJ = (int)$jr['content_id'];
    $opt = ((int)$jr['is_required'] === 0);
    $isWatchJ = (($jr['module_type'] ?? '') === 'watch');
    $isQotdJ  = (($jr['module_type'] ?? '') === 'qotd');
    $isGuideJ = (($jr['module_type'] ?? '') === 'guide_talk');
    if ($isQotdJ && (!$qrow || $readOnly)) { continue; }   /* no post yet (cooldown) or read-only preview: no beacon */
    if ($isGuideJ && $readOnly) { continue; }              /* previews don't carry the ask lane */
    if ($isQotdJ) {
        // Daily-repeatable node (SPEC §3 chunk 5): state from the QOTD tables
        // the dock already reads — answered today's post = done; else 'daily'
        // (its own pulsing class). Clicking opens the QOTD dock (JS below).
        $state = $qotdAnswered ? 'done' : 'daily';
    } elseif ($isGuideJ) {
        // Ask-the-Archivist node (SPEC §4 deep mode): always open — clicking
        // focuses the guide panel's ask lane. Never a completion state.
        $state = 'optional';
    } elseif ($isWatchJ) {
        // Pure watch node: state from the module_completions ledger; always
        // clickable until watched (a cutscene is never "locked").
        $state = !empty($jr['module_completed_at']) ? 'done'
               : (($wmodId !== '' && (string)$jr['module_id'] === $wmodId) ? 'current' : 'optional');
    } else {
        $state = $st === 'approved' ? 'done' : ($cidJ === $focusId ? 'current'
               : ($st === 'pending_review' ? 'pending' : ($st === 'rejected' ? 'rejected' : ($opt ? 'optional' : 'locked'))));
    }
    // nodes span 0..0.86 of the helix so the last few stay LEFT of / below the
    // top-right Gate panel (the strands still run the full 0..1 into the gate).
    $t = $jN > 1 ? ($i / ($jN - 1)) * 0.86 : 0;
    $o = $amp * sin($t * $twists * 2 * M_PI); $onA = ($i % 2 === 0);
    $journey[] = [
        'cid' => $cidJ, 'title' => $jr['title'], 'cr' => (int)$jr['credit_value'],
        'mid' => (string)($jr['module_id'] ?? ''), 'watch' => $isWatchJ, 'qotd' => $isQotdJ, 'guide' => $isGuideJ,
        'type' => $isQotdJ ? 'qotd' : ($isGuideJ ? 'guide' : ($isWatchJ ? 'video' : strtolower((string)$jr['content_type']))),
        'state' => $state, 'opt' => $opt,
        'x' => round($x0 + ($x1 - $x0) * $t + ($onA ? $o : -$o) * $perpX, 2),
        'y' => round($y0 + ($y1 - $y0) * $t + ($onA ? $o : -$o) * $perpY, 2),
    ];
}
$sA = []; $sB = []; $helixRungs = []; $SAMP = 140;
for ($s = 0; $s <= $SAMP; $s++) {
    $t = $s / $SAMP; $cxp = $x0 + ($x1 - $x0) * $t; $cyp = $y0 + ($y1 - $y0) * $t; $o = $amp * sin($t * $twists * 2 * M_PI);
    $sA[] = round($cxp + $o * $perpX, 2) . ',' . round($cyp + $o * $perpY, 2);
    $sB[] = round($cxp - $o * $perpX, 2) . ',' . round($cyp - $o * $perpY, 2);
    if ($s % 7 === 0 && abs($o) >= 1.4) { $helixRungs[] = [round($cxp + $o * $perpX, 2), round($cyp + $o * $perpY, 2), round($cxp - $o * $perpX, 2), round($cyp - $o * $perpY, 2)]; }
}
$helixA = 'M ' . implode(' L ', $sA); $helixB = 'M ' . implode(' L ', $sB);
$focus = null;
foreach ($journeyRows as $jr) { if ($jr['content_id'] !== null && (int)$jr['content_id'] === $focusId) { $focus = $jr; break; } }

// Resolve the focused watch module (the $wmodId itself is derived above the
// journey loop — see the note there).
$wfocus = null;
if ($wmodId !== '') {
    foreach ($journeyRows as $jr) {
        if (($jr['module_type'] ?? '') === 'watch' && (string)($jr['module_id'] ?? '') === $wmodId) {
            $wfocus = $jr;
            break;
        }
    }
}

// Flash from a just-completed action (set by board-action.php) — delivered via
// the session (Post-Redirect-Get) and consumed on read, so a refresh or a
// bookmarked URL never re-shows a stale message. Legacy ?kind=&msg= are ignored.
$flash = ''; $flashKind = 'ok';
if (!empty($_SESSION['board_flash']) && is_array($_SESSION['board_flash'])) {
    $flash = (string)($_SESSION['board_flash']['msg'] ?? '');
    $flashKind = (string)($_SESSION['board_flash']['kind'] ?? 'ok');
    unset($_SESSION['board_flash']);
}

// Unread notifications (guide-video events) — drives the Dashboard badge/nudge.
$unreadNotif = (int)($q("SELECT COUNT(*) AS c FROM member_notifications WHERE user_id = ? AND read_at IS NULL", [$discordId])[0]['c'] ?? 0);

// Your cohort (cohort-lite v1) — the shared Observer crew climbing Vol 1 together.
// Read-only "you + N others" panel; lesson-progress only (NO credits — respects the
// show_credits user_setting, default-off). Mirrors db.get_cohort_members_with_progress;
// the lesson count uses the same tier-content predicate as the content query above.
$cohort = $q(
    "SELECT c.cohort_id, c.name,
        (SELECT COUNT(*) FROM cohort_members WHERE cohort_id = c.cohort_id) AS member_count
     FROM cohort_members cm JOIN cohorts c ON cm.cohort_id = c.cohort_id
     WHERE cm.user_id = ? AND c.status = 'active'
     ORDER BY cm.joined_at ASC LIMIT 1",
    [$discordId]
)[0] ?? null;
$cohortMembers = $cohort ? $q(
    "SELECT u.user_id, u.username,
        (SELECT COUNT(*) FROM content_completion cc
            JOIN content_library cl ON cc.content_id = cl.content_id
          WHERE cc.user_id = u.user_id
            AND LOWER(COALESCE(cl.tier_requirement,'')) IN ('observer','')) AS lessons_done
     FROM cohort_members cm JOIN users u ON cm.user_id = u.user_id
     WHERE cm.cohort_id = ?
     ORDER BY lessons_done DESC, u.username ASC LIMIT 15",
    [$cohort['cohort_id']]
) : [];

// State of the Filter (canonical data — same source as the public progress.php scorecard)
// + Live Roadmap: the Active conditional moves from the bot's roadmap_triggers, right on
// the board where members live (founder direction 2026-07-04: the ASCEND board, not just
// the public site). Guarded reads — a missing table blanks the panel, never the page.
require_once __DIR__ . '/../includes/filter-scorecard-data.php';
$RM_ACTIVE = $q(
    "SELECT trigger_id, domain, title, condition_text FROM roadmap_triggers
      WHERE status = 'Active' ORDER BY domain, trigger_id LIMIT 6"
);
$RM_QUEUED = (int)($q("SELECT COUNT(*) c FROM roadmap_triggers WHERE status = 'Approved'")[0]['c'] ?? 0);

/* local mirror serves at /od9 (the /od9/public layout is retired — this path
   404'd every board image in local QA) */
$IMG = (($_SERVER['SERVER_NAME'] ?? '') === 'localhost' || strpos(__DIR__, 'xampp') !== false) ? '/od9/images/board' : '/images/board';
$h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>Your Board — <?= $h($zone['zone']) ?> · OD9</title>
<link rel="stylesheet" href="/css/board.css?v=<?= @filemtime(__DIR__ . '/../css/board.css') ?: '1' ?>">
<?php od9_ztrans_head(); ?>
<?php /* driver.js vendored locally (js/lib/, MIT, v1) — the CDN was a silent
         single point of failure for the tour: jsdelivr blocked by an adblocker
         or down = tour no-ops with no error. */ ?>
<link rel="stylesheet" href="/js/lib/driver.css?v=<?= @filemtime(__DIR__ . '/../js/lib/driver.css') ?: '1' ?>">
<link rel="stylesheet" href="/css/tour.css?v=<?= @filemtime(__DIR__ . '/../css/tour.css') ?: '1' ?>">
<style>
.od9-welcome{position:fixed;inset:0;z-index:9999;display:flex;align-items:center;justify-content:center;padding:24px}
.od9-welcome[hidden]{display:none}
.od9-welcome-bg{position:absolute;inset:0;background:rgba(6,8,10,.86);backdrop-filter:blur(6px)}
.od9-welcome-panel{position:relative;max-width:480px;width:100%;text-align:center;background:linear-gradient(180deg,#0d1117,#0a0a0a);border:1px solid var(--line-strong,#1c242a);border-radius:16px;padding:32px 28px;box-shadow:0 0 50px rgba(0,255,247,.18)}
.od9-welcome-portrait{width:96px;height:96px;margin:0 auto 12px;border-radius:50%;overflow:hidden;border:2px solid var(--world,#00FFF7);box-shadow:0 0 24px rgba(0,255,247,.4)}
.od9-welcome-portrait img{width:100%;height:100%;object-fit:cover;object-position:center 22%}
.od9-welcome-who{font-family:var(--font-mono,monospace);font-size:11px;letter-spacing:.16em;text-transform:uppercase;color:var(--world,#00FFF7);margin-bottom:10px}
.od9-welcome-panel h2{font-family:var(--font-display,sans-serif);font-weight:700;font-size:32px;color:var(--diamond,#F0F0F5);margin-bottom:12px}
.od9-welcome-panel p{color:var(--text-muted,#9AA6AB);font-size:15px;line-height:1.6;margin-bottom:22px}
.od9-welcome-actions{display:flex;flex-direction:column;gap:10px}
.od9-welcome-actions .cta{padding:13px 22px;background:linear-gradient(135deg,#00FFF7,#00BFFF);color:#06121a;font-family:var(--font-display,sans-serif);font-weight:700;letter-spacing:.04em;text-transform:uppercase;border:none;border-radius:10px;cursor:pointer;font-size:14px;box-shadow:0 0 22px rgba(0,255,247,.4)}
.od9-welcome-actions .cta-ghost{padding:11px;background:transparent;color:var(--text-faint,#5F6A70);border:1px solid var(--line-strong,#1c242a);border-radius:10px;cursor:pointer;font-size:13px;font-family:var(--font-display,sans-serif)}
</style>
</head>
<body data-tour="board"<?= $present ? ' class="present"' : '' ?> data-tour-csrf="<?= htmlspecialchars(od9_csrf_token(), ENT_QUOTES) ?>">
<?php od9_ztrans_body(); ?>
<?php if (isset($_GET['ascended'])): ?>
<!-- arrival cinematic after a PASSED gate check: play the gate clip over the new
     zone (muted video = autoplay-safe; odZtransPlay fails open if blocked). -->
<script>(function(){ if (window.odZtransPlay) { window.odZtransPlay('gate'); } })();</script>
<?php endif; ?>
<div class="board" style="background: linear-gradient(180deg, rgba(10,10,10,.35), rgba(10,10,10,.55) 45%, rgba(10,10,10,.92)), url('<?= $h($IMG) ?>/<?= $h($zone['img']) ?>') center 38% / cover no-repeat;">

  <header class="hud">
    <div class="brand">
      <img class="bmark" src="<?= $h($IMG) ?>/od9-logomark.png" alt="OD9" onerror="this.style.display='none'">
      <span class="bsep">//</span>
      <img class="bmark bword" src="<?= $h($IMG) ?>/ascend-wordmark.png" alt="ASCEND" onerror="this.style.display='none'">
      <span class="bsep">//</span>
      <span class="bzone"><?= $h($zone['zone']) ?></span>
    </div>
    <div class="spacer"></div>
    <div class="tier-badge"><span class="k">Current Tier</span><span class="v"><?= $h(ucfirst($tier)) ?></span></div>
    <?php if ($gate && $nextTier): ?>
    <div class="credits">
      <div class="num"><?= number_format($credits) ?> / <?= $gate['credits'] ?> CR</div>
      <div class="meter"><i style="width: <?= $gateCreditPct ?>%"></i></div>
      <div class="lbl">Progress to <?= $h($nextTier) ?></div>
    </div>
    <?php else: ?>
    <div class="credits maxed"><div class="num"><?= number_format($credits) ?> CR</div><div class="lbl">Max tier</div></div>
    <?php endif; ?>
    <a class="exit ztrans-link" data-ztrans="toolbox" href="<?= DASHBOARD_BASE_URL ?>/faq.php" title="How the progression system works">&#9432; FAQ</a>
    <?php /* two-faced beacon: "▶ START HERE" (gold, pulsing) until the tour is
             seen, then the subtle "ⓘ Tour" — tour.js toggles .seen
             from localStorage. Born from Law's first-lesson feedback (2026-07-26). */ ?>
    <a class="exit od9-tour-beacon" href="#" id="od9-tour-replay" title="Take the 90-second board tour"><span class="tc-new">&#9654; Start Here</span><span class="tc-seen">&#9432; Tour</span></a>
    <a class="exit" href="<?= DASHBOARD_BASE_URL ?>/world.php" title="The zoomed-out ascent — all five zones">World Map &rsaquo;</a>
    <a class="exit ztrans-link" data-ztrans="hatch" href="<?= DASHBOARD_BASE_URL ?>/bunker.php">The Bunker &rsaquo;</a>
    <a class="exit ztrans-link<?= $unreadNotif > 0 ? ' has-notif' : '' ?>" data-ztrans="<?= in_array($tier, ['architect','pioneer','benefactor'], true) ? 'hq-door-k1' : 'hq-door' ?>" href="<?= DASHBOARD_BASE_URL ?>/index.php">Dashboard<?php if ($unreadNotif > 0): ?> <span class="notif-dot" title="<?= $unreadNotif ?> new message<?= $unreadNotif === 1 ? '' : 's' ?>"><?= $unreadNotif ?></span><?php endif; ?> &rsaquo;</a>
  </header>

  <?php // Zone Navigator — the world map of tiers. Travel to any zone you've reached
        // (your own is interactive; lower zones read-only); future zones are locked
        // teasers. Reuses the existing ztrans "gate" transition. T-WEB-ZONE-NAV-001. ?>
  <nav class="zone-nav" aria-label="Zone map — your journey through the tiers">
    <?php foreach ($TIER_ORDER as $i => $tz):
      $z = ZONES[$tz];
      $unlocked  = ($i <= $memberIdx);
      $active    = ($tz === $tier);
      $isCurrent = ($tz === $memberTier);
      $cls = 'zone-node' . ($active ? ' active' : '') . ($isCurrent ? ' current' : '') . ($unlocked ? '' : ' locked');
      if ($unlocked): ?>
    <a class="<?= $cls ?> ztrans-link" data-ztrans="gate" href="?tier=<?= $h($tz) ?>">
      <span class="zn-i"><?= $i + 1 ?></span>
      <span class="zn-name"><?= $h($z['zone']) ?></span>
      <span class="zn-tier"><?= $h(ucfirst($tz)) ?><?= $isCurrent ? ' &middot; you' : '' ?></span>
    </a>
    <?php else: ?>
    <span class="<?= $cls ?>" title="Unlock at <?= $h(ucfirst($tz)) ?>">
      <span class="zn-i">&#128274;</span>
      <span class="zn-name"><?= $h($z['zone']) ?></span>
      <span class="zn-tier">Unlock at <?= $h(ucfirst($tz)) ?></span>
    </span>
    <?php endif; ?>
    <?php endforeach; ?>
  </nav>
  <?php if ($isPreview): ?>
  <div class="zone-preview-banner"><?php if ($readOnly): ?>&#128270; Previewing <b><?= $h($zone['zone']) ?></b> (<?= $h(ucfirst($tier)) ?>) — read-only.<?php else: ?>&#128296; Admin view — <b><?= $h($zone['zone']) ?></b> (<?= $h(ucfirst($tier)) ?>), fully interactive; anything you complete here applies to your account.<?php endif; ?> <a class="ztrans-link" data-ztrans="gate" href="?tier=<?= $h($memberTier) ?>">Return to <?= $h(ZONES[$memberTier]['zone']) ?> &rsaquo;</a></div>
  <?php endif; ?>

  <?php // World State → State of the Filter (canonical data) + the Live Roadmap panel.
        // (The old hardcoded rows had a stale CO₂ (426) and an unsourced GPI rank —
        // replaced by verified, single-source data; GPI can return once verified.) ?>
  <?php include __DIR__ . '/../includes/filter-scorecard-board.php'; ?>

  <div class="value">
    <div class="val-eyebrow">Your Verified Value — what your standing is built from</div>
    <?php foreach (DIMS as $dkey => $dmeta):
      // "Emerging" = no earning path at this tier yet, so show it muted with an
      // honest "unlocks" note rather than a value the member can't move. Resource
      // is the ROT / resource-sharing layer (far-future, scale-gated); System opens
      // at Theorist+ (evaluating others, project milestones) — an Observer can't
      // build it yet. The board must never imply a path that isn't there.
      $emerging = ($dkey === 'resource') || ($dkey === 'system' && $tier === 'observer');
      $have = (int) round($dim($dkey));
      $need = $emerging ? 0 : ($gate['dims'][$dkey] ?? 0);
      $pct  = $need ? min(100, (int) round($have / $need * 100)) : 0; ?>
    <div class="vd<?= ($dmeta['scarce'] && !$emerging) ? ' scarce' : '' ?><?= $emerging ? ' emerging' : '' ?>">
      <span class="vinfo" tabindex="0">i<span class="tip"><?= $h($dmeta['desc']) ?></span></span>
      <span class="vk"><?= $h($dmeta['label']) ?></span>
      <?php if ($emerging): ?>
      <span class="vv">&middot;</span>
      <span class="vsub"><?= $h($dmeta['emerge']) ?></span>
      <?php else: ?>
      <span class="vv"><?= $have ?><?php if ($need): ?> <small>/ <?= (int) $need ?></small><?php endif; ?></span>
      <?php if ($need): ?><div class="vbar"><i style="width: <?= $pct ?>%"></i></div><?php endif; ?>
      <span class="vsub"><?= $h($dmeta['sub']) ?></span>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
  <div class="val-note">&#9670; Every point is <b>verified</b> — earned only from evaluated contributions (reflections, discussions, capstones). Showing up earns recognition; <b>building</b> earns value.</div>

  <?php if ($cohort && $cohortMembers): ?>
  <div class="cohort">
    <div class="cohort-eyebrow">Your Cohort — climbing Volume I together</div>
    <div class="cohort-roster">
      <?php foreach ($cohortMembers as $m):
        $meRow = ((string)$m['user_id'] === (string)$discordId);
        $done  = (int)$m['lessons_done'];
        $cpct  = min(100, (int) round($done / 9 * 100)); ?>
      <div class="cm<?= $meRow ? ' me' : '' ?>">
        <span class="cm-name"><?= $h($m['username'] ?: 'member') ?><?php if ($meRow): ?> <small>&larr; you</small><?php endif; ?></span>
        <span class="cm-prog"><?= $done ?> / 9 lessons</span>
        <div class="cm-bar"><i style="width: <?= $cpct ?>%"></i></div>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="cohort-foot"><b><?= (int)($cohort['member_count'] ?? count($cohortMembers)) ?></b> climbing together &mdash; you're not climbing alone.</div>
  </div>
  <?php endif; ?>

  <?php if ($flash): ?><div class="flash <?= $h($flashKind) ?>"><?= $h($flash) ?></div><?php endif; ?>

  <div class="map helixmap">
    <!-- Origin crest (T-WEB-BOARD-HELIX-002): a per-tier illustrated emblem filling the
         empty lower-left corner the strands rise out of. z-index:2 = above the strands but
         ALWAYS under the nodes + "YOU ARE HERE" (z-index:3), and pointer-events:none so
         every node stays clickable. Masked toward the top-right so it CONNECTS to the helix
         base without a hard edge crossing it. Hidden until the tier's art exists — founder
         generates in Flow -> scripts/web/images/board/crests/<tier>.png (transparent). -->
    <img class="origin-crest" src="<?= $h($IMG) ?>/dna/<?= $h($tier) ?>.png?v=<?= @filemtime(__DIR__ . '/../images/board/dna/' . $tier . '.png') ?: '1' ?>" alt="" aria-hidden="true" onerror="this.style.display='none'">
    <!-- THE DOUBLE HELIX — two strands (Twin Singularities) + base-pair rungs, climbing
         the diagonal bottom-left -> top-right into the Gate. Each node = a tier doc,
         clickable to focus it in the dock (the reflection binds to that content_id). -->
    <svg class="pathsvg" viewBox="0 0 100 100" preserveAspectRatio="none">
      <?php foreach ($helixRungs as $r): ?>
      <line x1="<?= $r[0] ?>" y1="<?= $r[1] ?>" x2="<?= $r[2] ?>" y2="<?= $r[3] ?>" stroke="rgba(170,221,255,0.30)" stroke-width="0.55" />
      <?php endforeach; ?>
      <path d="<?= $helixA ?>" fill="none" stroke="rgba(0,255,247,0.16)" stroke-width="3" stroke-linecap="round" />
      <path d="<?= $helixA ?>" fill="none" stroke="rgba(0,255,247,0.85)" stroke-width="0.8" stroke-linecap="round" />
      <path d="<?= $helixB ?>" fill="none" stroke="rgba(122,0,255,0.18)" stroke-width="3" stroke-linecap="round" />
      <path d="<?= $helixB ?>" fill="none" stroke="rgba(155,92,255,0.8)" stroke-width="0.8" stroke-linecap="round" />
    </svg>
    <?php foreach ($journey as $jn):
      $jicon = $jn['state']==='done' ? '&#10003;' : ($jn['type']==='video' ? '&#9654;' : ($jn['opt'] ? '&#43;' : ($jn['state']==='current' ? '&#9654;' : '&#9670;')));
      if (!empty($jn['qotd']) && $jn['state'] !== 'done') { $jicon = '&#63;'; }
      if (!empty($jn['guide'])) { $jicon = '&#128220;'; }
      $jclick = !in_array($jn['state'], ['done','pending'], true);
      $jhref = '?' . ($isPreview ? 'tier=' . rawurlencode($tier) . '&' : '')
             . (!empty($jn['watch']) ? 'wmod=' . rawurlencode($jn['mid']) : 'focus=' . $jn['cid']);
      if (!empty($jn['qotd'])) { $jhref = '#qotd'; }   /* JS opens the QOTD dock in place */
      if (!empty($jn['guide'])) { $jhref = '#guideask'; }   /* JS focuses the ask lane */
      $jcls = 'hnode ' . $jn['state'] . ($jn['opt'] ? ' opt' : '') . (!empty($jn['qotd']) ? ' qotd' : '') . (!empty($jn['guide']) ? ' gtalk' : ''); ?>
      <?php if ($jclick): ?>
      <a class="<?= $jcls ?>" data-mid="<?= $h($jn['mid']) ?>" style="left:<?= $jn['x'] ?>%;top:<?= $jn['y'] ?>%" href="<?= $h($jhref) ?>" title="<?= $h($jn['title']) ?>"><span class="dot"><?= $jicon ?></span><?php if ($jn['state']==='current'): ?><span class="here">You are here</span><?php endif; ?></a>
      <?php else: ?>
      <span class="<?= $jcls ?>" data-mid="<?= $h($jn['mid']) ?>" style="left:<?= $jn['x'] ?>%;top:<?= $jn['y'] ?>%" title="<?= $h($jn['title']) ?>"><span class="dot"><?= $jicon ?></span></span>
      <?php endif; ?>
    <?php endforeach; ?>

    <!-- THE GATE (top-right) -->
    <?php if ($gate && $nextTier):
      $kMet = $credits >= $gate['credits'];
      $dimsTotal = count($gate['dims']);
      $dimsMet = 0; foreach ($gate['dims'] as $d => $need) { if ($dim($d) >= $need) $dimsMet++; }
      $dimsAllMet = $dimsMet >= $dimsTotal; $reqMet = ($reqTotal > 0 && $reqDone >= $reqTotal); ?>
    <div class="gate">
      <div class="gate-frame"><img src="<?= $h($IMG) ?>/gate/gate-advancement.jpg" alt="Gate" onerror="this.style.display='none'"><span class="gtag">Gate &middot; <?= $h($nextTier) ?> &middot; Locked</span></div>
      <div class="gate-locks">
        <h4>To pass the gate</h4>
        <div class="lock <?= $kMet ? 'met' : '' ?>"><span class="x"><?= $kMet ? '&#10003;' : '' ?></span><b>Credits <?= number_format($credits) ?> / <?= $gate['credits'] ?></b></div>
        <div class="lock <?= $dimsAllMet ? 'met' : '' ?>"><span class="x"><?= $dimsAllMet ? '&#10003;' : '' ?></span><b>Value dimensions <?= $dimsMet ?> / <?= $dimsTotal ?> met</b></div>
        <div class="lock <?= $reqMet ? 'met' : '' ?>"><span class="x"><?= $reqMet ? '&#10003;' : '' ?></span><b>Required curriculum <?= $reqDone ?> / <?= $reqTotal ?></b></div>
        <?php if ($tier === $memberTier && !$readOnly): ?>
        <!-- Face the Gate (chunk 4): member-triggered advancement check — the bot
             runs the REAL promotion path and answers in the Archivist's voice. -->
        <form class="gate-face" method="post" action="board-action.php">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(od9_csrf_token(), ENT_QUOTES) ?>">
          <input type="hidden" name="action" value="gate_check">
          <input type="hidden" name="tier" value="<?= $h($tier) ?>">
          <button class="cta gate-face-btn" type="submit">&#9670; Face the Gate</button>
        </form>
        <?php endif; ?>
      </div>
    </div>
    <?php else: ?>
    <!-- Max-tier SUMMIT terminus (T-WEB-BOARD-SUMMIT-001): at the top tier the helix has
         no gate to climb into, so it lands on a "mastery" emblem instead of empty space.
         Art: images/board/summit/<tier>.png (founder-generates in Flow; onerror-hidden so
         the label alone holds the spot until the art exists). -->
    <div class="summit">
      <img class="summit-art" src="<?= $h($IMG) ?>/summit/<?= $h($tier) ?>.jpeg?v=<?= @filemtime(__DIR__ . '/../images/board/summit/' . $tier . '.jpeg') ?: '1' ?>" alt="" aria-hidden="true" onerror="this.style.display='none'">
      <span class="summit-tag">&#9671; TYPE I &middot; THE HORIZON REACHED</span>
    </div>
    <?php endif; ?>

    <!-- THE GUIDE (bottom-right) — the Coach (SPEC §4, chunk 3). The chips are a
         FIXED intent menu (mirror of utils/coach.py INTENTS): a click asks the bot
         via coach-ask.php and the live answer lands in the speech bubble. -->
    <div class="guide">
      <div class="portrait"><img src="<?= $h($IMG) ?>/guides/<?= $h($zone['guide_img']) ?>?v=<?= @filemtime(__DIR__ . '/../images/board/guides/' . $zone['guide_img']) ?: '1' ?>" alt="<?= $h($zone['guide']) ?>" onerror="this.parentElement.style.display='none'"></div>
      <div class="say">
        <div class="who">Your Guide — <?= $h($zone['guide']) ?></div>
        <div class="vo">&ldquo;<?= $h($zone['vo']) ?>&rdquo;</div>
        <div class="coach-chips" role="group" aria-label="Ask your Guide">
          <button type="button" class="coach-chip" data-intent="next_move">My next move?</button>
          <button type="button" class="coach-chip" data-intent="the_gate">The Gate?</button>
          <button type="button" class="coach-chip" data-intent="how_advance">Advancing?</button>
          <button type="button" class="coach-chip" data-intent="credits">Credits?</button>
          <button type="button" class="coach-chip" data-intent="reviews">Reviews?</button>
        </div>
        <!-- guide_talk deep mode (SPEC §4): freeform lane — the Archivist answers
             from the manifesto itself, with citations, or says it isn't in there. -->
        <div class="guide-askrow" id="guideask">
          <input class="guide-askinput" type="text" maxlength="500"
                 placeholder="Ask the record anything&hellip;" aria-label="Ask the Archivist about the manifesto">
          <button type="button" class="guide-askbtn" title="Ask the Archivist">&#10148;</button>
        </div>
        <div class="guide-cites" hidden></div>
      </div>
    </div>

    <!-- DOCK (bottom-left): QOTD + the focused quest (read + reflect, bound to content_id) -->
    <div class="dockwrap">
      <?php if ($qrow && !$qotdAnswered && !$readOnly): ?>
      <details class="questdock qotd" id="qotd">
        <summary class="qd-head"><span class="qd-eyebrow">QOTD &middot; Today &middot; +3 CR</span><span class="qd-q"><?= $h($qrow['question_text']) ?></span></summary>
        <form class="qd-form" method="post" action="board-action.php">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(od9_csrf_token(), ENT_QUOTES) ?>">
          <input type="hidden" name="action" value="qotd_answer">
          <input type="hidden" name="post_id" value="<?= (int)$qrow['post_id'] ?>">
          <input type="hidden" name="tier" value="<?= $h($tier) ?>">
          <textarea name="text" required minlength="1" placeholder="Your answer..."></textarea>
          <button class="cta" type="submit">Submit answer</button>
        </form>
      </details>
      <?php endif; ?>

      <?php if ($wfocus):
        // Watch panel (SPEC §3 watch type): play the cutscene, one click to
        // log it. Media file resolved through the guide-video registry (the
        // same key->file map the Discord DM player uses).
        include_once __DIR__ . '/../guide-registry.php';
        $wKey = (string)($wfocus['media_key'] ?? '');
        $wFile = isset($VIDEO_MAP[$wKey]) ? (string)$VIDEO_MAP[$wKey][0] : '';
        $wIsLocal = (($_SERVER['SERVER_NAME'] ?? '') === 'localhost' || strpos(__DIR__, 'xampp') !== false);
        $wSrc = $wFile !== '' ? ($wIsLocal ? '/od9' : '') . '/guide-videos/' . rawurlencode($wFile) : '';
        $wDone = !empty($wfocus['module_completed_at']); ?>
      <div class="questdock">
        <div class="qd-eyebrow">&#9654; Cutscene &middot; The Archivist</div>
        <div class="qd-card"><div class="qd-body"><div class="ty">Watch &middot; The Wake</div><div class="ti"><?= $h($wfocus['title']) ?></div></div><div class="qd-rw"><span class="cr">+<?= (int)$wfocus['reward_credits'] ?> CR</span></div></div>
        <?php if ($wSrc !== ''): ?>
        <video src="<?= $h($wSrc) ?>" controls playsinline preload="metadata" style="width:100%;border-radius:10px;background:#000;margin:.5rem 0"></video>
        <?php endif; ?>
        <?php if ($wDone): ?>
        <div class="preview-note">&#10003; Watched &mdash; the Archivist remembers.</div>
        <?php elseif ($readOnly): ?>
        <div class="preview-note">&#128270; Preview &mdash; return to your own zone to log it.</div>
        <?php else: ?>
        <form class="reflect" method="post" action="board-action.php">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(od9_csrf_token(), ENT_QUOTES) ?>">
          <input type="hidden" name="action" value="watch_complete">
          <input type="hidden" name="module_id" value="<?= $h((string)$wfocus['module_id']) ?>">
          <input type="hidden" name="tier" value="<?= $h($tier) ?>">
          <div class="row"><span class="hint"><span class="okmsg">+<?= (int)$wfocus['reward_credits'] ?> CR for showing up</span></span><button class="cta" type="submit">I watched it &mdash; continue &rarr;</button></div>
        </form>
        <?php endif; ?>
      </div>
      <?php elseif ($focus):
        $fcid = (int)$focus['content_id']; $fcurl = trim((string)($focus['url'] ?? '')); $fctype = strtolower((string)$focus['content_type']);
        $frej = ((string)($focus['review_status'] ?? '') === 'rejected');
        $fpending = ((string)($focus['review_status'] ?? '') === 'pending_review');
        $fembed = ''; $fopen = '&#128214; Read it here &rarr;'; $ftype = 'Read + Check';
        if ($fctype === 'video' && preg_match('~(?:[?&]v=|youtu\.be/|/embed/)([A-Za-z0-9_-]{6,})~', $fcurl, $vm)) { $fembed = 'https://www.youtube.com/embed/' . $vm[1]; $fopen = '&#9654; Watch it here &rarr;'; $ftype = 'Watch + Reflect'; }
        elseif (preg_match('~/library/([^/]+)/([^/?#]+\.pdf)$~i', $fcurl, $pm)) { $fembed = '/download.php?tier=' . rawurlencode(strtolower($pm[1])) . '&file=' . rawurlencode($pm[2]); }
        elseif ($fctype === 'canon' && $fcurl !== '') { $fembed = $fcurl . (strpos($fcurl, '?') === false ? '?embed=1' : '&embed=1'); } ?>
      <div class="questdock">
        <div class="qd-eyebrow">&#9670; Your move this turn</div>
        <div class="qd-card"><div class="qd-body"><div class="ty"><?= $ftype ?> &middot; Knowledge</div><div class="ti"><?= $h($focus['title']) ?></div></div><div class="qd-rw"><span class="cr">+<?= (int)$focus['credit_value'] ?> CR</span></div></div>
        <?php if ($fpending): ?><div class="preview-note">&#9203; Submitted &mdash; a reviewer is confirming this one (the capstone is the synthesis gate, so a person signs off). Credit + advancement land on approval; meanwhile keep moving through the rest of your journey.</div>
        <?php elseif ($frej && !empty($focus['review_notes'])): ?><div class="reject-note"><b>Feedback:</b> <?= $h($focus['review_notes']) ?></div><?php endif; ?>
        <?php if ($readOnly): ?>
        <div class="preview-note">&#128270; Preview — return to your own zone to reflect &amp; earn credit.</div>
        <?php elseif (!$fpending): ?>
        <?php if ($fembed !== ''): $isCanon = ($fctype === 'canon'); ?>
        <button type="button" class="read-link" data-embed="<?= $h($fembed) ?>" data-title="<?= $h($focus['title']) ?>" data-unlock="reflect-<?= $fcid ?>" onclick="<?= $isCanon ? 'odCodex(this.dataset.embed,this.dataset.title,this.dataset.unlock)' : 'odReader(this.dataset.embed,this.dataset.title,this.dataset.unlock,true)' ?>"><?= $fopen ?></button>
        <?php else: ?>
        <a class="read-link" href="<?= $fcurl !== '' ? $h($fcurl) : '/library.php' ?>" target="_blank" rel="noopener" onclick="odUnlock('reflect-<?= $fcid ?>')"><?= $fopen ?></a>
        <?php endif; ?>
        <form class="reflect" id="reflect-<?= $fcid ?>" method="post" action="board-action.php">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(od9_csrf_token(), ENT_QUOTES) ?>">
          <input type="hidden" name="action" value="content_read_check">
          <input type="hidden" name="content_id" value="<?= $fcid ?>">
          <input type="hidden" name="tier" value="<?= $h($tier) ?>">
          <input type="hidden" name="next_id" value="<?= $nextFocusId ?>">
          <textarea name="reflection" required minlength="50" placeholder="Open the material above first, then reflect…" disabled></textarea>
          <div class="row"><span class="hint"><span class="lockmsg">&#128274; Open it to unlock</span><span class="okmsg" hidden>+<?= (int)$focus['credit_value'] ?> CR if it passes</span></span><button class="cta" type="submit" disabled>Submit reflection</button></div>
        </form>
        <?php endif; ?>
      </div>
      <?php elseif (!$contentTodo): ?>
      <?php if ($reqPending > 0): ?>
      <div class="questdock"><div class="qd-eyebrow">&#9203; In review</div><div class="qd-card"><div class="qd-body"><div class="ti"><?= (int)$reqPending ?> capstone submission<?= $reqPending === 1 ? '' : 's' ?> awaiting a reviewer — credit + advancement land on approval.</div></div></div></div>
      <?php else: ?>
      <div class="questdock"><div class="qd-eyebrow">&#10003; Curriculum</div><div class="qd-card"><div class="qd-body"><div class="ti">You've cleared this tier's curriculum.</div></div></div></div>
      <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>

  <div class="tagline">Level Up or Get Left Behind</div>
</div>

<!-- In-board reading modal (Phase 2). Opened by the content move's "Read it here"
     button (which also defines window.__odClose); closing it (overlay / × / Done)
     unlocks the reflection. Wiring is via inline onclick (kept — still works). NOTE:
     inline <script> on this site WAS broken by the ORIGIN HTML minifier collapsing
     newlines (config/minify.php; NOT Cloudflare, despite an earlier mis-blame) —
     fixed 2026-06-20. Inline scripts are safe now provided they avoid // line
     comments. See memory reference_origin_html_minifier. -->
<style>.reader-iosdoc{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:1rem;padding:2rem;text-align:center;background:#0a0a12}.reader-iosdoc[hidden]{display:none}.reader-iosdoc p{color:#c4c7cb;line-height:1.6;max-width:320px}.reader-pop{margin-left:auto;margin-right:.7rem;font-size:.78rem;color:#00FFF7;text-decoration:none;border:1px solid rgba(0,255,247,.35);padding:.22rem .6rem;border-radius:4px;white-space:nowrap}.reader-pop[hidden]{display:none}</style>
<div class="reader" id="reader" hidden>
  <div class="reader-overlay" onclick="if(window.__odClose)window.__odClose()"></div>
  <div class="reader-bulletin" aria-hidden="true">&#9670; OD9 &middot; INCOMING &#9670;</div>
  <div class="reader-panel">
    <div class="reader-head">
      <span class="reader-title">Reading</span>
      <a class="reader-pop" target="_blank" rel="noopener" href="#" hidden>Open &#8599;</a>
      <button class="reader-x" type="button" aria-label="Close" onclick="if(window.__odClose)window.__odClose()">&times;</button>
    </div>
    <iframe class="reader-frame" title="Document" src="about:blank"></iframe>
    <div class="reader-iosdoc" hidden>
      <p>&#128241; iPhone shows embedded documents one page at a time &mdash; open the full thing instead:</p>
      <a class="cta" target="_blank" rel="noopener" href="#">&#128214; Open the full document &#8599;</a>
      <p class="reader-hint">Read it there, then come back and hit <b>Done</b> to unlock your reflection.</p>
    </div>
    <div class="reader-foot">
      <span class="reader-hint">Read it through — close when you're done to unlock your reflection.</span>
      <button class="cta reader-done" type="button" onclick="if(window.__odClose)window.__odClose()">Done reading &rarr;</button>
    </div>
  </div>
</div>

<!-- Codex unseal transition: official OD9 codex docs play /video/transitions/codex.* full-screen, then the reader opens (no newspaper spin). Everything else gets the spin. -->
<div class="codex-trans" id="codexTrans" hidden><video class="codex-vid" id="codexVid" muted playsinline preload="none"></video></div>
<script>
(function(){
  var VID = <?= json_encode(((($_SERVER['SERVER_NAME'] ?? '') === 'localhost') || strpos(__DIR__, 'xampp') !== false) ? '/od9/video/transitions' : '/video/transitions', JSON_UNESCAPED_SLASHES) ?>;
  var reader = document.getElementById('reader');
  var rframe = reader ? reader.querySelector('.reader-frame') : null;
  var rtitle = reader ? reader.querySelector('.reader-title') : null;
  function unlock(id){ var f = id ? document.getElementById(id) : null; if (!f) return; f.querySelectorAll('textarea,button[type=submit]').forEach(function(e){ e.disabled = false; }); var k = f.querySelector('.lockmsg'), o = f.querySelector('.okmsg'); if (k) k.hidden = true; if (o) o.hidden = false; }
  window.odUnlock = unlock;
  window.__odClose = function(){ if (!reader) return; reader.setAttribute('hidden', ''); reader.classList.remove('spin'); if (rframe) rframe.src = 'about:blank'; document.body.style.overflow = ''; var u = reader.getAttribute('data-unlock'); if (u) unlock(u); };
  /* Present mode propagates INTO the reader iframe: the codex document is the
     surface actually being read aloud during a sermon, and it is a separate
     document (board.css cannot reach it). Only codex embeds understand the
     flag — YouTube/download.php URLs are left untouched. One chokepoint here
     covers every caller (odCodex, odReader, journey nodes). */
  function presentEmbed(u){
    if (!document.body.classList.contains('present')) return u;
    if (!u || u.indexOf('embed=1') === -1) return u;
    return u + '&present=1';
  }
  window.odReader = function(embed, title, unlockId, spin){ if (!reader) return; embed = presentEmbed(embed); var fb = reader.querySelector('.reader-iosdoc'); var pop = reader.querySelector('.reader-pop'); var isDoc = (embed || '').indexOf('download.php') !== -1; var ios = /iPad|iPhone|iPod/.test(navigator.userAgent || ''); if (pop) { if (isDoc) { pop.href = embed; pop.removeAttribute('hidden'); } else { pop.setAttribute('hidden',''); } } if (fb && ios && isDoc) { var fa = fb.querySelector('a.cta'); if (fa) fa.href = embed; fb.removeAttribute('hidden'); if (rframe) { rframe.src = 'about:blank'; rframe.style.display = 'none'; } } else { if (fb) fb.setAttribute('hidden',''); if (rframe) { rframe.style.display = ''; rframe.src = embed; } } if (rtitle) rtitle.textContent = title || 'Reading'; reader.setAttribute('data-unlock', unlockId || ''); reader.classList.toggle('spin', !!spin); reader.removeAttribute('hidden'); document.body.style.overflow = 'hidden'; };
  var cov = document.getElementById('codexTrans'), cv = document.getElementById('codexVid');
  window.odCodex = function(embed, title, unlockId){
    if (!cov || !cv) { window.odReader(embed, title, unlockId, false); return; }
    var done = false; var open = function(){ if (done) return; done = true; cov.setAttribute('hidden', ''); window.odReader(embed, title, unlockId, false); };
    cv.innerHTML = '<source src="' + VID + '/codex.webm" type="video/webm"><source src="' + VID + '/codex.mp4" type="video/mp4">';
    cov.removeAttribute('hidden'); cv.onended = open; cv.onerror = open; try { cv.load(); } catch (e) {} var p = cv.play(); if (p && p.catch) p.catch(open); setTimeout(open, 8000);
  };
})();
(function(){
  /* Presence pins (SPEC §5): consensual member tokens at their current node.
     Anchored to the [data-mid] node elements so positions always match the
     rendered helix; polled from presence.php (~25s). Anonymous tokens carry
     no name; the payload never carries ids at all. Desktop only — the mobile
     journey is a static dot track. */
  var map = document.querySelector('.map.helixmap');
  if (!map || window.innerWidth <= 760) { return; }
  var zone = <?= json_encode($tier, JSON_UNESCAPED_SLASHES) ?>;
  var layer = document.createElement('div');
  layer.className = 'presence-layer';
  map.appendChild(layer);
  function render(data){
    layer.innerHTML = '';
    if (!data || !data.ok) { return; }
    var byNode = {};
    (data.tokens || []).forEach(function(t){
      if (!byNode[t.node]) { byNode[t.node] = []; }
      byNode[t.node].push(t);
    });
    Object.keys(byNode).forEach(function(mid){
      var host = map.querySelector('[data-mid="' + (window.CSS && CSS.escape ? CSS.escape(mid) : mid) + '"]');
      if (!host) { return; }
      var list = byNode[mid];
      var wrap = document.createElement('div');
      wrap.className = 'ptok-cluster';
      wrap.style.left = host.style.left;
      wrap.style.top = host.style.top;
      var shown = list.slice(0, 4);
      shown.forEach(function(t, i){
        var el = document.createElement('span');
        el.className = 'ptok ' + (t.mode === 'visible' ? 'named' : 'anon');
        el.style.setProperty('--pi', String(i));
        if (t.mode === 'visible' && t.name) {
          el.textContent = t.name.slice(0, 1).toUpperCase();
          el.title = t.name + ' is on this step';
        } else {
          el.title = 'A member is on this step';
        }
        wrap.appendChild(el);
      });
      if (list.length > shown.length) {
        var more = document.createElement('span');
        more.className = 'ptok more';
        more.textContent = '+' + (list.length - shown.length);
        more.title = list.length + ' members are on this step';
        wrap.appendChild(more);
      }
      layer.appendChild(wrap);
    });
    var gate = map.querySelector('.gate');
    if (gate && data.gate > 0) {
      var g = document.createElement('div');
      g.className = 'ptok-gate';
      g.textContent = String(data.gate) + ' at the Gate';
      g.title = String(data.gate) + ' member(s) have met every requirement';
      layer.appendChild(g);
    }
  }
  function poll(){
    fetch('presence.php?zone=' + encodeURIComponent(zone), { credentials: 'same-origin' })
      .then(function(r){ return r.json(); })
      .then(render)
      .catch(function(){ /* empty room on error; try again next tick */ });
  }
  poll();
  setInterval(poll, 25000);
})();
(function(){
  /* QOTD beacon (chunk 5): the daily node opens the QOTD dock in place. */
  var qn = document.querySelector('a.hnode.qotd');
  var qd = document.querySelector('.questdock.qotd');
  if (qn && qd) {
    qn.addEventListener('click', function(e){
      e.preventDefault();
      qd.setAttribute('open', '');
      qd.scrollIntoView({ behavior: 'smooth', block: 'center' });
      var ta = qd.querySelector('textarea');
      if (ta) { setTimeout(function(){ ta.focus(); }, 450); }
    });
  }
})();
(function(){
  var g = document.querySelector('.helixmap .guide'); if (!g) { return; }
  var vo = g.querySelector('.vo'); var who = g.querySelector('.who'); if (!vo || !who) { return; }
  var who0 = who.textContent; var vo0 = vo.innerHTML;
  var csrf = document.body.getAttribute('data-tour-csrf') || '';
  var timer = null; var busy = false;
  function chips(){ return g.querySelectorAll('.coach-chip'); }
  function reveal(text){
    if (timer) { clearInterval(timer); }
    var i = 0; vo.textContent = '“…';
    timer = setInterval(function(){
      i += 2;
      if (i >= text.length) { vo.textContent = '“' + text + '”'; clearInterval(timer); timer = null; }
      else { vo.textContent = '“' + text.slice(0, i) + '…'; }
    }, 14);
  }
  chips().forEach(function(btn){
    btn.addEventListener('click', function(){
      if (busy) { return; } busy = true;
      chips().forEach(function(b){ b.classList.remove('on'); b.disabled = true; });
      btn.classList.add('on'); vo.textContent = '…';
      var fd = new FormData();
      fd.append('intent', btn.getAttribute('data-intent') || '');
      fd.append('csrf_token', csrf);
      fetch('coach-ask.php', { method: 'POST', body: fd, credentials: 'same-origin' })
        .then(function(r){ return r.json(); })
        .then(function(d){
          if (d && d.ok && d.text) { who.textContent = d.speaker || 'The Archivist'; reveal(d.text); }
          else { reveal('The record is quiet right now. Try again in a moment.'); }
        })
        .catch(function(){ who.textContent = who0; vo.innerHTML = vo0; })
        .then(function(){ busy = false; chips().forEach(function(b){ b.disabled = false; }); });
    });
  });
  /* guide_talk deep mode: the freeform ask lane shares the bubble, the busy
     flag, and the reveal — the Archivist is ONE voice, two kinds of question. */
  var input = g.querySelector('.guide-askinput');
  var send = g.querySelector('.guide-askbtn');
  var cites = g.querySelector('.guide-cites');
  function askGuide(){
    if (busy || !input) { return; }
    var q = (input.value || '').trim();
    if (!q) { return; }
    busy = true; send.disabled = true; input.disabled = true;
    if (cites) { cites.hidden = true; cites.textContent = ''; }
    who.textContent = 'The Archivist'; vo.textContent = '… consulting the record …';
    var fd = new FormData();
    fd.append('question', q);
    fd.append('csrf_token', csrf);
    fetch('guide-ask.php', { method: 'POST', body: fd, credentials: 'same-origin' })
      .then(function(r){ return r.json(); })
      .then(function(d){
        if (d && d.text) {
          reveal(d.text);
          if (cites && d.citations && d.citations.length) {
            cites.textContent = 'From the record: ' + d.citations.slice(0, 3).join(' · ');
            cites.hidden = false;
          }
          if (d.ok && !d.capped) { input.value = ''; }
        } else { reveal('The record is quiet right now. Try again in a moment.'); }
      })
      .catch(function(){ reveal('The record is out of reach — try again in a moment.'); })
      .then(function(){ busy = false; send.disabled = false; input.disabled = false; });
  }
  if (send) { send.addEventListener('click', askGuide); }
  if (input) { input.addEventListener('keydown', function(e){ if (e.key === 'Enter') { e.preventDefault(); askGuide(); } }); }
  var gnode = document.querySelector('a.hnode.gtalk');
  if (gnode && input) {
    gnode.addEventListener('click', function(e){
      e.preventDefault();
      g.scrollIntoView({ behavior: 'smooth', block: 'center' });
      setTimeout(function(){ input.focus(); }, 450);
    });
  }
})();
</script>
<div class="od9-welcome" id="od9-welcome" hidden>
  <div class="od9-welcome-bg"></div>
  <div class="od9-welcome-panel">
    <div class="od9-welcome-portrait"><img src="<?= $h($IMG) ?>/guides/archivist.jpg?v=<?= @filemtime(__DIR__ . '/../images/board/guides/archivist.jpg') ?: '1' ?>" alt="The Archivist" onerror="this.parentElement.style.display='none'"></div>
    <div class="od9-welcome-who">The Archivist</div>
    <h2>You made it.</h2>
    <p>Most people never even ask the question. You did &mdash; that's why you're here. Welcome to OD9, <b><?= $h($user['username'] ?? 'friend') ?></b>. This is your board. Let me show you around &mdash; then go make your first move.</p>
    <div class="od9-welcome-actions">
      <button type="button" class="cta" id="od9-welcome-go">Show me around &rarr;</button>
      <button type="button" class="cta-ghost" id="od9-welcome-skip">I'll explore on my own</button>
    </div>
  </div>
</div>

<script src="/js/lib/driver.js.iife.js?v=<?= @filemtime(__DIR__ . '/../js/lib/driver.js.iife.js') ?: '1' ?>"></script>
<script src="/dashboard/tour.js?v=<?= @filemtime(__DIR__ . '/tour.js') ?: '1' ?>"></script>
</body>
</html>
