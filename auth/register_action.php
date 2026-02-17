<?php
/**
 * User Registration Handler (action)
 */

require_once __DIR__ . '/../includes/config.php';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('register.html');
}

// Get and sanitize form data
$full_name = isset($_POST['full_name']) ? sanitize($_POST['full_name']) : '';
$email = isset($_POST['email']) ? sanitize($_POST['email']) : '';
$phone = isset($_POST['phone']) ? sanitize($_POST['phone']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';
$confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

// Server-side validation
$errors = [];

// Validate full name
if (empty($full_name)) {
    $errors[] = "Full name is required";
} elseif (strlen($full_name) < 3) {
    $errors[] = "Full name must be at least 3 characters";
}

// Validate email
if (empty($email)) {
    $errors[] = "Email is required";
} elseif (!validateEmail($email)) {
    $errors[] = "Please enter a valid email address";
}

// Validate phone
if (empty($phone)) {
    $errors[] = "Phone number is required";
} elseif (!validatePhone($phone)) {
    $errors[] = "Please enter a valid Ethiopian phone number (e.g., 0911234567)";
}

// Validate password
if (empty($password)) {
    $errors[] = "Password is required";
} elseif (strlen($password) < 6) {
    $errors[] = "Password must be at least 6 characters";
}

// Validate confirm password
if ($password !== $confirm_password) {
    $errors[] = "Passwords do not match";
}

// Check if email already exists
if (empty($errors)) {
    $check_email = "SELECT id FROM users WHERE email = ?";
    $stmt = mysqli_prepare($conn, $check_email);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        $errors[] = "Email already registered. Please login or use a different email.";
    }
    mysqli_stmt_close($stmt);
}

// Check if phone already exists
if (empty($errors)) {
    $check_phone = "SELECT id FROM users WHERE phone = ?";
    $stmt = mysqli_prepare($conn, $check_phone);
    mysqli_stmt_bind_param($stmt, "s", $phone);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        $errors[] = "Phone number already registered.";
    }
    mysqli_stmt_close($stmt);
}

// If there are errors, redirect back with error message
if (!empty($errors)) {
    $_SESSION['register_errors'] = $errors;
    $_SESSION['register_data'] = [
        'full_name' => $full_name,
        'email' => $email,
        'phone' => $phone
    ];
    redirect('register.html');
}

// Hash password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Insert user into database
$sql = "INSERT INTO users (full_name, email, phone, password) VALUES (?, ?, ?, ?)";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ssss", $full_name, $email, $phone, $hashed_password);

if (mysqli_stmt_execute($stmt)) {
    // Registration successful
    $user_id = mysqli_insert_id($conn);
    
    // Set session
    $_SESSION['user_id'] = $user_id;
    $_SESSION['user_name'] = $full_name;
    $_SESSION['user_email'] = $email;
    
    // Set success message
    setMessage("Registration successful! Welcome to EthioBus, $full_name!", 'success');
    
    // Redirect to search page
    redirect('../pages/search.html');
} else {
    // Registration failed
    $_SESSION['register_errors'] = ["Registration failed. Please try again."];
    redirect('register.html');
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>
