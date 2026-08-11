<?php
/**
 * Discord OAuth Entry Point
 * OD9 ASCEND Dashboard
 * 
 * This initiates the Discord OAuth2 flow to link user accounts.
 */

// Load config
require_once __DIR__ . '/../includes/config.php';

// Discord OAuth Configuration
// Set these in includes/config.php when ready:
// putenv('DISCORD_CLIENT_ID=your_client_id');
// putenv('DISCORD_CLIENT_SECRET=your_client_secret');
// putenv('DISCORD_REDIRECT_URI=https://offda9.com/dashboard/auth/callback.php');

$clientId = getenv('DISCORD_CLIENT_ID');
$clientSecret = getenv('DISCORD_CLIENT_SECRET');
$redirectUri = getenv('DISCORD_REDIRECT_URI') ?: 'https://offda9.com/dashboard/auth/callback.php';

// Check if OAuth is configured
if (!$clientId || !$clientSecret) {
    // Show setup placeholder page
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Discord Login - Coming Soon | OD9</title>
        <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@400;600;700&display=swap" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
        <style>
            :root {
                --primary-blue: #00bfff;
                --deep-space: #0a0a0f;
                --chrome: #C0C0C0;
            }
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                background: var(--deep-space);
                color: var(--chrome);
                font-family: 'Rajdhani', sans-serif;
                min-height: 100vh;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                padding: 2rem;
            }
            .container {
                max-width: 500px;
                text-align: center;
                background: rgba(20, 20, 30, 0.9);
                border: 1px solid rgba(0, 191, 255, 0.3);
                border-radius: 12px;
                padding: 3rem;
            }
            h1 {
                font-family: 'Orbitron', monospace;
                color: var(--primary-blue);
                font-size: 1.8rem;
                margin-bottom: 1.5rem;
            }
            .icon {
                font-size: 4rem;
                color: #5865F2;
                margin-bottom: 1.5rem;
            }
            p {
                margin-bottom: 1rem;
                line-height: 1.6;
            }
            .cta-btn {
                display: inline-block;
                background: #5865F2;
                color: white;
                text-decoration: none;
                padding: 1rem 2rem;
                border-radius: 8px;
                font-weight: 600;
                font-size: 1.1rem;
                margin-top: 1.5rem;
                transition: all 0.3s;
            }
            .cta-btn:hover {
                background: #4752C4;
                transform: translateY(-2px);
            }
            .back-link {
                display: block;
                margin-top: 2rem;
                color: var(--primary-blue);
                text-decoration: none;
            }
            .back-link:hover { text-decoration: underline; }
        </style>
    </head>
    <body>
        <div class="container">
            <i class="fab fa-discord icon"></i>
            <h1>Discord Login Coming Soon</h1>
            <p>We're setting up Discord account linking for the Progression Dashboard.</p>
            <p>In the meantime, join OD9 on Discord to start earning credits and climbing the tiers!</p>
            <a href="https://discord.gg/spgmrXVMWq" target="_blank" class="cta-btn">
                <i class="fab fa-discord"></i> Join OD9 Discord
            </a>
            <a href="/dashboard/" class="back-link">← Back to Dashboard</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// OAuth is configured - build authorization URL
require_once __DIR__ . '/../includes/auth.php';
od9_dashboard_boot();

// Flow-START diagnostic (2026-07-18, _souled0ut can't connect): the callback
// diag shows he NEVER reaches callback.php — this line shows whether he even
// reaches the start, and from which browser. Pair with [oauth-dbg] in callback.
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
error_log(sprintf('[oauth-dbg] start ua=%s', substr($ua, 0, 80)));

// In-app browsers (Discord-Android WebView, Facebook, Instagram, generic wv)
// break the OAuth round-trip: the flow starts in the embedded browser but the
// return lands where the session cookie doesn't exist. Detect and stop BEFORE
// starting a doomed flow — tell the member how to open it for real.
if (preg_match('/(Discord-Android|FBAN|FBAV|Instagram|; wv\))/i', $ua)) {
    ?>
    <!DOCTYPE html>
    <html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Open in your browser - OD9</title>
    <style>body{background:#0a0a12;color:#e8e8ec;font-family:system-ui,sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;padding:1.5rem;text-align:center}.card{max-width:420px;background:#12121c;border:1px solid rgba(0,255,247,.25);border-radius:12px;padding:2rem}.card h1{font-size:1.15rem;color:#00FFF7;margin:0 0 .8rem}.card p{line-height:1.6;margin:.6rem 0;color:#c4c7cb}.card b{color:#fff}</style>
    </head><body><div class="card">
    <h1>&#128241; One quick step</h1>
    <p>You're inside an app's built-in browser &mdash; Discord login can't complete here.</p>
    <p><b>Tap the &bull;&bull;&bull; menu</b> (top corner) and choose <b>"Open in Browser"</b> or <b>"Open in Safari"</b>, then hit the login button again.</p>
    <p style="font-size:.85rem;color:#7c8a96">offda9.com/dashboard</p>
    </div></body></html>
    <?php
    exit;
}

// Generate state token for CSRF protection
$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state;

// Scope: identify ONLY. Membership is resolved against our own bot DB
// (od9_bot_users), never Discord's guilds API, so guilds/guilds.members.read were
// unused consent surface — and an extra failure point on a fresh authorize.
// Minimal scope = the simplest possible "OD9 wants your username" prompt.
$scopes = 'identify';

$authUrl = 'https://discord.com/api/oauth2/authorize?' . http_build_query([
    'client_id' => $clientId,
    'redirect_uri' => $redirectUri,
    'response_type' => 'code',
    'scope' => $scopes,
    'state' => $state,
    'prompt' => 'consent'
]);

// Redirect to Discord
header('Location: ' . $authUrl);
exit;
