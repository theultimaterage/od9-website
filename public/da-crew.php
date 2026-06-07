<?php
$page_title = 'Da Crew - OD9 Music Collective | Meet the Artists';
$page_description = 'Meet Da Crew behind OD9\'s music collective. Artists using hip-hop and music as tools for consciousness shifting, culture building, and advancing humanity toward Type I civilization.';
$page_slug = 'da-crew.php';
$page_og_title = 'Da Crew - OD9 Music Collective';
$page_og_description = 'Meet the artists behind OD9. Music as a tool for consciousness shifting and advancing humanity.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include __DIR__ . '/includes/head.php'; ?>
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@graph": [
    {
      "@type": "WebPage",
      "@id": "https://offda9.com/da-crew.php",
      "url": "https://offda9.com/da-crew.php",
      "name": "Da Crew - OD9 Music Collective | Meet the Artists",
      "description": "Meet Da Crew behind OD9's music collective. Artists using hip-hop and music as tools for consciousness shifting, culture building, and advancing humanity.",
      "isPartOf": {"@id": "https://offda9.com/#website"},
      "mainEntity": {"@id": "https://offda9.com/da-crew.php#roster"}
    },
    {
      "@type": "ItemList",
      "@id": "https://offda9.com/da-crew.php#roster",
      "name": "OD9 Music Collective Roster",
      "numberOfItems": 5,
      "itemListElement": [
        {
          "@type": "ListItem",
          "position": 1,
          "item": {
            "@type": "Person",
            "@id": "https://offda9.com/da-crew.php#rage",
            "name": "The Ultimate Rage",
            "jobTitle": "Co-Founder / Producer / Vocalist / Technologist",
            "image": "https://offda9.com/images/crew/rage.png",
            "memberOf": {"@id": "https://offda9.com/#organization"},
            "sameAs": [
              "https://instagram.com/theultimaterage",
"https://youtube.com/@theultimaterage",
              "https://open.spotify.com/artist/0QvH8H7obaMerk1UkfFGaD",
              "https://soundcloud.com/theultimaterage",
              "https://facebook.com/theultimaterage",
              "https://twitter.com/theultimat63157",
              "https://theultimaterage.substack.com"
            ]
          }
        },
        {
          "@type": "ListItem",
          "position": 2,
          "item": {
            "@type": "Person",
            "@id": "https://offda9.com/da-crew.php#deezle",
            "name": "Deezle Deez",
            "jobTitle": "Co-Founder / Producer / Vocalist",
            "image": "https://offda9.com/images/crew/deez.jpg",
            "memberOf": {"@id": "https://offda9.com/#organization"},
            "sameAs": [
              "https://instagram.com/deezle_deez",
              "https://facebook.com/deezle.deez"
            ]
          }
        },
        {
          "@type": "ListItem",
          "position": 3,
          "item": {
            "@type": "Person",
            "@id": "https://offda9.com/da-crew.php#p-mac",
            "name": "P-Mac",
            "jobTitle": "Artist",
            "image": "https://offda9.com/images/crew/p-mac.png",
            "memberOf": {"@id": "https://offda9.com/#organization"},
            "sameAs": [
              "https://instagram.com/perryjay22",
              "https://facebook.com/perryjayjayr"
            ]
          }
        },
        {
          "@type": "ListItem",
          "position": 4,
          "item": {
            "@type": "Person",
            "@id": "https://offda9.com/da-crew.php#lb",
            "name": "L.B.",
            "jobTitle": "Artist",
            "image": "https://offda9.com/images/crew/lb.jpg",
            "memberOf": {"@id": "https://offda9.com/#organization"},
            "sameAs": [
              "https://instagram.com/iambilla",
"https://facebook.com/lb079"
            ]
          }
        },
        {
          "@type": "ListItem",
          "position": 5,
          "item": {
            "@type": "Person",
            "@id": "https://offda9.com/da-crew.php#joey-p",
            "name": "Joey P.",
            "jobTitle": "Artist",
            "image": "https://offda9.com/images/crew/joey-p.jpg",
            "memberOf": {"@id": "https://offda9.com/#organization"},
            "sameAs": [
              "https://instagram.com/jpacius_sm",
              "https://youtube.com/@joeyp.",
              "https://open.spotify.com/artist/2a1dff70K0i5OZXBkYQOzZ",
              "https://facebook.com/young.joseph.307539"
            ]
          }
        }
      ]
    }
  ]
}
</script>
<style>
body{background:var(--carbon);background-image:linear-gradient(45deg,#111 25%,transparent 25%),linear-gradient(-45deg,#111 25%,transparent 25%),linear-gradient(45deg,transparent 75%,#111 75%),linear-gradient(-45deg,transparent 75%,#111 75%);background-size:4px 4px;color:var(--chrome);font-family:'Exo 2',sans-serif;padding-top:var(--nav-height);min-height:100vh}
</style>
<style>
.container{max-width:1100px;margin:0 auto;padding:2rem}
h1{font-family:'Orbitron',sans-serif;font-size:3rem;color:#fff;text-align:center;margin-bottom:0.5rem;text-shadow:var(--glow)}
.subtitle{text-align:center;color:var(--primary-blue);font-size:1.3rem;margin-bottom:3rem;font-family:'Rajdhani',sans-serif;letter-spacing:3px}
.intro{background:var(--carbon-dark);border:1px solid var(--primary-blue);border-radius:12px;padding:2.5rem;margin-bottom:3rem;text-align:center}
.intro p{font-size:1.15rem;line-height:1.9;max-width:800px;margin:0 auto}
h2{font-family:'Orbitron',sans-serif;font-size:1.8rem;color:#fff;margin:3rem 0 1.5rem;text-align:center;text-shadow:var(--glow)}
.members-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1.5rem;margin-bottom:3rem}
.member-card{background:var(--carbon-dark);border:1px solid #333;border-radius:12px;overflow:hidden;transition:all 0.3s}
.member-card:hover{border-color:var(--primary-blue);transform:translateY(-5px);box-shadow:var(--glow)}
.member-card.founder{border-color:var(--primary-blue)}
.member-photo{width:100%;height:220px;background:linear-gradient(135deg,var(--carbon-dark),#222);overflow:hidden}
.member-photo img{width:100%;height:100%;object-fit:cover;object-position:center top;transition:transform 0.3s}
.member-card:hover .member-photo img{transform:scale(1.05)}
.member-info{padding:1.25rem;text-align:center}
.member-info h3{font-family:'Orbitron',sans-serif;font-size:1.2rem;color:var(--primary-blue);margin-bottom:0.3rem}
.member-info .role{font-family:'Rajdhani',sans-serif;color:var(--neon-blue);text-transform:uppercase;letter-spacing:2px;font-size:0.85rem;margin-bottom:0.75rem}
.member-card.founder .role{color:#FFD700}
.social-links{display:flex;justify-content:center;gap:0.5rem;flex-wrap:wrap}
.social-links a{color:#888;font-size:1.1rem;transition:color 0.3s;padding:0.25rem}
.social-links a:hover{color:var(--primary-blue)}
.cta-section{text-align:center;margin:3rem 0;padding:2rem;background:linear-gradient(135deg,rgba(0,191,255,0.1),transparent);border-radius:12px;border:1px solid var(--primary-blue)}
.cta-section h3{font-family:'Orbitron',sans-serif;color:#fff;margin-bottom:1rem}
.btn{display:inline-block;background:linear-gradient(135deg,var(--primary-blue),var(--electric-blue));color:var(--carbon);padding:0.8rem 2rem;border-radius:4px;text-decoration:none;font-family:'Rajdhani',sans-serif;font-weight:700;text-transform:uppercase;letter-spacing:2px;transition:all 0.3s}
.btn:hover{transform:translateY(-3px);box-shadow:var(--glow)}
.music-section{margin:3rem 0}
.ep-promo{display:grid;grid-template-columns:1fr 1fr;gap:2rem;align-items:center;background:var(--carbon-dark);border-radius:12px;padding:2rem;border:1px solid var(--primary-blue);margin-bottom:2rem}
.ep-promo video{width:100%;border-radius:8px;box-shadow:var(--glow)}
.ep-info{text-align:center}
.ep-info h3{font-family:'Orbitron',sans-serif;font-size:2rem;color:#fff;margin-bottom:0.5rem;text-shadow:var(--glow)}
.ep-info .coming-soon-badge{display:inline-block;background:linear-gradient(135deg,var(--primary-blue),var(--electric-blue));color:var(--carbon);padding:0.5rem 1.5rem;border-radius:20px;font-family:'Rajdhani',sans-serif;font-weight:700;text-transform:uppercase;letter-spacing:2px;margin-top:1rem;animation:pulse 2s infinite}
@keyframes pulse{0%,100%{box-shadow:0 0 10px rgba(0,191,255,0.5)}50%{box-shadow:0 0 25px rgba(0,191,255,0.8)}}
.music-videos{display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:1.5rem}
.video-card{background:var(--carbon-dark);border:1px solid #333;border-radius:12px;overflow:hidden;transition:all 0.3s}
.video-card:hover{border-color:var(--primary-blue);transform:translateY(-5px);box-shadow:var(--glow)}
.video-thumb{width:100%;aspect-ratio:16/9;object-fit:cover;display:block}
.video-info{padding:1.25rem;text-align:center}
.video-info h4{font-family:'Orbitron',sans-serif;font-size:1.3rem;color:#fff;margin-bottom:0.5rem}
.video-info .status{font-family:'Rajdhani',sans-serif;color:var(--neon-blue);text-transform:uppercase;letter-spacing:2px;font-size:0.9rem}
@media(max-width:768px){.ep-promo{grid-template-columns:1fr}}
</style>
</head>
<body>
<?php $current_page = 'da-crew'; include('includes/nav.php'); ?>
<div class="container">
<h1>DA CREW</h1>
<p class="subtitle">OD9 Music Collective</p>

<div class="intro">
<p>OD9 isn't just a movement for human advancement - it's also a music collective bringing raw, unfiltered sound from the streets of Chicago. We blend futuristic vision with authentic expression, creating music that hits different.</p>
</div>

<h2>THE ROSTER</h2>
<div class="members-grid">

<div class="member-card founder">
<div class="member-photo"><img width="945" height="945" src="images/crew/rage.png" alt="The Ultimate Rage"></div>
<div class="member-info">
<h3>The Ultimate Rage</h3>
<div class="role">Co-Founder</div>
<div class="social-links">
<a href="https://instagram.com/theultimaterage" target="_blank" title="Instagram"><i class="fab fa-instagram"></i></a>
<a href="https://www.twitch.tv/theultimaterage" target="_blank" title="Twitch"><i class="fab fa-twitch"></i></a>
<a href="https://youtube.com/@theultimaterage" target="_blank" title="YouTube"><i class="fab fa-youtube"></i></a>
<a href="https://open.spotify.com/artist/0QvH8H7obaMerk1UkfFGaD" target="_blank" title="Spotify"><i class="fab fa-spotify"></i></a>
<a href="https://soundcloud.com/theultimaterage" target="_blank" title="SoundCloud"><i class="fab fa-soundcloud"></i></a>
<a href="https://facebook.com/theultimaterage" target="_blank" title="Facebook"><i class="fab fa-facebook"></i></a>
<a href="https://twitter.com/theultimat63157" target="_blank" title="X/Twitter"><i class="fab fa-x-twitter"></i></a>
<a href="https://theultimaterage.substack.com" target="_blank" title="Substack"><i class="fas fa-newspaper"></i></a>
</div>
</div>
</div>

<div class="member-card founder">
<div class="member-photo"><img width="816" height="801" src="images/crew/deez.jpg" alt="Deezle Deez"></div>
<div class="member-info">
<h3>Deezle Deez</h3>
<div class="role">Co-Founder</div>
<div class="social-links">
<a href="https://instagram.com/deezle_deez" target="_blank" title="Instagram"><i class="fab fa-instagram"></i></a>
<a href="https://facebook.com/deezle.deez" target="_blank" title="Facebook"><i class="fab fa-facebook"></i></a>
</div>
</div>
</div>

<div class="member-card">
<div class="member-photo"><img width="2940" height="1922" src="images/crew/p-mac.png" alt="P-Mac"></div>
<div class="member-info">
<h3>P-Mac</h3>
<div class="role">Artist</div>
<div class="social-links">
<a href="https://instagram.com/perryjay22" target="_blank" title="Instagram"><i class="fab fa-instagram"></i></a>
<a href="https://facebook.com/perryjayjayr" target="_blank" title="Facebook"><i class="fab fa-facebook"></i></a>
</div>
</div>
</div>

<div class="member-card">
<div class="member-photo"><img width="1080" height="1080" src="images/crew/lb.jpg" alt="L.B."></div>
<div class="member-info">
<h3>L.B.</h3>
<div class="role">Artist</div>
<div class="social-links">
<a href="https://instagram.com/iambilla" target="_blank" title="Instagram"><i class="fab fa-instagram"></i></a>
<a href="https://facebook.com/lb079" target="_blank" title="Facebook"><i class="fab fa-facebook"></i></a>
</div>
</div>
</div>

<div class="member-card">
<div class="member-photo"><img width="1280" height="1280" src="images/crew/joey-p.jpg" alt="Joey P."></div>
<div class="member-info">
<h3>Joey P.</h3>
<div class="role">Artist</div>
<div class="social-links">
<a href="https://instagram.com/jpacius_sm" target="_blank" title="Instagram"><i class="fab fa-instagram"></i></a>
<a href="https://youtube.com/@joeyp." target="_blank" title="YouTube"><i class="fab fa-youtube"></i></a>
<a href="https://open.spotify.com/artist/2a1dff70K0i5OZXBkYQOzZ" target="_blank" title="Spotify"><i class="fab fa-spotify"></i></a>
<a href="https://facebook.com/young.joseph.307539" target="_blank" title="Facebook"><i class="fab fa-facebook"></i></a>
</div>
</div>
</div>

</div>

<h2>MUSIC</h2>
<div class="music-section">
<div class="ep-promo">
<video autoplay loop muted playsinline>
<source src="images/music/supreme-elevation-promo.mp4" type="video/mp4">
</video>
<div class="ep-info">
<h3>SUPREME ELEVATION</h3>
<p style="color:#aaa;margin-bottom:1rem;line-height:1.6">The collective speaks. The first full-crew project from OD9 - every voice, every perspective, one coordinated strike.</p>
<span class="coming-soon-badge">Coming Soon</span>
</div>
</div>

<h3 style="font-family:'Orbitron',sans-serif;color:#fff;text-align:center;margin:2rem 0 1.5rem">MUSIC VIDEOS</h3>
<div class="music-videos">
<div class="video-card">
<img width="3823" height="1890" src="images/music/supreme-screenshot.png" alt="Supreme" class="video-thumb">
<div class="video-info">
<h4>SUPREME</h4>
<div class="status">Music Video Coming Soon</div>
</div>
</div>

<div class="video-card">
<img width="3701" height="2002" src="images/music/hwul-screenshot.png" alt="HWUL" class="video-thumb">
<div class="video-info">
<h4>HWUL</h4>
<div class="status">Music Video Coming Soon</div>
</div>
</div>
</div>
</div>

<div class="cta-section">
<h3>CONNECT WITH DA CREW</h3>
<p style="margin-bottom:1.5rem;color:#888">Join the Discord to stay updated on new music and events</p>
<a href="https://discord.gg/spgmrXVMWq" target="_blank" class="btn"><i class="fab fa-discord" style="margin-right:0.5rem"></i> Join Discord</a>
</div>

</div>
<?php include('includes/footer.php'); ?>
</body>
</html>
