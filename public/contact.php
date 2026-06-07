<?php
$page_title = 'Contact OD9 - Get in Touch | Off Da Nine';
$page_description = 'Contact OD9 for collaborations, partnerships, media inquiries, or questions about the ASCEND Protocol and our mission to advance humanity toward Type I civilization.';
$page_slug = 'contact.php';
$page_og_title = 'Contact OD9 | Get in Touch';
$page_og_description = 'Reach out for collaborations, partnerships, media inquiries, or questions about OD9\'s mission.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include __DIR__ . '/includes/head.php'; ?>
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "ContactPage",
  "@id": "https://offda9.com/contact.php",
  "url": "https://offda9.com/contact.php",
  "name": "Contact OD9 - Get in Touch | Off Da Nine",
  "description": "Contact OD9 for collaborations, partnerships, media inquiries, or questions about the ASCEND Protocol and our mission to advance humanity toward Type I civilization.",
  "isPartOf": {"@id": "https://offda9.com/#website"},
  "mainEntityOfPage": "https://offda9.com/contact.php"
}
</script>
<style>
body{background:var(--carbon);background-image:linear-gradient(45deg,#111 25%,transparent 25%),linear-gradient(-45deg,#111 25%,transparent 25%),linear-gradient(45deg,transparent 75%,#111 75%),linear-gradient(-45deg,transparent 75%,#111 75%);background-size:4px 4px;color:var(--chrome);font-family:'Exo 2',sans-serif;padding-top:var(--nav-height);min-height:100vh}
</style>
<style>
.container{max-width:900px;margin:0 auto;padding:2rem}
h1{font-family:'Orbitron',sans-serif;font-size:2.5rem;color:#fff;text-align:center;margin-bottom:0.5rem;text-shadow:var(--glow)}
.subtitle{text-align:center;color:var(--primary-blue);font-size:1.2rem;margin-bottom:3rem}
.contact-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:2rem;margin-bottom:3rem}
.contact-card{background:var(--carbon-dark);border:1px solid #333;border-radius:12px;padding:2rem;text-align:center;transition:all 0.3s}
.contact-card:hover{border-color:var(--primary-blue);box-shadow:var(--glow)}
.contact-card i{font-size:2.5rem;color:var(--primary-blue);margin-bottom:1rem}
.contact-card h3{font-family:'Orbitron',sans-serif;font-size:1.2rem;color:#fff;margin-bottom:0.5rem}
.contact-card p{color:#888;margin-bottom:1rem;font-size:0.95rem}
.contact-card a{color:var(--neon-blue);text-decoration:none;font-weight:600}
.contact-card a:hover{color:var(--primary-blue)}
h2{font-family:'Orbitron',sans-serif;font-size:1.5rem;color:#fff;margin:2rem 0 1.5rem;text-align:center}

/* Contact Form */
.contact-form-section{background:var(--carbon-dark);border-radius:12px;padding:2rem;margin-bottom:3rem;border:1px solid #333}
.contact-form{max-width:600px;margin:0 auto}
.form-group{margin-bottom:1.5rem}
.form-group label{display:block;color:#fff;font-family:'Rajdhani',sans-serif;font-weight:600;margin-bottom:0.5rem;text-transform:uppercase;letter-spacing:1px;font-size:0.9rem}
.form-group input,.form-group textarea,.form-group select{width:100%;padding:0.75rem 1rem;background:var(--carbon);border:1px solid #444;border-radius:4px;color:#fff;font-family:'Exo 2',sans-serif;font-size:1rem;transition:border-color 0.3s}
.form-group input:focus,.form-group textarea:focus,.form-group select:focus{outline:none;border-color:var(--primary-blue);box-shadow:0 0 10px rgba(0,191,255,0.2)}
.form-group textarea{min-height:150px;resize:vertical}
.btn{display:inline-block;background:linear-gradient(135deg,var(--primary-blue),var(--electric-blue));color:var(--carbon);padding:0.8rem 2rem;border-radius:4px;text-decoration:none;font-family:'Rajdhani',sans-serif;font-weight:700;text-transform:uppercase;letter-spacing:2px;transition:all 0.3s;border:none;cursor:pointer;font-size:1rem}
.btn:hover{transform:translateY(-3px);box-shadow:var(--glow)}
.form-note{text-align:center;color:#888;font-size:0.85rem;margin-top:1rem}

/* Social Grid */
.social-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:1rem;margin-bottom:3rem}
.social-card{background:var(--carbon-dark);border:1px solid #333;border-radius:8px;padding:1.5rem;text-align:center;transition:all 0.3s;text-decoration:none}
.social-card:hover{border-color:var(--primary-blue);transform:translateY(-3px);box-shadow:var(--glow)}
.social-card i{font-size:2rem;margin-bottom:0.5rem;display:block}
.social-card span{color:#fff;font-family:'Rajdhani',sans-serif;font-weight:600;font-size:0.9rem}
.social-card.discord i{color:#5865F2}
.social-card.youtube i{color:#FF0000}

.social-card.instagram i{color:#E4405F}
.social-card.facebook i{color:#1877F2}
.social-card.twitter i{color:#fff}
.social-card.substack i{color:#FF6719}
.social-card.spotify i{color:#1DB954}
.social-card.soundcloud i{color:#FF5500}
</style>
</head>
<body>
<?php $current_page = 'contact'; include('includes/nav.php'); ?>
<div class="container">
<h1>CONTACT</h1>
<p class="subtitle">Get in Touch with OD9</p>

<div class="contact-grid">
<div class="contact-card">
<i class="fab fa-discord"></i>
<h3>Discord</h3>
<p>Join our community for direct access</p>
<a href="https://discord.gg/spgmrXVMWq" target="_blank">Join Server</a>
</div>

<div class="contact-card">
<i class="fas fa-envelope"></i>
<h3>Email</h3>
<p>Business inquiries & collaborations</p>
<a href="mailto:contact@offda9.com">contact@offda9.com</a>
</div>

<a href="https://www.twitch.tv/theultimaterage" target="_blank" class="contact-card"><i class="fab fa-twitch"></i><h3>Twitch</h3><p>Live NCZ streams and community interaction</p></a>
</div>

<h2>SEND A MESSAGE</h2>
<div class="contact-form-section">
<form class="contact-form" action="contact-handler.php" method="POST">
<div class="form-group">
<label for="name">Name</label>
<input type="text" id="name" name="name" required placeholder="Your name">
</div>
<div class="form-group">
<label for="email">Email</label>
<input type="email" id="email" name="email" required placeholder="your@email.com">
</div>
<div class="form-group">
<label for="subject">Subject</label>
<select id="subject" name="subject" required>
<option value="">Select a topic...</option>
<option value="general">General Inquiry</option>
<option value="collab">Collaboration / Features</option>
<option value="booking">Booking / Events</option>
<option value="press">Press / Media</option>
<option value="support">Technical Support</option>
<option value="other">Other</option>
</select>
</div>
<div class="form-group">
<label for="message">Message</label>
<textarea id="message" name="message" required placeholder="What's on your mind?"></textarea>
</div>
<button type="submit" class="btn"><i class="fas fa-paper-plane" style="margin-right:0.5rem"></i> Send Message</button>
<p class="form-note">We typically respond within 24-48 hours.</p>
</form>
</div>

<h2>OFFICIAL OD9 CHANNELS</h2>
<div class="social-grid">
<a href="https://discord.gg/spgmrXVMWq" target="_blank" class="social-card discord">
<i class="fab fa-discord"></i>
<span>Discord</span>
</a>
<a href="https://www.youtube.com/@OffDa9" target="_blank" class="social-card youtube">
<i class="fab fa-youtube"></i>
<span>YouTube</span>
</a>
<a href="https://theultimaterage.substack.com" target="_blank" class="social-card substack">
<i class="fas fa-newspaper"></i>
<span>Substack</span>
</a>
</div>

<h2>THE ULTIMATE RAGE</h2>
<div class="social-grid">
<a href="https://instagram.com/theultimaterage" target="_blank" class="social-card instagram">
<i class="fab fa-instagram"></i>
<span>Instagram</span>
</a>
<a href="https://www.twitch.tv/theultimaterage" target="_blank" class="social-card twitch">
<i class="fab fa-twitch"></i>
<span>Twitch</span>
</a>
<a href="https://youtube.com/@theultimaterage" target="_blank" class="social-card youtube">
<i class="fab fa-youtube"></i>
<span>YouTube</span>
</a>
<a href="https://open.spotify.com/artist/0QvH8H7obaMerk1UkfFGaD" target="_blank" class="social-card spotify">
<i class="fab fa-spotify"></i>
<span>Spotify</span>
</a>
<a href="https://soundcloud.com/theultimaterage" target="_blank" class="social-card soundcloud">
<i class="fab fa-soundcloud"></i>
<span>SoundCloud</span>
</a>
<a href="https://facebook.com/theultimaterage" target="_blank" class="social-card facebook">
<i class="fab fa-facebook"></i>
<span>Facebook</span>
</a>
<a href="https://twitter.com/theultimat63157" target="_blank" class="social-card twitter">
<i class="fab fa-x-twitter"></i>
<span>X / Twitter</span>
</a>
</div>

</div>
<?php include('includes/footer.php'); ?>
</body>
</html>

