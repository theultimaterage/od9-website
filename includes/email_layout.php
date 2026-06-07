<?php
declare(strict_types=1);

/**
 * OD9 Shared Email Layout — single source of truth for email chrome.
 *
 * Mirrors the website's shared-chrome model (head.php / nav.php / footer.php):
 * every OD9 email renders through od9_email_layout() so the header (logo),
 * footer (unsubscribe + CAN-SPAM postal address), button, and colors live in
 * exactly one place and can't drift.
 *
 * Markup is the approved email design. Structural colors are set INLINE so a
 * client that strips <style> still renders light-on-dark; the <style> block
 * only adds typography for markdown bodies (the weekly) and the mobile media
 * queries — inline styles on fragment emails always win over it.
 *
 * Asset URLs are absolute (Cloudflare-fronted offda9.com) via
 * OD9_EMAIL_ASSET_BASE, so there is never a relative-path src to "swap before
 * sending" — the layout emits production URLs by construction.
 */

const OD9_EMAIL_ASSET_BASE = 'https://offda9.com/images/email/';
const OD9_EMAIL_SITE_URL   = 'https://offda9.com';
const OD9_EMAIL_ENTITY     = 'Off Da 9 Ent. LLC';
const OD9_EMAIL_POSTAL     = 'Off Da 9 Ent. LLC &middot; 7948 S. Winchester Ave. &middot; Chicago, IL 60620-5308';

/**
 * The one canonical cyan-gradient CTA button (matches the site .nav-btn).
 */
if (!function_exists('od9_email_button')) {
    function od9_email_button(string $label, string $url): string {
        $u = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
        $l = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
        return <<<HTML
<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 4px;"><tr><td align="center" style="border-radius:4px;background:#00BFFF;background:linear-gradient(135deg,#00BFFF 0%,#00A0FF 100%);box-shadow:0 0 20px rgba(0,191,255,0.45);">
<a href="{$u}" style="display:inline-block;padding:14px 30px;font-family:'Rajdhani',Arial,sans-serif;font-size:14px;font-weight:700;letter-spacing:.09em;text-transform:uppercase;color:#0D0D0D;text-decoration:none;white-space:nowrap;">{$l}</a>
</td></tr></table>
HTML;
    }
}

/**
 * Wrap inner card content ($bodyHtml) in the full branded OD9 email shell.
 *
 * $opts:
 *   - title           string  <title> + default preheader
 *   - preheader       string  hidden inbox-preview line
 *   - transmission    string  optional, e.g. "07 / 33" -> "Transmission 07 / 33"
 *   - unsubscribe_url string  defaults to the {{EMAIL}} token form so each
 *                             sender's personalize() resolves it
 */
if (!function_exists('od9_email_layout')) {
    function od9_email_layout(string $bodyHtml, array $opts = []): string {
        $title = htmlspecialchars($opts['title'] ?? 'OD9', ENT_QUOTES, 'UTF-8');
        $unsub = htmlspecialchars(
            $opts['unsubscribe_url'] ?? (OD9_EMAIL_SITE_URL . '/unsubscribe.php?email={{EMAIL}}'),
            ENT_QUOTES,
            'UTF-8'
        );
        $base   = OD9_EMAIL_ASSET_BASE;
        $site   = OD9_EMAIL_SITE_URL;
        $entity = OD9_EMAIL_ENTITY;
        $postal = OD9_EMAIL_POSTAL;
        $year   = date('Y');

        $preheaderBlock = '';
        if (!empty($opts['preheader'])) {
            $ph = htmlspecialchars($opts['preheader'], ENT_QUOTES, 'UTF-8');
            $preheaderBlock = '<div style="display:none;max-height:0;overflow:hidden;mso-hide:all;font-size:1px;line-height:1px;color:#0D0D0D;opacity:0;">'
                . $ph . '&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;</div>';
        }

        $transmission = '';
        if (!empty($opts['transmission'])) {
            $t = htmlspecialchars($opts['transmission'], ENT_QUOTES, 'UTF-8');
            $transmission = '<div style="font-family:\'Rajdhani\',Arial,sans-serif;font-size:11px;font-weight:700;letter-spacing:.22em;color:#00BFFF;text-transform:uppercase;text-align:center;padding-bottom:12px;">Transmission '
                . $t . '</div>';
        }

        return <<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="x-apple-disable-message-reformatting">
<meta name="color-scheme" content="dark">
<meta name="supported-color-schemes" content="dark">
<title>{$title}</title>
<!--[if mso]><noscript><xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml></noscript><![endif]-->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@700&family=Rajdhani:wght@500;600;700&display=swap" rel="stylesheet">
<style>
  body{margin:0;padding:0;width:100%!important;background:#0D0D0D;}
  img{-ms-interpolation-mode:bicubic;}
  .em-card h1,.em-card h2,.em-card h3,.em-card h4{font-family:'Orbitron','Arial Black',Arial,sans-serif;color:#FFFFFF;text-transform:uppercase;line-height:1.2;margin:26px 0 12px;}
  .em-card h1{font-size:25px;}
  .em-card h2{font-size:18px;letter-spacing:.03em;}
  .em-card h3{font-size:15px;letter-spacing:.04em;}
  .em-card p{font-family:'Rajdhani',Arial,sans-serif;font-size:16px;font-weight:500;line-height:1.66;color:#C0C0C0;margin:0 0 18px;}
  .em-card a{color:#00BFFF;text-decoration:underline;}
  .em-card ul{padding-left:22px;margin:0 0 18px;}
  .em-card li{font-family:'Rajdhani',Arial,sans-serif;font-size:16px;font-weight:500;line-height:1.6;color:#C0C0C0;margin:0 0 8px;}
  .em-card strong{color:#FFFFFF;}
  .em-card code{background:#0F0F0F;color:#00BFFF;padding:2px 6px;border-radius:3px;font-size:90%;}
  .em-card hr{border:0;border-top:1px solid #333333;margin:24px 0;}
  .em-card blockquote{margin:0 0 22px;padding:2px 0 2px 20px;border-left:3px solid #00BFFF;}
  .em-card blockquote p{font-size:18px;font-weight:600;font-style:italic;color:#FFFFFF;}
  @media only screen and (max-width:600px){
    .em-container{width:100%!important;}
    .em-logo{width:78%!important;height:auto!important;}
    .em-pad{padding-left:22px!important;padding-right:22px!important;}
    .em-cardpad{padding-left:26px!important;padding-right:26px!important;}
  }
</style>
</head>
<body style="margin:0;padding:0;background:#0D0D0D;">
{$preheaderBlock}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#0D0D0D;" bgcolor="#0D0D0D"><tr><td align="center" style="padding:0;">
        <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" class="em-container" style="width:600px;max-width:600px;margin:0 auto;background:#0D0D0D;" bgcolor="#0D0D0D">
          <tr><td align="center" class="em-pad" style="padding:38px 24px 26px;border-bottom:2px solid #00BFFF;">
            <a href="{$site}"><img src="{$base}od9-lettermark.png" width="440" height="148" alt="OD9" class="em-logo" style="display:block;width:440px;max-width:82%;height:auto;margin:0 auto;border:0;outline:none;text-decoration:none;"></a>
            <div style="font-family:'Rajdhani',Arial,sans-serif;font-size:11px;font-weight:600;letter-spacing:.3em;color:#00BFFF;text-transform:uppercase;padding-top:17px;">The Movement &middot; Level Up or Get Left Behind</div>
          </td></tr>
          <tr><td style="padding:30px 24px 10px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#1A1A1A;border:1px solid #00BFFF;border-radius:8px;box-shadow:0 0 20px rgba(0,191,255,0.26);" bgcolor="#1A1A1A">
              <tr><td class="em-cardpad em-card" style="padding:42px 38px 40px;">
{$bodyHtml}
              </td></tr>
            </table>
          </td></tr>
          <tr><td class="em-pad" style="padding:30px 34px 40px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
              <tr>
                <td align="left" valign="middle" style="font-family:'Rajdhani',Arial,sans-serif;font-size:11px;font-weight:700;letter-spacing:.24em;color:#00BFFF;text-transform:uppercase;line-height:1.6;">Level Up or<br>Get Left Behind</td>
                <td align="right" valign="middle">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr>
              <td style="padding-left:16px;"><a href="https://www.youtube.com/@TheUltimateRage" style="text-decoration:none;"><img src="{$base}social-youtube.png" width="26" height="26" alt="YouTube" style="display:block;border:0;outline:none;"></a></td>
              <td style="padding-left:16px;"><a href="https://www.instagram.com/theultimaterage/" style="text-decoration:none;"><img src="{$base}social-instagram.png" width="26" height="26" alt="Instagram" style="display:block;border:0;outline:none;"></a></td>
              <td style="padding-left:16px;"><a href="https://www.facebook.com/profile.php?id=100085460577398" style="text-decoration:none;"><img src="{$base}social-facebook.png" width="26" height="26" alt="Facebook" style="display:block;border:0;outline:none;"></a></td>
              <td style="padding-left:16px;"><a href="https://x.com/theultimat63157" style="text-decoration:none;"><img src="{$base}social-x.png" width="26" height="26" alt="X" style="display:block;border:0;outline:none;"></a></td>
            </tr></table>
                </td>
              </tr>
            </table>
            <div style="height:1px;background:#333333;line-height:1px;font-size:0;margin:22px 0 18px;">&zwnj;</div>
            {$transmission}<div style="font-family:'Rajdhani',Arial,sans-serif;font-size:12px;font-weight:500;line-height:1.75;color:#666666;text-align:center;">
              You're receiving this because you joined the movement at offda9.com.<br>
              <a href="{$unsub}" style="color:#999999;text-decoration:underline;">Unsubscribe</a> &nbsp;&middot;&nbsp; <a href="https://offda9.com" style="color:#00BFFF;text-decoration:none;">offda9.com</a><br>
              <span style="color:#5a5a5a;">{$postal}</span><br>
              <span style="color:#4d4d4d;">&copy; {$year} {$entity}. All rights reserved.</span>
            </div>
          </td></tr>
        </table>
</td></tr></table>
</body>
</html>
HTML;
    }
}
