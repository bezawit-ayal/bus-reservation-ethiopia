<?php
/**
 * User Logout Handler
 * Ethiopian Bus Reservation System
 */

require_once __DIR__ . '/../includes/config.php';

// Clear all session variables
$_SESSION = array();

// Destroy session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy session
session_destroy();

// Clear remember me cookies
setcookie('remember_token', '', time() - 3600, '/');
setcookie('user_email', '', time() - 3600, '/');

// Redirect to home page
header("Location: ../index.html");
exit();
?>
