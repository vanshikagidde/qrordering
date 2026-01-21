<?php
// login.php
include "../config/db.php";
session_start();

$errors = [];
$email_value = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password'] ?? '';

    $email_value = $email; // for repopulating field

    if (empty($email)) {
        $errors[] = "Email is required";
    }
    if (empty($password)) {
        $errors[] = "Password is required";
    }

    if (empty($errors) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Use prepared statement
        $stmt = $conn->prepare("SELECT id, shop_name, owner_name, password_hash, status 
                                FROM shops 
                                WHERE email = ? 
                                LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            if (password_verify($password, $row['password_hash'])) {
                // Check account status
                if ($row['status'] === 'pending') {
                    $errors[] = "Your account is pending approval. Please wait for admin verification.";
                } elseif ($row['status'] === 'rejected') {
                    $errors[] = "Your account registration was rejected. Please contact support.";
                } else {
                    // Login successful
                    $_SESSION['shop_id']    = $row['id'];
                    $_SESSION['shop_name']  = $row['shop_name'];
                    $_SESSION['owner_name'] = $row['owner_name'];
                    $_SESSION['logged_in']  = true; // optional extra flag

                    // Remember me - 30 days
                    if (isset($_POST['remember']) && $_POST['remember'] === 'on') {
                        setcookie('shop_email', $email, time() + (30 * 24 * 60 * 60), "/", "", false, true);
                    } else {
                        // Clear cookie if not checked
                        if (isset($_COOKIE['shop_email'])) {
                            setcookie('shop_email', '', time() - 3600, "/");
                        }
                    }

                    $stmt->close();
                    header("Location: dashboard.php");
                    exit;
                }
            } else {
                $errors[] = "Incorrect password";
            }
        } else {
            $errors[] = "No account found with that email";
        }

        $stmt->close();
    } elseif (empty($errors)) {
        $errors[] = "Please enter a valid email address";
    }
}

// Pre-fill email from cookie if exists and no POST data
if (empty($email_value) && isset($_COOKIE['shop_email'])) {
    $email_value = htmlspecialchars($_COOKIE['shop_email']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Shop Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
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
            --shadow: 0 10px 30px rgba(0,0,0,0.08);
            --shadow-hover: 0 15px 40px rgba(0,0,0,0.12);
            --gradient: linear-gradient(135deg, #FF6B6B 0%, #FF8E8E 100%);
            --gradient-dark: linear-gradient(135deg, #2D3047 0%, #3D4166 100%);
        }

        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text);
            padding: 20px;
        }

        .login-container {
            display: flex;
            width: 90%;
            max-width: 1100px;
            background: rgba(255,255,255,0.97);
            backdrop-filter: blur(20px);
            border-radius: 30px;
            overflow: hidden;
            box-shadow: var(--shadow-hover);
        }

        .brand-panel {
            flex: 1;
            background: var(--gradient-dark);
            color: white;
            padding: 60px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .form-panel {
            flex: 1.2;
            padding: 60px 70px;
            background: white;
        }

        .logo-icon {
            width: 110px;
            height: 110px;
            background: var(--gradient);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
            font-size: 48px;
            box-shadow: 0 12px 30px rgba(255,107,107,0.35);
        }

        h1 { font-size: 3rem; font-weight: 800; text-align: center; margin-bottom: 20px; }
        h2 { font-size: 2.6rem; font-weight: 700; margin-bottom: 12px; position: relative; display: inline-block; }
        h2::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            width: 90px;
            height: 4px;
            background: var(--gradient);
            border-radius: 2px;
        }

        .form-group { margin-bottom: 28px; position: relative; }
        .form-label {
            display: block;
            margin-bottom: 9px;
            font-weight: 600;
            color: var(--dark);
        }
        .form-label.required::after { content: " *"; color: var(--error); }

        .input-wrapper { position: relative; }
        .form-input {
            width: 100%;
            padding: 16px 20px 16px 52px;
            border: 2px solid var(--gray);
            border-radius: 12px;
            font-size: 1.05rem;
            transition: all 0.3s ease;
        }
        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(255,107,107,0.12);
        }
        .form-input.error { border-color: var(--error); background: rgba(255,82,82,0.04); }

        .input-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
            font-size: 1.3rem;
        }

        .toggle-password {
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #555;
            font-size: 1.25rem;
        }

        .error-messages {
            background: rgba(255,82,82,0.1);
            border: 2px solid var(--error);
            border-radius: 12px;
            padding: 18px 24px;
            margin-bottom: 30px;
        }

        .options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 20px 0 32px;
            font-size: 0.98rem;
        }

        .remember-me { display: flex; align-items: center; gap: 10px; }

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
            transition: all 0.3s ease;
        }
        .submit-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(255,107,107,0.35);
        }

        .register-link { text-align: center; margin-top: 28px; font-size: 1.05rem; }
        .register-link a { color: var(--primary); font-weight: 600; text-decoration: none; }

        @media (max-width: 1024px) {
            .login-container { flex-direction: column; }
            .brand-panel, .form-panel { padding: 50px 40px; }
        }

        @media (max-width: 480px) {
            .form-panel { padding: 40px 25px; }
            h2 { font-size: 2.2rem; }
        }
    </style>
</head>
<body>

<div class="login-container">

    <!-- Branding side -->
    <div class="brand-panel">
        <div style="text-align:center;">
            <div class="logo-icon"><i class="fas fa-store"></i></div>
            <h1>Welcome Back!</h1>
            <p style="font-size:1.2rem; opacity:0.9; margin-top:15px;">
                Manage orders, update menu & grow your business
            </p>
        </div>
    </div>

    <!-- Form side -->
    <div class="form-panel">
        <h2>Sign In</h2>
        <p style="color:#555; margin-bottom:35px;">Access your shop dashboard</p>

        <?php if (!empty($errors)): ?>
            <div class="error-messages">
                <h4><i class="fas fa-exclamation-triangle"></i> Login issues</h4>
                <ul style="margin:12px 0 0 24px; line-height:1.6;">
                    <?php foreach ($errors as $err): ?>
                        <li><?= htmlspecialchars($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" novalidate>
            <div class="form-group">
                <label class="form-label required" for="email">Email</label>
                <div class="input-wrapper">
                    <i class="fas fa-envelope input-icon"></i>
                    <input type="email" name="email" id="email" 
                           class="form-input" value="<?= htmlspecialchars($email_value) ?>" 
                           required autocomplete="email" placeholder="owner@yourshop.com">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label required" for="password">Password</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" name="password" id="password" 
                           class="form-input" required autocomplete="current-password">
                    <i class="fas fa-eye toggle-password" id="togglePassword"></i>
                </div>
            </div>

            <div class="options">
                <label class="remember-me">
                    <input type="checkbox" name="remember" <?= isset($_COOKIE['shop_email']) ? 'checked' : '' ?>>
                    <span>Remember me</span>
                </label>
                <a href="#" class="forgot-password">Forgot password?</a>
            </div>

            <button type="submit" class="submit-btn">
                <i class="fas fa-arrow-right-to-bracket"></i> Login
            </button>

            <div class="register-link">
                New to the platform? <a href="register.php">Register your shop</a>
            </div>
        </form>
    </div>

</div>

<script>
// Password visibility toggle
document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.getElementById('togglePassword');
    const pass   = document.getElementById('password');

    if (toggle && pass) {
        toggle.addEventListener('click', () => {
            const type = pass.getAttribute('type') === 'password' ? 'text' : 'password';
            pass.setAttribute('type', type);
            toggle.classList.toggle('fa-eye');
            toggle.classList.toggle('fa-eye-slash');
        });
    }
});
</script>

</body>
</html>