<?php
/**
 * OD9 SEO — Schema.org structured data (T-INF-WEB-SEO-001).
 *
 * Included at the END of includes/head.php, so it emits AFTER each page's
 * <title>, meta description, and Open Graph tags. Adds Organization + WebSite
 * JSON-LD that search engines (Google, Bing, …) use for richer SERP results —
 * sitelinks, knowledge-panel hooks, social-profile tie-ins.
 */
?>
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Organization",
  "name": "OD9 - Off Da Nine",
  "alternateName": "OD9",
  "legalName": "OD9 LLC",
  "url": "https://offda9.com",
  "logo": "https://offda9.com/images/logos/od9-logo.png",
  "description": "A framework for accelerating human progress toward Type I civilization through STEAM optimization, the ASCEND Protocol, collaborative innovation, and the Kardashev Scale.",
  "founder": {
    "@type": "Person",
    "name": "The Ultimate Rage"
  },
  "sameAs": [
    "https://discord.gg/spgmrXVMWq",
    "https://youtube.com/@theultimaterage",
    "https://www.twitch.tv/theultimaterage",
    "https://instagram.com/theultimaterage",
    "https://open.spotify.com/artist/0QvH8H7obaMerk1UkfFGaD",
    "https://www.patreon.com/theultimaterage"
  ]
}
</script>
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "WebSite",
  "name": "OD9 - Off Da Nine",
  "url": "https://offda9.com",
  "description": "OD9 ecosystem: framework, ASCEND progression, music, NCZ media, Discord community.",
  "publisher": {
    "@type": "Organization",
    "name": "OD9 LLC",
    "logo": "https://offda9.com/images/logos/od9-logo.png"
  }
}
</script>
