<?php
/**
 * Cancel Booking Handler
 * Ethiopian Bus Reservation System
 */

require_once __DIR__ . '/../includes/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('/bus-reservation-ethiopia/auth/login.html');
}

// Get booking reference
$booking_ref = isset($_GET['ref']) ? sanitize($_GET['ref']) : '';

if (empty($booking_ref)) {
    setMessage("Invalid booking reference", 'error');
    redirect('/bus-reservation-ethiopia/booking/my-bookings.php');
}

// Verify the booking belongs to the user
$sql = "SELECT b.*, bs.departure_time FROM bookings b 
        INNER JOIN buses bs ON b.bus_id = bs.id
        WHERE b.booking_reference = ? AND b.user_id = ? AND b.booking_status = 'confirmed'";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "si", $booking_ref, $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    setMessage("Booking not found or already cancelled", 'error');
    redirect('/bus-reservation-ethiopia/booking/my-bookings.php');
}

$booking = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// Check if travel date is at least 24 hours away
$travel_datetime = new DateTime($booking['travel_date'] . ' ' . $booking['departure_time']);
$now = new DateTime();
$diff = $now->diff($travel_datetime);
$hours_until_travel = ($diff->days * 24) + $diff->h;

if ($travel_datetime < $now || $hours_until_travel < 24) {
    setMessage("Cannot cancel bookings less than 24 hours before departure", 'error');
    redirect('my-bookings.php');
}

// Cancel all bookings with this reference
$cancel_sql = "UPDATE bookings SET booking_status = 'cancelled' WHERE booking_reference = ? AND user_id = ?";
$cancel_stmt = mysqli_prepare($conn, $cancel_sql);
mysqli_stmt_bind_param($cancel_stmt, "si", $booking_ref, $_SESSION['user_id']);

if (mysqli_stmt_execute($cancel_stmt)) {
    setMessage("Booking cancelled successfully. Refund will be processed within 3-5 business days.", 'success');
} else {
    setMessage("Failed to cancel booking. Please try again.", 'error');
}

mysqli_stmt_close($cancel_stmt);
mysqli_close($conn);
redirect('/bus-reservation-ethiopia/booking/my-bookings.php');
?>
