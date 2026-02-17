<?php
/**
 * User Login Handler (action)
 */

require_once __DIR__ . '/../includes/config.php';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('login.html');
}

// Get and sanitize form data
$email = isset($_POST['email']) ? sanitize($_POST['email']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';
$remember = isset($_POST['remember']) ? true : false;

// Server-side validation
$errors = [];

// Validate email
if (empty($email)) {
    $errors[] = "Email is required";
} elseif (!validateEmail($email)) {
    $errors[] = "Please enter a valid email address";
}

// Validate password
if (empty($password)) {
    $errors[] = "Password is required";
}

// If validation errors, redirect back
if (!empty($errors)) {
    $_SESSION['login_errors'] = $errors;
    $_SESSION['login_email'] = $email;
    redirect('login.html');
}

// Check user credentials
$sql = "SELECT id, full_name, email, password FROM users WHERE email = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 1) {
    $user = mysqli_fetch_assoc($result);
    
    // Verify password
    if (password_verify($password, $user['password'])) {
        // Login successful
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_email'] = $user['email'];
        
        // Set remember me cookie (30 days)
        if ($remember) {
            $token = bin2hex(random_bytes(32));
            setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/');
            setcookie('user_email', $email, time() + (30 * 24 * 60 * 60), '/');
        }
        
        // Set success message
        setMessage("Welcome back, " . $user['full_name'] . "!", 'success');
        
        // Redirect to search page or intended page
        $redirect_to = isset($_SESSION['redirect_after_login']) ? $_SESSION['redirect_after_login'] : '../pages/search.html';
        unset($_SESSION['redirect_after_login']);
        redirect($redirect_to);
    } else {
        // Invalid password
        $_SESSION['login_errors'] = ["Invalid email or password"];
        $_SESSION['login_email'] = $email;
        redirect('login.html');
    }
} else {
    // User not found
    $_SESSION['login_errors'] = ["Invalid email or password"];
    $_SESSION['login_email'] = $email;
    redirect('login.html');
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>
