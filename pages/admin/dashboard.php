<?php
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

// Check if user is admin
if (($_SESSION['user_type'] ?? 'donor') !== 'admin') {
    $_SESSION['error'] = "Access denied. Admin privileges required.";
    redirect('../home.php');
}

try {
    $auth = new Auth();
    $conn = $auth->conn;
    
    // Get statistics for dashboard
    $stats = [];
    
    // Total users
    $stmt = $conn->query("SELECT COUNT(*) as total FROM users");
    $stats['total_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total donors
    $stmt = $conn->query("SELECT COUNT(*) as total FROM users WHERE user_type = 'donor'");
    $stats['total_donors'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Active requests
    $stmt = $conn->query("SELECT COUNT(*) as total FROM blood_requests WHERE status = 'open'");
    $stats['active_requests'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total donations
    $stmt = $conn->query("SELECT COUNT(*) as total FROM donations");
    $stats['total_donations'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Recent activities
    $stmt = $conn->query("
        (SELECT 'donation' as type, created_at, CONCAT('New donation recorded') as description FROM donations ORDER BY created_at DESC LIMIT 5)
        UNION
        (SELECT 'request' as type, created_at, CONCAT('Blood request for ', blood_type) as description FROM blood_requests ORDER BY created_at DESC LIMIT 5)
        UNION
        (SELECT 'user' as type, created_at, CONCAT('New user: ', full_name) as description FROM users ORDER BY created_at DESC LIMIT 5)
        ORDER BY created_at DESC LIMIT 10
    ");
    $recent_activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Low stock alerts
    $low_stock_threshold = 5;
    $stmt = $conn->prepare("SELECT blood_type, units_available FROM blood_inventory WHERE units_available < ?");
    $stmt->execute([$low_stock_threshold]);
    $low_stock = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error = "Error loading admin data: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Blood Donation System</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .admin-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .admin-stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .admin-stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #c33;
            margin: 10px 0;
        }
        .admin-nav {
            background: #2c3e50;
            padding: 15px;
            border-radius: 10px;
            margin: 20px 0;
        }
        .admin-nav a {
            color: white;
            text-decoration: none;
            padding: 10px 15px;
            margin: 0 5px;
            border-radius: 5px;
            transition: background 0.3s;
        }
        .admin-nav a:hover {
            background: #34495e;
        }
        .activity-item {
            background: white;
            padding: 15px;
            margin: 10px 0;
            border-radius: 8px;
            border-left: 4px solid #c33;
        }
        .alert-badge {
            background: #e74c3c;
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 12px;
            margin-left: 10px;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <header class="header">
            <div class="container">
                <h1>Admin Dashboard</h1>
                <div class="user-info">
                    <span style="background: #e74c3c; color: white; padding: 5px 10px; border-radius: 15px;">ADMIN</span>
                    <a href="../home.php" class="logout-btn">← User Dashboard</a>
                    <a href="../logout.php" class="logout-btn">Logout</a>
                </div>
            </div>
        </header>

        <main class="main-content">
            <div class="container">
                <?php if (isset($error)): ?>
                    <div class="alert error"><?php echo $error; ?></div>
                <?php endif; ?>

                <!-- Admin Navigation -->
                <div class="admin-nav">
                    <a href="dashboard.php">📊 Dashboard</a>
                    <a href="users.php">👥 Manage Users</a>
                    <a href="requests.php">🩸 Blood Requests</a>
                    <a href="inventory.php">🏥 Inventory</a>
                    <a href="notifications.php">🔔 Notifications</a>
                    <a href="settings.php">⚙️ Settings</a>
                    <a href="reports.php">📈 Reports</a>
                </div>

                <!-- Quick Stats -->
                <div class="admin-stats">
                    <div class="admin-stat-card">
                        <h3>Total Users</h3>
                        <div class="admin-stat-number"><?php echo $stats['total_users']; ?></div>
                        <span>Registered users</span>
                    </div>
                    <div class="admin-stat-card">
                        <h3>Active Donors</h3>
                        <div class="admin-stat-number"><?php echo $stats['total_donors']; ?></div>
                        <span>Available donors</span>
                    </div>
                    <div class="admin-stat-card">
                        <h3>Active Requests</h3>
                        <div class="admin-stat-number"><?php echo $stats['active_requests']; ?></div>
                        <span>Open requests</span>
                    </div>
                    <div class="admin-stat-card">
                        <h3>Total Donations</h3>
                        <div class="admin-stat-number"><?php echo $stats['total_donations']; ?></div>
                        <span>All time donations</span>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px; margin-top: 30px;">
                    <!-- Recent Activities -->
                    <div>
                        <h2>Recent Activities</h2>
                        <?php foreach ($recent_activities as $activity): ?>
                            <div class="activity-item">
                                <p><?php echo $activity['description']; ?></p>
                                <small style="color: #666;"><?php echo date('M j, Y g:i A', strtotime($activity['created_at'])); ?></small>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Alerts & Notifications -->
                    <div>
                        <h2>System Alerts</h2>
                        
                        <?php if (!empty($low_stock)): ?>
                            <div style="background: #f8d7da; padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                                <h3 style="color: #721c24; margin: 0 0 10px 0;">⚠️ Low Stock Alert</h3>
                                <?php foreach ($low_stock as $stock): ?>
                                    <p style="margin: 5px 0;">
                                        <strong><?php echo $stock['blood_type']; ?>:</strong> 
                                        <?php echo $stock['units_available']; ?> units
                                    </p>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Quick Actions -->
                        <div style="background: #d1ecf1; padding: 15px; border-radius: 8px;">
                            <h3 style="color: #0c5460; margin: 0 0 10px 0;">Quick Actions</h3>
                            <div style="display: grid; gap: 10px;">
                                <a href="users.php?action=add" style="background: #007bff; color: white; padding: 8px 12px; border-radius: 5px; text-decoration: none; text-align: center;">
                                    ➕ Add New User
                                </a>
                                <a href="inventory.php?action=update" style="background: #28a745; color: white; padding: 8px 12px; border-radius: 5px; text-decoration: none; text-align: center;">
                                    📦 Update Inventory
                                </a>
                                <a href="notifications.php?action=broadcast" style="background: #ffc107; color: black; padding: 8px 12px; border-radius: 5px; text-decoration: none; text-align: center;">
                                    📢 Send Broadcast
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>