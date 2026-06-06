<?php
/**
 * Profile visibility — web-owned single source of truth.
 *
 * Stored in MySQL `od9_profile_visibility` (see migrations/003_profile_visibility.sql).
 * The Discord bot has no profile_public logic (the SQLite column is vestigial),
 * so the website fully owns this flag — nothing to sync, nothing to diverge.
 *
 * Requires getDatabaseConnection() (config/database.php), loaded by the caller
 * (settings.php / profile.php both pull it in via includes/db.php). Default is
 * PRIVATE: a missing row or any read error means "not public".
 */

if (!function_exists('od9_is_profile_public')) {
    function od9_is_profile_public(string $discordId): bool
    {
        try {
            $db = getDatabaseConnection();
            $stmt = $db->prepare("SELECT is_public FROM od9_profile_visibility WHERE discord_id = ? LIMIT 1");
            $stmt->execute([$discordId]);
            return (bool) $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("[profile_visibility] read failed: " . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('od9_set_profile_public')) {
    function od9_set_profile_public(string $discordId, bool $isPublic): bool
    {
        try {
            $db = getDatabaseConnection();
            $stmt = $db->prepare(
                "INSERT INTO od9_profile_visibility (discord_id, is_public) VALUES (?, ?)
                 ON DUPLICATE KEY UPDATE is_public = VALUES(is_public)"
            );
            return $stmt->execute([$discordId, $isPublic ? 1 : 0]);
        } catch (PDOException $e) {
            error_log("[profile_visibility] write failed: " . $e->getMessage());
            return false;
        }
    }
}
