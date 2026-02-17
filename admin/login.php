<?php
/**
 * Admin Login Page & Handler
 * Ethiopian Bus Reservation System
 */

require_once __DIR__ . '/../includes/config.php';

// If already logged in, redirect to dashboard
if (isAdminLoggedIn()) {
    redirect('dashboard.php');
}

// Check if form is submitted (POST request)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Use trim only for login lookup - do not use sanitize() as it can alter the value
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    
    // Server-side validation
    $errors = [];
    
    if ($username === '') {
        $errors[] = "Username or email is required";
    }
    
    if ($password === '') {
        $errors[] = "Password is required";
    }
    
    // If validation errors, show them
    if (!empty($errors)) {
        $_SESSION['admin_login_errors'] = $errors;
    } else {
        // Check admin credentials (by username or email)
        $sql = "SELECT id, username, email, password FROM admins WHERE username = ? OR email = ?";
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            $_SESSION['admin_login_errors'] = ["System error. Please try again."];
        } else {
            mysqli_stmt_bind_param($stmt, "ss", $username, $username);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if ($result && mysqli_num_rows($result) === 1) {
                $admin = mysqli_fetch_assoc($result);
                $stored_hash = $admin['password'];
                
                // Verify password: support both hashed and legacy plain-text (migrate to hash on success)
                $password_ok = false;
                if (strlen($stored_hash) === 60 && strpos($stored_hash, '$2y$') === 0) {
                    $password_ok = password_verify($password, $stored_hash);
                } elseif ($stored_hash === $password) {
                    // Legacy plain-text password: upgrade to hash
                    $password_ok = true;
                    $new_hash = password_hash($password, PASSWORD_DEFAULT);
                    $upd = mysqli_prepare($conn, "UPDATE admins SET password = ? WHERE id = ?");
                    if ($upd) {
                        mysqli_stmt_bind_param($upd, "si", $new_hash, $admin['id']);
                        mysqli_stmt_execute($upd);
                        mysqli_stmt_close($upd);
                    }
                }
                
                if ($password_ok) {
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_username'] = $admin['username'];
                    $_SESSION['admin_email'] = $admin['email'];
                    redirect('dashboard.php');
                }
            }
            mysqli_stmt_close($stmt);
        }
        
        if (!isset($_SESSION['admin_login_errors'])) {
            $_SESSION['admin_login_errors'] = ["Invalid username or password"];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Ethiopian Bus Reservation</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/bus-reservation-ethiopia/assets/css/style.css">
</head>
<body>
    <!-- Main Content -->
    <main class="auth-container" style="background: linear-gradient(135deg, var(--accent-color) 0%, #1a252f 100%);">
        <div class="auth-box">
            <div class="logo text-center">Ethio<span style="color: var(--accent-color);">Bus</span></div>
            <h2>Admin Login</h2>
            <p style="text-align: center; color: #666; margin-bottom: 25px;">Access the administration panel</p>
            
            <!-- Alert placeholder -->
            <div id="alertContainer"></div>
            <?php
            // Display PHP error messages if any
            if (isset($_SESSION['admin_login_errors']) && !empty($_SESSION['admin_login_errors'])) {
                echo '<div class="alert alert-error">';
                foreach ($_SESSION['admin_login_errors'] as $error) {
                    echo '<p>' . htmlspecialchars($error) . '</p>';
                }
                echo '</div>';
                unset($_SESSION['admin_login_errors']);
            }
            ?>

            <form id="adminLoginForm" action="login.php" method="POST">
                <div class="form-group">
                    <label for="username">Username or Email</label>
                    <input type="text" id="username" name="username" placeholder="Enter username or email" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter password" required>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">Login to Dashboard</button>
            </form>
            
            <div class="auth-links" style="margin-top: 25px;">
                <p><a href="/bus-reservation-ethiopia/">← Back to Website</a></p>
            </div>
        </div>
    </main>

    <script src="../assets/js/validation.js"></script>
</body>
</html>
