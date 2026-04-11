<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@400;600;700&family=Exo+2:wght@400;600&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
:root{--primary-blue:#00BFFF;--electric-blue:#00A0FF;--neon-blue:#00D4FF;--chrome:#C0C0C0;--carbon:#0D0D0D;--carbon-dark:#1A1A1A;--glow:0 0 20px rgba(0,191,255,0.5);--nav-height:80px}
*{margin:0;padding:0;box-sizing:border-box}
body{background:var(--carbon);background-image:linear-gradient(45deg,#111 25%,transparent 25%),linear-gradient(-45deg,#111 25%,transparent 25%),linear-gradient(45deg,transparent 75%,#111 75%),linear-gradient(-45deg,transparent 75%,#111 75%);background-size:4px 4px;color:var(--chrome);font-family:'Exo 2',sans-serif;padding-top:var(--nav-height);min-height:100vh}
.od9-nav{position:fixed;top:0;left:0;width:100%;height:var(--nav-height);background:linear-gradient(180deg,rgba(13,13,13,0.98) 0%,rgba(26,26,26,0.95) 100%);backdrop-filter:blur(20px);border-bottom:2px solid var(--primary-blue);box-shadow:var(--glow);z-index:9999}
.nav-container{max-width:1200px;margin:0 auto;padding:0 2rem;height:100%;display:flex;justify-content:space-between;align-items:center}
.nav-logo{display:flex;align-items:center;text-decoration:none}
.nav-logo img{height:50px;margin-right:0.75rem;filter:drop-shadow(var(--glow))}
.nav-logo-text{font-family:'Orbitron',sans-serif;font-size:1.5rem;font-weight:700;color:var(--primary-blue);letter-spacing:3px;text-shadow:var(--glow)}
.nav-menu{display:flex;list-style:none;gap:1.5rem;align-items:center}
.nav-link{color:var(--chrome);text-decoration:none;font-family:'Rajdhani',sans-serif;font-size:1rem;font-weight:600;letter-spacing:1px;text-transform:uppercase;transition:all 0.3s;padding:0.5rem 0;position:relative}
.nav-link::after{content:'';position:absolute;bottom:0;left:50%;width:0;height:2px;background:var(--primary-blue);transition:all 0.3s;transform:translateX(-50%)}
.nav-link:hover,.nav-link.active{color:var(--primary-blue)}
.nav-link:hover::after,.nav-link.active::after{width:100%}
.nav-btn{background:linear-gradient(135deg,var(--primary-blue),var(--electric-blue));color:var(--carbon);padding:0.6rem 1.2rem;border-radius:4px;text-decoration:none;font-family:'Rajdhani',sans-serif;font-weight:700;font-size:0.9rem;text-transform:uppercase;display:flex;align-items:center;gap:0.5rem;transition:all 0.3s;box-shadow:var(--glow)}
.nav-btn:hover{transform:translateY(-2px)}
.mobile-toggle{display:none;background:none;border:none;cursor:pointer;padding:0.5rem;z-index:10001}
.mobile-toggle span{display:block;width:25px;height:3px;background:var(--primary-blue);margin:5px 0;border-radius:2px;transition:all 0.3s}
.mobile-toggle.active span:nth-child(1){transform:rotate(45deg) translate(5px,5px)}
.mobile-toggle.active span:nth-child(2){opacity:0}
.mobile-toggle.active span:nth-child(3){transform:rotate(-45deg) translate(7px,-6px)}
.mobile-menu{display:none;position:fixed;top:var(--nav-height);left:0;width:100%;background:linear-gradient(180deg,rgba(13,13,13,0.98) 0%,rgba(26,26,26,0.98) 100%);backdrop-filter:blur(20px);padding:1rem 0;border-bottom:2px solid var(--primary-blue);box-shadow:var(--glow);z-index:9998}
.mobile-menu.active{display:block}
.mobile-menu a{display:block;color:var(--chrome);text-decoration:none;font-family:'Rajdhani',sans-serif;font-size:1.1rem;font-weight:600;letter-spacing:1px;text-transform:uppercase;padding:1rem 2rem;transition:all 0.3s;border-bottom:1px solid #222}
.mobile-menu a:hover,.mobile-menu a.active{color:var(--primary-blue);background:rgba(0,191,255,0.1)}
.mobile-menu a:last-child{border-bottom:none}
.mobile-menu .mobile-discord{background:linear-gradient(135deg,var(--primary-blue),var(--electric-blue));color:var(--carbon);margin:1rem;border-radius:4px;text-align:center;border-bottom:none}
@media(max-width:900px){.nav-menu{display:none}.mobile-toggle{display:block}}
</style>
