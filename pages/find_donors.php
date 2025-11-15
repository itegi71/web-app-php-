<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

try {
    $auth = new Auth();
    $conn = $auth->conn;
    
    $blood_type = $_GET['blood_type'] ?? '';
    $city = $_GET['city'] ?? '';
    
    $query = "SELECT full_name, blood_type, city, phone, last_donation_date, is_available 
              FROM users 
              WHERE user_type = 'donor' AND is_available = 1";
    
    $params = [];
    
    if (!empty($blood_type)) {
        $query .= " AND blood_type = :blood_type";
        $params[':blood_type'] = $blood_type;
    }
    
    if (!empty($city)) {
        $query .= " AND city LIKE :city";
        $params[':city'] = "%$city%";
    }
    
    $query .= " ORDER BY full_name";
    
    $stmt = $conn->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $donors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error = "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find Donors - Blood Donation System</title>
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

        .alert.info {
            background: #e3f2fd;
            color: var(--accent);
            border-left: 4px solid var(--accent);
        }

        /* Search Section */
        .search-section {
            background: var(--white);
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
        }

        .search-section h2 {
            color: var(--primary);
            margin-bottom: 25px;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .search-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            align-items: end;
        }

        .form-group {
            margin-bottom: 0;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text);
        }

        input, select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: var(--white);
        }

        input:focus, select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(198, 40, 40, 0.1);
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
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            height: 44px;
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

        /* Results Section */
        .results-section {
            background: var(--white);
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        }

        .results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .results-header h2 {
            color: var(--primary);
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .results-count {
            background: var(--primary);
            color: var(--white);
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }

        /* Donor Cards */
        .donors-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }

        .donor-card {
            background: var(--white);
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.08);
            border: 1px solid #f0f0f0;
            transition: all 0.3s ease;
            position: relative;
        }

        .donor-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
        }

        .donor-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .donor-name {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 5px;
        }

        .donor-status {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            background: #e8f5e9;
            color: #2e7d32;
        }

        .blood-type-badge {
            background: var(--primary);
            color: var(--white);
            padding: 8px 15px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 1.1rem;
            display: inline-block;
            margin-bottom: 15px;
        }

        .donor-details {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--text-light);
        }

        .detail-item i {
            width: 16px;
            color: var(--primary);
        }

        .contact-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #f0f0f0;
        }

        .contact-btn {
            flex: 1;
            padding: 8px 12px;
            border: 1px solid var(--primary);
            border-radius: 6px;
            background: transparent;
            color: var(--primary);
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }

        .contact-btn:hover {
            background: var(--primary);
            color: var(--white);
        }

        /* Blood Type Info */
        .blood-type-info {
            background: #e3f2fd;
            border-radius: 8px;
            padding: 20px;
            margin-top: 30px;
        }

        .blood-type-info h3 {
            color: var(--accent);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .compatibility-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .compatibility-item {
            background: var(--white);
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .compatibility-blood-type {
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--primary);
            margin-bottom: 5px;
        }

        .compatibility-desc {
            font-size: 0.85rem;
            color: var(--text-light);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .header .container {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .user-info {
                justify-content: center;
            }
            
            .search-form {
                grid-template-columns: 1fr;
            }
            
            .donors-grid {
                grid-template-columns: 1fr;
            }
            
            .results-header {
                flex-direction: column;
                align-items: flex-start;
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

        .donor-card {
            animation: fadeInUp 0.5s ease forwards;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <header class="header">
            <div class="container">
                <h1>Find Blood Donors</h1>
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
                <!-- Search Section -->
                <div class="search-section">
                    <h2><i class="fas fa-search"></i> Find Available Donors</h2>
                    
                    <form method="GET" action="">
                        <div class="search-form">
                            <div class="form-group">
                                <label for="blood_type">Blood Type</label>
                                <select name="blood_type" id="blood_type">
                                    <option value="">All Blood Types</option>
                                    <option value="A+" <?php echo ($blood_type == 'A+') ? 'selected' : ''; ?>>A+</option>
                                    <option value="A-" <?php echo ($blood_type == 'A-') ? 'selected' : ''; ?>>A-</option>
                                    <option value="B+" <?php echo ($blood_type == 'B+') ? 'selected' : ''; ?>>B+</option>
                                    <option value="B-" <?php echo ($blood_type == 'B-') ? 'selected' : ''; ?>>B-</option>
                                    <option value="AB+" <?php echo ($blood_type == 'AB+') ? 'selected' : ''; ?>>AB+</option>
                                    <option value="AB-" <?php echo ($blood_type == 'AB-') ? 'selected' : ''; ?>>AB-</option>
                                    <option value="O+" <?php echo ($blood_type == 'O+') ? 'selected' : ''; ?>>O+</option>
                                    <option value="O-" <?php echo ($blood_type == 'O-') ? 'selected' : ''; ?>>O-</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="city">City</label>
                                <input type="text" name="city" id="city" placeholder="Enter city name" value="<?php echo htmlspecialchars($city); ?>">
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" class="btn">
                                    <i class="fas fa-search"></i>
                                    Search Donors
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <!-- Results Section -->
                <div class="results-section">
                    <div class="results-header">
                        <h2>
                            <i class="fas fa-users"></i>
                            Available Donors
                            <span class="results-count"><?php echo count($donors); ?> found</span>
                        </h2>
                        
                        <?php if (!empty($blood_type) || !empty($city)): ?>
                            <button class="btn btn-outline" onclick="window.location.href='find_donors.php'">
                                <i class="fas fa-times"></i>
                                Clear Filters
                            </button>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (empty($donors)): ?>
                        <div class="alert info">
                            <i class="fas fa-info-circle"></i>
                            No donors found matching your criteria. Try adjusting your search filters.
                        </div>
                    <?php else: ?>
                        <div class="donors-grid">
                            <?php foreach ($donors as $donor): ?>
                                <div class="donor-card">
                                    <div class="donor-header">
                                        <div>
                                            <div class="donor-name"><?php echo htmlspecialchars($donor['full_name']); ?></div>
                                            <span class="donor-status">
                                                <i class="fas fa-check-circle"></i>
                                                Available
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="blood-type-badge"><?php echo $donor['blood_type']; ?></div>
                                    
                                    <div class="donor-details">
                                        <div class="detail-item">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <span><?php echo $donor['city'] ? htmlspecialchars($donor['city']) : 'Location not specified'; ?></span>
                                        </div>
                                        
                                        <div class="detail-item">
                                            <i class="fas fa-phone"></i>
                                            <span><?php echo $donor['phone']; ?></span>
                                        </div>
                                        
                                        <?php if ($donor['last_donation_date']): ?>
                                            <div class="detail-item">
                                                <i class="fas fa-calendar-alt"></i>
                                                <span>Last donation: <?php echo date('M j, Y', strtotime($donor['last_donation_date'])); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="contact-actions">
                                        <button class="contact-btn" onclick="contactDonor('<?php echo $donor['phone']; ?>', '<?php echo htmlspecialchars($donor['full_name']); ?>')">
                                            <i class="fas fa-phone"></i>
                                            Call
                                        </button>
                                        <button class="contact-btn" onclick="messageDonor('<?php echo $donor['phone']; ?>', '<?php echo htmlspecialchars($donor['full_name']); ?>')">
                                            <i class="fas fa-comment"></i>
                                            Message
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Blood Type Compatibility Info -->
                <div class="blood-type-info">
                    <h3><i class="fas fa-info-circle"></i> Blood Type Compatibility</h3>
                    <div class="compatibility-grid">
                        <div class="compatibility-item">
                            <div class="compatibility-blood-type">O-</div>
                            <div class="compatibility-desc">Universal Donor</div>
                        </div>
                        <div class="compatibility-item">
                            <div class="compatibility-blood-type">O+</div>
                            <div class="compatibility-desc">Compatible with O+, A+, B+, AB+</div>
                        </div>
                        <div class="compatibility-item">
                            <div class="compatibility-blood-type">AB+</div>
                            <div class="compatibility-desc">Universal Recipient</div>
                        </div>
                        <div class="compatibility-item">
                            <div class="compatibility-blood-type">AB-</div>
                            <div class="compatibility-desc">Compatible with AB-, AB+</div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add search functionality enhancements
            const bloodTypeSelect = document.getElementById('blood_type');
            const cityInput = document.getElementById('city');
            
            // Real-time search suggestions
            const cities = ['New York', 'Los Angeles', 'Chicago', 'Houston', 'Phoenix', 
                           'Philadelphia', 'San Antonio', 'San Diego', 'Dallas', 'San Jose'];
            
            cityInput.addEventListener('input', function() {
                // In a real app, you would fetch cities from the database
                console.log('Searching for city:', this.value);
            });
            
            // Quick filter buttons for common blood types
            const quickFilters = document.createElement('div');
            quickFilters.className = 'quick-filters';
            quickFilters.style.marginTop = '15px';
            quickFilters.style.display = 'flex';
            quickFilters.style.gap = '10px';
            quickFilters.style.flexWrap = 'wrap';
            
            const commonBloodTypes = ['O+', 'O-', 'A+', 'B+', 'AB+'];
            commonBloodTypes.forEach(type => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'btn btn-outline';
                btn.style.padding = '5px 12px';
                btn.style.fontSize = '0.85rem';
                btn.textContent = type;
                btn.onclick = function() {
                    bloodTypeSelect.value = type;
                    document.querySelector('form').submit();
                };
                quickFilters.appendChild(btn);
            });
            
            document.querySelector('.search-form').appendChild(quickFilters);
        });
        
        function contactDonor(phone, name) {
            if (confirm(`Call ${name} at ${phone}?`)) {
                window.location.href = `tel:${phone}`;
            }
        }
        
        function messageDonor(phone, name) {
            if (confirm(`Send message to ${name} at ${phone}?`)) {
                window.location.href = `sms:${phone}`;
            }
        }
        
        function shareDonorInfo(name, bloodType, phone) {
            const message = `Blood Donor Contact:\nName: ${name}\nBlood Type: ${bloodType}\nPhone: ${phone}\n\nFound via BloodLife Donation System`;
            
            if (navigator.share) {
                navigator.share({
                    title: 'Blood Donor Contact',
                    text: message
                });
            } else {
                navigator.clipboard.writeText(message).then(() => {
                    alert('Donor information copied to clipboard!');
                });
            }
        }
    </script>
</body>
</html>