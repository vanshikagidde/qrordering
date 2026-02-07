<?php
include "../config/db.php";

$token    = $_GET['token'] ?? '';
$order_id = $_GET['order_id'] ?? '';
$shop_id  = $_GET['shop_id'] ?? '';

if (!$token || !$shop_id || !$order_id) {
    die("Invalid request");
}

/* Fetch shop name - Using prepared statement */
$stmt = $conn->prepare("SELECT shop_name FROM shops WHERE id = ?");
$stmt->bind_param("i", $shop_id);
$stmt->execute();
$result = $stmt->get_result();
$shop = $result->fetch_assoc();
$shop_name = $shop ? $shop['shop_name'] : 'Unknown Shop';

/* Fetch ordered items - Using prepared statement */
$stmt_items = $conn->prepare("SELECT m.item_name, m.price, oi.quantity 
    FROM order_item oi 
    JOIN menu m ON oi.item_id = m.id 
    WHERE oi.order_id = ?");
$stmt_items->bind_param("i", $order_id);
$stmt_items->execute();
$items_q = $stmt_items->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Order Confirmed | <?= htmlspecialchars($shop_name) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #10B981;
            --primary-dark: #059669;
            --primary-light: #D1FAE5;
            --accent: #F59E0B;
            --dark: #111827;
            --gray-900: #1F2937;
            --gray-700: #374151;
            --gray-500: #6B7280;
            --gray-300: #D1D5DB;
            --gray-100: #F3F4F6;
            --gray-50: #F9FAFB;
            --white: #FFFFFF;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(180deg, var(--gray-50) 0%, var(--gray-100) 100%);
            color: var(--dark);
            min-height: 100vh;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }

        /* Success Animation Overlay */
        .success-overlay {
            position: fixed;
            inset: 0;
            background: var(--primary);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: fadeOut 0.5s ease-out 1.5s forwards;
            pointer-events: none;
        }

        @keyframes fadeOut {
            to { opacity: 0; visibility: hidden; }
        }

        .success-check {
            width: 80px;
            height: 80px;
            background: var(--white);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: scaleIn 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .success-check i {
            font-size: 40px;
            color: var(--primary);
            animation: checkPop 0.4s ease-out 0.3s both;
        }

        @keyframes scaleIn {
            from { transform: scale(0); }
            to { transform: scale(1); }
        }

        @keyframes checkPop {
            from { transform: scale(0) rotate(-45deg); }
            to { transform: scale(1) rotate(0); }
        }

        /* Main Container */
        .container {
            max-width: 480px;
            margin: 0 auto;
            padding: 24px 16px;
            animation: slideUp 0.6s ease-out 0.3s both;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Header */
        .header {
            text-align: center;
            margin-bottom: 24px;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--primary-light);
            color: var(--primary-dark);
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 16px;
        }

        .status-badge::before {
            content: '';
            width: 6px;
            height: 6px;
            background: var(--primary);
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        h1 {
            font-size: 24px;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 8px;
            letter-spacing: -0.025em;
        }

        .subtitle {
            color: var(--gray-500);
            font-size: 14px;
        }

        /* Card Component */
        .card {
            background: var(--white);
            border-radius: 16px;
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            margin-bottom: 16px;
        }

        /* Shop Header */
        .shop-header {
            background: linear-gradient(135deg, var(--gray-900) 0%, var(--gray-700) 100%);
            color: var(--white);
            padding: 20px;
            text-align: center;
        }

        .shop-icon {
            width: 48px;
            height: 48px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 12px;
            font-size: 24px;
            backdrop-filter: blur(10px);
        }

        .shop-name {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .order-time {
            font-size: 13px;
            opacity: 0.8;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        /* Token Section */
        .token-section {
            padding: 24px 20px;
            text-align: center;
            background: linear-gradient(180deg, var(--primary-light) 0%, var(--white) 100%);
            border-bottom: 2px dashed var(--gray-300);
        }

        .token-label {
            font-size: 12px;
            font-weight: 600;
            color: var(--gray-500);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .token-value {
            font-size: 48px;
            font-weight: 800;
            color: var(--dark);
            font-family: 'Courier New', monospace;
            letter-spacing: 4px;
            line-height: 1;
            margin-bottom: 8px;
        }

        .token-hint {
            font-size: 13px;
            color: var(--gray-500);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        /* Order Meta */
        .order-meta {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1px;
            background: var(--gray-100);
        }

        .meta-item {
            background: var(--white);
            padding: 16px;
            text-align: center;
        }

        .meta-label {
            font-size: 11px;
            color: var(--gray-500);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }

        .meta-value {
            font-size: 14px;
            font-weight: 700;
            color: var(--dark);
        }

        /* Items Section */
        .items-section {
            padding: 20px;
        }

        .section-title {
            font-size: 14px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-title i {
            color: var(--gray-500);
        }

        .item-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 12px 0;
            border-bottom: 1px solid var(--gray-100);
            animation: fadeInLeft 0.4s ease-out both;
        }

        .item-row:nth-child(1) { animation-delay: 0.1s; }
        .item-row:nth-child(2) { animation-delay: 0.2s; }
        .item-row:nth-child(3) { animation-delay: 0.3s; }
        .item-row:nth-child(4) { animation-delay: 0.4s; }
        .item-row:nth-child(5) { animation-delay: 0.5s; }

        @keyframes fadeInLeft {
            from { opacity: 0; transform: translateX(-10px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .item-row:last-child {
            border-bottom: none;
        }

        .item-info {
            flex: 1;
        }

        .item-name {
            font-size: 14px;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 4px;
        }

        .item-quantity {
            font-size: 13px;
            color: var(--gray-500);
        }

        .item-price-group {
            text-align: right;
        }

        .item-unit-price {
            font-size: 13px;
            color: var(--gray-500);
        }

        .item-total {
            font-size: 14px;
            font-weight: 700;
            color: var(--dark);
        }

        /* Total Section */
        .total-section {
            background: var(--gray-50);
            padding: 16px 20px;
            border-top: 2px solid var(--gray-100);
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .total-label {
            font-size: 14px;
            font-weight: 600;
            color: var(--gray-700);
        }

        .total-amount {
            font-size: 24px;
            font-weight: 800;
            color: var(--primary-dark);
        }

        /* Instructions */
        .instructions-card {
            background: var(--white);
            border-radius: 16px;
            padding: 20px;
            box-shadow: var(--shadow);
            margin-bottom: 16px;
        }

        .instructions-title {
            font-size: 14px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 16px;
            text-align: center;
        }

        .steps {
            display: flex;
            justify-content: space-between;
            position: relative;
        }

        .steps::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 20%;
            right: 20%;
            height: 2px;
            background: var(--gray-200);
            z-index: 0;
        }

        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            flex: 1;
            position: relative;
            z-index: 1;
        }

        .step-number {
            width: 40px;
            height: 40px;
            background: var(--white);
            border: 2px solid var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 8px;
        }

        .step-label {
            font-size: 11px;
            color: var(--gray-500);
            text-align: center;
            line-height: 1.3;
            max-width: 80px;
        }

        /* Actions */
        .actions {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .btn {
            width: 100%;
            padding: 14px 24px;
            border: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-primary {
            background: var(--dark);
            color: var(--white);
        }

        .btn-primary:hover {
            background: var(--gray-900);
            transform: translateY(-1px);
            box-shadow: var(--shadow-lg);
        }

        .btn-secondary {
            background: var(--white);
            color: var(--gray-700);
            border: 1px solid var(--gray-300);
        }

        .btn-secondary:hover {
            background: var(--gray-50);
            border-color: var(--gray-400);
        }

        /* Print Styles */
        @media print {
            body { background: white; }
            .success-overlay, .actions, .instructions-card { display: none; }
            .container { max-width: 100%; padding: 0; }
            .card { box-shadow: none; border: 1px solid #ddd; }
        }

        /* Mobile Optimizations */
        @media (max-width: 380px) {
            .token-value { font-size: 36px; letter-spacing: 2px; }
            h1 { font-size: 20px; }
            .step-label { font-size: 10px; }
        }
    </style>
</head>
<body>
    <!-- Success Animation -->
    <div class="success-overlay">
        <div class="success-check">
            <i class="fas fa-check"></i>
        </div>
    </div>

    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="status-badge">Order Confirmed</div>
            <h1>Thank You!</h1>
            <p class="subtitle">Your order has been placed successfully</p>
        </div>

        <!-- Main Card -->
        <div class="card">
            <!-- Shop Header -->
            <div class="shop-header">
                <div class="shop-icon">
                    <i class="fas fa-store"></i>
                </div>
                <div class="shop-name"><?= htmlspecialchars($shop_name) ?></div>
                <div class="order-time">
                    <i class="far fa-clock"></i>
                    <span><?= date('M j, Y • h:i A') ?></span>
                </div>
            </div>

            <!-- Token Section -->
            <div class="token-section">
                <div class="token-label">Your Order Token</div>
                <div class="token-value"><?= htmlspecialchars(strtoupper($token)) ?></div>
                <div class="token-hint">
                    <i class="fas fa-info-circle"></i>
                    <span>Show this when collecting</span>
                </div>
            </div>

            <!-- Order Meta -->
            <div class="order-meta">
                <div class="meta-item">
                    <div class="meta-label">Order ID</div>
                    <div class="meta-value">#<?= htmlspecialchars($order_id) ?></div>
                </div>
                <div class="meta-item">
                    <div class="meta-label">Status</div>
                    <div class="meta-value" style="color: var(--primary);">Confirmed</div>
                </div>
            </div>

            <!-- Items Section -->
            <div class="items-section">
                <div class="section-title">
                    <i class="fas fa-receipt"></i>
                    Order Summary
                </div>
                <div class="items-list">
                    <?php 
                    $subtotal = 0;
                    while($item = $items_q->fetch_assoc()): 
                        $line_total = $item['price'] * $item['quantity'];
                        $subtotal += $line_total;
                    ?>
                        <div class="item-row">
                            <div class="item-info">
                                <div class="item-name"><?= htmlspecialchars($item['item_name']) ?></div>
                                <div class="item-quantity">Qty: <?= $item['quantity'] ?></div>
                            </div>
                            <div class="item-price-group">
                                <div class="item-unit-price">₹<?= number_format($item['price'], 0) ?> each</div>
                                <div class="item-total">₹<?= number_format($line_total, 0) ?></div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>

            <!-- Total -->
            <div class="total-section">
                <div class="total-row">
                    <span class="total-label">Total Amount</span>
                    <span class="total-amount">₹<?= number_format($subtotal, 0) ?></span>
                </div>
            </div>
        </div>

        <!-- Instructions -->
        <div class="instructions-card">
            <div class="instructions-title">What happens next?</div>
            <div class="steps">
                <div class="step">
                    <div class="step-number">1</div>
                    <div class="step-label">Kitchen prepares</div>
                </div>
                <div class="step">
                    <div class="step-number">2</div>
                    <div class="step-label">Token called</div>
                </div>
                <div class="step">
                    <div class="step-number">3</div>
                    <div class="step-label">Collect order</div>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="actions">
            <button class="btn btn-primary" onclick="window.print()">
                <i class="fas fa-print"></i>
                Print Receipt
            </button>
            <button class="btn btn-secondary" onclick="window.location.href='index.php'">
                <i class="fas fa-arrow-left"></i>
                Back to Menu
            </button>
        </div>
    </div>

    <script>
        // Prevent zoom on double tap
        document.addEventListener('dblclick', function(event) {
            event.preventDefault();
        }, { passive: false });

        // Add haptic feedback simulation
        document.querySelectorAll('.btn').forEach(btn => {
            btn.addEventListener('click', function() {
                this.style.transform = 'scale(0.98)';
                setTimeout(() => {
                    this.style.transform = '';
                }, 100);
            });
        });
    </script>
</body>
</html>