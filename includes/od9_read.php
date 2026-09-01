<?php
declare(strict_types=1);
/**
 * od9_read.php — read the bot's member data by QUERY NAME, over either transport.
 *
 * WHY
 * ---
 * od9_sqlite.php opens /home/ultimaterage/od9-discord-bot/data/od9.db by absolute
 * LOCAL path. That is fast and live, and it is also the single thing welding this
 * website to the bot's filesystem: neither can move hosts without the other.
 *
 * This file removes the weld without changing what members see. Callers ask for a
 * NAMED query (never SQL) and this picks the transport:
 *
 *   local   - open the SQLite file directly, exactly as today (default)
 *   remote  - POST the query name to the bot's /read endpoint, HMAC-signed
 *
 * Same answers, same freshness, and the migration becomes one env var rather than
 * a rewrite of eleven consumers. The SQL itself lives in ONE place both languages
 * read: config/read_api_queries.json in the bot repo.
 *
 * A 15-minute MySQL mirror was the previous decoupling attempt and was retired
 * 2026-06-28 because stale reads caused tier-gating bugs. This does not cache.
 *
 * FAIL-SAFE: every failure returns null, which is what the existing gates already
 * treat as "deny / unavailable". Never throws, never returns stale data.
 *
 * Env:
 *   OD9_READ_API_URL     set -> remote transport (e.g. https://bot.internal/read)
 *   OD9_READ_API_SECRET  shared secret, must match the bot's READ_API_SECRET
 *   OD9_BOT_DIR          override the bot directory for local transport
 */

/**
 * Load the transport config ourselves if the caller has not.
 *
 * OD9_READ_API_URL / _SECRET live in dashboard/includes/secrets.config.php, and
 * the DASHBOARD bootstrap loads that file — but the public pages
 * (funding/events/roadmap/progress/me) bootstrap through config/database.php and
 * never see it. So setting the URL flipped only half the site to the remote
 * transport while the other half silently stayed local, with no error anywhere:
 * both transports return correct data, so the split was invisible until the
 * access log showed public page loads producing ZERO requests (2026-09-01).
 *
 * A consumer should not have to know which bootstrap it arrived through to get
 * the right transport, so this file resolves its own config. require_once is a
 * no-op when the dashboard already loaded it, and the file only calls putenv().
 */
if ((getenv('OD9_READ_API_URL') ?: '') === '') {
    foreach ([
        __DIR__ . '/../dashboard/includes/secrets.config.php',
        __DIR__ . '/../../dashboard/includes/secrets.config.php',
    ] as $_od9_secrets) {
        if (is_file($_od9_secrets) && is_readable($_od9_secrets)) {
            @require_once $_od9_secrets;
            break;
        }
    }
    unset($_od9_secrets);
}

if (!defined('OD9_BOT_DIR')) {
    define('OD9_BOT_DIR', getenv('OD9_BOT_DIR') ?: '/home/ultimaterage/od9-discord-bot');
}
if (!defined('OD9_READ_REGISTRY')) {
    define('OD9_READ_REGISTRY', OD9_BOT_DIR . '/config/read_api_queries.json');
}
if (!defined('OD9_READ_DB')) {
    define('OD9_READ_DB', OD9_BOT_DIR . '/data/od9.db');
}

/** Remote when a URL is configured; otherwise read the file next door. */
function od9_read_transport(): string {
    return (getenv('OD9_READ_API_URL') ?: '') !== '' ? 'remote' : 'local';
}

/**
 * The shared query registry, or null if it is unreadable.
 * Only the LOCAL transport needs this — in remote mode the bot owns the SQL.
 */
function od9_read_registry(): ?array {
    static $reg = null;
    static $tried = false;
    if ($tried) {
        return $reg;
    }
    $tried = true;
    if (!is_file(OD9_READ_REGISTRY)) {
        error_log('[od9_read] registry missing: ' . OD9_READ_REGISTRY);
        return $reg = null;
    }
    $raw = json_decode((string) file_get_contents(OD9_READ_REGISTRY), true);
    if (!is_array($raw) || !isset($raw['queries']) || !is_array($raw['queries'])) {
        error_log('[od9_read] registry malformed');
        return $reg = null;
    }
    return $reg = $raw['queries'];
}

/**
 * Run a registered query. Returns an assoc row, a list of rows, or null.
 *
 *   od9_read('member_tier', ['user_id' => $discordId])   -> ['current_tier' => 'pioneer']
 *
 * A missing record is null, same as an outage — callers already fail safe on null,
 * and a gate that cannot confirm membership must deny either way.
 */
function od9_read(string $query, array $params = []) {
    try {
        return od9_read_transport() === 'remote'
            ? od9_read_remote($query, $params)
            : od9_read_local($query, $params);
    } catch (Throwable $e) {
        error_log('[od9_read] ' . $query . ' failed: ' . $e->getMessage());
        return null;
    }
}

/** Local transport: the registry's SQL against the bot's SQLite, read-only. */
function od9_read_local(string $query, array $params) {
    $reg = od9_read_registry();
    if ($reg === null || !isset($reg[$query])) {
        error_log('[od9_read] unknown query: ' . $query);
        return null;
    }
    $spec = $reg[$query];
    $declared = $spec['params'] ?? [];
    sort($declared);
    $given = array_keys($params);
    sort($given);
    if ($declared !== $given) {
        error_log('[od9_read] param mismatch for ' . $query);
        return null;
    }
    if (!is_file(OD9_READ_DB)) {
        return null;
    }
    // mode=ro at the driver level: this connection cannot write even by mistake.
    $pdo = new PDO('sqlite:' . OD9_READ_DB, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 5,   // matches the bot's busy_timeout
    ]);
    $stmt = $pdo->prepare($spec['sql']);
    foreach ($params as $k => $v) {
        $stmt->bindValue(':' . $k, $v);
    }
    $stmt->execute();
    if (($spec['returns'] ?? 'row') === 'row') {
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }
    return $stmt->fetchAll();
}

/**
 * Per-request circuit breaker for the remote transport.
 *
 * A DOWN bot is cheap: connect is refused instantly. A HUNG bot is not — each
 * call burns the full CURLOPT_TIMEOUT. roadmap.php issues six reads, so a wedged
 * bot would cost 6 x 5s = 30s, which is PHP's default max_execution_time: the
 * page dies with a 500 instead of degrading to the empty-list fallbacks every
 * caller already has.
 *
 * So the first TRANSPORT failure (curl error / timeout / 5xx) marks the backend
 * dead for the remainder of THIS request and later reads return null
 * immediately. Worst case becomes one timeout, not one per read.
 *
 * Deliberately NOT tripped by a 4xx: "unknown query" or "bad params" is a fault
 * in one call, not evidence the backend is gone, and letting it disable every
 * other read would turn a single typo into a blank page.
 */
function od9_read_remote_dead(?bool $set = null): bool {
    static $dead = false;
    if ($set !== null) { $dead = $set; }
    return $dead;
}

/** Remote transport: HMAC-signed POST to the bot. No SQL crosses the wire. */
function od9_read_remote(string $query, array $params) {
    $url = getenv('OD9_READ_API_URL') ?: '';
    $secret = getenv('OD9_READ_API_SECRET') ?: '';
    if ($url === '' || $secret === '') {
        error_log('[od9_read] remote transport not fully configured');
        return null;
    }
    if (od9_read_remote_dead()) {
        return null;   // breaker open — a prior call this request proved it unreachable
    }
    $body = json_encode(['query' => $query, 'params' => (object) $params],
                        JSON_UNESCAPED_SLASHES);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,             // a member page must not hang on this
        CURLOPT_CONNECTTIMEOUT => 2,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'X-OD9-Signature: ' . hash_hmac('sha256', $body, $secret),
        ],
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($resp === false || $code !== 200) {
        error_log('[od9_read] remote ' . $query . ' http=' . $code . ' ' . $err);
        // Transport-level failure (no connection, timeout, or the bot 5xx'd) =>
        // open the breaker so the rest of this request degrades instantly.
        // A 4xx is this query's problem alone and leaves the breaker closed.
        if ($resp === false || $code === 0 || $code >= 500) {
            od9_read_remote_dead(true);
            error_log('[od9_read] remote transport marked DEAD for this request'
                      . ' after ' . $query . ' (http=' . $code . ')');
        }
        return null;
    }
    $decoded = json_decode((string) $resp, true);
    if (!is_array($decoded) || empty($decoded['ok'])) {
        error_log('[od9_read] remote ' . $query . ' rejected: '
                  . (is_array($decoded) ? ($decoded['error'] ?? '?') : 'bad json'));
        return null;
    }
    return $decoded['data'];
}

/**
 * Is the read path working at all? For /health surfaces and the deploy smoke
 * test — a read layer that silently returns null for everything looks exactly
 * like a member with no data, which is the failure this repo keeps re-learning.
 */
function od9_read_healthy(): bool {
    // Deliberately NOT a member lookup: that returns null both for "no such
    // member" and "read layer is dead", so it could never prove the path works.
    // health_probe returns a row whenever the database is readable at all.
    $probe = od9_read('health_probe');
    return is_array($probe) && array_key_exists('member_count', $probe);
}
