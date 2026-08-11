<?php
/**
 * Template for public/dashboard/includes/secrets.config.php (gitignored).
 * Copy to secrets.config.php and fill real values; mode 600 on prod.
 */
putenv('DISCORD_CLIENT_SECRET=your_discord_client_secret');
putenv('OD9_WEB_SYNC_SECRET=generate_a_long_random_string');
// Phase C: shared HMAC-SHA256 secret for the board's member-action write-path
// (board-action.php -> bot POST /member/action). MUST match the bot's .env
// MEMBER_ACTION_WEBHOOK_SECRET. Generate: python -c "import secrets; print(secrets.token_hex(32))"
putenv('MEMBER_ACTION_WEBHOOK_SECRET=match_the_bot_dot_env_value');
// SoundCloud API (Artist Pro) — SERVER-SIDE / WEBSITE use only, to surface TUR's
// own catalog on offda9.com (allowed: promote/deliver your own content to your own
// site). NOT used by the Discord bot — that use is explicitly prohibited by the SC
// API ToS. App registered at developers.soundcloud.com; redirect URI (if OAuth):
// https://offda9.com/api/soundcloud/callback.php
putenv('SOUNDCLOUD_CLIENT_ID=your_soundcloud_client_id');
putenv('SOUNDCLOUD_CLIENT_SECRET=your_soundcloud_client_secret');
// Spotify Web API (client_credentials) — official releases surfaced on offda9.com
// (same website-side/embed shape as SoundCloud). App: Web API scope only.
putenv('SPOTIFY_CLIENT_ID=your_spotify_client_id');
putenv('SPOTIFY_CLIENT_SECRET=your_spotify_client_secret');
