<?php
/**
 * Integration test for the dashboard remember-me token lifecycle
 * (public/dashboard/includes/auth.php). Standalone — no PHPUnit needed.
 *
 *   php tests/test_remember_auth.php        (run from repo root, local XAMPP)
 *
 * Exercises: issue, consume+rotate, theft purge, membership re-check, logout.
 * Uses a real od9_members id (skips if none locally) + a synthetic non-member.
 * Cleans up after itself. setcookie/session warnings in CLI are expected and
 * suppressed; the assertions are on DB rows + $_COOKIE + $_SESSION state.
 */
error_reporting(E_ERROR | E_PARSE);
chdir(dirname(__DIR__));
$_SERVER['HTTP_USER_AGENT'] = 'OD9-RememberTest';

require 'config/database.php';
require 'public/includes/env.php';
require 'public/dashboard/includes/auth.php';

$pdo = getDatabaseConnection();
$member = $pdo->query('SELECT discord_user_id FROM od9_members LIMIT 1')->fetchColumn();
if (!$member) {
    fwrite(STDERR, "SKIP: no rows in od9_members locally to test the membership path\n");
    exit(0);
}
$member    = (string) $member;
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
