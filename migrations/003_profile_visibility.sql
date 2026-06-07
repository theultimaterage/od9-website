-- 003_profile_visibility.sql
--
-- Web-owned profile visibility for /dashboard/profile.php.
--
-- The Discord bot has NO profile_public logic — `user_settings.profile_public`
-- exists only as a vestigial column declaration (db/core.py) and is never read
-- or written by the bot. So the website is the single source of truth for
-- whether a member's public progression page is visible; there is nothing on
-- the bot side to diverge from or sync with.
--
-- This table is web-owned and intentionally OUTSIDE the dashboard_* family, so
-- it survives the Phase 3 retirement of the orphan dashboard_* sync tables.
-- Default is_public = 0 (private / opt-in): a member must explicitly enable
-- sharing from /dashboard/settings.php.

CREATE TABLE IF NOT EXISTS od9_profile_visibility (
    discord_id  VARCHAR(20) NOT NULL PRIMARY KEY,
    is_public   TINYINT(1)  NOT NULL DEFAULT 0,
    updated_at  TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
