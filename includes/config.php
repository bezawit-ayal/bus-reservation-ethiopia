<?php
/**
 * Ethiopian Bus Reservation System
 * Database Configuration File
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'bus_reservation_ethiopia');

// Create database connection
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set charset
mysqli_set_charset($conn, "utf8mb4");

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Site Configuration
define('SITE_NAME', 'Ethiopian Bus Reservation');
define('SITE_URL', 'http://localhost/bus-reservation-ethiopia/');
define('CURRENCY', 'ETB');

// Helper Functions

/**
 * Sanitize input to prevent XSS
 */
function sanitize($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    $data = mysqli_real_escape_string($conn, $data);
    return $data;
}

/**
 * Validate email format
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Validate phone number (Ethiopian format)
 */
function validatePhone($phone) {
    // Ethiopian phone: +251, 09, or 07 prefix
    $pattern = '/^(\+251|0)(9|7)[0-9]{8}$/';
    return preg_match($pattern, $phone);
}

/**
 * Generate booking reference
 */
function generateBookingReference() {
    return 'ETH' . date('Ymd') . strtoupper(substr(uniqid(), -6));
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if admin is logged in
 */
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']);
}

/**
 * Redirect function
 */
function redirect($url) {
    header("Location: $url");
    exit();
}

/**
 * Display alert message
 */
function setMessage($message, $type = 'success') {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
}

/**
 * Get and clear message
 */
function getMessage() {
    if (isset($_SESSION['message'])) {
        $message = $_SESSION['message'];
        $type = $_SESSION['message_type'];
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
        return ['message' => $message, 'type' => $type];
    }
    return null;
}

/**
 * Format currency (Ethiopian Birr)
 */
function formatCurrency($amount) {
    return number_format($amount, 2) . ' ' . CURRENCY;
}

/**
 * Get Ethiopian cities list
 */
function getEthiopianCities() {
    return [
        'Addis Ababa', 'Bahir Dar', 'Gondar', 'Hawassa', 'Dire Dawa',
        'Mekelle', 'Jimma', 'Adama', 'Harar', 'Dessie', 'Axum',
        'Arba Minch', 'Gambella', 'Jijiga', 'Debre Markos', 'Nekemte',
        'Bishoftu', 'Shashamane', 'Debre Birhan', 'Asosa'
    ];
}
?>
