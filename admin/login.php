<?php
// admin_login.php - Super Admin Login Page
session_start();
include "../config/db.php";

// If already logged in as admin, redirect to dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: admin_dashboard.php");
    exit;
}

$error = '';
$success = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Email and password are required";
    } else {
        // For simplicity: hard-coded admin credentials (CHANGE THIS IN PRODUCTION!)
        // In real system: use proper user table + hashed passwords
        $admin_email = "admin@restoflow.com";          // ← CHANGE THIS
        $admin_pass  = "Admin@2025Secure!";            // ← CHANGE THIS (and hash it!)

        // Very basic check (replace with proper database query later)
        if ($email === $admin_email && $password === $admin_pass) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_email']     = $email;
            $_SESSION['admin_role']      = 'superadmin';

            // Optional: log login attempt
            // file_put_contents('admin_logins.log', date('Y-m-d H:i:s') . " - Login: $email\n", FILE_APPEND);

            header("Location: admin_dashboard.php?login=success");
            exit;
        } else {
            $error = "Invalid email or password";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Admin Login - RestoFlow Control Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet"/>
    <style>
        :root {
            --primary: #7c3aed;
            --primary-dark: #6d28d9;
            --dark: #1f2937;
            --darker: #111827;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --light: #ffffff;
            --success: #10b981;
            --danger: #ef4444;
            --radius-lg: 16px;
            --shadow-lg: 0 10px 25px rgba(0,0,0,0.12);
            --transition: all 0.3s ease;
        }

        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family: 'Inter', 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            background: var(--light);
            width: 100%;
            max-width: 420px;
            padding: 48px 40px;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            animation: fadeInUp 0.6s ease-out;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .logo-area {
            text-align: center;
            margin-bottom: 40px;
        }

        .logo-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.8rem;
            color: white;
            margin: 0 auto 16px;
            box-shadow: 0 8px 20px rgba(124, 58, 237, 0.3);
        }

        h1 {
            font-size: 2rem;
            color: var(--darker);
            margin-bottom: 8px;
            text-align: center;
        }

        .subtitle {
            color: var(--gray-600);
            text-align: center;
            margin-bottom: 36px;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--gray-700);
        }

        .form-control {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid var(--gray-200);
            border-radius: 10px;
            font-size: 1rem;
            transition: var(--transition);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(124, 58, 237, 0.15);
        }

        .btn-login {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            margin-top: 12px;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(124, 58, 237, 0.4);
        }

        .error-message {
            background: #fee2e2;
            color: #991b1b;
            padding: 14px;
            border-radius: 10px;
            margin-bottom: 24px;
            text-align: center;
            font-weight: 500;
        }

        .success-message {
            background: #ecfdf5;
            color: #065f46;
            padding: 14px;
            border-radius: 10px;
            margin-bottom: 24px;
            text-align: center;
            font-weight: 500;
        }

        .back-link {
            text-align: center;
            margin-top: 32px;
            color: var(--gray-600);
        }

        .back-link a {
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
        }

        .back-link a:hover {
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 40px 24px;
            }
        }
    </style>
</head>
<body>

<div class="login-container">
    <div class="logo-area">
        <div class="logo-icon">
            <i class="fas fa-user-shield"></i>
        </div>
        <h1>Admin Panel</h1>
        <p class="subtitle">RestoFlow Super Admin Access</p>
    </div>

    <?php if ($error): ?>
        <div class="error-message">
            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="success-message">
            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <form method="post">
        <div class="form-group">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" 
                   placeholder="admin@restoflow.com" required autocomplete="email" autofocus>
        </div>

        <div class="form-group">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" 
                   placeholder="••••••••" required autocomplete="current-password">
        </div>

        <button type="submit" class="btn-login">
            <i class="fas fa-sign-in-alt"></i> Login to Admin Panel
        </button>
    </form>

    <div class="back-link">
        <a href="../index.php">← Back to Main Site</a>
    </div>
</div>

</body>
</html>