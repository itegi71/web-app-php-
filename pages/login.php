<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (isLoggedIn()) {
    redirect('home.php');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $auth = new Auth();
    
    $email = sanitizeInput($_POST['email']);
    $password = sanitizeInput($_POST['password']);
    
    if ($auth->login($email, $password)) {
        redirect('home.php');
    } else {
        $error = "Invalid email or password!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Blood Donation System</title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            width: 100%;
            max-width: 450px;
        }

        .form-container {
            background: var(--white);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
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
            background: linear-gradient(90deg, var(--primary), var(--primary-dark));
        }

        .form-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .form-header h2 {
            color: var(--primary);
            font-size: 2rem;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .form-header p {
            color: var(--text-light);
            font-size: 1rem;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideDown 0.5s ease;
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
            margin-bottom: 25px;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text);
            font-size: 0.95rem;
        }

        .input-with-icon {
            position: relative;
        }

        .input-with-icon i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            font-size: 1.1rem;
        }

        input {
            width: 100%;
            padding: 15px 15px 15px 45px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: var(--white);
            color: var(--text);
        }

        input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(198, 40, 40, 0.1);
            background: var(--white);
        }

        input::placeholder {
            color: #aaa;
        }

        .form-feedback {
            font-size: 0.85rem;
            margin-top: 8px;
            display: none;
            padding: 5px 10px;
            border-radius: 5px;
            font-weight: 500;
        }

        .form-feedback.valid {
            background: #e8f5e9;
            color: var(--success);
            display: block;
        }

        .form-feedback.invalid {
            background: #ffeaea;
            color: var(--danger);
            display: block;
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-light);
            cursor: pointer;
            font-size: 1.1rem;
            transition: color 0.3s ease;
        }

        .password-toggle:hover {
            color: var(--primary);
        }

        .btn {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: var(--white);
            border: none;
            padding: 16px 25px;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            position: relative;
            overflow: hidden;
        }

        .btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(198, 40, 40, 0.3);
        }

        .btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        .btn.loading {
            pointer-events: none;
        }

        .btn.loading .btn-text {
            opacity: 0;
        }

        .signup-link {
            text-align: center;
            margin-top: 25px;
            color: var(--text-light);
            font-size: 0.95rem;
        }

        .signup-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .signup-link a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        .forgot-password {
            text-align: center;
            margin-top: 15px;
        }

        .forgot-password a {
            color: var(--text-light);
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }

        .forgot-password a:hover {
            color: var(--primary);
        }

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
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

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
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .shake {
            animation: shake 0.5s ease-in-out;
        }

        .fade-in {
            animation: fadeIn 0.8s ease;
        }

        /* Responsive Design */
        @media (max-width: 480px) {
            .form-container {
                padding: 30px 25px;
            }
            
            .form-header h2 {
                font-size: 1.7rem;
            }
            
            input {
                padding: 12px 12px 12px 40px;
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
            <div class="loading-text" id="loadingText">Logging you in...</div>
        </div>
    </div>

    <div class="container">
        <div class="form-container fade-in">
            <div class="form-header">
                <h2><i class="fas fa-tint" style="color: var(--primary);"></i> Welcome Back</h2>
                <p>Login to your blood donation account</p>
            </div>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="alert success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($_GET['success']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" id="loginForm">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <div class="input-with-icon">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" id="email" placeholder="Enter your email address" required 
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                    <div class="form-feedback" id="emailFeedback"></div>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-with-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" id="password" placeholder="Enter your password" required>
                        <button type="button" class="password-toggle" id="passwordToggle">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="form-feedback" id="passwordFeedback"></div>
                </div>
                
                <button type="submit" class="btn" id="submitBtn">
                    <span class="btn-text" id="btnText">Login to Account</span>
                </button>
            </form>
            
            <div class="forgot-password">
                <a href="forgot_password.php">Forgot your password?</a>
            </div>
            
            <p class="signup-link">
                Don't have an account? <a href="signup.php">Sign up here</a>
            </p>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('loginForm');
            const submitBtn = document.getElementById('submitBtn');
            const btnText = document.getElementById('btnText');
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');
            const passwordToggle = document.getElementById('passwordToggle');
            const loadingOverlay = document.getElementById('loadingOverlay');
            const loadingText = document.getElementById('loadingText');

            // Password toggle functionality
            passwordToggle.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
            });

            // Email validation
            emailInput.addEventListener('input', function() {
                const email = this.value;
                const feedback = document.getElementById('emailFeedback');
                
                if (email.length > 0) {
                    if (validateEmail(email)) {
                        showFeedback(feedback, '✓ Valid email format', 'valid');
                        removeErrorHighlight(emailInput);
                    } else {
                        showFeedback(feedback, '✗ Please enter a valid email', 'invalid');
                        highlightError(emailInput);
                    }
                } else {
                    hideFeedback(feedback);
                    removeErrorHighlight(emailInput);
                }
                checkFieldCompletion();
            });

            // Password validation
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                const feedback = document.getElementById('passwordFeedback');
                
                if (password.length > 0) {
                    if (password.length >= 6) {
                        showFeedback(feedback, '✓ Password looks good', 'valid');
                        removeErrorHighlight(passwordInput);
                    } else {
                        showFeedback(feedback, '✗ Password must be at least 6 characters', 'invalid');
                        highlightError(passwordInput);
                    }
                } else {
                    hideFeedback(feedback);
                    removeErrorHighlight(passwordInput);
                }
                checkFieldCompletion();
            });

            // Check if both fields are filled
            function checkFieldCompletion() {
                const email = emailInput.value.trim();
                const password = passwordInput.value.trim();
                
                if (email && password && validateEmail(email) && password.length >= 6) {
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
                    shakeForm();
                } else {
                    e.preventDefault(); // Prevent default for demo
                    showLoading('Logging you in...');
                    
                    // Simulate API call
                    setTimeout(() => {
                        // In real implementation, remove this timeout and let the form submit naturally
                        hideLoading();
                        form.submit(); // This would actually submit the form in production
                    }, 3000);
                }
            });

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

            function validateForm() {
                let isValid = true;
                
                if (!emailInput.value.trim() || !validateEmail(emailInput.value)) {
                    isValid = false;
                    highlightError(emailInput);
                }
                
                if (!passwordInput.value.trim() || passwordInput.value.length < 6) {
                    isValid = false;
                    highlightError(passwordInput);
                }
                
                return isValid;
            }

            function validateEmail(email) {
                const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return re.test(email);
            }

            function highlightError(field) {
                field.style.borderColor = '#dc3545';
                field.style.boxShadow = '0 0 0 3px rgba(220, 53, 69, 0.1)';
            }

            function removeErrorHighlight(field) {
                field.style.borderColor = '#e0e0e0';
                field.style.boxShadow = 'none';
            }

            function showFeedback(element, message, type) {
                element.textContent = message;
                element.className = 'form-feedback ' + type;
            }

            function hideFeedback(element) {
                element.style.display = 'none';
            }

            function shakeForm() {
                form.classList.add('shake');
                setTimeout(() => {
                    form.classList.remove('shake');
                }, 500);
            }

            // Auto-focus email field
            emailInput.focus();

            // Initial check
            checkFieldCompletion();
        });
    </script>
</body>
</html>