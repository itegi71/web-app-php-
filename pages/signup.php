<?php
require_once __DIR__ . '/../includes/functions.php';

$error = null;

try {
    require_once __DIR__ . '/../includes/auth.php';
} catch (Exception $e) {
    $error = $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $auth = new Auth();
        
        $email = sanitizeInput($_POST['email']);
        $password = sanitizeInput($_POST['password']);
        $full_name = sanitizeInput($_POST['full_name']);
        $blood_type = sanitizeInput($_POST['blood_type']);
        $phone = sanitizeInput($_POST['phone']);
        
        $result = $auth->register($email, $password, $full_name, $blood_type, $phone);
        
        if ($result === true) {
            redirect('login.php?success=Registration successful! Please login.');
        } else {
            $error = $result;
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Blood Donation System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', Arial, sans-serif;
            background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), 
                        url('https://images.unsplash.com/photo-1582719478250-c89cae4dc85b') no-repeat center center/cover;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .form-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 450px;
            padding: 40px;
            animation: fadeIn 0.6s ease-out;
            position: relative;
            overflow: hidden;
        }

        .form-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #c33, #a22);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .form-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .form-header h2 {
            color: #c33;
            font-size: 28px;
            margin-bottom: 8px;
            font-weight: 700;
        }

        .form-header p {
            color: #666;
            font-size: 16px;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 14px;
        }

        .alert.error {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 16px;
            background: white;
            color: #333;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #c33;
            box-shadow: 0 0 0 3px rgba(195, 51, 51, 0.1);
        }

        .form-group input::placeholder {
            color: #999;
        }

        .btn {
            width: 100%;
            background: linear-gradient(135deg, #c33, #a22);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 16px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            position: relative;
            overflow: hidden;
        }

        .btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(195, 51, 51, 0.3);
        }

        .btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        .login-link {
            text-align: center;
            margin-top: 25px;
            color: #666;
            font-size: 14px;
        }

        .login-link a {
            color: #c33;
            text-decoration: none;
            font-weight: 600;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        .setup-instructions {
            margin-top: 25px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            text-align: center;
        }

        .setup-instructions small {
            color: #666;
            line-height: 1.4;
        }

        /* Password strength meter */
        .password-strength {
            height: 4px;
            border-radius: 2px;
            margin-top: 8px;
            background: #e1e5e9;
            transition: all 0.3s ease;
        }

        .strength-0 { background: linear-gradient(90deg, #dc3545 20%, #e1e5e9 80%); }
        .strength-1 { background: linear-gradient(90deg, #fd7e14 40%, #e1e5e9 60%); }
        .strength-2 { background: linear-gradient(90deg, #ffc107 60%, #e1e5e9 40%); }
        .strength-3 { background: linear-gradient(90deg, #20c997 80%, #e1e5e9 20%); }
        .strength-4 { background: #198754; }

        .strength-text {
            font-size: 12px;
            margin-top: 5px;
            text-align: right;
            color: #666;
        }

        .form-feedback {
            font-size: 12px;
            margin-top: 5px;
            padding: 4px 0;
            display: none;
        }

        .form-feedback.valid { 
            color: #198754; 
            font-weight: 500;
        }

        .form-feedback.invalid { 
            color: #dc3545; 
            font-weight: 500;
        }

        .real-time-check {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            margin-top: 5px;
            padding: 4px 0;
        }

        .check-icon { color: #198754; font-weight: bold; }
        .cross-icon { color: #dc3545; font-weight: bold; }

        /* Loading Overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            backdrop-filter: blur(5px);
        }

        .loading-overlay.active {
            display: flex;
        }

        .loading-content {
            text-align: center;
            color: white;
        }

        .loading-text {
            margin-top: 20px;
            font-size: 1.2rem;
            font-weight: 500;
        }

        /* Advanced Loader Styles */
        .loader {
            position: relative;
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: radial-gradient(
                circle,
                rgba(255, 255, 255, 0.1) 30%,
                transparent 70%
            );
            overflow: hidden;
            margin: 0 auto;
        }

        .loader::before {
            content: "";
            position: absolute;
            inset: 0;
            border-radius: 50%;
            border: 3px solid transparent;
            border-top-color: rgba(255, 255, 255, 0.8);
            animation: loader-spin 2s linear infinite;
        }

        .loader::after {
            content: "";
            position: absolute;
            inset: 10%;
            border-radius: 50%;
            background: conic-gradient(from 90deg, rgba(255, 255, 255, 0.3), transparent);
            filter: blur(2px);
            animation: loader-spin-reverse 1.5s linear infinite;
        }

        .loader__inner {
            position: absolute;
            top: 50%;
            left: 50%;
            width: 20px;
            height: 20px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            box-shadow: 0 0 15px rgba(255, 255, 255, 0.6);
            animation: loader-pulse 1s ease-in-out infinite;
        }

        .loader__orbit {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            animation: orbit-rotate 3s linear infinite;
        }

        .loader__dot {
            position: absolute;
            left: 50%;
            top: 50%;
            width: 6px;
            height: 6px;
            background: rgba(255, 255, 255, 0.8);
            border-radius: 50%;
        }

        .loader__dot:nth-child(1) {
            transform: rotate(0deg) translate(45px);
        }
        .loader__dot:nth-child(2) {
            transform: rotate(90deg) translate(45px);
        }
        .loader__dot:nth-child(3) {
            transform: rotate(180deg) translate(45px);
        }
        .loader__dot:nth-child(4) {
            transform: rotate(270deg) translate(45px);
        }

        /* Animations */
        @keyframes loader-spin {
            0% {
                transform: rotate(0deg);
            }
            100% {
                transform: rotate(360deg);
            }
        }

        @keyframes loader-spin-reverse {
            0% {
                transform: rotate(0deg);
            }
            100% {
                transform: rotate(-360deg);
            }
        }

        @keyframes loader-pulse {
            0%,
            100% {
                transform: translate(-50%, -50%) scale(1);
            }
            50% {
                transform: translate(-50%, -50%) scale(1.2);
            }
        }

        @keyframes orbit-rotate {
            0% {
                transform: rotate(0deg);
            }
            100% {
                transform: rotate(360deg);
            }
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-8px); }
            75% { transform: translateX(8px); }
        }

        .shake {
            animation: shake 0.5s ease-in-out;
        }

        /* Mobile responsiveness */
        @media (max-width: 480px) {
            .form-container {
                padding: 30px 20px;
            }
            
            body {
                padding: 10px;
            }
            
            .loader {
                width: 100px;
                height: 100px;
            }
            
            .loader__dot:nth-child(1),
            .loader__dot:nth-child(2),
            .loader__dot:nth-child(3),
            .loader__dot:nth-child(4) {
                transform: rotate(0deg) translate(35px);
            }
        }
    </style>
</head>
<body>
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-content">
            <div class="loader">
                <div class="loader__inner"></div>
                <div class="loader__orbit">
                    <div class="loader__dot"></div>
                    <div class="loader__dot"></div>
                    <div class="loader__dot"></div>
                    <div class="loader__dot"></div>
                </div>
            </div>
            <div class="loading-text" id="loadingText">Creating new account...</div>
        </div>
    </div>

    <div class="form-container">
        <div class="form-header">
            <h2><i class="fas fa-tint" style="color: #c33;"></i> Create Account</h2>
            <p>Join our blood donation community and save lives</p>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="alert error">
                <strong>Error:</strong> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" id="signupForm">
            <div class="form-group">
                <input type="text" name="full_name" id="full_name" placeholder="Full Name" required 
                       value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
                <div class="form-feedback" id="nameFeedback"></div>
            </div>
            
            <div class="form-group">
                <input type="email" name="email" id="email" placeholder="Email Address" required 
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                <div class="form-feedback" id="emailFeedback"></div>
                <div class="real-time-check" id="emailCheck" style="display: none;"></div>
            </div>
            
            <div class="form-group">
                <input type="password" name="password" id="password" placeholder="Password" required>
                <div class="password-strength" id="passwordStrength"></div>
                <div class="strength-text" id="strengthText">Enter a password</div>
                <div class="form-feedback" id="passwordFeedback"></div>
            </div>
            
            <div class="form-group">
                <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm Password" required>
                <div class="form-feedback" id="confirmPasswordFeedback"></div>
            </div>
            
            <div class="form-group">
                <select name="blood_type" id="blood_type" required>
                    <option value="">Select Blood Type</option>
                    <option value="A+" <?php echo (isset($_POST['blood_type']) && $_POST['blood_type'] == 'A+') ? 'selected' : ''; ?>>A+</option>
                    <option value="A-" <?php echo (isset($_POST['blood_type']) && $_POST['blood_type'] == 'A-') ? 'selected' : ''; ?>>A-</option>
                    <option value="B+" <?php echo (isset($_POST['blood_type']) && $_POST['blood_type'] == 'B+') ? 'selected' : ''; ?>>B+</option>
                    <option value="B-" <?php echo (isset($_POST['blood_type']) && $_POST['blood_type'] == 'B-') ? 'selected' : ''; ?>>B-</option>
                    <option value="AB+" <?php echo (isset($_POST['blood_type']) && $_POST['blood_type'] == 'AB+') ? 'selected' : ''; ?>>AB+</option>
                    <option value="AB-" <?php echo (isset($_POST['blood_type']) && $_POST['blood_type'] == 'AB-') ? 'selected' : ''; ?>>AB-</option>
                    <option value="O+" <?php echo (isset($_POST['blood_type']) && $_POST['blood_type'] == 'O+') ? 'selected' : ''; ?>>O+</option>
                    <option value="O-" <?php echo (isset($_POST['blood_type']) && $_POST['blood_type'] == 'O-') ? 'selected' : ''; ?>>O-</option>
                </select>
                <div class="form-feedback" id="bloodTypeFeedback"></div>
            </div>
            
            <div class="form-group">
                <input type="tel" name="phone" id="phone" placeholder="Phone Number" required 
                       value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                <div class="form-feedback" id="phoneFeedback"></div>
            </div>
            
            <button type="submit" class="btn" id="submitBtn">
                <span id="btnText">Create Account</span>
            </button>
        </form>
        
        <p class="login-link">
            Already have an account? <a href="login.php">Login here</a>
        </p>
        
        <div class="setup-instructions">
            <small>
                <strong>💡 Ready to save lives?</strong><br>
                Complete your registration to join our community of heroes
            </small>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('signupForm');
            const submitBtn = document.getElementById('submitBtn');
            const btnText = document.getElementById('btnText');
            const loadingOverlay = document.getElementById('loadingOverlay');
            const loadingText = document.getElementById('loadingText');
            
            // Elements
            const passwordInput = document.getElementById('password');
            const passwordStrength = document.getElementById('passwordStrength');
            const strengthText = document.getElementById('strengthText');
            const confirmPasswordInput = document.getElementById('confirm_password');
            const emailInput = document.getElementById('email');
            const phoneInput = document.getElementById('phone');
            const fullNameInput = document.getElementById('full_name');
            const bloodTypeSelect = document.getElementById('blood_type');

            // Loading functions
            function showLoading(text = 'Processing...') {
                loadingText.textContent = text;
                loadingOverlay.classList.add('active');
                document.body.style.overflow = 'hidden';
            }

            function hideLoading() {
                loadingOverlay.classList.remove('active');
                document.body.style.overflow = '';
            }

            // Password strength checker
            function checkPasswordStrength(password) {
                const strength = {
                    0: { text: "Very Weak", color: "#dc3545", score: 0 },
                    1: { text: "Weak", color: "#fd7e14", score: 1 },
                    2: { text: "Medium", color: "#ffc107", score: 2 },
                    3: { text: "Strong", color: "#20c997", score: 3 },
                    4: { text: "Very Strong", color: "#198754", score: 4 }
                };
                
                let score = 0;
                if (password.length >= 8) score++;
                if (password.match(/[a-z]/) && password.match(/[A-Z]/)) score++;
                if (password.match(/\d/)) score++;
                if (password.match(/[^a-zA-Z\d]/)) score++;
                
                return strength[score] || strength[0];
            }

            // Real-time password strength
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                
                if (password.length === 0) {
                    passwordStrength.className = 'password-strength';
                    strengthText.textContent = 'Enter a password';
                    strengthText.style.color = '#666';
                    return;
                }
                
                const strength = checkPasswordStrength(password);
                passwordStrength.className = 'password-strength strength-' + strength.score;
                strengthText.textContent = `Password strength: ${strength.text}`;
                strengthText.style.color = strength.color;
                
                validatePasswordMatch();
                checkFieldCompletion();
            });

            // Confirm password validation
            confirmPasswordInput.addEventListener('input', function() {
                validatePasswordMatch();
                checkFieldCompletion();
            });

            function validatePasswordMatch() {
                const password = passwordInput.value;
                const confirmPassword = confirmPasswordInput.value;
                const feedback = document.getElementById('confirmPasswordFeedback');
                
                if (confirmPassword.length > 0) {
                    if (password === confirmPassword) {
                        showFeedback(feedback, '✓ Passwords match', 'valid');
                    } else {
                        showFeedback(feedback, '✗ Passwords do not match', 'invalid');
                    }
                } else {
                    hideFeedback(feedback);
                }
            }

            // Email validation
            let emailCheckTimeout;
            emailInput.addEventListener('input', function() {
                const email = this.value;
                const feedback = document.getElementById('emailFeedback');
                const emailCheck = document.getElementById('emailCheck');
                
                clearTimeout(emailCheckTimeout);
                
                if (email.length > 0) {
                    if (validateEmail(email)) {
                        showFeedback(feedback, '✓ Valid email format', 'valid');
                        
                        // Simulate email availability check
                        emailCheckTimeout = setTimeout(() => {
                            emailCheck.innerHTML = '<span class="check-icon">⏳</span> Checking availability...';
                            emailCheck.style.display = 'flex';
                            
                            setTimeout(() => {
                                // Simulate API response
                                const isAvailable = Math.random() > 0.3;
                                emailCheck.innerHTML = isAvailable ? 
                                    '<span class="check-icon">✓</span> Email is available' :
                                    '<span class="cross-icon">✗</span> Email already registered';
                            }, 1000);
                        }, 800);
                        
                    } else {
                        showFeedback(feedback, '✗ Please enter a valid email', 'invalid');
                        emailCheck.style.display = 'none';
                    }
                } else {
                    hideFeedback(feedback);
                    emailCheck.style.display = 'none';
                }
                checkFieldCompletion();
            });

            function validateEmail(email) {
                const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return re.test(email);
            }

            // Phone validation
            phoneInput.addEventListener('input', function() {
                const phone = this.value;
                const feedback = document.getElementById('phoneFeedback');
                
                if (phone.length > 0) {
                    if (validatePhone(phone)) {
                        showFeedback(feedback, '✓ Valid phone number', 'valid');
                    } else {
                        showFeedback(feedback, '✗ Please enter a valid phone number', 'invalid');
                    }
                } else {
                    hideFeedback(feedback);
                }
                checkFieldCompletion();
            });

            function validatePhone(phone) {
                const re = /^[\+]?[1-9][\d]{0,15}$/;
                return re.test(phone.replace(/\s/g, ''));
            }

            // Name validation
            fullNameInput.addEventListener('input', function() {
                const name = this.value;
                const feedback = document.getElementById('nameFeedback');
                
                if (name.length > 0) {
                    if (name.length >= 2) {
                        showFeedback(feedback, '✓ Valid name', 'valid');
                    } else {
                        showFeedback(feedback, '✗ Name must be at least 2 characters', 'invalid');
                    }
                } else {
                    hideFeedback(feedback);
                }
                checkFieldCompletion();
            });

            // Blood type validation
            bloodTypeSelect.addEventListener('change', function() {
                const feedback = document.getElementById('bloodTypeFeedback');
                if (this.value) {
                    showFeedback(feedback, '✓ Blood type selected', 'valid');
                } else {
                    hideFeedback(feedback);
                }
                checkFieldCompletion();
            });

            // Check if all fields are filled and valid
            function checkFieldCompletion() {
                const email = emailInput.value.trim();
                const password = passwordInput.value.trim();
                const confirmPassword = confirmPasswordInput.value.trim();
                const fullName = fullNameInput.value.trim();
                const phone = phoneInput.value.trim();
                const bloodType = bloodTypeSelect.value;
                
                const isComplete = email && 
                                 password && 
                                 confirmPassword && 
                                 fullName && 
                                 phone && 
                                 bloodType &&
                                 validateEmail(email) &&
                                 password.length >= 6 &&
                                 password === confirmPassword &&
                                 fullName.length >= 2 &&
                                 validatePhone(phone);
                
                if (isComplete) {
                    submitBtn.style.opacity = '1';
                    submitBtn.disabled = false;
                } else {
                    submitBtn.style.opacity = '0.7';
                    submitBtn.disabled = true;
                }
            }

            // Form submission
            form.addEventListener('submit', function(e) {
                if (!validateForm()) {
                    e.preventDefault();
                    form.classList.add('shake');
                    setTimeout(() => form.classList.remove('shake'), 500);
                } else {
                    e.preventDefault(); // Prevent default for demo
                    showLoading('Creating new account...');
                    
                    // Simulate API call
                    setTimeout(() => {
                        // In real implementation, remove this timeout and let the form submit naturally
                        hideLoading();
                        form.submit(); // This would actually submit the form in production
                    }, 3000);
                }
            });

            function validateForm() {
                let isValid = true;
                const fields = [
                    fullNameInput, emailInput, passwordInput, 
                    confirmPasswordInput, bloodTypeSelect, phoneInput
                ];

                fields.forEach(field => {
                    if (!field.value.trim()) {
                        isValid = false;
                        highlightError(field);
                    } else {
                        removeErrorHighlight(field);
                    }
                });

                if (passwordInput.value !== confirmPasswordInput.value) {
                    isValid = false;
                    highlightError(confirmPasswordInput);
                }

                if (!validateEmail(emailInput.value)) {
                    isValid = false;
                    highlightError(emailInput);
                }

                if (!validatePhone(phoneInput.value)) {
                    isValid = false;
                    highlightError(phoneInput);
                }

                if (fullNameInput.value.length < 2) {
                    isValid = false;
                    highlightError(fullNameInput);
                }

                return isValid;
            }

            function highlightError(field) {
                field.style.borderColor = '#dc3545';
                field.style.boxShadow = '0 0 0 3px rgba(220, 53, 69, 0.1)';
            }

            function removeErrorHighlight(field) {
                field.style.borderColor = '#e1e5e9';
                field.style.boxShadow = 'none';
            }

            function showFeedback(element, message, type) {
                element.textContent = message;
                element.className = 'form-feedback ' + type;
                element.style.display = 'block';
            }

            function hideFeedback(element) {
                element.style.display = 'none';
            }

            // Initial check
            checkFieldCompletion();
        });
    </script>
</body>
</html>