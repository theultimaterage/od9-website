<?php
/**
 * Integration test for the dashboard remember-me token lifecycle
 * (public/dashboard/includes/auth.php). Standalone — no PHPUnit needed.
 *
 *   php tests/test_remember_auth.php        (run from repo root, local XAMPP)
 *
 * Exercises: issue, consume+rotate, theft purge, membership re-check, logout.
 * Uses a real member + a synthetic non-member. Cleans up after itself.
 * setcookie/session warnings in CLI are expected and suppressed; the assertions
 * are on DB rows + $_COOKIE + $_SESSION state.
 *
 * MEMBERSHIP COMES FROM THE BOT, NOT MySQL (2026-10-08). The consume path asks the
 * read seam (includes/od9_read.php, query member_exists) whether the token's owner
 * is still a member; the seam reads the bot's SQLite (OD9_BOT_DIR/data/od9.db, the
 * live file on the box). This test once took "a member" from MySQL's od9_members,
 * which the seam never consults, so on a workstation with no bot checkout at the
 * Linux path the seam answered null, the consume path treated the member as gone,
 * purged the token and cleared the cookie, and three assertions failed for a
 * harness reason. The member is now taken FROM the seam's own database, and a
 * seam that cannot answer is a loud failure here, never a silent pass:
 *
 *   OD9_BOT_DIR=C:/Users/Rage/IdeaProjects/OD9-Discord-Bot php tests/test_remember_auth.php
 */
error_reporting(E_ERROR | E_PARSE);
chdir(dirname(__DIR__));
$_SERVER['HTTP_USER_AGENT'] = 'OD9-RememberTest';

require 'config/database.php';
require 'includes/env.php';            // the public/ -> root restructure moved these; the old paths threw
require 'dashboard/includes/auth.php';

$pdo = getDatabaseConnection();

// The member comes from the seam's own database: the same `users` table the
// member_exists query reads. A seam that cannot answer fails this test out loud.
require 'includes/od9_read.php';
if (od9_read_transport() !== 'local' || !is_file(OD9_READ_DB) || !is_file(OD9_READ_REGISTRY)) {
    fwrite(STDERR, "FAIL: the membership seam is not reachable here (transport " . od9_read_transport()
        . "; db " . OD9_READ_DB . "; registry " . OD9_READ_REGISTRY . ").\n"
        . "      Point OD9_BOT_DIR at a bot checkout that holds data/od9.db and config/read_api_queries.json.\n");
    exit(1);
}
$bot    = new PDO('sqlite:' . OD9_READ_DB, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$member = $bot->query('SELECT user_id FROM users LIMIT 1')->fetchColumn();
if (!$member) {
    fwrite(STDERR, "FAIL: the seam's users table is empty at " . OD9_READ_DB . "; nothing to test membership with\n");
    exit(1);
}
$member = (string) $member;
if (od9_read('member_exists', ['user_id' => $member]) === null) {
    fwrite(STDERR, "FAIL: the seam does not confirm its own first user as a member; the registry or the db is wrong\n");
    exit(1);
}
$nonmember = '100000000000000001'; // synthetic; must not exist in od9_members

$pass = 0; $fail = 0;
function ok(bool $c, string $m): void { global $pass, $fail; echo ($c ? "  PASS " : "  FAIL ") . $m . "\n"; $c ? $pass++ : $fail++; }
function reset_cookie(): void { unset($_COOKIE['od9_remember']); }

$pdo->prepare('DELETE FROM od9_remember_tokens WHERE discord_id IN (?, ?)')->execute([$member, $nonmember]);
@session_start();

echo "== 1. issue ==\n";
$_SESSION = [];
od9_issue_remember_token($member);
$cookie = $_COOKIE['od9_remember'] ?? '';
ok(strpos($cookie, ':') !== false, 'cookie set as selector:validator');
[$sel, $val] = explode(':', $cookie, 2) + ['', ''];
$row = $pdo->query("SELECT * FROM od9_remember_tokens WHERE discord_id='$member'")->fetch(PDO::FETCH_ASSOC);
ok($row && $row['selector'] === $sel, 'DB row exists with the cookie selector');
ok($row && $row['validator_hash'] === hash('sha256', $val), 'validator stored as sha256 hash');
ok($row && $row['validator_hash'] !== $val, 'raw validator is NOT stored');

echo "== 2. consume (no session) -> establishes session + rotates ==\n";
$_SESSION = [];
od9_try_remember_login();
ok(($_SESSION['discord_id'] ?? null) === $member, 'session established from cookie');
$rotated = $_COOKIE['od9_remember'] ?? '';
ok($rotated !== '' && $rotated !== $cookie, 'token rotated (cookie changed)');
ok($pdo->query("SELECT COUNT(*) FROM od9_remember_tokens WHERE discord_id='$member'")->fetchColumn() == 1,
   'exactly one token remains (rotated in place)');

echo "== 3. theft: valid selector, wrong validator -> purge all ==\n";
[$sel2] = explode(':', $rotated, 2);
$_COOKIE['od9_remember'] = $sel2 . ':' . str_repeat('0', 64);
$_SESSION = [];
od9_try_remember_login();
ok(empty($_SESSION['discord_id']), 'not logged in on validator mismatch');
ok($pdo->query("SELECT COUNT(*) FROM od9_remember_tokens WHERE discord_id='$member'")->fetchColumn() == 0,
   "all of the user's tokens purged");
ok(empty($_COOKIE['od9_remember']), 'cookie cleared');

echo "== 4. membership: token for a non-member is refused ==\n";
$_SESSION = [];
od9_issue_remember_token($nonmember);
$_SESSION = [];
od9_try_remember_login();
ok(empty($_SESSION['discord_id']), 'non-member not logged in');
ok($pdo->query("SELECT COUNT(*) FROM od9_remember_tokens WHERE discord_id='$nonmember'")->fetchColumn() == 0,
   'non-member token purged');

echo "== 5. logout: token deleted + cookie cleared ==\n";
$_SESSION = ['discord_id' => $member];
od9_issue_remember_token($member);
od9_clear_remember_token();
ok($pdo->query("SELECT COUNT(*) FROM od9_remember_tokens WHERE discord_id='$member'")->fetchColumn() == 0,
   'token row deleted on logout');
ok(empty($_COOKIE['od9_remember']), 'cookie cleared on logout');

$pdo->prepare('DELETE FROM od9_remember_tokens WHERE discord_id IN (?, ?)')->execute([$member, $nonmember]);
echo "\n" . ($fail === 0 ? "ALL $pass PASSED" : "$pass passed, $fail FAILED") . "\n";
exit($fail === 0 ? 0 : 1);
