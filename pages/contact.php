<?php
/**
 * Contact Form Handler
 * Ethiopian Bus Reservation System
 */

require_once __DIR__ . '/../includes/config.php';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../contact.html');
}

// Get and sanitize form data
$name = isset($_POST['name']) ? sanitize($_POST['name']) : '';
$email = isset($_POST['email']) ? sanitize($_POST['email']) : '';
$phone = isset($_POST['phone']) ? sanitize($_POST['phone']) : '';
$subject = isset($_POST['subject']) ? sanitize($_POST['subject']) : '';
$message = isset($_POST['message']) ? sanitize($_POST['message']) : '';

// Server-side validation
$errors = [];

if (empty($name)) {
    $errors[] = "Name is required";
}

if (empty($email)) {
    $errors[] = "Email is required";
} elseif (!validateEmail($email)) {
    $errors[] = "Please enter a valid email address";
}

if (empty($subject)) {
    $errors[] = "Please select a subject";
}

if (empty($message)) {
    $errors[] = "Message is required";
} elseif (strlen($message) < 10) {
    $errors[] = "Message must be at least 10 characters";
}

if (!empty($errors)) {
    $_SESSION['contact_errors'] = $errors;
    redirect('../contact.html');
}

// Save the message to the database (contact_messages table must exist)
$table_exists = false;
$check = mysqli_query($conn, "SHOW TABLES LIKE 'contact_messages'");
if ($check && mysqli_num_rows($check) > 0) {
    $table_exists = true;
}

if ($table_exists) {
    $phone_val = $phone !== '' ? $phone : '';
    $sql = "INSERT INTO contact_messages (name, email, phone, subject, message) VALUES (?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "sssss", $name, $email, $phone_val, $subject, $message);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

setMessage("Thank you for contacting us! We will get back to you within 24-48 hours.", 'success');
redirect('../contact.html');
?>
