<?php
/**
 * OD9 Community Pulse Endpoint
 *
 * Read-only weekly metrics endpoint, called by the Sunday-morning Claude
 * remote routine. Returns JSON or markdown summarizing the past week's
 * community state. Token-gated via OD9_PULSE_TOKEN constant in
 * pulse_secret.config.php (sibling, mode 600, gitignored).
 *
 * Mounted at https://offda9.com/api/v1/pulse.php
 *
 * Query params:
 *   ?token=<secret>      REQUIRED - constant-time compared
 *   ?format=json|markdown  default json. markdown = pre-formatted Discord-ready text
 *
 * Data source: direct SQLite read of /home/ultimaterage/od9-discord-bot/data/od9.db
 *   (offda9 has world-readable access). ALL metrics read the bot's live DB —
 *   members + Patreon from `users`, plus content_completion / qotd_answer_streaks /
 *   think_tank_attendance. The od9_bot_* MySQL mirror was retired 2026-06-28.
 */

declare(strict_types=1);

const SQLITE_PATH = '/home/ultimaterage/od9-discord-bot/data/od9.db';
const FOUNDING_CAP = 25;

// ---------------------------------------------------------------------------
// Auth: token must match OD9_PULSE_TOKEN from sibling config (mode 600).
// ---------------------------------------------------------------------------
$secretFile = __DIR__ . '/pulse_secret.config.php';
if (!file_exists($secretFile)) {
    http_response_code(503);
    header('Content-Type: text/plain');
    echo "pulse not configured: missing pulse_secret.config.php\n";
    exit;
}
require_once $secretFile;
if (!defined('OD9_PULSE_TOKEN') || OD9_PULSE_TOKEN === '') {
    http_response_code(503);
    header('Content-Type: text/plain');
    echo "pulse not configured: OD9_PULSE_TOKEN missing\n";
    exit;
}

$token_in = (string)($_GET['token'] ?? '');
if ($token_in === '' || !hash_equals(OD9_PULSE_TOKEN, $token_in)) {
    http_response_code(401);
    header('Content-Type: text/plain');
    echo "unauthorized\n";
    exit;
}

// ---------------------------------------------------------------------------
// Connect to both data stores
// ---------------------------------------------------------------------------
$configPath = __DIR__ . '/../../../config/database.php';
if (!file_exists($configPath)) $configPath = __DIR__ . '/../../config/database.php';
if (!file_exists($configPath)) $configPath = __DIR__ . '/config/database.php';
require_once $configPath;

// Local mode: skip error_log calls entirely (XAMPP doesn't have prod schema,
// every dev hit would otherwise spam ~4 lines into apache/logs/error.log).
function pulse_log(string $msg): void {
    if (defined('ENVIRONMENT') && ENVIRONMENT === 'local') return;
    error_log('[pulse] ' . $msg);
}

function safe_int(callable $fn): int {
    try { return (int)$fn(); }
    catch (Throwable $e) {
        pulse_log('query failed: ' . $e->getMessage());
        return -1;
    }
}

// All metrics now read the bot's live SQLite directly — the od9_bot_* MySQL mirror
// was retired 2026-06-28.
$sqlite = null;
try {
    $sqlite = new PDO('sqlite:' . SQLITE_PATH);
    $sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Throwable $e) { pulse_log('sqlite connect failed: ' . $e->getMessage()); }

// ---------------------------------------------------------------------------
// Run queries
// ---------------------------------------------------------------------------
$pulse = [
    'generated_at' => gmdate('c'),
    'window_days'  => 7,
];

// 1) Reflections auto-approved this week (SQLite)
$pulse['reflections_auto_approved_7d'] = safe_int(function() use ($sqlite) {
    if (!$sqlite) return -1;
    $s = $sqlite->query(
        "SELECT COUNT(*) FROM content_completion
         WHERE review_status = 'approved'
           AND reviewed_by = 'auto_approver'
           AND completed_date >= date('now', '-7 days')"
    );
    return $s->fetchColumn();
});

// 1b) Reflections approved by humans this week (for context)
$pulse['reflections_human_approved_7d'] = safe_int(function() use ($sqlite) {
    if (!$sqlite) return -1;
    $s = $sqlite->query(
        "SELECT COUNT(*) FROM content_completion
         WHERE review_status = 'approved'
           AND reviewed_by != 'auto_approver'
           AND completed_date >= date('now', '-7 days')"
    );
    return $s->fetchColumn();
});

// 1c) Reflections still pending review
$pulse['reflections_pending'] = safe_int(function() use ($sqlite) {
    if (!$sqlite) return -1;
    $s = $sqlite->query(
        "SELECT COUNT(*) FROM content_completion WHERE review_status = 'pending_review'"
    );
    return $s->fetchColumn();
});

// 2) New Discord members this week (SQLite — the od9_bot_users mirror was retired)
$pulse['new_members_7d'] = safe_int(function() use ($sqlite) {
    if (!$sqlite) return -1;
    $s = $sqlite->query(
        "SELECT COUNT(*) FROM users
         WHERE join_date >= date('now', '-7 days')"
    );
    return $s->fetchColumn();
});

// Lifetime throughput, NOT current membership: users rows survive leaves,
// kicks, and bans (founder correction 2026-07-26). The bot cog injects
// current_discord_members from the live guild before analysis.
$pulse['lifetime_registered_members'] = safe_int(function() use ($sqlite) {
    if (!$sqlite) return -1;
    return $sqlite->query("SELECT COUNT(*) FROM users")->fetchColumn();
});

// 3) Patreon tier breakdown (SQLite)
$tier_counts = [];
$founding_filled = 0;
$mrr_dollars = 0;
$tier_prices = ['theorist'=>5, 'architect'=>15, 'pioneer'=>30, 'benefactor'=>50, 'founding'=>100];
try {
    if ($sqlite) {
        $rows = $sqlite->query(
            "SELECT LOWER(patreon_tier) AS tier, COUNT(*) AS n FROM users
             WHERE is_patreon_supporter = 1 AND patreon_tier IS NOT NULL AND patreon_tier != ''
             GROUP BY LOWER(patreon_tier)"
        )->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $r) {
            $tier_counts[$r['tier']] = (int)$r['n'];
            if ($r['tier'] === 'founding') $founding_filled = (int)$r['n'];
            $mrr_dollars += (int)$r['n'] * ($tier_prices[$r['tier']] ?? 0);
        }
    }
} catch (Throwable $e) { pulse_log('patreon query failed: ' . $e->getMessage()); }
$pulse['patreon_tier_counts'] = $tier_counts;
$pulse['founding_slots_remaining'] = max(0, FOUNDING_CAP - $founding_filled);
$pulse['mrr_dollars_estimate'] = $mrr_dollars;
$pulse['mrr_dollars_target'] = 200;

// 4) Active QOTD streaks >= 7d (SQLite, table is qotd_answer_streaks plural)
$pulse['qotd_streaks_7plus'] = safe_int(function() use ($sqlite) {
    if (!$sqlite) return -1;
    $s = $sqlite->query("SELECT COUNT(*) FROM qotd_answer_streaks WHERE current_streak >= 7");
    return $s->fetchColumn();
});

// 5) Think Tank / room participation this week (SQLite). "In the room" uses the
//    system's canonical definition — marked attendance UNION voice_presence
//    activity — mirroring db/think_tanks.py tt_room_happened() and
//    cogs/activation.py. The manual /thinktank attendance ledger alone reads 0
//    forever (zero rows ever written; 2026-07-26 founder report: a week of
//    nightly Think Tanks showed as 0), while the bot already logs
//    voice_presence for everyone actually in voice. Keep the three
//    definitions in sync — guarded by tests/test_pulse_room_definition.py.
$pulse['think_tank_sessions_7d'] = safe_int(function() use ($sqlite) {
    if (!$sqlite) return -1;
    $s = $sqlite->query(
        "SELECT COUNT(*) FROM think_tank_sessions
         WHERE date(scheduled_date) >= date('now', '-7 days')"
    );
    return $s->fetchColumn();
});

$pulse['think_tank_attendees_7d'] = safe_int(function() use ($sqlite) {
    if (!$sqlite) return -1;
    $s = $sqlite->query(
        "SELECT COUNT(DISTINCT user_id) FROM (
            SELECT ta.user_id
            FROM think_tank_attendance ta
            JOIN think_tank_sessions ts ON ts.session_id = ta.session_id
            WHERE ta.attended = 1
              AND date(ts.scheduled_date) >= date('now', '-7 days')
            UNION
            SELECT user_id
            FROM activity_log
            WHERE activity_type = 'voice_presence'
              AND activity_date >= datetime('now', '-7 days')
         )"
    );
    return $s->fetchColumn();
});

// 5b) Room depth by day — distinct members in voice per UTC day, so the weekly
//     analysis sees the actual distribution instead of inferring per-session
//     averages from two aggregate numbers.
$pulse['room_by_day_7d'] = (function() use ($sqlite) {
    if (!$sqlite) return [];
    try {
        $rows = $sqlite->query(
            "SELECT date(activity_date) AS d, COUNT(DISTINCT user_id) AS users
             FROM activity_log
             WHERE activity_type = 'voice_presence'
               AND activity_date >= datetime('now', '-7 days')
             GROUP BY date(activity_date) ORDER BY d"
        )->fetchAll(PDO::FETCH_ASSOC);
        return array_map(
            fn($r) => ['date' => $r['d'], 'in_room' => (int)$r['users']], $rows
        );
    } catch (Throwable $e) {
        pulse_log('room_by_day query failed: ' . $e->getMessage());
        return [];
    }
})();

// ---------------------------------------------------------------------------
// Output
// ---------------------------------------------------------------------------
$format = (string)($_GET['format'] ?? 'json');

if ($format === 'markdown' || $format === 'md') {
    header('Content-Type: text/plain; charset=utf-8');
    echo render_markdown($pulse);
    exit;
}

header('Content-Type: application/json');
echo json_encode($pulse, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";

// ---------------------------------------------------------------------------
// Markdown renderer (Discord-ready)
// ---------------------------------------------------------------------------
function render_markdown(array $p): string {
    $week = date('M j', strtotime('-7 days')) . ' - ' . date('M j, Y');
    $auto = $p['reflections_auto_approved_7d'];
    $human = $p['reflections_human_approved_7d'];
    $pending = $p['reflections_pending'];
    $new_members = $p['new_members_7d'];
    $total_members = $p['lifetime_registered_members'];
    $streaks = $p['qotd_streaks_7plus'];
    $tt = $p['think_tank_attendees_7d'];
    $tt_sessions = $p['think_tank_sessions_7d'] ?? -1;
    $founding_left = $p['founding_slots_remaining'];
    $mrr = $p['mrr_dollars_estimate'];
    $target = $p['mrr_dollars_target'];
    $mrr_pct = $target > 0 ? min(100, (int)round($mrr / $target * 100)) : 0;
    $tier_counts = $p['patreon_tier_counts'] ?? [];

    $tier_line = empty($tier_counts) ? 'no patrons yet' : implode(' / ', array_map(
        fn($k, $v) => "$v $k", array_keys($tier_counts), array_values($tier_counts)
    ));

    $flags = [];
    if ($pending > 5) $flags[] = ":warning: **{$pending} reflections still pending** review";
    if ($new_members === 0) $flags[] = ":information_source: No new members joined this week";
    if ($auto > 0) $flags[] = ":robot: {$auto} reflection(s) auto-approved (no human reviewer claimed in 7d)";
    if ($mrr === 0) $flags[] = ":money_with_wings: $0 MRR - no Patreon supporters yet";
    if ($founding_left < 25 && $founding_left > 0) $flags[] = ":crown: {$founding_left}/25 Founding Patron slots left";
    $flags_block = empty($flags) ? '_All clear._' : implode("\n", array_map(fn($f) => "- $f", $flags));

    $md = "**OD9 Weekly Pulse — {$week}**\n\n";
    $md .= "**Members:** {$total_members} lifetime through the door ({$new_members} new this week)\n";
    $md .= "**Patrons:** {$tier_line}\n";
    $md .= "**MRR:** \${$mrr} / \${$target} ({$mrr_pct}% to annual-billing unlock)\n";
    $md .= "**Founding Patron slots remaining:** {$founding_left} / 25\n\n";
    $md .= "**Engagement:**\n";
    $fmt = fn($n) => $n < 0 ? 'n/a' : (string)$n;
    $md .= "- Reflections approved this week: " . $fmt($human) . " by humans, " . $fmt($auto) . " auto-approved, " . $fmt($pending) . " still pending\n";
    $md .= "- QOTD streaks ≥ 7 days: " . $fmt($streaks) . "\n";
    $md .= "- Think Tanks held this week: " . $fmt($tt_sessions) . "; distinct members in the room (voice or marked attended): " . $fmt($tt) . "\n\n";
    $md .= "**Flags:**\n{$flags_block}\n\n";
    $md .= "_Generated " . $p['generated_at'] . "_\n";
    return $md;
}
