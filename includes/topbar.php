<?php
/**
 * OD9 Universal Topbar
 * Thin utility strip that sits ABOVE the main .od9-nav. Sticky.
 * Include this once at the top of every page's <body>, immediately
 * before includes/nav.php.
 *
 * Layout (desktop):
 *   [85 SECONDS TO MIDNIGHT]                [JOIN THE LIST]  [D] [Y] [P] [S]
 * Layout (mobile, ≤600px):
 *   [85 SEC TO MIDNIGHT]              [JOIN]  (icons hidden — chrome budget)
 *
 * The JOIN THE LIST button:
 *   - Opens the existing email signup popup (#emailPopup) defined in index.php
 *   - Clears the localStorage closed-flag so users who closed it once can re-open
 *   - On pages that don't include the popup (most non-index pages), falls back
 *     to scrolling to the global footer signup form
 */
?>
<style>
.od9-topbar{position:fixed;top:0;left:0;width:100%;height:var(--topbar-h);z-index:10000;background:#000;border-bottom:1px solid #1a1a1a;display:flex;align-items:center;padding:0 1.25rem;font-family:'Rajdhani',sans-serif;font-size:0.78rem;letter-spacing:2px;text-transform:uppercase}
.od9-topbar-inner{width:100%;max-width:1200px;margin:0 auto;display:flex;align-items:center;justify-content:space-between;gap:1rem}
.od9-topbar-tag{color:#888;font-family:'Orbitron',sans-serif;letter-spacing:3px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.od9-topbar-tag .pulse{display:inline-block;width:6px;height:6px;border-radius:50%;background:#FF1744;margin-right:0.5rem;vertical-align:middle;box-shadow:0 0 6px rgba(255,23,68,0.7);animation:od9-pulse 2s infinite}
@keyframes od9-pulse{0%,100%{opacity:1}50%{opacity:0.45}}
.od9-topbar-right{display:flex;align-items:center;gap:0.85rem;white-space:nowrap}
.od9-topbar-join{display:inline-flex;align-items:center;gap:0.4rem;padding:0.3rem 0.85rem;background:linear-gradient(135deg,#00BFFF,#00A0FF);color:#0a0a0e;text-decoration:none;border-radius:3px;font-family:'Rajdhani',sans-serif;font-weight:700;font-size:0.78rem;letter-spacing:1.5px;text-transform:uppercase;border:none;cursor:pointer;transition:transform 0.15s,box-shadow 0.2s}
.od9-topbar-join:hover{transform:translateY(-1px);box-shadow:0 0 14px rgba(0,191,255,0.5)}
.od9-topbar-socials{display:flex;align-items:center;gap:0.7rem}
.od9-topbar-socials a{color:#888;font-size:0.95rem;text-decoration:none;transition:color 0.2s}
.od9-topbar-socials a:hover{color:#00BFFF}
@media(max-width:780px){
  .od9-topbar{padding:0 0.85rem;font-size:0.7rem}
  .od9-topbar-tag{letter-spacing:2px}
  .od9-topbar-socials{display:none}
}
@media(max-width:480px){
  .od9-topbar-tag{font-size:0.65rem;letter-spacing:1.5px}
}
</style>
<div class="od9-topbar">
  <div class="od9-topbar-inner">
    <span class="od9-topbar-tag"><span class="pulse"></span>85 Seconds to Midnight</span>
    <div class="od9-topbar-right">
      <button type="button" class="od9-topbar-join" onclick="od9OpenJoinPopup()">
        <i class="fas fa-bolt"></i> Join the List
      </button>
      <div class="od9-topbar-socials">
        <a href="https://discord.gg/spgmrXVMWq" target="_blank" rel="noopener" title="Discord"><i class="fab fa-discord"></i></a>
        <a href="https://youtube.com/@theultimaterage" target="_blank" rel="noopener" title="YouTube"><i class="fab fa-youtube"></i></a>
        <a href="https://www.patreon.com/c/TheUltimateRage" target="_blank" rel="noopener" title="Patreon"><i class="fab fa-patreon"></i></a>
      </div>
    </div>
  </div>
</div>
<script>
function od9OpenJoinPopup() {
  // Preferred path: open the existing email popup (#emailPopup) defined on
  // pages that have it (index.php). Clear the localStorage flag so the
  // popup is visible even for users who previously closed it.
  var popup = document.getElementById('emailPopup');
  if (popup) {
    try { localStorage.removeItem('emailPopupClosed'); } catch(e) {}
    popup.classList.add('active');
    var emailInput = popup.querySelector('input[name="email"]');
    if (emailInput) setTimeout(function(){ emailInput.focus(); }, 120);
    return;
  }
  // Fallback: scroll to global footer signup form (every page with footer.php
  // would have one if we ever extend footer.php with an email block —
  // currently footer.php is signup-less; in that case the JOIN button on
  // non-index pages falls through to the popup-less behaviour below).
  var footerForm = document.getElementById('footerSignupForm');
  if (footerForm) {
    footerForm.scrollIntoView({behavior:'smooth',block:'center'});
    var input = footerForm.querySelector('input[name="email"]');
    if (input) setTimeout(function(){ input.focus(); }, 600);
    return;
  }
  // Last resort: navigate to a dedicated subscribe landing if one ever exists.
  window.location.href = 'index.php#emailPopup';
}
</script>
