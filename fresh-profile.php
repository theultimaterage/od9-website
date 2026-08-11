<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: public, max-age=3600');
$file = __DIR__ . '/.well-known/fresh-profile.json';
if (file_exists($file)) { readfile($file); } else { http_response_code(404); echo '{"error":"not found"}'; }
