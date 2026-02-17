<?php
/**
 * My Bookings Page
 * Ethiopian Bus Reservation System
 */

require_once __DIR__ . '/../includes/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    setMessage("Please login to view your bookings", 'error');
    $_SESSION['redirect_after_login'] = '/bus-reservation-ethiopia/booking/my-bookings.php';
    redirect('/bus-reservation-ethiopia/auth/login.html');
}

$user_id = $_SESSION['user_id'];

// Get user's bookings - Group by booking reference (works even without payment_method/transaction_id columns)
$sql = "SELECT b.booking_reference, b.travel_date, b.passenger_name, b.passenger_phone,
        b.payment_status, b.booking_status, b.created_at,
        bs.bus_name, bs.bus_number, bs.departure_time, bs.arrival_time, bs.bus_type,
        r.origin, r.destination,
        GROUP_CONCAT(s.seat_number ORDER BY CAST(s.seat_number AS UNSIGNED) SEPARATOR ', ') as seat_numbers,
        SUM(b.total_amount) as total_amount
        FROM bookings b
        INNER JOIN buses bs ON b.bus_id = bs.id
        INNER JOIN routes r ON bs.route_id = r.id
        INNER JOIN seats s ON b.seat_id = s.id
        WHERE b.user_id = ?
        GROUP BY b.booking_reference
        ORDER BY b.travel_date DESC, b.created_at DESC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Fetch all bookings
$bookings = [];
while ($row = mysqli_fetch_assoc($result)) {
    $bookings[] = $row;
}
mysqli_stmt_close($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - Ethiopian Bus Reservation</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
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
                <li><a href="/bus-reservation-ethiopia/pages/about.html">About Us</a></li>
                <li><a href="/bus-reservation-ethiopia/pages/contact.html">Contact</a></li>
                <li><a href="/bus-reservation-ethiopia/booking/my-bookings.php" class="active">My Bookings</a></li>
                <li><a href="/bus-reservation-ethiopia/auth/logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <main>
        <div class="container" style="padding: 40px 20px;">
            <h1 style="color: var(--accent-color); margin-bottom: 10px;">My Bookings</h1>
            <p style="color: #666; margin-bottom: 30px;">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>! View and manage your bus bookings.</p>

            <?php
            // Display messages (booking success, errors, etc.)
            $msg = getMessage();
            if ($msg):
                $is_success = ($msg['type'] === 'success');
            ?>
                <div class="alert alert-<?php echo $msg['type']; ?>" style="<?php echo $is_success ? 'font-size: 1rem; padding: 20px; border-left: 5px solid var(--success-color);' : ''; ?>">
                    <?php if ($is_success): ?>
                        <div style="display: flex; align-items: flex-start; gap: 15px;">
                            <span style="font-size: 2rem;">✓</span>
                            <div>
                                <strong style="display: block; margin-bottom: 8px; font-size: 1.1rem;">Booking Confirmed!</strong>
                                <?php echo $msg['message']; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php echo $msg['message']; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if (count($bookings) > 0): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Booking Ref</th>
                                <th>Route</th>
                                <th>Bus</th>
                                <th>Travel Date</th>
                                <th>Time</th>
                                <th>Seats</th>
                                <th>Amount</th>
                                <th>Payment</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $booking): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($booking['booking_reference']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($booking['origin']); ?> → <?php echo htmlspecialchars($booking['destination']); ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($booking['bus_name']); ?><br>
                                        <small style="color: #666;"><?php echo htmlspecialchars($booking['bus_number']); ?></small>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($booking['travel_date'])); ?></td>
                                    <td><?php echo date('h:i A', strtotime($booking['departure_time'])); ?></td>
                                    <td><?php echo htmlspecialchars($booking['seat_numbers']); ?></td>
                                    <td><strong><?php echo number_format($booking['total_amount'], 2); ?> ETB</strong></td>
                                    <td>
                                        <?php
                                        $pay_class = ($booking['payment_status'] === 'paid') ? 'status-confirmed' : 'status-pending';
                                        ?>
                                        <span class="status-badge <?php echo $pay_class; ?>"><?php echo ucfirst($booking['payment_status']); ?></span>
                                    </td>
                                    <td>
                                        <?php
                                        $status_class = '';
                                        switch ($booking['booking_status']) {
                                            case 'confirmed': $status_class = 'status-confirmed'; break;
                                            case 'cancelled': $status_class = 'status-cancelled'; break;
                                            case 'completed': $status_class = 'status-confirmed'; break;
                                            default: $status_class = 'status-pending';
                                        }
                                        ?>
                                        <span class="status-badge <?php echo $status_class; ?>"><?php echo ucfirst($booking['booking_status']); ?></span>
                                    </td>
                                    <td>
                                        <?php 
                                        $ref = $booking['booking_reference'];
                                        if ($booking['booking_status'] === 'confirmed'): 
                                            $travel_date_obj = new DateTime($booking['travel_date']);
                                            $today = new DateTime('today');
                                            $can_cancel = ($travel_date_obj > $today);
                                        ?>
                                            <?php if ($can_cancel): ?>
                                                <a href="cancel-booking.php?ref=<?php echo urlencode($ref); ?>" class="btn btn-danger" style="padding: 5px 10px; font-size: 12px;" onclick="return confirm('Are you sure you want to cancel this booking?')">Cancel</a>
                                            <?php endif; ?>
                                            <button type="button" onclick="printBooking('<?php echo htmlspecialchars($ref, ENT_QUOTES); ?>')" class="btn btn-primary" style="padding: 5px 10px; font-size: 12px;">Print</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                    <?php else: ?>
                <div class="card" style="text-align: center; padding: 60px;">
                    <h3>No bookings found</h3>
                    <p style="color: #666; margin: 20px 0;">You haven't made any bookings yet.</p>
                    <a href="/bus-reservation-ethiopia/pages/search.html" class="btn btn-primary">Book a Bus Now</a>
                </div>
            <?php endif; ?>

            <!-- Booking Statistics -->
            <?php if (count($bookings) > 0): ?>
                <div style="margin-top: 40px;">
                    <h3 style="color: var(--accent-color); margin-bottom: 20px;">Booking Summary</h3>
                    <div class="stats-grid" style="grid-template-columns: repeat(3, 1fr);">
                        <div class="stat-card">
                            <div class="stat-icon blue">🎫</div>
                            <div class="stat-info">
                                <h3><?php echo count($bookings); ?></h3>
                                <p>Total Bookings</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon green">✓</div>
                            <div class="stat-info">
                                <h3><?php 
                                    echo count(array_filter($bookings, function($b) { 
                                        return $b['booking_status'] === 'confirmed'; 
                                    })); 
                                ?></h3>
                                <p>Confirmed</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon yellow">💰</div>
                            <div class="stat-info">
                                <h3><?php 
                                    $total_spent = array_sum(array_column($bookings, 'total_amount'));
                                    echo number_format($total_spent, 0); 
                                ?> ETB</h3>
                                <p>Total Spent</p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
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

    <script src="../assets/js/validation.js"></script>
    <script>
        function printBooking(ref) {
            window.open('/bus-reservation-ethiopia/booking/print-ticket.php?ref=' + encodeURIComponent(ref), '_blank');
        }
    </script>
</body>
</html>
<?php mysqli_close($conn); ?>
