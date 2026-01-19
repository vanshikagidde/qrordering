<?php
include "../config/db.php";

/* Validate QR params */
if (!isset($_GET['shop'])) {
    die("Shop parameter missing");
}

$shop_name = isset($_GET['shop']) 
    ? mysqli_real_escape_string($conn, $_GET['shop']) 
    : '';

$table_no = isset($_GET['table']) ? htmlspecialchars($_GET['table']) : 'N/A';

/* Fetch shop */
$shop_q = mysqli_query($conn,
    "SELECT * FROM shops WHERE shop_name='$shop_name'"
);

if (mysqli_num_rows($shop_q) == 0) {
    die("Shop not found");
}

$shop = mysqli_fetch_assoc($shop_q);
$shop_id = $shop['id'];

/* Fetch menu */
$menu_q = mysqli_query($conn,
    "SELECT * FROM menu WHERE shop_id='$shop_id'"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($shop_name) ?> Menu</title>
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
            --shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            --shadow-hover: 0 15px 40px rgba(0, 0, 0, 0.12);
            --gradient: linear-gradient(135deg, #FF6B6B 0%, #FF8E8E 100%);
            --gradient-accent: linear-gradient(135deg, #FFD166 0%, #FFE8A0 100%);
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

        /* Header */
        .header {
            background: var(--gradient);
            color: white;
            padding: 25px 20px;
            text-align: center;
            position: relative;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(255, 107, 107, 0.3);
            animation: slideDown 0.8s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z' fill='%23ffffff' fill-opacity='0.1' fill-rule='evenodd'/%3E%3C/svg%3E");
            opacity: 0.3;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-100%);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .shop-icon {
            width: 70px;
            height: 70px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            animation: pulse 2s infinite;
            border: 3px solid rgba(255, 255, 255, 0.3);
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); box-shadow: 0 0 20px rgba(255, 255, 255, 0.3); }
        }

        .shop-icon i {
            font-size: 28px;
        }

        .header h2 {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 8px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            letter-spacing: -0.5px;
        }

        .header p {
            font-size: 1.2rem;
            opacity: 0.9;
            font-weight: 500;
            background: rgba(255, 255, 255, 0.2);
            display: inline-block;
            padding: 8px 20px;
            border-radius: 30px;
            backdrop-filter: blur(5px);
        }

        /* Container */
        .container {
            max-width: 700px;
            margin: 0 auto;
            padding: 25px 20px 100px;
        }

        /* Menu Items */
        .items-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }

        .item-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            opacity: 0;
            transform: translateY(30px);
            animation: fadeUp 0.6s forwards;
            border: 1px solid rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(10px);
        }

        .item-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: var(--gradient);
        }

        @keyframes fadeUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .item-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-hover);
        }

        .item-card:nth-child(1) { animation-delay: 0.1s; }
        .item-card:nth-child(2) { animation-delay: 0.2s; }
        .item-card:nth-child(3) { animation-delay: 0.3s; }
        .item-card:nth-child(4) { animation-delay: 0.4s; }
        .item-card:nth-child(5) { animation-delay: 0.5s; }
        .item-card:nth-child(6) { animation-delay: 0.6s; }
        .item-card:nth-child(7) { animation-delay: 0.7s; }
        .item-card:nth-child(8) { animation-delay: 0.8s; }

        .item-content {
            padding: 25px;
        }

        .item-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .item-name {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--dark);
            line-height: 1.3;
        }

        .item-price {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--primary);
            background: linear-gradient(to right, var(--primary), var(--primary-light));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .item-description {
            color: var(--text-light);
            font-size: 0.95rem;
            line-height: 1.5;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px dashed var(--gray);
        }

        /* Quantity Controls */
        .qty-controls {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .qty-box {
            display: flex;
            align-items: center;
            background: linear-gradient(135deg, #f8fafc 0%, #edf2f7 100%);
            border-radius: 50px;
            padding: 5px;
            box-shadow: inset 0 2px 5px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--gray);
        }

        .qty-btn {
            width: 40px;
            height: 40px;
            border: none;
            border-radius: 50%;
            background: white;
            color: var(--primary);
            font-size: 1.3rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.1);
        }

        .qty-btn:hover {
            background: var(--primary);
            color: white;
            transform: scale(1.1);
        }

        .qty-btn:active {
            transform: scale(0.95);
        }

        .qty-display {
            width: 50px;
            text-align: center;
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--dark);
            transition: all 0.3s ease;
        }

        .qty-display.active {
            color: var(--primary);
            animation: pop 0.3s ease;
        }

        @keyframes pop {
            0% { transform: scale(1); }
            50% { transform: scale(1.3); }
            100% { transform: scale(1); }
        }

        .add-to-cart {
            background: var(--gradient);
            color: white;
            border: none;
            border-radius: 50px;
            padding: 12px 25px;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 5px 15px rgba(255, 107, 107, 0.3);
        }

        .add-to-cart:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(255, 107, 107, 0.4);
        }

        .add-to-cart:active {
            transform: translateY(0);
        }

        .add-to-cart.added {
            background: var(--gradient-accent);
            color: var(--dark);
            animation: celebrate 0.5s ease;
        }

        @keyframes celebrate {
            0% { transform: scale(1); }
            50% { transform: scale(1.05) rotate(5deg); }
            100% { transform: scale(1); }
        }

        /* Cart Button */
        .cart-btn {
            position: fixed;
            bottom: 25px;
            left: 50%;
            transform: translateX(-50%);
            width: 90%;
            max-width: 500px;
            padding: 22px 30px;
            background: var(--gradient-dark);
            color: white;
            border: none;
            border-radius: 15px;
            font-size: 1.3rem;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 10px 30px rgba(45, 48, 71, 0.3);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            z-index: 100;
            overflow: hidden;
        }

        .cart-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.7s;
        }

        .cart-btn:hover::before {
            left: 100%;
        }

        .cart-btn:hover {
            transform: translateX(-50%) translateY(-5px);
            box-shadow: 0 15px 40px rgba(45, 48, 71, 0.4);
        }

        .cart-btn:active {
            transform: translateX(-50%) translateY(0);
        }

        .cart-btn.has-items {
            background: var(--gradient);
            animation: pulse-glow 2s infinite;
        }

        @keyframes pulse-glow {
            0%, 100% { box-shadow: 0 10px 30px rgba(255, 107, 107, 0.3); }
            50% { box-shadow: 0 15px 40px rgba(255, 107, 107, 0.5); }
        }

        .cart-count {
            background: white;
            color: var(--primary);
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            font-weight: 800;
            animation: bounceIn 0.5s;
        }

        @keyframes bounceIn {
            0% { transform: scale(0); }
            70% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }

        /* Empty State */
        .empty-menu {
            text-align: center;
            padding: 60px 20px;
            animation: fadeIn 1s ease;
        }

        .empty-icon {
            font-size: 4rem;
            color: var(--gray);
            margin-bottom: 20px;
            animation: float 3s ease-in-out infinite;
        }

        .empty-menu h3 {
            font-size: 1.8rem;
            color: var(--dark);
            margin-bottom: 10px;
        }

        .empty-menu p {
            color: var(--text-light);
            font-size: 1.1rem;
        }

        /* Loading Animation */
        .loading {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.95);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            flex-direction: column;
        }

        .loading.active {
            display: flex;
            animation: fadeIn 0.3s ease;
        }

        .spinner {
            width: 60px;
            height: 60px;
            border: 5px solid var(--gray);
            border-top: 5px solid var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 20px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .items-grid {
                grid-template-columns: 1fr;
            }
            
            .header h2 {
                font-size: 1.8rem;
            }
            
            .item-card {
                max-width: 100%;
            }
            
            .cart-btn {
                width: 95%;
                padding: 20px;
                font-size: 1.2rem;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 20px 15px 100px;
            }
            
            .item-content {
                padding: 20px;
            }
            
            .qty-controls {
                flex-direction: column;
                gap: 15px;
                align-items: stretch;
            }
            
            .add-to-cart {
                justify-content: center;
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

    <!-- Header -->
    <div class="header">
        <div class="shop-icon">
            <i class="fas fa-utensils"></i>
        </div>
        <h2><?= htmlspecialchars($shop_name) ?></h2>
        <p><i class="fas fa-chair"></i> Table <?= $table_no ?></p>
    </div>

    <!-- Container -->
    <div class="container">
        <?php if (mysqli_num_rows($menu_q) == 0) { ?>
            <div class="empty-menu">
                <div class="empty-icon">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <h3>Menu Coming Soon</h3>
                <p>We're preparing something delicious for you!</p>
            </div>
        <?php } else { ?>
            <div class="items-grid">
                <?php while ($item = mysqli_fetch_assoc($menu_q)) { 
                    // Check if item has description in database, otherwise use placeholder
                    $description = isset($item['description']) && !empty($item['description']) ? 
                                   htmlspecialchars($item['description']) : 
                                   "Delicious item prepared with care";
                ?>
                    <div class="item-card">
                        <div class="item-content">
                            <div class="item-header">
                                <h3 class="item-name"><?= htmlspecialchars($item['item_name']) ?></h3>
                                <div class="item-price">₹<?= number_format($item['price'], 0) ?></div>
                            </div>
                            <p class="item-description"><?= $description ?></p>
                            <div class="qty-controls">
                                <div class="qty-box">
                                    <button class="qty-btn minus-btn" data-id="<?= $item['id'] ?>">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                    <div class="qty-display" id="qty<?= $item['id'] ?>">0</div>
                                    <button class="qty-btn plus-btn" data-id="<?= $item['id'] ?>">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                                <button class="add-to-cart" data-id="<?= $item['id'] ?>">
                                    <i class="fas fa-cart-plus"></i> Add to Cart
                                </button>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>
        <?php } ?>
    </div>

    <!-- Cart Button -->
    <button class="cart-btn" id="cartBtn">
        <i class="fas fa-shopping-cart"></i>
        <span id="cartText">Place Order</span>
        <div class="cart-count" id="cartCount" style="display: none;">0</div>
    </button>

    <!-- Loading Overlay -->
    <div class="loading" id="loading">
        <div class="spinner"></div>
        <h3>Processing Your Order</h3>
        <p>Please wait a moment...</p>
    </div>

    <script>
        let cart = {};
        let totalItems = 0;

        // Initialize animations
        document.addEventListener('DOMContentLoaded', function() {
            // Animate items on scroll
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.animationDelay = '0s';
                        entry.target.style.animationPlayState = 'running';
                        observer.unobserve(entry.target);
                    }
                });
            }, observerOptions);
            
            document.querySelectorAll('.item-card').forEach(card => {
                observer.observe(card);
            });
            
            updateCartButton();
        });

        // Quantity change functions
        function changeQty(id, change) {
            const qtyElement = document.getElementById(`qty${id}`);
            const addButton = document.querySelector(`.add-to-cart[data-id="${id}"]`);
            let currentQty = parseInt(qtyElement.textContent);
            let newQty = currentQty + change;
            
            if (newQty < 0) newQty = 0;
            
            qtyElement.textContent = newQty;
            qtyElement.classList.add('active');
            
            setTimeout(() => {
                qtyElement.classList.remove('active');
            }, 300);
            
            // Update cart
            if (newQty === 0) {
                delete cart[id];
                if (addButton) {
                    addButton.classList.remove('added');
                    addButton.innerHTML = '<i class="fas fa-cart-plus"></i> Add to Cart';
                }
            } else {
                cart[id] = newQty;
                if (addButton && change > 0) {
                    addButton.classList.add('added');
                    addButton.innerHTML = '<i class="fas fa-check"></i> Added!';
                    
                    setTimeout(() => {
                        if (addButton) {
                            addButton.classList.remove('added');
                            addButton.innerHTML = '<i class="fas fa-cart-plus"></i> Update Cart';
                        }
                    }, 1500);
                }
            }
            
            updateCartButton();
            
            // Add animation to button
            if (change > 0) {
                const plusBtn = document.querySelector(`.plus-btn[data-id="${id}"]`);
                if (plusBtn) {
                    plusBtn.style.animation = 'none';
                    setTimeout(() => {
                        plusBtn.style.animation = 'celebrate 0.3s ease';
                    }, 10);
                }
            }
        }

        // Update cart button display
        function updateCartButton() {
            const cartBtn = document.getElementById('cartBtn');
            const cartCount = document.getElementById('cartCount');
            const cartText = document.getElementById('cartText');
            
            totalItems = Object.values(cart).reduce((sum, qty) => sum + qty, 0);
            
            if (totalItems > 0) {
                cartBtn.classList.add('has-items');
                cartCount.style.display = 'flex';
                cartCount.textContent = totalItems;
                cartText.textContent = `Place Order (${totalItems} items)`;
            } else {
                cartBtn.classList.remove('has-items');
                cartCount.style.display = 'none';
                cartText.textContent = 'Place Order';
            }
        }

        // Add to cart button click
        document.addEventListener('click', function(e) {
            if (e.target.closest('.plus-btn')) {
                const id = e.target.closest('.plus-btn').dataset.id;
                changeQty(id, 1);
            }
            
            if (e.target.closest('.minus-btn')) {
                const id = e.target.closest('.minus-btn').dataset.id;
                changeQty(id, -1);
            }
            
            if (e.target.closest('.add-to-cart')) {
                const id = e.target.closest('.add-to-cart').dataset.id;
                const currentQty = parseInt(document.getElementById(`qty${id}`).textContent);
                if (currentQty === 0) {
                    changeQty(id, 1);
                } else {
                    // Just show confirmation
                    const btn = e.target.closest('.add-to-cart');
                    btn.classList.add('added');
                    btn.innerHTML = '<i class="fas fa-check"></i> In Cart!';
                    
                    setTimeout(() => {
                        btn.classList.remove('added');
                        btn.innerHTML = '<i class="fas fa-cart-plus"></i> Update Cart';
                    }, 1000);
                }
            }
        });

        // Place order function
        async function placeOrder() {
            if (totalItems === 0) {
                // Show error animation
                const cartBtn = document.getElementById('cartBtn');
                cartBtn.style.animation = 'shake 0.5s ease';
                setTimeout(() => {
                    cartBtn.style.animation = '';
                }, 500);
                
                // Show message
                const originalText = document.getElementById('cartText').textContent;
                document.getElementById('cartText').textContent = 'Add items first!';
                setTimeout(() => {
                    document.getElementById('cartText').textContent = originalText;
                }, 1500);
                return;
            }

            // Show loading
            document.getElementById('loading').classList.add('active');
            
            try {
                const response = await fetch("save_cart.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({
                        shop_id: <?= $shop_id ?>,
                        table_no: "<?= $table_no ?>",
                        cart: cart
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Success animation
                    document.getElementById('loading').innerHTML = `
                        <div style="text-align: center;">
                            <div style="font-size: 4rem; color: #4ECDC4; margin-bottom: 20px;">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <h3>Order Placed Successfully!</h3>
                            <p>Redirecting to payment...</p>
                        </div>
                    `;
                    
                    setTimeout(() => {
                        window.location.href = "mock_payment.php";
                    }, 1500);
                } else {
                    throw new Error(data.message || "Order failed");
                }
            } catch (error) {
                document.getElementById('loading').classList.remove('active');
                
                // Show error message
                const cartBtn = document.getElementById('cartBtn');
                const originalHTML = cartBtn.innerHTML;
                cartBtn.innerHTML = `<i class="fas fa-exclamation-circle"></i> Order Failed`;
                cartBtn.style.background = 'linear-gradient(135deg, #FF4757 0%, #FF6B6B 100%)';
                
                setTimeout(() => {
                    cartBtn.innerHTML = originalHTML;
                    cartBtn.style.background = '';
                    updateCartButton();
                }, 2000);
                
                console.error("Order error:", error);
            }
        }

        // Attach placeOrder to cart button
        document.getElementById('cartBtn').addEventListener('click', placeOrder);

        // Add shake animation for error
        const style = document.createElement('style');
        style.textContent = `
            @keyframes shake {
                0%, 100% { transform: translateX(-50%) translateY(0); }
                10%, 30%, 50%, 70%, 90% { transform: translateX(-50%) translateY(0) translateX(-5px); }
                20%, 40%, 60%, 80% { transform: translateX(-50%) translateY(0) translateX(5px); }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>