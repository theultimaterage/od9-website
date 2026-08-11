<?php
/**
 * offda9.com/callin — THE permanent call-in door for the NCZ live show.
 *
 * Redirects callers to the CURRENT guest room (VDO.Ninja in Phase 0; the
 * self-hosted rig in Phase 1) so the public URL never changes while the
 * engine behind it can be swapped or burned in seconds.
 *
 * Config: callin_secret.config.php (sibling, mode 600, gitignored via the
 * *_secret.config.php pattern — deployed by hand like pulse_secret):
 *   <?php
 *   const NCZ_CALLIN_GUEST_URL = 'https://vdo.ninja/?room=...&password=...&...';
 * Config absent or URL empty => branded "lines are closed" page (the kill
 * switch is blanking the config). Director URL NEVER appears here.
 *
 * Spec: docs/streams/CALLIN-RIG-SPEC.md (bot repo).
 */
declare(strict_types=1);

$cfg = __DIR__ . '/callin_secret.config.php';
if (file_exists($cfg)) {
    require_once $cfg;
}
$target = defined('NCZ_CALLIN_GUEST_URL') ? (string)NCZ_CALLIN_GUEST_URL : '';

if ($target !== '') {
    header('Location: ' . $target, true, 302);
    header('Cache-Control: no-store');
    exit;
}

http_response_code(200);
header('Cache-Control: no-store');
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>NCZ — Call-In Line</title>
<style>
  body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center;
         background: #000; color: #E6E7EB; font-family: Inter, system-ui, sans-serif; text-align: center; }
  .box { padding: 40px 28px; border: 2px solid #FF1744; box-shadow: 0 0 18px rgba(255,23,68,.6); max-width: 520px; }
  h1 { font-family: Impact, 'Bebas Neue', sans-serif; letter-spacing: .04em; text-transform: uppercase;
       font-size: 44px; margin: 0 0 10px; }
  h1 span { color: #FF1744; }
  p { margin: 8px 0 0; line-height: 1.6; color: #9AA0AE; }
  a { color: #22E6F0; text-decoration: none; }
</style>
</head>
<body>
<div class="box">
  <h1>Lines are <span>closed</span></h1>
  <p>The call-in line opens during the live show. Catch the next one — daily, 4 PM Central.</p>
  <p><a href="https://offda9.com/">offda9.com</a></p>
</div>
</body>
</html>
