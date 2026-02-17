<?php
/**
 * Admin Dashboard
 * Ethiopian Bus Reservation System
 */

require_once __DIR__ . '/../includes/config.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    redirect('login.php');
}

// Get statistics
$stats = [];

// Total users
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM users");
$stats['users'] = mysqli_fetch_assoc($result)['count'];

// Total buses
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM buses WHERE status = 'active'");
$stats['buses'] = mysqli_fetch_assoc($result)['count'];

// Total routes
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM routes WHERE status = 'active'");
$stats['routes'] = mysqli_fetch_assoc($result)['count'];

// Total bookings
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM bookings WHERE booking_status = 'confirmed'");
$stats['bookings'] = mysqli_fetch_assoc($result)['count'];

// Total revenue
$result = mysqli_query($conn, "SELECT SUM(total_amount) as total FROM bookings WHERE booking_status = 'confirmed'");
$stats['revenue'] = mysqli_fetch_assoc($result)['total'] ?? 0;

// Today's bookings
$today = date('Y-m-d');
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM bookings WHERE DATE(created_at) = '$today'");
$stats['today_bookings'] = mysqli_fetch_assoc($result)['count'];

// Recent bookings
$recent_sql = "SELECT b.*, u.full_name, u.email, bs.bus_name, bs.bus_number, 
               r.origin, r.destination, s.seat_number
               FROM bookings b
               INNER JOIN users u ON b.user_id = u.id
               INNER JOIN buses bs ON b.bus_id = bs.id
               INNER JOIN routes r ON bs.route_id = r.id
               INNER JOIN seats s ON b.seat_id = s.id
               ORDER BY b.created_at DESC LIMIT 10";
$recent_result = mysqli_query($conn, $recent_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Ethiopian Bus Reservation</title>
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
                <li><a href="dashboard.php" class="active">📊 Dashboard</a></li>
                <li><a href="buses.php">🚌 Manage Buses</a></li>
                <li><a href="routes.php">🛤️ Manage Routes</a></li>
                <li><a href="bookings.php">🎫 View Bookings</a></li>
                <li><a href="users.php">👥 Manage Users</a></li>
                <li><a href="messages.php">✉️ Contact Messages</a></li>
                <li><a href="logout.php">🚪 Logout</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="admin-content">
            <div class="admin-header">
                <h1>Dashboard</h1>
                <p>Welcome, <?php echo htmlspecialchars($_SESSION['admin_username']); ?>!</p>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon blue">👥</div>
                    <div class="stat-info">
                        <h3><?php echo number_format($stats['users']); ?></h3>
                        <p>Registered Users</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon yellow">🚌</div>
                    <div class="stat-info">
                        <h3><?php echo number_format($stats['buses']); ?></h3>
                        <p>Active Buses</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green">🛤️</div>
                    <div class="stat-info">
                        <h3><?php echo number_format($stats['routes']); ?></h3>
                        <p>Active Routes</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon red">🎫</div>
                    <div class="stat-info">
                        <h3><?php echo number_format($stats['bookings']); ?></h3>
                        <p>Total Bookings</p>
                    </div>
                </div>
            </div>

            <!-- Revenue & Today's Stats -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-bottom: 30px;">
                <div class="card">
                    <h3 style="color: var(--accent-color); margin-bottom: 15px;">Total Revenue</h3>
                    <p style="font-size: 36px; font-weight: 700; color: var(--success-color);">
                        <?php echo number_format($stats['revenue'], 2); ?> ETB
                    </p>
                </div>
                <div class="card">
                    <h3 style="color: var(--accent-color); margin-bottom: 15px;">Today's Bookings</h3>
                    <p style="font-size: 36px; font-weight: 700; color: var(--secondary-color);">
                        <?php echo number_format($stats['today_bookings']); ?>
                    </p>
                </div>
            </div>

            <!-- Recent Bookings -->
            <div class="card">
                <div class="flex-between mb-20">
                    <h3 style="color: var(--accent-color);">Recent Bookings</h3>
                    <a href="bookings.php" class="btn btn-primary" style="padding: 8px 15px; font-size: 14px;">View All</a>
                </div>
                
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Reference</th>
                                <th>Customer</th>
                                <th>Route</th>
                                <th>Travel Date</th>
                                <th>Seat</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($booking = mysqli_fetch_assoc($recent_result)): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($booking['booking_reference']); ?></strong></td>
                                    <td>
                                        <?php echo htmlspecialchars($booking['full_name']); ?><br>
                                        <small style="color: #666;"><?php echo htmlspecialchars($booking['email']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($booking['origin']); ?> → <?php echo htmlspecialchars($booking['destination']); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($booking['travel_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($booking['seat_number']); ?></td>
                                    <td><?php echo number_format($booking['total_amount'], 2); ?> ETB</td>
                                    <td>
                                        <span class="status-badge status-<?php echo $booking['booking_status']; ?>">
                                            <?php echo ucfirst($booking['booking_status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
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
