-- 004_retire_dashboard_tables.sql
--
-- Retire the orphan "second sync" tables created by 001_dashboard_tables.sql.
-- They were never a live data source: the dashboard and profile.php read the bot
-- data directly, profile visibility moved to od9_profile_visibility (003), and
-- the writer (public/dashboard/api/sync.php) + its db.php helpers were removed in
-- the 2026-06 consolidation. Verified: no code references these tables anymore
-- (only this migrations/ dir), and the Discord-OAuth flow uses native PHP
-- sessions, not dashboard_sessions.
--
-- Idempotent: DROP IF EXISTS, FK children before the dashboard_users parent.

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS dashboard_progression;
DROP TABLE IF EXISTS dashboard_achievements;
DROP TABLE IF EXISTS dashboard_activity;
DROP TABLE IF EXISTS dashboard_sync_log;
DROP TABLE IF EXISTS dashboard_sessions;
DROP TABLE IF EXISTS dashboard_users;
SET FOREIGN_KEY_CHECKS = 1;
