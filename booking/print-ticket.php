<?php
/**
 * Print Ticket Page
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
    echo "Invalid booking reference";
    exit();
}

// Get booking details
$sql = "SELECT b.*, u.full_name, u.email, u.phone as user_phone,
        bs.bus_name, bs.bus_number, bs.departure_time, bs.arrival_time, bs.bus_type,
        r.origin, r.destination, r.distance_km, s.seat_number
        FROM bookings b
        INNER JOIN users u ON b.user_id = u.id
        INNER JOIN buses bs ON b.bus_id = bs.id
        INNER JOIN routes r ON bs.route_id = r.id
        INNER JOIN seats s ON b.seat_id = s.id
        WHERE b.booking_reference = ? AND b.user_id = ?
        ORDER BY s.seat_number ASC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "si", $booking_ref, $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    echo "Booking not found";
    exit();
}

// Collect booking data
$booking = null;
$seats = [];
$total = 0;

while ($row = mysqli_fetch_assoc($result)) {
    if (!$booking) {
        $booking = $row;
    }
    $seats[] = $row['seat_number'];
    $total += $row['total_amount'];
}
mysqli_stmt_close($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Ticket - <?php echo htmlspecialchars($booking_ref); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Poppins', Arial, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        .ticket {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 30px rgba(0,0,0,0.1);
        }
        .ticket-header {
            background: linear-gradient(135deg, #2C3E50 0%, #1a252f 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .ticket-header h1 {
            font-size: 28px;
            margin-bottom: 5px;
        }
        .ticket-header .logo {
            color: #F7DC6F;
        }
        .ticket-header .subtitle {
            opacity: 0.8;
            font-size: 14px;
        }
        .ticket-body {
            padding: 30px;
        }
        .reference-box {
            background: #F7DC6F;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 30px;
        }
        .reference-box .label {
            font-size: 12px;
            color: #666;
        }
        .reference-box .value {
            font-size: 24px;
            font-weight: 700;
            color: #2C3E50;
        }
        .route-display {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 30px;
            margin: 30px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        .route-display .city {
            text-align: center;
        }
        .route-display .city h3 {
            font-size: 24px;
            color: #2C3E50;
        }
        .route-display .city p {
            font-size: 14px;
            color: #666;
        }
        .route-display .arrow {
            font-size: 36px;
            color: #F7DC6F;
        }
        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .detail-item {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .detail-item .label {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
        }
        .detail-item .value {
            font-size: 16px;
            font-weight: 600;
            color: #2C3E50;
        }
        .ticket-footer {
            border-top: 2px dashed #ddd;
            padding: 20px 30px;
            background: #f8f9fa;
        }
        .important-info {
            font-size: 13px;
            color: #666;
            line-height: 1.8;
        }
        .important-info h4 {
            color: #2C3E50;
            margin-bottom: 10px;
        }
        .important-info ul {
            padding-left: 20px;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-confirmed {
            background: rgba(39, 174, 96, 0.2);
            color: #27AE60;
        }
        .print-btn {
            display: block;
            width: 200px;
            margin: 30px auto;
            padding: 15px;
            background: #2C3E50;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
        }
        .print-btn:hover {
            background: #1a252f;
        }
        @media print {
            body {
                background: white;
                padding: 0;
            }
            .ticket {
                box-shadow: none;
            }
            .print-btn {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="ticket">
        <div class="ticket-header">
            <h1><span class="logo">Ethio</span>Bus</h1>
            <p class="subtitle">Ethiopian Bus Reservation System - E-Ticket</p>
        </div>
        
        <div class="ticket-body">
            <div class="reference-box">
                <div class="label">Booking Reference</div>
                <div class="value"><?php echo htmlspecialchars($booking_ref); ?></div>
            </div>
            
            <div class="route-display">
                <div class="city">
                    <h3><?php echo htmlspecialchars($booking['origin']); ?></h3>
                    <p><?php echo date('h:i A', strtotime($booking['departure_time'])); ?></p>
                </div>
                <div class="arrow">→</div>
                <div class="city">
                    <h3><?php echo htmlspecialchars($booking['destination']); ?></h3>
                    <p><?php echo date('h:i A', strtotime($booking['arrival_time'])); ?></p>
                </div>
            </div>
            
            <div class="details-grid">
                <div class="detail-item">
                    <div class="label">Passenger Name</div>
                    <div class="value"><?php echo htmlspecialchars($booking['passenger_name']); ?></div>
                </div>
                <div class="detail-item">
                    <div class="label">Phone Number</div>
                    <div class="value"><?php echo htmlspecialchars($booking['passenger_phone']); ?></div>
                </div>
                <div class="detail-item">
                    <div class="label">Travel Date</div>
                    <div class="value"><?php echo date('l, F j, Y', strtotime($booking['travel_date'])); ?></div>
                </div>
                <div class="detail-item">
                    <div class="label">Bus Details</div>
                    <div class="value"><?php echo htmlspecialchars($booking['bus_name']); ?> (<?php echo htmlspecialchars($booking['bus_number']); ?>)</div>
                </div>
                <div class="detail-item">
                    <div class="label">Bus Type</div>
                    <div class="value"><?php echo htmlspecialchars($booking['bus_type']); ?></div>
                </div>
                <div class="detail-item">
                    <div class="label">Seat Numbers</div>
                    <div class="value"><?php echo implode(', ', $seats); ?></div>
                </div>
                <div class="detail-item">
                    <div class="label">Total Amount</div>
                    <div class="value" style="color: #27AE60; font-size: 20px;"><?php echo number_format($total, 2); ?> ETB</div>
                </div>
                <div class="detail-item">
                    <div class="label">Status</div>
                    <div class="value">
                        <span class="status-badge status-<?php echo $booking['booking_status']; ?>">
                            <?php echo ucfirst($booking['booking_status']); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="ticket-footer">
            <div class="important-info">
                <h4>Important Information</h4>
                <ul>
                    <li>Please arrive at the bus station at least 30 minutes before departure</li>
                    <li>Bring a valid ID and this e-ticket (printed or on your phone)</li>
                    <li>Payment should be made at the bus station counter if not paid online</li>
                    <li>For assistance, contact us at +251 911 123 456</li>
                </ul>
            </div>
        </div>
    </div>
    
    <button class="print-btn" onclick="window.print()">Print Ticket</button>
</body>
</html>
<?php mysqli_close($conn); ?>
