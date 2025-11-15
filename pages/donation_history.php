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
    
    // Get donation history
    $stmt = $conn->prepare("
        SELECT d.*, a.blood_bank_name, a.location 
        FROM donations d 
        LEFT JOIN appointments a ON d.user_id = a.user_id AND DATE(d.donation_date) = DATE(a.appointment_date)
        WHERE d.user_id = :user_id 
        ORDER BY d.donation_date DESC
    ");
    $stmt->bindParam(":user_id", $user_id);
    $stmt->execute();
    $donations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate statistics
    $total_donations = count($donations);
    $current_year_donations = 0;
    $last_donation_date = null;
    $total_lives_impacted = $total_donations * 3; // Each donation can save up to 3 lives
    
    if (!empty($donations)) {
        $last_donation_date = new DateTime($donations[0]['donation_date']);
        $today = new DateTime();
        $days_since_last_donation = $today->diff($last_donation_date)->format('%a');
        
        // Count donations this year
        $current_year = date('Y');
        foreach ($donations as $donation) {
            if (date('Y', strtotime($donation['donation_date'])) == $current_year) {
                $current_year_donations++;
            }
        }
    }
    
} catch (Exception $e) {
    $error = "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donation History - Blood Donation System</title>
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

        .stats-section h2 {
            color: var(--primary);
            margin-bottom: 25px;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
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

        .stat-description {
            font-size: 0.85rem;
            color: var(--text-light);
        }

        /* Donations Section */
        .donations-section {
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

        .donations-count {
            background: var(--primary);
            color: var(--white);
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .donations-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .donation-card {
            background: var(--white);
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.08);
            border: 1px solid #f0f0f0;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .donation-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
        }

        .donation-card::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 6px;
            background: var(--primary);
        }

        .donation-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .donation-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 5px;
        }

        .donation-date {
            font-size: 1rem;
            color: var(--text-light);
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: #e8f5e9;
            color: #2e7d32;
        }

        .donation-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .detail-label {
            font-size: 0.85rem;
            color: var(--text-light);
            font-weight: 600;
        }

        .detail-value {
            font-weight: 500;
        }

        .impact-badge {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: var(--white);
            padding: 8px 15px;
            border-radius: 8px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 10px;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 40px;
            color: var(--text-light);
        }

        .empty-state i {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
            color: var(--text);
        }

        .empty-state p {
            margin-bottom: 20px;
            font-size: 1rem;
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
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(198, 40, 40, 0.3);
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

        .milestone-card {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: var(--white);
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }

        .milestone-icon {
            font-size: 2.5rem;
            margin-bottom: 10px;
            opacity: 0.9;
        }

        .milestone-title {
            font-size: 1.1rem;
            margin-bottom: 5px;
            font-weight: 600;
        }

        .milestone-description {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .progress-section {
            margin-top: 15px;
        }

        .progress-label {
            display: flex;
            justify-content: between;
            margin-bottom: 5px;
            font-size: 0.9rem;
        }

        .progress-bar {
            background: #e0e0e0;
            border-radius: 10px;
            height: 8px;
            overflow: hidden;
        }

        .progress-fill {
            background: var(--primary);
            height: 100%;
            border-radius: 10px;
            transition: width 0.3s ease;
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
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .donation-details {
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

        .donation-card {
            animation: fadeInUp 0.5s ease forwards;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <header class="header">
            <div class="container">
                <h1>Donation History</h1>
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

                <div class="content-wrapper">
                    <div>
                        <!-- Statistics Section -->
                        <div class="stats-section">
                            <h2><i class="fas fa-chart-line"></i> Your Donation Impact</h2>
                            <div class="stats-grid">
                                <div class="stat-card">
                                    <i class="fas fa-tint stat-icon"></i>
                                    <div class="stat-number"><?php echo $total_donations; ?></div>
                                    <div class="stat-title">Total Donations</div>
                                    <div class="stat-description">Lifetime contributions</div>
                                </div>
                                
                                <div class="stat-card">
                                    <i class="fas fa-heart stat-icon"></i>
                                    <div class="stat-number"><?php echo $total_lives_impacted; ?></div>
                                    <div class="stat-title">Lives Impacted</div>
                                    <div class="stat-description">Estimated lives saved</div>
                                </div>
                                
                                <div class="stat-card">
                                    <i class="fas fa-calendar-alt stat-icon"></i>
                                    <div class="stat-number"><?php echo $current_year_donations; ?></div>
                                    <div class="stat-title">This Year</div>
                                    <div class="stat-description"><?php echo date('Y'); ?> donations</div>
                                </div>
                                
                                <div class="stat-card">
                                    <i class="fas fa-clock stat-icon"></i>
                                    <div class="stat-number">
                                        <?php 
                                        if (!empty($donations)) {
                                            echo $days_since_last_donation;
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </div>
                                    <div class="stat-title">Days Since Last</div>
                                    <div class="stat-description">Since last donation</div>
                                </div>
                            </div>
                        </div>

                        <!-- Donations List -->
                        <div class="donations-section">
                            <div class="section-header">
                                <h2>
                                    <i class="fas fa-history"></i>
                                    Your Donation Records
                                    <span class="donations-count"><?php echo count($donations); ?></span>
                                </h2>
                            </div>
                            
                            <?php if (empty($donations)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-tint"></i>
                                    <h3>No Donation Records Yet</h3>
                                    <p>You haven't made any blood donations yet. Start your journey to save lives today!</p>
                                    <a href="schedule_donation.php" class="btn">
                                        <i class="fas fa-calendar-plus"></i>
                                        Schedule Your First Donation
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="donations-list">
                                    <?php foreach ($donations as $index => $donation): ?>
                                        <div class="donation-card">
                                            <div class="donation-header">
                                                <div>
                                                    <div class="donation-title">
                                                        Donation #<?php echo $total_donations - $index; ?>
                                                    </div>
                                                    <div class="donation-date">
                                                        <?php echo date('F j, Y', strtotime($donation['donation_date'])); ?>
                                                    </div>
                                                </div>
                                                <span class="status-badge">
                                                    <i class="fas fa-check-circle"></i>
                                                    <?php echo ucfirst($donation['status']); ?>
                                                </span>
                                            </div>
                                            
                                            <div class="donation-details">
                                                <div class="detail-item">
                                                    <span class="detail-label">Location</span>
                                                    <span class="detail-value">
                                                        <?php echo htmlspecialchars($donation['blood_bank_name'] ?? $donation['location'] ?? 'Not specified'); ?>
                                                    </span>
                                                </div>
                                                
                                                <div class="detail-item">
                                                    <span class="detail-label">Date & Time</span>
                                                    <span class="detail-value">
                                                        <?php echo date('M j, Y g:i A', strtotime($donation['donation_date'])); ?>
                                                    </span>
                                                </div>
                                                
                                                <?php if ($donation['location']): ?>
                                                <div class="detail-item">
                                                    <span class="detail-label">Address</span>
                                                    <span class="detail-value"><?php echo htmlspecialchars($donation['location']); ?></span>
                                                </div>
                                                <?php endif; ?>
                                                
                                                <div class="detail-item">
                                                    <span class="detail-label">Donation Type</span>
                                                    <span class="detail-value">Whole Blood</span>
                                                </div>
                                            </div>
                                            
                                            <div class="impact-badge">
                                                <i class="fas fa-heart"></i>
                                                <span>You helped save up to 3 lives with this donation</span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Information Sidebar -->
                    <div class="info-sidebar">
                        <div class="info-card">
                            <h3><i class="fas fa-award"></i> Milestones</h3>
                            <div class="milestone-card">
                                <i class="fas fa-trophy milestone-icon"></i>
                                <div class="milestone-title">
                                    <?php 
                                    if ($total_donations >= 10) {
                                        echo 'Hero Donor';
                                    } elseif ($total_donations >= 5) {
                                        echo 'Dedicated Donor';
                                    } elseif ($total_donations >= 1) {
                                        echo 'First Time Donor';
                                    } else {
                                        echo 'New Donor';
                                    }
                                    ?>
                                </div>
                                <div class="milestone-description">
                                    <?php 
                                    if ($total_donations >= 10) {
                                        echo '10+ donations - Thank you for your incredible commitment!';
                                    } elseif ($total_donations >= 5) {
                                        echo '5-9 donations - You are making a huge difference!';
                                    } elseif ($total_donations >= 1) {
                                        echo '1-4 donations - Welcome to our lifesaving community!';
                                    } else {
                                        echo 'Start your donation journey today!';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>

                        <div class="info-card">
                            <h3><i class="fas fa-target"></i> Next Donation</h3>
                            <?php if (!empty($donations)): ?>
                                <div class="progress-section">
                                    <div class="progress-label">
                                        <span>Days until eligible</span>
                                        <span><?php echo max(0, 56 - $days_since_last_donation); ?> days</span>
                                    </div>
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?php echo min(100, ($days_since_last_donation / 56) * 100); ?>%"></div>
                                    </div>
                                    <p style="font-size: 0.85rem; color: var(--text-light); margin-top: 10px;">
                                        You can donate again after 56 days from your last donation.
                                    </p>
                                </div>
                            <?php else: ?>
                                <p style="color: var(--text-light); font-size: 0.9rem;">
                                    Schedule your first donation to start tracking your eligibility.
                                </p>
                            <?php endif; ?>
                        </div>

                        <div class="info-card">
                            <h3><i class="fas fa-lightbulb"></i> Donation Benefits</h3>
                            <ul>
                                <li>
                                    <i class="fas fa-heartbeat"></i>
                                    <span>Free health screening included</span>
                                </li>
                                <li>
                                    <i class="fas fa-burn"></i>
                                    <span>Burns 650 calories per donation</span>
                                </li>
                                <li>
                                    <i class="fas fa-recycle"></i>
                                    <span>Your body replenishes blood in 24-48 hours</span>
                                </li>
                                <li>
                                    <i class="fas fa-shield-alt"></i>
                                    <span>Reduces risk of heart disease</span>
                                </li>
                            </ul>
                        </div>

                        <div class="info-card">
                            <h3><i class="fas fa-share-alt"></i> Share Your Impact</h3>
                            <p style="margin-bottom: 15px; font-size: 0.9rem; color: var(--text-light);">
                                Inspire others by sharing your donation journey:
                            </p>
                            <div style="display: flex; gap: 10px;">
                                <button class="btn" style="flex: 1; padding: 8px; font-size: 0.85rem;" onclick="shareAchievement()">
                                    <i class="fas fa-share"></i>
                                    Share
                                </button>
                                <button class="btn" style="flex: 1; padding: 8px; font-size: 0.85rem; background: #25D366;" onclick="downloadCertificate()">
                                    <i class="fas fa-certificate"></i>
                                    Certificate
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add animation delays to donation cards
            const donationCards = document.querySelectorAll('.donation-card');
            donationCards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
            });
            
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
        
        function shareAchievement() {
            const totalDonations = <?php echo $total_donations; ?>;
            const livesImpacted = <?php echo $total_lives_impacted; ?>;
            
            const message = `I've donated blood ${totalDonations} times and helped save up to ${livesImpacted} lives! Join me in making a difference. ❤️ #BloodDonation #SaveLives`;
            
            if (navigator.share) {
                navigator.share({
                    title: 'My Blood Donation Achievement',
                    text: message,
                    url: window.location.href
                });
            } else {
                navigator.clipboard.writeText(message).then(() => {
                    alert('Achievement copied to clipboard! Share it on your social media.');
                });
            }
        }
        
        function downloadCertificate() {
            alert('Donation certificate feature coming soon! This will generate a personalized certificate of your donations.');
            // In a real implementation, this would generate and download a PDF certificate
        }
        
        function scheduleNextDonation() {
            const lastDonationDays = <?php echo $days_since_last_donation ?? 0; ?>;
            const daysUntilEligible = Math.max(0, 56 - lastDonationDays);
            
            if (daysUntilEligible === 0) {
                window.location.href = 'schedule_donation.php';
            } else {
                alert(`You can schedule your next donation in ${daysUntilEligible} days. We'll remind you when you're eligible!`);
            }
        }
    </script>
</body>
</html>