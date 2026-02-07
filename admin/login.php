<?php
session_start();
include "../config/db.php"; // adjust path if needed

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Email and password are required!";
    } else {
        $email = mysqli_real_escape_string($conn, $email);

        $q = mysqli_query($conn, "SELECT * FROM admins WHERE email = '$email' LIMIT 1");

        if ($q && mysqli_num_rows($q) === 1) {
            $admin = mysqli_fetch_assoc($q);

            if (password_verify($password, $admin['password_hash'])) {
                // ✅ Login success
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_name'] = $admin['name'] ?? 'Admin';
                $_SESSION['admin_email'] = $admin['email'];

                header("Location: dashboard.php");
                exit;
            } else {
                $error = "Invalid email or password!";
            }
        } else {
            $error = "Invalid email or password!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #F6921E;
            --primary-dark: #E07E0A;
            --primary-light: #FF8C42;
            --secondary: #FFF5F0;
            --accent: #FFAD87;
            --dark: #2D3436;
            --light: #FFFFFF;
            --gray: #F1F2F6;
            --text: #2D3436;
            --success: #00B894;
            --error: #FF5252;
            --shadow: 0 10px 40px rgba(255, 107, 53, 0.15);
            --shadow-hover: 0 20px 60px rgba(255, 107, 53, 0.25);
            --gradient: linear-gradient(135deg, #FF6B35 0%, #FF8C42 50%, #FFAD87 100%);
            --gradient-light: linear-gradient(135deg, #FFF5F0 0%, #FFFFFF 100%);
        }

        * { margin:0; padding:0; box-sizing:border-box; }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: var(--gradient-light);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text);
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: absolute;
            width: 600px;
            height: 600px;
            background: linear-gradient(135deg, rgba(246, 146, 30, 0.1) 0%, rgba(255, 140, 66, 0.05) 100%);
            border-radius: 50%;
            top: -300px;
            right: -200px;
            animation: float 20s infinite ease-in-out;
            z-index: 0;
        }

        body::after {
            content: '';
            position: absolute;
            width: 400px;
            height: 400px;
            background: linear-gradient(135deg, rgba(255, 173, 135, 0.15) 0%, rgba(246, 146, 30, 0.05) 100%);
            border-radius: 50%;
            bottom: -200px;
            left: -100px;
            animation: float 25s infinite ease-in-out reverse;
            z-index: 0;
        }

        @keyframes float {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(30px, -30px) scale(1.05); }
            66% { transform: translate(-20px, 20px) scale(0.95); }
        }

        .login-container {
            display: flex;
            width: 90%;
            max-width: 1100px;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border-radius: 30px;
            overflow: hidden;
            box-shadow: var(--shadow-hover);
            position: relative;
            z-index: 1;
            animation: slideUp 0.8s ease-out;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(40px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .brand-panel {
            flex: 1;
            background: var(--gradient);
            color: white;
            padding: 60px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
            animation: slideInLeft 0.8s ease-out 0.2s both;
        }

        @keyframes slideInLeft {
            from { opacity: 0; transform: translateX(-50px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .brand-panel::before {
            content: '';
            position: absolute;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
            background-size: 20px 20px;
            opacity: 0.3;
            animation: moveGrid 20s linear infinite;
        }

        @keyframes moveGrid {
            0% { transform: translate(0, 0); }
            100% { transform: translate(50px, 50px); }
        }

        .form-panel {
            flex: 1.2;
            padding: 60px 70px;
            background: white;
            animation: slideInRight 0.8s ease-out 0.3s both;
        }

        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(50px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .logo-icon {
            width: 120px;
            height: 120px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
            font-size: 48px;
            animation: pulse 2s infinite, floatIcon 6s ease-in-out infinite;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }

        @keyframes pulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(255, 255, 255, 0.4); }
            50% { box-shadow: 0 0 0 20px rgba(255, 255, 255, 0); }
        }

        @keyframes floatIcon {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        h1 { 
            font-size: 3rem; 
            font-weight: 800; 
            text-align: center; 
            margin-bottom: 20px;
            animation: fadeInUp 0.8s ease-out 0.5s both;
        }

        h2 { 
            font-size: 2.6rem; 
            font-weight: 700; 
            margin-bottom: 12px; 
            position: relative; 
            display: inline-block;
            animation: fadeInUp 0.8s ease-out 0.4s both;
        }

        h2::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            width: 90px;
            height: 4px;
            background: var(--gradient);
            border-radius: 2px;
            animation: expandWidth 0.8s ease-out 0.8s both;
        }

        @keyframes expandWidth {
            from { width: 0; }
            to { width: 90px; }
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .form-group { 
            margin-bottom: 28px; 
            position: relative;
            animation: fadeInUp 0.6s ease-out both;
        }

        .form-group:nth-child(1) { animation-delay: 0.5s; }
        .form-group:nth-child(2) { animation-delay: 0.6s; }

        .form-label {
            display: block;
            margin-bottom: 9px;
            font-weight: 600;
            color: var(--dark);
            transition: color 0.3s ease;
        }
        .form-label.required::after { content: " *"; color: var(--error); }

        .input-wrapper { position: relative; }
        
        .form-input {
            width: 100%;
            padding: 16px 20px 16px 52px;
            border: 2px solid var(--gray);
            border-radius: 12px;
            font-size: 1.05rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: white;
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(246, 146, 30, 0.15);
            transform: translateY(-2px);
        }
        
        .form-input.error { 
            border-color: var(--error); 
            background: rgba(255, 82, 82, 0.04);
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }

        .input-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary-light);
            font-size: 1.3rem;
            transition: all 0.3s ease;
        }

        .form-input:focus ~ .input-icon {
            color: var(--primary);
            transform: translateY(-50%) scale(1.1);
        }

        .toggle-password {
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #555;
            font-size: 1.25rem;
            transition: all 0.3s ease;
        }

        .toggle-password:hover {
            color: var(--primary);
            transform: translateY(-50%) scale(1.1);
        }

        .error-messages {
            background: rgba(255, 82, 82, 0.05);
            border-left: 4px solid var(--error);
            border-radius: 12px;
            padding: 18px 24px;
            margin-bottom: 30px;
            animation: slideIn 0.4s ease-out;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .submit-btn {
            background: var(--gradient);
            color: white;
            border: none;
            width: 100%;
            padding: 18px;
            font-size: 1.22rem;
            font-weight: 600;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            animation: fadeInUp 0.6s ease-out 0.8s both;
        }

        .submit-btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .submit-btn:hover::before {
            width: 300px;
            height: 300px;
        }

        .submit-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(246, 146, 30, 0.4);
        }

        .submit-btn:active {
            transform: translateY(-1px);
        }

        .submit-btn.loading {
            pointer-events: none;
            opacity: 0.8;
        }

        .submit-btn.loading::after {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            top: 50%;
            left: 50%;
            margin-left: -10px;
            margin-top: -10px;
            border: 2px solid #ffffff;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spinner 0.8s linear infinite;
        }

        @keyframes spinner {
            to { transform: rotate(360deg); }
        }

        @media (max-width: 1024px) {
            .login-container { flex-direction: column; }
            .brand-panel, .form-panel { padding: 50px 40px; }
            .brand-panel { animation: slideInUp 0.8s ease-out 0.2s both; }
        }

        @media (max-width: 480px) {
            .form-panel { padding: 40px 25px; }
            h2 { font-size: 2.2rem; }
            body::before, body::after { display: none; }
        }
    </style>
</head>
<body>

<div class="login-container">

    <div class="brand-panel">
        <div style="text-align:center; position: relative; z-index: 1;">
            <div class="logo-icon"><i class="fas fa-shield-halved"></i></div>
            <h1>Admin Portal</h1>
            <p style="font-size:1.2rem; opacity:0.95; margin-top:15px; animation: fadeInUp 0.8s ease-out 0.6s both;">
                Secure access to system management
            </p>
        </div>
    </div>

    <div class="form-panel">
        <h2>Sign In</h2>
        <p style="color:#555; margin-bottom:35px; animation: fadeInUp 0.6s ease-out 0.5s both;">Administrator Dashboard Access</p>

        <?php if ($error != ""): ?>
            <div class="error-messages">
                <h4 style="color: var(--error); margin-bottom: 10px;"><i class="fas fa-exclamation-circle"></i> Authentication Error</h4>
                <p style="color: #666;"><?php echo htmlspecialchars($error); ?></p>
            </div>
        <?php endif; ?>

        <form method="POST" novalidate id="loginForm">
            <div class="form-group">
                <label class="form-label required" for="email">Email</label>
                <div class="input-wrapper">
                    <i class="fas fa-envelope input-icon"></i>
                    <input type="email" name="email" id="email" 
                           class="form-input <?php echo $error != '' ? 'error' : ''; ?>" 
                           required autocomplete="email" placeholder="admin@system.com">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label required" for="password">Password</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" name="password" id="password" 
                           class="form-input <?php echo $error != '' ? 'error' : ''; ?>" 
                           required autocomplete="current-password">
                    <i class="fas fa-eye toggle-password" id="togglePassword"></i>
                </div>
            </div>

            <button type="submit" class="submit-btn" id="submitBtn">
                <i class="fas fa-arrow-right-to-bracket"></i> Login
            </button>
        </form>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.getElementById('togglePassword');
    const pass = document.getElementById('password');
    const form = document.getElementById('loginForm');
    const submitBtn = document.getElementById('submitBtn');

    if (toggle && pass) {
        toggle.addEventListener('click', () => {
            const type = pass.getAttribute('type') === 'password' ? 'text' : 'password';
            pass.setAttribute('type', type);
            toggle.classList.toggle('fa-eye');
            toggle.classList.toggle('fa-eye-slash');
            
            toggle.style.transform = 'translateY(-50%) scale(1.2)';
            setTimeout(() => {
                toggle.style.transform = 'translateY(-50%) scale(1)';
            }, 200);
        });
    }

    if (form) {
        form.addEventListener('submit', (e) => {
            submitBtn.classList.add('loading');
            submitBtn.innerHTML = '';
        });
    }

    const inputs = document.querySelectorAll('.form-input');
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.style.transform = 'scale(1.02)';
        });
        
        input.addEventListener('blur', function() {
            this.parentElement.style.transform = 'scale(1)';
        });
    });
});
</script>

</body>
</html>