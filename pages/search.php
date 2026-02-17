<?php
/**
 * Bus Search Handler
 * Ethiopian Bus Reservation System
 */

require_once __DIR__ . '/../includes/config.php';

// Get search parameters
$origin = isset($_GET['origin']) ? sanitize($_GET['origin']) : '';
$destination = isset($_GET['destination']) ? sanitize($_GET['destination']) : '';
$travel_date = isset($_GET['travel_date']) ? sanitize($_GET['travel_date']) : '';

// Validate search parameters
$errors = [];

if (empty($origin)) {
    $errors[] = "Please select a departure city";
}

if (empty($destination)) {
    $errors[] = "Please select a destination city";
}

if ($origin === $destination && !empty($origin)) {
    $errors[] = "Origin and destination cannot be the same";
}

if (empty($travel_date)) {
    $errors[] = "Please select a travel date";
} else {
    // Check if date is not in the past
    $selected_date = new DateTime($travel_date);
    $today = new DateTime('today');
    if ($selected_date < $today) {
        $errors[] = "Travel date cannot be in the past";
    }
}

// Store search parameters in session
$_SESSION['search'] = [
    'origin' => $origin,
    'destination' => $destination,
    'travel_date' => $travel_date
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results - Ethiopian Bus Reservation</title>
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
                <li><a href="/bus-reservation-ethiopia/pages/search.html" class="active">Search Bus</a></li>
                <li><a href="/bus-reservation-ethiopia/pages/about.html">About Us</a></li>
                <li><a href="/bus-reservation-ethiopia/pages/contact.html">Contact</a></li>
                <?php if (isLoggedIn()): ?>
                    <li><a href="/bus-reservation-ethiopia/booking/my-bookings.php">My Bookings</a></li>
                    <li><a href="/bus-reservation-ethiopia/auth/logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="/bus-reservation-ethiopia/auth/login.html">Login</a></li>
                    <li><a href="/bus-reservation-ethiopia/auth/register.html">Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <main>
        <div class="container" style="padding: 40px 20px;">
            
            <!-- Search Form -->
            <div class="search-box" style="margin-top: 0;">
                <form id="searchForm" action="search.php" method="GET">
                    <div class="search-form">
                        <div class="form-group">
                            <label for="origin">From (Departure City)</label>
                            <select id="origin" name="origin" required>
                                <option value="">Select Departure City</option>
                                <?php
                                $cities = getEthiopianCities();
                                foreach ($cities as $city) {
                                    $selected = ($city === $origin) ? 'selected' : '';
                                    echo "<option value=\"$city\" $selected>$city</option>";
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="destination">To (Destination City)</label>
                            <select id="destination" name="destination" required>
                                <option value="">Select Destination City</option>
                                <?php
                                foreach ($cities as $city) {
                                    $selected = ($city === $destination) ? 'selected' : '';
                                    echo "<option value=\"$city\" $selected>$city</option>";
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="travelDate">Travel Date</label>
                            <input type="date" id="travelDate" name="travel_date" value="<?php echo $travel_date; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary" style="width: 100%;">
                                Search Buses
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Display Errors -->
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error" style="margin-top: 20px;">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo $error; ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Search Results -->
            <?php if (empty($errors) && !empty($origin) && !empty($destination)): ?>
                <section style="margin-top: 40px;">
                    <div class="section-title">
                        <h2>Available Buses: <?php echo "$origin → $destination"; ?></h2>
                        <p>Travel Date: <?php echo date('l, F j, Y', strtotime($travel_date)); ?></p>
                    </div>

                    <div class="bus-list" id="busResults">
                        <?php
                        // Query to find buses for the route
                        $sql = "SELECT b.*, r.origin, r.destination, r.distance_km, r.duration_hours 
                                FROM buses b 
                                INNER JOIN routes r ON b.route_id = r.id 
                                WHERE r.origin = ? AND r.destination = ? AND b.status = 'active'
                                ORDER BY b.departure_time ASC";
                        
                        $stmt = mysqli_prepare($conn, $sql);
                        mysqli_stmt_bind_param($stmt, "ss", $origin, $destination);
                        mysqli_stmt_execute($stmt);
                        $result = mysqli_stmt_get_result($stmt);
                        
                        if (mysqli_num_rows($result) > 0):
                            while ($bus = mysqli_fetch_assoc($result)):
                                // Count available seats
                                $seat_sql = "SELECT COUNT(*) as available FROM seats s 
                                            WHERE s.bus_id = ? 
                                            AND s.id NOT IN (
                                                SELECT seat_id FROM bookings 
                                                WHERE bus_id = ? AND travel_date = ? AND booking_status != 'cancelled'
                                            )";
                                $seat_stmt = mysqli_prepare($conn, $seat_sql);
                                mysqli_stmt_bind_param($seat_stmt, "iis", $bus['id'], $bus['id'], $travel_date);
                                mysqli_stmt_execute($seat_stmt);
                                $seat_result = mysqli_stmt_get_result($seat_stmt);
                                $seat_data = mysqli_fetch_assoc($seat_result);
                                $available_seats = $seat_data['available'];
                                mysqli_stmt_close($seat_stmt);
                        ?>
                            <div class="card bus-card">
                                <div class="bus-info">
                                    <h3><?php echo htmlspecialchars($bus['bus_name']); ?></h3>
                                    <p><strong>Bus No:</strong> <?php echo htmlspecialchars($bus['bus_number']); ?></p>
                                    <p><strong>Type:</strong> <?php echo htmlspecialchars($bus['bus_type']); ?></p>
                                    <p>
                                        <span class="status-badge <?php echo $available_seats > 0 ? 'status-confirmed' : 'status-cancelled'; ?>">
                                            <?php echo $available_seats; ?> Seats Available
                                        </span>
                                    </p>
                                </div>
                                <div class="bus-route">
                                    <div class="route-display">
                                        <span class="city"><?php echo htmlspecialchars($bus['origin']); ?></span>
                                        <span class="arrow">→</span>
                                        <span class="city"><?php echo htmlspecialchars($bus['destination']); ?></span>
                                    </div>
                                    <div class="bus-time">
                                        <div class="time-item">
                                            <div class="label">Departure</div>
                                            <div class="value"><?php echo date('h:i A', strtotime($bus['departure_time'])); ?></div>
                                        </div>
                                        <div class="time-item">
                                            <div class="label">Arrival</div>
                                            <div class="value"><?php echo date('h:i A', strtotime($bus['arrival_time'])); ?></div>
                                        </div>
                                        <div class="time-item">
                                            <div class="label">Duration</div>
                                            <div class="value"><?php echo $bus['duration_hours']; ?> hrs</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="bus-price">
                                    <div class="price"><?php echo number_format($bus['price_birr'], 2); ?> <span>ETB</span></div>
                                    <?php if ($available_seats > 0): ?>
                                        <a href="/bus-reservation-ethiopia/pages/seat.php?bus_id=<?php echo $bus['id']; ?>&date=<?php echo $travel_date; ?>" class="btn btn-primary">
                                            Select Seats
                                        </a>
                                    <?php else: ?>
                                        <button class="btn btn-danger" disabled>Fully Booked</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php 
                            endwhile;
                        else:
                        ?>
                            <div class="card" style="text-align: center; padding: 50px;">
                                <h3>No buses found</h3>
                                <p style="color: #666; margin-top: 10px;">
                                    Sorry, no buses are available for the route <strong><?php echo "$origin → $destination"; ?></strong> on <strong><?php echo date('F j, Y', strtotime($travel_date)); ?></strong>.
                                </p>
                                <p style="color: #666; margin-top: 10px;">Please try a different date or route.</p>
                            </div>
                        <?php endif; ?>
                        
                        <?php mysqli_stmt_close($stmt); ?>
                    </div>
                </section>
            <?php else: ?>
                <div class="card" style="text-align: center; padding: 50px; margin-top: 40px;">
                    <h3>Search for Buses</h3>
                    <p style="color: #666; margin-top: 10px;">
                        Enter your departure city, destination, and travel date to find available buses.
                    </p>
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

    <script src="/bus-reservation-ethiopia/assets/js/validation.js"></script>
</body>
</html>
<?php mysqli_close($conn); ?>
