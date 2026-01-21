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
        $errors[] = "Please enter a valid 10-digit Indian mobile number (starts with 6-9)";
    }
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    }
    if ($password !== $confirm) {
        $errors[] = "Passwords do not match";
    }

    if (empty($errors)) {
        // Check for duplicate email
        $stmt = $conn->prepare("SELECT id FROM shops WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $errors[] = "This email is already registered";
        }
        $stmt->close();

        // Check for duplicate phone (optional but recommended)
        if (empty($errors)) {
            $stmt = $conn->prepare("SELECT id FROM shops WHERE phone = ? LIMIT 1");
            $stmt->bind_param("s", $phone);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $errors[] = "This phone number is already registered";
            }
            $stmt->close();
        }

        if (empty($errors)) {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("
                INSERT INTO shops 
                (shop_name, owner_name, email, phone, password_hash, status)
                VALUES (?, ?, ?, ?, ?, 'pending')
            ");

            $stmt->bind_param("sssss", $shop_name, $owner_name, $email, $phone, $hash);

            if ($stmt->execute()) {
                $_SESSION['register_success'] = true;
                header("Location: login.php?registered=1");
                exit;
            } else {
                $errors[] = "Registration failed. Please try again later.";
                // In production: log $conn->error instead of showing to user
            }
            $stmt->close();
        }
    }

    // Keep form values on error
    $_POST = array_map('htmlspecialchars', $_POST);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Your Shop</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

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

        .register-container {
            display: flex;
            width: 90%;
            max-width: 1200px;
            background: rgba(255, 255, 255, 0.97);
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
            position: relative;
        }

        .form-panel {
            flex: 1.2;
            padding: 60px 70px;
            background: white;
        }

        h1, h2 {
            font-weight: 800;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
        }

        .form-group.full-width {
            grid-column: span 2;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark);
        }

        .form-label.required::after {
            content: " *";
            color: var(--error);
        }

        .form-input {
            width: 100%;
            padding: 16px 20px;
            border: 2px solid var(--gray);
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(255,107,107,0.15);
        }

        .form-input.error {
            border-color: var(--error);
            background: rgba(255,82,82,0.05);
        }

        .error-messages {
            background: rgba(255,82,82,0.1);
            border: 2px solid var(--error);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .error-messages h4 {
            color: var(--error);
            margin-bottom: 12px;
        }

        .submit-btn {
            background: var(--gradient);
            color: white;
            border: none;
            width: 100%;
            padding: 18px;
            font-size: 1.2rem;
            font-weight: 600;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 20px;
        }

        .submit-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(255,107,107,0.4);
        }

        .login-link {
            text-align: center;
            margin-top: 25px;
            color: var(--text);
        }

        .login-link a {
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
        }

        @media (max-width: 1024px) {
            .register-container {
                flex-direction: column;
            }
            .brand-panel, .form-panel {
                padding: 50px 40px;
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
                padding: 40px 25px;
            }
        }
    </style>
</head>
<body>

<div class="register-container">

    <!-- Left - Branding -->
    <div class="brand-panel">
        <div style="text-align:center; margin-bottom:40px;">
            <div style="font-size:80px; margin-bottom:20px;">
                <i class="fas fa-store"></i>
            </div>
            <h1>Join Our Network</h1>
            <p style="font-size:1.2rem; opacity:0.9; margin-top:15px;">
                Grow your restaurant with online ordering & more customers
            </p>
        </div>

        <ul style="list-style:none; margin-top:30px;">
            <li style="margin:20px 0; font-size:1.1rem;"><i class="fas fa-check-circle" style="color:var(--accent); margin-right:12px;"></i> Increase sales up to 40%</li>
            <li style="margin:20px 0; font-size:1.1rem;"><i class="fas fa-check-circle" style="color:var(--accent); margin-right:12px;"></i> Easy order management</li>
            <li style="margin:20px 0; font-size:1.1rem;"><i class="fas fa-check-circle" style="color:var(--accent); margin-right:12px;"></i> Secure payments</li>
        </ul>
    </div>

    <!-- Right - Form -->
    <div class="form-panel">

        <h2 style="margin-bottom:10px;">Create Shop Account</h2>
        <p style="color:#666; margin-bottom:35px;">It takes less than 2 minutes</p>

        <?php if (!empty($errors)): ?>
            <div class="error-messages">
                <h4><i class="fas fa-exclamation-triangle"></i> Please correct the following:</h4>
                <ul style="margin-left:20px; line-height:1.6;">
                    <?php foreach ($errors as $err): ?>
                        <li><?= htmlspecialchars($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" novalidate>
            <div class="form-grid">

                <div class="form-group">
                    <label class="form-label required" for="shop_name">Shop Name</label>
                    <input type="text" name="shop_name" id="shop_name" class="form-input"
                           value="<?= htmlspecialchars($shop_name ?? '') ?>" required
                           placeholder="e.g. Shiva's Pure Veg">
                </div>

                <div class="form-group">
                    <label class="form-label required" for="owner_name">Owner Name</label>
                    <input type="text" name="owner_name" id="owner_name" class="form-input"
                           value="<?= htmlspecialchars($owner_name ?? '') ?>" required
                           placeholder="Full name">
                </div>

                <div class="form-group">
                    <label class="form-label required" for="email">Email Address</label>
                    <input type="email" name="email" id="email" class="form-input"
                           value="<?= htmlspecialchars($email ?? '') ?>" required
                           placeholder="owner@yourshop.com">
                </div>

                <div class="form-group">
                    <label class="form-label required" for="phone">Phone Number</label>
                    <input type="tel" name="phone" id="phone" class="form-input"
                           pattern="[6789][0-9]{9}" maxlength="10"
                           value="<?= htmlspecialchars($phone ?? '') ?>" required
                           placeholder="9876543210">
                </div>

                <div class="form-group">
                    <label class="form-label required" for="password">Password</label>
                    <input type="password" name="password" id="password" class="form-input" required
                           minlength="8" placeholder="Minimum 8 characters">
                </div>

                <div class="form-group">
                    <label class="form-label required" for="confirm">Confirm Password</label>
                    <input type="password" name="confirm" id="confirm" class="form-input" required
                           placeholder="Re-enter password">
                </div>

            </div>

            <button type="submit" class="submit-btn">
                <i class="fas fa-rocket" style="margin-right:10px;"></i> Register Shop
            </button>

            <div class="login-link">
                Already have an account? <a href="login.php">Login here</a>
            </div>
        </form>

    </div>

</div>

</body>
</html>