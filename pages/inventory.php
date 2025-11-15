<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

try {
    $auth = new Auth();
    $conn = $auth->conn;
    
    // Get blood inventory
    $stmt = $conn->prepare("SELECT * FROM blood_inventory ORDER BY blood_type");
    $stmt->execute();
    $inventory = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate statistics
    $total_units = 0;
    $critical_types = [];
    $low_types = [];
    $good_types = [];
    
    foreach ($inventory as $item) {
        $total_units += $item['units_available'];
        
        if ($item['units_available'] < 5) {
            $critical_types[] = $item['blood_type'];
        } elseif ($item['units_available'] < 10) {
            $low_types[] = $item['blood_type'];
        } else {
            $good_types[] = $item['blood_type'];
        }
    }
    
    $last_updated = date('F j, Y g:i A');
    
} catch (Exception $e) {
    $error = "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blood Inventory - Blood Donation System</title>
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
            --critical: #d32f2f;
            --low: #ffa000;
            --good: #388e3c;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
            color: var(--text);
            min-height: 100vh;
        }

        .dashboard {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: var(--white);
            padding: 15px 0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }

        .header .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }

        .header h1 {
            font-size: 1.8rem;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .back-btn, .logout-btn {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 8px 16px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .back-btn:hover, .logout-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }

        .main-content {
            flex: 1;
            padding: 30px 0;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert.error {
            background: #ffeaea;
            color: var(--danger);
            border-left: 4px solid var(--danger);
        }

        .alert.warning {
            background: #fff3e0;
            color: var(--warning);
            border-left: 4px solid var(--warning);
        }

        /* Content Layout */
        .content-wrapper {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 30px;
            align-items: start;
        }

        /* Stats Section */
        .stats-section {
            background: var(--white);
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
        }

        .stats-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .stats-header h2 {
            color: var(--primary);
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .last-updated {
            font-size: 0.9rem;
            color: var(--text-light);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .stat-card {
            background: var(--white);
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.05);
            display: flex;
            flex-direction: column;
            gap: 15px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border-left: 4px solid var(--primary);
            text-align: center;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .stat-icon {
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 10px;
        }

        .stat-number {
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 5px;
        }

        .stat-title {
            font-size: 1rem;
            color: var(--text-light);
            font-weight: 600;
        }

        /* Inventory Grid */
        .inventory-section {
            background: var(--white);
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .section-header h2 {
            color: var(--primary);
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .inventory-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .blood-card {
            background: var(--white);
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.05);
            text-align: center;
            transition: all 0.3s ease;
            border-top: 4px solid var(--primary);
            position: relative;
            overflow: hidden;
        }

        .blood-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
        }

        .blood-type {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 15px;
            color: var(--primary);
        }

        .units {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .status-good { color: var(--good); }
        .status-low { color: var(--low); }
        .status-critical { color: var(--critical); }

        .status-indicator {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-top: 10px;
        }

        .status-good-bg { background: #e8f5e9; color: var(--good); }
        .status-low-bg { background: #fff3e0; color: var(--low); }
        .status-critical-bg { background: #ffebee; color: var(--critical); }

        .urgent-alert {
            position: absolute;
            top: 10px;
            right: 10px;
            background: var(--critical);
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        /* Info Sidebar */
        .info-sidebar {
            background: var(--white);
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            position: sticky;
            top: 20px;
        }

        .info-card {
            margin-bottom: 25px;
        }

        .info-card:last-child {
            margin-bottom: 0;
        }

        .info-card h3 {
            color: var(--primary);
            margin-bottom: 15px;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .info-card ul {
            list-style: none;
            padding-left: 0;
        }

        .info-card li {
            padding: 8px 0;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            border-bottom: 1px solid #f0f0f0;
        }

        .info-card li:last-child {
            border-bottom: none;
        }

        .info-card li i {
            color: var(--primary);
            margin-top: 3px;
            flex-shrink: 0;
        }

        .guide-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 15px;
        }

        .guide-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            border-radius: 8px;
            background: #f8f9fa;
        }

        .guide-icon {
            font-size: 1.5rem;
            width: 40px;
            text-align: center;
        }

        .guide-content h4 {
            margin-bottom: 5px;
            font-size: 1rem;
        }

        .guide-content p {
            font-size: 0.85rem;
            color: var(--text-light);
            margin: 0;
        }

        .emergency-card {
            background: linear-gradient(135deg, var(--danger) 0%, #b71c1c 100%);
            color: var(--white);
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }

        .emergency-icon {
            font-size: 2.5rem;
            margin-bottom: 10px;
            opacity: 0.9;
        }

        .emergency-title {
            font-size: 1.1rem;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .emergency-contacts {
            text-align: left;
            margin-top: 15px;
        }

        .emergency-contacts li {
            color: white;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        .emergency-contacts li:last-child {
            border-bottom: none;
        }

        .btn {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: var(--white);
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-decoration: none;
            width: 100%;
            margin-top: 10px;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(198, 40, 40, 0.3);
        }

        .btn-outline {
            background: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
        }

        .btn-outline:hover {
            background: var(--primary);
            color: var(--white);
        }

        /* Responsive Design */
        @media (max-width: 968px) {
            .content-wrapper {
                grid-template-columns: 1fr;
            }
            
            .info-sidebar {
                position: static;
            }
        }

        @media (max-width: 768px) {
            .header .container {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .user-info {
                justify-content: center;
            }
            
            .stats-grid, .inventory-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 480px) {
            .stats-grid, .inventory-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Animation */
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

        .blood-card {
            animation: fadeInUp 0.5s ease forwards;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <header class="header">
            <div class="container">
                <h1>Blood Inventory</h1>
                <div class="user-info">
                    <a href="home.php" class="back-btn">
                        <i class="fas fa-arrow-left"></i>
                        Back to Dashboard
                    </a>
                    <a href="logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                    </a>
                </div>
            </div>
        </header>

        <main class="main-content">
            <div class="container">
                <?php if (isset($error)): ?>
                    <div class="alert error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($critical_types)): ?>
                    <div class="alert warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Urgent Need:</strong> 
                        <?php echo implode(', ', $critical_types); ?> blood types are critically low. Please consider donating.
                    </div>
                <?php endif; ?>

                <div class="content-wrapper">
                    <div>
                        <!-- Statistics Section -->
                        <div class="stats-section">
                            <div class="stats-header">
                                <h2><i class="fas fa-chart-bar"></i> Inventory Overview</h2>
                                <div class="last-updated">
                                    <i class="fas fa-clock"></i>
                                    Last updated: <?php echo $last_updated; ?>
                                </div>
                            </div>
                            <div class="stats-grid">
                                <div class="stat-card">
                                    <i class="fas fa-tint stat-icon"></i>
                                    <div class="stat-number"><?php echo $total_units; ?></div>
                                    <div class="stat-title">Total Units Available</div>
                                </div>
                                
                                <div class="stat-card">
                                    <i class="fas fa-check-circle stat-icon"></i>
                                    <div class="stat-number"><?php echo count($good_types); ?></div>
                                    <div class="stat-title">Good Stock Levels</div>
                                </div>
                                
                                <div class="stat-card">
                                    <i class="fas fa-exclamation-triangle stat-icon"></i>
                                    <div class="stat-number"><?php echo count($critical_types); ?></div>
                                    <div class="stat-title">Critical Types</div>
                                </div>
                                
                                <div class="stat-card">
                                    <i class="fas fa-hospital stat-icon"></i>
                                    <div class="stat-number">8</div>
                                    <div class="stat-title">Blood Banks</div>
                                </div>
                            </div>
                        </div>

                        <!-- Blood Inventory Grid -->
                        <div class="inventory-section">
                            <div class="section-header">
                                <h2><i class="fas fa-boxes"></i> Blood Type Availability</h2>
                            </div>
                            
                            <div class="inventory-grid">
                                <?php foreach ($inventory as $item): ?>
                                    <?php
                                    $status_class = 'status-good';
                                    $status_bg = 'status-good-bg';
                                    $status_text = 'Good';
                                    
                                    if ($item['units_available'] < 5) {
                                        $status_class = 'status-critical';
                                        $status_bg = 'status-critical-bg';
                                        $status_text = 'Critical';
                                    } elseif ($item['units_available'] < 10) {
                                        $status_class = 'status-low';
                                        $status_bg = 'status-low-bg';
                                        $status_text = 'Low';
                                    }
                                    ?>
                                    <div class="blood-card">
                                        <?php if ($item['units_available'] < 5): ?>
                                            <div class="urgent-alert">URGENT</div>
                                        <?php endif; ?>
                                        
                                        <div class="blood-type"><?php echo $item['blood_type']; ?></div>
                                        <div class="units <?php echo $status_class; ?>">
                                            <?php echo $item['units_available']; ?> units
                                        </div>
                                        <div class="status-indicator <?php echo $status_bg; ?>">
                                            <i class="fas fa-<?php 
                                                echo $status_text == 'Critical' ? 'exclamation-triangle' : 
                                                     ($status_text == 'Low' ? 'exclamation-circle' : 'check-circle'); 
                                            ?>"></i>
                                            <?php echo $status_text; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Information Sidebar -->
                    <div class="info-sidebar">
                        <div class="info-card">
                            <h3><i class="fas fa-info-circle"></i> Stock Level Guide</h3>
                            <div class="guide-grid">
                                <div class="guide-item">
                                    <div class="guide-icon" style="color: var(--good);">🟩</div>
                                    <div class="guide-content">
                                        <h4>Good</h4>
                                        <p>10+ units available<br>Healthy stock level</p>
                                    </div>
                                </div>
                                <div class="guide-item">
                                    <div class="guide-icon" style="color: var(--low);">🟨</div>
                                    <div class="guide-content">
                                        <h4>Low</h4>
                                        <p>5-9 units available<br>Monitor closely</p>
                                    </div>
                                </div>
                                <div class="guide-item">
                                    <div class="guide-icon" style="color: var(--critical);">🟥</div>
                                    <div class="guide-content">
                                        <h4>Critical</h4>
                                        <p>Less than 5 units<br>Urgent need</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="info-card">
                            <div class="emergency-card">
                                <i class="fas fa-ambulance emergency-icon"></i>
                                <div class="emergency-title">Emergency Contacts</div>
                                <p>Immediate blood needs</p>
                                <ul class="emergency-contacts">
                                    <li>
                                        <i class="fas fa-phone"></i>
                                        <span><strong>Emergency Hotline:</strong> (555) 987-6543</span>
                                    </li>
                                    <li>
                                        <i class="fas fa-hospital"></i>
                                        <span><strong>City Blood Bank:</strong> (555) 123-4567</span>
                                    </li>
                                    <li>
                                        <i class="fas fa-headset"></i>
                                        <span><strong>24/7 Support:</strong> (555) 456-7890</span>
                                    </li>
                                </ul>
                                <button class="btn" onclick="callEmergency()">
                                    <i class="fas fa-phone"></i>
                                    Call Emergency Line
                                </button>
                            </div>
                        </div>

                        <div class="info-card">
                            <h3><i class="fas fa-lightbulb"></i> Did You Know?</h3>
                            <ul>
                                <li>
                                    <i class="fas fa-clock"></i>
                                    <span>Blood has a shelf life of 42 days</span>
                                </li>
                                <li>
                                    <i class="fas fa-users"></i>
                                    <span>1 donation can save up to 3 lives</span>
                                </li>
                                <li>
                                    <i class="fas fa-sync-alt"></i>
                                    <span>O- is the universal donor type</span>
                                </li>
                                <li>
                                    <i class="fas fa-heart"></i>
                                    <span>AB+ is the universal recipient</span>
                                </li>
                            </ul>
                        </div>

                        <div class="info-card">
                            <h3><i class="fas fa-bullhorn"></i> Take Action</h3>
                            <p style="margin-bottom: 15px; font-size: 0.9rem; color: var(--text-light);">
                                Help maintain healthy blood supply levels:
                            </p>
                            <a href="schedule_donation.php" class="btn">
                                <i class="fas fa-calendar-plus"></i>
                                Schedule Donation
                            </a>
                            <button class="btn btn-outline" onclick="shareInventory()">
                                <i class="fas fa-share-alt"></i>
                                Share Inventory Status
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add animation delays to blood cards
            const bloodCards = document.querySelectorAll('.blood-card');
            bloodCards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
            });
            
            // Add real-time updates simulation
            function simulateRealTimeUpdate() {
                // In a real application, this would fetch data from the server
                console.log('Checking for inventory updates...');
            }
            
            // Check for updates every 30 seconds
            setInterval(simulateRealTimeUpdate, 30000);
            
            // Add hover effects to stat cards
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-8px)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(-5px)';
                });
            });
        });
        
        function callEmergency() {
            if (confirm('Call Emergency Blood Hotline at (555) 987-6543?')) {
                window.location.href = 'tel:5559876543';
            }
        }
        
        function shareInventory() {
            const criticalCount = <?php echo count($critical_types); ?>;
            const totalUnits = <?php echo $total_units; ?>;
            
            let message = `Current Blood Inventory: ${totalUnits} total units available. `;
            
            if (criticalCount > 0) {
                message += `URGENT: ${criticalCount} blood types are critically low. Your donation is needed! ❤️`;
            } else {
                message += `Stock levels are stable. Consider donating to help maintain supply! 🩸`;
            }
            
            message += ` #BloodDonation #SaveLives`;
            
            if (navigator.share) {
                navigator.share({
                    title: 'Blood Inventory Status',
                    text: message,
                    url: window.location.href
                });
            } else {
                navigator.clipboard.writeText(message).then(() => {
                    alert('Inventory status copied to clipboard! Share it to raise awareness.');
                });
            }
        }
        
        function requestBloodType(bloodType) {
            alert(`Requesting ${bloodType} blood type... This feature would connect you with available donors in a real application.`);
            // In a real implementation, this would open a request form or contact donors
        }
    </script>
</body>
</html>