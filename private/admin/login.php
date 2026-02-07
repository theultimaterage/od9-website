<?php
/**
 * OD9 Admin Login Page
 * 
 * Redirects to the unified admin login portal on the shared platform.
 * The portal handles authentication and routes back to this tenant's dashboard.
 */

header('Location: /shared-platform/admin/login.php?site=od9');
exit;
