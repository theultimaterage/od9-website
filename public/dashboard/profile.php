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
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle) ?></title>
<meta name="description" content="<?= htmlspecialchars($pageDesc) ?>">
<?php if (isset($profileData)): ?>
<meta property="og:title" content="<?= htmlspecialchars($displayName) ?>'s OD9 Progression">
<meta property="og:description" content="<?= htmlspecialchars($tierName) ?> tier member with <?= number_format($progression['total_credits']) ?> credits">
<meta property="og:type" content="profile">
<meta property="og:url" content="<?= htmlspecialchars(DASHBOARD_BASE_URL . '/profile.php?u=' . urlencode($requestedId)) ?>">
<meta name="twitter:card" content="summary">
<?php else: ?>
<meta name="robots" content="noindex">
<?php endif; ?>
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@400;600;700&family=Exo+2:wght@400;600&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
    --tier-color: <?= $tierColor ?? '#808080' ?>;
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

/* Nav styles */
.od9-nav { position: fixed; top: 0; left: 0; width: 100%; height: var(--nav-height); background: linear-gradient(180deg, rgba(13,13,13,0.98) 0%, rgba(26,26,26,0.95) 100%); backdrop-filter: blur(20px); border-bottom: 2px solid var(--primary-blue); box-shadow: var(--glow); z-index: 9999; }
.nav-container { max-width: 1200px; margin: 0 auto; padding: 0 2rem; height: 100%; display: flex; justify-content: space-between; align-items: center; }
.nav-logo { display: flex; align-items: center; text-decoration: none; }
.nav-logo img { height: 50px; margin-right: 0.75rem; filter: drop-shadow(var(--glow)); }
.nav-logo-text { font-family: 'Orbitron', sans-serif; font-size: 1.5rem; font-weight: 700; color: var(--primary-blue); letter-spacing: 3px; text-shadow: var(--glow); }
.nav-menu { display: flex; list-style: none; gap: 1.5rem; align-items: center; }
.nav-link { color: var(--chrome); text-decoration: none; font-family: 'Rajdhani', sans-serif; font-size: 1rem; font-weight: 600; letter-spacing: 1px; text-transform: uppercase; transition: all 0.3s; padding: 0.5rem 0; position: relative; }
.nav-link::after { content: ''; position: absolute; bottom: 0; left: 50%; width: 0; height: 2px; background: var(--primary-blue); transition: all 0.3s; transform: translateX(-50%); }
.nav-link:hover { color: var(--primary-blue); }
.nav-link:hover::after { width: 100%; }
.nav-btn { background: linear-gradient(135deg, var(--primary-blue), var(--electric-blue)); color: var(--carbon); padding: 0.6rem 1.2rem; border-radius: 4px; text-decoration: none; font-family: 'Rajdhani', sans-serif; font-weight: 700; font-size: 0.9rem; text-transform: uppercase; display: flex; align-items: center; gap: 0.5rem; transition: all 0.3s; box-shadow: var(--glow); }
.nav-btn:hover { transform: translateY(-2px); }

/* Profile container */
.profile-container {
    max-width: 1000px;
    margin: 0 auto;
    padding: 2rem;
}

/* Error state */
.error-container {
    text-align: center;
    padding: 4rem 2rem;
}

.error-icon {
    font-size: 4rem;
    color: #666;
    margin-bottom: 1.5rem;
}

.error-container h1 {
    font-family: 'Orbitron', sans-serif;
    font-size: 2rem;
    color: #fff;
    margin-bottom: 1rem;
}

.error-container p {
    color: var(--chrome);
    margin-bottom: 2rem;
    font-size: 1.1rem;
}

.error-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: var(--primary-blue);
    color: var(--carbon);
    text-decoration: none;
    padding: 0.75rem 1.5rem;
    border-radius: 6px;
    font-family: 'Rajdhani', sans-serif;
    font-weight: 600;
    transition: all 0.3s;
}

.error-btn:hover {
    transform: translateY(-2px);
}

/* Profile header */
.profile-header {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    margin-bottom: 2rem;
    padding: 2rem;
    background: linear-gradient(145deg, rgba(26,26,26,0.95), rgba(13,13,13,0.98));
    border: 1px solid rgba(0,191,255,0.3);
    border-radius: 12px;
}

.profile-avatar {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    border: 3px solid var(--tier-color);
    box-shadow: 0 0 20px var(--tier-color);
}

.profile-avatar-placeholder {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    border: 3px solid var(--tier-color);
    background: linear-gradient(135deg, #333, #222);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    color: var(--tier-color);
}

.profile-info h1 {
    font-family: 'Orbitron', sans-serif;
    font-size: 1.75rem;
    color: #fff;
    margin-bottom: 0.5rem;
}

.profile-info .tier-badge {
    display: inline-block;
    background: var(--tier-color);
    color: #000;
    padding: 0.35rem 1rem;
    border-radius: 20px;
    font-family: 'Rajdhani', sans-serif;
    font-weight: 700;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.profile-info .credits {
    color: var(--chrome);
    margin-top: 0.5rem;
    font-size: 1rem;
}

.profile-info .credits strong {
    color: var(--primary-blue);
}

/* Stats grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: linear-gradient(145deg, rgba(26,26,26,0.95), rgba(13,13,13,0.98));
    border: 1px solid rgba(0,191,255,0.3);
    border-radius: 12px;
    padding: 1.5rem;
}

.stat-card h2 {
    font-family: 'Orbitron', sans-serif;
    font-size: 1rem;
    color: var(--primary-blue);
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

/* Radar chart */
.radar-container {
    height: 300px;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Achievements */
.achievements-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
    gap: 0.75rem;
}

.achievement-item {
    text-align: center;
    padding: 0.75rem;
    background: rgba(0,0,0,0.3);
    border-radius: 8px;
    transition: transform 0.2s;
}

.achievement-item:hover {
    transform: scale(1.05);
}

.achievement-icon {
    font-size: 1.5rem;
    margin-bottom: 0.25rem;
}

.achievement-name {
    font-size: 0.7rem;
    color: var(--chrome);
    line-height: 1.2;
}

.no-achievements {
    color: #666;
    font-style: italic;
    text-align: center;
    padding: 2rem;
}

/* Activity feed */
.activity-list {
    list-style: none;
}

.activity-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    border-bottom: 1px solid rgba(255,255,255,0.05);
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-desc {
    color: var(--chrome);
    font-size: 0.9rem;
}

.activity-credits {
    color: var(--primary-blue);
    font-weight: 600;
    font-size: 0.85rem;
}

.no-activity {
    color: #666;
    font-style: italic;
    text-align: center;
    padding: 2rem;
}

/* Public badge */
.public-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    color: #28a745;
    font-size: 0.85rem;
    margin-top: 0.5rem;
}

/* Footer */
.profile-footer {
    text-align: center;
    margin-top: 3rem;
    padding-top: 2rem;
    border-top: 1px solid rgba(255,255,255,0.1);
    color: #666;
    font-size: 0.85rem;
}

.profile-footer a {
    color: var(--primary-blue);
    text-decoration: none;
}

/* Mobile */
@media (max-width: 600px) {
    .profile-header {
        flex-direction: column;
        text-align: center;
    }

    .profile-info h1 {
        font-size: 1.5rem;
    }

    .stats-grid {
        grid-template-columns: 1fr;
    }
}
/* Share Buttons */
.share-buttons {
    display: flex;
    gap: 0.75rem;
    justify-content: center;
    margin-top: 1.5rem;
}
.share-btn {
    width: 44px;
    height: 44px;
    border: none;
    border-radius: 50%;
    cursor: pointer;
    font-size: 1.1rem;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}
.share-twitter { background: #000; color: #fff; }
.share-twitter:hover { background: #333; transform: scale(1.1); }
.share-facebook { background: #1877f2; color: #fff; }
.share-facebook:hover { background: #0d65d9; transform: scale(1.1); }
.share-linkedin { background: #0a66c2; color: #fff; }
.share-linkedin:hover { background: #004182; transform: scale(1.1); }
.share-copy { background: var(--primary-blue); color: #000; }
.share-copy:hover { background: var(--neon-blue); transform: scale(1.1); }
.share-copy.copied { background: #28a745; color: #fff; }

/* Enhanced Mobile Responsive */
@media (max-width: 600px) {
    .profile-header { padding: 1.5rem 1rem; }
    .avatar { width: 80px; height: 80px; font-size: 2rem; }
    .profile-info h1 { font-size: 1.5rem; }
    .stats-grid { grid-template-columns: 1fr; gap: 1rem; padding: 0 0.5rem; }
    .stat-card { padding: 1rem; }
    .radar-container { max-width: 280px; }
    .achievement-grid { grid-template-columns: repeat(3, 1fr); gap: 0.5rem; }
    .activity-item { padding: 0.5rem; gap: 0.5rem; }
    .activity-icon { width: 32px; height: 32px; font-size: 0.9rem; }
    .share-buttons { gap: 0.5rem; }
    .share-btn { width: 40px; height: 40px; font-size: 1rem; }
}
@media (max-width: 400px) {
    .profile-info h1 { font-size: 1.25rem; }
    .tier-badge { font-size: 0.75rem; padding: 0.25rem 0.75rem; }
    .achievement-grid { grid-template-columns: repeat(2, 1fr); }
}
</style>
</head>
<body>

<!-- Navigation -->
<nav class="od9-nav">
    <div class="nav-container">
        <a href="/" class="nav-logo">
            <img src="/images/logos/od9-logo.png" alt="OD9">
            <span class="nav-logo-text">OD9</span>
        </a>
        <ul class="nav-menu">
            <li><a href="/" class="nav-link">Home</a></li>
            <li><a href="/tiers.php" class="nav-link">Tiers</a></li>
            <li><a href="/dashboard/" class="nav-link">Dashboard</a></li>
            <li><a href="https://discord.gg/spgmrXVMWq" class="nav-btn" target="_blank"><i class="fab fa-discord"></i> Join</a></li>
        </ul>
    </div>
</nav>

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
