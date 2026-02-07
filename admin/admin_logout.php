<?php
// admin_logout.php - Secure Admin Logout

session_start();

// Clear all admin-specific session variables
unset($_SESSION['admin_logged_in']);
unset($_SESSION['admin_id']);
unset($_SESSION['admin_email']);
unset($_SESSION['admin_name']);
unset($_SESSION['login_attempts']);
unset($_SESSION['last_attempt']);

// Destroy the entire session
session_destroy();

// Clear any remember-me cookies (if you added them)
if (isset($_COOKIE['admin_remember'])) {
    setcookie('admin_remember', '', time() - 3600, '/', '', true, true);
}

// Optional: Add security headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");

// Redirect to login with success flag
header("Location: admin_login.php?logout=success");
exit;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Logging Out - RestoFlow Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
    <style>
        :root {
            --primary: #7c3aed;
            --primary-dark: #6d28d9;
            --dark: #0f172a;
            --light: #ffffff;
            --success: #10b981;
            --radius-xl: 24px;
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1);
            --transition: all 0.3s ease;
        }

        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .logout-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 60px 48px;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-xl);
            text-align: center;
            max-width: 480px;
            width: 90%;
            animation: fadeIn 0.8s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .logout-icon {
            font-size: 5rem;
            color: var(--success);
            margin-bottom: 24px;
        }

        h1 {
            font-size: 2.2rem;
            color: var(--dark);
            margin-bottom: 16px;
        }

        p {
            color: var(--gray-600);
            font-size: 1.1rem;
            margin-bottom: 32px;
        }

        .spinner {
            width: 48px;
            height: 48px;
            border: 6px solid rgba(124, 58, 237, 0.2);
            border-top: 6px solid var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 24px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .countdown {
            font-size: 1.1rem;
            color: var(--gray-600);
        }

        .countdown strong {
            color: var(--primary);
            font-weight: 700;
        }
    </style>
</head>
<body>

<div class="logout-container">
    <div class="logout-icon">
        <i class="fas fa-sign-out-alt"></i>
    </div>
    
    <h1>Logging You Out...</h1>
    
    <p>Thank you for using RestoFlow Admin Panel.<br>Your session has been securely ended.</p>
    
    <div class="spinner"></div>
    
    <div class="countdown">
        Redirecting to login in <strong id="count">3</strong> seconds...
    </div>
</div>

<script>
    // Countdown + fallback redirect
    let seconds = 3;
    const countEl = document.getElementById('count');
    
    const timer = setInterval(() => {
        seconds--;
        countEl.textContent = seconds;
        
        if (seconds <= 0) {
            clearInterval(timer);
            window.location.href = 'login.php?logout=success';
        }
    }, 1000);
</script>

</body>
</html>