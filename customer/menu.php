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

// Enhanced food images database
$food_images = [
    'coffee' => 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?w=600&h=400&fit=crop',
    'tea' => 'https://images.unsplash.com/photo-1544787219-7f47ccb76574?w=600&h=400&fit=crop',
    'coke' => 'https://images.unsplash.com/photo-1622483767028-3f66f32aef97?w=600&h=400&fit=crop',
    'soda' => 'https://images.unsplash.com/photo-1625772299848-391b6a87d7b3?w=600&h=400&fit=crop',
    'juice' => 'https://images.unsplash.com/photo-1600271886742-f049cd451bba?w=600&h=400&fit=crop',
    'milkshake' => 'https://images.unsplash.com/photo-1572490122747-3968b75cc699?w=600&h=400&fit=crop',
    'water' => 'https://images.unsplash.com/photo-1548839140-29a749e1cf4d?w=600&h=400&fit=crop',
    'burger' => 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=600&h=400&fit=crop',
    'cheeseburger' => 'https://images.unsplash.com/photo-1594212699903-ec8a3eca50f5?w=600&h=400&fit=crop',
    'fries' => 'https://images.unsplash.com/photo-1573080496219-bb080dd4f877?w=600&h=400&fit=crop',
    'hotdog' => 'https://images.unsplash.com/photo-1612392062126-2f3db5023f3e?w=600&h=400&fit=crop',
    'sandwich' => 'https://images.unsplash.com/photo-1528735602780-2552fd46c7af?w=600&h=400&fit=crop',
    'pizza' => 'https://images.unsplash.com/photo-1565299624946-b28f40a0ca4b?w=600&h=400&fit=crop',
    'cheese pizza' => 'https://images.unsplash.com/photo-1513104890138-7c749659a591?w=600&h=400&fit=crop',
    'pepperoni' => 'https://images.unsplash.com/photo-1628840042765-356cda07504e?w=600&h=400&fit=crop',
    'pasta' => 'https://images.unsplash.com/photo-1621996346565-e3dbc646d9a9?w=600&h=400&fit=crop',
    'spaghetti' => 'https://images.unsplash.com/photo-1552611052-33e04de081de?w=600&h=400&fit=crop',
    'lasagna' => 'https://images.unsplash.com/photo-1574868235872-c5529e6e6e6e?w=600&h=400&fit=crop',
    'biryani' => 'https://images.unsplash.com/photo-1589302168068-964664d93dc0?w=600&h=400&fit=crop',
    'curry' => 'https://images.unsplash.com/photo-1565557623262-b51c2513a641?w=600&h=400&fit=crop',
    'naan' => 'https://images.unsplash.com/photo-1601050690597-df0568f70950?w=600&h=400&fit=crop',
    'samosa' => 'https://images.unsplash.com/photo-1601050690117-94f5f6fa8bd1?w=600&h=400&fit=crop',
    'tikka' => 'https://images.unsplash.com/photo-1567188040759-fb8a883dc6d8?w=600&h=400&fit=crop',
    'tandoori' => 'https://images.unsplash.com/photo-1599487488170-d11ec9c172f0?w=600&h=400&fit=crop',
    'sushi' => 'https://images.unsplash.com/photo-1579871494447-9811cf80d66c?w=600&h=400&fit=crop',
    'noodles' => 'https://images.unsplash.com/photo-1552611052-33e04de081de?w=600&h=400&fit=crop',
    'ramen' => 'https://images.unsplash.com/photo-1569718212165-3a8278d5f624?w=600&h=400&fit=crop',
    'dumplings' => 'https://images.unsplash.com/photo-1496116218417-1a781b1c416c?w=600&h=400&fit=crop',
    'spring roll' => 'https://images.unsplash.com/photo-1534422298391-e4f8c172dddb?w=600&h=400&fit=crop',
    'taco' => 'https://images.unsplash.com/photo-1551504734-5ee1c4a1479b?w=600&h=400&fit=crop',
    'burrito' => 'https://images.unsplash.com/photo-1626700051175-6818013e1d4f?w=600&h=400&fit=crop',
    'nachos' => 'https://images.unsplash.com/photo-1513456852971-30c0b8199d4d?w=600&h=400&fit=crop',
    'quesadilla' => 'https://images.unsplash.com/photo-1613514785611-03642da13457?w=600&h=400&fit=crop',
    'cake' => 'https://images.unsplash.com/photo-1578985545062-69928b1d9587?w=600&h=400&fit=crop',
    'ice cream' => 'https://images.unsplash.com/photo-1497034825429-c343d7c6a68f?w=600&h=400&fit=crop',
    'brownie' => 'https://images.unsplash.com/photo-1606313564200-e75d5e5fd76d?w=600&h=400&fit=crop',
    'cookie' => 'https://images.unsplash.com/photo-1499636136210-6f4ee915583e?w=600&h=400&fit=crop',
    'donut' => 'https://images.unsplash.com/photo-1551024601-bec78aea704b?w=600&h=400&fit=crop',
    'pancake' => 'https://images.unsplash.com/photo-1567620905732-2d1ec7ab7445?w=600&h=400&fit=crop',
    'waffle' => 'https://images.unsplash.com/photo-1562376552-0d160a2f238d?w=600&h=400&fit=crop',
    'omelette' => 'https://images.unsplash.com/photo-1510693206972-df098062cb71?w=600&h=400&fit=crop',
    'toast' => 'https://images.unsplash.com/photo-1484723091739-30a097e8f929?w=600&h=400&fit=crop',
    'croissant' => 'https://images.unsplash.com/photo-1555507036-ab1f4038808a?w=600&h=400&fit=crop',
    'salad' => 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=600&h=400&fit=crop',
    'wrap' => 'https://images.unsplash.com/photo-1626700051175-6818013e1d4f?w=600&h=400&fit=crop',
    'fish' => 'https://images.unsplash.com/photo-1519708227418-c8fd9a32b7a2?w=600&h=400&fit=crop',
    'shrimp' => 'https://images.unsplash.com/photo-1565680018434-b513d5e5fd47?w=600&h=400&fit=crop',
    'default' => 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=600&h=400&fit=crop'
];

function getFoodImage($item_name, $food_images) {
    $item_lower = strtolower($item_name);
    foreach ($food_images as $keyword => $url) {
        if (strpos($item_lower, $keyword) !== false) {
            return $url;
        }
    }
    return $food_images['default'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($shop_name) ?> - Menu</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #F97316;
            --primary-light: #FB923C;
            --primary-dark: #EA580C;
            --text: #1E293B;
            --text-light: #64748B;
            --border: #E2E8F0;
            --bg: #FAFAFA;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #ffffff;
            color: var(--text);
            min-height: 100vh;
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
            width: 400px;
            height: 400px;
            background: rgba(249, 115, 22, 0.15);
            top: -100px;
            right: -100px;
            animation: float1 20s infinite ease-in-out;
        }

        .shape-2 {
            width: 300px;
            height: 300px;
            background: rgba(251, 146, 60, 0.12);
            bottom: 10%;
            left: -50px;
            animation: float2 25s infinite ease-in-out;
        }

        .shape-3 {
            width: 250px;
            height: 250px;
            background: rgba(234, 88, 12, 0.1);
            top: 40%;
            right: 10%;
            animation: float3 18s infinite ease-in-out;
        }

        .shape-4 {
            width: 350px;
            height: 350px;
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

        /* Container */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            position: relative;
            z-index: 1;
        }

        /* Header */
        .header {
            text-align: center;
            padding: 30px 0 20px;
            position: relative;
        }

        .shop-title {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--text);
            margin-bottom: 10px;
            letter-spacing: -0.02em;
        }

        .table-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(249, 115, 22, 0.1);
            color: var(--primary-dark);
            padding: 10px 24px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 0.95rem;
            border: 2px solid rgba(249, 115, 22, 0.2);
        }

        /* Simple Clean Card */
        .menu-card {
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid var(--border);
            transition: box-shadow 0.3s ease;
            margin-bottom: 20px;
        }

        .menu-card:hover {
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        }

        .card-image {
            width: 100%;
            height: 200px;
            overflow: hidden;
            position: relative;
        }

        .card-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .card-content {
            padding: 20px;
        }

        .item-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 8px;
        }

        .item-name {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text);
            flex: 1;
            padding-right: 15px;
        }

        .item-price {
            font-size: 1.35rem;
            font-weight: 800;
            color: var(--primary);
        }

        .item-desc {
            color: var(--text-light);
            font-size: 0.9rem;
            margin-bottom: 15px;
            line-height: 1.5;
        }

        /* Quantity Controls */
        .controls {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .qty-control {
            display: flex;
            align-items: center;
            gap: 12px;
            background: #F8FAFC;
            padding: 6px;
            border-radius: 10px;
            border: 1px solid var(--border);
        }

        .qty-btn {
            width: 36px;
            height: 36px;
            border: none;
            border-radius: 8px;
            background: white;
            color: var(--primary);
            font-size: 0.9rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .qty-btn:hover {
            background: var(--primary);
            color: white;
        }

        .qty-display {
            width: 40px;
            text-align: center;
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--text);
        }

        .add-btn {
            flex: 1;
            padding: 12px 24px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.95rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .add-btn:hover {
            background: var(--primary-dark);
        }

        .add-btn.added {
            background: #10B981;
        }

        /* Grid Layout */
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            color: var(--text-light);
        }

        .empty-state i {
            font-size: 4rem;
            color: var(--primary-light);
            margin-bottom: 20px;
            opacity: 0.5;
        }

        /* Floating Cart */
        .floating-cart {
            position: fixed;
            bottom: 25px;
            right: 25px;
            z-index: 100;
        }

        .cart-btn {
            width: 65px;
            height: 65px;
            background: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            cursor: pointer;
            box-shadow: 0 10px 30px rgba(249, 115, 22, 0.4);
            border: 4px solid white;
            position: relative;
            transition: transform 0.3s ease;
        }

        .cart-btn:hover {
            transform: scale(1.1);
        }

        .cart-count {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #EF4444;
            color: white;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 0.85rem;
            border: 3px solid white;
        }

        /* Modal */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.6);
            backdrop-filter: blur(5px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            padding: 20px;
        }

        .modal-overlay.show {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 20px;
            padding: 30px;
            max-width: 450px;
            width: 100%;
            max-height: 80vh;
            overflow-y: auto;
            animation: modalIn 0.3s ease;
        }

        @keyframes modalIn {
            from { transform: scale(0.9); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }

        .modal-header {
            text-align: center;
            margin-bottom: 25px;
        }

        .modal-header h3 {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--text);
        }

        .cart-item {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            border-bottom: 1px solid var(--border);
        }

        .cart-item-info h4 {
            font-weight: 700;
            margin-bottom: 4px;
        }

        .cart-item-info span {
            color: var(--text-light);
            font-size: 0.9rem;
        }

        .cart-item-price {
            font-weight: 800;
            color: var(--primary);
        }

        .cart-summary {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid var(--primary);
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            color: var(--text-light);
        }

        .summary-row.total {
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--text);
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid var(--border);
        }

        .checkout-btn {
            width: 100%;
            padding: 16px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1.1rem;
            cursor: pointer;
            margin-top: 20px;
            transition: background 0.3s;
        }

        .checkout-btn:hover {
            background: var(--primary-dark);
        }

        .close-modal {
            width: 100%;
            padding: 12px;
            background: #F1F5F9;
            color: var(--text);
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 10px;
        }

        /* Toast */
        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            background: var(--primary);
            color: white;
            padding: 16px 24px;
            border-radius: 12px;
            font-weight: 600;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            transform: translateX(150%);
            transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            z-index: 1001;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .toast.show {
            transform: translateX(0);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .menu-grid {
                grid-template-columns: 1fr;
            }
            
            .shop-title {
                font-size: 1.75rem;
            }
            
            .controls {
                flex-direction: column;
                align-items: stretch;
            }
            
            .qty-control {
                justify-content: center;
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

    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1 class="shop-title"><?= htmlspecialchars($shop_name) ?></h1>
            <div class="table-badge">
                <i class="fas fa-chair"></i>
                <span><?= $table_no ?></span>
            </div>
        </div>

        <!-- Menu Grid -->
        <div class="menu-grid">
            <?php if (mysqli_num_rows($menu_q) == 0): ?>
                <div class="empty-state">
                    <i class="fas fa-utensils"></i>
                    <h3>Menu Coming Soon</h3>
                    <p>Delicious items will be added shortly</p>
                </div>
            <?php else: 
                while ($item = mysqli_fetch_assoc($menu_q)): 
                    $image_url = getFoodImage($item['item_name'], $food_images);
            ?>
            <div class="menu-card" data-id="<?= $item['id'] ?>">
                <div class="card-image">
                    <img src="<?= $image_url ?>" alt="<?= htmlspecialchars($item['item_name']) ?>" loading="lazy">
                </div>
                <div class="card-content">
                    <div class="item-header">
                        <h3 class="item-name"><?= htmlspecialchars($item['item_name']) ?></h3>
                        <span class="item-price">₹<?= number_format($item['price'], 0) ?></span>
                    </div>
                    <p class="item-desc">Freshly prepared with quality ingredients</p>
                    <div class="controls">
                        <div class="qty-control">
                            <button class="qty-btn minus" data-id="<?= $item['id'] ?>">
                                <i class="fas fa-minus"></i>
                            </button>
                            <span class="qty-display" id="qty<?= $item['id'] ?>">0</span>
                            <button class="qty-btn plus" data-id="<?= $item['id'] ?>">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                        <button class="add-btn" data-id="<?= $item['id'] ?>">
                            <i class="fas fa-plus"></i>
                            Add
                        </button>
                    </div>
                </div>
            </div>
            <?php endwhile; endif; ?>
        </div>
    </div>

    <!-- Floating Cart -->
    <div class="floating-cart">
        <div class="cart-btn" id="cartBtn">
            <i class="fas fa-shopping-bag"></i>
            <span class="cart-count" id="cartCount">0</span>
        </div>
    </div>

    <!-- Cart Modal -->
    <div class="modal-overlay" id="cartModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-receipt" style="color: var(--primary); margin-right: 10px;"></i>Your Order</h3>
            </div>
            <div id="cartItems"></div>
            <div class="cart-summary" id="cartSummary" style="display: none;">
                <div class="summary-row">
                    <span>Subtotal</span>
                    <span id="subtotal">₹0</span>
                </div>
                <div class="summary-row">
                    <span>Tax (5%)</span>
                    <span id="tax">₹0</span>
                </div>
                <div class="summary-row total">
                    <span>Total</span>
                    <span id="total">₹0</span>
                </div>
                <button class="checkout-btn" onclick="placeOrder()">
                    <i class="fas fa-lock" style="margin-right: 8px;"></i>Place Order
                </button>
            </div>
            <button class="close-modal" onclick="closeModal()">Continue Shopping</button>
        </div>
    </div>

    <!-- Toast -->
    <div class="toast" id="toast">
        <i class="fas fa-check-circle"></i>
        <span id="toastMsg">Added to cart</span>
    </div>

    <script>
        const cart = {};
        
        document.querySelectorAll('.plus').forEach(btn => {
            btn.addEventListener('click', () => updateQty(btn.dataset.id, 1));
        });
        
        document.querySelectorAll('.minus').forEach(btn => {
            btn.addEventListener('click', () => updateQty(btn.dataset.id, -1));
        });
        
        document.querySelectorAll('.add-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = btn.dataset.id;
                if (!cart[id]) {
                    updateQty(id, 1);
                    btn.classList.add('added');
                    btn.innerHTML = '<i class="fas fa-check"></i> Added';
                    setTimeout(() => {
                        btn.classList.remove('added');
                        btn.innerHTML = '<i class="fas fa-plus"></i> Add';
                    }, 1500);
                }
            });
        });
        
        function updateQty(id, change) {
            const current = cart[id] || 0;
            const newQty = Math.max(0, current + change);
            
            if (newQty === 0) {
                delete cart[id];
            } else {
                cart[id] = newQty;
            }
            
            document.getElementById(`qty${id}`).textContent = newQty;
            updateCartCount();
            
            if (change > 0) showToast('Added to cart');
        }
        
        function updateCartCount() {
            const total = Object.values(cart).reduce((a, b) => a + b, 0);
            document.getElementById('cartCount').textContent = total;
        }
        
        function showToast(msg) {
            const toast = document.getElementById('toast');
            document.getElementById('toastMsg').textContent = msg;
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 3000);
        }
        
        document.getElementById('cartBtn').addEventListener('click', showCart);
        
        function showCart() {
            const modal = document.getElementById('cartModal');
            const itemsContainer = document.getElementById('cartItems');
            const summary = document.getElementById('cartSummary');
            
            let html = '';
            let subtotal = 0;
            let hasItems = false;
            
            document.querySelectorAll('.menu-card').forEach(card => {
                const id = card.dataset.id;
                const qty = cart[id] || 0;
                
                if (qty > 0) {
                    hasItems = true;
                    const name = card.querySelector('.item-name').textContent;
                    const price = parseInt(card.querySelector('.item-price').textContent.replace('₹', ''));
                    const itemTotal = price * qty;
                    subtotal += itemTotal;
                    
                    html += `
                        <div class="cart-item">
                            <div class="cart-item-info">
                                <h4>${name}</h4>
                                <span>₹${price} × ${qty}</span>
                            </div>
                            <div class="cart-item-price">₹${itemTotal}</div>
                        </div>
                    `;
                }
            });
            
            if (!hasItems) {
                html = '<p style="text-align: center; color: var(--text-light); padding: 40px;">Your cart is empty</p>';
                summary.style.display = 'none';
            } else {
                const tax = Math.round(subtotal * 0.05);
                const total = subtotal + tax;
                
                document.getElementById('subtotal').textContent = '₹' + subtotal;
                document.getElementById('tax').textContent = '₹' + tax;
                document.getElementById('total').textContent = '₹' + total;
                summary.style.display = 'block';
            }
            
            itemsContainer.innerHTML = html;
            modal.classList.add('show');
        }
        
        function closeModal() {
            document.getElementById('cartModal').classList.remove('show');
        }
        
        async function placeOrder() {
            const btn = document.querySelector('.checkout-btn');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            
            try {
                const response = await fetch('save_cart.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        shop_id: <?= $shop_id ?>,
                        table_no: "<?= $table_no ?>",
                        cart: cart,
                        timestamp: new Date().toISOString()
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showToast('Order placed successfully!');
                    setTimeout(() => {
                        window.location.href = 'mock_payment.php?order_id=' + data.order_id;
                    }, 1000);
                } else {
                    showToast('Failed to place order');
                    btn.innerHTML = '<i class="fas fa-lock"></i> Place Order';
                }
            } catch (err) {
                showToast('Error: ' + err.message);
                btn.innerHTML = '<i class="fas fa-lock"></i> Place Order';
            }
        }
        
        // Close modal on outside click
        document.getElementById('cartModal').addEventListener('click', (e) => {
            if (e.target === e.currentTarget) closeModal();
        });
    </script>
</body>
</html>