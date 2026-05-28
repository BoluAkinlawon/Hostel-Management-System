<?php
declare(strict_types=1);
/**
 * Include at the top of every admin page.
 * Redirects to admin login if not authenticated.
 */
if (!isset($_SESSION['admin_id'])) {
    flash('error', 'Please login to access the admin panel.');
    header('Location: /admin/login.php');
    exit;
}
