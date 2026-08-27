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

/* ── World presence (SPEC §5, migration 007) ─────────────────────────────
 * Whether the member's token shows on the zone map: hidden | anon | visible.
 * Default (missing row, bad value, any error) is ALWAYS 'hidden' — presence
 * is consensual visibility, never tracking. */

const OD9_PRESENCE_MODES = ['hidden', 'anon', 'visible'];

if (!function_exists('od9_get_presence')) {
    function od9_get_presence(string $discordId): string
    {
        try {
            $db = getDatabaseConnection();
            $stmt = $db->prepare("SELECT presence FROM od9_profile_visibility WHERE discord_id = ? LIMIT 1");
            $stmt->execute([$discordId]);
            $v = (string) $stmt->fetchColumn();
            return in_array($v, OD9_PRESENCE_MODES, true) ? $v : 'hidden';
        } catch (PDOException $e) {
            error_log("[profile_visibility] presence read failed: " . $e->getMessage());
            return 'hidden';
        }
    }
}

if (!function_exists('od9_set_presence')) {
    function od9_set_presence(string $discordId, string $mode): bool
    {
        if (!in_array($mode, OD9_PRESENCE_MODES, true)) {
            return false;
        }
        try {
            $db = getDatabaseConnection();
            $stmt = $db->prepare(
                "INSERT INTO od9_profile_visibility (discord_id, presence) VALUES (?, ?)
                 ON DUPLICATE KEY UPDATE presence = VALUES(presence)"
            );
            return $stmt->execute([$discordId, $mode]);
        } catch (PDOException $e) {
            error_log("[profile_visibility] presence write failed: " . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('od9_presence_optins')) {
    /** Map of discord_id => 'anon'|'visible' for every member who opted in.
     *  Errors return [] — the map simply shows no one (fail-closed). */
    function od9_presence_optins(): array
    {
        try {
            $db = getDatabaseConnection();
            $out = [];
            foreach ($db->query("SELECT discord_id, presence FROM od9_profile_visibility WHERE presence IN ('anon','visible')") as $r) {
                $out[(string) $r['discord_id']] = (string) $r['presence'];
            }
            return $out;
        } catch (PDOException $e) {
            error_log("[profile_visibility] optin map failed: " . $e->getMessage());
            return [];
        }
    }
}
