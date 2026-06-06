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

// Secure session
session_set_cookie_params([
    'lifetime' => 604800,
    'path' => '/',
    'secure' => od9_cookie_secure(),
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();

// Check if logged in via Discord OAuth
$isLoggedIn = !empty($_SESSION['discord_id']);
$user = null;
$progression = null;
$achievements = [];
$credits = null;
$streak = null;
$tier = null;

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
            "SELECT ua.achievement_id, ua.earned_at, ad.name, ad.icon, ad.rarity, ad.credit_reward as points
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
        foreach ($botFetch($db,
            "SELECT dimension, score FROM user_dimensions
             WHERE user_id = ?", $params) as $row) {
            $dim = strtolower($row['dimension'] ?? '');
            if (isset($dimensions[$dim])) {
                $dimensions[$dim] = (float)$row['score'];
            }
        }

        // Recent activity feed.
        $recentActivity = $botFetch($db,
            "SELECT activity_type, activity_description, credits_earned, streak_bonus, activity_date
             FROM activity_log WHERE user_id = ?
             ORDER BY activity_date DESC LIMIT 20", $params);

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php $nav_base = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/dashboard/index.php')), '/'); include __DIR__ . '/../includes/head.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
body {
    background: var(--carbon);
    background-image: linear-gradient(45deg, #111 25%, transparent 25%), linear-gradient(-45deg, #111 25%, transparent 25%);
    background-size: 4px 4px;
    color: var(--chrome);
    font-family: 'Exo 2', sans-serif;
    padding-top: var(--nav-height);
    min-height: 100vh;
}

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
    background: var(--carbon-dark);
    border: 1px solid #333;
    border-radius: 12px;
    padding: 1.5rem;
}

.card h3 {
    font-family: 'Orbitron', sans-serif;
    color: var(--primary-blue);
    font-size: 1.2rem;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid #333;
}

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

.tier-observer { background: linear-gradient(135deg, #808080, #606060); color: #fff; }
.tier-theorist { background: linear-gradient(135deg, #4169E1, #3050B0); color: #fff; }
.tier-architect { background: linear-gradient(135deg, #9932CC, #7020A0); color: #fff; }
.tier-pioneer { background: linear-gradient(135deg, #FFD700, #FFA500); color: #000; }
.tier-benefactor { background: linear-gradient(135deg, #FF4500, #DC143C); color: #fff; }

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
}

.achievement-item:hover {
    transform: translateY(-2px);
    background: #333;
}

.achievement-icon {
    font-size: 2rem;
    margin-bottom: 0.5rem;
}

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
    color: #666;
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
    color: #666;
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
</head>
<body>

<?php
// Universal site nav. We're one level deep in /dashboard/, so set the nav base
// to the site root (up one dir from SCRIPT_NAME) before including it.
$nav_base = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/dashboard/index.php')), '/\\');
include __DIR__ . '/../includes/nav.php';
?>

<div class="dashboard-container">
    <div class="dashboard-header">
        <h1><i class="fas fa-chart-line"></i> Progression Dashboard</h1>
        <p class="subtitle">Track your ASCEND Protocol journey</p>
    </div>

    <?php if (!$isLoggedIn): ?>
    <!-- Not logged in - show login prompt -->
    <div class="login-prompt">
        <h2>Connect Your Discord</h2>
        <p>Link your Discord account to see your progression stats, achievements, and activity feed.</p>
        <a href="auth/discord.php" class="discord-login-btn">
            <i class="fab fa-discord"></i> Login with Discord
        </a>
        <p style="margin-top: 1.5rem; font-size: 0.9rem; color: #666;">
            Don't have an account? <a href="/join.php" style="color: var(--primary-blue);">Join OD9 on Discord</a>
        </p>
    </div>

    <?php else: ?>
    <!-- Logged in - show dashboard -->
    <div class="dashboard-grid">
        <!-- Tier & Progress Card -->
        <div class="card">
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
        </div>

        <!-- Dimensions Radar Card -->
        <div class="card">
            <h3><i class="fas fa-star"></i> Dimension Scores</h3>
            <div class="radar-container">
                <canvas id="dimensionRadar"></canvas>
            </div>
        </div>

        <!-- Achievements Card -->
        <div class="card">
            <h3><i class="fas fa-trophy"></i> Recent Achievements</h3>
            <?php if (empty($achievements)): ?>
                <p style="color: #666; text-align: center; padding: 2rem;">No achievements yet. Keep contributing!</p>
            <?php else: ?>
                <div class="achievement-grid">
                    <?php foreach ($achievements as $ach): ?>
                    <div class="achievement-item" title="<?= htmlspecialchars($ach['name'] ?? $ach['achievement_id']) ?>">
                        <div class="achievement-icon"><?= htmlspecialchars($ach['icon'] ?? '🏆') ?></div>
                        <div class="achievement-name"><?= htmlspecialchars($ach['name'] ?? $ach['achievement_id']) ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Activity Feed Card -->
        <div class="card">
            <h3><i class="fas fa-stream"></i> Recent Activity</h3>
            <?php if (empty($recentActivity)): ?>
                <p style="color: #666; text-align: center; padding: 2rem;">No activity yet. Start contributing on Discord!</p>
            <?php else: ?>
                <div class="activity-feed">
                    <?php foreach ($recentActivity as $activity): ?>
                    <div class="activity-item">
                        <div class="activity-icon">
                            <i class="fas fa-bolt"></i>
                        </div>
                        <div class="activity-content">
                            <div class="activity-desc"><?= htmlspecialchars($activity['activity_description'] ?? $activity['activity_type']) ?></div>
                            <div class="activity-time"><?= date('M j, g:i A', strtotime($activity['activity_date'])) ?></div>
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

</body>
</html>
