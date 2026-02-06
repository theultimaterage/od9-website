<?php
/**
 * OD9 Admin - Tiers Module
 * 
 * Membership tier management using the universal admin page template.
 */

// Define site root before loading template
define('SITE_ROOT', dirname(dirname(dirname(__DIR__))));

// Load the universal admin page template
require_once dirname(dirname(dirname(dirname(__DIR__)))) . '/shared-platform/admin/includes/page-template.php';

// Render page - provide config UPFRONT instead of in callback
$pageConfig = [
    'title' => 'Tiers',
    'description' => 'Manage membership tiers and progression levels',
    'actions' => '<a href="add.php" class="btn btn-primary"><i class="fas fa-plus"></i> Add New Tier</a>'
];

renderAdminPage($pageConfig, function($context) use (&$pageConfig) {
    
    // NOW db() helper is available - Bootstrap has loaded
    
    // Fetch all tiers ordered by tier level
    $tiers = db()->query("
        SELECT t.*, 
               COUNT(DISTINCT m.member_id) as member_count
        FROM od9_tiers t
        LEFT JOIN od9_members m ON m.current_tier_id = t.tier_id
        GROUP BY t.tier_id
        ORDER BY t.tier_order ASC
    ");
    
    // Calculate statistics
    $totalTiers = count($tiers);
    $totalMembers = array_sum(array_column($tiers, 'member_count'));
    $avgPerTier = $totalTiers > 0 ? round($totalMembers / $totalTiers, 1) : 0;
    
    // Add stats to page config
    $pageConfig['stats'] = [
        [
            'icon' => 'fas fa-layer-group',
            'title' => 'Total Tiers',
            'value' => $totalTiers,
            'label' => 'Active Tier Levels'
        ],
        [
            'icon' => 'fas fa-users',
            'title' => 'Total Members',
            'value' => $totalMembers,
            'label' => 'Across All Tiers'
        ],
        [
            'icon' => 'fas fa-chart-line',
            'title' => 'Average per Tier',
            'value' => $avgPerTier,
            'label' => 'Members per Level'
        ]
    ];
    ?>
    <div class="card">
        <h3 class="card-title">
            <i class="fas fa-list"></i> All Tiers (<?php echo count($tiers); ?>)
        </h3>
        
        <?php if (empty($tiers)): ?>
            <div class="empty-state">
                <i class="fas fa-layer-group" style="font-size: 48px; opacity: 0.3; margin-bottom: 1rem;"></i>
                <p>No tiers found. Create your first tier to get started.</p>
                <a href="add.php" class="btn btn-primary mt-2">
                    <i class="fas fa-plus"></i> Create First Tier
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Level</th>
                            <th>Tier Name</th>
                            <th>Description</th>
                            <th>Credits Required</th>
                            <th>Discord Role</th>
                            <th>Members</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tiers as $tier): ?>
                            <tr>
                                <td>
                                    <span class="badge badge-primary"><?php echo htmlspecialchars($tier['tier_order']); ?></span>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($tier['tier_name']); ?></strong>
                                </td>
                                <td style="color: var(--admin-text-secondary); max-width: 300px;">
                                    <?php echo htmlspecialchars($tier['description'] ?? 'No description'); ?>
                                </td>
                                <td>
                                    <span style="color: var(--admin-primary); font-weight: 600;">
                                        <?php echo number_format($tier['credit_requirement']); ?> credits
                                    </span>
                                </td>
                                <td>
                                    <code style="font-size: 0.875rem;">
                                        <?php echo $tier['discord_role_id'] ? 'ID: ' . htmlspecialchars($tier['discord_role_id']) : 'Not set'; ?>
                                    </code>
                                </td>
                                <td><?php echo $tier['member_count']; ?> members</td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="edit.php?id=<?php echo $tier['tier_id']; ?>" class="btn btn-sm btn-secondary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="view.php?id=<?php echo $tier['tier_id']; ?>" class="btn btn-sm btn-secondary" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    <?php
});
