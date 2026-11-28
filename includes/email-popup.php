<?php
/**
 * OD9 universal email-signup popup.
 * Rendered once per page via includes/nav.php, so the topbar "Join the List"
 * button (od9OpenJoinPopup) opens it inline on EVERY page. Styles live in
 * css/od9.css (.email-popup-*). Auto-shows only on the homepage (once, unless
 * dismissed). Fixes the prior bug: the form had no #popupFormMsg, so the AJAX
 * handler never bound and a submit reloaded the page to raw JSON.
 *
 * THE FORM GUARD (2026-11-28, piece 4 of the form checks). subscribe.php refuses a
 * signup that does not carry the guard's trap and proof from a stamped page, and the
 * popup sits on pages that started no session (this file is printed after their output
 * began). So the guard's fields are printed with the proof the session has, empty on
 * those pages, and when the popup OPENS its script asks form-stamp.php for the stamp and
 * the proof. A submit waits for the stamp and for the guard's minimum after it, so a fast
 * person is never refused as a program. After any refusal the widget (while the human
 * check is on) is asked for a fresh token: a token answers one question only.
 * tests/test_public_forms_use_the_guard.php holds every piece of this to its place.
 */
require_once __DIR__ . '/od9_form_guard.php';
$_pp_base = $nav_base ?? rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'), '/\\');
?>
<div class="email-popup-overlay" id="emailPopup">
<div class="email-popup">
<button class="email-popup-close" onclick="closeEmailPopup()" aria-label="Close">&times;</button>
<h3>JOIN THE MOVEMENT</h3>
<p class="tagline">Stay Updated on OD9</p>
<p>Get exclusive updates, new music drops, and content directly in your inbox. Be the first to know about community events and releases.</p>
<form class="email-signup-form" id="popupSignupForm">
<input type="text" name="name" placeholder="Your Name" required>
<input type="email" name="email" placeholder="Your Email" required>
<?= od9_form_guard_fields() ?>
<button type="submit"><i class="fas fa-bolt" style="margin-right:0.5rem"></i> Subscribe</button>
</form>
<p id="popupFormMsg" style="display:none;text-align:center;margin-top:1rem;font-size:0.9rem"></p>
<p class="privacy-note">We respect your privacy. Unsubscribe anytime.</p>
</div>
</div>
<?= od9_form_guard_script() ?>
<script>
function closeEmailPopup(){var p=document.getElementById('emailPopup');if(p){p.classList.remove('active');}try{localStorage.setItem('emailPopupClosed','true');}catch(e){}}
(function(){
  var overlay=document.getElementById('emailPopup'); if(!overlay) return;
  var base='<?= htmlspecialchars($_pp_base, ENT_QUOTES) ?>';
  overlay.addEventListener('click',function(e){ if(e.target===this) closeEmailPopup(); });
  var path=location.pathname;
  if((path==='/'||/\/index\.php$/.test(path)) && !localStorage.getItem('emailPopupClosed')){
    setTimeout(function(){ overlay.classList.add('active'); }, 5000);
  }
  var form=document.getElementById('popupSignupForm'), msgEl=document.getElementById('popupFormMsg');
  if(!form||!msgEl) return;
  var proof=form.querySelector('input[name="fg_proof"]'), stamp=null, stampedAt=0, waitMs=3250;
  // The guard's stamp: the page's own when it rendered one, else form-stamp.php's. The
  // proof is put back each time, because form.reset() after a signup empties it.
  function stampOnce(){
    if(!proof){ return Promise.resolve(); }
    var have=proof.getAttribute('data-fg');
    if(have){ proof.value=have; return Promise.resolve(); }
    if(stamp){ return stamp; }
    stamp=fetch(base+'/form-stamp.php',{credentials:'same-origin',cache:'no-store'})
      .then(function(r){ if(!r.ok){ throw new Error('stamp '+r.status); } return r.json(); })
      .then(function(d){ if(!d||!d.proof){ throw new Error('no proof'); }
        proof.setAttribute('data-fg',d.proof); proof.value=d.proof; stampedAt=Date.now();
        waitMs=((d.min_seconds||3)*1000)+250; })
      .catch(function(e){ stamp=null; throw e; });
    return stamp;
  }
  if('MutationObserver' in window){
    new MutationObserver(function(){ if(overlay.classList.contains('active')){ stampOnce().catch(function(){}); } })
      .observe(overlay,{attributes:true,attributeFilter:['class']});
  }
  form.addEventListener('focusin',function(){ stampOnce().catch(function(){}); });
  function fresh(){ if(window.fgHuman){ window.fgHuman.reset(form); } }
  form.addEventListener('submit',function(e){
    e.preventDefault();
    var btn=form.querySelector('button[type="submit"]'), origText=btn.innerHTML;
    btn.innerHTML='<i class="fas fa-spinner fa-spin" style="margin-right:0.5rem"></i> Joining...'; btn.disabled=true; msgEl.style.display='none';
    stampOnce()
      .then(function(){ var w=stampedAt?stampedAt+waitMs-Date.now():0; return new Promise(function(ok){ setTimeout(ok,w>0?w:0); }); })
      .then(function(){ var data={}; new FormData(form).forEach(function(v,k){ data[k]=v; });
        return fetch(base+'/subscribe.php',{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json'},body:JSON.stringify(data)}); })
      .then(function(r){ return r.json(); })
      .then(function(res){ msgEl.style.display='block'; if(res.success){ msgEl.style.color='#00BFFF'; msgEl.textContent=res.message; form.reset(); setTimeout(closeEmailPopup,3000); } else { msgEl.style.color='#ff6b6b'; msgEl.textContent=res.error||'Something went wrong. Try again.'; fresh(); } })
      .catch(function(){ msgEl.style.display='block'; msgEl.style.color='#ff6b6b'; msgEl.textContent='Network error. Please try again.'; fresh(); })
      .finally(function(){ btn.innerHTML=origText; btn.disabled=false; });
  });
})();
</script>
