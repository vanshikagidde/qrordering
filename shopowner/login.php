<?php
include "../config/db.php";
session_start();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shop_name = mysqli_real_escape_string($conn, $_POST['shop_name']);
    $owner_name = mysqli_real_escape_string($conn, $_POST['owner_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm'];

    if (!$shop_name) $errors[] = "Shop name required";
    if (!$owner_name) $errors[] = "Owner name required";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email required";
    if (!preg_match('/^[0-9]{10}$/', $phone)) $errors[] = "Valid phone required";
    if (strlen($password) < 6) $errors[] = "Password min 6 chars";
    if ($password !== $confirm) $errors[] = "Passwords do not match";

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        mysqli_query($conn,"
            INSERT INTO shops (shop_name, owner_name, owner_email, owner_phone, password, status)
            VALUES ('$shop_name','$owner_name','$email','$phone','$hash','pending')
        ");

        header("Location: login.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register Shop</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    :root {
        --primary: #FF6B6B;
        --primary-light: #FF8E8E;
        --secondary: #4ECDC4;
        --accent: #FFD166;
        --dark: #2D3047;
        --light: #F7F9FC;
        --gray: #E2E8F0;
        --text: #333333;
        --success: #4CAF50;
        --error: #FF5252;
        --shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        --shadow-hover: 0 15px 40px rgba(0, 0, 0, 0.12);
        --gradient: linear-gradient(135deg, #FF6B6B 0%, #FF8E8E 100%);
        --gradient-dark: linear-gradient(135deg, #2D3047 0%, #3D4166 100%);
        --gradient-accent: linear-gradient(135deg, #FFD166 0%, #FFE8A0 100%);
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    html {
        height: 100%;
        width: 100%;
    }

    body {
        font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: var(--text);
        min-height: 100vh;
        width: 100%;
        overflow-x: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        padding: 0;
    }

    /* Animated Background */
    .animated-bg {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: -1;
        overflow: hidden;
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.9) 0%, rgba(118, 75, 162, 0.9) 100%);
    }

    .bg-circle {
        position: absolute;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
        animation: float 25s infinite ease-in-out;
    }

    .bg-1 {
        width: 600px;
        height: 600px;
        top: -300px;
        right: -200px;
        background: radial-gradient(circle, rgba(255, 107, 107, 0.15) 0%, transparent 70%);
        animation-delay: 0s;
    }

    .bg-2 {
        width: 500px;
        height: 500px;
        bottom: -200px;
        left: -150px;
        background: radial-gradient(circle, rgba(78, 205, 196, 0.15) 0%, transparent 70%);
        animation-delay: 5s;
        animation-duration: 30s;
    }

    .bg-3 {
        width: 400px;
        height: 400px;
        top: 50%;
        left: 80%;
        background: radial-gradient(circle, rgba(255, 209, 102, 0.15) 0%, transparent 70%);
        animation-delay: 10s;
        animation-duration: 20s;
    }

    @keyframes float {
        0%, 100% { transform: translateY(0) rotate(0deg) scale(1); }
        33% { transform: translateY(-40px) rotate(120deg) scale(1.1); }
        66% { transform: translateY(30px) rotate(240deg) scale(0.95); }
    }

    /* Main Container */
    .register-container {
        display: flex;
        width: 90%;
        max-width: 1200px;
        min-height: 85vh;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(20px);
        border-radius: 30px;
        overflow: hidden;
        box-shadow: var(--shadow-hover);
        animation: fadeIn 0.8s ease-out;
        margin: 20px;
        position: relative;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(30px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Left Panel - Branding */
    .brand-panel {
        flex: 1;
        background: var(--gradient-dark);
        padding: 60px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        color: white;
        position: relative;
        overflow: hidden;
        min-width: 400px;
    }

    .brand-panel::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z' fill='%23ffffff' fill-opacity='0.05' fill-rule='evenodd'/%3E%3C/svg%3E");
        opacity: 0.3;
    }

    .brand-logo {
        text-align: center;
        margin-bottom: 50px;
    }

    .logo-icon {
        width: 120px;
        height: 120px;
        background: var(--gradient);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 25px;
        font-size: 48px;
        animation: pulse 3s infinite;
        box-shadow: 0 15px 35px rgba(255, 107, 107, 0.3);
    }

    @keyframes pulse {
        0%, 100% { transform: scale(1) rotate(0deg); }
        25% { transform: scale(1.05) rotate(5deg); }
        50% { transform: scale(1.1) rotate(0deg); }
        75% { transform: scale(1.05) rotate(-5deg); }
    }

    .brand-panel h1 {
        font-size: 3.5rem;
        font-weight: 800;
        margin-bottom: 20px;
        text-align: center;
        background: linear-gradient(to right, #ffffff, #FFD166);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        letter-spacing: -1px;
        line-height: 1.2;
    }

    .brand-panel p {
        font-size: 1.3rem;
        opacity: 0.9;
        text-align: center;
        margin-bottom: 40px;
        line-height: 1.6;
    }

    .features-list {
        list-style: none;
        margin-top: 40px;
    }

    .features-list li {
        display: flex;
        align-items: center;
        margin-bottom: 25px;
        font-size: 1.1rem;
        opacity: 0.9;
    }

    .features-list i {
        width: 40px;
        height: 40px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 15px;
        flex-shrink: 0;
    }

    /* Right Panel - Form */
    .form-panel {
        flex: 1.2;
        padding: 60px 70px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        background: white;
        position: relative;
        min-width: 500px;
    }

    .form-panel::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 6px;
        background: var(--gradient);
    }

    .form-header {
        margin-bottom: 40px;
    }

    .form-header h2 {
        font-size: 2.8rem;
        font-weight: 700;
        color: var(--dark);
        margin-bottom: 15px;
        position: relative;
        display: inline-block;
    }

    .form-header h2::after {
        content: '';
        position: absolute;
        bottom: -8px;
        left: 0;
        width: 80px;
        height: 4px;
        background: var(--gradient);
        border-radius: 2px;
    }

    .form-header p {
        color: var(--text);
        font-size: 1.2rem;
        opacity: 0.8;
    }

    /* Form Styles */
    .form-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 25px;
        margin-bottom: 30px;
    }

    .form-group {
        position: relative;
    }

    .form-group.full-width {
        grid-column: span 2;
    }

    .form-label {
        display: block;
        margin-bottom: 10px;
        font-weight: 600;
        color: var(--dark);
        font-size: 1rem;
    }

    .form-label.required::after {
        content: ' *';
        color: var(--error);
    }

    .form-input {
        width: 100%;
        padding: 18px 20px;
        border: 2px solid var(--gray);
        border-radius: 12px;
        font-size: 1.1rem;
        font-family: inherit;
        transition: all 0.3s ease;
        background: white;
    }

    .form-input:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 4px rgba(255, 107, 107, 0.1);
        transform: translateY(-2px);
    }

    .form-input.error {
        border-color: var(--error);
        background: rgba(255, 82, 82, 0.03);
    }

    .input-icon {
        position: absolute;
        right: 20px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text);
        font-size: 1.2rem;
    }

    .form-help {
        font-size: 0.9rem;
        color: var(--text);
        margin-top: 8px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .form-help i {
        color: var(--primary);
    }

    /* Error Messages */
    .error-messages {
        background: rgba(255, 82, 82, 0.1);
        border: 2px solid var(--error);
        border-radius: 12px;
        padding: 20px 25px;
        margin-bottom: 30px;
        animation: slideDown 0.5s ease-out;
    }

    @keyframes slideDown {
        from { transform: translateY(-20px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }

    .error-messages h4 {
        color: var(--error);
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 1.1rem;
    }

    .error-messages ul {
        list-style: none;
        padding-left: 0;
    }

    .error-messages li {
        color: var(--error);
        margin-bottom: 6px;
        padding-left: 25px;
        position: relative;
        font-size: 0.95rem;
    }

    .error-messages li:before {
        content: '•';
        color: var(--error);
        position: absolute;
        left: 10px;
        font-size: 1.2rem;
    }

    /* Legal Notice */
    .legal-notice {
        background: linear-gradient(135deg, #f8fafc 0%, #edf2f7 100%);
        border-radius: 16px;
        padding: 25px 30px;
        margin: 30px 0;
        border: 2px solid var(--gray);
        position: relative;
        overflow: hidden;
    }

    .legal-notice::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 8px;
        height: 100%;
        background: var(--gradient);
    }

    .legal-notice h4 {
        color: var(--dark);
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 15px;
        font-size: 1.3rem;
    }

    .legal-notice h4 i {
        color: var(--primary);
        background: white;
        width: 45px;
        height: 45px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .legal-notice p {
        color: var(--text);
        font-size: 1rem;
        line-height: 1.6;
        margin-bottom: 15px;
    }

    .requirements-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
        margin-top: 20px;
    }

    .requirement-item {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 12px;
        background: rgba(255, 255, 255, 0.7);
        border-radius: 10px;
        transition: transform 0.3s ease;
    }

    .requirement-item:hover {
        transform: translateX(5px);
    }

    .requirement-item i {
        color: var(--success);
        font-size: 1.2rem;
        margin-top: 2px;
        flex-shrink: 0;
    }

    .requirement-text {
        font-size: 0.95rem;
        color: var(--text);
        line-height: 1.4;
    }

    /* Checkbox Styles */
    .checkbox-group {
        display: flex;
        align-items: flex-start;
        gap: 15px;
        margin-bottom: 20px;
        padding: 15px;
        background: white;
        border-radius: 12px;
        border: 2px solid var(--gray);
        transition: all 0.3s ease;
    }

    .checkbox-group:hover {
        border-color: var(--primary);
        box-shadow: 0 5px 15px rgba(255, 107, 107, 0.1);
    }

    .checkbox-input {
        margin-top: 3px;
        width: 22px;
        height: 22px;
        accent-color: var(--primary);
        flex-shrink: 0;
    }

    .checkbox-label {
        font-size: 1rem;
        color: var(--text);
        line-height: 1.5;
        cursor: pointer;
    }

    .checkbox-label a {
        color: var(--primary);
        text-decoration: none;
        font-weight: 600;
        position: relative;
    }

    .checkbox-label a:hover {
        text-decoration: underline;
    }

    /* Submit Button */
    .submit-section {
        margin-top: 30px;
        position: relative;
    }

    .submit-btn {
        background: var(--gradient);
        color: white;
        border: none;
        border-radius: 15px;
        padding: 22px 40px;
        font-size: 1.3rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 15px;
        box-shadow: 0 8px 25px rgba(255, 107, 107, 0.3);
        position: relative;
        overflow: hidden;
        letter-spacing: 0.5px;
    }

    .submit-btn::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
        transition: left 0.7s;
    }

    .submit-btn:hover::before {
        left: 100%;
    }

    .submit-btn:hover {
        transform: translateY(-5px) scale(1.02);
        box-shadow: 0 15px 35px rgba(255, 107, 107, 0.4);
    }

    .submit-btn:active {
        transform: translateY(-2px);
    }

    .submit-btn i {
        font-size: 1.4rem;
        transition: transform 0.3s ease;
    }

    .submit-btn:hover i {
        transform: translateX(5px) rotate(10deg);
    }

    /* Already have account */
    .login-link {
        text-align: center;
        margin-top: 25px;
        padding-top: 25px;
        border-top: 2px solid var(--gray);
        color: var(--text);
        font-size: 1.1rem;
    }

    .login-link a {
        color: var(--primary);
        text-decoration: none;
        font-weight: 700;
        position: relative;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s ease;
    }

    .login-link a:hover {
        gap: 12px;
    }

    .login-link a::after {
        content: '→';
        opacity: 0;
        transition: opacity 0.3s ease, transform 0.3s ease;
        transform: translateX(-10px);
    }

    .login-link a:hover::after {
        opacity: 1;
        transform: translateX(0);
    }

    /* Progress Indicator */
    .progress-indicator {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 30px;
        margin-bottom: 40px;
    }

    .progress-step {
        display: flex;
        flex-direction: column;
        align-items: center;
        position: relative;
    }

    .progress-step:not(:last-child)::after {
        content: '';
        position: absolute;
        top: 25px;
        right: -55px;
        width: 50px;
        height: 2px;
        background: var(--gray);
    }

    .step-circle {
        width: 50px;
        height: 50px;
        border: 3px solid var(--gray);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        color: var(--text);
        background: white;
        margin-bottom: 10px;
        transition: all 0.3s ease;
        position: relative;
        z-index: 2;
    }

    .progress-step.active .step-circle {
        border-color: var(--primary);
        background: var(--gradient);
        color: white;
        box-shadow: 0 0 0 8px rgba(255, 107, 107, 0.1);
    }

    .step-label {
        font-size: 0.9rem;
        color: var(--text);
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .progress-step.active .step-label {
        color: var(--primary);
        font-weight: 600;
    }

    /* Responsive */
    @media (max-width: 1024px) {
        .register-container {
            flex-direction: column;
            width: 95%;
            min-height: auto;
        }

        .brand-panel, .form-panel {
            min-width: 100%;
            padding: 40px;
        }

        .brand-panel {
            padding-top: 60px;
        }

        .logo-icon {
            width: 100px;
            height: 100px;
            font-size: 40px;
        }

        .brand-panel h1 {
            font-size: 2.8rem;
        }
    }

    @media (max-width: 768px) {
        .form-grid {
            grid-template-columns: 1fr;
        }

        .form-group.full-width {
            grid-column: span 1;
        }

        .requirements-grid {
            grid-template-columns: 1fr;
        }

        .brand-panel h1 {
            font-size: 2.2rem;
        }

        .brand-panel p {
            font-size: 1.1rem;
        }

        .form-panel {
            padding: 40px 30px;
        }

        .form-header h2 {
            font-size: 2.2rem;
        }

        .progress-indicator {
            gap: 20px;
        }

        .progress-step:not(:last-child)::after {
            right: -45px;
            width: 40px;
        }
    }

    @media (max-width: 480px) {
        .register-container {
            margin: 10px;
            width: 95%;
            border-radius: 20px;
        }

        .brand-panel, .form-panel {
            padding: 30px 20px;
        }

        .logo-icon {
            width: 80px;
            height: 80px;
            font-size: 32px;
        }

        .brand-panel h1 {
            font-size: 1.8rem;
        }

        .form-header h2 {
            font-size: 1.8rem;
        }

        .form-input {
            padding: 15px;
        }

        .submit-btn {
            padding: 18px 30px;
            font-size: 1.1rem;
        }
    }

    /* Form Validation Animation */
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
        20%, 40%, 60%, 80% { transform: translateX(5px); }
    }

    .form-input.error {
        animation: shake 0.5s ease-in-out;
    }

    /* Success Animation */
    .success-animation {
        display: none;
        text-align: center;
        padding: 40px;
    }

    .success-animation.active {
        display: block;
        animation: fadeIn 0.5s ease-out;
    }

    .success-icon {
        width: 100px;
        height: 100px;
        background: var(--gradient-success);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 25px;
        color: white;
        font-size: 48px;
        animation: successPop 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    @keyframes successPop {
        0% { transform: scale(0); opacity: 0; }
        70% { transform: scale(1.1); }
        100% { transform: scale(1); opacity: 1; }
    }
</style>
</head>
<body>
    <!-- Animated Background -->
    <div class="animated-bg">
        <div class="bg-circle bg-1"></div>
        <div class="bg-circle bg-2"></div>
        <div class="bg-circle bg-3"></div>
    </div>

    <!-- Main Container -->
    <div class="register-container">
        <!-- Left Panel - Branding -->
        <div class="brand-panel">
            <div class="brand-logo">
                <div class="logo-icon">
                    <i class="fas fa-store"></i>
                </div>
                <h1>Join Our Restaurant Network</h1>
                <p>Grow your business with our digital ordering platform. Reach more customers and streamline your operations.</p>
            </div>

            <ul class="features-list">
                <li>
                    <i class="fas fa-chart-line"></i>
                    <span>Increase sales by up to 40% with digital ordering</span>
                </li>
                <li>
                    <i class="fas fa-users"></i>
                    <span>Reach thousands of customers in your area</span>
                </li>
                <li>
                    <i class="fas fa-cogs"></i>
                    <span>Easy-to-use dashboard for managing orders</span>
                </li>
                <li>
                    <i class="fas fa-shield-alt"></i>
                    <span>Secure payment processing & data protection</span>
                </li>
                <li>
                    <i class="fas fa-headset"></i>
                    <span>24/7 customer support for your business</span>
                </li>
            </ul>

            <div style="margin-top: auto; opacity: 0.8; font-size: 0.9rem; text-align: center;">
                <p><i class="fas fa-star"></i> Trusted by 500+ restaurants nationwide</p>
            </div>
        </div>

        <!-- Right Panel - Form -->
        <div class="form-panel">
            <!-- Progress Indicator -->
            <div class="progress-indicator">
                <div class="progress-step active" id="step1">
                    <div class="step-circle">1</div>
                    <div class="step-label">Business Info</div>
                </div>
                <div class="progress-step" id="step2">
                    <div class="step-circle">2</div>
                    <div class="step-label">Legal Docs</div>
                </div>
                <div class="progress-step" id="step3">
                    <div class="step-circle">3</div>
                    <div class="step-label">Complete</div>
                </div>
            </div>

            <!-- Form Header -->
            <div class="form-header">
                <h2>Shop Registration</h2>
                <p>Fill in your business details to get started. It takes less than 2 minutes.</p>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="error-messages">
                    <h4><i class="fas fa-exclamation-triangle"></i> Please fix the following errors:</h4>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" id="registrationForm">
                <div class="form-grid">
                    <!-- Business Information -->
                    <div class="form-group">
                        <label class="form-label required" for="shop_name">
                            <i class="fas fa-store"></i> Shop Display Name
                        </label>
                        <input type="text" id="shop_name" name="shop_name" 
                               value="<?php echo isset($_POST['shop_name']) ? htmlspecialchars($_POST['shop_name']) : ''; ?>"
                               class="form-input" required placeholder="e.g., Joe's Coffee Shop">
                        <div class="form-help">
                            <i class="fas fa-info-circle"></i> This name will appear to customers
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label required" for="owner_name">
                            <i class="fas fa-user-tie"></i> Owner Name
                        </label>
                        <input type="text" id="owner_name" name="owner_name"
                               value="<?php echo isset($_POST['owner_name']) ? htmlspecialchars($_POST['owner_name']) : ''; ?>"
                               class="form-input" required placeholder="Full name">
                    </div>

                    <div class="form-group">
                        <label class="form-label required" for="email">
                            <i class="fas fa-envelope"></i> Email Address
                        </label>
                        <input type="email" id="email" name="email"
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                               class="form-input" required placeholder="owner@example.com">
                        <div class="form-help">
                            <i class="fas fa-info-circle"></i> We'll send verification to this email
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label required" for="phone">
                            <i class="fas fa-phone"></i> Phone Number
                        </label>
                        <input type="tel" id="phone" name="phone"
                               value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>"
                               class="form-input" required placeholder="10-digit mobile number">
                        <div class="form-help">
                            <i class="fas fa-info-circle"></i> For order notifications and support
                        </div>
                    </div>

                    <!-- Password Fields -->
                    <div class="form-group">
                        <label class="form-label required" for="password">
                            <i class="fas fa-lock"></i> Password
                        </label>
                        <input type="password" id="password" name="password" class="form-input" required>
                        <div class="form-help">
                            <i class="fas fa-info-circle"></i> Minimum 6 characters
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label required" for="confirm">
                            <i class="fas fa-lock"></i> Confirm Password
                        </label>
                        <input type="password" id="confirm" name="confirm" class="form-input" required>
                    </div>
                </div>

                <!-- Legal Notice -->
                <div class="legal-notice">
                    <h4><i class="fas fa-scale-balanced"></i> Legal Requirements</h4>
                    <p>For legal compliance, you will need to provide the following documents within 30 days of registration:</p>
                    
                    <div class="requirements-grid">
                        <div class="requirement-item">
                            <i class="fas fa-file-contract"></i>
                            <div class="requirement-text">
                                <strong>Business Address Proof</strong><br>
                                For tax and legal purposes
                            </div>
                        </div>
                        <div class="requirement-item">
                            <i class="fas fa-receipt"></i>
                            <div class="requirement-text">
                                <strong>GSTIN Certificate</strong><br>
                                Required if turnover exceeds ₹20 lakhs
                            </div>
                        </div>
                        <div class="requirement-item">
                            <i class="fas fa-utensils"></i>
                            <div class="requirement-text">
                                <strong>FSSAI License</strong><br>
                                Mandatory for food businesses
                            </div>
                        </div>
                        <div class="requirement-item">
                            <i class="fas fa-building"></i>
                            <div class="requirement-text">
                                <strong>Trade License</strong><br>
                                From local municipal authority
                            </div>
                        </div>
                    </div>

                    <p style="margin-top: 20px; color: var(--primary); font-weight: 500;">
                        <i class="fas fa-info-circle"></i> You can add these documents later in your dashboard
                    </p>
                </div>

                <!-- Terms and Conditions -->
                <div class="checkbox-group">
                    <input type="checkbox" id="terms" name="terms" class="checkbox-input" required>
                    <label for="terms" class="checkbox-label">
                        I agree to the <a href="#" onclick="openTermsModal()">Terms of Service</a> and understand my responsibilities as a business owner
                    </label>
                </div>

                <div class="checkbox-group">
                    <input type="checkbox" id="privacy" name="privacy" class="checkbox-input" required>
                    <label for="privacy" class="checkbox-label">
                        I agree to the <a href="#" onclick="openPrivacyModal()">Privacy Policy</a> and consent to data processing as described
                    </label>
                </div>

                <!-- Submit Section -->
                <div class="submit-section">
                    <button type="submit" class="submit-btn">
                        <i class="fas fa-rocket"></i> Launch My Shop
                    </button>

                    <div class="login-link">
                        Already have an account? <a href="login.php">Login to your dashboard</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Success Animation (Hidden by default) -->
    <div class="success-animation" id="successAnimation">
        <div class="success-icon">
            <i class="fas fa-check"></i>
        </div>
        <h2 style="color: var(--success); margin-bottom: 15px;">Registration Successful!</h2>
        <p style="color: var(--text); font-size: 1.1rem; margin-bottom: 30px;">
            Your shop registration has been submitted. Please check your email for verification.
        </p>
        <button onclick="window.location.href='login.php'" class="submit-btn" style="max-width: 300px; margin: 0 auto;">
            <i class="fas fa-sign-in-alt"></i> Go to Login
        </button>
    </div>

    <!-- Terms Modal -->
    <div id="termsModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 10000; justify-content: center; align-items: center; padding: 20px;">
        <div style="background: white; padding: 50px; border-radius: 25px; max-width: 800px; max-height: 90vh; overflow-y: auto; position: relative; box-shadow: 0 20px 60px rgba(0,0,0,0.3);">
            <button onclick="closeModal('termsModal')" style="position: absolute; top: 25px; right: 25px; background: var(--primary); color: white; border: none; width: 40px; height: 40px; border-radius: 50%; font-size: 24px; cursor: pointer; display: flex; align-items: center; justify-content: center;">×</button>
            <h2 style="color: var(--dark); margin-bottom: 25px; font-size: 2rem; display: flex; align-items: center; gap: 15px;">
                <i class="fas fa-file-contract" style="color: var(--primary);"></i> Terms of Service
            </h2>
            <div style="margin-top: 20px; line-height: 1.6; color: var(--text);">
                <h3 style="color: var(--dark); margin: 25px 0 15px 0;">1. Legal Compliance</h3>
                <p>You agree to comply with all applicable laws including GST, FSSAI, and local business regulations.</p>
                
                <h3 style="color: var(--dark); margin: 25px 0 15px 0;">2. Business Information Accuracy</h3>
                <p>You certify that all business information provided is accurate and up-to-date.</p>
                
                <h3 style="color: var(--dark); margin: 25px 0 15px 0;">3. Tax Compliance</h3>
                <p>You are responsible for collecting and remitting all applicable taxes as per government regulations.</p>
                
                <h3 style="color: var(--dark); margin: 25px 0 15px 0;">4. Data Protection</h3>
                <p>You agree to protect customer data and comply with data protection laws including applicable privacy regulations.</p>
            </div>
        </div>
    </div>

    <!-- Privacy Modal -->
    <div id="privacyModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 10000; justify-content: center; align-items: center; padding: 20px;">
        <div style="background: white; padding: 50px; border-radius: 25px; max-width: 800px; max-height: 90vh; overflow-y: auto; position: relative; box-shadow: 0 20px 60px rgba(0,0,0,0.3);">
            <button onclick="closeModal('privacyModal')" style="position: absolute; top: 25px; right: 25px; background: var(--primary); color: white; border: none; width: 40px; height: 40px; border-radius: 50%; font-size: 24px; cursor: pointer; display: flex; align-items: center; justify-content: center;">×</button>
            <h2 style="color: var(--dark); margin-bottom: 25px; font-size: 2rem; display: flex; align-items: center; gap: 15px;">
                <i class="fas fa-shield-alt" style="color: var(--primary);"></i> Privacy Policy
            </h2>
            <div style="margin-top: 20px; line-height: 1.6; color: var(--text);">
                <h3 style="color: var(--dark); margin: 25px 0 15px 0;">1. Data Collection</h3>
                <p>We collect business information for legal compliance, verification, and service delivery purposes.</p>
                
                <h3 style="color: var(--dark); margin: 25px 0 15px 0;">2. Data Usage</h3>
                <p>Your data is used for platform operations, compliance verification, and improving our services.</p>
                
                <h3 style="color: var(--dark); margin: 25px 0 15px 0;">3. Data Protection</h3>
                <p>We implement industry-standard security measures to protect your information from unauthorized access.</p>
                
                <h3 style="color: var(--dark); margin: 25px 0 15px 0;">4. Third-Party Sharing</h3>
                <p>We may share information with legal authorities as required by law or to comply with legal processes.</p>
            </div>
        </div>
    </div>

    <script>
        // Form validation and animations
        document.addEventListener('DOMContentLoaded', function() {
            // Update progress indicator on input focus
            const inputs = document.querySelectorAll('.form-input');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    updateProgress(this);
                });
            });

            // Form validation
            const form = document.getElementById('registrationForm');
            if (form) {
                form.addEventListener('submit', function(e) {
                    let isValid = true;
                    
                    // Clear previous errors
                    document.querySelectorAll('.form-input.error').forEach(el => {
                        el.classList.remove('error');
                    });
                    
                    // Validate phone
                    const phone = document.getElementById('phone');
                    if (phone && !/^[0-9]{10}$/.test(phone.value)) {
                        showError(phone, 'Please enter a valid 10-digit phone number');
                        isValid = false;
                    }
                    
                    // Validate password
                    const password = document.getElementById('password');
                    if (password && password.value.length < 6) {
                        showError(password, 'Password must be at least 6 characters');
                        isValid = false;
                    }
                    
                    // Validate password match
                    const confirm = document.getElementById('confirm');
                    if (password && confirm && password.value !== confirm.value) {
                        showError(confirm, 'Passwords do not match');
                        isValid = false;
                    }
                    
                    // Validate terms and privacy
                    const terms = document.getElementById('terms');
                    const privacy = document.getElementById('privacy');
                    
                    if (!terms || !terms.checked) {
                        showCheckboxError(terms, 'You must agree to the Terms of Service');
                        isValid = false;
                    }
                    
                    if (!privacy || !privacy.checked) {
                        showCheckboxError(privacy, 'You must agree to the Privacy Policy');
                        isValid = false;
                    }
                    
                    if (!isValid) {
                        e.preventDefault();
                        // Scroll to first error
                        const firstError = document.querySelector('.form-input.error');
                        if (firstError) {
                            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }
                    } else {
                        // Show success animation
                        e.preventDefault();
                        showSuccessAnimation();
                    }
                });
            }

            // Real-time validation
            const phoneInput = document.getElementById('phone');
            if (phoneInput) {
                phoneInput.addEventListener('blur', function() {
                    if (this.value && !/^[0-9]{10}$/.test(this.value)) {
                        showError(this, 'Please enter a valid 10-digit phone number');
                    }
                });
            }

            const passwordInput = document.getElementById('password');
            if (passwordInput) {
                passwordInput.addEventListener('input', function() {
                    if (this.value.length > 0 && this.value.length < 6) {
                        showError(this, 'Password must be at least 6 characters');
                    } else {
                        this.classList.remove('error');
                        const errorMsg = this.parentNode.querySelector('.error-message');
                        if (errorMsg) errorMsg.remove();
                    }
                });
            }

            const confirmInput = document.getElementById('confirm');
            if (confirmInput && passwordInput) {
                confirmInput.addEventListener('input', function() {
                    if (this.value !== passwordInput.value) {
                        showError(this, 'Passwords do not match');
                    } else {
                        this.classList.remove('error');
                        const errorMsg = this.parentNode.querySelector('.error-message');
                        if (errorMsg) errorMsg.remove();
                    }
                });
            }
        });

        function updateProgress(input) {
            const step1 = document.getElementById('step1');
            const step2 = document.getElementById('step2');
            const step3 = document.getElementById('step3');
            
            // Reset all steps
            step1.classList.remove('active');
            step2.classList.remove('active');
            step3.classList.remove('active');
            
            // Determine which step to activate
            if (input.id === 'password' || input.id === 'confirm') {
                step3.classList.add('active');
            } else if (input.id === 'phone' || input.id === 'email') {
                step2.classList.add('active');
            } else {
                step1.classList.add('active');
            }
        }

        function showError(input, message) {
            input.classList.add('error');
            // Remove existing error message
            const existingError = input.parentNode.querySelector('.error-message');
            if (existingError) existingError.remove();
            
            const errorDiv = document.createElement('div');
            errorDiv.className = 'error-message';
            errorDiv.style.cssText = 'color: var(--error); font-size: 0.85rem; margin-top: 8px; display: flex; align-items: center; gap: 8px;';
            errorDiv.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
            input.parentNode.appendChild(errorDiv);
        }

        function showCheckboxError(checkbox, message) {
            const checkboxGroup = checkbox.closest('.checkbox-group');
            if (checkboxGroup) {
                checkboxGroup.style.borderColor = 'var(--error)';
                checkboxGroup.style.animation = 'shake 0.5s ease-in-out';
                
                setTimeout(() => {
                    checkboxGroup.style.animation = '';
                }, 500);
                
                // Remove existing error message
                const existingError = checkboxGroup.querySelector('.checkbox-error');
                if (existingError) existingError.remove();
                
                const errorDiv = document.createElement('div');
                errorDiv.className = 'checkbox-error';
                errorDiv.style.cssText = 'color: var(--error); font-size: 0.85rem; margin-top: 5px; display: flex; align-items: center; gap: 8px;';
                errorDiv.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
                checkboxGroup.appendChild(errorDiv);
            }
        }

        function showSuccessAnimation() {
            const form = document.getElementById('registrationForm');
            const successAnimation = document.getElementById('successAnimation');
            
            if (form && successAnimation) {
                form.style.display = 'none';
                successAnimation.classList.add('active');
                
                // Scroll to success message
                successAnimation.scrollIntoView({ behavior: 'smooth', block: 'center' });
                
                // Submit form after animation
                setTimeout(() => {
                    form.submit();
                }, 3000);
            }
        }

        // Modal functions
        function openTermsModal() {
            document.getElementById('termsModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function openPrivacyModal() {
            document.getElementById('privacyModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        // Close modals on outside click
        window.onclick = function(event) {
            if (event.target.id === 'termsModal') closeModal('termsModal');
            if (event.target.id === 'privacyModal') closeModal('privacyModal');
        }

        // Add subtle animations to form elements
        const formGroups = document.querySelectorAll('.form-group');
        formGroups.forEach((group, index) => {
            group.style.opacity = '0';
            group.style.transform = 'translateY(20px)';
            group.style.animation = `fadeIn 0.5s forwards ${index * 0.1}s`;
        });

        // Add keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 'Enter') {
                // Ctrl+Enter to submit form
                document.querySelector('.submit-btn').click();
            }
            if (e.key === 'Escape') {
                // Escape to close modals
                closeModal('termsModal');
                closeModal('privacyModal');
            }
        });
    </script>
</body>
</html>