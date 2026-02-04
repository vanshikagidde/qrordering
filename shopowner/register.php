<?php
// register.php
include "../config/db.php";
session_start();

$errors = [];
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shop_name   = trim($_POST['shop_name']   ?? '');
    $owner_name  = trim($_POST['owner_name']  ?? '');
    $email       = trim($_POST['email']       ?? '');
    $phone       = trim($_POST['phone']       ?? '');
    $password    = $_POST['password']    ?? '';
    $confirm     = $_POST['confirm']     ?? '';

    // Validation
    if (empty($shop_name)) {
        $errors[] = "Shop name is required";
    }
    if (empty($owner_name)) {
        $errors[] = "Owner name is required";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address";
    }
    if (!preg_match('/^[6-9]\d{9}$/', $phone)) {
        $errors[] = "Please enter a valid 10-digit Indian mobile number";
    }
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters";
    }
    if ($password !== $confirm) {
        $errors[] = "Passwords do not match";
    }

    if (empty($errors)) {
        // Check duplicates
        $stmt = $conn->prepare("SELECT id FROM shops WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = "This email is already registered";
        }
        $stmt->close();

        if (empty($errors)) {
            $stmt = $conn->prepare("SELECT id FROM shops WHERE phone = ? LIMIT 1");
            $stmt->bind_param("s", $phone);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $errors[] = "This phone number is already registered";
            }
            $stmt->close();
        }

        if (empty($errors)) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO shops (shop_name, owner_name, email, phone, password_hash, status) VALUES (?, ?, ?, ?, ?, 'pending')");
            $stmt->bind_param("sssss", $shop_name, $owner_name, $email, $phone, $hash);

            if ($stmt->execute()) {
                $_SESSION['register_success'] = true;
                header("Location: login.php?registered=1");
                exit;
            } else {
                $errors[] = "Registration failed. Please try again.";
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Your Shop - QR Ordering</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #F97316;
            --primary-light: #FB923C;
            --primary-dark: #EA580C;
            --secondary: #0F172A;
            --surface: #FFFFFF;
            --background: #F8FAFC;
            --text: #1E293B;
            --text-secondary: #64748B;
            --border: #E2E8F0;
            --success: #10B981;
            --error: #EF4444;
            
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1);
            --shadow-orange: 0 10px 40px -10px rgba(249, 115, 22, 0.5);
            
            --ease-spring: cubic-bezier(0.34, 1.56, 0.64, 1);
            --ease-smooth: cubic-bezier(0.4, 0, 0.2, 1);
            --ease-expo: cubic-bezier(0.16, 1, 0.3, 1);
            
            --radius: 20px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: linear-gradient(135deg, #F8FAFC 0%, #FFF7ED 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }

        /* Animated Background Shapes */
        .bg-shapes {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 0;
            overflow: hidden;
        }

        .shape {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.5;
            animation: float 20s infinite ease-in-out;
        }

        .shape-1 {
            width: 500px;
            height: 500px;
            background: linear-gradient(135deg, rgba(249, 115, 22, 0.4), rgba(251, 146, 60, 0.2));
            top: -200px;
            right: -100px;
            animation-delay: 0s;
        }

        .shape-2 {
            width: 400px;
            height: 400px;
            background: linear-gradient(135deg, rgba(255, 237, 213, 0.8), rgba(254, 215, 170, 0.3));
            bottom: -100px;
            left: -100px;
            animation-delay: -5s;
        }

        .shape-3 {
            width: 300px;
            height: 300px;
            background: linear-gradient(135deg, rgba(251, 146, 60, 0.3), rgba(249, 115, 22, 0.1));
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            animation-delay: -10s;
        }

        @keyframes float {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(30px, -30px) scale(1.1); }
            66% { transform: translate(-20px, 20px) scale(0.9); }
        }

        .register-container {
            display: flex;
            width: 90%;
            max-width: 1100px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow-xl), 0 0 0 1px rgba(255,255,255,0.5) inset;
            position: relative;
            z-index: 1;
            animation: slideUp 0.8s var(--ease-expo) backwards;
            border: 1px solid rgba(255,255,255,0.6);
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(40px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .brand-panel {
            flex: 1;
            background: linear-gradient(135deg, var(--secondary) 0%, #1e293b 100%);
            color: white;
            padding: 60px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .brand-panel::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23f97316' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            opacity: 0.5;
        }

        .form-panel {
            flex: 1.3;
            padding: 50px 60px;
            background: rgba(255, 255, 255, 0.8);
            position: relative;
        }

        .logo-section {
            text-align: center;
            margin-bottom: 40px;
            position: relative;
            z-index: 1;
        }

        .logo-icon {
            width: 90px;
            height: 90px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            border-radius: 24px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            margin-bottom: 24px;
            box-shadow: 0 20px 40px rgba(249, 115, 22, 0.3);
            animation: iconPulse 3s infinite;
            position: relative;
            overflow: hidden;
        }

        .logo-icon::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, transparent, rgba(255,255,255,0.3));
        }

        @keyframes iconPulse {
            0%, 100% { box-shadow: 0 20px 40px rgba(249, 115, 22, 0.3); transform: scale(1); }
            50% { box-shadow: 0 30px 60px rgba(249, 115, 22, 0.5); transform: scale(1.02); }
        }

        .brand-panel h1 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 16px;
            background: linear-gradient(135deg, #ffffff, #cbd5e1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .brand-panel p {
            font-size: 1.1rem;
            opacity: 0.9;
            line-height: 1.6;
            margin-bottom: 40px;
        }

        .feature-list {
            list-style: none;
            position: relative;
            z-index: 1;
        }

        .feature-list li {
            margin: 20px 0;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideInLeft 0.6s var(--ease-expo) backwards;
        }

        .feature-list li:nth-child(1) { animation-delay: 0.2s; }
        .feature-list li:nth-child(2) { animation-delay: 0.3s; }
        .feature-list li:nth-child(3) { animation-delay: 0.4s; }

        @keyframes slideInLeft {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .feature-list i {
            width: 32px;
            height: 32px;
            background: rgba(249, 115, 22, 0.2);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-light);
            font-size: 14px;
        }

        .form-header {
            margin-bottom: 32px;
        }

        .form-header h2 {
            font-size: 1.875rem;
            font-weight: 800;
            color: var(--secondary);
            margin-bottom: 8px;
        }

        .form-header p {
            color: var(--text-secondary);
            font-size: 1rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-group.full-width {
            grid-column: span 2;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 700;
            color: var(--text);
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-label.required::after {
            content: " *";
            color: var(--error);
        }

        .input-wrapper {
            position: relative;
        }

        .form-input {
            width: 100%;
            padding: 14px 16px 14px 48px;
            border: 2px solid var(--border);
            border-radius: 12px;
            font-size: 15px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            transition: all 0.3s var(--ease-spring);
            background: rgba(255,255,255,0.8);
        }

        .form-input:hover {
            border-color: var(--primary-light);
            transform: translateY(-2px);
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(249, 115, 22, 0.1), 0 4px 12px rgba(249, 115, 22, 0.1);
            transform: translateY(-2px);
        }

        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
            font-size: 18px;
            transition: all 0.3s var(--ease-spring);
        }

        .form-input:focus + .input-icon {
            color: var(--primary);
            transform: translateY(-50%) scale(1.1);
        }

        .error-messages {
            background: rgba(239, 68, 68, 0.1);
            border: 2px solid var(--error);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 24px;
            animation: shake 0.5s var(--ease-spring);
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }

        .error-messages h4 {
            color: var(--error);
            margin-bottom: 12px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .submit-btn {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border: none;
            width: 100%;
            padding: 16px;
            font-size: 16px;
            font-weight: 700;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s var(--ease-spring);
            margin-top: 24px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(249, 115, 22, 0.3);
        }

        .submit-btn::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, transparent, rgba(255,255,255,0.3), transparent);
            transform: translateX(-100%);
            transition: transform 0.6s;
        }

        .submit-btn:hover::before {
            transform: translateX(100%);
        }

        .submit-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(249, 115, 22, 0.4);
        }

        .submit-btn:active {
            transform: scale(0.98);
        }

        .login-link {
            text-align: center;
            margin-top: 24px;
            color: var(--text-secondary);
            font-size: 15px;
        }

        .login-link a {
            color: var(--primary);
            font-weight: 700;
            text-decoration: none;
            position: relative;
        }

        .login-link a::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--primary);
            transition: width 0.3s var(--ease-spring);
        }

        .login-link a:hover::after {
            width: 100%;
        }

        /* Success Modal */
        .success-modal {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(5px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            animation: fadeIn 0.3s ease;
        }

        .success-modal.show {
            display: flex;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .success-content {
            background: white;
            padding: 40px;
            border-radius: var(--radius);
            text-align: center;
            box-shadow: var(--shadow-xl);
            animation: scaleIn 0.4s var(--ease-spring);
            max-width: 400px;
        }

        @keyframes scaleIn {
            from { transform: scale(0.8); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }

        .success-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--success), #059669);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            color: white;
            margin: 0 auto 20px;
            animation: successPop 0.5s var(--ease-spring);
        }

        @keyframes successPop {
            0% { transform: scale(0); }
            80% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .register-container {
                flex-direction: column;
            }
            .brand-panel {
                padding: 40px;
                text-align: center;
            }
            .brand-panel h1 {
                font-size: 2rem;
            }
            .form-panel {
                padding: 40px;
            }
            .form-grid {
                grid-template-columns: 1fr;
            }
            .form-group.full-width {
                grid-column: span 1;
            }
        }

        @media (max-width: 480px) {
            .form-panel {
                padding: 30px 20px;
            }
            .brand-panel {
                padding: 30px 20px;
            }
            .logo-icon {
                width: 70px;
                height: 70px;
                font-size: 32px;
            }
        }

        /* Loading State */
        .loading {
            position: relative;
            pointer-events: none;
        }

        .loading::after {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            top: 50%;
            left: 50%;
            margin: -10px 0 0 -10px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- Background Shapes -->
    <div class="bg-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
    </div>

    <div class="register-container">
        <!-- Left - Branding -->
        <div class="brand-panel">
            <div class="logo-section">
                <div class="logo-icon">
                    <i class="fas fa-store"></i>
                </div>
                <h1>Join Our Network</h1>
                <p>Transform your restaurant with digital ordering and reach more customers than ever before.</p>
            </div>

            <ul class="feature-list">
                <li>
                    <i class="fas fa-chart-line"></i>
                    <span>Increase sales up to 40%</span>
                </li>
                <li>
                    <i class="fas fa-mobile-alt"></i>
                    <span>QR code table ordering</span>
                </li>
                <li>
                    <i class="fas fa-shield-alt"></i>
                    <span>Secure payment processing</span>
                </li>
            </ul>
        </div>

        <!-- Right - Form -->
        <div class="form-panel">
            <div class="form-header">
                <h2>Create Shop Account</h2>
                <p>Get started in less than 2 minutes</p>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="error-messages">
                    <h4><i class="fas fa-exclamation-circle"></i> Please fix the following:</h4>
                    <ul style="margin-left: 20px; line-height: 1.8; color: var(--error);">
                        <?php foreach ($errors as $err): ?>
                            <li><?= htmlspecialchars($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" novalidate id="registerForm">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label required" for="shop_name">Shop Name</label>
                        <div class="input-wrapper">
                            <input type="text" name="shop_name" id="shop_name" class="form-input"
                                   value="<?= htmlspecialchars($_POST['shop_name'] ?? '') ?>" required
                                   placeholder="Your Restaurant Name">
                            <i class="fas fa-store input-icon"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label required" for="owner_name">Owner Name</label>
                        <div class="input-wrapper">
                            <input type="text" name="owner_name" id="owner_name" class="form-input"
                                   value="<?= htmlspecialchars($_POST['owner_name'] ?? '') ?>" required
                                   placeholder="Full Name">
                            <i class="fas fa-user input-icon"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label required" for="email">Email Address</label>
                        <div class="input-wrapper">
                            <input type="email" name="email" id="email" class="form-input"
                                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required
                                   placeholder="owner@restaurant.com">
                            <i class="fas fa-envelope input-icon"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label required" for="phone">Phone Number</label>
                        <div class="input-wrapper">
                            <input type="tel" name="phone" id="phone" class="form-input"
                                   pattern="[6789][0-9]{9}" maxlength="10"
                                   value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" required
                                   placeholder="9876543210">
                            <i class="fas fa-phone input-icon"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label required" for="password">Password</label>
                        <div class="input-wrapper">
                            <input type="password" name="password" id="password" class="form-input" required
                                   minlength="8" placeholder="Min 8 characters">
                            <i class="fas fa-lock input-icon"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label required" for="confirm">Confirm Password</label>
                        <div class="input-wrapper">
                            <input type="password" name="confirm" id="confirm" class="form-input" required
                                   placeholder="Re-enter password">
                            <i class="fas fa-lock input-icon"></i>
                        </div>
                    </div>
                </div>

                <button type="submit" class="submit-btn" id="submitBtn">
                    <i class="fas fa-rocket" style="margin-right: 10px;"></i> Create Account
                </button>

                <div class="login-link">
                    Already have an account? <a href="login.php">Sign in here</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Form validation and loading state
        const form = document.getElementById('registerForm');
        const submitBtn = document.getElementById('submitBtn');

        form.addEventListener('submit', function(e) {
            submitBtn.classList.add('loading');
            submitBtn.innerHTML = '<span style="opacity: 0;">Creating...</span>';
        });

        // Input animations
        document.querySelectorAll('.form-input').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'scale(1.02)';
            });
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'scale(1)';
            });
        });

        // Password match validation
        const password = document.getElementById('password');
        const confirm = document.getElementById('confirm');

        function validatePasswordMatch() {
            if (confirm.value && password.value !== confirm.value) {
                confirm.style.borderColor = 'var(--error)';
            } else {
                confirm.style.borderColor = '';
            }
        }

        confirm.addEventListener('input', validatePasswordMatch);
        password.addEventListener('input', validatePasswordMatch);
    </script>
</body>
</html>