<style>
/* Email Signup Popup */
.email-popup-overlay{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.8);z-index:99999;justify-content:center;align-items:center}
.email-popup-overlay.active{display:flex}
.email-popup{background:var(--carbon-dark);border:2px solid var(--primary-blue);border-radius:16px;padding:2.5rem;max-width:450px;width:90%;position:relative;box-shadow:var(--glow);animation:popIn 0.3s ease}
@keyframes popIn{from{transform:scale(0.8);opacity:0}to{transform:scale(1);opacity:1}}
.email-popup-close{position:absolute;top:15px;right:15px;background:none;border:none;color:#666;font-size:1.5rem;cursor:pointer;transition:color 0.3s}
.email-popup-close:hover{color:var(--primary-blue)}
.email-popup h3{font-family:'Orbitron',sans-serif;font-size:1.5rem;color:#fff;text-align:center;margin-bottom:0.5rem}
.email-popup .tagline{text-align:center;color:var(--primary-blue);font-family:'Rajdhani',sans-serif;margin-bottom:1.5rem}
.email-popup p{text-align:center;color:#888;font-size:0.95rem;margin-bottom:1.5rem;line-height:1.6}
.email-signup-form{display:flex;flex-direction:column;gap:1rem}
.email-signup-form input{padding:0.75rem 1rem;background:var(--carbon);border:1px solid #444;border-radius:4px;color:#fff;font-family:'Exo 2',sans-serif;font-size:1rem}
.email-signup-form input:focus{outline:none;border-color:var(--primary-blue)}
.email-signup-form button{background:linear-gradient(135deg,var(--primary-blue),var(--electric-blue));color:var(--carbon);padding:0.8rem;border:none;border-radius:4px;font-family:'Rajdhani',sans-serif;font-weight:700;text-transform:uppercase;letter-spacing:2px;cursor:pointer;transition:all 0.3s}
.email-signup-form button:hover{box-shadow:var(--glow)}
.email-popup .privacy-note{text-align:center;color:#555;font-size:0.75rem;margin-top:1rem}
</style>

<!-- Email Signup Popup -->
<div class="email-popup-overlay" id="emailPopup">
<div class="email-popup">
<button class="email-popup-close" onclick="closeEmailPopup()">&times;</button>
<h3>JOIN THE MOVEMENT</h3>
<p class="tagline">Stay Updated on OD9</p>
<p>Get exclusive updates, new music drops, and content directly in your inbox. Be the first to know about community events and releases.</p>
<form class="email-signup-form" action="subscribe.php" method="POST">
<input type="text" name="name" placeholder="Your Name" required>
<input type="email" name="email" placeholder="Your Email" required>
<button type="submit"><i class="fas fa-bolt" style="margin-right:0.5rem"></i> Subscribe</button>
</form>
<p class="privacy-note">We respect your privacy. Unsubscribe anytime.</p>
</div>
</div>

<script>
function closeEmailPopup() {
    document.getElementById('emailPopup').classList.remove('active');
    localStorage.setItem('emailPopupClosed', 'true');
}

// Show popup after 5 seconds if not already closed
setTimeout(function() {
    if (!localStorage.getItem('emailPopupClosed')) {
        document.getElementById('emailPopup').classList.add('active');
    }
}, 5000);

// Close on overlay click
document.getElementById('emailPopup').addEventListener('click', function(e) {
    if (e.target === this) closeEmailPopup();
});
</script>
