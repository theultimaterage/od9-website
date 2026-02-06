# OD9 Project Context

**Purpose**: Persistent memory for AI agents working on OD9  
**Created**: 2026-01-30  
**Update Trigger**: After ANY error, learned pattern, or discovered solution

---

## [REQUIRED] Version History

```
[2026-01-30] v1.0 - Initial creation with core patterns from admin conversion
```

---

## [REQUIRED] Critical Patterns

### Pattern 1: Standalone Pages (OD9-specific implementation)
**Use when:** Page needs OD9-specific branding, custom logic, or unique features

```php
<?php
define('SITE_ROOT', dirname(dirname(__DIR__)));
require_once SITE_ROOT . '/config/bootstrap.php';
require_once SHARED_PATH . '/core/AdminAuth.php';
require_once SHARED_PATH . '/admin/AdminNavigation.php';
AdminAuth::require();
$currentAdmin = AdminAuth::getCurrentAdmin();
// OD9-specific implementation here
```

**Examples:** dashboard.php, login.php

### Pattern 2: Wrapper Pages (Shared-platform delegation)
**Use when:** Page uses shared-platform implementation with OD9 branding

```php
<?php
define('SITE_ID', 'od9');
define('SITE_PATH', dirname(dirname(__DIR__)));
require_once dirname(dirname(dirname(dirname(__DIR__)))) . '/shared-platform/admin/[module]/[file].php';
```

**Examples:** members.php, resources.php, tiers.php, events/*, tickets/*, etc.

**WHY THIS IS VALID:** White-label architecture. Shared-platform provides core functionality, OD9 passes branding context via SITE_ID.

**CRITICAL:** Wrapper pattern is INTENTIONAL for white-label system. Not a bug, not non-compliant - it's the architecture for centralized updates across multiple sites.

**Sidebar Rendering**:
```php
<?php AdminNavigation::renderSidebar($currentAdmin); ?>
```

**Database Queries** - Use prepared statements only:
```php
$stmt = db()->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
```

**Shared Platform Path**:
- `SHARED_PATH` - filesystem path to shared-platform
- `SHARED_URL` - URL to shared-platform assets

---

## [REQUIRED] Known Issues

- **Issue**: Missing SITE_ROOT causes "failed to open stream" errors  
  **Solution**: Add `define('SITE_ROOT', dirname(dirname(__DIR__)));` before any require

- **Issue**: AdminNavigation not found  
  **Solution**: Ensure `require_once SHARED_PATH . '/admin/AdminNavigation.php';` after bootstrap

- **Issue**: CSS not loading in admin pages  
  **Solution**: Use `<?php echo SHARED_URL; ?>/assets/css/admin.css` for shared stylesheets

- **Issue**: Auth redirect loop  
  **Solution**: Check that login.php does NOT call `AdminAuth::require()`

---

## [REQUIRED] Test Commands

```bash
# PHP syntax check (run on any modified file)
php -l filename.php

# Check all admin pages for syntax errors
Get-ChildItem -Path "C:\xampp\htdocs\od9\private\admin" -Filter "*.php" -Recurse | ForEach-Object { php -l $_.FullName }

# Test page loads (replace with actual URL)
curl -I http://localhost/od9/private/admin/dashboard.php

# Check Apache error log
Get-Content "C:\xampp\apache\logs\error.log" -Tail 20
```

---

## [REQUIRED] Infrastructure Context

**Environment Variables** (set in config/bootstrap.php or .env):
```
ENVIRONMENT      = development | production
DEBUG_MODE       = true | false
DB_HOST          = localhost
DB_NAME          = od9
DB_USER          = root
DB_PASS          = (site-specific)
```

**External Dependencies**:
- MySQL 8.0+ (localhost:3306)
- Apache 2.4+ with mod_rewrite
- PHP 8.1+ with PDO, mysqli extensions
- shared-platform (sibling directory)

**File Permissions**:
- /private/logs: writable by web server
- /public/uploads: writable by web server (if uploads enabled)

---

## [OPTIONAL] Database Schema

```
Key Tables:
- admins: id, username, email, password_hash, role, created_at
- members: id, user_id, tier_id, status, joined_at
- tiers: id, name, price, features, active
- orders: id, member_id, amount, status, created_at
- tickets: id, member_id, subject, status, priority, created_at
- events: id, title, date, venue, ticket_price, capacity
```

---

## [OPTIONAL] Architecture Overview

```
PROJECT STRUCTURE:
  /config           - Site configuration (bootstrap.php, database.php)
  /private/admin    - Admin panel pages (PHP)
  /private/logs     - Application logs
  /private/migrations - Database migrations
  /public           - Public-facing pages and assets
  /public/admin     - Admin entry point (.htaccess redirect)

KEY FLOWS:
  Admin Login: public/admin/ → private/admin/login.php → AdminAuth::login()
  Admin Page: private/admin/*.php → bootstrap.php → shared-platform → render
  
DEPENDENCIES:
  - PHP 8.1+
  - MySQL 8.0
  - Apache with mod_rewrite
  - shared-platform (C:\xampp\htdocs\shared-platform)

SHARED PLATFORM INTEGRATION:
  - Bootstrap loads: shared-platform/core/Bootstrap.php
  - Provides: AdminAuth, AdminNavigation, SiteConfig, db()
  - Assets at: SHARED_URL/assets/css/admin.css
```

---

## [OPTIONAL] Documentation Links

- Shared Platform: `C:\xampp\htdocs\shared-platform\README.md`
- Admin CSS: `C:\xampp\htdocs\shared-platform\assets\css\admin.css`

---

## [AUTO-UPDATE] Error Log

```
Format: [YYYY-MM-DD HH:MM] Error: [description] | Fix: [what was done]

[2026-01-30 12:00] Error: Bulk conversion of 32 admin pages caused inconsistent implementations | Fix: Implementing Generator/Critic pattern with incremental validation
```

---

## [AUTO-UPDATE] Agent Handoff Notes

```
Format: [YYYY-MM-DD HH:MM] Agent: [role] | Status: [state] | Next: [action needed]

[2026-01-30 12:41] Agent: Setup | Status: AGENTS.md created | Next: Audit 32 admin pages for issues
```
