<?php
/**
 * Admin - Manage Users
 * Ethiopian Bus Reservation System
 */

require_once __DIR__ . '/../includes/config.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    redirect('login.php');
}

// Handle user deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'delete') {
        $user_id = intval($_POST['user_id']);
        
        // Check if user has any bookings
        $check_sql = "SELECT COUNT(*) as count FROM bookings WHERE user_id = ?";
        $check_stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($check_stmt, "i", $user_id);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        $count = mysqli_fetch_assoc($check_result)['count'];
        mysqli_stmt_close($check_stmt);
        
        if ($count > 0) {
            setMessage("Cannot delete user with existing bookings. Deactivate instead.", 'error');
        } else {
            $sql = "DELETE FROM users WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            
            if (mysqli_stmt_execute($stmt)) {
                setMessage("User deleted successfully!", 'success');
            } else {
                setMessage("Failed to delete user", 'error');
            }
            mysqli_stmt_close($stmt);
        }
    }
    
    redirect('users.php');
}

// Search parameter
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// Build query
$sql = "SELECT u.*, 
        (SELECT COUNT(*) FROM bookings WHERE user_id = u.id) as booking_count,
        (SELECT SUM(total_amount) FROM bookings WHERE user_id = u.id AND booking_status = 'confirmed') as total_spent
        FROM users u WHERE 1=1";

$params = [];
$types = "";

if ($search) {
    $sql .= " AND (u.full_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

$sql .= " ORDER BY u.created_at DESC";

$stmt = mysqli_prepare($conn, $sql);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin - Ethiopian Bus Reservation</title>
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
                <li><a href="bookings.php">🎫 View Bookings</a></li>
                <li><a href="users.php" class="active">👥 Manage Users</a></li>
                <li><a href="messages.php">✉️ Contact Messages</a></li>
                <li><a href="logout.php">🚪 Logout</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="admin-content">
            <div class="admin-header">
                <h1>Manage Users</h1>
            </div>

            <?php
            $msg = getMessage();
            if ($msg):
            ?>
                <div class="alert alert-<?php echo $msg['type']; ?>">
                    <?php echo $msg['message']; ?>
                </div>
            <?php endif; ?>

            <!-- Search -->
            <div class="card mb-30">
                <form method="GET" style="display: flex; gap: 15px; align-items: end;">
                    <div class="form-group" style="margin-bottom: 0; flex: 1;">
                        <label for="search">Search Users</label>
                        <input type="text" id="search" name="search" placeholder="Search by name, email, or phone" 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <button type="submit" class="btn btn-primary">Search</button>
                    <a href="users.php" class="btn btn-secondary">Clear</a>
                </form>
            </div>

            <!-- Users Table -->
            <div class="card">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Bookings</th>
                                <th>Total Spent</th>
                                <th>Registered</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($user = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($user['full_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo htmlspecialchars($user['phone']); ?></td>
                                    <td>
                                        <span class="status-badge status-confirmed">
                                            <?php echo $user['booking_count']; ?> bookings
                                        </span>
                                    </td>
                                    <td><?php echo number_format($user['total_spent'] ?? 0, 2); ?> ETB</td>
                                    <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <a href="bookings.php?search=<?php echo urlencode($user['email']); ?>" 
                                           class="btn btn-secondary" style="padding: 5px 10px; font-size: 12px;">
                                            Bookings
                                        </a>
                                        <?php if ($user['booking_count'] == 0): ?>
                                            <form method="POST" style="display: inline;" 
                                                  onsubmit="return confirm('Are you sure you want to delete this user?')">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" class="btn btn-danger" style="padding: 5px 10px; font-size: 12px;">
                                                    Delete
                                                </button>
                                            </form>
                                        <?php endif; ?>
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
<?php 
mysqli_stmt_close($stmt);
mysqli_close($conn); 
?>
