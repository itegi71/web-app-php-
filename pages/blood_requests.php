<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

try {
    $auth = new Auth();
    $conn = $auth->conn;
    
    // Get blood requests
    $stmt = $conn->prepare("
        SELECT br.*, u.full_name as requester_name 
        FROM blood_requests br 
        LEFT JOIN users u ON br.requested_by = u.id 
        WHERE br.status = 'open' 
        ORDER BY 
            CASE br.urgency_level 
                WHEN 'critical' THEN 1 
                WHEN 'high' THEN 2 
                WHEN 'medium' THEN 3 
                WHEN 'low' THEN 4 
            END,
            br.required_date ASC
    ");
    $stmt->execute();
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Create new request
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_request'])) {
        $patient_name = sanitizeInput($_POST['patient_name']);
        $blood_type = sanitizeInput($_POST['blood_type']);
        $units_required = sanitizeInput($_POST['units_required']);
        $hospital_name = sanitizeInput($_POST['hospital_name']);
        $urgency_level = sanitizeInput($_POST['urgency_level']);
        $contact_number = sanitizeInput($_POST['contact_number']);
        $address = sanitizeInput($_POST['address']);
        $required_date = sanitizeInput($_POST['required_date']);
        $additional_notes = sanitizeInput($_POST['additional_notes']);
        
        $insert_query = "INSERT INTO blood_requests 
                        (patient_name, blood_type, units_required, hospital_name, urgency_level, contact_number, address, required_date, additional_notes, requested_by) 
                        VALUES (:patient_name, :blood_type, :units_required, :hospital_name, :urgency_level, :contact_number, :address, :required_date, :additional_notes, :requested_by)";
        
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bindParam(":patient_name", $patient_name);
        $insert_stmt->bindParam(":blood_type", $blood_type);
        $insert_stmt->bindParam(":units_required", $units_required);
        $insert_stmt->bindParam(":hospital_name", $hospital_name);
        $insert_stmt->bindParam(":urgency_level", $urgency_level);
        $insert_stmt->bindParam(":contact_number", $contact_number);
        $insert_stmt->bindParam(":address", $address);
        $insert_stmt->bindParam(":required_date", $required_date);
        $insert_stmt->bindParam(":additional_notes", $additional_notes);
        $insert_stmt->bindParam(":requested_by", $_SESSION['user_id']);
        
        if ($insert_stmt->execute()) {
            $success = "Blood request created successfully!";
            
            // Send notifications to matching donors (commented out as in original)
            /*
            require_once __DIR__ . '/../includes/email_service.php';
            require_once __DIR__ . '/../includes/sms_service.php';
            
            $emailService = new EmailService(new Database());
            $smsService = new SMSService(new Database());
            
            // Send email notifications
            $emails_sent = $emailService->sendBloodRequestNotification(
                $blood_type, 
                $units_required, 
                $hospital_name, 
                $urgency_level
            );
            
            // Send SMS to available donors with matching blood type
            $stmt = $conn->prepare("
                SELECT phone, full_name FROM users 
                WHERE blood_type = ? AND is_available = 1 AND user_type = 'donor' AND phone IS NOT NULL
            ");
            $stmt->execute([$blood_type]);
            $donors = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $sms_sent = 0;
            foreach ($donors as $donor) {
                if ($smsService->sendUrgentRequestAlert($donor['phone'], $blood_type, $hospital_name)) {
                    $sms_sent++;
                }
            }
            
            $success .= " Notifications sent to $emails_sent donors via email and $sms_sent via SMS.";
            */
            
            // Refresh requests
            $stmt->execute();
            $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $error = "Failed to create blood request.";
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
    <title>Blood Requests - Blood Donation System</title>
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
            --high: #f57c00;
            --medium: #ffa000;
            --low: #388e3c;
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

        .alert.info {
            background: #e3f2fd;
            color: var(--accent);
            border-left: 4px solid var(--accent);
        }

        /* Content Layout */
        .content-wrapper {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 30px;
            align-items: start;
        }

        /* Form Styles */
        .form-container {
            background: var(--white);
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
        }

        .form-container h2 {
            color: var(--primary);
            margin-bottom: 25px;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

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

        /* Request Cards */
        .requests-section {
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

        .requests-count {
            background: var(--primary);
            color: var(--white);
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .requests-grid {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .request-card {
            background: var(--white);
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.08);
            border: 1px solid #f0f0f0;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .request-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
        }

        .request-card::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 6px;
        }

        .urgency-critical::before { background: var(--critical); }
        .urgency-high::before { background: var(--high); }
        .urgency-medium::before { background: var(--medium); }
        .urgency-low::before { background: var(--low); }

        .request-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .patient-info {
            flex: 1;
        }

        .patient-name {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 5px;
        }

        .blood-type-badge {
            background: var(--primary);
            color: var(--white);
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: 700;
            font-size: 1rem;
            display: inline-block;
        }

        .urgency-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .critical { background: var(--critical); color: white; }
        .high { background: var(--high); color: white; }
        .medium { background: var(--medium); color: white; }
        .low { background: var(--low); color: white; }

        .request-details {
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

        .match-alert {
            background: #e8f5e9;
            border: 1px solid #c8e6c9;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .match-alert i {
            color: var(--success);
            font-size: 1.2rem;
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

        .stats-card {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: var(--white);
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }

        .stats-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .stats-description {
            font-size: 0.9rem;
            opacity: 0.9;
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

        .request-card {
            animation: fadeInUp 0.5s ease forwards;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <header class="header">
            <div class="container">
                <h1>Blood Requests</h1>
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
                        <!-- Create Request Form -->
                        <div class="form-container">
                            <h2><i class="fas fa-plus-circle"></i> Create New Blood Request</h2>
                            <form method="POST" action="">
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label for="patient_name">Patient Name</label>
                                        <input type="text" name="patient_name" id="patient_name" required placeholder="Enter patient's full name">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="blood_type">Blood Type Required</label>
                                        <select name="blood_type" id="blood_type" required>
                                            <option value="">Select Blood Type</option>
                                            <option value="A+">A+</option>
                                            <option value="A-">A-</option>
                                            <option value="B+">B+</option>
                                            <option value="B-">B-</option>
                                            <option value="AB+">AB+</option>
                                            <option value="AB-">AB-</option>
                                            <option value="O+">O+</option>
                                            <option value="O-">O-</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="units_required">Units Required</label>
                                        <input type="number" name="units_required" id="units_required" min="1" max="10" required placeholder="e.g., 2">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="urgency_level">Urgency Level</label>
                                        <select name="urgency_level" id="urgency_level" required>
                                            <option value="low">Low</option>
                                            <option value="medium" selected>Medium</option>
                                            <option value="high">High</option>
                                            <option value="critical">Critical</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="hospital_name">Hospital Name</label>
                                        <input type="text" name="hospital_name" id="hospital_name" required placeholder="Enter hospital name">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="required_date">Required Date</label>
                                        <input type="date" name="required_date" id="required_date" min="<?php echo date('Y-m-d'); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="contact_number">Contact Number</label>
                                    <input type="tel" name="contact_number" id="contact_number" required placeholder="Enter contact phone number">
                                </div>
                                
                                <div class="form-group">
                                    <label for="address">Hospital Address</label>
                                    <textarea name="address" id="address" rows="3" required placeholder="Full hospital address including city and zip code"></textarea>
                                </div>

                                <div class="form-group">
                                    <label for="additional_notes">Additional Notes (Optional)</label>
                                    <textarea name="additional_notes" id="additional_notes" rows="2" placeholder="Any special requirements or additional information"></textarea>
                                </div>
                                
                                <button type="submit" name="create_request" class="btn">
                                    <i class="fas fa-plus-circle"></i>
                                    Create Blood Request
                                </button>
                            </form>
                        </div>

                        <!-- Active Requests -->
                        <div class="requests-section">
                            <div class="section-header">
                                <h2>
                                    <i class="fas fa-tint"></i>
                                    Active Blood Requests
                                    <span class="requests-count"><?php echo count($requests); ?></span>
                                </h2>
                            </div>
                            
                            <?php if (empty($requests)): ?>
                                <div class="alert info">
                                    <i class="fas fa-info-circle"></i>
                                    No active blood requests at the moment. Create one above to get started.
                                </div>
                            <?php else: ?>
                                <div class="requests-grid">
                                    <?php foreach ($requests as $request): ?>
                                        <div class="request-card urgency-<?php echo $request['urgency_level']; ?>">
                                            <div class="request-header">
                                                <div class="patient-info">
                                                    <div class="patient-name">
                                                        <?php echo htmlspecialchars($request['patient_name']); ?>
                                                        <span class="blood-type-badge"><?php echo $request['blood_type']; ?></span>
                                                    </div>
                                                    <div style="margin-top: 5px;">
                                                        <span class="detail-value"><?php echo $request['units_required']; ?> units needed</span>
                                                    </div>
                                                </div>
                                                <span class="urgency-badge <?php echo $request['urgency_level']; ?>">
                                                    <i class="fas fa-<?php 
                                                        echo $request['urgency_level'] == 'critical' ? 'exclamation-triangle' : 
                                                             ($request['urgency_level'] == 'high' ? 'exclamation-circle' : 
                                                             ($request['urgency_level'] == 'medium' ? 'info-circle' : 'clock')); 
                                                    ?>"></i>
                                                    <?php echo ucfirst($request['urgency_level']); ?>
                                                </span>
                                            </div>
                                            
                                            <div class="request-details">
                                                <div class="detail-item">
                                                    <span class="detail-label">Hospital</span>
                                                    <span class="detail-value"><?php echo htmlspecialchars($request['hospital_name']); ?></span>
                                                </div>
                                                <div class="detail-item">
                                                    <span class="detail-label">Required By</span>
                                                    <span class="detail-value"><?php echo date('M j, Y', strtotime($request['required_date'])); ?></span>
                                                </div>
                                                <div class="detail-item">
                                                    <span class="detail-label">Contact</span>
                                                    <span class="detail-value"><?php echo htmlspecialchars($request['contact_number']); ?></span>
                                                </div>
                                                <div class="detail-item">
                                                    <span class="detail-label">Requested By</span>
                                                    <span class="detail-value"><?php echo htmlspecialchars($request['requester_name'] ?? 'Anonymous'); ?></span>
                                                </div>
                                            </div>
                                            
                                            <div class="detail-item">
                                                <span class="detail-label">Address</span>
                                                <span class="detail-value"><?php echo htmlspecialchars($request['address']); ?></span>
                                            </div>

                                            <?php if (!empty($request['additional_notes'])): ?>
                                                <div class="detail-item">
                                                    <span class="detail-label">Additional Notes</span>
                                                    <span class="detail-value"><?php echo htmlspecialchars($request['additional_notes']); ?></span>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="detail-item">
                                                <span class="detail-label">Posted</span>
                                                <span class="detail-value"><?php echo date('M j, Y g:i A', strtotime($request['created_at'])); ?></span>
                                            </div>
                                            
                                            <?php if ($_SESSION['blood_type'] == $request['blood_type']): ?>
                                                <div class="match-alert">
                                                    <i class="fas fa-check-circle"></i>
                                                    <div>
                                                        <strong>You can help!</strong> Your blood type matches this request.
                                                        <a href="find_donors.php?blood_type=<?php echo $request['blood_type']; ?>" class="btn" style="margin-left: 10px; padding: 5px 15px; font-size: 14px;">
                                                            Find Donors
                                                        </a>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Information Sidebar -->
                    <div class="info-sidebar">
                        <div class="info-card">
                            <h3><i class="fas fa-info-circle"></i> About Blood Requests</h3>
                            <p style="margin-bottom: 15px; font-size: 14px; color: var(--text-light);">
                                Create blood requests to find donors for patients in need. Your request will be visible to all registered donors.
                            </p>
                        </div>

                        <div class="info-card">
                            <div class="stats-card">
                                <div class="stats-number"><?php echo count($requests); ?></div>
                                <div class="stats-description">Active Requests</div>
                            </div>
                        </div>

                        <div class="info-card">
                            <h3><i class="fas fa-clock"></i> Urgency Levels</h3>
                            <ul>
                                <li>
                                    <i class="fas fa-exclamation-triangle" style="color: var(--critical);"></i>
                                    <span><strong>Critical:</strong> Immediate need (within hours)</span>
                                </li>
                                <li>
                                    <i class="fas fa-exclamation-circle" style="color: var(--high);"></i>
                                    <span><strong>High:</strong> Urgent need (within 24 hours)</span>
                                </li>
                                <li>
                                    <i class="fas fa-info-circle" style="color: var(--medium);"></i>
                                    <span><strong>Medium:</strong> Needed soon (2-3 days)</span>
                                </li>
                                <li>
                                    <i class="fas fa-clock" style="color: var(--low);"></i>
                                    <span><strong>Low:</strong> Planned requirement (1+ week)</span>
                                </li>
                            </ul>
                        </div>

                        <div class="info-card">
                            <h3><i class="fas fa-lightbulb"></i> Tips for Success</h3>
                            <ul>
                                <li>
                                    <i class="fas fa-check"></i>
                                    <span>Provide accurate contact information</span>
                                </li>
                                <li>
                                    <i class="fas fa-check"></i>
                                    <span>Set realistic urgency levels</span>
                                </li>
                                <li>
                                    <i class="fas fa-check"></i>
                                    <span>Include complete hospital details</span>
                                </li>
                                <li>
                                    <i class="fas fa-check"></i>
                                    <span>Update request status when fulfilled</span>
                                </li>
                            </ul>
                        </div>

                        <div class="info-card">
                            <h3><i class="fas fa-phone-alt"></i> Emergency Support</h3>
                            <p style="margin-bottom: 10px; font-size: 14px;">
                                For immediate critical needs, contact our 24/7 emergency line:
                            </p>
                            <ul>
                                <li><i class="fas fa-phone"></i> 1-800-BLOOD-HELP</li>
                                <li><i class="fas fa-envelope"></i> emergency@bloodlife.org</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Set minimum date to today
            const dateInput = document.getElementById('required_date');
            const today = new Date().toISOString().split('T')[0];
            dateInput.min = today;
            
            // Add urgency level color coding to form
            const urgencySelect = document.getElementById('urgency_level');
            const formContainer = document.querySelector('.form-container');
            
            function updateFormUrgencyColor() {
                const urgency = urgencySelect.value;
                const colors = {
                    'critical': '#d32f2f',
                    'high': '#f57c00', 
                    'medium': '#ffa000',
                    'low': '#388e3c'
                };
                
                formContainer.style.borderTop = `4px solid ${colors[urgency]}`;
            }
            
            urgencySelect.addEventListener('change', updateFormUrgencyColor);
            updateFormUrgencyColor(); // Initial call
            
            // Auto-suggest hospitals
            const hospitalInput = document.getElementById('hospital_name');
            const hospitals = [
                'City General Hospital',
                'Regional Medical Center', 
                'Community Health Hospital',
                'University Medical Center',
                'Memorial Hospital',
                'Saint Mary Hospital',
                'Children Medical Center'
            ];
            
            hospitalInput.addEventListener('input', function() {
                // In a real app, you would show suggestions
                console.log('Searching for hospital:', this.value);
            });
            
            // Show confirmation for critical requests
            const form = document.querySelector('form');
            form.addEventListener('submit', function(e) {
                const urgency = urgencySelect.value;
                const bloodType = document.getElementById('blood_type').value;
                
                if (urgency === 'critical') {
                    const confirmed = confirm('You are creating a CRITICAL blood request. This will send emergency alerts to all matching donors. Continue?');
                    if (!confirmed) {
                        e.preventDefault();
                    }
                }
            });
            
            // Quick blood type selector
            const bloodTypeSelect = document.getElementById('blood_type');
            const quickTypes = document.createElement('div');
            quickTypes.className = 'quick-types';
            quickTypes.style.marginTop = '10px';
            quickTypes.style.display = 'flex';
            quickTypes.style.gap = '8px';
            quickTypes.style.flexWrap = 'wrap';
            
            const commonTypes = ['O+', 'O-', 'A+', 'B+', 'AB+'];
            commonTypes.forEach(type => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'btn btn-outline';
                btn.style.padding = '5px 10px';
                btn.style.fontSize = '0.8rem';
                btn.textContent = type;
                btn.onclick = function() {
                    bloodTypeSelect.value = type;
                };
                quickTypes.appendChild(btn);
            });
            
            bloodTypeSelect.parentNode.appendChild(quickTypes);
        });
    </script>
</body>
</html>