<?php
/**
 * OD9 Admin Dashboard
 * 
 * Main dashboard for the OD9 Command Center.
 * Uses shared platform template for design consistency.
 */

// Define site path for bootstrap (SITE_PATH required by shared Bootstrap.php)
define('SITE_PATH', dirname(dirname(__DIR__)));

// Load shared page template
require_once SITE_PATH . '/config/bootstrap.php';
require_once SHARED_PATH . '/admin/includes/page-template.php';

// Render page using shared template
$pageConfig = [
    'title' => 'OD9 Command Center',
    'description' => 'Main dashboard overview'
];

renderAdminPage($pageConfig, function($context) {
    $currentAdmin = $context['currentAdmin'];
    ?>
    
    <div class="content-grid-4">
        <div class="stat-card">
            <div class="stat-card-icon">
                <i class="fas fa-users"></i>
            </div>
            <h3>Members</h3>
            <div class="count">0</div>
            <div class="label">Total Members</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-card-icon">
                <i class="fas fa-layer-group"></i>
            </div>
            <h3>Active Tiers</h3>
            <div class="count">4</div>
            <div class="label">Progression Levels</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-card-icon">
                <i class="fab fa-discord"></i>
            </div>
            <h3>Discord</h3>
            <div class="count">0</div>
            <div class="label">Connected Users</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-card-icon">
                <i class="fas fa-book"></i>
            </div>
            <h3>Resources</h3>
            <div class="count">0</div>
            <div class="label">Available Items</div>
        </div>
    </div>
    
    <div class="content-grid mt-3">
        <div class="card">
            <h3 class="card-title">
                <i class="fas fa-bolt"></i> Quick Actions
            </h3>
            <div class="flex gap-2" style="flex-wrap: wrap;">
                <a href="members/list.php" class="btn btn-primary">
                    <i class="fas fa-users"></i> View Members
                </a>
                <a href="discord/dashboard.php" class="btn btn-secondary">
                    <i class="fab fa-discord"></i> Discord Bot
                </a>
                <a href="resources/list.php" class="btn btn-secondary">
                    <i class="fas fa-book"></i> Resources
                </a>
            </div>
        </div>
        
        <div class="card">
            <h3 class="card-title">
                <i class="fas fa-info-circle"></i> System Status
            </h3>
            <div class="mb-2">
                <span class="badge badge-success">
                    <i class="fas fa-circle"></i> All Systems Operational
                </span>
            </div>
            <p class="text-muted text-sm">
                OD9 Platform v<?php echo SiteConfig::get('version', '1.0.0'); ?><br>
                Welcome back, <?php echo htmlspecialchars($currentAdmin['name']); ?>!
            </p>
        </div>
    </div>
    
<?php });
