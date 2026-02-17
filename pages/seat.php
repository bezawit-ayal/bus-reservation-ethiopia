<?php
/**
 * Seat Selection Page
 * Ethiopian Bus Reservation System
 */

require_once __DIR__ . '/../includes/config.php';

// Get bus ID and date from URL (accept multiple param names and fall back to session or today)
$bus_id = isset($_GET['bus_id']) ? intval($_GET['bus_id']) : 0;
if (isset($_GET['date'])) {
    $travel_date = sanitize($_GET['date']);
} elseif (isset($_GET['travel_date'])) {
    $travel_date = sanitize($_GET['travel_date']);
} elseif (!empty($_SESSION['search']['travel_date'])) {
    $travel_date = sanitize($_SESSION['search']['travel_date']);
} else {
    // fallback to today if no date provided (useful for direct links from homepage)
    $travel_date = date('Y-m-d');
}

// Validate parameters
if ($bus_id <= 0) {
    setMessage("Invalid bus selection", 'error');
    redirect('/bus-reservation-ethiopia/pages/search.html');
}

// Get bus details
$sql = "SELECT b.*, r.origin, r.destination, r.distance_km, r.duration_hours 
        FROM buses b 
        INNER JOIN routes r ON b.route_id = r.id 
        WHERE b.id = ? AND b.status = 'active'";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $bus_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    setMessage("Bus not found", 'error');
    redirect('/bus-reservation-ethiopia/pages/search.html');
}

$bus = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// Get all seats for this bus
$seats_sql = "SELECT * FROM seats WHERE bus_id = ? ORDER BY CAST(seat_number AS UNSIGNED)";
$seats_stmt = mysqli_prepare($conn, $seats_sql);
mysqli_stmt_bind_param($seats_stmt, "i", $bus_id);
mysqli_stmt_execute($seats_stmt);
$seats_result = mysqli_stmt_get_result($seats_stmt);

// Get booked seats for this bus and date
$booked_sql = "SELECT seat_id FROM bookings 
               WHERE bus_id = ? AND travel_date = ? AND booking_status != 'cancelled'";
$booked_stmt = mysqli_prepare($conn, $booked_sql);
mysqli_stmt_bind_param($booked_stmt, "is", $bus_id, $travel_date);
mysqli_stmt_execute($booked_stmt);
$booked_result = mysqli_stmt_get_result($booked_stmt);

$booked_seat_ids = [];
while ($row = mysqli_fetch_assoc($booked_result)) {
    $booked_seat_ids[] = $row['seat_id'];
}
mysqli_stmt_close($booked_stmt);

// Build seats array
$seats = [];
$booked_seat_numbers = [];
while ($seat = mysqli_fetch_assoc($seats_result)) {
    $seats[] = $seat;
    if (in_array($seat['id'], $booked_seat_ids)) {
        $booked_seat_numbers[] = (string)$seat['seat_number'];
    }
}
mysqli_stmt_close($seats_stmt);

// If no seats exist in database, we need to create them or warn user
$seats_exist = count($seats) > 0;

// Store booking info in session
$_SESSION['booking'] = [
    'bus_id' => $bus_id,
    'travel_date' => $travel_date,
    'bus_name' => $bus['bus_name'],
    'bus_number' => $bus['bus_number'],
    'origin' => $bus['origin'],
    'destination' => $bus['destination'],
    'departure_time' => $bus['departure_time'],
    'arrival_time' => $bus['arrival_time'],
    'price' => $bus['price_birr']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Seats - <?php echo htmlspecialchars($bus['bus_name']); ?> - Ethiopian Bus Reservation</title>
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
            <h1 style="text-align: center; color: var(--accent-color); margin-bottom: 10px;">Select Your Seats</h1>
            <p style="text-align: center; color: #666; margin-bottom: 40px;">
                <?php echo htmlspecialchars($bus['origin']); ?> → <?php echo htmlspecialchars($bus['destination']); ?> | 
                <?php echo date('l, F j, Y', strtotime($travel_date)); ?>
            </p>

            <?php
            // Display messages
            $msg = getMessage();
            if ($msg):
            ?>
                <div class="alert alert-<?php echo $msg['type']; ?>">
                    <?php echo $msg['message']; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!$seats_exist): ?>
                <div class="alert alert-warning">
                    <strong>Note:</strong> Seats are not yet configured for this bus in the database. 
                    Please ensure the database has been properly set up with the GenerateSeats procedure.
                </div>
            <?php endif; ?>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px;">
                
                <!-- Bus Layout -->
                <div class="card">
                    <h3 style="text-align: center; margin-bottom: 20px; color: var(--accent-color);">
                        <?php echo htmlspecialchars($bus['bus_name']); ?> - Seating Layout
                    </h3>
                    
                    <div class="bus-layout">
                        <div class="bus-front" style="text-align: center; padding: 15px; background: #333; color: white; border-radius: 8px 8px 0 0; margin-bottom: 20px;">
                            <strong>FRONT (Driver)</strong>
                        </div>
                        
                        <!-- Seat Legend -->
                        <div class="seat-legend" style="display: flex; justify-content: center; gap: 30px; margin-bottom: 20px;">
                            <div class="legend-item" style="display: flex; align-items: center; gap: 8px;">
                                <div style="width: 25px; height: 25px; background: #e8f5e9; border: 2px solid #1a5f2a; border-radius: 5px;"></div>
                                <span>Available</span>
                            </div>
                            <div class="legend-item" style="display: flex; align-items: center; gap: 8px;">
                                <div style="width: 25px; height: 25px; background: #d4a53c; border-radius: 5px;"></div>
                                <span>Selected</span>
                            </div>
                            <div class="legend-item" style="display: flex; align-items: center; gap: 8px;">
                                <div style="width: 25px; height: 25px; background: #ccc; border-radius: 5px;"></div>
                                <span>Booked</span>
                            </div>
                        </div>
                        
                        <!-- Seat Grid -->
                        <div id="seatGrid" style="display: flex; flex-direction: column; gap: 10px; padding: 20px; background: #f5f5f5; border-radius: 8px; position: relative; z-index: 1;">
                            <?php
                            // Render seats using actual seats from DB (ensures correct seat ids and numbers)
                            $idx = 0;
                            $seat_count = count($seats);
                            while ($idx < $seat_count):
                            ?>
                            <div style="display: flex; justify-content: center; gap: 10px;">
                                <?php
                                // Left side seats (2)
                                for ($i = 0; $i < 2 && $idx < $seat_count; $i++):
                                    $s = $seats[$idx];
                                    $seat_number = $s['seat_number'];
                                    $is_booked = in_array($s['id'], $booked_seat_ids);
                                    $booked_class = $is_booked ? 'booked' : '';
                                ?>
                                <div class="seat <?php echo $booked_class; ?>" 
                                     data-seat="<?php echo htmlspecialchars($seat_number); ?>"
                                     style="width: 45px; height: 45px; display: flex; align-items: center; justify-content: center; 
                                            border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s;
                                            user-select: none; -webkit-user-select: none; position: relative; z-index: 2;
                                            <?php if ($is_booked): ?>
                                            background: #ccc; border: 2px solid #999; color: #666; cursor: not-allowed; pointer-events: none;
                                            <?php else: ?>
                                            background: #e8f5e9; border: 2px solid #1a5f2a; color: #1a5f2a; pointer-events: auto;
                                            <?php endif; ?>">
                                    <?php echo htmlspecialchars($seat_number); ?>
                                </div>
                                <?php 
                                    $idx++;
                                endfor;
                                ?>

                                <!-- Aisle Space -->
                                <div style="width: 40px;"></div>

                                <?php
                                // Right side seats (2)
                                for ($i = 0; $i < 2 && $idx < $seat_count; $i++):
                                    $s = $seats[$idx];
                                    $seat_number = $s['seat_number'];
                                    $is_booked = in_array($s['id'], $booked_seat_ids);
                                    $booked_class = $is_booked ? 'booked' : '';
                                ?>
                                <div class="seat <?php echo $booked_class; ?>" 
                                     data-seat="<?php echo htmlspecialchars($seat_number); ?>"
                                     style="width: 45px; height: 45px; display: flex; align-items: center; justify-content: center; 
                                            border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s;
                                            user-select: none; -webkit-user-select: none; position: relative; z-index: 2;
                                            <?php if ($is_booked): ?>
                                            background: #ccc; border: 2px solid #999; color: #666; cursor: not-allowed; pointer-events: none;
                                            <?php else: ?>
                                            background: #e8f5e9; border: 2px solid #1a5f2a; color: #1a5f2a; pointer-events: auto;
                                            <?php endif; ?>">
                                    <?php echo htmlspecialchars($seat_number); ?>
                                </div>
                                <?php 
                                    $idx++;
                                endfor;
                                ?>
                            </div>
                            <?php endwhile; ?>
                        </div>
                        
                        <div class="bus-back" style="text-align: center; padding: 10px; background: #666; color: white; border-radius: 0 0 8px 8px; margin-top: 20px;">
                            <strong>BACK</strong>
                        </div>
                    </div>
                    
                </div>

                <!-- Booking Details -->
                <div>
                    <!-- Trip Summary -->
                    <div class="card mb-30">
                        <h3 style="color: var(--accent-color); margin-bottom: 20px;">Trip Summary</h3>
                        
                        <div class="booking-details" style="background: none; padding: 0;">
                            <div class="row">
                                <span>Bus Name:</span>
                                <strong><?php echo htmlspecialchars($bus['bus_name']); ?></strong>
                            </div>
                            <div class="row">
                                <span>Bus Number:</span>
                                <strong><?php echo htmlspecialchars($bus['bus_number']); ?></strong>
                            </div>
                            <div class="row">
                                <span>Route:</span>
                                <strong><?php echo htmlspecialchars($bus['origin']); ?> → <?php echo htmlspecialchars($bus['destination']); ?></strong>
                            </div>
                            <div class="row">
                                <span>Travel Date:</span>
                                <strong><?php echo date('l, F j, Y', strtotime($travel_date)); ?></strong>
                            </div>
                            <div class="row">
                                <span>Departure:</span>
                                <strong><?php echo date('h:i A', strtotime($bus['departure_time'])); ?></strong>
                            </div>
                            <div class="row">
                                <span>Arrival:</span>
                                <strong><?php echo date('h:i A', strtotime($bus['arrival_time'])); ?></strong>
                            </div>
                            <div class="row">
                                <span>Duration:</span>
                                <strong><?php echo $bus['duration_hours']; ?> hours</strong>
                            </div>
                            <div class="row">
                                <span>Bus Type:</span>
                                <strong><?php echo htmlspecialchars($bus['bus_type']); ?></strong>
                            </div>
                            <div class="row">
                                <span>Price per Seat:</span>
                                <strong><?php echo number_format($bus['price_birr'], 2); ?> ETB</strong>
                            </div>
                        </div>
                    </div>

                    <!-- Booking Form -->
                    <div class="card">
                        <h3 style="color: var(--accent-color); margin-bottom: 20px;">Passenger Details</h3>
                        
                        <?php if (!isLoggedIn()): ?>
                            <div class="alert alert-warning">
                                Please <a href="/bus-reservation-ethiopia/auth/login.html">login</a> or <a href="/bus-reservation-ethiopia/auth/register.html">register</a> to book tickets.
                            </div>
                        <?php endif; ?>
                        
                        <form id="bookingForm" action="/bus-reservation-ethiopia/booking/book.php" method="POST" onsubmit="return validateBookingForm(event);">
                            <!-- Hidden fields -->
                            <input type="hidden" name="bus_id" value="<?php echo $bus_id; ?>">
                            <input type="hidden" name="travel_date" value="<?php echo $travel_date; ?>">
                            <input type="hidden" id="pricePerSeat" value="<?php echo $bus['price_birr']; ?>">
                            <input type="hidden" id="selectedSeatsInput" name="seats" value="">
                            
                            <div class="form-group">
                                <label for="passengerName">Passenger Name</label>
                                <input type="text" id="passengerName" name="passenger_name" 
                                       placeholder="Enter passenger name" required
                                       value="<?php echo isLoggedIn() ? htmlspecialchars($_SESSION['user_name']) : ''; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="passengerPhone">Phone Number</label>
                                <input type="tel" id="passengerPhone" name="passenger_phone" 
                                       placeholder="e.g., 0911234567" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Selected Seats</label>
                                <div style="padding: 12px 15px; background: var(--light-gray); border-radius: 8px;">
                                    <span id="selectedSeatsDisplay">None</span>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Total Amount</label>
                                <div style="padding: 12px 15px; background: var(--light-gray); border-radius: 8px; font-size: 24px; font-weight: 700; color: var(--success-color);">
                                    <span id="totalPrice">0.00 ETB</span>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-success" style="width: 100%; margin-top: 10px;"
                                    <?php echo !isLoggedIn() ? 'disabled' : ''; ?>>
                                <?php echo isLoggedIn() ? 'Confirm Booking' : 'Login to Book'; ?>
                            </button>
                        </form>
                        
                        <p style="text-align: center; margin-top: 15px; font-size: 14px; color: #666;">
                            <a href="/bus-reservation-ethiopia/pages/search.php?origin=<?php echo urlencode($bus['origin']); ?>&destination=<?php echo urlencode($bus['destination']); ?>&travel_date=<?php echo $travel_date; ?>">
                                ← Back to Search Results
                            </a>
                        </p>
                    </div>
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
        // Seat Selection Script - loads AFTER validation.js so our validateBookingForm overrides
        (function() {
            'use strict';
            
            // Variables (used by selectSeat and updateDisplay)
            let selectedSeats = [];
            let pricePerSeat = 0;
            const maxSeats = 5;
            
            // Select/Deselect seat function - Global scope
            window.selectSeat = function(seatElement) {
                if (!seatElement) {
                    console.error('selectSeat: No element provided');
                    return false;
                }
                
                // Check if seat is booked
                if (seatElement.classList.contains('booked')) {
                    alert('This seat is already booked!');
                    return false;
                }
                
                const seatNumber = seatElement.getAttribute('data-seat');
                if (!seatNumber) {
                    console.error('selectSeat: Missing data-seat attribute');
                    return false;
                }
                
                console.log('Seat clicked:', seatNumber);
                
                if (seatElement.classList.contains('selected')) {
                    // Deselect
                    seatElement.classList.remove('selected');
                    seatElement.style.background = '#e8f5e9';
                    seatElement.style.borderColor = '#1a5f2a';
                    seatElement.style.color = '#1a5f2a';
                    selectedSeats = selectedSeats.filter(s => s !== seatNumber);
                } else {
                    // Check limit
                    if (selectedSeats.length >= maxSeats) {
                        alert('You can select maximum ' + maxSeats + ' seats per booking!');
                        return false;
                    }
                    
                    // Select
                    seatElement.classList.add('selected');
                    seatElement.style.background = '#d4a53c';
                    seatElement.style.borderColor = '#b8912f';
                    seatElement.style.color = 'white';
                    selectedSeats.push(seatNumber);
                }
                
                updateDisplay();
                return true;
            };
            
            // Update display
            function updateDisplay() {
                const displayElement = document.getElementById('selectedSeatsDisplay');
                if (displayElement) {
                    displayElement.textContent = selectedSeats.length > 0 ? 'Seat ' + selectedSeats.join(', Seat ') : 'None';
                }
                
                const hiddenInput = document.getElementById('selectedSeatsInput');
                if (hiddenInput) {
                    hiddenInput.value = selectedSeats.join(',');
                }
                
                const totalElement = document.getElementById('totalPrice');
                if (totalElement) {
                    const total = selectedSeats.length * pricePerSeat;
                    totalElement.textContent = total.toFixed(2) + ' ETB';
                }
            }
            
            // Initialize when DOM is ready
            function initSeatSelection() {
                console.log('Initializing seat selection...');
                
                // Get price
                const priceElement = document.getElementById('pricePerSeat');
                if (priceElement) {
                    pricePerSeat = parseFloat(priceElement.value) || 0;
                    console.log('Price per seat:', pricePerSeat);
                }
                
                // Get all available seats
                const seats = document.querySelectorAll('.seat:not(.booked)');
                console.log('Found', seats.length, 'available seats');
                
                if (seats.length === 0) {
                    console.warn('No available seats found!');
                    return;
                }
                
                // Attach click handlers to each seat
                seats.forEach(function(seat) {
                    // Remove any existing listeners by cloning
                    const newSeat = seat.cloneNode(true);
                    seat.parentNode.replaceChild(newSeat, seat);
                    
                    // Add click handler
                    newSeat.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        console.log('Direct click on seat:', this.getAttribute('data-seat'));
                        window.selectSeat(this);
                    }, false);
                    
                    // Hover effects
                    newSeat.addEventListener('mouseenter', function() {
                        if (!this.classList.contains('selected') && !this.classList.contains('booked')) {
                            this.style.background = '#c8e6c9';
                            this.style.transform = 'scale(1.05)';
                        }
                    });
                    
                    newSeat.addEventListener('mouseleave', function() {
                        if (!this.classList.contains('selected') && !this.classList.contains('booked')) {
                            this.style.background = '#e8f5e9';
                            this.style.transform = 'scale(1)';
                        }
                    });
                });
                
                // Also use event delegation on seat grid
                const seatGrid = document.getElementById('seatGrid');
                if (seatGrid) {
                    seatGrid.addEventListener('click', function(e) {
                        const seat = e.target.closest('.seat');
                        if (seat && !seat.classList.contains('booked')) {
                            e.preventDefault();
                            e.stopPropagation();
                            console.log('Delegated click on seat:', seat.getAttribute('data-seat'));
                            window.selectSeat(seat);
                        }
                    }, false);
                }
                
                console.log('Seat selection initialized successfully');
            }
            
            // Wait for DOM to be ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initSeatSelection);
            } else {
                // DOM already loaded
                initSeatSelection();
            }
            
        })();
        
        // Override validateBookingForm - must be defined AFTER validation.js so this is used.
        // Reads seats from #selectedSeatsInput (updated by our seat script).
        function validateBookingForm(event) {
            if (event) event.preventDefault();
            
            var form = document.getElementById('bookingForm');
            var passengerName = document.getElementById('passengerName');
            var passengerPhone = document.getElementById('passengerPhone');
            var selectedSeatsInput = document.getElementById('selectedSeatsInput');
            
            if (!form || !selectedSeatsInput) {
                alert('Form error. Please refresh the page.');
                return false;
            }
            
            var nameValue = passengerName ? passengerName.value.trim() : '';
            var phoneValue = passengerPhone ? passengerPhone.value.trim() : '';
            var seatsValue = selectedSeatsInput.value.trim();
            var seats = seatsValue ? seatsValue.split(',').filter(function(s){ return s.trim() !== ''; }) : [];
            
            if (nameValue === '') {
                alert('Please enter passenger name');
                if (passengerName) passengerName.focus();
                return false;
            }
            if (phoneValue === '') {
                alert('Please enter phone number');
                if (passengerPhone) passengerPhone.focus();
                return false;
            }
            var phoneRegex = /^(\+251|0)(9|7)[0-9]{8}$/;
            if (!phoneRegex.test(phoneValue.replace(/\s/g, ''))) {
                alert('Please enter a valid Ethiopian phone number (e.g., 0911234567)');
                if (passengerPhone) passengerPhone.focus();
                return false;
            }
            if (seats.length === 0) {
                alert('Please select at least one seat!');
                return false;
            }
            
            var btn = form.querySelector('button[type="submit"]');
            if (btn) { btn.disabled = true; btn.textContent = 'Processing...'; }
            form.submit();
            return false;
        }
    </script>
</body>
</html>
<?php mysqli_close($conn); ?>
