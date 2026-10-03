<?php
/**
 * The contact page and its handler are wired to the guard and the mail route, and
 * every result the handler sends back is shown to the visitor. Static checks on the
 * source, standalone:
 *
 *   php tests/test_contact_wiring.php    (exit 0 = all pass)
 *
 * Why static: the regressions this pins are a missing call or a call in the wrong
 * order, and until 2026-10-02 the page showed none of the handler's results at all.
 * Each detector is first run against a known-bad snippet, so a broken pattern
 * cannot report "clean" (the scanner has to be able to find something).
 */
declare(strict_types=1);
chdir(dirname(__DIR__));

$pass = 0;
$fail = 0;
function ok(bool $cond, string $what): void
{
    global $pass, $fail;
    if ($cond) { $pass++; echo "PASS {$what}\n"; } else { $fail++; echo "FAIL {$what}\n"; }
}

$rawMail = '/(?<![\w>:$])@?mail\s*\(/';
ok(preg_match($rawMail, '$sent = @mail($to, $s, $b, $h);') === 1, 'detector: finds a raw mail() call');
ok(preg_match($rawMail, 'od9_send_mail($to, $s, $h);') === 0, 'detector: does not mistake od9_send_mail() for mail()');

$handler = (string) file_get_contents('contact-handler.php');
$code = preg_replace('~/\*.*?\*/|//[^\n]*~s', '', $handler);   // judge the code, not its comments
$admit = strpos($code, 'FormGuard::admit(');
$limit = strpos($code, 'FormGuard::limit(');
$send = strpos($code, 'od9_send_mail(');
ok($admit !== false && $send !== false && $admit < $send, 'the handler passes the guard before it sends');
ok($limit !== false && $send !== false && $limit < $send, 'the handler counts the hourly limit before it sends');
ok(preg_match($rawMail, (string) $code) === 0, 'the handler never calls mail() itself');
ok(strpos($code, 'od9_mail_route(') !== false, 'the handler logs the route the message took');
ok(strpos($code, 'contact-form.jsonl') !== false, 'every outcome is written to logs/contact-form.jsonl');

preg_match_all('/error=([a-z_]+)/', $code, $m);
$codes = array_values(array_unique($m[1]));
ok(count($codes) >= 4, 'the handler sends back its error codes (found ' . count($codes) . ')');

$page = (string) file_get_contents('contact.php');
$open = strpos($page, 'FormGuard::open(');
$doctype = stripos($page, '<!DOCTYPE');
ok($open !== false && $doctype !== false && $open < $doctype, 'the page stamps the guard before any output');
$form = strpos($page, '<form class="contact-form"');
$fields = strpos($page, 'FormGuard::fields(');
$end = strpos($page, '</form>');
ok($form !== false && $fields !== false && $end !== false && $form < $fields && $fields < $end, 'the guard fields are inside the form');
ok(strpos($page, 'FormGuard::script()') !== false, 'the page prints the guard script');
ok(strpos($page, "isset(\$_GET['success'])") !== false, 'the page shows a sent message');
foreach ($codes as $c) {
    ok(preg_match("/'" . preg_quote($c, '/') . "'\s*=>/", $page) === 1, "the page shows error={$c}");
}

echo "{$pass} passed, {$fail} failed\n";
exit($fail === 0 && $pass > 0 ? 0 : 1);
