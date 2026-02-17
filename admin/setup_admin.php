<?php
/**
 * One-time setup: ensure default admin exists with hashed password.
 * Run once in browser: http://localhost/.../admin/setup_admin.php
 * Then delete this file or restrict access.
 */

require_once __DIR__ . '/../includes/config.php';

$created = false;
$updated = false;
$default_username = 'admin';
$default_email = 'admin@busethiopia.com';
$default_password = 'password'; // change after first login
$hash = password_hash($default_password, PASSWORD_DEFAULT);

$stmt_check = mysqli_prepare($conn, "SELECT id, password FROM admins WHERE username = ? LIMIT 1");
mysqli_stmt_bind_param($stmt_check, "s", $default_username);
mysqli_stmt_execute($stmt_check);
$result = mysqli_stmt_get_result($stmt_check);
if (!$result) {
    die('Database error: ' . mysqli_error($conn));
}

if (mysqli_num_rows($result) === 0) {
    $stmt = mysqli_prepare($conn, "INSERT INTO admins (username, email, password) VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "sss", $default_username, $default_email, $hash);
    if (mysqli_stmt_execute($stmt)) {
        $created = true;
    } else {
        die('Insert failed: ' . mysqli_stmt_error($stmt));
    }
    mysqli_stmt_close($stmt);
} else {
    $row = mysqli_fetch_assoc($result);
    $current = $row['password'];
    if (strlen($current) !== 60 || strpos($current, '$2y$') !== 0) {
        $stmt = mysqli_prepare($conn, "UPDATE admins SET password = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "si", $hash, $row['id']);
        if (mysqli_stmt_execute($stmt)) {
            $updated = true;
        }
        mysqli_stmt_close($stmt);
    }
}

header('Content-Type: text/html; charset=utf-8');
echo '<!DOCTYPE html><html><head><title>Admin setup</title></head><body>';
if ($created) {
    echo '<p>Default admin created. Username: <strong>admin</strong>, Password: <strong>password</strong>. <a href="login.php">Go to login</a></p>';
} elseif ($updated) {
    echo '<p>Default admin password updated to hashed value. Username: <strong>admin</strong>, Password: <strong>password</strong>. <a href="login.php">Go to login</a></p>';
} else {
    echo '<p>Admin already exists with hashed password. <a href="login.php">Go to login</a></p>';
}
echo '<p><small>For security, delete or protect <code>setup_admin.php</code> after use.</small></p></body></html>';
