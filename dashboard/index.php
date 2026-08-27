<?php
$page_title = 'Progression Dashboard - OD9 ASCEND Protocol';
$page_description = 'Track your ASCEND Protocol progression. View your tier, dimension scores, achievements, and activity feed.';
$page_slug = 'index.php';
$page_robots = 'noindex, nofollow';
?>
<?php
/**
 * OD9 Progression Dashboard
 *
 * Visual progression display for OD9 Discord members.
 * Reads progression data directly from bot's SQLite database.
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/../includes/env.php';

$current_page = 'dashboard';

// Secure session + persistent remember-me login (centralized in includes/auth.php)
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/ztrans.php';
od9_dashboard_boot();

// Check if logged in via Discord OAuth
$isLoggedIn = !empty($_SESSION['discord_id']);
$user = null;
$progression = null;
$achievements = [];
$credits = null;
$streak = null;
$tier = null;
$notifications = [];
$unreadNotifCount = 0;

if ($isLoggedIn) {
    $discordId = $_SESSION['discord_id'];
    // We intentionally do NOT filter by guild_id. The live bot SQLite stores
    // members under the real OD9 guild (1146833684952006769), which differs from
    // the OD9_GUILD_ID constant (1309609816934559785) — filtering by the constant
    // matched zero users and blanked the dashboard. me.php keys on user_id alone
    // too; the bot DB is single-guild in practice.

    // Each bot query is guarded independently (see api/v1/pulse.php's safe-read
    // pattern): a single schema drift — a renamed column, a missing table —
    // should blank only its own widget, never abort the whole dashboard load.
    $botFetch = function (?PDO $db, string $sql, array $params): array {
        if (!$db) return [];
        try {
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Dashboard query failed: " . $e->getMessage());
            return [];
        }
    };

    $db = null;
    try {
        $botDbPath = defined('OD9_BOT_DB_PATH') ? OD9_BOT_DB_PATH : 'C:/Users/Rage/IdeaProjects/OD9-Discord-Bot/od9.db';
        $db = new PDO("sqlite:$botDbPath", null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } catch (PDOException $e) {
        error_log("Dashboard DB connection error: " . $e->getMessage());
    }

    $params = [$discordId];

    // Foundational row: tier + credits + join date.
    $user = $botFetch($db,
        "SELECT user_id, username, current_tier, total_credits, join_date
         FROM users WHERE user_id = ? LIMIT 1", $params)[0] ?? null;

    if ($user) {
        $tierName = ucfirst($user['current_tier'] ?? 'observer');
        $creditBalance = (int)($user['total_credits'] ?? 0);

        // Streak (real bot columns: longest_streak, last_activity_date).
        $streakData = $botFetch($db,
            "SELECT current_streak, longest_streak, last_activity_date
             FROM user_streaks WHERE user_id = ? LIMIT 1", $params)[0] ?? [];
        $streak = [
            'current' => (int)($streakData['current_streak'] ?? 0),
            'best' => (int)($streakData['longest_streak'] ?? 0),
        ];

        // Total earned achievements (may exceed the 10 shown in the grid).
        $achievementCount = (int)($botFetch($db,
            "SELECT COUNT(*) AS c FROM user_achievements
             WHERE user_id = ? AND earned_at IS NOT NULL", $params)[0]['c'] ?? 0);

        // Most-recent achievements for the grid.
        $achievements = $botFetch($db,
            "SELECT ua.achievement_id, ua.earned_at, ad.name, ad.icon, ad.rarity, ad.credit_reward as points,
                    ad.badge_image, ad.description, ua.revealed
             FROM user_achievements ua
             LEFT JOIN achievement_definitions ad
               ON ua.achievement_id = ad.achievement_id AND ua.guild_id = ad.guild_id
             WHERE ua.user_id = ? AND ua.earned_at IS NOT NULL
             ORDER BY ua.earned_at DESC LIMIT 10", $params);

        // Dimension scores for the radar (bot dims per the bot's config.py).
        $dimensions = [
            'knowledge' => 0,
            'resource' => 0,
            'community' => 0,
            'consciousness' => 0,
            'system' => 0,
        ];
        // The bot's user_dimensions is WIDE (one row per user, *_score columns)
        // — the old long-format (dimension, score) query matched a schema that
        // never existed, so the guarded failure rendered the radar as zeros
        // for everyone (fixed 2026-06-12, audit P0-2).
        $dimRow = $botFetch($db,
            "SELECT knowledge_score, resource_score, community_score,
                    consciousness_score, system_score
             FROM user_dimensions WHERE user_id = ?", $params)[0] ?? null;
        if ($dimRow) {
            foreach (array_keys($dimensions) as $dim) {
                $dimensions[$dim] = (float)($dimRow[$dim . '_score'] ?? 0);
            }
        }

        // Recent activity feed.
        $recentActivity = $botFetch($db,
            "SELECT activity_type, activity_description, credits_earned, streak_bonus, activity_date
             FROM activity_log WHERE user_id = ?
             ORDER BY activity_date DESC LIMIT 20", $params);

        // Notification inbox — UNREAD only. Dismissing one (Watch or ×) marks it
        // read and it drops off the list, so the inbox stays a clean action queue.
        $notifications = $botFetch($db,
            "SELECT notification_id, type, video_key, guide_name, title, body, read_at, created_at
             FROM member_notifications WHERE user_id = ? AND read_at IS NULL
             ORDER BY created_at DESC, notification_id DESC LIMIT 20", $params);
        $unreadNotifCount = count($notifications);

        // Build progression object consumed by the view below.
        $progression = [
            'current_tier' => $tierName,
            'total_credits' => $creditBalance,
            'current_streak' => $streak['current'],
            'best_streak' => $streak['best'],
            'achievements_count' => $achievementCount,
            'member_since' => $user['join_date'],
            'dim_knowledge' => $dimensions['knowledge'],
            'dim_resource' => $dimensions['resource'],
            'dim_community' => $dimensions['community'],
            'dim_consciousness' => $dimensions['consciousness'],
            'dim_system' => $dimensions['system'],
        ];
    }
}

// HQ environment scales with tier: grimy sub-level bunker -> gleaming K1 command
// center (the Type-I civilization vision rendered as your HQ leveling up). The
// brighter K1 tiers get a darker overlay so the header + cards stay readable; each
// tier also carries its accent color (tint) + a "clearance" label.
$hqTier = strtolower($progression['current_tier'] ?? 'observer');
/* tints = the iced tier ladder as RGB (includes/tiers.php, 2026-08-13 flip) */
$HQ_ENV = [
    'observer'   => ['bg' => 'hq-observer.jpg',   'o' => ['.64', '.84', '.93'], 'tint' => '138,148,153', 'label' => 'Sub-Level Bunker'],
    'theorist'   => ['bg' => 'hq-theorist.jpg',   'o' => ['.64', '.84', '.93'], 'tint' => '46,139,255',  'label' => 'Bunker &middot; Powered Up'],
    'architect'  => ['bg' => 'hq-architect.jpg',  'o' => ['.78', '.86', '.93'], 'tint' => '155,92,255',  'label' => 'Command Center &middot; Online'],
    'pioneer'    => ['bg' => 'hq-pioneer.jpg',    'o' => ['.78', '.86', '.93'], 'tint' => '229,181,58',  'label' => 'Command Nexus'],
    'benefactor' => ['bg' => 'hq-benefactor.jpg', 'o' => ['.80', '.87', '.94'], 'tint' => '229,72,58',   'label' => 'Type-I Command'],
];
$hq = $HQ_ENV[$hqTier] ?? $HQ_ENV['observer'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php $nav_base = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/dashboard/index.php')), '/'); include __DIR__ . '/../includes/head.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
body {
    /* Tier-scaled HQ bg: grimy bunker -> gleaming K1 command center (chosen per-tier
       in PHP above). A faint tier-color wash sits over a dark gradient that keeps the
       header + cards readable; brighter K1 tiers use a darker overlay. */
    background:
        linear-gradient(180deg, rgba(<?= $hq['tint'] ?>,.12), rgba(8,9,11,<?= $hq['o'][0] ?>) 16%, rgba(8,9,11,<?= $hq['o'][1] ?>) 52%, rgba(8,9,11,<?= $hq['o'][2] ?>)),
        url('/images/dashboard/<?= $hq['bg'] ?>') center top / cover no-repeat fixed,
        var(--carbon);
    color: var(--chrome);
    font-family: 'Exo 2', sans-serif;
    padding-top: var(--nav-height);
    min-height: 100vh;
}
/* HQ clearance eyebrow — ties the environment to your tier (accent = tier color) */
.hq-clearance { display: inline-flex; align-items: center; gap: 8px; font-family: 'Exo 2', sans-serif; font-size: 11px; letter-spacing: .22em; text-transform: uppercase; color: rgb(<?= $hq['tint'] ?>); margin-bottom: 10px; text-shadow: 0 0 10px rgba(<?= $hq['tint'] ?>,.45); }
.hq-clearance .hq-dot { width: 8px; height: 8px; border-radius: 50%; background: rgb(<?= $hq['tint'] ?>); box-shadow: 0 0 9px rgba(<?= $hq['tint'] ?>,.9); }

/* Dashboard layout */
.dashboard-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
}

.dashboard-header {
    text-align: center;
    margin-bottom: 2rem;
}

.dashboard-header h1 {
    font-family: 'Orbitron', sans-serif;
    font-size: 2.5rem;
    color: #fff;
    text-shadow: var(--glow);
    margin-bottom: 0.5rem;
}

.dashboard-header .subtitle {
    color: var(--chrome);
    font-size: 1.1rem;
}

/* Login prompt */
.login-prompt {
    background: var(--carbon-dark);
    border: 2px solid var(--primary-blue);
    border-radius: 12px;
    padding: 3rem;
    text-align: center;
    max-width: 500px;
    margin: 4rem auto;
}

.login-prompt h2 {
    font-family: 'Orbitron', sans-serif;
    color: #fff;
    margin-bottom: 1rem;
}

.login-prompt p {
    color: var(--chrome);
    margin-bottom: 2rem;
    line-height: 1.6;
}

.discord-login-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.75rem;
    background: #5865F2;
    color: #fff;
    padding: 1rem 2rem;
    border-radius: 8px;
    text-decoration: none;
    font-family: 'Rajdhani', sans-serif;
    font-weight: 700;
    font-size: 1.1rem;
    text-transform: uppercase;
    transition: all 0.3s;
}

.discord-login-btn:hover {
    background: #4752C4;
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(88, 101, 242, 0.4);
}

/* Dashboard grid */
.dashboard-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
}

@media (max-width: 900px) {
    .dashboard-grid { grid-template-columns: 1fr; }
}

/* Cards */
.card {
    /* per-tier accent: cards pick up the current tier's color (tint set in PHP above) */
    background: linear-gradient(rgba(<?= $hq['tint'] ?>,.05), rgba(<?= $hq['tint'] ?>,.015)), var(--carbon-dark);
    border: 1px solid rgba(<?= $hq['tint'] ?>,.30);
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 8px 26px rgba(0,0,0,.34), 0 0 22px rgba(<?= $hq['tint'] ?>,.07);
}

.card h3 {
    font-family: 'Orbitron', sans-serif;
    color: rgb(<?= $hq['tint'] ?>);
    font-size: 1.2rem;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid rgba(<?= $hq['tint'] ?>,.30);
}

/* Notification center */
.notif-card { margin-bottom: 2rem; border-color: var(--primary-blue); }
.notif-count { display:inline-block; background:#FF3B5C; color:#fff; font-family:'Rajdhani',sans-serif; font-weight:700; font-size:0.8rem; min-width:1.4rem; text-align:center; padding:0.05rem 0.45rem; border-radius:999px; vertical-align:middle; margin-left:0.4rem; }
.notif-list { display:flex; flex-direction:column; gap:0.6rem; }
.notif-item { display:flex; align-items:center; justify-content:space-between; gap:1rem; padding:0.85rem 1rem; background:#0d0d0d; border:1px solid #2a2a2a; border-radius:8px; }
.notif-item.unread { border-left:3px solid var(--primary-blue); background:#0f1418; }
.notif-text { display:flex; flex-direction:column; gap:0.15rem; min-width:0; }
.notif-guide { font-family:'Orbitron',sans-serif; font-size:0.65rem; letter-spacing:1.5px; text-transform:uppercase; color:var(--primary-blue); }
.notif-ttl { color:#fff; font-weight:600; }
.notif-body { color:#999; font-size:0.88rem; line-height:1.45; }
.notif-watch { flex-shrink:0; background:var(--primary-blue); color:#03121a; border:none; font-family:'Rajdhani',sans-serif; font-weight:700; font-size:0.85rem; letter-spacing:1px; text-transform:uppercase; padding:0.5rem 1.1rem; border-radius:6px; cursor:pointer; transition:opacity 0.2s, box-shadow 0.2s; }
.notif-watch:hover { opacity:0.9; box-shadow:0 0 16px rgba(0,191,255,0.4); }
.notif-actions { display:flex; align-items:center; gap:0.5rem; flex-shrink:0; }
a.notif-watch { text-decoration:none; display:inline-flex; align-items:center; }
.notif-dismiss { background:none; border:1px solid #2a2a2a; color:#888; width:1.9rem; height:1.9rem; border-radius:6px; font-size:1.15rem; line-height:1; cursor:pointer; transition:color 0.2s, border-color 0.2s; }
.notif-dismiss:hover { color:#fff; border-color:#FF3B5C; }

/* Guide-video modal */
.guide-modal { position:fixed; inset:0; z-index:1000; display:flex; align-items:center; justify-content:center; padding:1.5rem; }
.guide-modal[hidden] { display:none; }
.guide-modal-overlay { position:absolute; inset:0; background:rgba(0,0,0,0.82); }
.guide-modal-panel { position:relative; width:100%; max-width:900px; background:#070707; border:1px solid var(--primary-blue); border-radius:10px; overflow:hidden; box-shadow:0 0 50px rgba(0,191,255,0.18); }
.guide-modal-head { display:flex; align-items:center; justify-content:space-between; padding:0.85rem 1.2rem; border-bottom:1px solid #1a1a1a; }
.guide-modal-title { font-family:'Orbitron',sans-serif; color:#fff; font-size:1rem; letter-spacing:1px; }
.guide-modal-x { background:none; border:none; color:#888; font-size:1.6rem; line-height:1; cursor:pointer; }
.guide-modal-x:hover { color:#fff; }
.guide-modal-frame { width:100%; aspect-ratio:16/9; border:0; display:block; background:#000; }

/* Tier badge */
.tier-badge {
    display: inline-block;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-family: 'Orbitron', sans-serif;
    font-weight: 700;
    font-size: 1rem;
    text-transform: uppercase;
}

/* Tier badges — the iced design-system ladder (2026-08-13 flip; these were a
   drifted third variant that matched neither Discord nor the design system) */
.tier-observer { background: linear-gradient(135deg, #8A9499, #6A737A); color: #fff; }
.tier-theorist { background: linear-gradient(135deg, #2E8BFF, #1F64C0); color: #fff; }
.tier-architect { background: linear-gradient(135deg, #9B5CFF, #7A3FD0); color: #fff; }
.tier-pioneer { background: linear-gradient(135deg, #E5B53A, #B8962B); color: #0A0A0A; }
.tier-benefactor { background: linear-gradient(135deg, #E5483A, #B03028); color: #fff; }

/* Progress bar */
.progress-container {
    margin: 1rem 0;
}

.progress-bar {
    height: 20px;
    background: #222;
    border-radius: 10px;
    overflow: hidden;
    position: relative;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--primary-blue), var(--neon-blue));
    border-radius: 10px;
    transition: width 0.5s ease;
}

.progress-text {
    display: flex;
    justify-content: space-between;
    margin-top: 0.5rem;
    font-size: 0.9rem;
    color: var(--chrome);
}

/* "Continue your journey" CTA — the dashboard's primary call to action.
   Routes the member to their current tier's curriculum on the library. */
.journey-cta {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.55rem;
    padding: 0.95rem 1.75rem;
    background: linear-gradient(135deg, var(--primary-blue), #00A0FF);
    color: #06121a;
    font-family: 'Rajdhani', sans-serif;
    font-weight: 700;
    font-size: 1.05rem;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    text-decoration: none;
    border-radius: 8px;
    transition: transform 0.15s ease, box-shadow 0.2s ease;
    box-shadow: 0 0 18px rgba(0, 191, 255, 0.35);
}
.journey-cta:hover { transform: translateY(-2px); box-shadow: 0 0 26px rgba(0, 191, 255, 0.55); }
.journey-cta-wrap { text-align: center; margin-top: 1.25rem; }
.journey-cta-sub { margin-top: 0.5rem; font-size: 0.78rem; color: #8aa3b0; letter-spacing: 1px; font-family: 'Rajdhani', sans-serif; text-transform: uppercase; }
.journey-cta-top { padding: 0.5rem 1.1rem; font-size: 0.9rem; box-shadow: 0 0 12px rgba(0, 191, 255, 0.3); }
.journey-cta-row { display: grid; grid-auto-flow: column; grid-auto-columns: 1fr; gap: 0.75rem; max-width: 560px; margin: 0 auto; }
.journey-cta-row > .journey-cta { width: 100%; }
/* The Map / Board CTA — cyan "world" accent (brand: cyan = the Map; gold = Bunker; violet = Gate) */
.journey-cta-map { background: linear-gradient(135deg, var(--cyan, #00FFF7), var(--cyan-web, #00BFFF)); color: #06121a; box-shadow: 0 0 18px rgba(0, 255, 247, 0.40); }
.journey-cta-map:hover { box-shadow: 0 0 28px rgba(0, 255, 247, 0.65); }
/* Throb: pulse the glow on the primary "Back to the Map" CTA to pull the eye back into the world */
@keyframes ctaPulse { 0%, 100% { box-shadow: 0 0 16px rgba(0, 255, 247, 0.35); } 50% { box-shadow: 0 0 32px rgba(0, 255, 247, 0.80); } }
.journey-cta-pulse { animation: ctaPulse 1.9s ease-in-out infinite; }

/* Radar chart container */
.radar-container {
    width: 100%;
    max-width: 350px;
    margin: 0 auto;
}

/* Achievement grid */
.achievement-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
    gap: 1rem;
}

.achievement-item {
    text-align: center;
    padding: 0.75rem;
    background: #222;
    border-radius: 8px;
    transition: all 0.3s;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: flex-start;
    position: relative;
}
.achievement-item[data-desc]:hover::after {
    content: attr(data-desc);
    position: absolute;
    bottom: calc(100% + 6px);
    left: 50%;
    transform: translateX(-50%);
    width: max-content;
    max-width: 180px;
    white-space: normal;
    text-align: center;
    background: #0a0a0a;
    color: #eee;
    border: 1px solid #00FFF7;
    border-radius: 8px;
    padding: 0.5rem 0.7rem;
    font-size: 0.7rem;
    line-height: 1.3;
    z-index: 50;
    pointer-events: none;
    box-shadow: 0 4px 16px rgba(0,0,0,0.6);
}

.achievement-item:hover {
    transform: translateY(-2px);
    background: #333;
}

.achievement-icon {
    font-size: 2rem;
    margin-bottom: 0.5rem;
}

.achievement-badge {
    width: 72px;
    height: 72px;
    object-fit: contain;
    margin: 0 auto 0.4rem;
    display: block;
}

.achievement-item.sealed { background: #1a0f2e; border: 1px solid #7A00FF; box-shadow: 0 0 14px #7A00FF44; }
.achievement-item.sealed .achievement-name { color: #b388ff; }

.achievement-name {
    font-size: 0.75rem;
    color: var(--chrome);
}

/* Activity feed */
.activity-feed {
    max-height: 300px;
    overflow-y: auto;
}

.activity-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.75rem;
    border-bottom: 1px solid #333;
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-icon {
    width: 40px;
    height: 40px;
    background: #222;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--primary-blue);
}

.activity-content {
    flex: 1;
}

.activity-desc {
    color: #fff;
    font-size: 0.9rem;
}

.activity-time {
    color: #888;
    font-size: 0.75rem;
    margin-top: 0.25rem;
}

.activity-credits {
    color: var(--neon-blue);
    font-weight: 700;
}

/* Footer */
.dashboard-footer {
    text-align: center;
    margin-top: 3rem;
    padding: 2rem;
    color: #888;
    font-size: 0.9rem;
}

/* Enhanced Mobile Responsive */
@media (max-width: 600px) {
    .dashboard-container { padding: 1rem 0.5rem; }
    .card { padding: 1rem; }
    .card h3 { font-size: 1rem; }
    .radar-container { max-width: 280px; }
    .achievement-grid { grid-template-columns: repeat(3, 1fr); gap: 0.5rem; }
    .achievement-item { padding: 0.5rem; }
    .achievement-icon { font-size: 1.5rem; }
    .achievement-name { font-size: 0.65rem; }
    .activity-item { padding: 0.5rem; gap: 0.5rem; }
    .activity-icon { width: 32px; height: 32px; }
    .tier-badge { font-size: 0.85rem; padding: 0.4rem 0.8rem; }
}
@media (max-width: 400px) {
    .achievement-grid { grid-template-columns: repeat(2, 1fr); }
    .card h3 { font-size: 0.9rem; }
}
</style>
<?php if ($isLoggedIn) od9_ztrans_head(); ?>
<?php if ($isLoggedIn): ?>
<link rel="stylesheet" href="/js/lib/driver.css?v=<?= @filemtime(__DIR__ . '/../js/lib/driver.css') ?: '1' ?>">
<link rel="stylesheet" href="/css/tour.css?v=<?= @filemtime(__DIR__ . '/../css/tour.css') ?: '1' ?>">
<?php endif; ?>
</head>
<body<?= $isLoggedIn ? ' data-tour="dashboard" data-tour-csrf="' . htmlspecialchars(od9_csrf_token(), ENT_QUOTES) . '"' : '' ?>>
<?php if ($isLoggedIn) od9_ztrans_body(); ?>

<?php
// Universal site nav. We're one level deep in /dashboard/, so set the nav base
// to the site root (up one dir from SCRIPT_NAME) before including it.
$nav_base = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/dashboard/index.php')), '/\\');
include __DIR__ . '/../includes/nav.php';

// "Continue your journey" CTA target — computed once, reused in the header and
// the tier card. Routes the member to their current tier's section on the
// library (anchors added there), where the curriculum + capstone docs live.
$journeyTier = strtolower($progression['current_tier'] ?? 'observer');
$_TSEQ = ['observer', 'theorist', 'architect', 'pioneer', 'benefactor'];
$_ti = array_search($journeyTier, $_TSEQ, true);
$journeyNext = ($_ti !== false && $_ti < count($_TSEQ) - 1) ? $_TSEQ[$_ti + 1] : null;
$journeyHref = 'https://offda9.com/library.php#' . rawurlencode($journeyTier);
$journeyLabel = $journeyNext ? 'Continue Your Journey' : 'Explore the Library';
?>

<div class="dashboard-container">
    <div class="dashboard-header">
        <?php if ($isLoggedIn): ?><div class="hq-clearance"><span class="hq-dot"></span> OD9 HQ // <?= $hq['label'] ?> &middot; <?= htmlspecialchars(ucfirst($hqTier)) ?> Clearance</div><?php endif; ?>
        <?php if ($isLoggedIn && !empty($_SESSION['discord_avatar'])): ?>
        <div class="dash-identity">
            <img class="dash-avatar" src="<?= htmlspecialchars($_SESSION['discord_avatar']) ?>" alt="Your Discord avatar" referrerpolicy="no-referrer" loading="lazy">
            <div class="dash-hello">
                <span class="dash-hello-label">Welcome back,</span>
                <span class="dash-hello-name"><?= htmlspecialchars($_SESSION['discord_global_name'] ?: ($_SESSION['discord_username'] ?? 'Awakened')) ?></span>
            </div>
        </div>
        <?php endif; ?>
        <?php
        // The belonging strip: the next room, above the tier telemetry
        // (BELONGING_FIRST_RESEQUENCE_SPEC web change #4).
        require_once __DIR__ . '/../includes/next_event.php';
        $nextRoomLabel = od9_next_event_label();
        ?>
        <?php if ($isLoggedIn): ?>
        <p style="margin-top: 0.5rem; font-family: 'Rajdhani', sans-serif; letter-spacing: 1px;">
            <a href="https://offda9.com/events.php" style="color: #00FFF7; text-decoration: none; text-transform: uppercase; font-weight: 700;">
                <i class="fas fa-calendar-check"></i> Next up: <?= htmlspecialchars($nextRoomLabel) ?> &middot; pull up &rarr;
            </a>
        </p>
        <?php endif; ?>
        <h1><i class="fas fa-chart-line"></i> Progression Dashboard</h1>
        <p class="subtitle">Track your ASCEND Protocol journey</p>
        <?php if ($isLoggedIn): ?>
        <p style="margin-top: 0.75rem;">
            <a class="od9-tour-beacon" href="#" id="od9-tour-replay" title="Tour OD9 HQ"><span class="tc-new">&#9654; Tour This Zone</span><span class="tc-seen">&#9432; Tour</span></a>
            <a href="/dashboard/settings.php" class="ztrans-link" data-ztrans="toolbox" style="color: var(--primary-blue); text-decoration: none; font-family: 'Rajdhani', sans-serif; text-transform: uppercase; letter-spacing: 1px; margin-left: 1.5rem;">
                <i class="fas fa-cog"></i> Settings
            </a>
            <a href="/dashboard/bunker.php" class="ztrans-link" data-ztrans="hatch" style="color: #D4AF37; text-decoration: none; font-family: 'Rajdhani', sans-serif; text-transform: uppercase; letter-spacing: 1px; margin-left: 1.5rem;">
                <i class="fas fa-box-open"></i> The Bunker
            </a>
            <a href="/dashboard/world.php" style="color: var(--zone-cyan, #00FFF7); text-decoration: none; font-family: 'Rajdhani', sans-serif; text-transform: uppercase; letter-spacing: 1px; margin-left: 1.5rem;">
                <i class="fas fa-map"></i> World Map
            </a>
            <a href="/dashboard/faq.php" style="color: #b388ff; text-decoration: none; font-family: 'Rajdhani', sans-serif; text-transform: uppercase; letter-spacing: 1px; margin-left: 1.5rem;">
                <i class="fas fa-circle-question"></i> How It Works
            </a>
            <a href="https://discord.gg/spgmrXVMWq" target="_blank" rel="noopener" style="color: #5865F2; text-decoration: none; font-family: 'Rajdhani', sans-serif; text-transform: uppercase; letter-spacing: 1px; margin-left: 1.5rem;">
                <i class="fab fa-discord"></i> Open Discord
            </a>
            <a href="/dashboard/auth/logout.php" style="color: #949494; text-decoration: none; font-family: 'Rajdhani', sans-serif; text-transform: uppercase; letter-spacing: 1px; margin-left: 1.5rem;">
                <i class="fas fa-door-open"></i> Sign Out
            </a>
        </p>
        <p style="margin-top: 0.85rem;">
            <span class="journey-cta-row">
                <a href="<?= htmlspecialchars($journeyHref) ?>" class="journey-cta">
                    <i class="fas fa-bolt"></i> <?= htmlspecialchars($journeyLabel) ?>
                </a>
                <a href="/dashboard/board.php" class="journey-cta journey-cta-map ztrans-link" data-ztrans="gate">
                    <i class="fas fa-map"></i> The Map
                </a>
            </span>
        </p>
        <?php endif; ?>
    </div>

    <?php if (!$isLoggedIn): ?>
    <!-- Not logged in - show login prompt -->
    <div class="login-prompt">
        <h2>Connect Your Discord</h2>
        <p>Link your Discord account to see your progression stats, achievements, and activity feed.</p>
        <a href="auth/discord.php" class="discord-login-btn">
            <i class="fab fa-discord"></i> Login with Discord
        </a>
        <p style="margin-top: 1.5rem; font-size: 0.9rem; color: #888;">
            Don't have an account? <a href="/join.php" style="color: var(--primary-blue);">Join OD9 on Discord</a>
        </p>
    </div>

    <?php else: ?>
    <!-- Logged in - show dashboard -->
    <?php if (!empty($notifications)): ?>
    <div class="card notif-card">
        <h3><i class="fas fa-bell"></i> Notifications<?php if ($unreadNotifCount > 0): ?> <span class="notif-count"><?= (int)$unreadNotifCount ?></span><?php endif; ?></h3>
        <div class="notif-list">
            <?php foreach ($notifications as $n):
                $vkey = trim((string)($n['video_key'] ?? ''));
                $nid = (int)($n['notification_id'] ?? 0);
                $isSotw = (($n['type'] ?? '') === 'sotw') || strpos($vkey, 'sotw:') === 0;
            ?>
            <div class="notif-item unread" data-id="<?= $nid ?>">
                <div class="notif-text">
                    <?php if (!empty($n['guide_name'])): ?><span class="notif-guide"><?= htmlspecialchars($n['guide_name']) ?></span><?php endif; ?>
                    <span class="notif-ttl"><?= htmlspecialchars($n['title']) ?></span>
                    <?php if (!empty($n['body'])): ?><div class="notif-body"><?= htmlspecialchars($n['body']) ?></div><?php endif; ?>
                </div>
                <div class="notif-actions">
                    <?php if ($isSotw): ?>
                    <a class="notif-watch ztrans-link" data-ztrans="hatch" href="/dashboard/bunker.php" onclick="od9MarkNotifRead(<?= $nid ?>)">Open Bunker &rsaquo;</a>
                    <?php elseif ($vkey !== ''): ?>
                    <button type="button" class="notif-watch" onclick="od9PlayGuide(<?= htmlspecialchars(json_encode($vkey), ENT_QUOTES) ?>,<?= htmlspecialchars(json_encode($n['title']), ENT_QUOTES) ?>,<?= (int)$nid ?>)">&#9654;&nbsp;Watch</button>
                    <?php endif; ?>
                    <button type="button" class="notif-dismiss" title="Dismiss" aria-label="Dismiss" onclick="od9DismissNotif(<?= $nid ?>)">&times;</button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="dashboard-grid">
        <!-- Tier & Progress Card -->
        <div class="card" id="card-tier">
            <h3><i class="fas fa-layer-group"></i> Current Tier</h3>
            <div style="text-align: center; margin: 1.5rem 0;">
                <?php
                $tier = strtolower($progression['current_tier'] ?? 'observer');
                $tierDisplay = ucfirst($tier);
                $credits = $progression['total_credits'] ?? 0;
                $progress = $progression['tier_progress_pct'] ?? 0;
                ?>
                <span class="tier-badge tier-<?= $tier ?>"><?= htmlspecialchars($tierDisplay) ?></span>
                <div style="margin-top: 1rem; font-size: 2rem; color: #fff; font-family: 'Orbitron', sans-serif;">
                    <?= number_format($credits) ?> <span style="font-size: 1rem; color: var(--chrome);">credits</span>
                </div>
            </div>
            <div class="progress-container">
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?= min(100, $progress) ?>%;"></div>
                </div>
                <div class="progress-text">
                    <span>Progress to next tier</span>
                    <span><?= number_format($progress, 1) ?>%</span>
                </div>
            </div>
            <div class="journey-cta-wrap">
                <a href="/dashboard/board.php" class="journey-cta journey-cta-map journey-cta-pulse ztrans-link" data-ztrans="gate">
                    <i class="fas fa-map"></i> Back to the Map
                </a>
                <a href="/dashboard/bunker.php" class="journey-cta ztrans-link" data-ztrans="hatch" style="margin-top:0.6rem; background:linear-gradient(135deg,#D4AF37,#b8860b); color:#1a1206; box-shadow:0 0 18px rgba(212,175,55,.35);">
                    <i class="fas fa-box-open"></i> Enter The Bunker
                </a>
                <?php if ($journeyNext): ?>
                <div class="journey-cta-sub">Next tier: <?= htmlspecialchars(ucfirst($journeyNext)) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Dimensions Radar Card -->
        <div class="card" id="card-dimensions">
            <h3><i class="fas fa-star"></i> Dimension Scores</h3>
            <div class="radar-container">
                <canvas id="dimensionRadar"></canvas>
            </div>
            <p style="margin-top: 0.75rem; font-size: 0.78rem; color: #8a93a0; line-height: 1.5;">
                <strong style="color: #aab3bf;">Resource</strong> unlocks as the protocol scales (shared-resource pools); <strong style="color: #aab3bf;">System</strong> builds from Theorist+. A low score in those is by design, not a gap — build Knowledge, Consciousness, and Community first.
            </p>
        </div>

        <!-- Achievements Card -->
        <div class="card" id="card-achievements">
            <h3><i class="fas fa-trophy"></i> Recent Achievements</h3>
            <?php if (empty($achievements)): ?>
                <p style="color: #888; text-align: center; padding: 2rem;">No achievements yet. Keep contributing!</p>
            <?php else: ?>
                <div class="achievement-grid">
                    <?php foreach ($achievements as $ach):
                        $sealed = empty($ach['revealed']);            // earned but unrevealed — the Firm ceremony
                        $bi = (string)($ach['badge_image'] ?? '');
                    ?>
                    <div class="achievement-item<?= $sealed ? ' sealed' : '' ?>" data-desc="<?= htmlspecialchars($sealed ? 'Sealed — run /firm reveal in Discord to claim it.' : ($ach['description'] ?? ($ach['name'] ?? '')), ENT_QUOTES) ?>">
                        <?php if ($sealed): ?>
                            <div class="achievement-icon badge-sealed">&#x1F381;</div>
                        <?php elseif ($bi !== ''): ?>
                            <img class="achievement-badge" src="/assets/badges/<?= htmlspecialchars(rawurlencode($bi), ENT_QUOTES) ?>" alt="<?= htmlspecialchars($ach['name'] ?? '', ENT_QUOTES) ?>" loading="lazy" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                            <div class="achievement-icon" style="display:none"><?= htmlspecialchars($ach['icon'] ?? '🏆') ?></div>
                        <?php else: ?>
                            <div class="achievement-icon"><?= htmlspecialchars($ach['icon'] ?? '🏆') ?></div>
                        <?php endif; ?>
                        <div class="achievement-name"><?= htmlspecialchars($sealed ? 'Sealed' : ($ach['name'] ?? $ach['achievement_id'])) ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Activity Feed Card -->
        <div class="card" id="card-activity">
            <h3><i class="fas fa-stream"></i> Recent Activity</h3>
            <?php if (empty($recentActivity)): ?>
                <p style="color: #888; text-align: center; padding: 2rem;">No activity yet. Start contributing on Discord!</p>
            <?php else: ?>
                <div class="activity-feed">
                    <?php foreach ($recentActivity as $activity): ?>
                    <div class="activity-item">
                        <div class="activity-icon">
                            <i class="fas fa-bolt"></i>
                        </div>
                        <div class="activity-content">
                            <div class="activity-desc"><?= htmlspecialchars($activity['activity_description'] ?? $activity['activity_type']) ?></div>
                            <div class="activity-time"><?= htmlspecialchars(od9_local_time($activity['activity_date'] ?? null)) ?></div>
                        </div>
                        <?php if (($activity['credits_earned'] ?? 0) > 0): ?>
                        <div class="activity-credits">+<?= $activity['credits_earned'] ?><?= ($activity['streak_bonus'] ?? 0) > 0 ? ' (+' . $activity['streak_bonus'] . ' streak)' : '' ?></div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
    // Dimension Radar Chart
    const ctx = document.getElementById('dimensionRadar').getContext('2d');
    new Chart(ctx, {
        type: 'radar',
        data: {
            labels: ['Knowledge', 'Resource', 'Community', 'Consciousness', 'System'],
            datasets: [{
                label: 'Dimension Scores',
                data: [
                    <?= $progression['dim_knowledge'] ?? 0 ?>,
                    <?= $progression['dim_resource'] ?? 0 ?>,
                    <?= $progression['dim_community'] ?? 0 ?>,
                    <?= $progression['dim_consciousness'] ?? 0 ?>,
                    <?= $progression['dim_system'] ?? 0 ?>
                ],
                fill: true,
                backgroundColor: 'rgba(0, 191, 255, 0.2)',
                borderColor: 'rgba(0, 191, 255, 1)',
                pointBackgroundColor: 'rgba(0, 191, 255, 1)',
                pointBorderColor: '#fff',
                pointHoverBackgroundColor: '#fff',
                pointHoverBorderColor: 'rgba(0, 191, 255, 1)'
            }]
        },
        options: {
            elements: { line: { borderWidth: 3 } },
            scales: {
                r: {
                    angleLines: { color: '#333' },
                    grid: { color: '#333' },
                    pointLabels: { color: '#C0C0C0', font: { size: 12 } },
                    ticks: { display: false },
                    suggestedMin: 0,
                    suggestedMax: 100
                }
            },
            plugins: {
                legend: { display: false }
            }
        }
    });
    </script>
    <?php endif; ?>

    <div class="dashboard-footer">
        <p>Part of the <a href="/tiers.php" style="color: var(--primary-blue);">ASCEND Protocol</a> | <a href="https://discord.gg/spgmrXVMWq" style="color: var(--primary-blue);">Join OD9 Discord</a></p>
    </div>
</div>

<!-- Guide-video player (opened from the Notifications section) -->
<div class="guide-modal" id="guideModal" hidden>
  <div class="guide-modal-overlay" onclick="od9CloseGuide()"></div>
  <div class="guide-modal-panel">
    <div class="guide-modal-head">
      <span class="guide-modal-title" id="guideModalTitle">Guide</span>
      <button class="guide-modal-x" type="button" aria-label="Close" onclick="od9CloseGuide()">&times;</button>
    </div>
    <iframe class="guide-modal-frame" id="guideModalFrame" title="Guide video" src="about:blank" allow="fullscreen"></iframe>
  </div>
</div>
<script>
var OD9_NOTIF_BASE='<?= DASHBOARD_BASE_URL ?>';
// Per-item mark-read (keepalive so it still lands when the click navigates away).
// The bot is the only DB writer — board-action.php HMAC-signs + forwards.
function od9MarkNotifRead(id){
  try{ fetch(OD9_NOTIF_BASE+'/board-action.php',{method:'POST',keepalive:true,headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'action=notifications_read&notification_id='+encodeURIComponent(id)}).catch(function(){}); }catch(e){}
}
function od9RemoveNotif(id){
  var el=document.querySelector('.notif-item[data-id="'+id+'"]'); if(el) el.remove();
  var card=document.querySelector('.notif-card'); if(!card) return;
  var left=card.querySelectorAll('.notif-item').length;
  var badge=card.querySelector('.notif-count');
  if(badge){ if(left>0){ badge.textContent=left; } else { badge.remove(); } }
  if(left===0) card.remove();
}
function od9DismissNotif(id){ od9MarkNotifRead(id); od9RemoveNotif(id); }
function od9PlayGuide(key, title, id){
  var m=document.getElementById('guideModal'); if(!m) return;
  document.getElementById('guideModalTitle').textContent=title||'Guide';
  document.getElementById('guideModalFrame').src='/guide.php?key='+encodeURIComponent(key)+'&embed=1';
  m.removeAttribute('hidden'); document.body.style.overflow='hidden';
  if(id){ od9MarkNotifRead(id); od9RemoveNotif(id); }
}
function od9CloseGuide(){
  var m=document.getElementById('guideModal'); if(!m) return;
  document.getElementById('guideModalFrame').src='about:blank';
  m.setAttribute('hidden',''); document.body.style.overflow='';
}
</script>

<?php if ($isLoggedIn): ?>
<script src="/js/lib/driver.js.iife.js?v=<?= @filemtime(__DIR__ . '/../js/lib/driver.js.iife.js') ?: '1' ?>"></script>
<script src="/dashboard/tour.js?v=<?= @filemtime(__DIR__ . '/tour.js') ?: '1' ?>"></script>
<?php endif; ?>
</body>
</html>
