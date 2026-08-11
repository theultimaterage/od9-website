<?php
/**
 * OD9 — SoundCloud catalog fetcher (Slice 1).
 *
 * Website-side ONLY (ToS-compliant: own content / own site, metadata + embed/link,
 * NEVER the Discord bot). Pulls The Ultimate Rage's released catalog into the offda9
 * MySQL `music_catalog`, using a cached client_credentials token. Run via cPanel cron
 * (CLI), e.g. daily:  php /home/offda9/public_html/api/soundcloud/fetch.php
 *
 * Spec: docs/OD9_SOUNDCLOUD_INTEGRATION_SPEC.md
 * Output: a one-line summary (counts). NEVER prints creds or the token.
 */
declare(strict_types=1);

// CLI-only — no public web trigger (avoids strangers burning the 50-token/12h quota).
if (PHP_SAPI !== 'cli') { http_response_code(403); exit("CLI only\n"); }

// DB config — prefer the OUTER config (above webroot, holds the live password),
// fall back to the inner repo copy, exactly as patron_gate.php resolves it.
$__cfg = null;
foreach ([__DIR__ . '/../../../config/database.php', __DIR__ . '/../../config/database.php'] as $__c) {
    if (is_file($__c)) { $__cfg = $__c; break; }
}
if ($__cfg === null) { fwrite(STDERR, "ERROR: db config not found\n"); exit(1); }
require $__cfg;                                                 // getDatabaseConnection() + env
$secrets = __DIR__ . '/../../dashboard/includes/secrets.config.php';
if (is_file($secrets)) { require $secrets; }                   // putenv SOUNDCLOUD_*

$CID  = getenv('SOUNDCLOUD_CLIENT_ID');
$CSEC = getenv('SOUNDCLOUD_CLIENT_SECRET');
if (!$CID || !$CSEC) { fwrite(STDERR, "ERROR: SoundCloud creds not configured\n"); exit(1); }

const SC_TOKEN_URL = 'https://secure.soundcloud.com/oauth/token';
const SC_API       = 'https://api.soundcloud.com';
const SC_PROFILE_URL = 'https://soundcloud.com/theultimaterage';  // catalog source; /resolve -> user id

function sc_http(string $url, array $headers, ?string $post = null): array {
    $ch = curl_init($url);
    $opt = [CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 25, CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true, CURLOPT_MAXREDIRS => 3];  // /resolve 302s to the API resource
    if ($post !== null) { $opt[CURLOPT_POST] = true; $opt[CURLOPT_POSTFIELDS] = $post; }
    curl_setopt_array($ch, $opt);
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$code, json_decode((string) $body, true)];
}

try { $db = getDatabaseConnection(); }
catch (Throwable $e) { fwrite(STDERR, "ERROR: DB connect failed\n"); exit(1); }

// ── schema (idempotent) ─────────────────────────────────────────────────────
$db->exec("CREATE TABLE IF NOT EXISTS music_api_token (
  platform VARCHAR(20) PRIMARY KEY,
  access_token TEXT NOT NULL, expires_at DATETIME NOT NULL, obtained_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$db->exec("CREATE TABLE IF NOT EXISTS music_catalog (
  platform VARCHAR(20) NOT NULL, track_id VARCHAR(64) NOT NULL,
  title VARCHAR(512) NULL, artist VARCHAR(255) NULL,
  permalink_url TEXT NULL, artwork_url TEXT NULL,
  duration_ms INT NULL, description MEDIUMTEXT NULL, genre VARCHAR(128) NULL,
  is_public TINYINT(1) DEFAULT 1, src_created_at DATETIME NULL,
  first_seen_at DATETIME NOT NULL, last_synced_at DATETIME NOT NULL,
  PRIMARY KEY (platform, track_id), KEY idx_first_seen (platform, first_seen_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// ── cached client_credentials token (~1h; only 50 tokens/12h, so cache hard) ──
function sc_token(PDO $db, string $cid, string $csec): string {
    $row = $db->query("SELECT access_token, expires_at FROM music_api_token WHERE platform='soundcloud'")->fetch();
    if ($row && strtotime($row['expires_at']) > time() + 60) { return $row['access_token']; }
    [$code, $d] = sc_http(SC_TOKEN_URL,
        ['Authorization: Basic ' . base64_encode($cid . ':' . $csec),
         'Content-Type: application/x-www-form-urlencoded', 'Accept: application/json'],
        'grant_type=client_credentials');
    if ($code !== 200 || empty($d['access_token'])) { throw new RuntimeException("token http=$code"); }
    $st = $db->prepare("INSERT INTO music_api_token (platform, access_token, expires_at, obtained_at)
        VALUES ('soundcloud', ?, ?, ?)
        ON DUPLICATE KEY UPDATE access_token=VALUES(access_token), expires_at=VALUES(expires_at), obtained_at=VALUES(obtained_at)");
    $st->execute([$d['access_token'], date('Y-m-d H:i:s', time() + (int)($d['expires_in'] ?? 3600)), date('Y-m-d H:i:s')]);
    return $d['access_token'];
}

try { $token = sc_token($db, $CID, $CSEC); }
catch (Throwable $e) { error_log('[sc-fetch] ' . $e->getMessage()); fwrite(STDERR, "ERROR: token exchange failed (" . $e->getMessage() . ")\n"); exit(1); }

$AUTH = ['Authorization: OAuth ' . $token, 'Accept: application/json'];

// client_credentials is app-only (no user) so /me 401s; resolve the public profile -> user id.
$resolve = SC_API . '/resolve?url=' . urlencode(SC_PROFILE_URL);
[$code, $u] = sc_http($resolve, $AUTH);
if ($code !== 200 || empty($u['id'])) { fwrite(STDERR, "ERROR: resolve http=$code (url=" . SC_PROFILE_URL . ")\n"); exit(1); }
$uid = $u['id'];

// ── pull the catalog (paginated) ─────────────────────────────────────────────
$tracks = []; $url = SC_API . "/users/$uid/tracks?limit=200&linked_partitioning=true"; $pages = 0;
while ($url && $pages < 25) {
    [$code, $d] = sc_http($url, $AUTH);
    if ($code !== 200 || !is_array($d)) { error_log("[sc-fetch] tracks http=$code"); break; }
    foreach (($d['collection'] ?? []) as $t) { if (!empty($t['id'])) { $tracks[] = $t; } }
    $url = $d['next_href'] ?? null;
    $pages++;
}

// ── upsert (first_seen_at preserved on update → drives slice-4 new-release notify) ──
$now = date('Y-m-d H:i:s'); $new = 0; $upd = 0;
$exists = $db->prepare("SELECT 1 FROM music_catalog WHERE platform='soundcloud' AND track_id=?");
$up = $db->prepare("INSERT INTO music_catalog
  (platform, track_id, title, artist, permalink_url, artwork_url, duration_ms, description, genre, is_public, src_created_at, first_seen_at, last_synced_at)
  VALUES ('soundcloud', ?,?,?,?,?,?,?,?,?,?,?,?)
  ON DUPLICATE KEY UPDATE title=VALUES(title), permalink_url=VALUES(permalink_url), artwork_url=VALUES(artwork_url),
    duration_ms=VALUES(duration_ms), description=VALUES(description), genre=VALUES(genre),
    is_public=VALUES(is_public), src_created_at=VALUES(src_created_at), last_synced_at=VALUES(last_synced_at)");
foreach ($tracks as $t) {
    $tid = (string) $t['id'];
    $exists->execute([$tid]); $isNew = !$exists->fetchColumn();
    $created = !empty($t['created_at']) ? date('Y-m-d H:i:s', strtotime($t['created_at'])) : null;
    $up->execute([$tid, $t['title'] ?? null, $t['user']['username'] ?? null, $t['permalink_url'] ?? null,
        $t['artwork_url'] ?? null, isset($t['duration']) ? (int) $t['duration'] : null,
        $t['description'] ?? null, $t['genre'] ?? null, (($t['sharing'] ?? 'public') !== 'private') ? 1 : 0, $created, $now, $now]);
    $isNew ? $new++ : $upd++;
}

printf("OK soundcloud: %d tracks (%d new, %d updated) over %d page(s); token cached.\n", count($tracks), $new, $upd, $pages);
