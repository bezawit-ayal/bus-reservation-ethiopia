<?php
/**
 * Payment Page
 * Ethiopian Bus Reservation System
 */

require_once __DIR__ . '/../includes/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    setMessage("Please login to make payment", 'error');
    redirect('/bus-reservation-ethiopia/auth/login.html');
}

// Get booking reference
$booking_ref = isset($_GET['ref']) ? sanitize($_GET['ref']) : '';

if (empty($booking_ref)) {
    setMessage("Invalid booking reference", 'error');
    redirect('/bus-reservation-ethiopia/booking/my-bookings.php');
}

// Get booking details
$sql = "SELECT b.*, bs.bus_name, bs.bus_number, bs.departure_time, bs.arrival_time,
        r.origin, r.destination, s.seat_number
        FROM bookings b
        INNER JOIN buses bs ON b.bus_id = bs.id
        INNER JOIN routes r ON bs.route_id = r.id
        INNER JOIN seats s ON b.seat_id = s.id
        WHERE b.booking_reference = ? AND b.user_id = ? AND b.payment_status = 'pending'
        ORDER BY s.seat_number";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "si", $booking_ref, $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    setMessage("Booking not found or already paid", 'error');
    redirect('/bus-reservation-ethiopia/booking/my-bookings.php');
}

// Group bookings and calculate total
$bookings = [];
$total_amount = 0;
$first_booking = null;

while ($row = mysqli_fetch_assoc($result)) {
    if ($first_booking === null) {
        $first_booking = $row;
    }
    $bookings[] = $row;
    $total_amount += $row['total_amount'];
}
mysqli_stmt_close($stmt);

// Handle payment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_method = isset($_POST['payment_method']) ? sanitize($_POST['payment_method']) : '';
    $transaction_id = isset($_POST['transaction_id']) ? sanitize($_POST['transaction_id']) : '';
    
    if (empty($payment_method)) {
        setMessage("Please select a payment method", 'error');
    } elseif (empty($transaction_id)) {
        setMessage("Please enter transaction ID", 'error');
    } else {
        // Update payment status
        $update_sql = "UPDATE bookings SET payment_status = 'paid', payment_method = ?, transaction_id = ? 
                       WHERE booking_reference = ? AND user_id = ?";
        $update_stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($update_stmt, "sssi", $payment_method, $transaction_id, $booking_ref, $_SESSION['user_id']);
        
                        if (mysqli_stmt_execute($update_stmt)) {
                            setMessage("Payment successful! Your booking is now confirmed.", 'success');
                            redirect('/bus-reservation-ethiopia/booking/my-bookings.php');
                        } else {
            setMessage("Payment update failed. Please try again.", 'error');
        }
        mysqli_stmt_close($update_stmt);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - Ethiopian Bus Reservation</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/bus-reservation-ethiopia/assets/css/style.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container">
            <a href="/bus-reservation-ethiopia/" class="logo">Ethio<span>Bus</span></a>
            <button class="mobile-menu-btn" onclick="toggleMobileMenu()">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <ul class="nav-links">
                <li><a href="/bus-reservation-ethiopia/">Home</a></li>
                <li><a href="/bus-reservation-ethiopia/pages/search.html">Search Bus</a></li>
                <li><a href="/bus-reservation-ethiopia/booking/my-bookings.php" class="active">My Bookings</a></li>
                <li><a href="/bus-reservation-ethiopia/auth/logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <main>
        <div class="container" style="padding: 40px 20px;">
            <h1 style="color: var(--accent-color); margin-bottom: 10px;">Complete Payment</h1>
            <p style="color: #666; margin-bottom: 30px;">Booking Reference: <strong><?php echo htmlspecialchars($booking_ref); ?></strong></p>

            <?php
            // Display messages
            $msg = getMessage();
            if ($msg):
            ?>
                <div class="alert alert-<?php echo $msg['type']; ?>">
                    <?php echo $msg['message']; ?>
                </div>
            <?php endif; ?>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px;">
                
                <!-- Booking Summary -->
                <div class="card">
                    <h3 style="color: var(--accent-color); margin-bottom: 20px;">Booking Summary</h3>
                    
                    <div class="booking-details">
                        <div class="row">
                            <span>Route:</span>
                            <strong><?php echo htmlspecialchars($first_booking['origin']); ?> → <?php echo htmlspecialchars($first_booking['destination']); ?></strong>
                        </div>
                        <div class="row">
                            <span>Bus:</span>
                            <strong><?php echo htmlspecialchars($first_booking['bus_name']); ?> (<?php echo htmlspecialchars($first_booking['bus_number']); ?>)</strong>
                        </div>
                        <div class="row">
                            <span>Travel Date:</span>
                            <strong><?php echo date('l, F j, Y', strtotime($first_booking['travel_date'])); ?></strong>
                        </div>
                        <div class="row">
                            <span>Departure:</span>
                            <strong><?php echo date('h:i A', strtotime($first_booking['departure_time'])); ?></strong>
                        </div>
                        <div class="row">
                            <span>Seats:</span>
                            <strong><?php 
                                $seat_numbers = array_column($bookings, 'seat_number');
                                echo implode(', ', $seat_numbers);
                            ?></strong>
                        </div>
                        <div class="row" style="border-top: 2px solid var(--accent-color); padding-top: 15px; margin-top: 15px;">
                            <span style="font-size: 18px;">Total Amount:</span>
                            <strong style="font-size: 24px; color: var(--success-color);">
                                <?php echo number_format($total_amount, 2); ?> ETB
                            </strong>
                        </div>
                    </div>
                </div>

                <!-- Payment Form -->
                <div class="card">
                    <h3 style="color: var(--accent-color); margin-bottom: 20px;">Select Payment Method</h3>
                    
                    <form method="POST" action="" id="paymentForm" onsubmit="return validatePaymentForm(event)">
                        
                        <!-- Payment Methods -->
                        <div class="form-group">
                            <label>Payment Method</label>
                            <div style="display: grid; gap: 15px; margin-top: 10px;">
                                
                                <!-- CBE (Commercial Bank of Ethiopia) -->
                                <label style="display: flex; align-items: center; padding: 15px; border: 2px solid var(--border-color); border-radius: 8px; cursor: pointer; transition: all 0.3s;" 
                                       onmouseover="this.style.borderColor='var(--primary-color)'" 
                                       onmouseout="this.style.borderColor='var(--border-color)'">
                                    <input type="radio" name="payment_method" value="CBE" required style="margin-right: 15px;">
                                    <div style="flex: 1;">
                                        <strong>Commercial Bank of Ethiopia (CBE)</strong>
                                        <p style="margin: 5px 0 0 0; font-size: 0.9rem; color: #666;">
                                            Account: 1000123456789<br>
                                            Name: EthioBus Ticket Booking
                                        </p>
                                    </div>
                                </label>

                                <!-- Awash Bank -->
                                <label style="display: flex; align-items: center; padding: 15px; border: 2px solid var(--border-color); border-radius: 8px; cursor: pointer; transition: all 0.3s;"
                                       onmouseover="this.style.borderColor='var(--primary-color)'" 
                                       onmouseout="this.style.borderColor='var(--border-color)'">
                                    <input type="radio" name="payment_method" value="Awash Bank" required style="margin-right: 15px;">
                                    <div style="flex: 1;">
                                        <strong>Awash Bank</strong>
                                        <p style="margin: 5px 0 0 0; font-size: 0.9rem; color: #666;">
                                            Account: 0134567890123<br>
                                            Name: EthioBus Ticket Booking
                                        </p>
                                    </div>
                                </label>

                                <!-- Dashen Bank -->
                                <label style="display: flex; align-items: center; padding: 15px; border: 2px solid var(--border-color); border-radius: 8px; cursor: pointer; transition: all 0.3s;"
                                       onmouseover="this.style.borderColor='var(--primary-color)'" 
                                       onmouseout="this.style.borderColor='var(--border-color)'">
                                    <input type="radio" name="payment_method" value="Dashen Bank" required style="margin-right: 15px;">
                                    <div style="flex: 1;">
                                        <strong>Dashen Bank</strong>
                                        <p style="margin: 5px 0 0 0; font-size: 0.9rem; color: #666;">
                                            Account: 1234567890123<br>
                                            Name: EthioBus Ticket Booking
                                        </p>
                                    </div>
                                </label>

                                <!-- Bank of Abyssinia -->
                                <label style="display: flex; align-items: center; padding: 15px; border: 2px solid var(--border-color); border-radius: 8px; cursor: pointer; transition: all 0.3s;"
                                       onmouseover="this.style.borderColor='var(--primary-color)'" 
                                       onmouseout="this.style.borderColor='var(--border-color)'">
                                    <input type="radio" name="payment_method" value="Bank of Abyssinia" required style="margin-right: 15px;">
                                    <div style="flex: 1;">
                                        <strong>Bank of Abyssinia</strong>
                                        <p style="margin: 5px 0 0 0; font-size: 0.9rem; color: #666;">
                                            Account: 9876543210987<br>
                                            Name: EthioBus Ticket Booking
                                        </p>
                                    </div>
                                </label>

                                <!-- M-Pesa -->
                                <label style="display: flex; align-items: center; padding: 15px; border: 2px solid var(--border-color); border-radius: 8px; cursor: pointer; transition: all 0.3s;"
                                       onmouseover="this.style.borderColor='var(--primary-color)'" 
                                       onmouseout="this.style.borderColor='var(--border-color)'">
                                    <input type="radio" name="payment_method" value="M-Pesa" required style="margin-right: 15px;">
                                    <div style="flex: 1;">
                                        <strong>M-Pesa</strong>
                                        <p style="margin: 5px 0 0 0; font-size: 0.9rem; color: #666;">
                                            Phone: +251 911 123 456<br>
                                            Send money to EthioBus
                                        </p>
                                    </div>
                                </label>

                                <!-- Cash at Station -->
                                <label style="display: flex; align-items: center; padding: 15px; border: 2px solid var(--border-color); border-radius: 8px; cursor: pointer; transition: all 0.3s;"
                                       onmouseover="this.style.borderColor='var(--primary-color)'" 
                                       onmouseout="this.style.borderColor='var(--border-color)'">
                                    <input type="radio" name="payment_method" value="Cash at Station" required style="margin-right: 15px;">
                                    <div style="flex: 1;">
                                        <strong>Pay at Station</strong>
                                        <p style="margin: 5px 0 0 0; font-size: 0.9rem; color: #666;">
                                            Pay cash when you arrive at the bus station
                                        </p>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Transaction ID -->
                        <div class="form-group">
                            <label for="transaction_id">Transaction ID / Reference Number</label>
                            <input type="text" id="transaction_id" name="transaction_id" 
                                   placeholder="Enter transaction ID or reference number" required>
                            <small style="color: #666; display: block; margin-top: 5px;">
                                For bank transfers, enter the transaction reference number. For cash payment, enter "CASH".
                            </small>
                        </div>

                        <!-- Payment Instructions -->
                        <div style="background: #e7f3ff; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                            <h4 style="color: var(--primary-color); margin-bottom: 10px; font-size: 0.95rem;">Payment Instructions:</h4>
                            <ul style="list-style: disc; padding-left: 20px; font-size: 0.9rem; color: #666; line-height: 1.8;">
                                <li>For bank transfers, use the account details shown above</li>
                                <li>After transferring, enter the transaction reference number</li>
                                <li>For M-Pesa, send money and enter the M-Pesa transaction code</li>
                                <li>For cash payment, select "Pay at Station" and enter "CASH"</li>
                                <li>Payment verification may take up to 24 hours</li>
                            </ul>
                        </div>

                        <button type="submit" class="btn btn-success" style="width: 100%;">
                            Confirm Payment
                        </button>
                        
                        <a href="/bus-reservation-ethiopia/booking/my-bookings.php" class="btn btn-outline" style="width: 100%; margin-top: 10px; text-align: center;">
                            Cancel
                        </a>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-bottom">
                <p>&copy; 2026 EthioBus. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="/bus-reservation-ethiopia/assets/js/validation.js"></script>
    <script>
        function validatePaymentForm(event) {
            const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
            const transactionId = document.getElementById('transaction_id').value.trim();
            
            if (!paymentMethod) {
                alert('Please select a payment method');
                return false;
            }
            
            if (transactionId === '') {
                alert('Please enter transaction ID or reference number');
                return false;
            }
            
            return true;
        }
    </script>
</body>
</html>
<?php mysqli_close($conn); ?>
