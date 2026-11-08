<?php
/**
 * Chapter macros — a citation names a chapter by its registry id, never by a number.
 *
 *   {{ch:ch4}}            -> Ch.3            (short, the weeklies' form)
 *   {{chapter:ch4}}       -> Chapter 3       (long, the lesson pages' eyebrow)
 *   {{chapter_nbsp:ch4}}  -> Chapter&nbsp;3  (long, non-breaking, the Source line)
 *   {{chtitle:ch4}}       -> The Great Awakening   (htmlspecialchars ENT_NOQUOTES)
 *
 * The id is a node id of data/manifesto-map.json (ch4, ch22, v3-space, appendix-a,
 * preface). Node ids never move; `num` is what the chapter is called today, so a
 * renumber is a registry sync and every citation follows it with no edit. A
 * non-numeric num renders as a name (A -> Appendix A, P -> Preface); a title that
 * begins with its own label ("Appendix A — Methods & ...") loses that prefix in
 * {{chtitle}}.
 *
 * This is the PHP half, used at render time by api/drip/weekly_lovelogic_sender.php,
 * api/drip/sender.php and curriculum/_codex.php. tools/chapter_macros.py is the Python
 * half the gates use; tools/chapter_macros_parity.py proves the two agree byte for byte.
 *
 * An unknown id is left exactly as written and returned through $unknown: the SENDERS
 * refuse to mail a message that still carries a macro (a subscriber must never see
 * "{{ch:ch99}}"); the Codex renderer prints it, where the drip gate names it.
 */
declare(strict_types=1);

const OD9_CHAPTER_MACRO_RE = '/\{\{(ch|chapter|chapter_nbsp|chtitle):([A-Za-z0-9_-]+)\}\}/';

/** @return array<string, array> node id => node, from the website registry (cached). */
function od9_chapter_registry(?string $path = null): array
{
    static $cache = [];
    $path = $path ?? (dirname(__DIR__) . '/data/manifesto-map.json');
    if (isset($cache[$path])) {
        return $cache[$path];
    }
    $raw = @file_get_contents($path);
    if ($raw === false) {
        throw new RuntimeException("chapter registry not readable: {$path}");
    }
    $data = json_decode($raw, true);
    $nodes = is_array($data) && isset($data['nodes']) ? $data['nodes'] : $data;
    if (!is_array($nodes)) {
        throw new RuntimeException("chapter registry has no nodes: {$path}");
    }
    $byId = [];
    foreach ($nodes as $n) {
        if (isset($n['id'])) {
            $byId[(string)$n['id']] = $n;
        }
    }
    return $cache[$path] = $byId;
}

function od9_chapter_label(array $node, bool $long, bool $nbsp = false): string
{
    $num = (string)($node['num'] ?? '');
    $sep = $nbsp ? '&nbsp;' : ' ';
    if (ctype_digit($num)) {
        return $long ? 'Chapter' . $sep . $num : 'Ch.' . $num;
    }
    $named = ['A' => 'Appendix A', 'P' => 'Preface'];
    $name = $named[$num] ?? $num;
    return $nbsp ? str_replace(' ', $sep, $name) : $name;
}

function od9_chapter_title(array $node): string
{
    $t = (string)($node['title'] ?? '');
    $t = preg_replace('/^(Appendix\s+[A-Z]|Preface)\s*[\x{2014}\x{2013}-]\s*/u', '', $t) ?? $t;
    return htmlspecialchars(trim($t), ENT_NOQUOTES, 'UTF-8');
}

function od9_chapter_render(string $kind, array $node): string
{
    switch ($kind) {
        case 'ch':           return od9_chapter_label($node, false);
        case 'chapter':      return od9_chapter_label($node, true);
        case 'chapter_nbsp': return od9_chapter_label($node, true, true);
        default:             return od9_chapter_title($node);
    }
}

/**
 * Resolve every macro in $text. Unknown ids are left as written and appended to $unknown.
 * @param list<string>|null $unknown
 */
function od9_resolve_chapter_macros(string $text, ?array &$unknown = null, ?array $registry = null): string
{
    $reg = $registry ?? od9_chapter_registry();
    $unknown = $unknown ?? [];
    $out = preg_replace_callback(OD9_CHAPTER_MACRO_RE, function (array $m) use ($reg, &$unknown): string {
        if (!isset($reg[$m[2]])) {
            $unknown[] = $m[2];
            return $m[0];
        }
        return od9_chapter_render($m[1], $reg[$m[2]]);
    }, $text);
    if ($out === null) {
        throw new RuntimeException('chapter macro resolution failed: ' . preg_last_error_msg());
    }
    return $out;
}

/** True when $text still carries a chapter macro (resolved or not). */
function od9_has_chapter_macro(string $text): bool
{
    return (bool)preg_match(OD9_CHAPTER_MACRO_RE, $text);
}
