<?php $current_page = $current_page ?? basename($_SERVER["PHP_SELF"], ".php"); ?>
<nav class="od9-nav"><div class="nav-container">
<a href="index.php" class="nav-logo"><img src="images/logos/od9-logo.png" alt="OD9"><span class="nav-logo-text">OD9</span></a>
<ul class="nav-menu">
<li><a href="index.php" class="nav-link <?= $current_page=="index"?"active":"" ?>">Home</a></li>
<li><a href="framework.php" class="nav-link <?= $current_page=="framework"?"active":"" ?>">Framework</a></li>
<li><a href="tiers.php" class="nav-link <?= $current_page=="tiers"?"active":"" ?>">Tiers</a></li>
<li><a href="da-crew.php" class="nav-link <?= $current_page=="da-crew"?"active":"" ?>">Da Crew</a></li>
<li><a href="music.php" class="nav-link <?= $current_page=="music"?"active":"" ?>">Music</a></li>
<li><a href="resources.php" class="nav-link <?= $current_page=="resources"?"active":"" ?>">Resources</a></li>
<li><a href="support.php" class="nav-link <?= $current_page=="support"?"active":"" ?>">Support</a></li>
<li><a href="contact.php" class="nav-link <?= $current_page=="contact"?"active":"" ?>">Contact</a></li>
<li><a href="https://discord.gg/spgmrXVMWq" target="_blank" class="nav-btn"><i class="fab fa-discord"></i> Discord</a></li>
</ul>
<button class="mobile-toggle" id="hamburger"><span></span><span></span><span></span></button>
</div></nav>
<div class="mobile-menu" id="mobileMenu">
<a href="index.php" class="<?= $current_page=="index"?"active":"" ?>">Home</a>
<a href="framework.php" class="<?= $current_page=="framework"?"active":"" ?>">Framework</a>
<a href="tiers.php" class="<?= $current_page=="tiers"?"active":"" ?>">Tiers</a>
<a href="da-crew.php" class="<?= $current_page=="da-crew"?"active":"" ?>">Da Crew</a>
<a href="music.php" class="<?= $current_page=="music"?"active":"" ?>">Music</a>
<a href="resources.php" class="<?= $current_page=="resources"?"active":"" ?>">Resources</a>
<a href="support.php" class="<?= $current_page=="support"?"active":"" ?>">Support</a>
<a href="contact.php" class="<?= $current_page=="contact"?"active":"" ?>">Contact</a>
<a href="https://discord.gg/spgmrXVMWq" target="_blank" class="mobile-discord"><i class="fab fa-discord"></i> Join Discord</a>
</div>
<script>
document.getElementById("hamburger").addEventListener("click", function() {
    this.classList.toggle("active");
    document.getElementById("mobileMenu").classList.toggle("active");
});
</script>
