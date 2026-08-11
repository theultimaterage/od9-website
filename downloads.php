<?php
$page_title = 'OD9 Downloads | Wallpapers, Lockscreens & Widgets';
$page_description = 'Free OD9 wallpapers, lockscreens, and phone widgets. Diamond chrome logo with blue neon glow on carbon fiber. iPhone and Android.';
$page_slug = 'downloads.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include __DIR__ . '/includes/head.php'; ?>
<style>
body{background:var(--d);background-image:linear-gradient(45deg,#111 25%,transparent 25%),linear-gradient(-45deg,#111 25%,transparent 25%),linear-gradient(45deg,transparent 75%,#111 75%),linear-gradient(-45deg,transparent 75%,#111 75%);background-size:4px 4px;color:var(--c);font-family:'Exo 2',sans-serif}
.container{max-width:1200px;margin:0 auto;padding:2rem}.hero,.section{background:var(--dd);border:1px solid #2a2a2a;border-radius:16px;padding:2rem;margin-bottom:1.5rem}.hero{background:linear-gradient(135deg,rgba(0,191,255,.13),rgba(0,0,0,.72));box-shadow:var(--g);text-align:center}h1,h2,h3{font-family:'Orbitron',sans-serif;color:#fff}h1{font-size:2.5rem;margin-bottom:.75rem}p{line-height:1.75;color:#bcbcbc}.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:1.25rem}.card{background:#121212;border:1px solid #2a2a2a;border-radius:14px;padding:1.2rem;text-align:center}.card img{width:100%;aspect-ratio:1/1;object-fit:contain;border-radius:12px;margin-bottom:1rem;border:1px solid rgba(0,191,255,.15)}.card h3{margin-bottom:.5rem;font-size:1rem}.btn{display:inline-block;background:linear-gradient(135deg,var(--b),#00A0FF);color:#0D0D0D;padding:.7rem 1.2rem;border-radius:5px;text-decoration:none;font-family:'Rajdhani',sans-serif;font-weight:700;letter-spacing:1px;text-transform:uppercase;margin-top:.75rem;font-size:.85rem}.btn:hover{transform:translateY(-2px);box-shadow:var(--g)}.footer{margin-top:3rem;padding:2rem 0;border-top:1px solid #333;text-align:center}.footer a{color:#00BFFF;text-decoration:none;margin:0 .6rem}
</style>
</head>
<body>
<?php $current_page = 'downloads'; include('includes/nav.php'); ?>

<div class="container">

<section class="hero">
<h1>DOWNLOADS</h1>
<p>OD9 wallpapers, lockscreens, and widgets. Diamond chrome on carbon fiber. Free. Put the movement on your screen.</p>
</section>

<section class="section">
<h2>WALLPAPER & LOCKSCREEN</h2>
<div class="grid">
<div class="card">
<img width="768" height="1376" src="images/downloads/od9-wallpaper.png" alt="OD9 Wallpaper - Diamond Chrome Logo">
<h3>OD9 WALLPAPER</h3>
<p>Desktop and phone background. Diamond chrome OD9 logo with blue neon glow on carbon fiber.</p>
<a href="images/downloads/od9-wallpaper.png" download class="btn"><i class="fas fa-download"></i> Download</a>
</div>
<div class="card">
<img width="768" height="1376" src="images/downloads/od9-lockscreen.png" alt="OD9 Lockscreen">
<h3>OD9 LOCKSCREEN</h3>
<p>Lock screen with the OD9 chrome logo, time display, and OFF DA NINE branding.</p>
<a href="images/downloads/od9-lockscreen.png" download class="btn"><i class="fas fa-download"></i> Download</a>
</div>
</div>
</section>

<section class="section">
<h2>iPHONE WIDGETS</h2>
<div class="grid">
<div class="card">
<img width="1024" height="1024" src="images/downloads/od9-iphone-widget-1.png" alt="OD9 iPhone Widget 1">
<h3>iPHONE WIDGET 1</h3>
<a href="images/downloads/od9-iphone-widget-1.png" download class="btn"><i class="fas fa-download"></i> Download</a>
</div>
<div class="card">
<img width="1376" height="768" src="images/downloads/od9-iphone-widget-2.png" alt="OD9 iPhone Widget 2">
<h3>iPHONE WIDGET 2</h3>
<a href="images/downloads/od9-iphone-widget-2.png" download class="btn"><i class="fas fa-download"></i> Download</a>
</div>
<div class="card">
<img width="1408" height="768" src="images/downloads/od9-iphone-widget-3.png" alt="OD9 iPhone Widget 3">
<h3>iPHONE WIDGET 3</h3>
<a href="images/downloads/od9-iphone-widget-3.png" download class="btn"><i class="fas fa-download"></i> Download</a>
</div>
</div>
</section>

<section class="section">
<h2>ANDROID WIDGETS</h2>
<div class="grid">
<div class="card">
<img width="1408" height="768" src="images/downloads/od9-android-widget-1.png" alt="OD9 Android Widget 1">
<h3>ANDROID WIDGET 1</h3>
<a href="images/downloads/od9-android-widget-1.png" download class="btn"><i class="fas fa-download"></i> Download</a>
</div>
<div class="card">
<img width="1024" height="1024" src="images/downloads/od9-android-widget-2.png" alt="OD9 Android Widget 2">
<h3>ANDROID WIDGET 2</h3>
<a href="images/downloads/od9-android-widget-2.png" download class="btn"><i class="fas fa-download"></i> Download</a>
</div>
<div class="card">
<img width="1456" height="720" src="images/downloads/od9-android-widget-3.png" alt="OD9 Android Widget 3">
<h3>ANDROID WIDGET 3</h3>
<a href="images/downloads/od9-android-widget-3.png" download class="btn"><i class="fas fa-download"></i> Download</a>
</div>
<div class="card">
<img width="1024" height="1024" src="images/downloads/od9-android-widget-4.png" alt="OD9 Android Widget 4">
<h3>ANDROID WIDGET 4</h3>
<a href="images/downloads/od9-android-widget-4.png" download class="btn"><i class="fas fa-download"></i> Download</a>
</div>
</div>
</section>

</div>

<?php include('includes/footer.php'); ?>
</body>
</html>