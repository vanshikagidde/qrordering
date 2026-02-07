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
  $menu_id = (int)$menu_id;
  $q = mysqli_query($conn, "SELECT price FROM menu WHERE id=$menu_id");
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
    <title>Payment - Secure Checkout</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #F97316;
            --primary-light: #FB923C;
            --primary-dark: #EA580C;
            --success: #10B981;
            --error: #EF4444;
            --text: #1E293B;
            --text-light: #64748B;
            --border: #E2E8F0;
            --bg: #F8FAFC;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #ffffff;
            color: var(--text);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
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
            filter: blur(60px);
            opacity: 0.4;
        }

        .shape-1 {
            width: 350px;
            height: 350px;
            background: rgba(249, 115, 22, 0.15);
            top: -100px;
            right: -100px;
            animation: float1 20s infinite ease-in-out;
        }

        .shape-2 {
            width: 280px;
            height: 280px;
            background: rgba(251, 146, 60, 0.12);
            bottom: 10%;
            left: -80px;
            animation: float2 25s infinite ease-in-out;
        }

        .shape-3 {
            width: 200px;
            height: 200px;
            background: rgba(234, 88, 12, 0.1);
            top: 40%;
            right: 15%;
            animation: float3 18s infinite ease-in-out;
        }

        .shape-4 {
            width: 300px;
            height: 300px;
            background: rgba(251, 146, 60, 0.08);
            bottom: -100px;
            right: 20%;
            animation: float4 22s infinite ease-in-out;
        }

        @keyframes float1 {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(30px, -50px) scale(1.1); }
            66% { transform: translate(-20px, 30px) scale(0.9); }
        }

        @keyframes float2 {
            0%, 100% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(40px, -30px) scale(1.15); }
        }

        @keyframes float3 {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(-30px, 40px) scale(0.95); }
            66% { transform: translate(20px, -20px) scale(1.05); }
        }

        @keyframes float4 {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            50% { transform: translate(-30px, -50px) rotate(180deg); }
        }

        /* Payment Container */
        .payment-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 40px;
            max-width: 450px;
            width: 100%;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(249, 115, 22, 0.1);
            position: relative;
            z-index: 1;
            overflow: hidden;
        }

        .payment-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, var(--primary), var(--primary-light), var(--primary));
            background-size: 200% 100%;
            animation: shimmer 3s infinite linear;
        }

        @keyframes shimmer {
            0% { background-position: 100% 0; }
            100% { background-position: -100% 0; }
        }

        /* Header */
        .payment-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .payment-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: white;
            font-size: 36px;
            box-shadow: 0 10px 30px rgba(249, 115, 22, 0.3);
            animation: iconPulse 2s infinite;
            position: relative;
            overflow: hidden;
        }

        .payment-icon::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, transparent, rgba(255,255,255,0.3));
        }

        @keyframes iconPulse {
            0%, 100% { transform: scale(1); box-shadow: 0 10px 30px rgba(249, 115, 22, 0.3); }
            50% { transform: scale(1.05); box-shadow: 0 15px 40px rgba(249, 115, 22, 0.4); }
        }

        .payment-header h2 {
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--text);
            margin-bottom: 8px;
        }

        .payment-header p {
            color: var(--text-light);
            font-size: 1rem;
        }

        /* Order Details */
        .order-details {
            background: linear-gradient(135deg, #FFF7ED 0%, #FFF7ED 100%);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 25px;
            border: 1px solid rgba(249, 115, 22, 0.15);
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 14px;
            padding-bottom: 14px;
            border-bottom: 1px dashed rgba(249, 115, 22, 0.2);
        }

        .detail-row:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }

        .detail-label {
            color: var(--text-light);
            font-size: 0.95rem;
            font-weight: 500;
        }

        .detail-value {
            font-weight: 700;
            color: var(--text);
            font-size: 1rem;
        }

        .order-id {
            font-family: 'Courier New', monospace;
            background: var(--text);
            color: white;
            padding: 6px 14px;
            border-radius: 30px;
            font-size: 0.85rem;
            letter-spacing: 1px;
        }

        /* Total Amount */
        .total-section {
            text-align: center;
            margin: 30px 0;
            padding: 25px;
            background: white;
            border-radius: 16px;
            border: 2px solid rgba(249, 115, 22, 0.2);
            position: relative;
            overflow: hidden;
        }

        .total-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--primary-light));
        }

        .total-label {
            font-size: 0.9rem;
            color: var(--text-light);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .total-amount {
            font-size: 3rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            line-height: 1;
        }

        /* Payment Buttons */
        .payment-options {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 20px;
        }

        .payment-btn {
            padding: 18px 24px;
            border: none;
            border-radius: 14px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            position: relative;
            overflow: hidden;
        }

        .payment-btn::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, transparent, rgba(255,255,255,0.3), transparent);
            transform: translateX(-100%);
            transition: transform 0.6s;
        }

        .payment-btn:hover::before {
            transform: translateX(100%);
        }

        .payment-btn.success {
            background: linear-gradient(135deg, var(--success), #059669);
            color: white;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }

        .payment-btn.success:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
        }

        .payment-btn.fail {
            background: linear-gradient(135deg, var(--error), #DC2626);
            color: white;
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
        }

        .payment-btn.fail:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(239, 68, 68, 0.4);
        }

        .payment-btn i {
            font-size: 1.3rem;
        }

        /* Security Note */
        .security-note {
            text-align: center;
            color: var(--text-light);
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid var(--border);
        }

        .security-note i {
            color: var(--success);
        }

        /* Animation Overlay */
        .animation-overlay {
            position: absolute;
            inset: 0;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            display: none;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 10;
            padding: 40px;
            text-align: center;
        }

        .animation-overlay.active {
            display: flex;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* Spinner */
        .spinner {
            width: 70px;
            height: 70px;
            border: 5px solid rgba(249, 115, 22, 0.2);
            border-top-color: var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 25px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Success/Error Animations */
        .status-icon {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 25px;
            font-size: 40px;
            color: white;
            display: none;
        }

        .status-icon.success {
            background: linear-gradient(135deg, var(--success), #059669);
            animation: popIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .status-icon.error {
            background: linear-gradient(135deg, var(--error), #DC2626);
            animation: shake 0.5s ease;
        }

        @keyframes popIn {
            0% { transform: scale(0); }
            80% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-10px); }
            40%, 80% { transform: translateX(10px); }
        }

        .status-title {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--text);
            margin-bottom: 10px;
        }

        .status-message {
            color: var(--text-light);
            font-size: 1rem;
            max-width: 300px;
            line-height: 1.5;
        }

        /* Confetti */
        .confetti {
            position: absolute;
            width: 10px;
            height: 10px;
            background: var(--primary);
            animation: confettiFall 3s ease-out forwards;
        }

        @keyframes confettiFall {
            0% { transform: translateY(-100px) rotate(0deg); opacity: 1; }
            100% { transform: translateY(400px) rotate(720deg); opacity: 0; }
        }

        /* Responsive */
        @media (max-width: 480px) {
            .payment-container {
                padding: 30px 25px;
            }

            .payment-icon {
                width: 70px;
                height: 70px;
                font-size: 30px;
            }

            .payment-header h2 {
                font-size: 1.5rem;
            }

            .total-amount {
                font-size: 2.5rem;
            }

            .payment-btn {
                padding: 16px;
                font-size: 1rem;
            }
        }
    </style>
</head>

<body>
    <!-- Animated Background -->
    <div class="bg-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
        <div class="shape shape-4"></div>
    </div>

    <div class="payment-container" id="paymentContainer">
        <!-- Header -->
        <div class="payment-header">
            <div class="payment-icon">
                <i class="fas fa-shield-alt"></i>
            </div>
            <h2>Secure Payment</h2>
            <p>Complete your order safely</p>
        </div>

        <!-- Order Details -->
        <div class="order-details">
            <div class="detail-row">
                <span class="detail-label">Order ID</span>
                <span class="detail-value order-id"><?= htmlspecialchars($_SESSION['order']['order_id'] ?? 'N/A') ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Items</span>
                <span class="detail-value"><?= array_sum($cart) ?> items</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Table</span>
                <span class="detail-value"><?= htmlspecialchars($_SESSION['order']['table_no'] ?? 'N/A') ?></span>
            </div>
        </div>

        <!-- Total -->
        <div class="total-section">
            <div class="total-label">Total Amount</div>
            <div class="total-amount">₹<?= number_format($total, 0) ?></div>
        </div>

        <!-- Buttons -->
        <div class="payment-options">
            <button class="payment-btn success" onclick="processPayment('success')">
                <i class="fas fa-check-circle"></i>
                Pay Now
            </button>
            <button class="payment-btn fail" onclick="processPayment('failed')">
                <i class="fas fa-times-circle"></i>
                Test Failure
            </button>
        </div>

        <div class="security-note">
            <i class="fas fa-lock"></i>
            <span>Mock payment gateway for demo purposes</span>
        </div>

        <!-- Animation Overlay -->
        <div class="animation-overlay" id="animationOverlay">
            <div class="spinner" id="spinner"></div>
            <div class="status-icon success" id="successIcon">
                <i class="fas fa-check"></i>
            </div>
            <div class="status-icon error" id="errorIcon">
                <i class="fas fa-times"></i>
            </div>
            <div class="status-title" id="statusTitle">Processing</div>
            <div class="status-message" id="statusMessage">Please wait...</div>
        </div>
    </div>

    <script>
        function processPayment(status) {
            const overlay = document.getElementById('animationOverlay');
            const spinner = document.getElementById('spinner');
            const successIcon = document.getElementById('successIcon');
            const errorIcon = document.getElementById('errorIcon');
            const title = document.getElementById('statusTitle');
            const message = document.getElementById('statusMessage');

            // Show overlay
            overlay.classList.add('active');
            
            // Reset states
            spinner.style.display = 'block';
            successIcon.style.display = 'none';
            errorIcon.style.display = 'none';
            title.textContent = 'Processing Payment';
            message.textContent = 'Please wait while we secure your transaction...';

            // Simulate processing
            setTimeout(() => {
                spinner.style.display = 'none';
                
                if (status === 'success') {
                    successIcon.style.display = 'flex';
                    title.textContent = 'Payment Successful!';
                    message.textContent = 'Your order has been confirmed. Redirecting...';
                    
                    // Create confetti
                    createConfetti();
                    
                    // Redirect
                    setTimeout(() => {
                        window.location.href = "payment_callback.php?status=success";
                    }, 2000);
                } else {
                    errorIcon.style.display = 'flex';
                    title.textContent = 'Payment Failed';
                    message.textContent = 'Transaction could not be completed. Please try again.';
                    
                    // Close after delay
                    setTimeout(() => {
                        overlay.classList.remove('active');
                    }, 2500);
                }
            }, 2000);
        }

        function createConfetti() {
            const colors = ['#F97316', '#FB923C', '#10B981', '#3B82F6', '#F59E0B'];
            const container = document.getElementById('animationOverlay');
            
            for (let i = 0; i < 50; i++) {
                const confetti = document.createElement('div');
                confetti.className = 'confetti';
                confetti.style.left = Math.random() * 100 + '%';
                confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                confetti.style.animationDelay = Math.random() * 0.5 + 's';
                confetti.style.borderRadius = Math.random() > 0.5 ? '50%' : '0';
                container.appendChild(confetti);
                
                setTimeout(() => confetti.remove(), 3000);
            }
        }
    </script>
</body>
</html>