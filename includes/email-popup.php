<?php
/**
 * OD9 universal email-signup popup.
 * Rendered once per page via includes/nav.php, so the topbar "Join the List"
 * button (od9OpenJoinPopup) opens it inline on EVERY page. Styles live in
 * css/od9.css (.email-popup-*). Auto-shows only on the homepage (once, unless
 * dismissed). Fixes the prior bug: the form had no #popupFormMsg, so the AJAX
 * handler never bound and a submit reloaded the page to raw JSON.
 */
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
<button type="submit"><i class="fas fa-bolt" style="margin-right:0.5rem"></i> Subscribe</button>
</form>
<p id="popupFormMsg" style="display:none;text-align:center;margin-top:1rem;font-size:0.9rem"></p>
<p class="privacy-note">We respect your privacy. Unsubscribe anytime.</p>
</div>
</div>
<script>
function closeEmailPopup(){var p=document.getElementById('emailPopup');if(p){p.classList.remove('active');}try{localStorage.setItem('emailPopupClosed','true');}catch(e){}}
(function(){
  var overlay=document.getElementById('emailPopup'); if(!overlay) return;
  overlay.addEventListener('click',function(e){ if(e.target===this) closeEmailPopup(); });
  var path=location.pathname;
  if((path==='/'||/\/index\.php$/.test(path)) && !localStorage.getItem('emailPopupClosed')){
    setTimeout(function(){ overlay.classList.add('active'); }, 5000);
  }
  var form=document.getElementById('popupSignupForm'), msgEl=document.getElementById('popupFormMsg');
  if(!form||!msgEl) return;
  form.addEventListener('submit',function(e){
    e.preventDefault();
    var btn=form.querySelector('button[type="submit"]'), origText=btn.innerHTML;
    btn.innerHTML='<i class="fas fa-spinner fa-spin" style="margin-right:0.5rem"></i> Joining...'; btn.disabled=true; msgEl.style.display='none';
    var data={}; new FormData(form).forEach(function(v,k){ data[k]=v; });
    fetch('<?= htmlspecialchars($_pp_base, ENT_QUOTES) ?>/subscribe.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(data)})
      .then(function(r){ return r.json(); })
      .then(function(res){ msgEl.style.display='block'; if(res.success){ msgEl.style.color='#00BFFF'; msgEl.textContent=res.message; form.reset(); setTimeout(closeEmailPopup,3000); } else { msgEl.style.color='#ff6b6b'; msgEl.textContent=res.error||'Something went wrong. Try again.'; } })
      .catch(function(){ msgEl.style.display='block'; msgEl.style.color='#ff6b6b'; msgEl.textContent='Network error. Please try again.'; })
      .finally(function(){ btn.innerHTML=origText; btn.disabled=false; });
  });
})();
</script>
