<?php
// logout.php - Secure Logout Page
session_start();

// Clear all session variables
$_SESSION = [];

// Destroy the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Completely destroy the session
session_destroy();

// Optional: Clear any remember-me cookies if you ever add them
// setcookie('remember_token', '', time() - 3600, '/');

// Redirect to login page with success message
header("Location: login.php?logout=success");
exit;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Logging Out - RestoFlow</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet"/>
    <style>
        :root {
            --primary: #7c3aed;
            --primary-dark: #6d28d9;
            --dark: #1f2937;
            --darker: #111827;
            --gray-600: #4b5563;
            --light: #ffffff;
            --success: #10b981;
            --radius-lg: 16px;
            --shadow-lg: 0 10px 25px rgba(0,0,0,0.1);
            --transition: all 0.4s ease;
        }

        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family: 'Inter', 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
            color: var(--dark);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .logout-container {
            text-align: center;
            background: var(--light);
            padding: 60px 40px;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
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
            color: var(--darker);
            margin-bottom: 16px;
        }

        p {
            color: var(--gray-600);
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 32px;
        }

        .redirect-message {
            font-size: 1rem;
            color: var(--gray-600);
            margin-top: 24px;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 5px solid var(--gray-200);
            border-top: 5px solid var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 24px auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>

<div class="logout-container">
    <div class="logout-icon">
        <i class="fas fa-sign-out-alt"></i>
    </div>
    
    <h1>Logging You Out...</h1>
    
    <p>Thank you for using RestoFlow!<br>Your session has been securely ended.</p>
    
    <div class="spinner"></div>
    
    <div class="redirect-message">
        Redirecting to login page in <span id="countdown">3</span> seconds...
    </div>
</div>

<script>
    // Simple countdown redirect (fallback if PHP header fails)
    let seconds = 3;
    const countdownEl = document.getElementById('countdown');
    
    const timer = setInterval(() => {
        seconds--;
        countdownEl.textContent = seconds;
        
        if (seconds <= 0) {
            clearInterval(timer);
            window.location.href = 'login.php?logout=success';
        }
    }, 1000);
</script>

</body>
</html>