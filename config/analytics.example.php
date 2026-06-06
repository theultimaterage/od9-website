<?php
/**
 * OD9 Web Analytics — Plausible config (T-INF-WEB-ANALYTICS-001).
 *
 * Copy this file to `analytics.php` in this dir and uncomment the define
 * to activate Plausible tracking on offda9.com. Footer.php conditionally
 * loads + renders the snippet only when PLAUSIBLE_DOMAIN is defined.
 *
 * Setup steps (one-time):
 *   1. Sign up at https://plausible.io/register
 *   2. Add `offda9.com` as a site
 *   3. Verify ownership via the snippet method (Plausible will tell you the
 *      data-domain value to use — usually just 'offda9.com')
 *   4. Define PLAUSIBLE_DOMAIN below to that value
 *   5. Deploy: /sync-local + /deploy-web
 *   6. Wait ~30 sec, refresh offda9.com, check Plausible dashboard for the
 *      first pageview
 *
 * For self-hosted Plausible, also override PLAUSIBLE_SCRIPT_URL.
 */

// Uncomment to activate:
// define('PLAUSIBLE_DOMAIN', 'offda9.com');

// Optional override for self-hosted Plausible. Default uses cloud.
// define('PLAUSIBLE_SCRIPT_URL', 'https://plausible.io/js/script.js');
