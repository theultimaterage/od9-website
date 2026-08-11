<?php
/**
 * Reusable CLICK-TRIGGERED zone transition (the ztrans overlay) — one
 * implementation for every zone page so there's no drift.
 *
 *   od9_ztrans_head()  — call inside <head>: links css/ztrans.css.
 *   od9_ztrans_body()  — call right after <body>: the (empty) <video> overlay +
 *                        a delegated click handler.
 *
 * A nav link opts in by adding class="ztrans-link" data-ztrans="<clip>" to an
 * <a href="…">. On click we play <clip> full-screen OVER the current page, then
 * navigate to the link's href when the clip ends. Because a click is a user
 * gesture, video.play() is always permitted.
 *
 * Fail-OPEN, never strands a click: modified-click / new-tab fall through to a
 * normal navigation; a play() rejection, an <video> error, the clip ending, or a
 * 7s hard cap all navigate. If JS is off the links just work. NOTE: OS
 * reduced-motion is intentionally NOT honored — the owner wants the transitions
 * for everyone ("members who dislike it can kick rocks"); the SKIP button is the
 * dismiss path. Revisit if a per-user opt-out lands in Settings.
 *
 * !! MINIFY-SAFE — DO NOT use `//` line comments inside the <script> below. This
 * origin runs an HTML minifier that STRIPS NEWLINES from PHP output, collapsing
 * the whole script onto one line; a `//` comment would then swallow the rest of
 * the line (incl. a closing brace) and break it — that was the 2026-06-20
 * "Unexpected token ')'" bug. Use block comments only (never the // form), and
 * keep every statement semicolon-terminated so the collapsed line stays valid JS.
 *
 * Clips: /video/transitions/<clip>.{webm,mp4,jpg} — 'gate' (→board), 'hatch'
 * (→bunker), 'hq-door' (→dashboard), 'toolbox' (→settings). Visuals: css/ztrans.css.
 */
declare(strict_types=1);

if (!function_exists('od9_ztrans_head')):
function od9_ztrans_head(): void {
    $cssv = @filemtime(__DIR__ . '/../../css/ztrans.css') ?: '1';
    echo '<link rel="stylesheet" href="/css/ztrans.css?v=' . $cssv . '">' . "\n";
}
endif;

if (!function_exists('od9_ztrans_body')):
function od9_ztrans_body(): void {
    $isLocal = (($_SERVER['SERVER_NAME'] ?? '') === 'localhost') || strpos(__DIR__, 'xampp') !== false;
    $vid = $isLocal ? '/od9/video/transitions' : '/video/transitions';
    ?>
<!-- click-triggered zone transition: <a class="ztrans-link" data-ztrans="…"> plays the clip, then navigates -->
<div class="ztrans" id="ztrans" aria-hidden="true">
  <video class="ztrans-vid" id="ztransVid" muted playsinline preload="none"></video>
  <button class="ztrans-skip" type="button" id="ztransSkip">SKIP &rsaquo;</button>
</div>
<script>
(function(){
  var VID = <?= json_encode($vid, JSON_UNESCAPED_SLASHES) ?>;
  var ov  = document.getElementById('ztrans');
  var v   = document.getElementById('ztransVid');
  if (!ov || !v) return;
  var nav = null, cb = null, going = false, tmr = 0;
  function go(){
    if (going) return; going = true;
    if (nav) { window.location.href = nav; return; }
    /* callback mode (no navigation): hide the overlay, then hand off */
    ov.classList.remove('zt-on');
    try { v.pause(); } catch (e) {}
    v.removeAttribute('poster'); v.innerHTML = '';
    if (cb) { var f = cb; cb = null; f(); }
  }
  function play(clip, href, done){
    nav = href || null; cb = done || null; going = false;
    v.innerHTML = '<source src="' + VID + '/' + clip + '.webm" type="video/webm">'
                + '<source src="' + VID + '/' + clip + '.mp4" type="video/mp4">';
    v.setAttribute('poster', VID + '/' + clip + '.jpg');
    ov.classList.add('zt-on');
    v.onended = go; v.onerror = go;
    /* a missing clip errors on the LAST <source>, not the <video> — catch it
       there too so absent files fail open instantly instead of eating the 7s cap */
    var ls = v.querySelector('source:last-of-type');
    if (ls) ls.onerror = go;
    try { v.load(); } catch (e) {}
    var p = v.play();
    if (p && p.catch) p.catch(go);
    clearTimeout(tmr); tmr = setTimeout(go, 7000);
  }
  /* play a clip full-screen WITHOUT navigating, then invoke done() — used by the
     onboarding tour (clip 'tour'). Fail-open like everything else here. */
  window.odZtransPlay = function(clip, done){ play(clip, null, done); };
  document.addEventListener('click', function(e){
    var a = e.target.closest ? e.target.closest('a.ztrans-link') : null;
    if (!a) return;
    var clip = a.getAttribute('data-ztrans'), href = a.getAttribute('href');
    if (!clip || !href) return;
    if (e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
    if (a.target && a.target !== '_self') return;
    e.preventDefault();
    play(clip, href);
  });
  var skip = document.getElementById('ztransSkip');
  if (skip) skip.addEventListener('click', function(e){ e.preventDefault(); e.stopPropagation(); go(); });
})();
</script>
    <?php
}
endif;
