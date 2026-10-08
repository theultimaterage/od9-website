<?php
/**
 * The Off Da 9 profiles stay linked from the site.
 *
 * 2026-10-08: the rebrand created facebook.com/offda9, instagram.com/offda9official and
 * youtube.com/@OffDa9, and the site still pointed only at the founder's artist accounts; the
 * resources card titled "OD9 YouTube" sent people to the artist channel. This pins every
 * surface that carries the collective's profiles: the shared footer, the contact page's
 * "Official OD9 channels" grid, the Organization structured data (site-wide and home page),
 * and that resources card.
 *
 *   php tests/test_social_links.php        (exit 0 = all pass)
 */
$root  = dirname(__DIR__);
$links = [
    'https://www.facebook.com/offda9',
    'https://www.instagram.com/offda9official',
    'https://www.youtube.com/@OffDa9',
];
$files = [
    'includes/footer.php'     => $links,
    'contact.php'             => $links,
    'includes/seo_schema.php' => $links,
    'index.php'               => $links,
    'resources.php'           => ['https://www.youtube.com/@OffDa9'],
];
$fail = 0;
foreach ($files as $file => $want) {
    $html = @file_get_contents("$root/$file");
    foreach ($want as $url) {
        $ok = $html !== false && strpos($html, $url) !== false;
        echo ($ok ? 'PASS' : 'FAIL') . " $file links $url\n";
        if (!$ok) {
            $fail++;
        }
    }
}
// The resources card titled "OD9 YouTube" must not send people to the artist channel.
$res  = (string) @file_get_contents("$root/resources.php");
$at   = strpos($res, '<h3>OD9 YouTube</h3>');
$card = $at === false ? '' : substr($res, $at, 600);
$ok   = $card !== '' && strpos($card, '@theultimaterage') === false;
echo ($ok ? 'PASS' : 'FAIL') . " resources.php: the OD9 YouTube card exists and does not link the artist channel\n";
if (!$ok) {
    $fail++;
}
echo $fail ? "FAIL ($fail)\n" : "ALL PASS\n";
exit($fail ? 1 : 0);
