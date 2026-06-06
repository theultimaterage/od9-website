<?php
$page_title = '<?= htmlspecialchars($pageTitle) ?>';
$page_description = '<?= htmlspecialchars($pageDesc) ?>';
$page_slug = 'profile.php';
$page_og_title = '<?= htmlspecialchars($displayName) ?>\'s OD9 Progression';
$page_og_description = '<?= htmlspecialchars($tierName) ?> tier member with <?= number_format($progression[\'total_credits\']) ?> credits';
?>
<?php
/**
 * OD9 Public Profile Page
 *
 * Displays a user's progression if their profile is public.
 * URL: /dashboard/profile.php?u=<discord_id>
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

// Get the requested Discord ID
$requestedId = $_GET['u'] ?? '';
$guildId = OD9_GUILD_ID ?? '1309609816934559785';

// Validate input
if (!$requestedId || !preg_match('/^\d{17,20}$/', $requestedId)) {
    http_response_code(404);
    $error = 'invalid';
} else {
    // Get public profile data
    $profileData = getPublicProfileData($requestedId, $guildId);

    if (!$profileData) {
        http_response_code(404);
        $error = 'not_found';
    }
}

// If we have profile data, extract it
if (isset($profileData)) {
    $user = $profileData['user'];
    $progression = $profileData['progression'];
    $achievements = $profileData['achievements'];
    $activity = $profileData['activity'];

    // Tier colors
    $tierColors = [
        'OBSERVER' => '#808080',
        'THEORIST' => '#4169E1',
        'ARCHITECT' => '#9932CC',
        'PIONEER' => '#FFD700',
        'BENEFACTOR' => '#FF4500'
    ];

    $tierName = strtoupper($progression['tier'] ?? 'OBSERVER');
    $tierColor = $tierColors[$tierName] ?? '#808080';

    // Build page title and description for SEO
    $displayName = $user['display_name'] ?: $user['discord_username'] ?: 'OD9 Member';
    $pageTitle = "{$displayName}'s Progression - OD9 ASCEND Protocol";
    $pageDesc = "{$displayName} is a {$tierName} tier member of OD9 with {$progression['total_credits']} credits earned.";
} else {
    $pageTitle = 'Profile Not Found - OD9';
    $pageDesc = 'This profile is either private or does not exist.';
}

$page_title       = $pageTitle;
$page_description = $pageDesc;
$page_slug        = 'profile.php';
$nav_base         = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/dashboard/profile.php')), '/\\');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include __DIR__ . '/../includes/head.php'; ?>
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
</style>
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
        <?php
        $avatarUrl = null;
        if ($user['discord_avatar']) {
            $avatarUrl = "https://cdn.discordapp.com/avatars/{$user['discord_id']}/{$user['discord_avatar']}.png?size=256";
        }
        ?>
        <?php if ($avatarUrl): ?>
            <img src="<?= htmlspecialchars($avatarUrl) ?>" alt="Avatar" class="profile-avatar">
        <?php else: ?>
            <div class="profile-avatar-placeholder">
                <i class="fas fa-user"></i>
            </div>
        <?php endif; ?>

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
            <h2><i class="fas fa-chart-radar"></i> Dimension Scores</h2>
            <div class="radar-container">
                <canvas id="dimensionChart"></canvas>
            </div>
        </div>

        <!-- Achievements -->
        <div class="stat-card">
            <h2><i class="fas fa-trophy"></i> Achievements</h2>
            <?php if (!empty($achievements)): ?>
                <div class="achievements-grid">
                    <?php foreach (array_slice($achievements, 0, 12) as $ach): ?>
                        <div class="achievement-item" title="<?= htmlspecialchars($ach['name'] ?? 'Achievement') ?>">
                            <div class="achievement-icon"><?= $ach['icon'] ?? '🏆' ?></div>
                            <div class="achievement-name"><?= htmlspecialchars($ach['name'] ?? 'Achievement') ?></div>
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
                            <span class="activity-desc"><?= htmlspecialchars($act['description'] ?? $act['activity_type'] ?? 'Activity') ?></span>
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
    // Dimension radar chart
    const ctx = document.getElementById('dimensionChart').getContext('2d');
    new Chart(ctx, {
        type: 'radar',
        data: {
            labels: ['Intellectual', 'Creative', 'Community', 'Practical', 'Leadership'],
            datasets: [{
                data: [
                    <?= (float)($progression['dim_analytical'] ?? 0) ?>,
                    <?= (float)($progression['dim_creative'] ?? 0) ?>,
                    <?= (float)($progression['dim_collaborative'] ?? 0) ?>,
                    <?= (float)($progression['dim_practical'] ?? 0) ?>,
                    <?= (float)($progression['dim_leadership'] ?? 0) ?>
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
