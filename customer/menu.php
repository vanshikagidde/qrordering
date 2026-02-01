<?php
include "../config/db.php";

// Get shop name from URL
$shop_name = isset($_GET['shop']) ? mysqli_real_escape_string($conn, $_GET['shop']) : '';
$table_no = isset($_GET['table']) ? htmlspecialchars($_GET['table']) : 'Table 1';

// Get shop info
$shop_q = mysqli_query($conn, "SELECT * FROM shops WHERE shop_name='$shop_name'");
if (mysqli_num_rows($shop_q) == 0) {
    die("Shop not found");
}

$shop = mysqli_fetch_assoc($shop_q);
$shop_id = $shop['id'];

// Get menu items
$menu_q = mysqli_query($conn, "SELECT * FROM menu WHERE shop_id='$shop_id' ORDER BY item_name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($shop_name) ?> - Digital Menu</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        /* Simple and Clean Styling */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            color: white;
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Header */
        .header {
            text-align: center;
            padding: 30px 0;
            position: relative;
        }

        .shop-title {
            font-size: 3rem;
            font-weight: 800;
            background: linear-gradient(45deg, #ff6b6b, #ffa726);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
        }

        .table-info {
            display: inline-block;
            background: rgba(255, 255, 255, 0.1);
            padding: 10px 25px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 1.1rem;
            border: 2px solid #ff6b6b;
        }

        /* Promo Banner */
        .promo-banner {
            background: linear-gradient(45deg, #ff6b6b, #ffa726);
            border-radius: 15px;
            padding: 20px;
            margin: 30px 0;
            text-align: center;
            box-shadow: 0 10px 30px rgba(255, 107, 107, 0.3);
        }

        .promo-banner h3 {
            font-size: 1.5rem;
            margin-bottom: 5px;
        }

        /* Menu Grid */
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            margin: 40px 0;
        }

        /* Menu Card */
        .menu-card {
            background: rgba(255, 255, 255, 0.08);
            border-radius: 15px;
            overflow: hidden;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .menu-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            border-color: #ff6b6b;
        }

        .card-image {
            height: 200px;
            width: 100%;
            overflow: hidden;
        }

        .card-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .menu-card:hover .card-image img {
            transform: scale(1.1);
        }

        .card-content {
            padding: 20px;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .item-name {
            font-size: 1.3rem;
            font-weight: 700;
            color: white;
        }

        .price {
            background: linear-gradient(45deg, #ff6b6b, #ffa726);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 1.2rem;
        }

        .description {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.9rem;
            line-height: 1.5;
            margin-bottom: 20px;
            min-height: 40px;
        }

        /* Quantity Controls */
        .quantity-controls {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .qty-selector {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(0, 0, 0, 0.3);
            padding: 8px;
            border-radius: 50px;
        }

        .qty-btn {
            width: 35px;
            height: 35px;
            border: none;
            border-radius: 50%;
            background: linear-gradient(45deg, #ff6b6b, #ffa726);
            color: white;
            font-size: 1rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .qty-btn:hover {
            transform: scale(1.1);
        }

        .qty-display {
            width: 40px;
            text-align: center;
            font-weight: 700;
            color: white;
        }

        .add-to-cart {
            flex: 1;
            padding: 12px;
            background: #4ecdc4;
            border: none;
            border-radius: 10px;
            color: white;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .add-to-cart:hover {
            background: #45b7aa;
            transform: translateY(-3px);
        }

        /* Floating Cart */
        .floating-cart {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1000;
        }

        .cart-button {
            width: 70px;
            height: 70px;
            background: linear-gradient(45deg, #ff6b6b, #ffa726);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 10px 30px rgba(255, 107, 107, 0.5);
            transition: all 0.3s ease;
        }

        .cart-button:hover {
            transform: scale(1.1);
        }

        .cart-count {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #4ecdc4;
            color: white;
            width: 25px;
            height: 25px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.8rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .menu-grid {
                grid-template-columns: 1fr;
            }
            
            .shop-title {
                font-size: 2rem;
            }
            
            .floating-cart {
                bottom: 20px;
                right: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1 class="shop-title"><?= htmlspecialchars($shop_name) ?></h1>
            <div class="table-info">
                <i class="fas fa-chair"></i>
                <?= $table_no ?>
            </div>
        </div>

        <!-- Promo Banner -->
        <div class="promo-banner">
            <h3>🎉 SPECIAL OFFER 🎉</h3>
            <p>Get 10% off on your first order! • Scan QR to order</p>
        </div>

        <!-- Menu Grid -->
        <div class="menu-grid">
            <?php 
            // Sample images for different items
            $sample_images = [
                'Coffee' => 'https://images.unsplash.com/photo-1513558161293-cdaf765ed2fd?w=600&h=400&fit=crop',
                'Burger' => 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=600&h=400&fit=crop',
                'Pizza' => 'https://images.unsplash.com/photo-1565299624946-b28f40a0ca4b?w=600&h=400&fit=crop'
            ];
            
            $default_image = 'https://images.unsplash.com/photo-1513104890138-7c749659a591?w=600&h=400&fit=crop';
            
            if (mysqli_num_rows($menu_q) == 0): ?>
                <div style="text-align: center; grid-column: 1/-1; padding: 50px;">
                    <i class="fas fa-concierge-bell" style="font-size: 4rem; color: #ff6b6b; margin-bottom: 20px;"></i>
                    <h3>Menu Coming Soon!</h3>
                    <p>Delicious dishes will be available soon.</p>
                </div>
            <?php else: 
                while ($item = mysqli_fetch_assoc($menu_q)): 
                    // Get image for this item
                    $image_url = $default_image;
                    foreach ($sample_images as $item_name => $url) {
                        if (stripos($item['item_name'], $item_name) !== false) {
                            $image_url = $url;
                            break;
                        }
                    }
                    
                    // Simple description
                    $description = "Freshly prepared with quality ingredients. Delicious taste you'll love!";
            ?>
            <div class="menu-card" data-id="<?= $item['id'] ?>">
                <div class="card-image">
                    <img src="<?= $image_url ?>" alt="<?= htmlspecialchars($item['item_name']) ?>">
                </div>
                <div class="card-content">
                    <div class="card-header">
                        <h3 class="item-name"><?= htmlspecialchars($item['item_name']) ?></h3>
                        <div class="price">₹<?= number_format($item['price'], 0) ?></div>
                    </div>
                    <p class="description"><?= $description ?></p>
                    <div class="quantity-controls">
                        <div class="qty-selector">
                            <button class="qty-btn minus-btn" data-id="<?= $item['id'] ?>">
                                <i class="fas fa-minus"></i>
                            </button>
                            <div class="qty-display" id="qty<?= $item['id'] ?>">0</div>
                            <button class="qty-btn plus-btn" data-id="<?= $item['id'] ?>">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                        <button class="add-to-cart" data-id="<?= $item['id'] ?>">
                            <i class="fas fa-shopping-cart"></i>
                            ADD
                        </button>
                    </div>
                </div>
            </div>
            <?php endwhile; endif; ?>
        </div>
    </div>

    <!-- Floating Cart -->
    <div class="floating-cart">
        <div class="cart-button" id="cartButton">
            <i class="fas fa-shopping-bag"></i>
            <div class="cart-count" id="cartCount">0</div>
        </div>
    </div>

    <script>
        // Simple Shopping Cart
        class ShoppingCart {
            constructor() {
                this.cart = {};
                this.init();
            }
            
            init() {
                this.setupEventListeners();
            }
            
            setupEventListeners() {
                // Plus button
                document.addEventListener('click', (e) => {
                    if (e.target.closest('.plus-btn')) {
                        const id = e.target.closest('.plus-btn').dataset.id;
                        this.addItem(id, 1);
                        this.animateButton(e.target.closest('.plus-btn'));
                    }
                    
                    // Minus button
                    if (e.target.closest('.minus-btn')) {
                        const id = e.target.closest('.minus-btn').dataset.id;
                        this.removeItem(id, 1);
                    }
                    
                    // Add to cart button
                    if (e.target.closest('.add-to-cart')) {
                        const id = e.target.closest('.add-to-cart').dataset.id;
                        if (!this.cart[id] || this.cart[id] === 0) {
                            this.addItem(id, 1);
                        }
                        this.animateAddButton(e.target.closest('.add-to-cart'));
                    }
                });
                
                // Cart button
                document.getElementById('cartButton').addEventListener('click', () => {
                    this.showOrderSummary();
                });
            }
            
            addItem(id, quantity) {
                const current = this.cart[id] || 0;
                this.cart[id] = current + quantity;
                
                this.updateDisplay(id);
                this.showMessage('Added to cart!', 'success');
            }
            
            removeItem(id, quantity) {
                const current = this.cart[id] || 0;
                const newQty = Math.max(0, current - quantity);
                
                if (newQty === 0) {
                    delete this.cart[id];
                } else {
                    this.cart[id] = newQty;
                }
                
                this.updateDisplay(id);
            }
            
            updateDisplay(id) {
                // Update quantity display
                const qty = this.cart[id] || 0;
                const qtyElement = document.getElementById(`qty${id}`);
                if (qtyElement) {
                    qtyElement.textContent = qty;
                    qtyElement.style.color = qty > 0 ? '#4ecdc4' : 'white';
                }
                
                // Update cart count
                const totalItems = Object.values(this.cart).reduce((a, b) => a + b, 0);
                document.getElementById('cartCount').textContent = totalItems;
                
                // Animate cart button
                const cartBtn = document.getElementById('cartButton');
                cartBtn.style.transform = 'scale(1.2)';
                setTimeout(() => {
                    cartBtn.style.transform = '';
                }, 300);
            }
            
            showOrderSummary() {
                const totalItems = Object.values(this.cart).reduce((a, b) => a + b, 0);
                if (totalItems === 0) {
                    this.showMessage('Your cart is empty!', 'info');
                    return;
                }
                
                // Calculate total
                let totalPrice = 0;
                let itemsHTML = '';
                
                document.querySelectorAll('.menu-card').forEach(card => {
                    const id = card.dataset.id;
                    const qty = this.cart[id] || 0;
                    if (qty > 0) {
                        const name = card.querySelector('.item-name').textContent;
                        const price = parseInt(card.querySelector('.price').textContent.replace('₹', ''));
                        const itemTotal = price * qty;
                        totalPrice += itemTotal;
                        
                        itemsHTML += `
                            <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,0.1);">
                                <div>
                                    <div style="font-weight: 600;">${name}</div>
                                    <div style="font-size: 0.8rem; opacity: 0.7;">₹${price} × ${qty}</div>
                                </div>
                                <div style="font-weight: 700;">₹${itemTotal}</div>
                            </div>
                        `;
                    }
                });
                
                const tax = totalPrice * 0.05;
                const finalTotal = totalPrice + tax;
                
                // Create modal
                const modal = document.createElement('div');
                modal.style.cssText = `
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0,0,0,0.9);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    z-index: 10000;
                    padding: 20px;
                `;
                
                modal.innerHTML = `
                    <div style="background: #1a1a2e; border-radius: 15px; padding: 25px; max-width: 400px; width: 100%; border: 2px solid #ff6b6b;">
                        <h3 style="text-align: center; margin-bottom: 20px; color: white;">
                            <i class="fas fa-receipt"></i> Order Summary
                        </h3>
                        ${itemsHTML}
                        <div style="margin-top: 20px; padding-top: 15px; border-top: 2px solid #ff6b6b;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                <span>Subtotal:</span>
                                <span>₹${totalPrice}</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                <span>Tax (5%):</span>
                                <span>₹${Math.round(tax)}</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; font-size: 1.2rem; font-weight: 700; color: #4ecdc4;">
                                <span>TOTAL:</span>
                                <span>₹${Math.round(finalTotal)}</span>
                            </div>
                        </div>
                        <div style="margin-top: 25px; display: flex; gap: 10px;">
                            <button onclick="shoppingCart.placeOrder()" 
                                style="flex: 1; padding: 12px; background: linear-gradient(45deg, #ff6b6b, #ffa726); border: none; border-radius: 10px; color: white; font-weight: 600; cursor: pointer;">
                                <i class="fas fa-rocket"></i> Place Order
                            </button>
                            <button onclick="this.parentElement.parentElement.parentElement.remove()" 
                                style="padding: 12px 20px; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 10px; color: white; cursor: pointer;">
                                Close
                            </button>
                        </div>
                    </div>
                `;
                
                document.body.appendChild(modal);
            }
            
            async placeOrder() {
                const totalItems = Object.values(this.cart).reduce((a, b) => a + b, 0);
                if (totalItems === 0) {
                    this.showMessage('Cart is empty!', 'warning');
                    return;
                }
                
                try {
                    // Calculate total price
                    let totalPrice = 0;
                    const cartData = {};
                    
                    document.querySelectorAll('.menu-card').forEach(card => {
                        const id = card.dataset.id;
                        const qty = this.cart[id] || 0;
                        if (qty > 0) {
                            const price = parseInt(card.querySelector('.price').textContent.replace('₹', ''));
                            totalPrice += price * qty;
                            cartData[id] = qty;
                        }
                    });
                    
                    // Send to server
                    const response = await fetch("save_cart.php", {
                        method: "POST",
                        headers: { "Content-Type": "application/json" },
                        body: JSON.stringify({
                            shop_id: <?= $shop_id ?>,
                            table_no: "<?= $table_no ?>",
                            cart: cartData,
                            total: totalPrice,
                            timestamp: new Date().toISOString()
                        })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        this.showMessage('Order placed successfully!', 'success');
                        
                        // Clear cart
                        this.cart = {};
                        document.querySelectorAll('.menu-card').forEach(card => {
                            const id = card.dataset.id;
                            document.getElementById(`qty${id}`).textContent = '0';
                            document.getElementById(`qty${id}`).style.color = 'white';
                        });
                        document.getElementById('cartCount').textContent = '0';
                        
                        // Remove modal
                        document.querySelectorAll('div[style*="position: fixed; top: 0"]').forEach(el => el.remove());
                        
                        // Redirect to payment
                        setTimeout(() => {
                            window.location.href = "mock_payment.php?order_id=" + data.order_id;
                        }, 1500);
                    } else {
                        this.showMessage('Order failed: ' + data.message, 'error');
                    }
                } catch (error) {
                    this.showMessage('Error: ' + error.message, 'error');
                }
            }
            
            // Helper functions
            animateButton(btn) {
                btn.style.transform = 'scale(0.9)';
                setTimeout(() => btn.style.transform = '', 200);
            }
            
            animateAddButton(btn) {
                btn.style.transform = 'translateY(-5px)';
                btn.style.boxShadow = '0 15px 25px rgba(78, 205, 196, 0.4)';
                setTimeout(() => {
                    btn.style.transform = '';
                    btn.style.boxShadow = '';
                }, 300);
            }
            
            showMessage(message, type) {
                const colors = {
                    success: '#4ecdc4',
                    error: '#ff6b6b',
                    warning: '#ffa726',
                    info: '#45b7aa'
                };
                
                const msg = document.createElement('div');
                msg.textContent = message;
                msg.style.cssText = `
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: ${colors[type] || colors.info};
                    color: white;
                    padding: 15px 25px;
                    border-radius: 10px;
                    font-weight: 600;
                    z-index: 1000;
                    transform: translateX(100%);
                    transition: transform 0.3s ease;
                    box-shadow: 0 10px 25px rgba(0,0,0,0.3);
                `;
                
                document.body.appendChild(msg);
                
                setTimeout(() => msg.style.transform = 'translateX(0)', 10);
                
                setTimeout(() => {
                    msg.style.transform = 'translateX(100%)';
                    setTimeout(() => msg.remove(), 300);
                }, 3000);
            }
        }
        
        // Start shopping cart
        const shoppingCart = new ShoppingCart();
    </script>
</body>
</html>