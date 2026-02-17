<?php
/**
 * Admin - Manage Routes
 * Ethiopian Bus Reservation System
 */

require_once __DIR__ . '/../includes/config.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    redirect('login.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    if ($action === 'add' || $action === 'edit') {
        $origin = sanitize($_POST['origin']);
        $destination = sanitize($_POST['destination']);
        $distance_km = intval($_POST['distance_km']);
        $duration_hours = floatval($_POST['duration_hours']);
        $status = sanitize($_POST['status']);
        
        if ($action === 'add') {
            $sql = "INSERT INTO routes (origin, destination, distance_km, duration_hours, status) 
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ssids", $origin, $destination, $distance_km, $duration_hours, $status);
            
            if (mysqli_stmt_execute($stmt)) {
                setMessage("Route added successfully!", 'success');
            } else {
                setMessage("Failed to add route", 'error');
            }
            mysqli_stmt_close($stmt);
        } else {
            $route_id = intval($_POST['route_id']);
            $sql = "UPDATE routes SET origin=?, destination=?, distance_km=?, duration_hours=?, status=? 
                    WHERE id=?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ssidsi", $origin, $destination, $distance_km, $duration_hours, $status, $route_id);
            
            if (mysqli_stmt_execute($stmt)) {
                setMessage("Route updated successfully!", 'success');
            } else {
                setMessage("Failed to update route", 'error');
            }
            mysqli_stmt_close($stmt);
        }
    } elseif ($action === 'delete') {
        $route_id = intval($_POST['route_id']);
        $sql = "DELETE FROM routes WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $route_id);
        
        if (mysqli_stmt_execute($stmt)) {
            setMessage("Route deleted successfully!", 'success');
        } else {
            setMessage("Failed to delete route. It may have buses assigned.", 'error');
        }
        mysqli_stmt_close($stmt);
    }
    
    redirect('routes.php');
}

// Get all routes
$routes_sql = "SELECT r.*, 
               (SELECT COUNT(*) FROM buses WHERE route_id = r.id AND status = 'active') as bus_count
               FROM routes r ORDER BY r.origin ASC";
$routes_result = mysqli_query($conn, $routes_sql);

// Ethiopian cities list
$cities = getEthiopianCities();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Routes - Admin - Ethiopian Bus Reservation</title>
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
                <li><a href="routes.php" class="active">🛤️ Manage Routes</a></li>
                <li><a href="bookings.php">🎫 View Bookings</a></li>
                <li><a href="users.php">👥 Manage Users</a></li>
                <li><a href="messages.php">✉️ Contact Messages</a></li>
                <li><a href="logout.php">🚪 Logout</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="admin-content">
            <div class="admin-header">
                <h1>Manage Routes</h1>
                <button class="btn btn-primary" onclick="openModal('addRouteModal')">+ Add New Route</button>
            </div>

            <?php
            $msg = getMessage();
            if ($msg):
            ?>
                <div class="alert alert-<?php echo $msg['type']; ?>">
                    <?php echo $msg['message']; ?>
                </div>
            <?php endif; ?>

            <!-- Routes Table -->
            <div class="card">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Origin</th>
                                <th>Destination</th>
                                <th>Distance (km)</th>
                                <th>Duration (hrs)</th>
                                <th>Active Buses</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($route = mysqli_fetch_assoc($routes_result)): ?>
                                <tr>
                                    <td><?php echo $route['id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($route['origin']); ?></strong></td>
                                    <td><strong><?php echo htmlspecialchars($route['destination']); ?></strong></td>
                                    <td><?php echo number_format($route['distance_km']); ?> km</td>
                                    <td><?php echo $route['duration_hours']; ?> hrs</td>
                                    <td><?php echo $route['bus_count']; ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $route['status'] === 'active' ? 'confirmed' : 'cancelled'; ?>">
                                            <?php echo ucfirst($route['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-secondary" style="padding: 5px 10px; font-size: 12px;" 
                                                onclick="editRoute(<?php echo htmlspecialchars(json_encode($route)); ?>)">
                                            Edit
                                        </button>
                                        <form method="POST" style="display: inline;" 
                                              onsubmit="return confirm('Are you sure? This will affect all buses on this route.')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="route_id" value="<?php echo $route['id']; ?>">
                                            <button type="submit" class="btn btn-danger" style="padding: 5px 10px; font-size: 12px;">
                                                Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Add Route Modal -->
    <div class="modal" id="addRouteModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Route</h3>
                <button class="modal-close" onclick="closeModal('addRouteModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                
                <div class="form-group">
                    <label for="origin">Origin City</label>
                    <select id="origin" name="origin" required>
                        <option value="">Select City</option>
                        <?php foreach ($cities as $city): ?>
                            <option value="<?php echo $city; ?>"><?php echo $city; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="destination">Destination City</label>
                    <select id="destination" name="destination" required>
                        <option value="">Select City</option>
                        <?php foreach ($cities as $city): ?>
                            <option value="<?php echo $city; ?>"><?php echo $city; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="distance_km">Distance (km)</label>
                    <input type="number" id="distance_km" name="distance_km" min="1" required>
                </div>
                
                <div class="form-group">
                    <label for="duration_hours">Duration (hours)</label>
                    <input type="number" id="duration_hours" name="duration_hours" step="0.5" min="0.5" required>
                </div>
                
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">Add Route</button>
            </form>
        </div>
    </div>

    <!-- Edit Route Modal -->
    <div class="modal" id="editRouteModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Route</h3>
                <button class="modal-close" onclick="closeModal('editRouteModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="route_id" id="edit_route_id">
                
                <div class="form-group">
                    <label for="edit_origin">Origin City</label>
                    <select id="edit_origin" name="origin" required>
                        <?php foreach ($cities as $city): ?>
                            <option value="<?php echo $city; ?>"><?php echo $city; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="edit_destination">Destination City</label>
                    <select id="edit_destination" name="destination" required>
                        <?php foreach ($cities as $city): ?>
                            <option value="<?php echo $city; ?>"><?php echo $city; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="edit_distance_km">Distance (km)</label>
                    <input type="number" id="edit_distance_km" name="distance_km" min="1" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_duration_hours">Duration (hours)</label>
                    <input type="number" id="edit_duration_hours" name="duration_hours" step="0.5" min="0.5" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_status">Status</label>
                    <select id="edit_status" name="status" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">Update Route</button>
            </form>
        </div>
    </div>

    <script src="../assets/js/validation.js"></script>
    <script>
        function editRoute(route) {
            document.getElementById('edit_route_id').value = route.id;
            document.getElementById('edit_origin').value = route.origin;
            document.getElementById('edit_destination').value = route.destination;
            document.getElementById('edit_distance_km').value = route.distance_km;
            document.getElementById('edit_duration_hours').value = route.duration_hours;
            document.getElementById('edit_status').value = route.status;
            openModal('editRouteModal');
        }
    </script>
</body>
</html>
<?php mysqli_close($conn); ?>
