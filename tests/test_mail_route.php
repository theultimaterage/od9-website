<?php
/**
 * od9_mail_route() (includes/mail.php) — mail for a domain this server hosts must
 * never be handed to Brevo, which accepts mail for @offda9.com and never delivers
 * it (all 236 contact-form notices from 2026-06-16 to 2026-10-02). Standalone:
 *
 *   php tests/test_mail_route.php        (exit 0 = all pass)
 *
 * Pins every branch of the route, the domain list in config/mail.php, and that
 * od9_send_mail() asks the route BEFORE it reaches the platform (Brevo) code.
 */
declare(strict_types=1);
chdir(dirname(__DIR__));
require 'includes/mail.php';

$pass = 0;
$fail = 0;
function ok(bool $cond, string $what): void
{
    global $pass, $fail;
    if ($cond) { $pass++; echo "PASS {$what}\n"; } else { $fail++; echo "FAIL {$what}\n"; }
}

$home = ['offda9.com', 'freshthaband.com', 'freshthaplatform.com'];
ok(od9_mail_route('contact@offda9.com', [], 'smtp', $home) === 'local', 'contact@offda9.com is delivered on this server');
ok(od9_mail_route('Admin@FreshThaBand.com', [], 'smtp', $home) === 'local', 'the domain match ignores case');
ok(od9_mail_route('info@freshthaplatform.com>', [], 'smtp', $home) === 'local', 'a stray > after the address does not defeat the match');
ok(od9_mail_route('someone@gmail.com', [], 'smtp', $home) === 'platform', 'mail to anyone else goes to the platform');
ok(od9_mail_route('someone@offda9.com.evil.example', [], 'smtp', $home) === 'platform', 'a look-alike domain is not a home domain');
ok(od9_mail_route('someone@gmail.com', ['_no_platform' => true], 'smtp', $home) === 'smtp', '_no_platform falls to the driver');
ok(od9_mail_route('contact@offda9.com', [], 'file', $home) === 'file', 'local development captures everything to a file');
ok(od9_mail_route('no-at-sign', [], 'smtp', $home) === 'platform', 'an address with no @ is not treated as local');

ok(defined('MAIL_LOCAL_DOMAINS') && is_array(MAIL_LOCAL_DOMAINS), 'MAIL_LOCAL_DOMAINS is defined once mail.php is loaded');
ok(defined('MAIL_LOCAL_DOMAINS') && array_diff($home, MAIL_LOCAL_DOMAINS) === [], 'all three home domains are in MAIL_LOCAL_DOMAINS');

// The list has to live in a file the deploy SHIPS. config/mail.php is excluded (prod
// keeps its own copy), so a list kept only there would never reach production and the
// route would silently stay on Brevo.
$deploy = json_decode((string) file_get_contents('tools/prod-deploy-config.v2.json'), true);
$excluded = [];
foreach (($deploy['mappings'] ?? []) as $mapping) {
    $excluded = array_merge($excluded, $mapping['exclude'] ?? []);
}
ok($excluded !== [], 'the deploy config was read (it names its excludes)');
$ships = static function (string $rel) use ($excluded): bool {
    foreach ($excluded as $pattern) {
        if ($rel === $pattern || fnmatch($pattern, $rel) || (substr($pattern, -1) === '/' && strpos($rel, $pattern) === 0)) {
            return false;
        }
    }
    return true;
};
ok($ships('includes/mail.php'), 'includes/mail.php is deployed');
ok(!$ships('config/mail.php') || strpos((string) file_get_contents('config/mail.php'), "define('MAIL_LOCAL_DOMAINS'") === false,
   'the list is not kept only in config/mail.php');
ok(strpos((string) file_get_contents('includes/mail.php'), "define('MAIL_LOCAL_DOMAINS'") !== false, 'includes/mail.php carries the list');
ok($ships('includes/FormGuard.php') && $ships('contact-handler.php') && $ships('contact.php'), 'the guard, the handler and the page are deployed');

$src = (string) file_get_contents('includes/mail.php');
$fn = strpos($src, 'function od9_send_mail(');
$route = strpos($src, "od9_mail_route(\$to, \$opts) === 'local'", (int) $fn);
$platform = strpos($src, 'od9_send_via_platform', (int) $fn);
ok($fn !== false && $route !== false && $platform !== false && $route < $platform, 'od9_send_mail() checks the route before the platform code');
$local = substr($src, (int) $route, 400);
ok(strpos($local, '_od9_mail_via_mailfunc(') !== false, 'the local route sends with PHP mail()');

echo "{$pass} passed, {$fail} failed\n";
exit($fail === 0 && $pass > 0 ? 0 : 1);
