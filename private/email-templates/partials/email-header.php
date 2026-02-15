<?php
/**
 * OD9 Email Header - Blue/Chrome theme
 * Uses OD9 branding: primary blue #00BFFF, carbon black #0D0D0D, chrome #C0C0C0
 */
$_brand = 'OD9 - Off Da Nine';
$_primary = '#00BFFF';
$_logo = 'https://offda9.com/images/logos/od9-logo.png';
$_domain = 'offda9.com';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
body{margin:0;padding:0;font-family:Arial,Helvetica,sans-serif;background-color:#0a0a0a;color:#C0C0C0}
.email-container{max-width:600px;margin:0 auto;background-color:#0D0D0D}
.header{background:linear-gradient(135deg,#0D0D0D,#1A1A1A);padding:40px 20px;text-align:center;border-bottom:3px solid #00BFFF}
.logo-image{max-width:150px;height:auto;margin:0 auto 15px;display:block}
.logo-text{font-size:28px;font-weight:bold;color:#00BFFF;letter-spacing:4px;margin:0;text-transform:uppercase}
.tagline{font-size:12px;color:#C0C0C0;letter-spacing:3px;text-transform:uppercase;margin-top:8px}
.content{padding:30px 20px}
h2{color:#00BFFF;margin:0 0 15px;font-size:22px}
h3{color:#00BFFF;margin:20px 0 10px;font-size:16px}
p{color:#C0C0C0;line-height:1.7;margin:0 0 15px;font-size:15px}
a{color:#00BFFF}
.btn{display:inline-block;padding:14px 32px;background:#00BFFF;color:#0D0D0D !important;text-decoration:none;border-radius:4px;font-weight:bold;font-size:14px;letter-spacing:1px;text-transform:uppercase}
.btn:hover{background:#00A0FF}
.card{background:#1A1A1A;border:1px solid rgba(0,191,255,0.2);border-radius:8px;padding:20px;margin:15px 0}
.tier-badge{display:inline-block;padding:4px 12px;border-radius:12px;font-size:12px;font-weight:bold;letter-spacing:1px;text-transform:uppercase}
.tier-observer{background:#808080;color:#fff}
.tier-theorist{background:#4169E1;color:#fff}
.tier-architect{background:#9932CC;color:#fff}
.tier-pioneer{background:#FFD700;color:#000}
.tier-benefactor{background:#E74C3C;color:#fff}
.stat{display:inline-block;text-align:center;padding:10px 20px}
.stat-num{font-size:24px;font-weight:bold;color:#00BFFF;display:block}
.stat-label{font-size:11px;color:#666;text-transform:uppercase;letter-spacing:1px}
.divider{border:none;border-top:1px solid rgba(0,191,255,0.15);margin:25px 0}
</style>
</head>
<body>
<div class="email-container">
<div class="header">
<img src="<?= htmlspecialchars($_logo) ?>" alt="OD9" class="logo-image">
<h1 class="logo-text">OFF DA NINE</h1>
<p class="tagline">Advancing Humanity Toward Type I Civilization</p>
</div>
<div class="content">
