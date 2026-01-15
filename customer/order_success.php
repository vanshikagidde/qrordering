<?php
include "../config/db.php";

$token    = $_GET['token'] ?? '';
$order_id = $_GET['order_id'] ?? '';
$shop_id  = $_GET['shop_id'] ?? '';

if (!$token || !$shop_id || !$order_id) {
    die("Invalid request");
}

/* Fetch shop name */
$q = mysqli_query($conn,
    "SELECT shop_name FROM shops WHERE id='$shop_id'"
);
$shop = mysqli_fetch_assoc($q);
$shop_name = $shop ? $shop['shop_name'] : 'Unknown Shop';

/* Fetch ordered items */
$items_q = mysqli_query($conn,
    "SELECT m.item_name, m.price, oi.quantity
     FROM order_items oi
     JOIN menu m ON oi.menu_id = m.id
     WHERE oi.order_id='$order_id'"
);
?>
<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Order Confirmed</title>
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
        --shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        --shadow-hover: 0 15px 40px rgba(0, 0, 0, 0.12);
        --gradient: linear-gradient(135deg, #FF6B6B 0%, #FF8E8E 100%);
        --gradient-success: linear-gradient(135deg, #4CAF50 0%, #66BB6A 100%);
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
        padding: 20px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
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

    .confirmation-container {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border-radius: 24px;
        padding: 40px;
        max-width: 500px;
        width: 100%;
        box-shadow: var(--shadow);
        border: 1px solid rgba(255, 255, 255, 0.3);
        position: relative;
        overflow: hidden;
        margin-bottom: 30px;
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

    .confirmation-container::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 5px;
        background: var(--gradient-success);
    }

    .confirmation-header {
        text-align: center;
        margin-bottom: 30px;
        position: relative;
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
        animation: successPop 0.8s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        box-shadow: 0 10px 30px rgba(76, 175, 80, 0.3);
    }

    @keyframes successPop {
        0% { transform: scale(0); opacity: 0; }
        70% { transform: scale(1.1); }
        100% { transform: scale(1); opacity: 1; }
    }

    .confirmation-header h1 {
        font-size: 2.5rem;
        font-weight: 700;
        color: var(--dark);
        margin-bottom: 10px;
        background: linear-gradient(to right, var(--primary), var(--success));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .confirmation-header p {
        color: var(--text-light);
        font-size: 1.2rem;
    }

    .shop-info {
        text-align: center;
        margin-bottom: 30px;
        padding: 20px;
        background: linear-gradient(135deg, #f8fafc 0%, #edf2f7 100%);
        border-radius: 16px;
        border: 1px solid var(--gray);
    }

    .shop-name {
        font-size: 1.8rem;
        font-weight: 700;
        color: var(--dark);
        margin-bottom: 5px;
    }

    .order-meta {
        display: flex;
        justify-content: space-between;
        margin-bottom: 25px;
        background: var(--light);
        padding: 20px;
        border-radius: 16px;
        border: 1px solid var(--gray);
    }

    .meta-item {
        text-align: center;
        flex: 1;
    }

    .meta-label {
        display: block;
        color: var(--text-light);
        font-size: 0.9rem;
        margin-bottom: 5px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .meta-value {
        font-size: 1.4rem;
        font-weight: 700;
        color: var(--dark);
    }

    .token-display {
        background: var(--gradient-dark);
        color: white;
        padding: 25px;
        border-radius: 16px;
        text-align: center;
        margin-bottom: 30px;
        position: relative;
        overflow: hidden;
        box-shadow: 0 8px 25px rgba(45, 48, 71, 0.2);
        animation: tokenGlow 3s infinite alternate;
    }

    @keyframes tokenGlow {
        from { box-shadow: 0 8px 25px rgba(45, 48, 71, 0.2); }
        to { box-shadow: 0 8px 25px rgba(45, 48, 71, 0.4), 0 0 30px rgba(45, 48, 71, 0.1); }
    }

    .token-display::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z' fill='%23ffffff' fill-opacity='0.1' fill-rule='evenodd'/%3E%3C/svg%3E");
        opacity: 0.3;
    }

    .token-label {
        display: block;
        font-size: 1.1rem;
        color: rgba(255, 255, 255, 0.9);
        margin-bottom: 10px;
    }

    .token-value {
        font-size: 4rem;
        font-weight: 800;
        letter-spacing: 5px;
        font-family: 'Courier New', monospace;
        text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
    }

    .order-items {
        margin-bottom: 30px;
    }

    .items-header {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--dark);
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .items-header i {
        color: var(--primary);
    }

    .items-list {
        background: var(--light);
        border-radius: 16px;
        border: 1px solid var(--gray);
        overflow: hidden;
    }

    .item-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 18px 25px;
        border-bottom: 1px dashed var(--gray);
        transition: all 0.3s ease;
        opacity: 0;
        transform: translateX(-20px);
        animation: slideIn 0.5s forwards;
    }

    @keyframes slideIn {
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    .item-row:nth-child(1) { animation-delay: 0.1s; }
    .item-row:nth-child(2) { animation-delay: 0.2s; }
    .item-row:nth-child(3) { animation-delay: 0.3s; }
    .item-row:nth-child(4) { animation-delay: 0.4s; }
    .item-row:nth-child(5) { animation-delay: 0.5s; }
    .item-row:nth-child(6) { animation-delay: 0.6s; }

    .item-row:last-child {
        border-bottom: none;
    }

    .item-row:hover {
        background: rgba(255, 255, 255, 0.5);
        transform: translateX(5px);
    }

    .item-name {
        flex: 1;
        font-weight: 600;
        color: var(--dark);
        font-size: 1.1rem;
    }

    .item-details {
        text-align: right;
    }

    .item-quantity {
        font-size: 1rem;
        color: var(--text-light);
        margin-bottom: 5px;
    }

    .item-price {
        font-size: 1.2rem;
        font-weight: 700;
        color: var(--primary);
    }

    .line-total {
        font-size: 0.9rem;
        color: var(--text-light);
        margin-top: 5px;
    }

    .total-section {
        background: var(--gradient);
        color: white;
        padding: 25px;
        border-radius: 16px;
        text-align: center;
        margin-bottom: 25px;
        box-shadow: 0 8px 25px rgba(255, 107, 107, 0.2);
    }

    .total-label {
        font-size: 1.2rem;
        margin-bottom: 10px;
        opacity: 0.9;
    }

    .total-amount {
        font-size: 3rem;
        font-weight: 800;
        letter-spacing: 1px;
    }

    .instructions {
        background: linear-gradient(135deg, #f8fafc 0%, #edf2f7 100%);
        padding: 25px;
        border-radius: 16px;
        border: 1px solid var(--gray);
        margin-bottom: 30px;
        text-align: center;
    }

    .instructions h3 {
        color: var(--dark);
        margin-bottom: 15px;
        font-size: 1.3rem;
    }

    .instruction-steps {
        display: flex;
        justify-content: space-around;
        flex-wrap: wrap;
        gap: 15px;
    }

    .step {
        display: flex;
        flex-direction: column;
        align-items: center;
        flex: 1;
        min-width: 120px;
    }

    .step-icon {
        width: 50px;
        height: 50px;
        background: var(--gradient);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.5rem;
        margin-bottom: 10px;
    }

    .step-text {
        font-size: 0.9rem;
        color: var(--text-light);
        text-align: center;
        line-height: 1.4;
    }

    .action-buttons {
        display: flex;
        gap: 15px;
        margin-top: 20px;
    }

    .action-btn {
        flex: 1;
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

    .action-btn::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
        transition: left 0.7s;
    }

    .action-btn:hover::before {
        left: 100%;
    }

    .action-btn:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
    }

    .action-btn:active {
        transform: translateY(0);
    }

    .print-btn {
        background: var(--gradient-dark);
        color: white;
    }

    .home-btn {
        background: var(--gradient);
        color: white;
    }

    /* PRINT ONLY RECEIPT */
    @media print {
        * {
            -webkit-print-color-adjust: exact !important;
            color-adjust: exact !important;
        }

        body {
            background: white !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        /* Hide background elements */
        .floating-bg,
        .floating-bg * {
            display: none !important;
        }

        /* Hide action buttons */
        .action-buttons {
            display: none !important;
        }

        /* Show and style the receipt */
        .confirmation-container {
            display: block !important;
            position: static !important;
            background: white !important;
            backdrop-filter: none !important;
            border-radius: 0 !important;
            box-shadow: none !important;
            border: none !important;
            animation: none !important;
            transform: none !important;
            opacity: 1 !important;
            margin: 0 !important;
            padding: 20px !important;
            max-width: 100% !important;
            width: 100% !important;
            color: black !important;
            overflow: visible !important;
        }

        .confirmation-container::before {
            display: none !important;
        }

        /* Ensure all text in receipt is visible and black */
        .confirmation-container,
        .confirmation-container *,
        .confirmation-container h1,
        .confirmation-container h3,
        .confirmation-container p,
        .confirmation-container span,
        .confirmation-container div {
            color: black !important;
            background: white !important;
            box-shadow: none !important;
            text-shadow: none !important;
            border-color: black !important;
        }

        .success-icon {
            width: 70px !important;
            height: 70px !important;
            font-size: 32px !important;
            color: black !important;
        }

        .token-value {
            font-size: 3rem !important;
            color: black !important;
            background: white !important;
        }

        .total-amount {
            font-size: 2.5rem !important;
            color: black !important;
            background: white !important;
        }

        /* Ensure gradients don't print */
        [style*="gradient"] {
            background: white !important;
            color: black !important;
        }
    }

    /* Responsive */
    @media (max-width: 600px) {
        body {
            padding: 15px;
        }
        
        .confirmation-container {
            padding: 30px 25px;
        }
        
        .success-icon {
            width: 80px;
            height: 80px;
            font-size: 36px;
        }
        
        .confirmation-header h1 {
            font-size: 2rem;
        }
        
        .token-value {
            font-size: 3rem;
            letter-spacing: 3px;
        }
        
        .order-meta {
            flex-direction: column;
            gap: 15px;
        }
        
        .action-buttons {
            flex-direction: column;
        }
        
        .instruction-steps {
            flex-direction: column;
            align-items: center;
            gap: 20px;
        }
        
        .step {
            min-width: 100%;
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

    <!-- Main Confirmation Container -->
    <div class="confirmation-container print-area">
        <div class="confirmation-header">
            <div class="success-icon">
                <i class="fas fa-check"></i>
            </div>
            <h1>Order Confirmed!</h1>
            <p>Your order has been placed successfully</p>
        </div>

        <div class="shop-info">
            <div class="shop-name">🏪 <?= htmlspecialchars($shop_name) ?></div>
            <p style="color: var(--text-light); margin-top: 5px;">
                <i class="fas fa-clock"></i> Order placed at <?= date('h:i A') ?>
            </p>
        </div>

        <div class="order-meta">
            <div class="meta-item">
                <span class="meta-label">Order ID</span>
                <span class="meta-value">#<?= htmlspecialchars($order_id) ?></span>
            </div>
            <div class="meta-item">
                <span class="meta-label">Status</span>
                <span class="meta-value" style="color: var(--success);">✅ Confirmed</span>
            </div>
        </div>

        <div class="token-display">
            <span class="token-label">Your Order Token</span>
            <div class="token-value"><?= htmlspecialchars($token) ?></div>
            <p style="margin-top: 15px; opacity: 0.9; font-size: 1rem;">
                <i class="fas fa-info-circle"></i> Show this token when collecting your order
            </p>
        </div>

        <div class="order-items">
            <h3 class="items-header">
                <i class="fas fa-receipt"></i> Order Summary
            </h3>
            <div class="items-list">
                <?php 
                mysqli_data_seek($items_q, 0); // Reset pointer
                $subtotal = 0;
                $animation_delay = 0;
                while($item = mysqli_fetch_assoc($items_q)): 
                    $line_total = $item['price'] * $item['quantity'];
                    $subtotal += $line_total;
                    $animation_delay += 0.1;
                ?>
                    <div class="item-row" style="animation-delay: <?= $animation_delay ?>s">
                        <div class="item-name"><?= htmlspecialchars($item['item_name']) ?></div>
                        <div class="item-details">
                            <div class="item-quantity">Quantity: x<?= $item['quantity'] ?></div>
                            <div class="item-price">₹<?= number_format($item['price'], 0) ?></div>
                            <?php if ($item['quantity'] > 1): ?>
                                <div class="line-total">Line total: ₹<?= number_format($line_total, 0) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; 
                $total = $subtotal;
                ?>
            </div>
        </div>

        <div class="total-section">
            <div class="total-label">Total Amount</div>
            <div class="total-amount">₹<?= number_format($total, 0) ?></div>
        </div>

        <div class="instructions">
            <h3><i class="fas fa-info-circle"></i> What Happens Next?</h3>
            <div class="instruction-steps">
                <div class="step">
                    <div class="step-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="step-text">Wait for kitchen to prepare your order</div>
                </div>
                <div class="step">
                    <div class="step-icon">
                        <i class="fas fa-bullhorn"></i>
                    </div>
                    <div class="step-text">We'll announce your token when ready</div>
                </div>
                <div class="step">
                    <div class="step-icon">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="step-text">Show token and collect your order</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="action-buttons">
        <button class="action-btn print-btn" onclick="printReceipt()">
            <i class="fas fa-print"></i> Print Receipt
        </button>
        <button class="action-btn home-btn" onclick="window.location.href='index.php'">
            <i class="fas fa-home"></i> Back to Home
        </button>
    </div>

    <script>
        // Add celebration confetti
        document.addEventListener('DOMContentLoaded', function() {
            createConfetti();
            
            // Add subtle animation to token
            const token = document.querySelector('.token-value');
            setInterval(() => {
                token.style.transform = 'scale(1.02)';
                setTimeout(() => {
                    token.style.transform = 'scale(1)';
                }, 300);
            }, 3000);
        });

        function createConfetti() {
            const colors = ['#FF6B6B', '#4ECDC4', '#FFD166', '#2D3047', '#4CAF50'];
            const container = document.querySelector('body');
            
            for (let i = 0; i < 50; i++) {
                const confetti = document.createElement('div');
                confetti.style.position = 'fixed';
                confetti.style.width = Math.random() * 10 + 5 + 'px';
                confetti.style.height = Math.random() * 10 + 5 + 'px';
                confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                confetti.style.borderRadius = Math.random() > 0.5 ? '50%' : '0';
                confetti.style.left = Math.random() * 100 + 'vw';
                confetti.style.top = '-20px';
                confetti.style.opacity = '0.8';
                confetti.style.zIndex = '9999';
                confetti.style.pointerEvents = 'none';
                container.appendChild(confetti);
                
                // Animate confetti
                const animation = confetti.animate([
                    { transform: 'translateY(0) rotate(0deg)', opacity: 1 },
                    { transform: `translateY(${window.innerHeight}px) rotate(${Math.random() * 720}deg)`, opacity: 0 }
                ], {
                    duration: Math.random() * 3000 + 1000,
                    easing: 'cubic-bezier(0.215, 0.610, 0.355, 1)'
                });
                
                animation.onfinish = () => confetti.remove();
            }
        }

        function printReceipt() {
            // Create a new window for printing
            const printWindow = window.open('', '_blank', 'width=600,height=800');
            
            // Get the receipt content
            const receiptContent = document.querySelector('.confirmation-container').outerHTML;
            
            // Create print-specific HTML
            const printHTML = `
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Order Receipt</title>
                    <style>
                        body {
                            font-family: Arial, sans-serif;
                            margin: 0;
                            padding: 20px;
                            background: white;
                            color: black;
                        }
                        .confirmation-container {
                            background: white;
                            color: black;
                            padding: 20px;
                            max-width: 100%;
                            box-shadow: none;
                            border: none;
                        }
                        .confirmation-container * {
                            color: black !important;
                            background: white !important;
                        }
                        .success-icon {
                            width: 60px;
                            height: 60px;
                            font-size: 28px;
                            color: black;
                        }
                        .token-value {
                            font-size: 2.5rem;
                            color: black;
                            font-weight: bold;
                        }
                        .total-amount {
                            font-size: 2rem;
                            color: black;
                            font-weight: bold;
                        }
                        .item-row {
                            margin: 10px 0;
                            padding: 5px 0;
                            border-bottom: 1px solid #ccc;
                        }
                        .item-name {
                            font-weight: bold;
                        }
                        .item-price {
                            float: right;
                        }
                        @media print {
                            body { margin: 0; }
                        }
                    </style>
                </head>
                <body>
                    ${receiptContent}
                </body>
                </html>
            `;
            
            printWindow.document.write(printHTML);
            printWindow.document.close();
            
            // Wait for content to load then print
            printWindow.onload = function() {
                printWindow.print();
                printWindow.close();
            };
        }
    </script>
</body>
</html>