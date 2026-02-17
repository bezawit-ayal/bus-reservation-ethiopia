<?php
/**
 * Admin - View Bookings
 * Ethiopian Bus Reservation System
 */

require_once __DIR__ . '/../includes/config.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    redirect('login.php');
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_status') {
        $booking_ref = sanitize($_POST['booking_ref']);
        $new_status = sanitize($_POST['status']);
        
        $sql = "UPDATE bookings SET booking_status = ? WHERE booking_reference = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $new_status, $booking_ref);
        
        if (mysqli_stmt_execute($stmt)) {
            setMessage("Booking status updated successfully!", 'success');
        } else {
            setMessage("Failed to update booking status", 'error');
        }
        mysqli_stmt_close($stmt);
    }
    
    redirect('bookings.php');
}

// Filter parameters
$filter_status = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$filter_date = isset($_GET['date']) ? sanitize($_GET['date']) : '';
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// Build query
$sql = "SELECT b.*, u.full_name, u.email, u.phone as user_phone, 
        bs.bus_name, bs.bus_number, r.origin, r.destination, s.seat_number
        FROM bookings b
        INNER JOIN users u ON b.user_id = u.id
        INNER JOIN buses bs ON b.bus_id = bs.id
        INNER JOIN routes r ON bs.route_id = r.id
        INNER JOIN seats s ON b.seat_id = s.id
        WHERE 1=1";

$params = [];
$types = "";

if ($filter_status) {
    $sql .= " AND b.booking_status = ?";
    $params[] = $filter_status;
    $types .= "s";
}

if ($filter_date) {
    $sql .= " AND b.travel_date = ?";
    $params[] = $filter_date;
    $types .= "s";
}

if ($search) {
    $sql .= " AND (b.booking_reference LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

$sql .= " ORDER BY b.created_at DESC";

$stmt = mysqli_prepare($conn, $sql);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Group bookings by reference
$bookings = [];
while ($row = mysqli_fetch_assoc($result)) {
    $ref = $row['booking_reference'];
    if (!isset($bookings[$ref])) {
        $bookings[$ref] = $row;
        $bookings[$ref]['seats'] = [$row['seat_number']];
        $bookings[$ref]['total'] = $row['total_amount'];
    } else {
        $bookings[$ref]['seats'][] = $row['seat_number'];
        $bookings[$ref]['total'] += $row['total_amount'];
    }
}
mysqli_stmt_close($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Bookings - Admin - Ethiopian Bus Reservation</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/bus-reservation-ethiopia/assets/css/style.css">
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo">Ethio<span>Bus</span></div>
                <p style="font-size: 12px; opacity: 0.8; margin-top: 5px;">Admin Panel</p>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php">📊 Dashboard</a></li>
                <li><a href="buses.php">🚌 Manage Buses</a></li>
                <li><a href="routes.php">🛤️ Manage Routes</a></li>
                <li><a href="bookings.php" class="active">🎫 View Bookings</a></li>
                <li><a href="users.php">👥 Manage Users</a></li>
                <li><a href="messages.php">✉️ Contact Messages</a></li>
                <li><a href="logout.php">🚪 Logout</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="admin-content">
            <div class="admin-header">
                <h1>View Bookings</h1>
                <p>Total: <?php echo count($bookings); ?> bookings</p>
            </div>

            <?php
            $msg = getMessage();
            if ($msg):
            ?>
                <div class="alert alert-<?php echo $msg['type']; ?>">
                    <?php echo $msg['message']; ?>
                </div>
            <?php endif; ?>

            <!-- Filters -->
            <div class="card mb-30">
                <form method="GET" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: end;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="search">Search</label>
                        <input type="text" id="search" name="search" placeholder="Reference, Name, or Email" 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="">All Status</option>
                            <option value="confirmed" <?php echo $filter_status === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="cancelled" <?php echo $filter_status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            <option value="completed" <?php echo $filter_status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="date">Travel Date</label>
                        <input type="date" id="date" name="date" value="<?php echo $filter_date; ?>">
                    </div>
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="bookings.php" class="btn btn-secondary">Clear</a>
                </form>
            </div>

            <!-- Bookings Table -->
            <div class="card">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Reference</th>
                                <th>Customer</th>
                                <th>Contact</th>
                                <th>Route</th>
                                <th>Bus</th>
                                <th>Travel Date</th>
                                <th>Seats</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Booked On</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $ref => $booking): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($ref); ?></strong></td>
                                    <td>
                                        <?php echo htmlspecialchars($booking['full_name']); ?><br>
                                        <small style="color: #666;"><?php echo htmlspecialchars($booking['passenger_name']); ?></small>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($booking['email']); ?><br>
                                        <small><?php echo htmlspecialchars($booking['passenger_phone']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($booking['origin']); ?> → <?php echo htmlspecialchars($booking['destination']); ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($booking['bus_name']); ?><br>
                                        <small><?php echo htmlspecialchars($booking['bus_number']); ?></small>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($booking['travel_date'])); ?></td>
                                    <td><?php echo implode(', ', $booking['seats']); ?></td>
                                    <td><strong><?php echo number_format($booking['total'], 2); ?> ETB</strong></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $booking['booking_status']; ?>">
                                            <?php echo ucfirst($booking['booking_status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M j, Y H:i', strtotime($booking['created_at'])); ?></td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="booking_ref" value="<?php echo $ref; ?>">
                                            <select name="status" onchange="this.form.submit()" style="padding: 5px;">
                                                <option value="confirmed" <?php echo $booking['booking_status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                                <option value="cancelled" <?php echo $booking['booking_status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                                <option value="completed" <?php echo $booking['booking_status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                            </select>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($bookings)): ?>
                                <tr>
                                    <td colspan="11" style="text-align: center; padding: 30px;">No bookings found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script src="../assets/js/validation.js"></script>
</body>
</html>
<?php mysqli_close($conn); ?>
