<?php
/**
 * od9_session_store (includes/session_store.php): session files go to THIS account's own
 * folder, one per app, 0700, with PHP's own garbage collection on; a folder that cannot
 * be made is refused loudly and PHP's path is left alone. Standalone, no PHPUnit:
 *
 *   php tests/test_session_store.php        (exit 0 = all pass)
 *
 * The incident (2026-11-06): every site wrote into cPanel's shared root-owned folder; a
 * file another account owned refused to open ("Permission denied"), and cPanel's root
 * cleaner deleted files at the global 24-minute lifetime whatever the app had set.
 */
declare(strict_types=1);
chdir(dirname(__DIR__));
require 'includes/session_store.php';
// PHP refuses a session ini change once output has started ("headers already sent"), and
// the PASS lines below are output: buffer them, as a page buffers nothing before its boot.
ob_start();

$pass = 0;
$fail = 0;
function ok(bool $cond, string $what): void
{
    global $pass, $fail;
    if ($cond) { $pass++; echo "PASS {$what}\n"; } else { $fail++; echo "FAIL {$what}\n"; }
}

putenv('SESSION_STORE_ROOT');
ok(od9_account_home('/home/offda9/public_html/includes') === '/home/offda9', 'the account home is read from the path on cPanel');
ok(od9_account_home('C:\\xampp\\htdocs\\od9\\includes') === null, 'no account home locally');
ok(od9_session_root('/home/offda9/public_html/includes') === '/home/offda9/tmp/php_sessions', 'the root is under the account home');
ok(strpos(od9_session_root('C:/xampp/htdocs/od9/includes'), '/var/cpanel') === false, "the local root is never cPanel's shared folder");
ok(od9_session_dir('odd name/', '/r') === '/r/odd_name_', 'an app label cannot carry path characters');
ok(od9_session_app('PHPSESSID') === 'site' && od9_session_app('') === 'site', "PHP's default cookie name is the one site folder");
ok(od9_session_app('OTHER_NAME') === 'other_name', 'another session name gets its own folder');
ok(od9_session_dir(od9_session_app(), '/r') === '/r/site', 'with no label the folder follows the session name in force');

$root = rtrim(sys_get_temp_dir(), '/\\') . '/od9_ss_' . bin2hex(random_bytes(4));
$savedPath = ini_get('session.save_path');
$dir = od9_session_store('dashboard', $root);
ok($dir === $root . '/dashboard', 'prepare returns the app folder');
ok(is_dir($dir), 'the folder is created');
ok(DIRECTORY_SEPARATOR !== '/' || (fileperms($dir) & 0777) === 0700, 'the folder is private (0700)');
ok(ini_get('session.save_path') === $dir, 'PHP is pointed at it');
ok(ini_get('session.gc_probability') === '1' && ini_get('session.gc_divisor') === '100', "PHP's own gc is on");
ok(od9_session_store('dashboard', $root) === $dir, 'a second call is a no-op that still answers');
ini_set('session.save_path', $savedPath);

$file = $root . '/blocked';
file_put_contents($file, 'not a directory');
ok(@od9_session_store('site', $file) === null, 'a root that cannot hold folders is refused');
ok(ini_get('session.save_path') === $savedPath, "PHP's path is left alone when refused");

foreach (glob($dir . '/*') ?: [] as $f) { @unlink($f); }
@rmdir($dir); @unlink($file); @rmdir($root);

echo "\n{$pass} passed, {$fail} failed\n";
ob_end_flush();
exit($fail === 0 ? 0 : 1);
