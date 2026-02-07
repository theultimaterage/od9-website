<?php
/**
 * Site Configuration - OD9 (Off Da Nine)
 * 
 * Advancing Humanity Toward Type I Civilization
 * Iced-out, chrome/diamond luxury aesthetic with electric blue neon accents
 */

return [
    // Site identity
    'id' => 'od9',
    'name' => 'OFF DA NINE',
    'tier' => 'ultra',  // Full access to all platform features
    'tagline' => 'Advancing Humanity Toward Type I Civilization',
    'domain' => 'offda9.com',
    'version' => '1.0.0',
    'timezone' => 'America/Chicago',
    
    // Development path (for local XAMPP)
    'dev_base_path' => '/od9/public',
    
    // Branding colors - Iced out luxury with electric blue
    'colors' => [
        'primary' => '#00D4FF',       // Electric blue neon
        'secondary' => '#0099CC',     // Darker electric blue
        'accent' => '#E0E0E0',        // Chrome/silver
        'background' => '#0A0A0F',    // Deep dark
        'text' => '#FFFFFF',          // White
        'muted' => '#8A8A8A',         // Gray
        'diamond' => '#B9F2FF',       // Diamond sparkle
    ],
    
    // Typography
    'fonts' => [
        'heading' => 'Bebas Neue',
        'body' => 'Roboto Condensed',
        'accent' => 'Oswald',
    ],
    
    // Logos
    'logos' => [
        'default' => 'assets/images/logos/od9-logo.png',
        'light' => 'assets/images/logos/od9-logo-light.png',
        'icon' => 'assets/images/logos/od9-icon.png',
        'favicon' => 'assets/images/favicon.ico',
    ],
    
    // Feature toggles
    'features' => [
        'tickets' => true,            // Events
        'merch' => true,              // Merchandise store
        'music' => true,              // Music section
        'videos' => false,            // No videos section
        'gallery' => false,           // No gallery
        'email_signup' => true,       // Newsletter
        'admin_panel' => true,        // Admin dashboard
        'promo_codes' => true,        // Discount codes
        'digital_downloads' => true,  // Digital content
        'discord_bot' => true,        // Discord community
        'video_generation' => false,  // No AI video
        'framework' => true,          // OD9 Framework page
        'tiers' => true,              // Progression tiers
        'resources' => true,          // Resources page
    ],
    
    // Navigation menu
    'navigation' => [
        ['label' => 'Home', 'url' => 'index.php', 'id' => 'index'],
        ['label' => 'Framework', 'url' => 'framework.php', 'id' => 'framework', 'feature' => 'framework'],
        ['label' => 'Tiers', 'url' => 'tiers.php', 'id' => 'tiers', 'feature' => 'tiers'],
        ['label' => 'Da Crew', 'url' => 'da-crew.php', 'id' => 'da-crew'],
        ['label' => 'Music', 'url' => 'music.php', 'id' => 'music', 'feature' => 'music'],
        ['label' => 'Resources', 'url' => 'resources.php', 'id' => 'resources', 'feature' => 'resources'],
        ['label' => 'Support', 'url' => 'support.php', 'id' => 'support'],
        ['label' => 'Contact', 'url' => 'contact.php', 'id' => 'contact'],
    ],
    
    // Social media
    'social' => [
        'discord' => 'https://discord.gg/spgmrXVMWq',
        'twitter' => '',
        'instagram' => '',
        'youtube' => '',
        'twitch' => '',
        'tiktok' => '',
        'facebook' => '',
        'spotify' => '',
    ],
    
    // Contact info
    'contact' => [
        'email' => 'info@offda9.com',
        'support_email' => 'support@offda9.com',
        'phone' => '',
        'address' => '',
    ],
    
    // SEO defaults
    'seo' => [
        'title_suffix' => ' | OD9 - Off Da Nine',
        'description' => 'OD9 is a framework for advancing humanity toward Type I civilization status through STEAM optimization, community building, and technological progress.',
        'keywords' => 'OD9, Kardashev Scale, Type I Civilization, STEAM, Transhumanism, Human Advancement',
        'og_image' => 'images/og-image.jpg',
    ],
    
    // Email settings (used by shared Email.php)
    'email' => [
        'from_name' => 'OD9',
        'from_email' => 'noreply@offda9.com',
        'reply_to' => 'support@offda9.com',
        'smtp_host' => 'sandbox.smtp.mailtrap.io',
        'smtp_port' => 2525,
        'smtp_user' => '',
        'smtp_pass' => '',
        'smtp_secure' => 'tls',
        'debug_mode' => false,
    ],
    
    // Payment settings (used by shared Payment.php)
    'payment' => [
        'gateway' => 'square',
        'access_token' => '',
        'application_id' => '',
        'location_id' => '',
        'environment' => 'sandbox',
        'currency' => 'USD',
    ],
    
    // Ticket code prefix (used by shared TicketCodeGenerator.php)
    'ticket_prefix' => 'OD9',
    
    // Footer configuration
    'footer' => [
        'quick_links' => [
            ['label' => 'Join Discord', 'url' => 'https://discord.gg/spgmrXVMWq', 'external' => true],
            ['label' => 'Framework', 'url' => 'framework.php', 'feature' => 'framework'],
            ['label' => 'Resources', 'url' => 'resources.php', 'feature' => 'resources'],
            ['label' => 'Contact', 'url' => 'contact.php'],
        ],
    ],
    
    // Progression Tiers (OD9-specific feature)
    'progression_tiers' => [
        'observer' => [
            'name' => 'Observer',
            'description' => 'Learning the fundamentals',
            'level' => 1,
        ],
        'theorist' => [
            'name' => 'Theorist',
            'description' => 'Synthesizing concepts',
            'level' => 2,
        ],
        'architect' => [
            'name' => 'Architect',
            'description' => 'Building solutions',
            'level' => 3,
        ],
        'pioneer' => [
            'name' => 'Pioneer',
            'description' => 'Leading initiatives',
            'level' => 4,
        ],
    ],
    
    // Mission pillars
    'mission_pillars' => [
        [
            'title' => 'Kardashev Scale',
            'description' => 'Understanding where we are and mapping the path to becoming a Type I civilization capable of harnessing our planet\'s full energy potential.',
            'icon' => 'fa-bolt',
        ],
        [
            'title' => 'STEAM Integration',
            'description' => 'Optimizing Science, Technology, Engineering, Arts, and Mathematics through interdisciplinary collaboration and innovation.',
            'icon' => 'fa-atom',
        ],
        [
            'title' => 'Community Building',
            'description' => 'Creating synergy through collaborative think tanks, project development, and collective problem-solving frameworks.',
            'icon' => 'fa-users',
        ],
        [
            'title' => 'Transhumanism',
            'description' => 'Exploring responsible advancement of human potential through technological integration and biological optimization.',
            'icon' => 'fa-dna',
        ],
    ],
    
    // Admin panel branding
    'branding' => [
        'primary_color' => '#00D4FF',
        'primary_hover' => '#33DDFF',
        'logo' => 'assets/images/logos/od9-logo.png',
        'admin_title' => 'OD9 Command Center',
    ],
    
    // Admin navigation: Uses default from AdminNavigation.php
    // which includes all modules (Orders, Customers, Products, etc.)
    // plus OD9-specific items (Members, Tiers, Discord Bot, Resources)
];
