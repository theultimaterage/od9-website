<?php
$page_title = 'Settings - OD9 Dashboard';
$page_description = '';
$page_slug = 'settings.php';
?>
<?php
/**
 * OD9 Dashboard Settings
 * 
 * User settings including public profile toggle.
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/profile_visibility.php';
require_once __DIR__ . '/../includes/env.php';

$current_page = 'settings';

// Secure session
session_set_cookie_params([
    'lifetime' => 604800,
    'path' => '/',
    'secure' => od9_cookie_secure(),
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();

// Must be logged in
if (empty($_SESSION['discord_id'])) {
    header('Location: ' . DASHBOARD_BASE_URL . '/auth/discord.php');
    exit;
}

$discordId = $_SESSION['discord_id'];
$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        $message = 'Invalid request. Please try again.';
        $messageType = 'error';
    } else {
        $publicProfile = isset($_POST['public_profile']) && $_POST['public_profile'] === '1';
        
        if (od9_set_profile_public($discordId, $publicProfile)) {
            $message = $publicProfile 
                ? 'Your profile is now public! Share your profile link with others.' 
                : 'Your profile is now private.';
            $messageType = 'success';
        } else {
            $message = 'Failed to update settings. Please try again.';
            $messageType = 'error';
        }
    }
}

// Generate CSRF token
$_SESSION['csrf_token'] = bin2hex(random_bytes(16));

// Get current visibility (web-owned MySQL; default private / opt-in)
$isPublic = od9_is_profile_public($discordId);

// Build profile URL
$profileUrl = DASHBOARD_BASE_URL . '/profile.php?u=' . urlencode($discordId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php $nav_base = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/dashboard/settings.php')), '/'); include __DIR__ . '/../includes/head.php'; ?>
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
$nav_base = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/dashboard/settings.php')), '/\\');
include __DIR__ . '/../includes/nav.php';
?>

<div class="settings-container">
    <div class="settings-header">
        <h1><i class="fas fa-cog"></i> Settings</h1>
        <p>Manage your OD9 dashboard preferences</p>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?>">
        <i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
        <?= htmlspecialchars($message) ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
        
        <div class="settings-card">
            <h2><i class="fas fa-user-shield"></i> Profile Visibility</h2>
            <p>
                Control whether your progression profile is visible to others. When enabled, anyone with your 
                profile link can view your tier, dimension scores, achievements, and recent activity.
            </p>

            <div class="toggle-wrapper">
                <div class="toggle-label">
                    <strong>Public Profile</strong>
                    <span>Allow others to view your progression</span>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" name="public_profile" value="1" id="publicToggle" <?= $isPublic ? 'checked' : '' ?>>
                    <span class="toggle-slider"></span>
                </label>
            </div>

            <div class="profile-url-section <?= $isPublic ? '' : 'hidden' ?>" id="urlSection">
                <h3><i class="fas fa-link"></i> Your Profile URL</h3>
                <div class="url-box">
                    <input type="text" class="url-input" id="profileUrl" value="<?= htmlspecialchars($profileUrl) ?>" readonly>
                    <button type="button" class="copy-btn" id="copyBtn" onclick="copyUrl()">
                        <i class="fas fa-copy"></i> Copy
                    </button>
                </div>
            </div>
        </div>

        <button type="submit" class="save-btn">
            <i class="fas fa-save"></i> Save Settings
        </button>
    </form>

    <a href="/dashboard/" class="back-link">
        <i class="fas fa-arrow-left"></i> Back to Dashboard
    </a>

    <div class="settings-footer">
        <p>Part of the <a href="/tiers.php">ASCEND Protocol</a> | <a href="https://discord.gg/spgmrXVMWq">Join OD9 Discord</a></p>
    </div>
</div>

<script>
// Toggle URL section visibility
document.getElementById('publicToggle').addEventListener('change', function() {
    document.getElementById('urlSection').classList.toggle('hidden', !this.checked);
});

// Copy URL function
function copyUrl() {
    const urlInput = document.getElementById('profileUrl');
    const copyBtn = document.getElementById('copyBtn');
    
    navigator.clipboard.writeText(urlInput.value).then(() => {
        copyBtn.innerHTML = '<i class="fas fa-check"></i> Copied!';
        copyBtn.classList.add('copied');
        
        setTimeout(() => {
            copyBtn.innerHTML = '<i class="fas fa-copy"></i> Copy';
            copyBtn.classList.remove('copied');
        }, 2000);
    });
}
</script>

</body>
</html>
