<?php
/**
 * Admin - Manage Buses
 * Ethiopian Bus Reservation System
 */

require_once __DIR__ . '/../includes/config.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    redirect('login.php');
}

// Handle form submission for adding/editing bus
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    if ($action === 'add' || $action === 'edit') {
        $bus_name = sanitize($_POST['bus_name']);
        $bus_number = sanitize($_POST['bus_number']);
        $bus_type = sanitize($_POST['bus_type']);
        $total_seats = intval($_POST['total_seats']);
        $route_id = intval($_POST['route_id']);
        $departure_time = sanitize($_POST['departure_time']);
        $arrival_time = sanitize($_POST['arrival_time']);
        $price_birr = floatval($_POST['price_birr']);
        $status = sanitize($_POST['status']);
        
        if ($action === 'add') {
            $sql = "INSERT INTO buses (bus_name, bus_number, bus_type, total_seats, route_id, 
                    departure_time, arrival_time, price_birr, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "sssisdsds", $bus_name, $bus_number, $bus_type, 
                                  $total_seats, $route_id, $departure_time, $arrival_time, $price_birr, $status);
            
            if (mysqli_stmt_execute($stmt)) {
                $bus_id = mysqli_insert_id($conn);
                
                // Generate seats for the new bus
                for ($i = 1; $i <= $total_seats; $i++) {
                    $seat_type = ($i % 4 == 1 || $i % 4 == 0) ? 'window' : 'aisle';
                    $seat_sql = "INSERT INTO seats (bus_id, seat_number, seat_type) VALUES (?, ?, ?)";
                    $seat_stmt = mysqli_prepare($conn, $seat_sql);
                    mysqli_stmt_bind_param($seat_stmt, "iss", $bus_id, $i, $seat_type);
                    mysqli_stmt_execute($seat_stmt);
                    mysqli_stmt_close($seat_stmt);
                }
                
                setMessage("Bus added successfully!", 'success');
            } else {
                setMessage("Failed to add bus", 'error');
            }
            mysqli_stmt_close($stmt);
        } else {
            $bus_id = intval($_POST['bus_id']);
            $sql = "UPDATE buses SET bus_name=?, bus_number=?, bus_type=?, total_seats=?, 
                    route_id=?, departure_time=?, arrival_time=?, price_birr=?, status=? 
                    WHERE id=?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "sssisdsdsi", $bus_name, $bus_number, $bus_type, 
                                  $total_seats, $route_id, $departure_time, $arrival_time, 
                                  $price_birr, $status, $bus_id);
            
            if (mysqli_stmt_execute($stmt)) {
                setMessage("Bus updated successfully!", 'success');
            } else {
                setMessage("Failed to update bus", 'error');
            }
            mysqli_stmt_close($stmt);
        }
    } elseif ($action === 'delete') {
        $bus_id = intval($_POST['bus_id']);
        $sql = "DELETE FROM buses WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $bus_id);
        
        if (mysqli_stmt_execute($stmt)) {
            setMessage("Bus deleted successfully!", 'success');
        } else {
            setMessage("Failed to delete bus. It may have existing bookings.", 'error');
        }
        mysqli_stmt_close($stmt);
    }
    
    redirect('buses.php');
}

// Get all buses
$buses_sql = "SELECT b.*, r.origin, r.destination FROM buses b 
              INNER JOIN routes r ON b.route_id = r.id 
              ORDER BY b.bus_name ASC";
$buses_result = mysqli_query($conn, $buses_sql);

// Get all routes for dropdown
$routes_sql = "SELECT * FROM routes WHERE status = 'active' ORDER BY origin ASC";
$routes_result = mysqli_query($conn, $routes_sql);
$routes = [];
while ($route = mysqli_fetch_assoc($routes_result)) {
    $routes[] = $route;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Buses - Admin - Ethiopian Bus Reservation</title>
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
                <li><a href="buses.php" class="active">🚌 Manage Buses</a></li>
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
                <h1>Manage Buses</h1>
                <button class="btn btn-primary" onclick="openModal('addBusModal')">+ Add New Bus</button>
            </div>

            <?php
            $msg = getMessage();
            if ($msg):
            ?>
                <div class="alert alert-<?php echo $msg['type']; ?>">
                    <?php echo $msg['message']; ?>
                </div>
            <?php endif; ?>

            <!-- Buses Table -->
            <div class="card">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Bus Name</th>
                                <th>Bus Number</th>
                                <th>Type</th>
                                <th>Route</th>
                                <th>Departure</th>
                                <th>Arrival</th>
                                <th>Price (ETB)</th>
                                <th>Seats</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($bus = mysqli_fetch_assoc($buses_result)): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($bus['bus_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($bus['bus_number']); ?></td>
                                    <td><?php echo htmlspecialchars($bus['bus_type']); ?></td>
                                    <td><?php echo htmlspecialchars($bus['origin']); ?> → <?php echo htmlspecialchars($bus['destination']); ?></td>
                                    <td><?php echo date('h:i A', strtotime($bus['departure_time'])); ?></td>
                                    <td><?php echo date('h:i A', strtotime($bus['arrival_time'])); ?></td>
                                    <td><?php echo number_format($bus['price_birr'], 2); ?></td>
                                    <td><?php echo $bus['total_seats']; ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $bus['status'] === 'active' ? 'confirmed' : 'cancelled'; ?>">
                                            <?php echo ucfirst($bus['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-secondary" style="padding: 5px 10px; font-size: 12px;" 
                                                onclick="editBus(<?php echo htmlspecialchars(json_encode($bus)); ?>)">
                                            Edit
                                        </button>
                                        <form method="POST" style="display: inline;" 
                                              onsubmit="return confirm('Are you sure you want to delete this bus?')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="bus_id" value="<?php echo $bus['id']; ?>">
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

    <!-- Add Bus Modal -->
    <div class="modal" id="addBusModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Bus</h3>
                <button class="modal-close" onclick="closeModal('addBusModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                
                <div class="form-group">
                    <label for="bus_name">Bus Name</label>
                    <input type="text" id="bus_name" name="bus_name" required>
                </div>
                
                <div class="form-group">
                    <label for="bus_number">Bus Number</label>
                    <input type="text" id="bus_number" name="bus_number" placeholder="e.g., ETH-011" required>
                </div>
                
                <div class="form-group">
                    <label for="bus_type">Bus Type</label>
                    <select id="bus_type" name="bus_type" required>
                        <option value="Standard">Standard</option>
                        <option value="Semi-Luxury">Semi-Luxury</option>
                        <option value="Luxury">Luxury</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="route_id">Route</label>
                    <select id="route_id" name="route_id" required>
                        <?php foreach ($routes as $route): ?>
                            <option value="<?php echo $route['id']; ?>">
                                <?php echo htmlspecialchars($route['origin']); ?> → <?php echo htmlspecialchars($route['destination']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="total_seats">Total Seats</label>
                    <input type="number" id="total_seats" name="total_seats" value="45" min="1" max="60" required>
                </div>
                
                <div class="form-group">
                    <label for="departure_time">Departure Time</label>
                    <input type="time" id="departure_time" name="departure_time" required>
                </div>
                
                <div class="form-group">
                    <label for="arrival_time">Arrival Time</label>
                    <input type="time" id="arrival_time" name="arrival_time" required>
                </div>
                
                <div class="form-group">
                    <label for="price_birr">Price (ETB)</label>
                    <input type="number" id="price_birr" name="price_birr" step="0.01" min="0" required>
                </div>
                
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">Add Bus</button>
            </form>
        </div>
    </div>

    <!-- Edit Bus Modal -->
    <div class="modal" id="editBusModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Bus</h3>
                <button class="modal-close" onclick="closeModal('editBusModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="bus_id" id="edit_bus_id">
                
                <div class="form-group">
                    <label for="edit_bus_name">Bus Name</label>
                    <input type="text" id="edit_bus_name" name="bus_name" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_bus_number">Bus Number</label>
                    <input type="text" id="edit_bus_number" name="bus_number" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_bus_type">Bus Type</label>
                    <select id="edit_bus_type" name="bus_type" required>
                        <option value="Standard">Standard</option>
                        <option value="Semi-Luxury">Semi-Luxury</option>
                        <option value="Luxury">Luxury</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="edit_route_id">Route</label>
                    <select id="edit_route_id" name="route_id" required>
                        <?php foreach ($routes as $route): ?>
                            <option value="<?php echo $route['id']; ?>">
                                <?php echo htmlspecialchars($route['origin']); ?> → <?php echo htmlspecialchars($route['destination']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="edit_total_seats">Total Seats</label>
                    <input type="number" id="edit_total_seats" name="total_seats" min="1" max="60" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_departure_time">Departure Time</label>
                    <input type="time" id="edit_departure_time" name="departure_time" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_arrival_time">Arrival Time</label>
                    <input type="time" id="edit_arrival_time" name="arrival_time" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_price_birr">Price (ETB)</label>
                    <input type="number" id="edit_price_birr" name="price_birr" step="0.01" min="0" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_status">Status</label>
                    <select id="edit_status" name="status" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">Update Bus</button>
            </form>
        </div>
    </div>

    <script src="../assets/js/validation.js"></script>
    <script>
        function editBus(bus) {
            document.getElementById('edit_bus_id').value = bus.id;
            document.getElementById('edit_bus_name').value = bus.bus_name;
            document.getElementById('edit_bus_number').value = bus.bus_number;
            document.getElementById('edit_bus_type').value = bus.bus_type;
            document.getElementById('edit_route_id').value = bus.route_id;
            document.getElementById('edit_total_seats').value = bus.total_seats;
            document.getElementById('edit_departure_time').value = bus.departure_time;
            document.getElementById('edit_arrival_time').value = bus.arrival_time;
            document.getElementById('edit_price_birr').value = bus.price_birr;
            document.getElementById('edit_status').value = bus.status;
            openModal('editBusModal');
        }
    </script>
</body>
</html>
<?php mysqli_close($conn); ?>
