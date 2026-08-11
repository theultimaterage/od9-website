<?php
/**
 * OD9 — Spotify catalog fetcher. Mirror of api/soundcloud/fetch.php. CLI-only.
 *
 * Pulls The Ultimate Rage's released Spotify catalog into the offda9 MySQL
 * `music_catalog` as platform='spotify' rows, using a cached client_credentials
 * token. Run via cPanel cron (CLI), e.g. daily:
 *   php /home/offda9/public_html/api/spotify/fetch.php
 *
 * Spec: Spotify Web API — Client Credentials flow + GET /artists/{id}/albums +
 * GET /albums/{id}/tracks (artwork comes from the album's images). Output: a
 * one-line summary. NEVER prints creds or the token.
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(403); exit("CLI only\n"); }

// DB config — OUTER first (live password, above webroot), then inner repo copy.
$__cfg = null;
foreach ([__DIR__ . '/../../../config/database.php', __DIR__ . '/../../config/database.php'] as $__c) {
    if (is_file($__c)) { $__cfg = $__c; break; }
}
if ($__cfg === null) { fwrite(STDERR, "ERROR: db config not found\n"); exit(1); }
require $__cfg;                                                 // getDatabaseConnection()
$secrets = __DIR__ . '/../../dashboard/includes/secrets.config.php';
if (is_file($secrets)) { require $secrets; }                   // putenv SPOTIFY_*

$CID  = getenv('SPOTIFY_CLIENT_ID');
$CSEC = getenv('SPOTIFY_CLIENT_SECRET');
if (!$CID || !$CSEC) { fwrite(STDERR, "ERROR: Spotify creds not configured (SPOTIFY_CLIENT_ID/SECRET)\n"); exit(1); }

// The Ultimate Rage's Spotify artist ID — set once (open.spotify.com/artist/<ID>).
const SP_ARTIST_ID = '0QvH8H7obaMerk1UkfFGaD';  // The Ultimate Rage
const SP_TOKEN_URL = 'https://accounts.spotify.com/api/token';
const SP_API       = 'https://api.spotify.com/v1';
const SP_MARKET    = 'US';

if (SP_ARTIST_ID === 'REPLACE_WITH_ARTIST_ID') {
    fwrite(STDERR, "ERROR: set SP_ARTIST_ID (the artist's Spotify ID) before running\n"); exit(1);
}

function sp_http(string $url, array $headers, ?string $post = null): array {
    $ch = curl_init($url);
    $opt = [CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 25, CURLOPT_CONNECTTIMEOUT => 10];
    if ($post !== null) { $opt[CURLOPT_POST] = true; $opt[CURLOPT_POSTFIELDS] = $post; }
    curl_setopt_array($ch, $opt);
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$code, json_decode((string) $body, true)];
}

try { $db = getDatabaseConnection(); }
catch (Throwable $e) { fwrite(STDERR, "ERROR: DB connect failed\n"); exit(1); }

// ── schema (idempotent; same tables as the SC fetcher) ───────────────────────
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

// ── cached client_credentials token (~1h) ────────────────────────────────────
function sp_token(PDO $db, string $cid, string $csec): string {
    $row = $db->query("SELECT access_token, expires_at FROM music_api_token WHERE platform='spotify'")->fetch();
    if ($row && strtotime($row['expires_at']) > time() + 60) { return $row['access_token']; }
    [$code, $d] = sp_http(SP_TOKEN_URL,
        ['Authorization: Basic ' . base64_encode($cid . ':' . $csec),
         'Content-Type: application/x-www-form-urlencoded', 'Accept: application/json'],
        'grant_type=client_credentials');
    if ($code !== 200 || empty($d['access_token'])) { throw new RuntimeException("token http=$code"); }
    $st = $db->prepare("INSERT INTO music_api_token (platform, access_token, expires_at, obtained_at)
        VALUES ('spotify', ?, ?, ?)
        ON DUPLICATE KEY UPDATE access_token=VALUES(access_token), expires_at=VALUES(expires_at), obtained_at=VALUES(obtained_at)");
    $st->execute([$d['access_token'], date('Y-m-d H:i:s', time() + (int)($d['expires_in'] ?? 3600)), date('Y-m-d H:i:s')]);
    return $d['access_token'];
}

try { $token = sp_token($db, $CID, $CSEC); }
catch (Throwable $e) { error_log('[sp-fetch] ' . $e->getMessage()); fwrite(STDERR, "ERROR: token exchange failed (" . $e->getMessage() . ")\n"); exit(1); }
$AUTH = ['Authorization: Bearer ' . $token, 'Accept: application/json'];

// ── 1) the artist's albums + singles (paginated; albums limit maxes at 10) ────
$albums = []; $url = SP_API . '/artists/' . SP_ARTIST_ID . '/albums?include_groups=album,single&market=' . SP_MARKET . '&limit=10'; $pages = 0;
while ($url && $pages < 20) {
    [$code, $d] = sp_http($url, $AUTH);
    if ($code !== 200 || !is_array($d)) { error_log("[sp-fetch] albums http=$code"); break; }
    foreach (($d['items'] ?? []) as $a) { if (!empty($a['id'])) { $albums[$a['id']] = $a; } }  // dedupe by album id
    $url = $d['next'] ?? null;
    $pages++;
}
if (!$albums) { fwrite(STDERR, "ERROR: no albums for artist " . SP_ARTIST_ID . " (check the ID / market)\n"); exit(1); }

// ── 2) each album's tracks -> upsert (artwork from the album's images) ────────
$now = date('Y-m-d H:i:s'); $new = 0; $upd = 0; $seen = 0;
$exists = $db->prepare("SELECT 1 FROM music_catalog WHERE platform='spotify' AND track_id=?");
$up = $db->prepare("INSERT INTO music_catalog
  (platform, track_id, title, artist, permalink_url, artwork_url, duration_ms, description, genre, is_public, src_created_at, first_seen_at, last_synced_at)
  VALUES ('spotify', ?,?,?,?,?,?,?,?,?,?,?,?)
  ON DUPLICATE KEY UPDATE title=VALUES(title), artist=VALUES(artist), permalink_url=VALUES(permalink_url),
    artwork_url=VALUES(artwork_url), duration_ms=VALUES(duration_ms), is_public=VALUES(is_public),
    src_created_at=VALUES(src_created_at), last_synced_at=VALUES(last_synced_at)");
foreach ($albums as $aid => $a) {
    $art = $a['images'][0]['url'] ?? null;
    $rel = !empty($a['release_date']) ? date('Y-m-d H:i:s', strtotime($a['release_date'])) : null;
    $turl = SP_API . '/albums/' . $aid . '/tracks?market=' . SP_MARKET . '&limit=50'; $tp = 0;
    while ($turl && $tp < 10) {
        [$tcode, $td] = sp_http($turl, $AUTH);
        if ($tcode !== 200 || !is_array($td)) { error_log("[sp-fetch] tracks http=$tcode album=$aid"); break; }
        foreach (($td['items'] ?? []) as $t) {
            if (empty($t['id'])) { continue; }
            $seen++;
            $tid = (string) $t['id'];
            $exists->execute([$tid]); $isNew = !$exists->fetchColumn();
            $artist = $t['artists'][0]['name'] ?? null;
            $perma  = $t['external_urls']['spotify'] ?? null;
            $up->execute([$tid, $t['name'] ?? null, $artist, $perma, $art,
                isset($t['duration_ms']) ? (int) $t['duration_ms'] : null, null, null, 1, $rel, $now, $now]);
            $isNew ? $new++ : $upd++;
        }
        $turl = $td['next'] ?? null;
        $tp++;
    }
}

printf("OK spotify: %d album(s), %d track(s) (%d new, %d updated); token cached.\n", count($albums), $seen, $new, $upd);
