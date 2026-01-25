<?php
// dashboard.php - Shop Owner Live Orders Dashboard
session_start();
include "../config/db.php";

// Security: must be logged in
if (!isset($_SESSION['shop_id']) || !isset($_SESSION['logged_in'])) {
    header("Location: login.php");
    exit;
}

$shop_id = (int)$_SESSION['shop_id'];

// Handle AJAX status update → now updates order_item status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_status') {
        header('Content-Type: application/json');

        $order_id   = (int)($_POST['order_id'] ?? 0);
        $new_status = trim($_POST['new_status'] ?? '');

        $allowed_statuses = ['preparing', 'served', 'cancelled']; // removed 'completed' as it's not in order_item

        if ($order_id > 0 && in_array($new_status, $allowed_statuses)) {
            // Update all items in this order to new status (common in restaurants)
            $stmt = $conn->prepare("
                UPDATE order_item 
                SET status = ?
                WHERE order_id = ? AND shop_id = ?
            ");
            $stmt->bind_param("sii", $new_status, $order_id, $shop_id);

            if ($stmt->execute()) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => $conn->error]);
            }
            $stmt->close();
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid request']);
        }
        exit;
    }
}

// Fetch current live orders with items
function get_live_orders($conn, $shop_id) {
    $stmt = $conn->prepare("
        SELECT 
            o.order_id,
            o.table_no,
            o.total,
            o.token,
            o.status AS order_status,
            o.created_at
        FROM orders o
        WHERE o.shop_id = ?
          AND o.status IN ('pending', 'paid')
        ORDER BY o.created_at DESC
    ");
    $stmt->bind_param("i", $shop_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $orders = [];
    $order_ids = [];

    while ($row = $result->fetch_assoc()) {
        $orders[$row['order_id']] = $row;
        $orders[$row['order_id']]['items'] = [];
        $orders[$row['order_id']]['current_status'] = 'pending'; // default
        $order_ids[] = $row['order_id'];
    }
    $stmt->close();

    if (empty($order_ids)) {
        return $orders;
    }

    // Safely build placeholders
    $placeholders = implode(',', array_fill(0, count($order_ids), '?'));
    $types = str_repeat('i', count($order_ids));

    $stmt = $conn->prepare("
        SELECT 
            oi.order_id,
            oi.item_name,
            oi.quantity,
            oi.price AS unit_price,
            oi.total_price,
            oi.status AS item_status
        FROM order_item oi
        WHERE oi.order_id IN ($placeholders)
        ORDER BY oi.order_id, oi.order_item_id
    ");
    $stmt->bind_param($types, ...$order_ids);
    $stmt->execute();
    $result = $stmt->get_result();

    // Status priority: most advanced status wins
    $priority = [
        'cancelled' => 0,
        'served'    => 1,
        'preparing' => 2,
        'pending'   => 3
    ];

    while ($item = $result->fetch_assoc()) {
        $oid = $item['order_id'];
        $orders[$oid]['items'][] = $item;

        // Determine overall order status from items
        $current_pri = $priority[$orders[$oid]['current_status']] ?? 3;
        $new_pri     = $priority[$item['item_status']] ?? 3;

        if ($new_pri < $current_pri) {
            $orders[$oid]['current_status'] = $item['item_status'];
        }
    }
    $stmt->close();

    return $orders;
}

$live_orders = get_live_orders($conn, $shop_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Live Orders - <?= htmlspecialchars($_SESSION['shop_name'] ?? 'Dashboard') ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet"/>
    <style>
        :root {
            --primary: #7c3aed;
            --primary-dark: #6d28d9;
            --secondary: #10b981;
            --dark: #1f2937;
            --darker: #111827;
            --gray-100: #f9fafb;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --light: #ffffff;
            --success: #10b981;
            --warning: #f59e0b;
            --info: #3b82f6;
            --danger: #ef4444;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --radius: 12px;
            --radius-lg: 16px;
            --sidebar-width: 260px;
            --sidebar-collapsed: 70px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family: 'Inter', 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
            color: var(--dark);
            min-height: 100vh;
            overflow-x: hidden;
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
            position: relative;
        }

        /* Sidebar Styles */
        .sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(180deg, var(--darker) 0%, #1e1b4b 100%);
            color: white;
            position: fixed;
            height: 100vh;
            padding: 24px 16px;
            display: flex;
            flex-direction: column;
            z-index: 100;
            transition: var(--transition);
            box-shadow: var(--shadow-xl);
        }

        .sidebar.collapsed {
            width: var(--sidebar-collapsed);
        }

        .sidebar.collapsed .logo-text,
        .sidebar.collapsed .nav-text,
        .sidebar.collapsed .user-info {
            display: none;
        }

        .sidebar.collapsed .user-actions {
            flex-direction: column;
            gap: 12px;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0 8px 24px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 24px;
        }

        .logo-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary), #a855f7);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .logo-text {
            font-size: 1.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #ffffff, #c7d2fe);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .sidebar-toggle {
            position: absolute;
            right: -12px;
            top: 24px;
            width: 24px;
            height: 24px;
            background: var(--light);
            border: 2px solid var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: var(--primary);
            font-size: 12px;
            transition: var(--transition);
            z-index: 101;
        }

        .sidebar-toggle:hover {
            transform: scale(1.1);
            box-shadow: var(--shadow);
        }

        .nav-menu {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 16px;
            border-radius: 10px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: var(--transition);
            position: relative;
        }

        .nav-item:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            transform: translateX(4px);
        }

        .nav-item.active {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            box-shadow: var(--shadow);
        }

        .nav-item i {
            font-size: 18px;
            width: 24px;
            text-align: center;
        }

        .nav-text {
            font-weight: 500;
            font-size: 15px;
        }

        /* User Actions */
        .user-actions {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 20px;
            margin-top: auto;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .user-info {
            padding: 0 8px 16px;
            text-align: center;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #8b5cf6, #ec4899);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin: 0 auto 8px;
        }

        .user-name {
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 4px;
        }

        .user-role {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.6);
        }

        .action-btn {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 16px;
            border-radius: 10px;
            background: transparent;
            border: none;
            color: rgba(255, 255, 255, 0.8);
            font-family: inherit;
            font-size: 15px;
            cursor: pointer;
            transition: var(--transition);
            width: 100%;
            text-align: left;
        }

        .action-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .action-btn.logout {
            color: #fca5a5;
        }

        .action-btn.logout:hover {
            background: rgba(239, 68, 68, 0.2);
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 24px;
            transition: var(--transition);
        }

        .main-content.expanded {
            margin-left: var(--sidebar-collapsed);
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
            background: var(--light);
            padding: 20px 24px;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow);
        }

        .welcome-message h1 {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--darker);
            margin-bottom: 4px;
        }

        .welcome-message p {
            color: var(--gray-600);
            font-size: 14px;
        }

        .stats-bar {
            display: flex;
            gap: 16px;
            margin-bottom: 32px;
        }

        .stat-card {
            flex: 1;
            background: var(--light);
            padding: 20px;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            gap: 16px;
            transition: var(--transition);
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }

        .stat-icon {
            width: 56px;
            height: 56px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .stat-icon.pending { background: #fef3c7; color: #d97706; }
        .stat-icon.preparing { background: #dbeafe; color: #2563eb; }
        .stat-icon.served { background: #dcfce7; color: #16a34a; }

        .stat-info h3 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .stat-info p {
            color: var(--gray-600);
            font-size: 14px;
        }

        /* Orders Grid */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--darker);
        }

        .refresh-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: var(--transition);
        }

        .refresh-btn:hover {
            background: var(--primary-dark);
            transform: scale(1.05);
        }

        .orders-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 24px;
        }

        .order-card {
            background: var(--light);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow);
            padding: 24px;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .order-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 6px;
            height: 100%;
            background: var(--primary);
        }

        .order-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-lg);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
        }

        .order-id {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--darker);
        }

        .order-time {
            font-size: 0.875rem;
            color: var(--gray-600);
            background: var(--gray-100);
            padding: 4px 8px;
            border-radius: 6px;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.875rem;
            margin-bottom: 16px;
        }

        .status-badge i {
            font-size: 12px;
        }

        .status-pending { background: #fef3c7; color: #d97706; }
        .status-preparing { background: #dbeafe; color: #2563eb; }
        .status-served { background: #dcfce7; color: #16a34a; }
        .status-cancelled { background: #fee2e2; color: #dc2626; }

        .order-meta {
            display: flex;
            gap: 16px;
            margin-bottom: 20px;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            background: var(--gray-100);
            border-radius: 8px;
            font-size: 0.875rem;
        }

        .meta-item i {
            color: var(--primary);
        }

        .items-list {
            margin: 20px 0;
        }

        .item-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid var(--gray-200);
        }

        .item-row:last-child {
            border-bottom: none;
        }

        .item-name {
            flex: 3;
            font-weight: 500;
        }

        .item-notes {
            font-size: 0.875rem;
            color: var(--gray-600);
            margin-top: 4px;
        }

        .item-qty {
            flex: 1;
            text-align: center;
            font-weight: 600;
        }

        .item-price {
            flex: 1.5;
            text-align: right;
            font-weight: 600;
            color: var(--darker);
        }

        .order-footer {
            margin-top: 20px;
            padding-top: 16px;
            border-top: 2px solid var(--gray-200);
        }

        .order-total {
            display: flex;
            justify-content: space-between;
            font-size: 1.125rem;
            font-weight: 700;
            margin-bottom: 16px;
        }

        .status-selector {
            width: 100%;
            padding: 12px 16px;
            font-size: 0.95rem;
            border: 2px solid var(--gray-200);
            border-radius: 10px;
            background: var(--light);
            cursor: pointer;
            font-weight: 500;
            transition: var(--transition);
        }

        .status-selector:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
        }

        .empty-state {
            text-align: center;
            padding: 80px 20px;
            background: var(--light);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow);
            grid-column: 1 / -1;
        }

        .empty-icon {
            font-size: 4rem;
            color: var(--gray-300);
            margin-bottom: 20px;
        }

        .empty-title {
            font-size: 1.5rem;
            color: var(--gray-700);
            margin-bottom: 8px;
        }

        .empty-subtitle {
            color: var(--gray-600);
            max-width: 400px;
            margin: 0 auto;
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .orders-grid {
                grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            }
        }

        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.active {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
            }
            .mobile-toggle {
                display: block;
            }
        }

        @media (max-width: 768px) {
            .orders-grid {
                grid-template-columns: 1fr;
            }
            .stats-bar {
                flex-direction: column;
            }
            .top-bar {
                flex-direction: column;
                gap: 16px;
                text-align: center;
            }
            .main-content {
                padding: 16px;
            }
        }

        /* Mobile Toggle */
        .mobile-toggle {
            display: none;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 99;
            background: var(--primary);
            color: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 10px;
            font-size: 20px;
            cursor: pointer;
            box-shadow: var(--shadow);
        }

        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .order-card {
            animation: fadeIn 0.5s ease-out;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Mobile Toggle Button -->
        <button class="mobile-toggle" id="mobileToggle">
            <i class="fas fa-bars"></i>
        </button>

        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-toggle" id="sidebarToggle">
                <i class="fas fa-chevron-left"></i>
            </div>

            <div class="logo">
                <div class="logo-icon">
                    <i class="fas fa-store"></i>
                </div>
                <span class="logo-text">RestoFlow</span>
            </div>

            <nav class="nav-menu">
                <a href="#" class="nav-item active">
                    <i class="fas fa-tachometer-alt"></i>
                    <span class="nav-text">Dashboard</span>
                </a>
                <a href="menu.php" class="nav-item">
                    <i class="fas fa-utensils"></i>
                    <span class="nav-text">Menu Management</span>
                </a>
                <a href="orders.php" class="nav-item">
                    <i class="fas fa-clipboard-list"></i>
                    <span class="nav-text">All Orders</span>
                </a>
                <a href="analytics.php" class="nav-item">
                    <i class="fas fa-chart-bar"></i>
                    <span class="nav-text">Analytics</span>
                </a>
                <a href="settings.php" class="nav-item">
                    <i class="fas fa-cog"></i>
                    <span class="nav-text">Settings</span>
                </a>
            </nav>

            <!-- User Actions Section -->
            <div class="user-actions">
                <div class="user-info">
                    <div class="user-avatar">
                        <?= strtoupper(substr($_SESSION['shop_name'] ?? 'S', 0, 1)) ?>
                    </div>
                    <div class="user-name"><?= htmlspecialchars($_SESSION['shop_name'] ?? 'Shop Owner') ?></div>
                    <div class="user-role">Owner</div>
                </div>
                
                <button class="action-btn" onclick="location.href='profile.php'">
                    <i class="fas fa-user-circle"></i>
                    <span class="nav-text">Profile</span>
                </button>
                
                <button class="action-btn logout" onclick="location.href='logout.php'">
                    <i class="fas fa-sign-out-alt"></i>
                    <span class="nav-text">Logout</span>
                </button>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content" id="mainContent">
            <div class="top-bar">
                <div class="welcome-message">
                    <h1>Welcome back, <?= htmlspecialchars($_SESSION['shop_name'] ?? 'Owner') ?>!</h1>
                    <p>Manage your live orders and restaurant operations</p>
                </div>
                <div class="quick-stats">
                    <div class="stats-bar">
                        <?php
                        $status_counts = ['pending' => 0, 'preparing' => 0, 'served' => 0];
                        foreach ($live_orders as $order) {
                            $status = $order['current_status'] ?? 'pending';
                            if (isset($status_counts[$status])) {
                                $status_counts[$status]++;
                            }
                        }
                        ?>
                        <div class="stat-card">
                            <div class="stat-icon pending">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?= $status_counts['pending'] ?></h3>
                                <p>Pending Orders</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon preparing">
                                <i class="fas fa-blender"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?= $status_counts['preparing'] ?></h3>
                                <p>Preparing</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon served">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?= $status_counts['served'] ?></h3>
                                <p>Ready to Serve</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="section-header">
                <h2 class="section-title">Live Orders</h2>
                <button class="refresh-btn" onclick="location.reload()">
                    <i class="fas fa-sync-alt"></i>
                    Refresh
                </button>
            </div>

            <div class="orders-grid" id="live-orders-container">
                <?php if (empty($live_orders)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i class="fas fa-coffee"></i>
                        </div>
                        <h3 class="empty-title">No active orders right now</h3>
                        <p class="empty-subtitle">New orders will appear here automatically. Stay ready!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($live_orders as $order): 
                        $current_status = $order['current_status'] ?? 'pending';
                    ?>
                        <div class="order-card" data-order-id="<?= $order['order_id'] ?>">
                            <div class="order-header">
                                <div class="order-id">Order #<?= $order['order_id'] ?></div>
                                <div class="order-time">
                                    <?= date('h:i A • d M', strtotime($order['created_at'])) ?>
                                </div>
                            </div>

                            <div class="status-badge status-<?= $current_status ?>">
                                <i class="fas fa-circle"></i>
                                <?= ucfirst(str_replace('_', ' ', $current_status)) ?>
                            </div>

                            <div class="order-meta">
                                <?php if (!empty($order['table_no'])): ?>
                                    <div class="meta-item">
                                        <i class="fas fa-chair"></i>
                                        Table <?= htmlspecialchars($order['table_no']) ?>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($order['token'])): ?>
                                    <div class="meta-item">
                                        <i class="fas fa-hashtag"></i>
                                        Token <?= htmlspecialchars($order['token']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="items-list">
                                <?php if (!empty($order['items'])): ?>
                                    <?php foreach ($order['items'] as $item): ?>
                                        <div class="item-row">
                                            <div class="item-name">
                                                <?= htmlspecialchars($item['item_name']) ?>
                                            </div>
                                            <div class="item-qty">× <?= $item['quantity'] ?></div>
                                            <div class="item-price">₹<?= number_format($item['total_price'], 2) ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="item-row">
                                        <div class="item-name" style="color:#9ca3af;">No items found</div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="order-footer">
                                <div class="order-total">
                                    <span>Total Amount</span>
                                    <span>₹<?= number_format($order['total'], 2) ?></span>
                                </div>
                                
                                <select class="status-selector" onchange="changeStatus(<?= $order['order_id'] ?>, this.value)">
                                    <option value="" disabled selected>Update Status</option>
                                    <option value="preparing" <?= $current_status === 'preparing' ? 'selected' : '' ?>>
                                        Preparing
                                    </option>
                                    <option value="served" <?= $current_status === 'served' ? 'selected' : '' ?>>
                                        Ready to Serve
                                    </option>
                                    <option value="cancelled" <?= $current_status === 'cancelled' ? 'selected' : '' ?>>
                                        Cancel Order
                                    </option>
                                </select>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        // Sidebar Toggle (unchanged)
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const mainContent = document.getElementById('mainContent');
        const mobileToggle = document.getElementById('mobileToggle');
        
        let isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';

        function updateSidebar() {
            if (isCollapsed) {
                sidebar.classList.add('collapsed');
                mainContent.classList.add('expanded');
                sidebarToggle.innerHTML = '<i class="fas fa-chevron-right"></i>';
            } else {
                sidebar.classList.remove('collapsed');
                mainContent.classList.remove('expanded');
                sidebarToggle.innerHTML = '<i class="fas fa-chevron-left"></i>';
            }
        }

        sidebarToggle.addEventListener('click', () => {
            isCollapsed = !isCollapsed;
            localStorage.setItem('sidebarCollapsed', isCollapsed);
            updateSidebar();
        });

        mobileToggle.addEventListener('click', () => {
            sidebar.classList.toggle('active');
        });

        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 992 && 
                !sidebar.contains(e.target) && 
                !mobileToggle.contains(e.target)) {
                sidebar.classList.remove('active');
            }
        });

        updateSidebar();

        // Status Update Function
        function changeStatus(orderId, newStatus) {
            if (!newStatus) return;

            fetch('', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: `action=update_status&order_id=${orderId}&new_status=${encodeURIComponent(newStatus)}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showNotification('Status updated successfully!', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showNotification('Failed to update status: ' + (data.error || 'Unknown error'), 'error');
                }
            })
            .catch(err => {
                console.error(err);
                showNotification('Network error. Please try again.', 'error');
            });
        }

        // Notification System (unchanged)
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                <span>${message}</span>
                <button onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            `;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.remove();
                }
            }, 5000);
        }

        // Auto-refresh every 30 seconds
        setInterval(() => {
            fetch('?refresh=true')
                .then(response => response.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newContainer = doc.querySelector('#live-orders-container');
                    if (newContainer) {
                        document.getElementById('live-orders-container').innerHTML = newContainer.innerHTML;
                        showNotification('Orders refreshed', 'info');
                    }
                })
                .catch(() => {});
        }, 30000);

        // Notification styles (unchanged)
        const style = document.createElement('style');
        style.textContent = `
            .notification {
                position: fixed;
                top: 20px;
                right: 20px;
                background: white;
                padding: 16px 24px;
                border-radius: 10px;
                box-shadow: var(--shadow-lg);
                display: flex;
                align-items: center;
                gap: 12px;
                z-index: 1000;
                animation: slideIn 0.3s ease-out;
                border-left: 4px solid var(--primary);
            }
            .notification.success { border-left-color: var(--success); }
            .notification.error { border-left-color: var(--danger); }
            .notification i { font-size: 20px; }
            .notification.success i { color: var(--success); }
            .notification.error i { color: var(--danger); }
            .notification button { background: none; border: none; color: var(--gray-600); cursor: pointer; margin-left: auto; }
            @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>