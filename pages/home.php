<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

try {
    $auth = new Auth();
    $conn = $auth->conn;
    
    $user_id = $_SESSION['user_id'];
    
    // Get user's donation count
    $donation_stmt = $conn->prepare("SELECT COUNT(*) as donation_count FROM donations WHERE user_id = :user_id");
    $donation_stmt->bindParam(":user_id", $user_id);
    $donation_stmt->execute();
    $donation_data = $donation_stmt->fetch(PDO::FETCH_ASSOC);
    $user_donation_count = $donation_data['donation_count'];
    
    // Get last donation date
    $last_donation_stmt = $conn->prepare("SELECT donation_date FROM donations WHERE user_id = :user_id ORDER BY donation_date DESC LIMIT 1");
    $last_donation_stmt->bindParam(":user_id", $user_id);
    $last_donation_stmt->execute();
    $last_donation = $last_donation_stmt->fetch(PDO::FETCH_ASSOC);
    $last_donation_days = '-';
    if ($last_donation) {
        $last_date = new DateTime($last_donation['donation_date']);
        $today = new DateTime();
        $interval = $today->diff($last_date);
        $last_donation_days = $interval->format('%a') . ' days ago';
    }
    
    // Get active blood requests count
    $requests_stmt = $conn->prepare("SELECT COUNT(*) as request_count FROM blood_requests WHERE status = 'open'");
    $requests_stmt->execute();
    $requests_data = $requests_stmt->fetch(PDO::FETCH_ASSOC);
    $active_requests = $requests_data['request_count'];
    
    // Get available donors count
    $donors_stmt = $conn->prepare("SELECT COUNT(*) as donor_count FROM users WHERE is_available = 1 AND user_type = 'donor'");
    $donors_stmt->execute();
    $donors_data = $donors_stmt->fetch(PDO::FETCH_ASSOC);
    $available_donors = $donors_data['donor_count'];
    
} catch (Exception $e) {
    $error = "Error loading dashboard data: " . $e->getMessage();
}

// Check if user is admin
$is_admin = ($_SESSION['user_type'] ?? 'donor') === 'admin';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blood Donation Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        :root {
            --primary: #c62828;
            --primary-dark: #8e0000;
            --primary-light: #ff5f52;
            --secondary: #f5f5f5;
            --accent: #2196f3;
            --text: #333333;
            --text-light: #757575;
            --white: #ffffff;
            --success: #4caf50;
            --warning: #ff9800;
            --danger: #f44336;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 260px;
            background: linear-gradient(180deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: var(--white);
            padding: 0;
            box-shadow: 4px 0 15px rgba(0, 0, 0, 0.1);
            z-index: 100;
        }

        .logo-container {
            padding: 25px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo {
            font-size: 28px;
            color: var(--white);
        }

        .logo-text {
            font-size: 20px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .user-info {
            padding: 25px 20px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .user-name {
            font-size: 18px;
            font-weight: 600;
        }

        .user-details {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            opacity: 0.9;
        }

        .blood-type {
            background: rgba(255, 255, 255, 0.2);
            padding: 4px 10px;
            border-radius: 20px;
            font-weight: 600;
        }

        .nav-menu {
            padding: 20px 0;
        }

        .nav-item {
            padding: 14px 25px;
            display: flex;
            align-items: center;
            gap: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
            text-decoration: none;
            color: inherit;
        }

        .nav-item:hover, .nav-item.active {
            background: rgba(255, 255, 255, 0.1);
            border-left: 4px solid var(--white);
        }

        .nav-icon {
            font-size: 18px;
            width: 24px;
            text-align: center;
        }

        .nav-text {
            font-size: 16px;
            font-weight: 500;
        }

        .logout-container {
            margin-top: auto;
            padding: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .logout-btn {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 12px 15px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
        }

        .logout-btn:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        /* Main Content Styles */
        .main-content {
            flex: 1;
            padding: 30px;
            display: flex;
            flex-direction: column;
            gap: 25px;
            overflow-y: auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .page-title {
            font-size: 28px;
            font-weight: 700;
            color: var(--primary);
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            background: #ffeaea;
            color: #c00;
            border-left: 4px solid #c00;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 10px;
        }

        .stat-card {
            background: var(--white);
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            display: flex;
            flex-direction: column;
            gap: 15px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border-left: 4px solid var(--primary);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .stat-title {
            font-size: 16px;
            color: var(--text-light);
            font-weight: 600;
        }

        .stat-icon {
            font-size: 24px;
            color: var(--primary);
        }

        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: var(--primary);
        }

        .stat-description {
            font-size: 14px;
            color: var(--text-light);
        }

        .actions-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 10px;
        }

        .action-card {
            background: var(--white);
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            display: flex;
            flex-direction: column;
            gap: 15px;
            transition: all 0.3s ease;
            cursor: pointer;
            border-top: 4px solid var(--primary);
            text-decoration: none;
            color: inherit;
        }

        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .action-header {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .action-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: rgba(198, 40, 40, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            color: var(--primary);
        }

        .action-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--text);
        }

        .action-description {
            font-size: 14px;
            color: var(--text-light);
            line-height: 1.5;
        }

        .action-button {
            margin-top: 10px;
            padding: 10px 15px;
            background: var(--primary);
            color: var(--white);
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
        }

        .action-button:hover {
            background: var(--primary-dark);
        }

        .admin-panel {
            background: linear-gradient(135deg, #ffeaa7 0%, #fdcb6e 100%);
            padding: 25px;
            border-radius: 12px;
            margin: 20px 0;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            border: 1px solid #fdcb6e;
        }

        .admin-panel h2 {
            color: #d35400;
            margin-bottom: 20px;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .content-row {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
        }

        .activity-card, .emergency-card {
            background: var(--white);
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .card-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--text);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .activity-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .activity-item {
            display: flex;
            gap: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f5f5f5;
        }

        .activity-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(33, 150, 243, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: var(--accent);
            flex-shrink: 0;
        }

        .activity-content {
            flex: 1;
        }

        .activity-text {
            font-size: 15px;
            margin-bottom: 5px;
            line-height: 1.4;
        }

        .activity-time {
            font-size: 13px;
            color: var(--text-light);
        }

        .emergency-alert {
            background: linear-gradient(135deg, #ff5252 0%, #b33939 100%);
            color: var(--white);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .emergency-icon {
            font-size: 28px;
        }

        .emergency-content h3 {
            font-size: 18px;
            margin-bottom: 5px;
        }

        .emergency-content p {
            font-size: 14px;
            opacity: 0.9;
        }

        .sos-button {
            width: 100%;
            padding: 14px;
            background: var(--danger);
            color: var(--white);
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 15px;
            text-decoration: none;
        }

        .sos-button:hover {
            background: #d32f2f;
        }

        @media (max-width: 1100px) {
            .content-row {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .dashboard-container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
            }
            
            .stats-container, .actions-container {
                grid-template-columns: 1fr;
            }
        }

        /* Animation for stats */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .stat-card, .action-card, .activity-item {
            animation: fadeInUp 0.5s ease forwards;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="logo-container">
                <i class="fas fa-tint logo"></i>
                <div class="logo-text">BloodLife</div>
            </div>
            
            <div class="user-info">
                <div class="user-name">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></div>
                <div class="user-details">
                    <span>Blood Type:</span>
                    <span class="blood-type"><?php echo htmlspecialchars($_SESSION['blood_type']); ?></span>
                    <?php if ($is_admin): ?>
                        <span style="background: #e74c3c; padding: 2px 8px; border-radius: 10px; font-size: 12px;">ADMIN</span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="nav-menu">
                <a href="home.php" class="nav-item active">
                    <i class="fas fa-home nav-icon"></i>
                    <span class="nav-text">Dashboard</span>
                </a>
                <a href="schedule_donation.php" class="nav-item">
                    <i class="fas fa-calendar-plus nav-icon"></i>
                    <span class="nav-text">Schedule Donation</span>
                </a>
                <a href="find_donors.php" class="nav-item">
                    <i class="fas fa-user-friends nav-icon"></i>
                    <span class="nav-text">Find Donors</span>
                </a>
                <a href="blood_requests.php" class="nav-item">
                    <i class="fas fa-hand-holding-heart nav-icon"></i>
                    <span class="nav-text">Blood Requests</span>
                </a>
                <a href="donation_history.php" class="nav-item">
                    <i class="fas fa-history nav-icon"></i>
                    <span class="nav-text">Donation History</span>
                </a>
                <a href="inventory.php" class="nav-item">
                    <i class="fas fa-chart-line nav-icon"></i>
                    <span class="nav-text">Blood Inventory</span>
                </a>
                <a href="my_profile.php" class="nav-item">
                    <i class="fas fa-user nav-icon"></i>
                    <span class="nav-text">My Profile</span>
                </a>
            </div>
            
            <div class="logout-container">
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1 class="page-title">Dashboard</h1>
                <div class="date-display" id="currentDate">Loading date...</div>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <!-- Stats Section -->
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">YOUR DONATIONS</div>
                        <i class="fas fa-tint stat-icon"></i>
                    </div>
                    <div class="stat-value"><?php echo htmlspecialchars($user_donation_count); ?></div>
                    <div class="stat-description">Total contributions</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">LAST DONATION</div>
                        <i class="fas fa-history stat-icon"></i>
                    </div>
                    <div class="stat-value"><?php echo htmlspecialchars($last_donation_days); ?></div>
                    <div class="stat-description">
                        <?php echo $last_donation_days === '-' ? 'No previous donations' : 'Days ago'; ?>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">BLOOD REQUESTS</div>
                        <i class="fas fa-hand-holding-medical stat-icon"></i>
                    </div>
                    <div class="stat-value"><?php echo htmlspecialchars($active_requests); ?></div>
                    <div class="stat-description">Active requests</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">AVAILABLE DONORS</div>
                        <i class="fas fa-users stat-icon"></i>
                    </div>
                    <div class="stat-value"><?php echo htmlspecialchars($available_donors); ?></div>
                    <div class="stat-description">Ready to help</div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="actions-container">
                <a href="schedule_donation.php" class="action-card">
                    <div class="action-header">
                        <div class="action-icon">
                            <i class="fas fa-calendar-plus"></i>
                        </div>
                        <div class="action-title">Schedule Donation</div>
                    </div>
                    <div class="action-description">
                        Book your next blood donation appointment at a convenient time and location.
                    </div>
                    <div class="action-button">
                        <i class="fas fa-plus"></i>
                        Schedule Now
                    </div>
                </a>
                
                <a href="find_donors.php" class="action-card">
                    <div class="action-header">
                        <div class="action-icon">
                            <i class="fas fa-user-friends"></i>
                        </div>
                        <div class="action-title">Find Donors</div>
                    </div>
                    <div class="action-description">
                        Search for available donors by blood type, location, and availability.
                    </div>
                    <div class="action-button">
                        <i class="fas fa-search"></i>
                        Search Donors
                    </div>
                </a>
                
                <a href="blood_requests.php" class="action-card">
                    <div class="action-header">
                        <div class="action-icon">
                            <i class="fas fa-hand-holding-heart"></i>
                        </div>
                        <div class="action-title">Blood Requests</div>
                    </div>
                    <div class="action-description">
                        View urgent blood requests from hospitals and patients in need.
                    </div>
                    <div class="action-button">
                        <i class="fas fa-list"></i>
                        View Requests
                    </div>
                </a>
            </div>

            <?php if ($is_admin): ?>
            <div class="admin-panel">
                <h2>🛠️ Admin Panel</h2>
                <div class="actions-container">
                    <a href="manage_users.php" class="action-card">
                        <div class="action-header">
                            <div class="action-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="action-title">Manage Users</div>
                        </div>
                        <div class="action-description">
                            User management and permissions.
                        </div>
                        <div class="action-button">
                            <i class="fas fa-cog"></i>
                            Manage Users
                        </div>
                    </a>
                    <a href="manage_requests.php" class="action-card">
                        <div class="action-header">
                            <div class="action-icon">
                                <i class="fas fa-clipboard-list"></i>
                            </div>
                            <div class="action-title">Manage Requests</div>
                        </div>
                        <div class="action-description">
                            Blood requests management and approval.
                        </div>
                        <div class="action-button">
                            <i class="fas fa-tasks"></i>
                            Manage Requests
                        </div>
                    </a>
                    <a href="inventory_management.php" class="action-card">
                        <div class="action-header">
                            <div class="action-icon">
                                <i class="fas fa-boxes"></i>
                            </div>
                            <div class="action-title">Inventory Management</div>
                        </div>
                        <div class="action-description">
                            Stock control and blood inventory management.
                        </div>
                        <div class="action-button">
                            <i class="fas fa-chart-bar"></i>
                            Manage Inventory
                        </div>
                    </a>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Content Row -->
            <div class="content-row">
                <!-- Recent Activity -->
                <div class="activity-card">
                    <div class="card-header">
                        <h2 class="card-title">
                            <i class="fas fa-bell"></i>
                            Recent Activity
                        </h2>
                        <a href="blood_requests.php" style="color: var(--primary); text-decoration: none; font-weight: 500;">View All</a>
                    </div>
                    
                    <div class="activity-list">
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i class="fas fa-exclamation-circle"></i>
                            </div>
                            <div class="activity-content">
                                <div class="activity-text">
                                    <strong>Urgent:</strong> Need <strong>O+</strong> blood at City Hospital - 2 units required
                                </div>
                                <div class="activity-time">2 hours ago</div>
                            </div>
                        </div>
                        
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i class="fas fa-ambulance"></i>
                            </div>
                            <div class="activity-content">
                                <div class="activity-text">
                                    <strong>Emergency:</strong> <strong>B-</strong> required at Regional Blood Bank
                                </div>
                                <div class="activity-time">5 hours ago</div>
                            </div>
                        </div>
                        
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <div class="activity-content">
                                <div class="activity-text">
                                    Blood drive scheduled at Community Center this weekend
                                </div>
                                <div class="activity-time">1 day ago</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Emergency Section -->
                <div class="emergency-card">
                    <div class="card-header">
                        <h2 class="card-title">
                            <i class="fas fa-first-aid"></i>
                            Emergency SOS
                        </h2>
                    </div>
                    
                    <div class="emergency-alert">
                        <div class="emergency-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="emergency-content">
                            <h3>Critical Need</h3>
                            <p><?php echo htmlspecialchars($_SESSION['blood_type']); ?> blood required immediately</p>
                        </div>
                    </div>
                    
                    <p style="margin-bottom: 15px; font-size: 14px; color: var(--text-light);">
                        There is an urgent need for your blood type. Your donation can save lives immediately.
                    </p>
                    
                    <a href="schedule_donation.php" class="sos-button">
                        <i class="fas fa-bolt"></i>
                        Respond to Emergency
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Add interactivity to the dashboard
        document.addEventListener('DOMContentLoaded', function() {
            // Update date display
            const dateElement = document.getElementById('currentDate');
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            const today = new Date();
            dateElement.textContent = today.toLocaleDateString('en-US', options);
            
            // Navigation active state
            const navItems = document.querySelectorAll('.nav-item');
            navItems.forEach(item => {
                item.addEventListener('click', function() {
                    navItems.forEach(i => i.classList.remove('active'));
                    this.classList.add('active');
                });
            });
            
            // Add hover effect to action cards
            const actionCards = document.querySelectorAll('.action-card');
            actionCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-8px)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });

            // Add notification badge for urgent requests
            const bloodRequestsBtn = document.querySelector('a[href="blood_requests.php"]');
            if (bloodRequestsBtn && <?php echo $active_requests; ?> > 0) {
                const badge = document.createElement('span');
                badge.textContent = '!';
                badge.style.position = 'absolute';
                badge.style.top = '10px';
                badge.style.right = '10px';
                badge.style.background = '#e74c3c';
                badge.style.color = 'white';
                badge.style.width = '20px';
                badge.style.height = '20px';
                badge.style.borderRadius = '50%';
                badge.style.display = 'flex';
                badge.style.alignItems = 'center';
                badge.style.justifyContent = 'center';
                badge.style.fontSize = '12px';
                badge.style.fontWeight = 'bold';
                bloodRequestsBtn.style.position = 'relative';
                bloodRequestsBtn.appendChild(badge);
            }
        });
    </script>
</body>
</html>