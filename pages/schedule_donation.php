<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $auth = new Auth();
        $conn = $auth->conn;
        
        $user_id = $_SESSION['user_id'];
        $appointment_date = $_POST['appointment_date'] . ' ' . $_POST['appointment_time'];
        $blood_bank_name = sanitizeInput($_POST['blood_bank_name']);
        $location = sanitizeInput($_POST['location']);
        $notes = sanitizeInput($_POST['notes']);
        
        $query = "INSERT INTO appointments (user_id, appointment_date, blood_bank_name, location, notes) 
                 VALUES (:user_id, :appointment_date, :blood_bank_name, :location, :notes)";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":appointment_date", $appointment_date);
        $stmt->bindParam(":blood_bank_name", $blood_bank_name);
        $stmt->bindParam(":location", $location);
        $stmt->bindParam(":notes", $notes);
        
        if ($stmt->execute()) {
            $success = "Appointment scheduled successfully!";
            
            // Schedule reminders
            require_once __DIR__ . '/../includes/email_service.php';
            require_once __DIR__ . '/../includes/sms_service.php';
            
            $emailService = new EmailService(new Database());
            $smsService = new SMSService(new Database());
            
            // Send immediate confirmation
            $emailService->sendDonationReminder(
                $_SESSION['user_email'],
                $_SESSION['user_name'],
                $appointment_date,
                $location
            );
            
            // Send SMS reminder if user has phone
            $user_stmt = $conn->prepare("SELECT phone FROM users WHERE id = ?");
            $user_stmt->execute([$_SESSION['user_id']]);
            $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && $user['phone']) {
                $smsService->sendAppointmentReminder(
                    $user['phone'],
                    $_SESSION['user_name'],
                    $appointment_date,
                    $blood_bank_name
                );
            }
            
            $success .= " Confirmation and reminders have been sent.";
        } else {
            $error = "Failed to schedule appointment.";
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Donation - Blood Donation System</title>
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

        .content-wrapper {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 30px;
            align-items: start;
        }

        .form-container {
            background: var(--white);
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        }

        .form-container h2 {
            color: var(--primary);
            margin-bottom: 25px;
            font-size: 1.8rem;
            display: flex;
            align-items: center;
            gap: 10px;
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
            width: 100%;
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

        /* Sidebar Styles */
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

        .eligibility-criteria {
            background: #e3f2fd;
            border-left: 4px solid var(--accent);
            padding: 15px;
            border-radius: 0 8px 8px 0;
        }

        .eligibility-criteria h4 {
            color: var(--accent);
            margin-bottom: 10px;
        }

        .stats-card {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: var(--white);
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }

        .stats-card h3 {
            font-size: 1.1rem;
            margin-bottom: 10px;
            opacity: 0.9;
        }

        .stats-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .stats-description {
            font-size: 0.9rem;
            opacity: 0.8;
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

        .form-container, .info-sidebar {
            animation: fadeInUp 0.5s ease forwards;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <header class="header">
            <div class="container">
                <h1>Schedule Blood Donation</h1>
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
                <div class="content-wrapper">
                    <div class="form-container">
                        <h2><i class="fas fa-calendar-plus"></i> Book Your Donation Appointment</h2>
                        
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

                        <form method="POST" action="">
                            <div class="form-group">
                                <label for="blood_bank_name">Blood Bank / Donation Center</label>
                                <select name="blood_bank_name" id="blood_bank_name" required>
                                    <option value="">Select Blood Bank</option>
                                    <option value="City Blood Bank">City Blood Bank</option>
                                    <option value="Regional Hospital">Regional Hospital</option>
                                    <option value="Community Health Center">Community Health Center</option>
                                    <option value="Red Cross Center">Red Cross Center</option>
                                    <option value="University Medical Center">University Medical Center</option>
                                    <option value="Memorial Hospital">Memorial Hospital</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="location">Location</label>
                                <input type="text" name="location" id="location" placeholder="Enter full address of the donation center" required>
                            </div>

                            <div class="form-group">
                                <label for="appointment_date">Appointment Date</label>
                                <input type="date" name="appointment_date" id="appointment_date" min="<?php echo date('Y-m-d'); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="appointment_time">Appointment Time</label>
                                <select name="appointment_time" id="appointment_time" required>
                                    <option value="">Select Time Slot</option>
                                    <option value="08:00">08:00 AM</option>
                                    <option value="09:00">09:00 AM</option>
                                    <option value="10:00">10:00 AM</option>
                                    <option value="11:00">11:00 AM</option>
                                    <option value="13:00">01:00 PM</option>
                                    <option value="14:00">02:00 PM</option>
                                    <option value="15:00">03:00 PM</option>
                                    <option value="16:00">04:00 PM</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="notes">Additional Notes (Optional)</label>
                                <textarea name="notes" id="notes" placeholder="Any special requirements, dietary restrictions, or notes for the staff..." rows="4"></textarea>
                            </div>

                            <button type="submit" class="btn">
                                <i class="fas fa-calendar-check"></i>
                                Schedule Appointment
                            </button>
                        </form>
                    </div>

                    <!-- Information Sidebar -->
                    <div class="info-sidebar">
                        <div class="info-card">
                            <h3><i class="fas fa-lightbulb"></i> Donation Tips</h3>
                            <ul>
                                <li>
                                    <i class="fas fa-check-circle"></i>
                                    <span>Drink plenty of water 24 hours before donation</span>
                                </li>
                                <li>
                                    <i class="fas fa-check-circle"></i>
                                    <span>Eat iron-rich foods and a healthy meal before appointment</span>
                                </li>
                                <li>
                                    <i class="fas fa-check-circle"></i>
                                    <span>Bring a valid government-issued ID</span>
                                </li>
                                <li>
                                    <i class="fas fa-check-circle"></i>
                                    <span>Wear comfortable clothing with sleeves that can be raised</span>
                                </li>
                                <li>
                                    <i class="fas fa-check-circle"></i>
                                    <span>Avoid heavy exercise immediately after donation</span>
                                </li>
                            </ul>
                        </div>

                        <div class="info-card">
                            <div class="eligibility-criteria">
                                <h4><i class="fas fa-clipboard-check"></i> Quick Eligibility Check</h4>
                                <ul>
                                    <li><i class="fas fa-user"></i> Age: 18-65 years</li>
                                    <li><i class="fas fa-weight"></i> Weight: At least 50 kg (110 lbs)</li>
                                    <li><i class="fas fa-heartbeat"></i> Good general health</li>
                                    <li><i class="fas fa-clock"></i> 8+ weeks since last donation</li>
                                </ul>
                            </div>
                        </div>

                        <div class="info-card">
                            <div class="stats-card">
                                <h3>Your Donation Impact</h3>
                                <div class="stats-number">3</div>
                                <div class="stats-description">Lives saved per donation</div>
                            </div>
                        </div>

                        <div class="info-card">
                            <h3><i class="fas fa-clock"></i> What to Expect</h3>
                            <ul>
                                <li><strong>10 min</strong> Registration & Health Screening</li>
                                <li><strong>10 min</strong> Actual Donation Process</li>
                                <li><strong>15 min</strong> Rest & Refreshments</li>
                                <li><strong>35 min</strong> Total Time</li>
                            </ul>
                        </div>

                        <div class="info-card">
                            <h3><i class="fas fa-phone-alt"></i> Need Help?</h3>
                            <p>Contact our support team:</p>
                            <ul>
                                <li><i class="fas fa-phone"></i> 1-800-DONATE</li>
                                <li><i class="fas fa-envelope"></i> support@bloodlife.org</li>
                                <li><i class="fas fa-comments"></i> Live Chat Available</li>
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
            const dateInput = document.getElementById('appointment_date');
            const today = new Date().toISOString().split('T')[0];
            dateInput.min = today;
            
            // Add real-time validation
            const form = document.querySelector('form');
            const inputs = form.querySelectorAll('input, select, textarea');
            
            inputs.forEach(input => {
                input.addEventListener('blur', function() {
                    if (this.value.trim() !== '' && this.checkValidity()) {
                        this.style.borderColor = '#4caf50';
                    } else if (this.value.trim() === '' && this.required) {
                        this.style.borderColor = '#f44336';
                    }
                });
                
                input.addEventListener('input', function() {
                    if (this.checkValidity()) {
                        this.style.borderColor = '#ddd';
                    }
                });
            });
            
            // Auto-fill location based on blood bank selection
            const bloodBankSelect = document.getElementById('blood_bank_name');
            const locationInput = document.getElementById('location');
            
            const bloodBankLocations = {
                'City Blood Bank': '123 Main Street, Downtown',
                'Regional Hospital': '456 Healthcare Ave, Medical District',
                'Community Health Center': '789 Wellness Blvd, Community Plaza',
                'Red Cross Center': '321 Humanitarian Road, Civic Center',
                'University Medical Center': '654 Campus Drive, University District',
                'Memorial Hospital': '987 Remembrance Lane, Memorial Park'
            };
            
            bloodBankSelect.addEventListener('change', function() {
                const selectedBank = this.value;
                if (bloodBankLocations[selectedBank]) {
                    locationInput.value = bloodBankLocations[selectedBank];
                }
            });
            
            // Show confirmation before submitting
            form.addEventListener('submit', function(e) {
                const confirmed = confirm('Are you sure you want to schedule this appointment? You will receive a confirmation email and reminder.');
                if (!confirmed) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>