<?php
/**
 * The baseline security headers (2026-10-07 website audit, finding 3). Standalone:
 *
 *   php tests/test_security_headers.php                       (local XAMPP)
 *   php tests/test_security_headers.php https://offda9.com    (production)
 *
 * Reads what the server actually SENDS, on a page and on a 404, rather than
 * grepping .htaccess: a header block inside an IfModule for a module that is not
 * loaded is valid config that sends nothing. Exit 0 = every header present.
 */
declare(strict_types=1);

$base = rtrim($argv[1] ?? 'http://localhost/od9', '/');
$want = [
    'strict-transport-security' => 'max-age=31536000',
    'x-content-type-options'    => 'nosniff',
    'x-frame-options'           => 'SAMEORIGIN',
    'referrer-policy'           => 'strict-origin-when-cross-origin',
];

$pass = 0;
$fail = 0;
function ok(bool $cond, string $what): void
{
    global $pass, $fail;
    if ($cond) { $pass++; echo "PASS {$what}\n"; } else { $fail++; echo "FAIL {$what}\n"; }
}

function headers_of(string $url): ?array
{
    $ctx = stream_context_create(['http' => ['method' => 'GET', 'ignore_errors' => true, 'timeout' => 20,
                                             'header' => "User-Agent: od9-header-test\r\n"]]);
    $body = @file_get_contents($url, false, $ctx);
    if ($body === false && empty($http_response_header)) {
        return null;
    }
    $out = [];
    foreach ($http_response_header as $line) {
        if (strpos($line, ':') !== false) {
            [$k, $v] = explode(':', $line, 2);
            $out[strtolower(trim($k))] = trim($v);
        }
    }
    return $out;
}

foreach (['/about.php' => 'a page', '/no-such-page-od9-header-test' => 'a 404'] as $path => $what) {
    $h = headers_of($base . $path);
    ok($h !== null, "{$base}{$path} answered");
    if ($h === null) {
        // No response at all is the server, not the headers: on 2026-10-08 a deploy was refused
        // for an hour because local XAMPP Apache was simply not running.
        echo "     no response from {$base}: is the server running? (local XAMPP: C:\\xampp\\apache_start.bat)\n";
        continue;
    }
    foreach ($want as $name => $value) {
        ok(($h[$name] ?? '') === $value, "{$what} sends {$name}: {$value}" . (isset($h[$name]) && $h[$name] !== $value ? " (got {$h[$name]})" : ''));
    }
}

echo "\n{$pass} passed, {$fail} failed\n";
exit($fail === 0 ? 0 : 1);
