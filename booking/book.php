<?php
/**
 * Booking Handler
 * Ethiopian Bus Reservation System
 */

require_once __DIR__ . '/../includes/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    setMessage("Please login to book tickets", 'error');
    $_SESSION['redirect_after_login'] = $_SERVER['HTTP_REFERER'];
    redirect('/bus-reservation-ethiopia/auth/login.html');
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setMessage("Invalid request method", 'error');
    redirect('/bus-reservation-ethiopia/pages/search.html');
}

// Get and sanitize form data
$bus_id = isset($_POST['bus_id']) ? intval($_POST['bus_id']) : 0;
$travel_date = isset($_POST['travel_date']) ? sanitize($_POST['travel_date']) : '';
$passenger_name = isset($_POST['passenger_name']) ? sanitize($_POST['passenger_name']) : '';
$passenger_phone = isset($_POST['passenger_phone']) ? sanitize($_POST['passenger_phone']) : '';
$seats = isset($_POST['seats']) ? sanitize($_POST['seats']) : '';
$user_id = $_SESSION['user_id'];

// Debug: Log received data (remove in production)
error_log("Booking attempt - Bus ID: $bus_id, Date: $travel_date, Seats: $seats, User: $user_id");

// Server-side validation
$errors = [];

if ($bus_id <= 0) {
    $errors[] = "Invalid bus selection";
}

if (empty($travel_date)) {
    $errors[] = "Travel date is required";
} else {
    $selected_date = new DateTime($travel_date);
    $today = new DateTime('today');
    if ($selected_date < $today) {
        $errors[] = "Travel date cannot be in the past";
    }
}

if (empty($passenger_name)) {
    $errors[] = "Passenger name is required";
}

if (empty($passenger_phone)) {
    $errors[] = "Phone number is required";
} elseif (!validatePhone($passenger_phone)) {
    $errors[] = "Please enter a valid Ethiopian phone number";
}

if (empty($seats)) {
    $errors[] = "Please select at least one seat";
}

// Get bus details
if (empty($errors)) {
    $bus_sql = "SELECT b.*, r.origin, r.destination FROM buses b 
                INNER JOIN routes r ON b.route_id = r.id 
                WHERE b.id = ? AND b.status = 'active'";
    $bus_stmt = mysqli_prepare($conn, $bus_sql);
    mysqli_stmt_bind_param($bus_stmt, "i", $bus_id);
    mysqli_stmt_execute($bus_stmt);
    $bus_result = mysqli_stmt_get_result($bus_stmt);
    
    if (mysqli_num_rows($bus_result) === 0) {
        $errors[] = "Bus not found";
    } else {
        $bus = mysqli_fetch_assoc($bus_result);
    }
    mysqli_stmt_close($bus_stmt);
}

// Validate selected seats
$selected_seats = explode(',', $seats);
$seat_ids = [];

if (empty($errors)) {
    foreach ($selected_seats as $seat_num) {
        $seat_num = trim($seat_num);
        if (empty($seat_num)) continue;
        
        // Get seat ID
        $seat_sql = "SELECT id FROM seats WHERE bus_id = ? AND seat_number = ?";
        $seat_stmt = mysqli_prepare($conn, $seat_sql);
        mysqli_stmt_bind_param($seat_stmt, "is", $bus_id, $seat_num);
        mysqli_stmt_execute($seat_stmt);
        $seat_result = mysqli_stmt_get_result($seat_stmt);
        
        if (mysqli_num_rows($seat_result) === 0) {
            $errors[] = "Seat $seat_num does not exist";
        } else {
            $seat_data = mysqli_fetch_assoc($seat_result);
            $seat_id = $seat_data['id'];
            
            // Check if seat is already booked
            $check_sql = "SELECT id FROM bookings 
                         WHERE bus_id = ? AND seat_id = ? AND travel_date = ? AND booking_status != 'cancelled'";
            $check_stmt = mysqli_prepare($conn, $check_sql);
            mysqli_stmt_bind_param($check_stmt, "iis", $bus_id, $seat_id, $travel_date);
            mysqli_stmt_execute($check_stmt);
            $check_result = mysqli_stmt_get_result($check_stmt);
            
            if (mysqli_num_rows($check_result) > 0) {
                $errors[] = "Seat $seat_num is already booked";
            } else {
                $seat_ids[$seat_num] = $seat_id;
            }
            mysqli_stmt_close($check_stmt);
        }
        mysqli_stmt_close($seat_stmt);
    }
}

// If there are errors, redirect back
if (!empty($errors)) {
    setMessage(implode('<br>', $errors), 'error');
    redirect('/bus-reservation-ethiopia/pages/seat.php?bus_id=' . urlencode($bus_id) . '&date=' . urlencode($travel_date));
}

// Calculate total amount
$price_per_seat = $bus['price_birr'];
$total_amount = $price_per_seat * count($seat_ids);

// Generate booking reference
$booking_reference = generateBookingReference();

// Begin transaction
mysqli_begin_transaction($conn);

try {
    // Insert booking for each seat
    $booking_sql = "INSERT INTO bookings (booking_reference, user_id, bus_id, seat_id, travel_date, 
                    passenger_name, passenger_phone, total_amount, payment_status, booking_status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'confirmed')";
    
    $booking_ids = [];
    foreach ($seat_ids as $seat_num => $seat_id) {
        $seat_amount = $price_per_seat; // Price per seat
        $stmt = mysqli_prepare($conn, $booking_sql);
        mysqli_stmt_bind_param($stmt, "siiisssd", $booking_reference, $user_id, $bus_id, $seat_id, 
                              $travel_date, $passenger_name, $passenger_phone, $seat_amount);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Failed to create booking for seat $seat_num");
        }
        $booking_ids[] = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);
    }
    
    // Commit transaction
    mysqli_commit($conn);
    
    // Set success message and redirect to My Bookings
    $success_msg = "You booked successfully! Your booking reference is: <strong>" . htmlspecialchars($booking_reference) . "</strong>. " .
                   htmlspecialchars($bus['origin']) . " → " . htmlspecialchars($bus['destination']) . 
                   " on " . date('M j, Y', strtotime($travel_date)) . ". Seats: " . implode(', ', array_keys($seat_ids)) . 
                   ". Total: " . number_format($total_amount, 2) . " ETB.";
    setMessage($success_msg, 'success');
    
    header("Location: /bus-reservation-ethiopia/booking/my-bookings.php");
    exit();
    
} catch (Exception $e) {
    // Rollback transaction
    mysqli_rollback($conn);
    setMessage("Booking failed: " . $e->getMessage(), 'error');
    redirect('/bus-reservation-ethiopia/pages/seat.php?bus_id=' . urlencode($bus_id) . '&date=' . urlencode($travel_date));
}

mysqli_close($conn);
?>
