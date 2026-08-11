<?php
/**
 * Site-wide HTML minifier — registered as auto_prepend_file via the docroot
 * .user.ini, so it runs on EVERY PHP response (web SAPI only; CLI ignores
 * .user.ini, which is why CLI renders are never minified). Collapses inter-tag
 * whitespace on text/html responses; skips /api/ and non-HTML content types
 * (so JSON/webhook responses are never touched).
 *
 * <pre|script|style|textarea> contents are PROTECTED (placeholdered) before the
 * whitespace collapse so their internal newlines survive. The open-tag pattern
 * matches WITH OR WITHOUT attributes — a former `[\s>][^>]*>` form failed to
 * protect attribute-less tags whose body contained no `>` (it ran past the
 * closing tag), leaking the script into the collapse.
 *
 * !! DO NOT re-collapse after restoring the protected blocks. A former "second
 * pass" did exactly that and stripped the newlines INSIDE inline <script>,
 * collapsing each script onto one line — which turned every `// line comment`
 * into a line-eater that swallowed the following closing brace and broke the JS
 * ("Uncaught SyntaxError: Unexpected token ')'"). That silently killed the zone
 * transitions on 2026-06-20. Pass 1 already collapses the whitespace AROUND the
 * placeholders, so restoring the protected blocks must be the FINAL step.
 */
if (php_sapi_name() !== 'cli'
    && !defined('SP_MINIFY_REGISTERED')
    && strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') === false) {
    define('SP_MINIFY_REGISTERED', true);
    ob_start(function (string $buffer): string {
        foreach (headers_list() as $h) {
            if (stripos($h, 'content-type:') === 0 && stripos($h, 'text/html') === false) {
                return $buffer;
            }
        }
        $protected = [];
        $buffer = preg_replace_callback(
            '#(<(pre|script|style|textarea)(?:\s[^>]*)?>)(.*?)(</\2\s*>)#si',
            function (array $m) use (&$protected): string {
                $key = "\x02PROTECTED_" . count($protected) . "\x03";
                $protected[$key] = $m[0];
                return $key;
            },
            $buffer
        );
        $buffer = preg_replace('/>\s{2,}</', '> <', $buffer);
        $buffer = preg_replace('/\s{2,}/', ' ', $buffer);
        // Restore protected blocks LAST — no further collapsing (see docblock).
        if ($protected) {
            $buffer = str_replace(array_keys($protected), array_values($protected), $buffer);
        }
        return $buffer;
    });
}
