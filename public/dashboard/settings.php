<?php
/**
 * OD9 Dashboard Settings
 * 
 * User settings including public profile toggle.
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

$current_page = 'settings';

// Secure session
session_set_cookie_params([
    'lifetime' => 604800,
    'path' => '/',
    'secure' => strpos(__DIR__, 'xampp') === false,
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
        
        if (setPublicProfile($discordId, $publicProfile)) {
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

// Get current settings
$user = getPublicUserByDiscordId($discordId);
$isPublic = $user ? (bool)$user['public_profile'] : false;

// Build profile URL
$profileUrl = DASHBOARD_BASE_URL . '/profile.php?u=' . urlencode($discordId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Settings - OD9 Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@400;600;700&family=Exo+2:wght@400;600&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
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
    --success: #28a745;
    --error: #dc3545;
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
.nav-link:hover, .nav-link.active { color: var(--primary-blue); }
.nav-link:hover::after, .nav-link.active::after { width: 100%; }

/* Settings layout */
.settings-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 2rem;
}

.settings-header {
    text-align: center;
    margin-bottom: 2rem;
}

.settings-header h1 {
    font-family: 'Orbitron', sans-serif;
    font-size: 2rem;
    color: #fff;
    text-shadow: var(--glow);
    margin-bottom: 0.5rem;
}

.settings-header p {
    color: var(--chrome);
    font-size: 1rem;
}

/* Message alerts */
.alert {
    padding: 1rem 1.5rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.alert-success {
    background: rgba(40, 167, 69, 0.2);
    border: 1px solid var(--success);
    color: #90EE90;
}

.alert-error {
    background: rgba(220, 53, 69, 0.2);
    border: 1px solid var(--error);
    color: #FFB6C1;
}

/* Settings card */
.settings-card {
    background: linear-gradient(145deg, rgba(26,26,26,0.95), rgba(13,13,13,0.98));
    border: 1px solid rgba(0,191,255,0.3);
    border-radius: 12px;
    padding: 2rem;
    margin-bottom: 1.5rem;
}

.settings-card h2 {
    font-family: 'Orbitron', sans-serif;
    font-size: 1.25rem;
    color: var(--primary-blue);
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.settings-card p {
    color: var(--chrome);
    margin-bottom: 1.5rem;
    line-height: 1.6;
}

/* Toggle switch */
.toggle-wrapper {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem;
    background: rgba(0,0,0,0.3);
    border-radius: 8px;
    margin-bottom: 1.5rem;
}

.toggle-label {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.toggle-label strong {
    color: #fff;
    font-size: 1rem;
}

.toggle-label span {
    color: var(--chrome);
    font-size: 0.85rem;
}

.toggle-switch {
    position: relative;
    width: 60px;
    height: 32px;
}

.toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: #333;
    transition: 0.3s;
    border-radius: 32px;
    border: 2px solid #444;
}

.toggle-slider:before {
    position: absolute;
    content: "";
    height: 24px;
    width: 24px;
    left: 2px;
    bottom: 2px;
    background: #666;
    transition: 0.3s;
    border-radius: 50%;
}

input:checked + .toggle-slider {
    background: var(--primary-blue);
    border-color: var(--electric-blue);
}

input:checked + .toggle-slider:before {
    transform: translateX(28px);
    background: #fff;
}

/* Profile URL preview */
.profile-url-section {
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid rgba(255,255,255,0.1);
}

.profile-url-section.hidden {
    display: none;
}

.profile-url-section h3 {
    font-family: 'Rajdhani', sans-serif;
    font-size: 1rem;
    color: var(--chrome);
    margin-bottom: 0.75rem;
}

.url-box {
    display: flex;
    gap: 0.5rem;
}

.url-input {
    flex: 1;
    background: rgba(0,0,0,0.4);
    border: 1px solid rgba(0,191,255,0.3);
    border-radius: 6px;
    padding: 0.75rem 1rem;
    color: var(--primary-blue);
    font-family: monospace;
    font-size: 0.9rem;
}

.copy-btn {
    background: var(--primary-blue);
    color: var(--carbon);
    border: none;
    border-radius: 6px;
    padding: 0.75rem 1.25rem;
    cursor: pointer;
    font-family: 'Rajdhani', sans-serif;
    font-weight: 600;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.copy-btn:hover {
    background: var(--electric-blue);
    transform: translateY(-2px);
}

.copy-btn.copied {
    background: var(--success);
}

/* Save button */
.save-btn {
    background: linear-gradient(135deg, var(--primary-blue), var(--electric-blue));
    color: var(--carbon);
    border: none;
    border-radius: 8px;
    padding: 1rem 2rem;
    font-family: 'Rajdhani', sans-serif;
    font-weight: 700;
    font-size: 1.1rem;
    text-transform: uppercase;
    cursor: pointer;
    transition: all 0.3s;
    box-shadow: var(--glow);
    width: 100%;
}

.save-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 0 30px rgba(0,191,255,0.6);
}

/* Back link */
.back-link {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--chrome);
    text-decoration: none;
    margin-top: 2rem;
    font-family: 'Rajdhani', sans-serif;
    transition: color 0.3s;
}

.back-link:hover {
    color: var(--primary-blue);
}

/* Footer */
.settings-footer {
    text-align: center;
    margin-top: 3rem;
    padding-top: 2rem;
    border-top: 1px solid rgba(255,255,255,0.1);
    color: #666;
    font-size: 0.85rem;
}

.settings-footer a {
    color: var(--primary-blue);
    text-decoration: none;
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
            <li><a href="/dashboard/settings.php" class="nav-link active">Settings</a></li>
        </ul>
    </div>
</nav>

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
