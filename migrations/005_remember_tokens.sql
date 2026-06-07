-- 005_remember_tokens.sql
--
-- Persistent "remember me" login for /dashboard (task #7). Lets a returning OD9
-- member stay logged in for up to 30 days (sliding) without re-running Discord
-- OAuth, by re-establishing the PHP session from a secure split-token cookie
-- after the server-side session has been garbage-collected.
--
-- Web-owned, same MySQL DB as od9_profile_visibility (003). Reached via
-- getDatabaseConnection(). See docs/persistent-login-spec.md.
--
-- Apply MANUALLY (no runner): local XAMPP `od9_tickets` and prod
-- `offda9_od9_tickets`. Idempotent (CREATE TABLE IF NOT EXISTS).
--
-- Security model (full analysis in the spec):
--   * cookie = "<selector>:<validator>"; only sha256(validator) is stored, so a
--     DB read leak yields no usable cookie.
--   * selector is the indexed lookup key (not secret) -> O(1), no timing leak;
--     validator compared with hash_equals (constant time).
--   * rotated on each use; a selector-hit with a wrong validator = theft -> all
--     of that user's tokens are deleted.

CREATE TABLE IF NOT EXISTS od9_remember_tokens (
    selector       CHAR(24)     NOT NULL PRIMARY KEY,   -- bin2hex(random_bytes(12))
    validator_hash CHAR(64)     NOT NULL,               -- hash('sha256', validator)
    discord_id     VARCHAR(20)  NOT NULL,
    expires_at     DATETIME     NOT NULL,
    created_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_used_at   DATETIME     NULL,
    user_agent     VARCHAR(255) NULL,                   -- audit only, NOT enforced
    INDEX idx_discord (discord_id),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
