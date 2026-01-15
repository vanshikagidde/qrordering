<?php
session_start();

if(!isset($_SESSION['order'])){
  header("Location: index.php");
  exit;
}

include "../config/db.php";

$cart = $_SESSION['order']['cart'];
$total = 0;
foreach ($cart as $menu_id => $qty) {
  $q = mysqli_query($conn, "SELECT price FROM menu WHERE id='$menu_id'");
  $item = mysqli_fetch_assoc($q);
  if ($item) {
    $total += $item['price'] * $qty;
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #FF6B6B;
            --primary-light: #FF8E8E;
            --primary-dark: #FF4757;
            --secondary: #4ECDC4;
            --accent: #FFD166;
            --dark: #2D3047;
            --light: #F7F9FC;
            --gray: #E2E8F0;
            --text: #333333;
            --text-light: #718096;
            --success: #4CAF50;
            --error: #FF5252;
            --shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            --shadow-hover: 0 15px 40px rgba(0, 0, 0, 0.12);
            --gradient: linear-gradient(135deg, #FF6B6B 0%, #FF8E8E 100%);
            --gradient-success: linear-gradient(135deg, #4CAF50 0%, #66BB6A 100%);
            --gradient-error: linear-gradient(135deg, #FF5252 0%, #FF8A80 100%);
            --gradient-dark: linear-gradient(135deg, #2D3047 0%, #3D4166 100%);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            overflow-x: hidden;
        }

        /* Floating Background Elements */
        .floating-bg {
            position: fixed;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
        }

        .floating-circle {
            position: absolute;
            border-radius: 50%;
            background: radial-gradient(circle, var(--primary-light) 0%, transparent 70%);
            opacity: 0.1;
            animation: float 20s infinite ease-in-out;
        }

        .circle-1 {
            width: 300px;
            height: 300px;
            top: 10%;
            left: 5%;
            animation-delay: 0s;
        }

        .circle-2 {
            width: 200px;
            height: 200px;
            top: 60%;
            right: 10%;
            background: radial-gradient(circle, var(--secondary) 0%, transparent 70%);
            animation-delay: 5s;
            animation-duration: 25s;
        }

        .circle-3 {
            width: 150px;
            height: 150px;
            bottom: 10%;
            left: 15%;
            background: radial-gradient(circle, var(--accent) 0%, transparent 70%);
            animation-delay: 10s;
            animation-duration: 30s;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            33% { transform: translateY(-30px) rotate(120deg); }
            66% { transform: translateY(20px) rotate(240deg); }
        }

        .payment-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            padding: 40px;
            max-width: 450px;
            width: 100%;
            box-shadow: var(--shadow);
            border: 1px solid rgba(255, 255, 255, 0.3);
            position: relative;
            overflow: hidden;
            transform: translateY(20px);
            opacity: 0;
            animation: slideUp 0.6s forwards 0.3s;
        }

        @keyframes slideUp {
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .payment-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: var(--gradient);
        }

        .payment-header {
            text-align: center;
            margin-bottom: 30px;
            position: relative;
        }

        .payment-icon {
            width: 80px;
            height: 80px;
            background: var(--gradient);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: white;
            font-size: 32px;
            animation: pulse 2s infinite;
            box-shadow: 0 8px 20px rgba(255, 107, 107, 0.3);
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .payment-header h2 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 8px;
        }

        .payment-header p {
            color: var(--text-light);
            font-size: 1.1rem;
        }

        .order-details {
            background: linear-gradient(135deg, #f8fafc 0%, #edf2f7 100%);
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 30px;
            border: 1px solid var(--gray);
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px dashed var(--gray);
        }

        .detail-row:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }

        .detail-label {
            color: var(--text-light);
            font-size: 1rem;
        }

        .detail-value {
            font-weight: 600;
            color: var(--dark);
            font-size: 1.1rem;
        }

        .order-id {
            font-family: 'Courier New', monospace;
            background: var(--dark);
            color: white;
            padding: 8px 15px;
            border-radius: 30px;
            font-size: 0.9rem;
            letter-spacing: 1px;
        }

        .total-amount {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--primary);
            text-align: center;
            margin: 25px 0;
            background: linear-gradient(to right, var(--primary), var(--primary-light));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: amountGlow 3s infinite alternate;
        }

        @keyframes amountGlow {
            from { filter: drop-shadow(0 0 5px rgba(255, 107, 107, 0.3)); }
            to { filter: drop-shadow(0 0 10px rgba(255, 107, 107, 0.5)); }
        }

        .payment-options {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-bottom: 25px;
        }

        .payment-btn {
            padding: 20px;
            border: none;
            border-radius: 16px;
            font-size: 1.2rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .payment-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.7s;
        }

        .payment-btn:hover::before {
            left: 100%;
        }

        .payment-btn:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        .payment-btn:active {
            transform: translateY(0);
        }

        .payment-btn.success {
            background: var(--gradient-success);
            color: white;
        }

        .payment-btn.fail {
            background: var(--gradient-error);
            color: white;
        }

        .payment-btn i {
            font-size: 1.4rem;
        }

        /* Animation Container */
        .animation-container {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(5px);
            border-radius: 24px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            opacity: 0;
            visibility: hidden;
            transform: scale(0.9);
            transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            z-index: 10;
            padding: 40px;
            text-align: center;
        }

        .animation-container.active {
            opacity: 1;
            visibility: visible;
            transform: scale(1);
        }

        .spinner {
            width: 80px;
            height: 80px;
            border: 6px solid var(--gray);
            border-top: 6px solid var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 25px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .success-animation {
            width: 100px;
            height: 100px;
            background: var(--gradient-success);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 25px;
            animation: successPop 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .error-animation {
            width: 100px;
            height: 100px;
            background: var(--gradient-error);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 25px;
            animation: errorShake 0.6s ease-in-out;
        }

        @keyframes successPop {
            0% { transform: scale(0); opacity: 0; }
            70% { transform: scale(1.1); }
            100% { transform: scale(1); opacity: 1; }
        }

        @keyframes errorShake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-10px); }
            20%, 40%, 60%, 80% { transform: translateX(10px); }
        }

        .success-animation i,
        .error-animation i {
            color: white;
            font-size: 48px;
        }

        .status-message {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 15px;
        }

        .status-submessage {
            color: var(--text-light);
            font-size: 1.1rem;
            max-width: 300px;
            line-height: 1.5;
        }

        .processing .payment-btn {
            opacity: 0.5;
            pointer-events: none;
        }

        /* Responsive */
        @media (max-width: 480px) {
            body {
                padding: 15px;
            }

            .payment-container {
                padding: 30px 25px;
            }

            .payment-icon {
                width: 70px;
                height: 70px;
                font-size: 28px;
            }

            .payment-header h2 {
                font-size: 1.8rem;
            }

            .order-details {
                padding: 20px;
            }

            .total-amount {
                font-size: 2.2rem;
            }

            .payment-btn {
                padding: 18px;
                font-size: 1.1rem;
            }

            .success-animation,
            .error-animation {
                width: 80px;
                height: 80px;
            }

            .success-animation i,
            .error-animation i {
                font-size: 36px;
            }
        }
    </style>
</head>

<body>
    <!-- Floating Background Elements -->
    <div class="floating-bg">
        <div class="floating-circle circle-1"></div>
        <div class="floating-circle circle-2"></div>
        <div class="floating-circle circle-3"></div>
    </div>

    <div class="payment-container" id="paymentContainer">
        <div class="payment-header">
            <div class="payment-icon">
                <i class="fas fa-credit-card"></i>
            </div>
            <h2>Secure Payment</h2>
            <p>Complete your order with a mock payment</p>
        </div>

        <div class="order-details">
            <div class="detail-row">
                <span class="detail-label">Order ID</span>
                <span class="detail-value order-id"><?= $_SESSION['order_id'] ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Items in Cart</span>
                <span class="detail-value"><?= array_sum($cart) ?> items</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Payment Method</span>
                <span class="detail-value">Mock Gateway</span>
            </div>
        </div>

        <div class="total-amount">₹<?= number_format($total, 0) ?></div>

        <div class="payment-options">
            <button class="payment-btn success" onclick="initiatePayment('success')">
                <i class="fas fa-check-circle"></i>
                Complete Payment
            </button>
            <button class="payment-btn fail" onclick="initiatePayment('failed')">
                <i class="fas fa-times-circle"></i>
                Simulate Failure
            </button>
        </div>

        <p style="text-align: center; color: var(--text-light); font-size: 0.9rem; margin-top: 20px;">
            <i class="fas fa-shield-alt"></i> This is a mock payment gateway for demonstration purposes
        </p>

        <!-- Animation Container -->
        <div class="animation-container" id="animationContainer">
            <div class="spinner" id="spinner"></div>
            <div class="success-animation" id="successAnimation" style="display: none;">
                <i class="fas fa-check"></i>
            </div>
            <div class="error-animation" id="errorAnimation" style="display: none;">
                <i class="fas fa-times"></i>
            </div>
            <div class="status-message" id="statusMessage"></div>
            <div class="status-submessage" id="statusSubmessage"></div>
        </div>
    </div>

    <script>
        function initiatePayment(status) {
            const container = document.getElementById('paymentContainer');
            const animationContainer = document.getElementById('animationContainer');
            const spinner = document.getElementById('spinner');
            const successAnimation = document.getElementById('successAnimation');
            const errorAnimation = document.getElementById('errorAnimation');
            const statusMessage = document.getElementById('statusMessage');
            const statusSubmessage = document.getElementById('statusSubmessage');

            // Hide buttons and show loading
            container.classList.add('processing');
            animationContainer.classList.add('active');
            
            // Show processing state
            spinner.style.display = 'block';
            successAnimation.style.display = 'none';
            errorAnimation.style.display = 'none';
            statusMessage.textContent = 'Processing Payment';
            statusSubmessage.textContent = 'Please wait while we process your transaction...';

            // Simulate payment processing delay
            setTimeout(() => {
                spinner.style.display = 'none';
                
                if (status === 'success') {
                    successAnimation.style.display = 'flex';
                    statusMessage.textContent = 'Payment Successful!';
                    statusSubmessage.textContent = 'Your order has been confirmed. Redirecting...';
                    
                    // Add confetti effect
                    createConfetti();
                    
                    // Redirect after animation
                    setTimeout(() => {
                        window.location.href = "payment_callback.php?status=success";
                    }, 2000);
                } else {
                    errorAnimation.style.display = 'flex';
                    statusMessage.textContent = 'Payment Failed';
                    statusSubmessage.textContent = 'The transaction could not be completed. Please try again.';
                    
                    // Reset after animation
                    setTimeout(() => {
                        animationContainer.classList.remove('active');
                        container.classList.remove('processing');
                    }, 3000);
                }
            }, 2500); // 2.5s processing time
        }

        function createConfetti() {
            const colors = ['#FF6B6B', '#4ECDC4', '#FFD166', '#2D3047'];
            const container = document.querySelector('.animation-container');
            
            for (let i = 0; i < 100; i++) {
                const confetti = document.createElement('div');
                confetti.style.position = 'absolute';
                confetti.style.width = Math.random() * 10 + 5 + 'px';
                confetti.style.height = Math.random() * 10 + 5 + 'px';
                confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                confetti.style.borderRadius = Math.random() > 0.5 ? '50%' : '0';
                confetti.style.left = Math.random() * 100 + '%';
                confetti.style.top = '-20px';
                confetti.style.opacity = '0.8';
                confetti.style.transform = `rotate(${Math.random() * 360}deg)`;
                container.appendChild(confetti);
                
                // Animate confetti
                const animation = confetti.animate([
                    { transform: `translateY(0) rotate(0deg)`, opacity: 1 },
                    { transform: `translateY(${window.innerHeight}px) rotate(${Math.random() * 720}deg)`, opacity: 0 }
                ], {
                    duration: Math.random() * 2000 + 1000,
                    easing: 'cubic-bezier(0.215, 0.610, 0.355, 1)'
                });
                
                animation.onfinish = () => confetti.remove();
            }
        }

        // Add slight entrance animation delay for elements
        document.addEventListener('DOMContentLoaded', function() {
            const rows = document.querySelectorAll('.detail-row');
            rows.forEach((row, index) => {
                row.style.animationDelay = `${0.1 * index}s`;
                row.style.opacity = '0';
                row.style.transform = 'translateX(-20px)';
                row.style.animation = `slideUp 0.5s forwards ${0.3 + 0.1 * index}s`;
            });
        });
    </script>
</body>
</html>