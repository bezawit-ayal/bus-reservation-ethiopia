<?php
/**
 * Admin - Contact Messages
 * Ethiopian Bus Reservation System
 * View messages sent by users via the contact form.
 */

require_once __DIR__ . '/../includes/config.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    redirect('login.php');
}

// Check if contact_messages table exists
$table_exists = false;
$check = mysqli_query($conn, "SHOW TABLES LIKE 'contact_messages'");
if ($check && mysqli_num_rows($check) > 0) {
    $table_exists = true;
}

// Handle mark as read (POST or via view)
if ($table_exists && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'mark_read' && isset($_POST['id'])) {
        $id = intval($_POST['id']);
        mysqli_query($conn, "UPDATE contact_messages SET is_read = 1 WHERE id = $id");
        setMessage("Message marked as read.", 'success');
        redirect('messages.php');
    }
}

$view_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$view_msg = null;

$subject_labels = [
    'booking' => 'Booking Inquiry',
    'refund' => 'Refund Request',
    'complaint' => 'Complaint',
    'feedback' => 'Feedback',
    'partnership' => 'Partnership',
    'other' => 'Other'
];

if ($table_exists) {
    // Fetch message to view and mark as read
    if ($view_id > 0) {
        $r = mysqli_query($conn, "SELECT * FROM contact_messages WHERE id = $view_id");
        if ($r && $row = mysqli_fetch_assoc($r)) {
            $view_msg = $row;
            mysqli_query($conn, "UPDATE contact_messages SET is_read = 1 WHERE id = $view_id");
        }
    }

    // Build list query with optional filters
    $filter_read = isset($_GET['read']) ? sanitize($_GET['read']) : '';
    $filter_subject = isset($_GET['subject']) ? sanitize($_GET['subject']) : '';
    $search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

    $sql = "SELECT * FROM contact_messages WHERE 1=1";
    $params = [];
    $types = "";

    if ($filter_read === 'unread') {
        $sql .= " AND is_read = 0";
    } elseif ($filter_read === 'read') {
        $sql .= " AND is_read = 1";
    }

    if ($filter_subject) {
        $sql .= " AND subject = ?";
        $params[] = $filter_subject;
        $types .= "s";
    }

    if ($search) {
        $sql .= " AND (name LIKE ? OR email LIKE ? OR message LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $types .= "sss";
    }

    $sql .= " ORDER BY is_read ASC, created_at DESC";

    $stmt = mysqli_prepare($conn, $sql);
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    $messages_result = mysqli_stmt_get_result($stmt);
    $messages = mysqli_fetch_all($messages_result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
} else {
    $messages = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Messages - Admin - Ethiopian Bus Reservation</title>
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
                <li><a href="users.php">👥 Manage Users</a></li>
                <li><a href="messages.php" class="active">✉️ Contact Messages</a></li>
                <li><a href="logout.php">🚪 Logout</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="admin-content">
            <div class="admin-header">
                <h1>Contact Messages</h1>
                <p>Messages sent by users from the contact form. Total: <?php echo $table_exists ? count($messages) : 0; ?></p>
            </div>

            <?php
            $msg = getMessage();
            if ($msg):
            ?>
                <div class="alert alert-<?php echo $msg['type']; ?>">
                    <?php echo $msg['message']; ?>
                </div>
            <?php endif; ?>

            <?php if (!$table_exists): ?>
                <div class="alert alert-error">
                    The <strong>contact_messages</strong> table does not exist. Run this SQL to create it:
                    <br><code>database/contact_messages.sql</code>
                </div>
            <?php else: ?>

            <!-- View single message -->
            <?php if ($view_msg): ?>
                <div class="card mb-30">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h3 style="color: var(--accent-color);">Message from <?php echo htmlspecialchars($view_msg['name']); ?></h3>
                        <a href="messages.php" class="btn btn-secondary">← Back to list</a>
                    </div>
                    <table style="width: 100%;">
                        <tr><td style="width: 140px; padding: 8px 0;"><strong>Name</strong></td><td><?php echo htmlspecialchars($view_msg['name']); ?></td></tr>
                        <tr><td style="padding: 8px 0;"><strong>Email</strong></td><td><a href="mailto:<?php echo htmlspecialchars($view_msg['email']); ?>"><?php echo htmlspecialchars($view_msg['email']); ?></a></td></tr>
                        <tr><td style="padding: 8px 0;"><strong>Phone</strong></td><td><?php echo htmlspecialchars($view_msg['phone'] ?: '—'); ?></td></tr>
                        <tr><td style="padding: 8px 0;"><strong>Subject</strong></td><td><?php echo htmlspecialchars($subject_labels[$view_msg['subject']] ?? $view_msg['subject']); ?></td></tr>
                        <tr><td style="padding: 8px 0;"><strong>Date</strong></td><td><?php echo date('M j, Y \a\t H:i', strtotime($view_msg['created_at'])); ?></td></tr>
                    </table>
                    <div style="margin-top: 20px; padding: 15px; background: #f5f5f5; border-radius: 8px;">
                        <strong>Message:</strong><br>
                        <?php echo nl2br(htmlspecialchars($view_msg['message'])); ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Filters -->
            <div class="card mb-30">
                <form method="GET" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: end;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="search">Search</label>
                        <input type="text" id="search" name="search" placeholder="Name, email, or message"
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="read">Status</label>
                        <select id="read" name="read">
                            <option value="">All</option>
                            <option value="unread" <?php echo ($filter_read ?? '') === 'unread' ? 'selected' : ''; ?>>Unread</option>
                            <option value="read" <?php echo ($filter_read ?? '') === 'read' ? 'selected' : ''; ?>>Read</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="subject">Subject</label>
                        <select id="subject" name="subject">
                            <option value="">All subjects</option>
                            <?php foreach ($subject_labels as $val => $label): ?>
                                <option value="<?php echo $val; ?>" <?php echo $filter_subject === $val ? 'selected' : ''; ?>><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="messages.php" class="btn btn-secondary">Clear</a>
                </form>
            </div>

            <!-- Messages Table -->
            <div class="card">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Subject</th>
                                <th>Date</th>
                                <th>Read</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($messages as $m): ?>
                                <tr style="<?php echo ($m['is_read'] ?? 0) ? '' : 'background: #f9f9f9;'; ?>">
                                    <td><?php echo htmlspecialchars($m['name']); ?></td>
                                    <td><a href="mailto:<?php echo htmlspecialchars($m['email']); ?>"><?php echo htmlspecialchars($m['email']); ?></a></td>
                                    <td><?php echo htmlspecialchars($subject_labels[$m['subject']] ?? $m['subject']); ?></td>
                                    <td><?php echo date('M j, Y H:i', strtotime($m['created_at'])); ?></td>
                                    <td><?php echo ($m['is_read'] ?? 0) ? '✓' : '•'; ?></td>
                                    <td>
                                        <a href="messages.php?id=<?php echo (int)$m['id']; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $filter_read ? '&read=' . urlencode($filter_read) : ''; ?><?php echo $filter_subject ? '&subject=' . urlencode($filter_subject) : ''; ?>" class="btn btn-primary" style="padding: 5px 10px; font-size: 12px;">View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($messages)): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 30px;">No contact messages found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php endif; ?>
        </main>
    </div>

    <script src="../assets/js/validation.js"></script>
</body>
</html>
<?php mysqli_close($conn); ?>
