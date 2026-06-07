<?php
/**
 * OD9 Insights - Substack syndication page
 *
 * Fetches The Ultimate Rage's Substack RSS feed and renders the recent
 * posts on offda9.com. Each post has its own canonical URL on Substack
 * but is also accessible at https://offda9.com/insights.php — the SEO
 * loop pulls discovery traffic onto the OD9 domain even when the content
 * lives on Substack.
 *
 * Caches the feed for 30 minutes to /tmp to avoid hammering Substack on
 * every page load.
 */

declare(strict_types=1);

const FEED_URL    = 'https://theultimaterage.substack.com/feed';
const CACHE_FILE  = '/tmp/od9_substack_feed.cache';
const CACHE_TTL   = 1800;   // 30 min
const MAX_POSTS   = 12;

function load_feed(): array {
    // Cache hit?
    if (is_file(CACHE_FILE) && (time() - filemtime(CACHE_FILE)) < CACHE_TTL) {
        $cached = @file_get_contents(CACHE_FILE);
        if ($cached) {
            $unserialized = @unserialize($cached);
            if (is_array($unserialized)) return $unserialized;
        }
    }

    // Fresh fetch
    $ctx = stream_context_create([
        'http' => [
            'timeout' => 8,
            'header'  => "User-Agent: OD9-InsightsRenderer/1.0\r\n",
        ],
    ]);
    $xml = @file_get_contents(FEED_URL, false, $ctx);
    if ($xml === false) return [];

    libxml_use_internal_errors(true);
    $rss = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
    if ($rss === false) return [];

    $items = [];
    foreach ($rss->channel->item ?? [] as $item) {
        $namespaces = $item->getNameSpaces(true);
        $content = $namespaces['content'] ?? null;
        $body_html = '';
        if ($content) {
            $encoded = $item->children($content)->encoded ?? null;
            if ($encoded !== null) $body_html = (string)$encoded;
        }
        $items[] = [
            'title'   => trim((string)$item->title),
            'link'    => trim((string)$item->link),
            'pubDate' => trim((string)$item->pubDate),
            'desc'    => trim((string)$item->description),
            'body'    => $body_html,
        ];
        if (count($items) >= MAX_POSTS) break;
    }

    @file_put_contents(CACHE_FILE, serialize($items));
    return $items;
}

function excerpt_text(string $html, int $words = 38): string {
    $text = strip_tags($html);
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace('/\s+/u', ' ', trim($text));
    $parts = preg_split('/\s+/u', $text, $words + 1);
    if ($parts === false) return $text;
    if (count($parts) <= $words) return $text;
    return implode(' ', array_slice($parts, 0, $words)) . '...';
}

function fmt_date(string $rfc): string {
    $ts = strtotime($rfc);
    return $ts ? date('M j, Y', $ts) : '';
}

$posts = load_feed();
?>
<?php
$page_title = 'OD9 Insights — Long-form essays from The Ultimate Rage';
$page_description = 'Recent essays from The Ultimate Rage\'s Substack on coordination failure, Type I civilization, and the OD9 framework.';
$page_slug = 'insights.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include __DIR__ . '/includes/head.php'; ?>
<style>
:root{--b:#00BFFF;--eb:#00A0FF;--d:#0A0A0A;--dd:#111;--c:#C0C0C0;--g:0 0 20px rgba(0,191,255,.45)}
*{margin:0;padding:0;box-sizing:border-box}
body{background:var(--d);color:var(--c);font-family:'Exo 2',sans-serif;min-height:100vh;padding:2rem 1rem 4rem;line-height:1.7}
.wrap{max-width:760px;margin:0 auto}
header{text-align:center;margin-bottom:3rem}
.logo{display:inline-block;margin-bottom:1.25rem}
.logo img{height:60px;filter:drop-shadow(var(--g))}
h1{font-family:'Orbitron',sans-serif;font-size:clamp(1.6rem,4.5vw,2.4rem);font-weight:900;color:#fff;letter-spacing:2px;margin-bottom:0.5rem;text-shadow:var(--g)}
.tag{font-family:'Rajdhani',sans-serif;color:var(--b);letter-spacing:4px;font-size:0.9rem;text-transform:uppercase;margin-bottom:0.75rem}
.lede{color:#bbb;font-size:1rem;max-width:540px;margin:0 auto}
.posts{display:grid;gap:1rem}
.post{background:var(--dd);border:1px solid #222;border-radius:10px;padding:1.5rem 1.5rem;transition:border-color 0.2s,transform 0.2s}
.post:hover{border-color:var(--b);transform:translateY(-2px)}
.post-meta{display:flex;justify-content:space-between;align-items:center;margin-bottom:0.75rem;color:#888;font-size:0.8rem}
.post-meta .date{font-family:'Orbitron',sans-serif;letter-spacing:1px;text-transform:uppercase}
.post-meta .source{color:var(--b);font-family:'Rajdhani',sans-serif;font-weight:600;letter-spacing:1.5px}
.post h2{font-family:'Orbitron',sans-serif;font-size:1.15rem;color:#fff;margin-bottom:0.65rem;line-height:1.35;letter-spacing:0.5px}
.post h2 a{color:#fff;text-decoration:none}
.post h2 a:hover{color:var(--b)}
.post-excerpt{color:#aaa;font-size:0.95rem;line-height:1.7;margin-bottom:1rem}
.post-link{color:var(--b);text-decoration:none;font-family:'Rajdhani',sans-serif;font-weight:600;font-size:0.85rem;letter-spacing:1px;text-transform:uppercase}
.post-link:hover{text-decoration:underline}
.empty{background:var(--dd);border:1px solid #222;border-radius:10px;padding:2rem;text-align:center;color:#888}
.empty a{color:var(--b);text-decoration:none}
.cta{text-align:center;margin:3rem 0 1.5rem;padding:2rem 1.5rem;background:var(--dd);border:1px solid #222;border-radius:12px}
.cta h3{font-family:'Orbitron',sans-serif;color:#fff;font-size:1.1rem;margin-bottom:0.6rem;letter-spacing:1px}
.cta p{color:#999;font-size:0.95rem;margin-bottom:1.25rem}
.btn{display:inline-block;padding:0.85rem 1.75rem;background:linear-gradient(135deg,var(--b),var(--eb));color:var(--d);text-decoration:none;font-family:'Rajdhani',sans-serif;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;border-radius:6px;font-size:0.9rem;box-shadow:var(--g)}
.btn:hover{transform:translateY(-2px)}
.bottom-links{text-align:center;margin-top:2rem}
.bottom-links a{color:#888;text-decoration:none;font-size:0.85rem;margin:0 0.75rem}
.bottom-links a:hover{color:var(--b)}
</style>
</head>
<body>
<div class="wrap">
  <header>
    <a href="index.php" class="logo"><img width="1408" height="768" src="images/logos/od9-logo.png" alt="OD9"></a>
    <p class="tag">Long-form essays</p>
    <h1>OD9 Insights</h1>
    <p class="lede">Essays from <strong style="color:var(--b)">The Ultimate Rage's Substack</strong> on coordination failure, Type I civilization, and the OD9 framework. Updated automatically.</p>
  </header>

  <?php if (empty($posts)): ?>
    <div class="empty">
      <p>The feed is taking a moment to load. <a href="https://theultimaterage.substack.com" target="_blank" rel="noopener">Read the latest on Substack →</a></p>
    </div>
  <?php else: ?>
    <div class="posts">
      <?php foreach ($posts as $p):
          $excerpt = excerpt_text($p['body'] !== '' ? $p['body'] : $p['desc']);
      ?>
        <article class="post">
          <div class="post-meta">
            <span class="date"><?= htmlspecialchars(fmt_date($p['pubDate']), ENT_QUOTES) ?></span>
            <span class="source">Substack</span>
          </div>
          <h2><a href="<?= htmlspecialchars($p['link'], ENT_QUOTES) ?>" target="_blank" rel="noopener"><?= htmlspecialchars($p['title'], ENT_QUOTES) ?></a></h2>
          <?php if ($excerpt !== ''): ?>
            <p class="post-excerpt"><?= htmlspecialchars($excerpt, ENT_QUOTES) ?></p>
          <?php endif; ?>
          <a class="post-link" href="<?= htmlspecialchars($p['link'], ENT_QUOTES) ?>" target="_blank" rel="noopener">
            Read on Substack →
          </a>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <div class="cta">
    <h3>Want every new essay in your inbox?</h3>
    <p>Subscribe to The Ultimate Rage on Substack — free tier covers most posts, paid tier unlocks the deep dives.</p>
    <a href="https://theultimaterage.substack.com" target="_blank" rel="noopener" class="btn">Subscribe on Substack</a>
  </div>

  <div class="bottom-links">
    <a href="index.php">Home</a>
    <a href="framework.php">Framework</a>
    <a href="ncz.php">NCZ Show</a>
    <a href="ecosystem.php">Ecosystem</a>
    <a href="https://discord.gg/spgmrXVMWq" target="_blank">Discord</a>
  </div>
</div>
</body>
</html>
