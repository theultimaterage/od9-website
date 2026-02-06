<?php
/**
 * OD9 Admin Login Page
 * 
 * Uses the shared admin authentication system.
 */

// Define site root for bootstrap
define('SITE_ROOT', dirname(dirname(__DIR__)));

// Load shared bootstrap
require_once SITE_ROOT . '/config/bootstrap.php';

// Load shared admin auth
require_once SHARED_PATH . '/core/AdminAuth.php';

// Initialize auth
AdminAuth::init();

// If already logged in, redirect to dashboard
if (AdminAuth::isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

// Generate CSRF token
$csrfToken = AdminAuth::generateCSRFToken();

// Initialize variables
$errorMessage = '';
$usernameValue = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!AdminAuth::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errorMessage = 'Invalid security token. Please refresh and try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $rememberMe = isset($_POST['remember_me']);
        
        $usernameValue = htmlspecialchars($username);
        
        if (empty($username) || empty($password)) {
            $errorMessage = 'Please enter both username and password.';
        } else {
            try {
                $db = Database::getInstance();
                
                // Look up user
                $user = $db->queryOne("
                    SELECT admin_id, username, email, password_hash, full_name, role,
                           is_active, failed_login_attempts, account_locked_until
                    FROM admin_users
                    WHERE (username = ? OR email = ?) AND is_active = 1
                ", [$username, $username]);
                
                // Check if account is locked
                if ($user && $user['account_locked_until'] && strtotime($user['account_locked_until']) > time()) {
                    $minutesRemaining = ceil((strtotime($user['account_locked_until']) - time()) / 60);
                    $errorMessage = "Account temporarily locked. Try again in {$minutesRemaining} minute(s).";
                    
                    AdminAuth::logSecurityEvent('locked_login_attempt', [
                        'username' => $username,
                    ]);
                }
                // Verify password
                elseif ($user && password_verify($password, $user['password_hash'])) {
                    // Successful login
                    $db->execute("
                        UPDATE admin_users
                        SET failed_login_attempts = 0,
                            account_locked_until = NULL,
                            last_login_at = NOW(),
                            last_login_ip = ?
                        WHERE admin_id = ?
                    ", [$_SERVER['REMOTE_ADDR'], $user['admin_id']]);
                    
                    session_regenerate_id(true);
                    
                    $_SESSION['admin_id'] = $user['admin_id'];
                    $_SESSION['admin_username'] = $user['username'];
                    $_SESSION['admin_email'] = $user['email'];
                    $_SESSION['admin_name'] = $user['full_name'];
                    $_SESSION['admin_role'] = $user['role'];
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['last_activity'] = time();
                    $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
                    
                    // Handle remember me
                    if ($rememberMe) {
                        $token = bin2hex(random_bytes(32));
                        $tokenHash = hash('sha256', $token);
                        $expiresAt = date('Y-m-d H:i:s', time() + (30 * 24 * 60 * 60));
                        
                        $db->execute("DELETE FROM remember_tokens WHERE admin_user_id = ?", [$user['admin_id']]);
                        $db->insert("
                            INSERT INTO remember_tokens (admin_user_id, token_hash, expires_at)
                            VALUES (?, ?, ?)
                        ", [$user['admin_id'], $tokenHash, $expiresAt]);
                        
                        $cookieName = SiteConfig::get('id', 'od9') . '_remember';
                        setcookie($cookieName, $token, time() + (30 * 24 * 60 * 60), '/', '', true, true);
                        $_SESSION['remembered'] = true;
                    }
                    
                    AdminAuth::logSecurityEvent('admin_login', [
                        'admin_id' => $user['admin_id'],
                        'username' => $user['username'],
                    ]);
                    
                    $redirectUrl = $_SESSION['intended_url'] ?? 'dashboard.php';
                    unset($_SESSION['intended_url']);
                    header('Location: ' . $redirectUrl);
                    exit;
                } else {
                    $errorMessage = 'Invalid username or password.';
                    
                    if ($user) {
                        $failedAttempts = $user['failed_login_attempts'] + 1;
                        $accountLockedUntil = null;
                        
                        if ($failedAttempts >= 5) {
                            $accountLockedUntil = date('Y-m-d H:i:s', time() + (15 * 60));
                            $errorMessage = 'Too many failed attempts. Account locked for 15 minutes.';
                        }
                        
                        $db->execute("
                            UPDATE admin_users
                            SET failed_login_attempts = ?,
                                account_locked_until = ?
                            WHERE admin_id = ?
                        ", [$failedAttempts, $accountLockedUntil, $user['admin_id']]);
                    }
                    
                    AdminAuth::logSecurityEvent('failed_login', [
                        'username' => $username,
                    ]);
                }
            } catch (PDOException $e) {
                $errorMessage = 'Database error. Please try again later.';
                error_log("OD9 Admin login error: " . $e->getMessage());
            }
        }
    }
}

// Check for session timeout message
if (isset($_SESSION['login_error'])) {
    $errorMessage = $_SESSION['login_error'];
    unset($_SESSION['login_error']);
}

// Get site branding
$siteName = SiteConfig::get('name', 'OD9');
$primaryColor = SiteConfig::get('colors.primary', '#00D4FF');
$adminTitle = SiteConfig::get('branding.admin_title', 'Admin Portal');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | <?php echo htmlspecialchars($siteName); ?></title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Roboto+Condensed:wght@400;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: <?php echo $primaryColor; ?>;
            --primary-dark: <?php echo SiteConfig::get('colors.secondary', '#0099CC'); ?>;
            --bg-dark: #0A0A0F;
            --bg-card: #12121A;
            --text: #ffffff;
            --text-muted: #8A8A8A;
            --border: #1E1E2E;
            --error: #ff4444;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Roboto Condensed', sans-serif;
            background: var(--bg-dark);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background-image: 
                radial-gradient(circle at 20% 80%, rgba(0, 212, 255, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(0, 212, 255, 0.05) 0%, transparent 50%);
        }
        
        .login-container {
            background: var(--bg-card);
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            width: 100%;
            max-width: 420px;
            padding: 40px;
            border: 1px solid var(--border);
            position: relative;
            overflow: hidden;
        }
        
        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, transparent, var(--primary), transparent);
        }
        
        .logo-section {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo-section img {
            max-width: 150px;
            height: auto;
            margin-bottom: 15px;
            filter: drop-shadow(0 0 20px rgba(0, 212, 255, 0.3));
        }
        
        .logo-section h1 {
            font-family: 'Bebas Neue', sans-serif;
            color: var(--primary);
            font-size: 28px;
            letter-spacing: 3px;
            margin-bottom: 8px;
        }
        
        .logo-section p {
            color: var(--text-muted);
            font-size: 14px;
        }
        
        .error-message {
            background: rgba(255, 68, 68, 0.1);
            border: 1px solid var(--error);
            color: var(--error);
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            color: var(--text-muted);
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 8px;
        }
        
        .form-group input[type="text"],
        .form-group input[type="password"] {
            width: 100%;
            padding: 14px 16px;
            background: var(--bg-dark);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text);
            font-size: 15px;
            transition: all 0.3s ease;
            font-family: 'Roboto Condensed', sans-serif;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(0, 212, 255, 0.1);
        }
        
        .form-group input::placeholder {
            color: var(--text-muted);
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
            margin-right: 10px;
            cursor: pointer;
            accent-color: var(--primary);
        }
        
        .checkbox-group label {
            color: var(--text-muted);
            font-size: 14px;
            cursor: pointer;
        }
        
        .login-button {
            width: 100%;
            padding: 14px;
            background: var(--primary);
            color: #000;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-family: 'Roboto Condensed', sans-serif;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .login-button:hover {
            background: #33DDFF;
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0, 212, 255, 0.4);
        }
        
        .login-footer {
            margin-top: 25px;
            padding-top: 25px;
            border-top: 1px solid var(--border);
            text-align: center;
        }
        
        .login-footer a {
            color: var(--primary);
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s ease;
        }
        
        .login-footer a:hover {
            color: #33DDFF;
        }
        
        .security-badge {
            margin-top: 20px;
            text-align: center;
            color: var(--text-muted);
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .security-badge i {
            color: var(--primary);
        }
        
        @media (max-width: 480px) {
            .login-container {
                padding: 30px 20px;
            }
            
            .logo-section h1 {
                font-size: 24px;
            }
            
            .logo-section img {
                max-width: 120px;
            }
        }
        
        .login-button.loading {
            opacity: 0.7;
            cursor: not-allowed;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo-section">
            <img src="../../public/assets/images/logos/od9-logo.png" alt="OD9 Logo">
            <h1><?php echo htmlspecialchars($adminTitle); ?></h1>
            <p>Secure Admin Access</p>
        </div>
        
        <?php if (!empty($errorMessage)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-triangle"></i>
                <span><?php echo htmlspecialchars($errorMessage); ?></span>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" id="loginForm">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            
            <div class="form-group">
                <label for="username">
                    <i class="fas fa-user"></i> Username or Email
                </label>
                <input 
                    type="text" 
                    id="username" 
                    name="username" 
                    value="<?php echo $usernameValue; ?>"
                    placeholder="Enter your username or email"
                    required
                    autocomplete="username"
                    autofocus
                >
            </div>
            
            <div class="form-group">
                <label for="password">
                    <i class="fas fa-lock"></i> Password
                </label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    placeholder="Enter your password"
                    required
                    autocomplete="current-password"
                >
            </div>
            
            <div class="checkbox-group">
                <input type="checkbox" id="remember_me" name="remember_me" value="1">
                <label for="remember_me">Keep me signed in for 30 days</label>
            </div>
            
            <button type="submit" class="login-button">
                <i class="fas fa-sign-in-alt"></i>
                Access Command Center
            </button>
        </form>
        
        <div class="login-footer">
            <a href="forgot-password.php">
                <i class="fas fa-key"></i> Forgot Password?
            </a>
        </div>
        
        <div class="security-badge">
            <i class="fas fa-shield-alt"></i>
            <span>OD9 Secure Portal</span>
        </div>
    </div>
    
    <script>
        document.getElementById('loginForm').addEventListener('submit', function() {
            const button = document.querySelector('.login-button');
            button.classList.add('loading');
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Authenticating...';
        });
    </script>
</body>
</html>
