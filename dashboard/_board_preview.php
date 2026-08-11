<?php
// LOCAL-ONLY board preview harness (lives in htdocs only — never in the repo, never
// deployed). Fakes the founder's session so the REAL board.php renders for visual
// verification, and rewrites root-absolute asset URLs for the local /od9/ prefix.
if (($_SERVER['SERVER_NAME'] ?? '') !== 'localhost') { http_response_code(404); exit; }

session_start();                                   // od9_dashboard_boot() no-ops after this
$_SESSION['discord_id'] = '633039899947434015';    // founder (OD9_ADMIN_DISCORD_IDS)

ob_start();
require __DIR__ . '/board.php';
$html = ob_get_clean();

// Local path rewrites (prod serves at root; local mirror lives under /od9/)
$html = str_replace('/od9/public/images/board', '/od9/images/board', $html);
$html = str_replace('href="/css/', 'href="/od9/css/', $html);
$html = str_replace('href="/progress.php', 'href="/od9/progress.php', $html);
$html = str_replace('href="/roadmap.php', 'href="/od9/roadmap.php', $html);
echo $html;
