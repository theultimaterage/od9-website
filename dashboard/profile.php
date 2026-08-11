<?php
/**
 * OD9 Public Profile Page — /dashboard/profile.php?u=<discord_id>
 *
 * Shows a member's LIVE progression (read from the bot's SQLite DB, exactly
 * like the dashboard) when their profile visibility is public. Visibility is
 * web-owned in MySQL (od9_profile_visibility) — the bot has no profile_public
 * logic, so the web is the single source of truth.
 */

require_once __DIR__ . '/includes/config.php';             // OD9_BOT_DB_PATH
require_once __DIR__ . '/includes/db.php';                 // loads config/database.php -> getDatabaseConnection()
require_once __DIR__ . '/includes/profile_visibility.php'; // od9_is_profile_public()
require_once __DIR__ . '/../includes/env.php';             // od9_local_time()
require_once __DIR__ . '/../includes/tiers.php';           // od9_tier_color() — canonical tier colors

$requestedId = $_GET['u'] ?? '';

// View defaults.
$error       = null;
$user        = null;
$progression = null;
$achievements = [];
$activity     = [];
$displayName  = 'OD9 Member';
$tierName     = 'OBSERVER';
$tierColor    = '#808080';

if (!$requestedId || !preg_match('/^\d{17,20}$/', $requestedId)) {
    http_response_code(404);
    $error = 'invalid';
} elseif (!od9_is_profile_public($requestedId)) {
    http_response_code(404);
    $error = 'not_found';
} else {
    // Live progression from the bot SQLite (same guarded pattern as index.php):
    // one bad query degrades its own widget, never the whole page.
    $botFetch = function (?PDO $db, string $sql, array $params): array {
        if (!$db) return [];
        try {
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("profile.php query failed: " . $e->getMessage());
            return [];
        }
    };

    $bot = null;
    try {
        $bot = new PDO("sqlite:" . (defined('OD9_BOT_DB_PATH') ? OD9_BOT_DB_PATH : ''), null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } catch (PDOException $e) {
        error_log("profile.php bot DB connection failed: " . $e->getMessage());
    }

    $p = [$requestedId];

    // We key on user_id alone — the bot DB is single-guild and the OD9_GUILD_ID
    // filter was the bug that blanked the dashboard (see index.php notes).
    $user = $botFetch($bot,
        "SELECT user_id, username, current_tier, total_credits, join_date
         FROM users WHERE user_id = ? LIMIT 1", $p)[0] ?? null;

    if (!$user) {
        http_response_code(404);
        $error = 'not_found';
    } else {
        $dimensions = ['knowledge' => 0, 'resource' => 0, 'community' => 0, 'consciousness' => 0, 'system' => 0];
        // Wide schema (*_score columns), same fix as index.php (2026-06-12).
        $dimRow = $botFetch($bot, "SELECT knowledge_score, resource_score, community_score, consciousness_score, system_score FROM user_dimensions WHERE user_id = ?", $p)[0] ?? null;
        if ($dimRow) {
            foreach (array_keys($dimensions) as $d) {
                $dimensions[$d] = (float) ($dimRow[$d . '_score'] ?? 0);
            }
        }

        $achievements = $botFetch($bot,
            "SELECT ua.achievement_id, ua.earned_at, ad.name, ad.icon, ad.rarity,
                    ad.badge_image, ad.description, ua.revealed
             FROM user_achievements ua
             LEFT JOIN achievement_definitions ad
               ON ua.achievement_id = ad.achievement_id AND ua.guild_id = ad.guild_id
             WHERE ua.user_id = ? AND ua.earned_at IS NOT NULL
             ORDER BY ua.earned_at DESC LIMIT 12", $p);

        $activity = $botFetch($bot,
            "SELECT activity_type, activity_description, credits_earned, activity_date
             FROM activity_log WHERE user_id = ?
             ORDER BY activity_date DESC LIMIT 8", $p);

        $tierName   = strtoupper($user['current_tier'] ?? 'observer');
        // Canonical tier color (mirror of config.TIER_COLORS) — single source
        // shared with me.php / library.php / tiers.php.
        $tierColor   = od9_tier_color($user['current_tier'] ?? 'observer');
        $displayName = $user['username'] ?: 'OD9 Member';

        $progression = [
            'tier'             => $tierName,
            'total_credits'    => (int) ($user['total_credits'] ?? 0),
            'dim_knowledge'    => $dimensions['knowledge'],
            'dim_resource'     => $dimensions['resource'],
            'dim_community'    => $dimensions['community'],
            'dim_consciousness'=> $dimensions['consciousness'],
            'dim_system'       => $dimensions['system'],
        ];
    }
}

// SEO / OG meta — computed AFTER the data (replaces the old broken preamble
// that leaked raw PHP echo tags into the OG meta as literal text).
if ($error === null) {
    $pageTitle = "{$displayName}'s Progression - OD9 ASCEND Protocol";
    $pageDesc  = "{$displayName} is a {$tierName} tier member of OD9 with "
               . number_format($progression['total_credits']) . " credits earned.";
} else {
    $pageTitle = 'Profile Not Found - OD9';
    $pageDesc  = 'This profile is either private or does not exist.';
}
$page_title          = $pageTitle;
$page_description    = $pageDesc;
$page_slug           = 'profile.php';
$page_og_title       = $pageTitle;
$page_og_description = $pageDesc;
$nav_base            = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/dashboard/profile.php')), '/\\');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include __DIR__ . '/../includes/head.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link rel="stylesheet" href="/css/dashboard.css">
<style>:root { --tier-color: <?= htmlspecialchars($tierColor, ENT_QUOTES) ?>; }</style>
</head>
<body>

<?php
// Universal site nav (matches the homepage). One level deep in /dashboard/,
// so set the nav base to the site root before including it.
$nav_base = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/dashboard/profile.php')), '/\\');
include __DIR__ . '/../includes/nav.php';
?>

<div class="profile-container">

<?php if (isset($error)): ?>
    <!-- Error state -->
    <div class="error-container">
        <?php if ($error === 'not_found'): ?>
            <i class="fas fa-user-secret error-icon"></i>
            <h1>Profile Not Available</h1>
            <p>This profile is either private or the user hasn't joined OD9 yet.</p>
        <?php else: ?>
            <i class="fas fa-exclamation-triangle error-icon"></i>
            <h1>Invalid Profile Link</h1>
            <p>The profile URL you followed is invalid.</p>
        <?php endif; ?>
        <a href="https://discord.gg/spgmrXVMWq" class="error-btn" target="_blank">
            <i class="fab fa-discord"></i> Join OD9 Discord
        </a>
    </div>

<?php else: ?>
    <!-- Profile header -->
    <div class="profile-header">
        <?php // The bot users table has no avatar hash, so show the placeholder. ?>
        <div class="profile-avatar-placeholder">
            <i class="fas fa-user"></i>
        </div>

        <div class="profile-info">
            <h1><?= htmlspecialchars($displayName) ?></h1>
            <span class="tier-badge"><?= htmlspecialchars($tierName) ?></span>
            <p class="credits"><strong><?= number_format($progression['total_credits'] ?? 0) ?></strong> total credits earned</p>
            <span class="public-badge"><i class="fas fa-globe"></i> Public Profile</span>

            <!-- Share Buttons -->
            <div class="share-buttons">
                <button class="share-btn share-twitter" onclick="shareTwitter()" title="Share on X/Twitter">
                    <i class="fab fa-x-twitter"></i>
                </button>
                <button class="share-btn share-facebook" onclick="shareFacebook()" title="Share on Facebook">
                    <i class="fab fa-facebook-f"></i>
                </button>
                <button class="share-btn share-linkedin" onclick="shareLinkedIn()" title="Share on LinkedIn">
                    <i class="fab fa-linkedin-in"></i>
                </button>
                <button class="share-btn share-copy" onclick="copyProfileLink()" title="Copy Link">
                    <i class="fas fa-link"></i>
                </button>
            </div>
        </div>
    </div>

    <div class="stats-grid">
        <!-- Dimension Radar -->
        <div class="stat-card">
            <h2><i class="fas fa-chart-pie"></i> Dimension Scores</h2>
            <div class="radar-container">
                <canvas id="dimensionChart"></canvas>
            </div>
        </div>

        <!-- Achievements -->
        <div class="stat-card">
            <h2><i class="fas fa-trophy"></i> Achievements</h2>
            <?php if (!empty($achievements)): ?>
                <div class="achievements-grid">
                    <?php foreach (array_slice($achievements, 0, 12) as $ach):
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
                            <div class="achievement-name"><?= htmlspecialchars($sealed ? 'Sealed' : ($ach['name'] ?? 'Achievement')) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="no-achievements">No achievements earned yet</p>
            <?php endif; ?>
        </div>

        <!-- Recent Activity -->
        <div class="stat-card" style="grid-column: 1 / -1;">
            <h2><i class="fas fa-history"></i> Recent Activity</h2>
            <?php if (!empty($activity)): ?>
                <ul class="activity-list">
                    <?php foreach (array_slice($activity, 0, 8) as $act): ?>
                        <li class="activity-item">
                            <span class="activity-desc"><?= htmlspecialchars($act['activity_description'] ?? $act['activity_type'] ?? 'Activity') ?></span>
                            <span class="activity-time" style="color:#888;font-size:0.75rem;margin-left:0.5rem"><?= htmlspecialchars(od9_local_time($act['activity_date'] ?? null)) ?></span>
                            <?php if (!empty($act['credits_earned'])): ?>
                                <span class="activity-credits">+<?= number_format($act['credits_earned']) ?></span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="no-activity">No recent activity</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="profile-footer">
        <p>Part of the <a href="/tiers.php">ASCEND Protocol</a> | <a href="https://discord.gg/spgmrXVMWq">Join OD9 Discord</a></p>
    </div>

    <script>
    // Dimension radar chart (bot dims: knowledge/resource/community/consciousness/system)
    const ctx = document.getElementById('dimensionChart').getContext('2d');
    new Chart(ctx, {
        type: 'radar',
        data: {
            labels: ['Knowledge', 'Resource', 'Community', 'Consciousness', 'System'],
            datasets: [{
                data: [
                    <?= (float)($progression['dim_knowledge'] ?? 0) ?>,
                    <?= (float)($progression['dim_resource'] ?? 0) ?>,
                    <?= (float)($progression['dim_community'] ?? 0) ?>,
                    <?= (float)($progression['dim_consciousness'] ?? 0) ?>,
                    <?= (float)($progression['dim_system'] ?? 0) ?>
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
                    pointLabels: { color: '#C0C0C0', font: { size: 11 } },
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

</div>

<script>
// Share functionality
const profileUrl = window.location.href;
const profileTitle = document.title;
const shareText = '<?= isset($displayName) ? addslashes($displayName) : "OD9 Member" ?> is a <?= isset($tierName) ? $tierName : "member" ?> on OD9 ASCEND Protocol!';

function shareTwitter() {
    const url = `https://twitter.com/intent/tweet?text=${encodeURIComponent(shareText)}&url=${encodeURIComponent(profileUrl)}`;
    window.open(url, '_blank', 'width=550,height=420');
}

function shareFacebook() {
    const url = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(profileUrl)}`;
    window.open(url, '_blank', 'width=550,height=420');
}

function shareLinkedIn() {
    const url = `https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(profileUrl)}`;
    window.open(url, '_blank', 'width=550,height=420');
}

function copyProfileLink() {
    navigator.clipboard.writeText(profileUrl).then(() => {
        const btn = document.querySelector('.share-copy');
        btn.classList.add('copied');
        btn.innerHTML = '<i class="fas fa-check"></i>';
        setTimeout(() => {
            btn.classList.remove('copied');
            btn.innerHTML = '<i class="fas fa-link"></i>';
        }, 2000);
    });
}
</script>

</body>
</html>
