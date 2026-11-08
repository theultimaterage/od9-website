<?php
/**
 * Resolve chapter macros in FILE through the PHP resolver and print the result.
 *
 *   php tools/chapter_macros_render.php FILE [REGISTRY.json]
 *
 * Exit 0 when every macro resolved; 1 when an id is unknown (names them on stderr);
 * 2 when the file or the registry cannot be read. Used by tools/chapter_macros_parity.py
 * to prove the PHP half and the Python half agree byte for byte.
 */
declare(strict_types=1);

require_once __DIR__ . '/../includes/chapter_macros.php';

$file = $argv[1] ?? null;
if ($file === null || !is_readable($file)) {
    fwrite(STDERR, "usage: php chapter_macros_render.php FILE [REGISTRY.json]\n");
    exit(2);
}
try {
    $registry = isset($argv[2]) ? od9_chapter_registry($argv[2]) : od9_chapter_registry();
    $text = file_get_contents($file);
    if ($text === false) {
        exit(2);
    }
    $unknown = [];
    $out = od9_resolve_chapter_macros($text, $unknown, $registry);
} catch (RuntimeException $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(2);
}
fwrite(STDOUT, $out);
if ($unknown) {
    fwrite(STDERR, 'unknown chapter id(s): ' . implode(', ', array_unique($unknown)) . "\n");
    exit(1);
}
exit(0);
