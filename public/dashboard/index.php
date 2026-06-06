<?php
/**
 * OD9 Progression Dashboard
 *
 * Visual progression display for OD9 Discord members.
 * Reads progression data directly from bot's SQLite database.
 */

require_once __DIR__ . '/includes/config.php';

$current_page = 'dashboard';

// Secure session
session_set_cookie_params([
    'lifetime' => 604800,
    'path' => '/',
    'secure' => strpos(__DIR__, 'xampp') === false,
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
    $guildId = OD9_GUILD_ID ?? '1309609816934559785';

    try {
        $botDbPath = defined('OD9_BOT_DB_PATH') ? OD9_BOT_DB_PATH : 'C:/Users/Rage/IdeaProjects/OD9-Discord-Bot/od9.db';
        $db = new PDO("sqlite:$botDbPath", null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        // Get user data
        $stmt = $db->prepare("
            SELECT user_id, username, current_tier, total_credits, join_date
            FROM users WHERE user_id = ? AND guild_id = ? LIMIT 1
        ");
        $stmt->execute([$discordId, $guildId]);
        $user = $stmt->fetch();

        if ($user) {
            // Get tier info
            $tierName = ucfirst($user['current_tier'] ?? 'observer');
            $tier = [
                'name' => $tierName,
                'color' => match(strtolower($tierName)) {
                    'observer' => '#808080',
                    'theorist' => '#4169E1',
                    'architect' => '#32CD32',
                    'pioneer' => '#D4AF37',
                    'benefactor' => '#9400D3',
                    default => '#808080'
                }
            ];

            // Get credits
            $credits = [
                'balance' => (int)($user['total_credits'] ?? 0)
            ];

            // Get streak
            $stmt = $db->prepare("
                SELECT current_streak, best_streak, last_activity
                FROM user_streaks WHERE user_id = ? AND guild_id = ? LIMIT 1
            ");
            $stmt->execute([$discordId, $guildId]);
            $streakData = $stmt->fetch();
            $streak = [
                'current' => (int)($streakData['current_streak'] ?? 0),
                'best' => (int)($streakData['best_streak'] ?? 0)
            ];

            // Get achievements count
            $stmt = $db->prepare("
                SELECT COUNT(*) FROM user_achievements
                WHERE user_id = ? AND guild_id = ? AND earned_at IS NOT NULL
            ");
            $stmt->execute([$discordId, $guildId]);
            $achievementCount = (int)$stmt->fetchColumn();

            // Get recent achievements
            $stmt = $db->prepare("
                SELECT ua.achievement_id, ua.earned_at, ad.name, ad.icon, ad.rarity, ad.credit_reward as points
                FROM user_achievements ua
                LEFT JOIN achievement_definitions ad ON ua.achievement_id = ad.achievement_id AND ua.guild_id = ad.guild_id
                WHERE ua.user_id = ? AND ua.guild_id = ? AND ua.earned_at IS NOT NULL
                ORDER BY ua.earned_at DESC LIMIT 10
            ");
            $stmt->execute([$discordId, $guildId]);
            $achievements = $stmt->fetchAll();

            // Get user dimensions for radar chart
            $stmt = $db->prepare("
                SELECT dimension, score
                FROM user_dimensions 
                WHERE user_id = ? AND guild_id = ?
            ");
            $stmt->execute([$discordId, $guildId]);
            $dimensionRows = $stmt->fetchAll();
            $dimensions = [
                'intellectual' => 0,
                'creative' => 0,
                'community' => 0,
                'practical' => 0,
                'leadership' => 0
            ];
            foreach ($dimensionRows as $row) {
                $dim = strtolower($row['dimension']);
                if (isset($dimensions[$dim])) {
                    $dimensions[$dim] = (float)$row['score'];
                }
            }

            // Get recent activity feed
            $stmt = $db->prepare("
                SELECT activity_type, activity_description, credits_earned, streak_bonus, activity_date
                FROM activity_log 
                WHERE user_id = ? AND guild_id = ?
                ORDER BY activity_date DESC LIMIT 20
            ");
            $stmt->execute([$discordId, $guildId]);
            $recentActivity = $stmt->fetchAll();

            // Build progression object for compatibility
            $progression = [
                'current_tier' => $tierName,
                'total_credits' => $credits['balance'],
                'current_streak' => $streak['current'],
                'best_streak' => $streak['best'],
                'achievements_count' => $achievementCount,
                'member_since' => $user['join_date'],
                'dim_intellectual' => $dimensions['intellectual'],
                'dim_creative' => $dimensions['creative'],
                'dim_community' => $dimensions['community'],
                'dim_practical' => $dimensions['practical'],
                'dim_leadership' => $dimensions['leadership']
            ];
        }
    } catch (PDOException $e) {
        error_log("Dashboard DB error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Progression Dashboard - OD9 ASCEND Protocol</title>
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@400;600;700&family=Exo+2:wght@400;600&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<meta name="description" content="Track your ASCEND Protocol progression. View your tier, dimension scores, achievements, and activity feed.">
<meta name="robots" content="noindex, nofollow">
<link rel="icon" type="image/png" href="/images/logos/od9-logo.png">

<style>
:root {
    --primary-blue: #00BFFF;
    --electric-blue: #00A0FF;
    --neon-blue: #00D4FF;
    --chrome: #C0C0C0;
    --carbon: #0D0D0D;
    --carbon-dark: #1A1A1A;
    --glow: 0 0 20px rgba(0,191,255,0.5);
    --nav-height: 80px;
    --tier-observer: #808080;
    --tier-theorist: #4169E1;
    --tier-architect: #9932CC;
    --tier-pioneer: #FFD700;
    --tier-benefactor: #FF4500;
}
* { margin: 0; padding: 0; box-sizing: border-box; }
body {
    background: var(--carbon);
    background-image: linear-gradient(45deg, #111 25%, transparent 25%), linear-gradient(-45deg, #111 25%, transparent 25%);
    background-size: 4px 4px;
    color: var(--chrome);
    font-family: 'Exo 2', sans-serif;
    padding-top: var(--nav-height);
    min-height: 100vh;
}

/* Nav styles from tiers.php */
.od9-nav { position: fixed; top: 0; left: 0; width: 100%; height: var(--nav-height); background: linear-gradient(180deg, rgba(13,13,13,0.98) 0%, rgba(26,26,26,0.95) 100%); backdrop-filter: blur(20px); border-bottom: 2px solid var(--primary-blue); box-shadow: var(--glow); z-index: 9999; }
.nav-container { max-width: 1200px; margin: 0 auto; padding: 0 2rem; height: 100%; display: flex; justify-content: space-between; align-items: center; }
.nav-logo { display: flex; align-items: center; text-decoration: none; }
.nav-logo img { height: 50px; margin-right: 0.75rem; filter: drop-shadow(var(--glow)); }
.nav-logo-text { font-family: 'Orbitron', sans-serif; font-size: 1.5rem; font-weight: 700; color: var(--primary-blue); letter-spacing: 3px; text-shadow: var(--glow); }
.nav-menu { display: flex; list-style: none; gap: 1.5rem; align-items: center; }
.nav-link { color: var(--chrome); text-decoration: none; font-family: 'Rajdhani', sans-serif; font-size: 1rem; font-weight: 600; letter-spacing: 1px; text-transform: uppercase; transition: all 0.3s; padding: 0.5rem 0; position: relative; }
.nav-link::after { content: ''; position: absolute; bottom: 0; left: 50%; width: 0; height: 2px; background: var(--primary-blue); transition: all 0.3s; transform: translateX(-50%); }
.nav-link:hover, .nav-link.active { color: var(--primary-blue); }
.nav-link:hover::after, .nav-link.active::after { width: 100%; }
.nav-btn { background: linear-gradient(135deg, var(--primary-blue), var(--electric-blue)); color: var(--carbon); padding: 0.6rem 1.2rem; border-radius: 4px; text-decoration: none; font-family: 'Rajdhani', sans-serif; font-weight: 700; font-size: 0.9rem; text-transform: uppercase; display: flex; align-items: center; gap: 0.5rem; transition: all 0.3s; box-shadow: var(--glow); }
.nav-btn:hover { transform: translateY(-2px); }

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

/* Mobile menu - hidden by default, visible on toggle */
.mobile-menu {
    display: none;
    position: fixed;
    top: var(--nav-height);
    left: 0;
    width: 100%;
    background: rgba(13,13,13,0.98);
    backdrop-filter: blur(20px);
    padding: 1rem 0;
    z-index: 9998;
    border-bottom: 2px solid var(--primary-blue);
    flex-direction: column;
    align-items: center;
}
.mobile-menu.active { display: flex; }
.mobile-menu a {
    color: var(--chrome);
    text-decoration: none;
    padding: 0.75rem 2rem;
    font-family: 'Rajdhani', sans-serif;
    font-weight: 600;
    text-transform: uppercase;
    transition: all 0.3s;
}
.mobile-menu a:hover, .mobile-menu a.active { color: var(--primary-blue); }
.mobile-menu .mobile-discord {
    background: #5865F2;
    color: #fff;
    border-radius: 4px;
    margin-top: 0.5rem;
}

/* Mobile hamburger */
.mobile-toggle {
    display: none;
    background: none;
    border: none;
    cursor: pointer;
    padding: 0.5rem;
}
.mobile-toggle span {
    display: block;
    width: 25px;
    height: 2px;
    background: var(--chrome);
    margin: 5px 0;
    transition: all 0.3s;
}
.mobile-toggle.active span:nth-child(1) { transform: rotate(45deg) translate(5px, 5px); }
.mobile-toggle.active span:nth-child(2) { opacity: 0; }
.mobile-toggle.active span:nth-child(3) { transform: rotate(-45deg) translate(5px, -5px); }

@media (max-width: 1100px) {
    .nav-menu { display: none; }
    .mobile-toggle { display: block; }
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
                $tier = strtolower($progression['tier'] ?? 'observer');
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
                    <div class="achievement-item" title="<?= htmlspecialchars($ach['achievement_desc'] ?? $ach['achievement_name']) ?>">
                        <div class="achievement-icon">🏆</div>
                        <div class="achievement-name"><?= htmlspecialchars($ach['achievement_name'] ?? $ach['achievement_id']) ?></div>
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
            labels: ['Analytical', 'Creative', 'Collaborative', 'Practical', 'Leadership'],
            datasets: [{
                label: 'Dimension Scores',
                data: [
                    <?= $progression['dim_analytical'] ?? 0 ?>,
                    <?= $progression['dim_creative'] ?? 0 ?>,
                    <?= $progression['dim_collaborative'] ?? 0 ?>,
                    <?= $progression['dim_practical'] ?? 0 ?>,
                    <?= $progression['dim_leadership'] ?? 0 ?>
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
