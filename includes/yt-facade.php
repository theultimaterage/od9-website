<?php
/**
 * Click-to-load YouTube (2026-10-07 website audit, finding 5).
 *
 * A YouTube iframe pulls ~3.7 MB of player script the moment the page loads,
 * whether or not anyone presses play: Support and NCZ each weighed ~4.7 MB for
 * it. This renders the video's thumbnail and a play button instead, and swaps
 * in the real player (autoplaying, since the visitor just asked for it) on
 * click. Without a video id it shows a branded play panel.
 *
 *   require_once __DIR__ . '/includes/yt-facade.php';
 *   echo od9_yt_facade($embedUrl, $title, $videoId);   // $videoId may be null
 */
function od9_yt_facade(string $embedUrl, string $title, ?string $videoId = null): string
{
    static $scriptSent = false;
    $sep = strpos($embedUrl, '?') === false ? '?' : '&';
    $src = htmlspecialchars($embedUrl . $sep . 'autoplay=1', ENT_QUOTES);
    $t = htmlspecialchars($title, ENT_QUOTES);
    $thumb = $videoId
        ? '<img src="https://i.ytimg.com/vi/' . rawurlencode($videoId) . '/hqdefault.jpg" alt="" width="480" height="360" loading="lazy">'
        : '';
    $html = '<div class="embed-wrap yt-facade"><button type="button" class="yt-facade-btn" data-src="' . $src
        . '" data-title="' . $t . '" aria-label="Play: ' . $t . '">' . $thumb
        . '<span class="yt-facade-play" aria-hidden="true"></span><span class="yt-facade-title">' . $t . '</span></button></div>';
    if (!$scriptSent) {
        $scriptSent = true;
        $html .= '<script>document.addEventListener("click",function(e){var b=e.target.closest(".yt-facade-btn");if(!b)return;'
            . 'var f=document.createElement("iframe");f.src=b.dataset.src;f.title=b.dataset.title;f.allowFullscreen=true;'
            . 'f.setAttribute("allow","accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture");'
            . 'b.parentNode.replaceChild(f,b);});</script>';
    }
    return $html;
}
