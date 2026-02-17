<?php
/**
 * Admin Logout Handler
 * Ethiopian Bus Reservation System
 */

require_once __DIR__ . '/../includes/config.php';

// Clear admin session variables
unset($_SESSION['admin_id']);
unset($_SESSION['admin_username']);
unset($_SESSION['admin_email']);

// Redirect to admin login
header("Location: login.php");
exit();
?>
