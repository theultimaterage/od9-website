<?php
/**
 * OD9 Agent Profile API Endpoint
 * 
 * Returns machine-readable profile for AI agents and automated systems.
 * Part of F.R.E.S.H. platform ACO (Agent-Commerce Optimization) layer.
 * 
 * @see https://offda9.com/llms.txt
 * @see https://offda9.com/.well-known/fresh-profile.json
 */

// Set appropriate headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Cache-Control: public, max-age=3600');
header('X-Powered-By: F.R.E.S.H.');

// Handle CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed', 'allowed' => ['GET']]);
    exit;
}

// Agent profile data
$profile = [
    'version' => '1.0',
    'generated' => gmdate('c'),
    'platform' => 'F.R.E.S.H.',
    'protocol' => [
        'name' => 'FRESH Agent Protocol',
        'version' => '1.0',
        'spec_url' => 'https://freshthaplatform.com/docs/agent-protocol'
    ],
    'identity' => [
        '@context' => 'https://schema.org',
        '@type' => ['Organization', 'MusicGroup'],
        '@id' => 'https://offda9.com/#organization',
        'name' => 'OD9 - Off Da Nine',
        'alternateName' => 'Off Da Nine',
        'url' => 'https://offda9.com',
        'logo' => 'https://offda9.com/images/logos/od9-logo.png',
        'description' => 'Chicago-based music collective and creative innovation group producing original hip-hop, R&B, and funk music while building frameworks for human advancement through STEAM optimization.',
        'genre' => ['Hip-Hop', 'R&B', 'Funk', 'Alternative'],
        'location' => [
            'city' => 'Chicago',
            'state' => 'IL',
            'country' => 'US'
        ],
        'foundingDate' => '2024'
    ],
    'members' => [
        [
            'name' => 'The Ultimate Rage',
            'role' => 'Co-Founder, Producer, Vocalist, Technologist',
            'profile_url' => 'https://offda9.com/da-crew.php#rage'
        ],
        [
            'name' => 'Deezle',
            'role' => 'Co-Founder, Producer, Vocalist',
            'profile_url' => 'https://offda9.com/da-crew.php#deezle'
        ],
        [
            'name' => 'Joey P.',
            'role' => 'Artist, Marketing',
            'profile_url' => 'https://offda9.com/da-crew.php#joey-p'
        ],
        [
            'name' => 'LB',
            'role' => 'Member',
            'profile_url' => 'https://offda9.com/da-crew.php#lb'
        ],
        [
            'name' => 'P-Mac',
            'role' => 'Member',
            'profile_url' => 'https://offda9.com/da-crew.php#p-mac'
        ]
    ],
    'capabilities' => [
        'music' => [
            'enabled' => true,
            'catalog_url' => 'https://offda9.com/music.php',
            'streaming' => [
                'spotify' => 'https://open.spotify.com/album/6V3kmOLMgNgsCLrRJaMiN3'
            ],
            'releases' => [
                [
                    'title' => 'The Outrageous EP',
                    'artist' => 'The Ultimate Rage',
                    'type' => 'EP',
                    'url' => 'https://offda9.com/music.php#outrageous-ep'
                ],
                [
                    'title' => 'Missing In Action',
                    'artist' => 'Joey P.',
                    'type' => 'Album',
                    'url' => 'https://open.spotify.com/album/7jnkBN83bZO0YTsORUrKBZ'
                ]
            ]
        ],
        'community' => [
            'enabled' => true,
            'discord' => 'https://discord.gg/spgmrXVMWq',
            'tiers_url' => 'https://offda9.com/tiers.php',
            'support_url' => 'https://offda9.com/support.php'
        ],
        'contact' => [
            'enabled' => true,
            'url' => 'https://offda9.com/contact.php'
        ],
        'framework' => [
            'enabled' => true,
            'url' => 'https://offda9.com/framework.php',
            'description' => 'OD9 Framework for advancing humanity toward Type I civilization'
        ]
    ],
    'discovery' => [
        'schema_json_ld' => 'https://offda9.com/',
        'llms_txt' => 'https://offda9.com/llms.txt',
        'llms_full_txt' => 'https://offda9.com/llms-full.txt',
        'sitemap' => 'https://offda9.com/sitemap.xml',
        'robots' => 'https://offda9.com/robots.txt',
        'well_known' => 'https://offda9.com/.well-known/fresh-profile.json'
    ],
    'social' => [
        'discord' => 'https://discord.gg/spgmrXVMWq',
        'youtube' => 'https://youtube.com/@theultimaterage',
        'patreon' => 'https://www.patreon.com/c/TheUltimateRage',
        'substack' => 'https://theultimaterage.substack.com'
    ],
    'member_of' => [
        'platform' => 'F.R.E.S.H.',
        'name' => 'Funky Real Entertainers Standing Here',
        'url' => 'https://freshthaplatform.com'
    ],
    'agent_instructions' => [
        'booking' => 'Contact via https://offda9.com/contact.php for booking inquiries',
        'music' => 'Music available on Spotify and other streaming platforms. Catalog at https://offda9.com/music.php',
        'support' => 'Community support and membership tiers at https://offda9.com/tiers.php',
        'partnership' => 'For partnerships and collaborations, use contact form'
    ]
];

// Output
echo json_encode($profile, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
