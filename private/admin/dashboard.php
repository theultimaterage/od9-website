<?php
/**
 * OD9 Admin Dashboard
 * 
 * Main dashboard for the OD9 Command Center.
 */

// Define site root for bootstrap
define('SITE_ROOT', dirname(dirname(__DIR__)));

// Load shared bootstrap
require_once SITE_ROOT . '/config/bootstrap.php';

// Load shared admin auth and navigation
require_once SHARED_PATH . '/core/AdminAuth.php';
require_once SHARED_PATH . '/admin/AdminNavigation.php';

// Require authentication
AdminAuth::require();

// Get current admin
$currentAdmin = AdminAuth::getCurrentAdmin();

// Get site branding
$siteName = SiteConfig::get('name', 'OD9');
$primaryColor = SiteConfig::get('colors.primary', '#00D4FF');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | <?php echo htmlspecialchars($siteName); ?> Admin</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Roboto+Condensed:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo SHARED_URL; ?>/assets/css/admin.css">
    
    <style>
        :root {
            --admin-primary: <?php echo $primaryColor; ?>;
            --admin-primary-hover: #33DDFF;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php AdminNavigation::renderSidebar($currentAdmin); ?>
        
        <main class="main-content">
            <div class="page-header">
                <h1>OD9 Command Center</h1>
                <p>Welcome back, <?php echo htmlspecialchars($currentAdmin['name']); ?>!</p>
            </div>
            
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
                    <p class="text-muted" style="font-size: 14px;">
                        OD9 Platform v<?php echo SiteConfig::get('version', '1.0.0'); ?><br>
                        Last login: <?php echo date('M j, Y g:i A'); ?>
                    </p>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
