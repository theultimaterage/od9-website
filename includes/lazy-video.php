<?php
/**
 * A silent looping clip that downloads only when it scrolls near view
 * (2026-10-07 website audit). The Supreme Elevation promo is 3.2 MB and used to
 * download on every visit to Music and Da Crew, seen or not. It still plays
 * silent and looped, exactly as before, once it is about to be seen.
 *
 *   require_once __DIR__ . '/includes/lazy-video.php';
 *   echo od9_lazy_video('images/music/supreme-elevation-promo.mp4');
 */
function od9_lazy_video(string $src, string $class = ''): string
{
    static $scriptSent = false;
    $html = '<video class="lazy-video' . ($class !== '' ? ' ' . htmlspecialchars($class, ENT_QUOTES) : '')
        . '" loop muted playsinline preload="none" data-src="' . htmlspecialchars($src, ENT_QUOTES) . '"></video>';
    if (!$scriptSent) {
        $scriptSent = true;
        $html .= '<script>(function(){function go(v){if(!v.src){v.src=v.dataset.src;v.play().catch(function(){});}}'
            . 'function init(){var vs=[].slice.call(document.querySelectorAll("video.lazy-video"));'
            . 'if(!("IntersectionObserver" in window)){vs.forEach(go);return;}'
            . 'var io=new IntersectionObserver(function(es){es.forEach(function(e){if(e.isIntersecting){go(e.target);io.unobserve(e.target);}});},{rootMargin:"200px"});'
            . 'vs.forEach(function(v){io.observe(v);});}'
            . 'if(document.readyState==="loading"){document.addEventListener("DOMContentLoaded",init);}else{init();}})();</script>';
    }
    return $html;
}
