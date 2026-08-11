<?php
/**
 * Guide-video registry — single source of truth for the trigger-key -> video map.
 * Shared by guide.php (the DM-linked single player) and guide-gallery.php (the
 * inspection gallery). Keep filenames in sync with utils/guide_videos.py triggers.
 *
 *   $VIDEO_MAP[key] = [filename, character, title, description]
 */

$VIDEO_MAP = [
    // Onboarding
    'arrived' => [
        'archivist-welcome.mp4', 'The Archivist',
        'Welcome to The Wake',
        'You have arrived. The Archivist welcomes you to your journey of understanding reality.',
    ],
    'observer_rising' => [
        'archivist-observer-rising.mp4', 'The Archivist',
        'The Climb Ahead',
        'Fifty credits in. The Archivist marks how far you have come, and shows you the climb that waits above The Wake.',
    ],

    // Tier Entry
    'tier_theorist' => [
        'archivist-theorist-entry.mp4', 'The Archivist',
        'Welcome to The Diagnostic',
        'You have passed through The Wake. The Archivist acknowledges your growth into a Theorist.',
    ],
    'tier_approaching_theorist' => [
        'archivist-approaching-theorist.mp4', 'The Archivist',
        'A Shift Approaches',
        'The Archivist senses your progress. You are nearing the threshold of the Theorist tier.',
    ],
    'tier_architect' => [
        'forgemaster-architect-entry.mp4', 'The Forgemaster',
        'Welcome to The Forge',
        'You have proven you can diagnose. The Forgemaster calls you up to The Forge, where you stop describing the problem and start building the replacement.',
    ],
    'tier_pioneer' => [
        'navigator-pioneer-entry.mp4', 'The Navigator',
        'Welcome to The Bridge',
        'You no longer climb alone. The Navigator welcomes you to The Bridge, where Pioneers lead the ones still climbing.',
    ],
    'tier_benefactor' => [
        'benefactor-entry-montage-final.mp4', 'All Guides',
        'Welcome to The Horizon',
        'All four guides gather to mark your arrival at the highest tier. The Horizon awaits.',
    ],

    // First Activities
    'first_content'          => ['activity-first-content-archivist.mp4',      'The Archivist',   'First Knowledge Absorbed',    'The Archivist notes your first completed content. The library opens to you.'],
    'first_discussion'       => ['activity-first-discussion-archivist.mp4',   'The Archivist',   'First Voice Raised',          'You have spoken in the halls. The Archivist records your first discussion contribution.'],
    'first_qotd'             => ['activity-first-qotd-archivist.mp4',         'The Archivist',   'Thinking Together',           'Your voice joins the collective inquiry. The Archivist honors your first Question of the Day response.'],
    'first_reflection'       => ['activity-first-reflection-archivist.mp4',   'The Archivist',   'Reflection Crystallizes',     'The Archivist witnesses your insight take form. Reflection transforms knowledge into wisdom.'],
    'first_evaluation'       => ['activity-first-evaluation-archivist.mp4',   'The Archivist',   'First Judgment Cast',         "You have evaluated another's work. The Archivist acknowledges your discernment."],
    'first_governance'       => ['activity-first-governance-navigator.mp4',   'The Navigator',   'First Civic Act',             'The Navigator welcomes you to governance. Your vote shapes the path forward.'],
    'first_mentorship'       => ['activity-first-mentorship-navigator.mp4',   'The Navigator',   'First Connection Forged',     'The Navigator celebrates your entry into mentorship. Growth flows both ways.'],
    'first_milestone'        => ['activity-first-milestone-forgemaster.mp4',  'The Forgemaster', 'First Milestone Reached',     'The Forgemaster marks your first project milestone. The forge burns brighter.'],
    'first_project'          => ['activity-first-project-forgemaster.mp4',    'The Forgemaster', 'First Creation Begun',        'The Forgemaster acknowledges your first project. Build what has never existed.'],
    'first_research_checkin' => ['activity-research-checkin-forgemaster.mp4', 'The Forgemaster', 'Research Cell Active',        'The Forgemaster notes your research cell check-in. Inquiry drives innovation.'],
    'first_pathway_complete' => ['activity-pathway-completion-archivist.mp4', 'The Archivist',   'Pathway Completed',           'The Archivist records your completed learning pathway. Knowledge crystallized.'],
    'mentorship_90day'       => ['activity-mentorship-90day-navigator.mp4',   'The Navigator',   '90-Day Mentorship Milestone', 'The Navigator honors 90 days of dedicated mentorship. A lasting bond forged.'],
    'pessimism_training'     => ['activity-pessimism-training-archivist.mp4', 'The Archivist',   'Pessimism Training Complete', 'The Archivist guides you through pessimism as a diagnostic tool, not a destination.'],

    // Streaks
    'streak_3'      => ['streak-3day-archivist.mp4',    'The Archivist',   '3-Day Streak!',       'The Archivist sees your consistency. Three days of dedicated engagement.'],
    'streak_7'      => ['streak-7day-archivist.mp4',    'The Archivist',   'Weekly Warrior',      'Seven days strong. The Archivist marks your first full week of dedication.'],
    'streak_14'     => ['streak-14day-forgemaster.mp4', 'The Forgemaster', 'Fortnight Focus',     'The Forgemaster acknowledges two weeks of unbroken commitment.'],
    'streak_30'     => ['streak-30day-forgemaster.mp4', 'The Forgemaster', 'Monthly Devotion',    'Thirty days. The Forgemaster salutes your relentless dedication to the cause.'],
    'streak_60'     => ['streak-60day-navigator.mp4',   'The Navigator',   'Relentless',          'Sixty days of continuous growth. The Navigator charts your extraordinary trajectory.'],
    'streak_100'    => ['streak-100day-navigator.mp4',  'The Navigator',   'Centurion',           'One hundred days. The Navigator declares you a Centurion of commitment.'],
    'streak_shield' => ['streak-shield-archivist.mp4',  'The Archivist',   'Shield Activated',    'Your streak shield protected you. The Archivist reminds you: resilience is not perfection.'],

    // Capstones
    'observer_capstone_complete'  => ['observer-capstone-complete.mp4',  'The Archivist', 'Observer Capstone Complete',  'The Archivist marks your passage through The Diagnosis Preview. You have seen the shape of the problem. Now you learn its texture.'],
    'theorist_capstone_complete'  => ['theorist-capstone-complete.mp4',  'The Archivist', 'Theorist Capstone Complete',  'The Archivist honors your Theorist Capstone. You have demonstrated mastery of diagnostic thinking.'],

    // The Firm — founding-circle induction
    'firm_seal_reveal' => ['firm-seal-reveal.mp4',   'The Firm',      "You've Been Made — Seal of The Firm", 'The seal comes down. You are one of the first five to cross the gate — the inner circle of The Firm.'],
    'firm_og_reveal'   => ['firm-og-reveal.mp4',     'The Firm',      "You've Been Made — OG Member",        'The seal is struck. You stand among the founding circle of OD9 — an OG Member of The Firm.'],
    'firm_archivist'   => ['firm-archivist-rite.mp4','The Archivist', 'The Rite of The Firm',                'The Archivist marks your induction and names the charge that comes with the seal.'],
];

$CHARACTER_COLORS = [
    'The Archivist'   => '#00FFF7',
    'The Forgemaster' => '#FF6B35',
    'The Navigator'   => '#7A00FF',
    'The Watcher'     => '#FFD700',
    'All Guides'      => '#00FFF7',
    'The Firm'        => '#7A00FF',
];
