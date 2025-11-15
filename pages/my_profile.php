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
    
    // Get user data
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = :id");
    $stmt->bindParam(":id", $user_id);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get donation statistics
    $donation_stmt = $conn->prepare("SELECT COUNT(*) as donation_count, MAX(donation_date) as last_donation FROM donations WHERE user_id = :user_id");
    $donation_stmt->bindParam(":user_id", $user_id);
    $donation_stmt->execute();
    $donation_data = $donation_stmt->fetch(PDO::FETCH_ASSOC);
    
    $total_donations = $donation_data['donation_count'] ?? 0;
    $last_donation = $donation_data['last_donation'] ?? null;
    
    // Calculate days since last donation
    $days_since_last = '-';
    if ($last_donation) {
        $last_date = new DateTime($last_donation);
        $today = new DateTime();
        $interval = $today->diff($last_date);
        $days_since_last = $interval->format('%a') . ' days ago';
    }
    
    // Update profile if form submitted
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $full_name = sanitizeInput($_POST['full_name']);
        $phone = sanitizeInput($_POST['phone']);
        $address = sanitizeInput($_POST['address']);
        $city = sanitizeInput($_POST['city']);
        $is_available = isset($_POST['is_available']) ? 1 : 0;
        
        // Handle profile picture upload
        $profile_picture = $user['profile_picture'] ?? null;
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/../uploads/profiles/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_extension = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
            $filename = 'profile_' . $user_id . '_' . time() . '.' . $file_extension;
            $target_file = $upload_dir . $filename;
            
            // Validate file type and size
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
            $max_size = 2 * 1024 * 1024; // 2MB
            
            if (in_array(strtolower($file_extension), $allowed_types) && 
                $_FILES['profile_picture']['size'] <= $max_size) {
                
                if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target_file)) {
                    $profile_picture = $filename;
                    
                    // Delete old profile picture if exists
                    if (!empty($user['profile_picture']) && file_exists($upload_dir . $user['profile_picture'])) {
                        unlink($upload_dir . $user['profile_picture']);
                    }
                }
            }
        }
        
        $update_query = "UPDATE users SET full_name = :full_name, phone = :phone, 
                         address = :address, city = :city, is_available = :is_available,
                         profile_picture = :profile_picture 
                         WHERE id = :id";
        
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bindParam(":full_name", $full_name);
        $update_stmt->bindParam(":phone", $phone);
        $update_stmt->bindParam(":address", $address);
        $update_stmt->bindParam(":city", $city);
        $update_stmt->bindParam(":is_available", $is_available);
        $update_stmt->bindParam(":profile_picture", $profile_picture);
        $update_stmt->bindParam(":id", $user_id);
        
        if ($update_stmt->execute()) {
            $_SESSION['user_name'] = $full_name;
            if ($profile_picture) {
                $_SESSION['profile_picture'] = $profile_picture;
            }
            $success = "Profile updated successfully!";
            // Refresh user data
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $error = "Failed to update profile.";
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
    <title>My Profile - Blood Donation System</title>
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

        .alert.success {
            background: #e8f5e9;
            color: var(--success);
            border-left: 4px solid var(--success);
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

        /* Profile Section */
        .profile-section {
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

        /* Profile Picture Section */
        .profile-picture-section {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 30px;
            border-bottom: 1px solid #f0f0f0;
        }

        .profile-picture-container {
            position: relative;
            display: inline-block;
            margin-bottom: 15px;
        }

        .profile-picture {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--primary);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: var(--primary-light);
        }

        .profile-picture-upload {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background: var(--primary);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid white;
        }

        .profile-picture-upload:hover {
            background: var(--primary-dark);
            transform: scale(1.1);
        }

        .profile-picture-upload input {
            display: none;
        }

        .profile-picture-text {
            font-size: 0.9rem;
            color: var(--text-light);
        }

        /* Form Styles */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text);
        }

        input, select, textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: var(--white);
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(198, 40, 40, 0.1);
        }

        input:disabled {
            background: #f8f9fa;
            color: var(--text-light);
            cursor: not-allowed;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 5px;
        }

        .checkbox-group input[type="checkbox"] {
            width: auto;
            transform: scale(1.2);
        }

        .form-note {
            font-size: 0.85rem;
            color: var(--text-light);
            margin-top: 5px;
        }

        .btn {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: var(--white);
            border: none;
            padding: 14px 25px;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
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

        /* Stats Section */
        .stats-section {
            background: var(--white);
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            margin-top: 30px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .stat-card {
            background: var(--white);
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.05);
            text-align: center;
            transition: transform 0.3s ease;
            border-left: 4px solid var(--primary);
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 0.9rem;
            color: var(--text-light);
            font-weight: 600;
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

        .badge-card {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: var(--white);
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }

        .badge-icon {
            font-size: 3rem;
            margin-bottom: 10px;
            opacity: 0.9;
        }

        .badge-title {
            font-size: 1.1rem;
            margin-bottom: 5px;
            font-weight: 600;
        }

        .badge-description {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .progress-section {
            margin-top: 15px;
        }

        .progress-label {
            display: flex;
            justify-content: space-between;
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
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
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

        .profile-section, .stats-section {
            animation: fadeInUp 0.5s ease forwards;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <header class="header">
            <div class="container">
                <h1>My Profile</h1>
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
                <?php if (isset($success)): ?>
                    <div class="alert success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo $success; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="alert error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <div class="content-wrapper">
                    <div>
                        <!-- Profile Information Section -->
                        <div class="profile-section">
                            <div class="section-header">
                                <h2><i class="fas fa-user-circle"></i> Personal Information</h2>
                            </div>

                            <!-- Profile Picture -->
                            <div class="profile-picture-section">
                                <div class="profile-picture-container">
                                    <div class="profile-picture" id="profilePicturePreview">
                                        <?php if (!empty($user['profile_picture'])): ?>
                                            <img src="../uploads/profiles/<?php echo htmlspecialchars($user['profile_picture']); ?>" 
                                                 alt="Profile Picture" 
                                                 style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                                        <?php else: ?>
                                            <i class="fas fa-user"></i>
                                        <?php endif; ?>
                                    </div>
                                    <label class="profile-picture-upload" for="profilePictureInput">
                                        <i class="fas fa-camera"></i>
                                        <input type="file" id="profilePictureInput" name="profile_picture" accept="image/*" onchange="previewProfilePicture(this)">
                                    </label>
                                </div>
                                <div class="profile-picture-text">
                                    Click the camera icon to upload a profile picture<br>
                                    Max size: 2MB • JPG, PNG, GIF
                                </div>
                            </div>

                            <!-- Profile Form -->
                            <form method="POST" action="" enctype="multipart/form-data">
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label for="full_name">Full Name</label>
                                        <input type="text" name="full_name" id="full_name" 
                                               value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                                    </div>

                                    <div class="form-group">
                                        <label for="email">Email Address</label>
                                        <input type="email" id="email" 
                                               value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                                        <div class="form-note">Email cannot be changed</div>
                                    </div>

                                    <div class="form-group">
                                        <label for="blood_type">Blood Type</label>
                                        <input type="text" id="blood_type" 
                                               value="<?php echo htmlspecialchars($user['blood_type']); ?>" disabled>
                                        <div class="form-note">Your blood type is permanent</div>
                                    </div>

                                    <div class="form-group">
                                        <label for="phone">Phone Number</label>
                                        <input type="tel" name="phone" id="phone" 
                                               value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" required>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="address">Address</label>
                                    <textarea name="address" id="address" rows="3" 
                                              placeholder="Enter your complete address"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                                </div>

                                <div class="form-group">
                                    <label for="city">City</label>
                                    <input type="text" name="city" id="city" 
                                           value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>" 
                                           placeholder="Enter your city">
                                </div>

                                <div class="form-group">
                                    <div class="checkbox-group">
                                        <input type="checkbox" name="is_available" id="is_available" value="1" 
                                               <?php echo ($user['is_available'] ?? 1) ? 'checked' : ''; ?>>
                                        <label for="is_available" style="margin: 0; font-weight: 600;">
                                            Available for donation requests
                                        </label>
                                    </div>
                                    <div class="form-note">
                                        When checked, other users can find you when searching for donors matching your blood type
                                    </div>
                                </div>

                                <button type="submit" class="btn">
                                    <i class="fas fa-save"></i>
                                    Update Profile
                                </button>
                            </form>
                        </div>

                        <!-- Donation Statistics Section -->
                        <div class="stats-section">
                            <div class="section-header">
                                <h2><i class="fas fa-chart-line"></i> Donation Statistics</h2>
                            </div>
                            
                            <div class="stats-grid">
                                <div class="stat-card">
                                    <div class="stat-number"><?php echo $total_donations; ?></div>
                                    <div class="stat-label">Total Donations</div>
                                </div>
                                
                                <div class="stat-card">
                                    <div class="stat-number"><?php echo $total_donations * 3; ?></div>
                                    <div class="stat-label">Lives Impacted</div>
                                </div>
                                
                                <div class="stat-card">
                                    <div class="stat-number"><?php echo $days_since_last; ?></div>
                                    <div class="stat-label">Last Donation</div>
                                </div>
                                
                                <div class="stat-card">
                                    <div class="stat-number">
                                        <?php echo ($user['is_available'] ?? 1) ? 'Yes' : 'No'; ?>
                                    </div>
                                    <div class="stat-label">Available to Donate</div>
                                </div>
                            </div>
                            
                            <?php if (!empty($last_donation)): ?>
                                <div class="progress-section">
                                    <div class="progress-label">
                                        <span>Days until next donation</span>
                                        <span><?php echo max(0, 56 - (int)$days_since_last); ?> days</span>
                                    </div>
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?php echo min(100, ((int)$days_since_last / 56) * 100); ?>%"></div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Information Sidebar -->
                    <div class="info-sidebar">
                        <div class="info-card">
                            <h3><i class="fas fa-award"></i> Your Badge</h3>
                            <div class="badge-card">
                                <i class="fas fa-trophy badge-icon"></i>
                                <div class="badge-title">
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
                                <div class="badge-description">
                                    <?php 
                                    if ($total_donations >= 10) {
                                        echo '10+ donations - Lifesaving Hero!';
                                    } elseif ($total_donations >= 5) {
                                        echo '5-9 donations - Making a huge impact!';
                                    } elseif ($total_donations >= 1) {
                                        echo '1-4 donations - Welcome to the team!';
                                    } else {
                                        echo 'Ready to start your journey!';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>

                        <div class="info-card">
                            <h3><i class="fas fa-shield-alt"></i> Privacy & Safety</h3>
                            <ul>
                                <li>
                                    <i class="fas fa-eye"></i>
                                    <span>Only your name and blood type are visible to others</span>
                                </li>
                                <li>
                                    <i class="fas fa-phone"></i>
                                    <span>Contact details are shared only when you approve requests</span>
                                </li>
                                <li>
                                    <i class="fas fa-lock"></i>
                                    <span>Your data is encrypted and secure</span>
                                </li>
                                <li>
                                    <i class="fas fa-bell"></i>
                                    <span>You control all notification preferences</span>
                                </li>
                            </ul>
                        </div>

                        <div class="info-card">
                            <h3><i class="fas fa-cog"></i> Quick Actions</h3>
                            <a href="schedule_donation.php" class="btn" style="margin-bottom: 10px;">
                                <i class="fas fa-calendar-plus"></i>
                                Schedule Donation
                            </a>
                            <a href="donation_history.php" class="btn btn-outline" style="margin-bottom: 10px;">
                                <i class="fas fa-history"></i>
                                View History
                            </a>
                            <button class="btn btn-outline" onclick="downloadData()" style="width: 100%;">
                                <i class="fas fa-download"></i>
                                Export My Data
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize profile picture preview
            const profilePictureInput = document.getElementById('profilePictureInput');
            const profilePicturePreview = document.getElementById('profilePicturePreview');
            
            // Add animation to stat cards
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
            });
        });
        
        function previewProfilePicture(input) {
            const preview = document.getElementById('profilePicturePreview');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.innerHTML = `<img src="${e.target.result}" alt="Profile Picture" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">`;
                }
                
                reader.readAsDataURL(input.files[0]);
                
                // Validate file size
                if (input.files[0].size > 2 * 1024 * 1024) {
                    alert('File size must be less than 2MB');
                    input.value = '';
                    preview.innerHTML = '<i class="fas fa-user"></i>';
                }
            }
        }
        
        function downloadData() {
            alert('Data export feature coming soon! This will generate a PDF with your donation history and profile information.');
            // In a real implementation, this would generate and download a PDF report
        }
        
        function shareProfile() {
            const totalDonations = <?php echo $total_donations; ?>;
            const bloodType = '<?php echo $user['blood_type']; ?>';
            const livesImpacted = totalDonations * 3;
            
            const message = `I'm a blood donor with type ${bloodType} and have donated ${totalDonations} times, helping save up to ${livesImpacted} lives! Join me in making a difference. ❤️ #BloodDonation #SaveLives`;
            
            if (navigator.share) {
                navigator.share({
                    title: 'My Blood Donation Profile',
                    text: message,
                    url: window.location.href
                });
            } else {
                navigator.clipboard.writeText(message).then(() => {
                    alert('Profile summary copied to clipboard! Share it on your social media.');
                });
            }
        }
    </script>
</body>
</html>