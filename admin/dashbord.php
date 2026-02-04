<?php
// admin_dashboard.php - Super Admin Panel
session_start();
include "../config/db.php";

// Security: only allow super admin (you can change this logic)
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit;
}

// 1. Total Shops
$shops_query = "SELECT COUNT(*) as total_shops FROM shops";
$total_shops = $conn->query($shops_query)->fetch_assoc()['total_shops'] ?? 0;

// 2. Active Shops
$active_shops_query = "SELECT COUNT(*) as active FROM shops WHERE status = 'active'";
$active_shops = $conn->query($active_shops_query)->fetch_assoc()['active'] ?? 0;

// 3. Total Orders (all shops)
$orders_query = "SELECT COUNT(*) as total_orders FROM orders";
$total_orders = $conn->query($orders_query)->fetch_assoc()['total_orders'] ?? 0;

// 4. Total Revenue (all shops)
$revenue_query = "SELECT COALESCE(SUM(total), 0) as total_revenue 
                  FROM orders 
                  WHERE status IN ('paid', 'completed')";
$total_revenue = $conn->query($revenue_query)->fetch_assoc()['total_revenue'] ?? 0;

// 5. Recent Orders (last 10)
$recent_orders_query = "SELECT o.order_id, s.shop_name, o.total, o.status, o.created_at
                        FROM orders o
                        JOIN shops s ON o.shop_id = s.id
                        ORDER BY o.created_at DESC
                        LIMIT 10";
$recent_orders = $conn->query($recent_orders_query)->fetch_all(MYSQLI_ASSOC);

// 6. Top Shops by Revenue (last 30 days)
$top_shops_query = "SELECT s.shop_name, COALESCE(SUM(o.total), 0) as revenue
                    FROM shops s
                    LEFT JOIN orders o ON s.id = o.shop_id 
                      AND o.status IN ('paid', 'completed')
                      AND o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                    GROUP BY s.id, s.shop_name
                    ORDER BY revenue DESC
                    LIMIT 5";
$top_shops = $conn->query($top_shops_query)->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Admin Dashboard - RestoFlow Control Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet"/>
    <style>
        /* === YOUR ORIGINAL DASHBOARD STYLES - PRESERVED & EXTENDED === */
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

        .dashboard-container { display: flex; min-height: 100vh; position: relative; }

        /* Sidebar - exact same as your shop owner pages */
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

        .sidebar.collapsed { width: var(--sidebar-collapsed); }
        .sidebar.collapsed .logo-text,
        .sidebar.collapsed .nav-text,
        .sidebar.collapsed .user-info { display: none; }

        .logo { display: flex; align-items: center; gap: 12px; padding: 0 8px 24px; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 24px; }
        .logo-icon { width: 40px; height: 40px; background: linear-gradient(135deg, var(--primary), #a855f7); border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px; }
        .logo-text { font-size: 1.5rem; font-weight: 700; background: linear-gradient(135deg, #ffffff, #c7d2fe); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }

        .sidebar-toggle { position: absolute; right: -12px; top: 24px; width: 24px; height: 24px; background: var(--light); border: 2px solid var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; color: var(--primary); font-size: 12px; transition: var(--transition); z-index: 101; }
        .sidebar-toggle:hover { transform: scale(1.1); box-shadow: var(--shadow); }

        .nav-menu { flex: 1; display: flex; flex-direction: column; gap: 8px; }
        .nav-item { display: flex; align-items: center; gap: 12px; padding: 14px 16px; border-radius: 10px; color: rgba(255,255,255,0.8); text-decoration: none; transition: var(--transition); }
        .nav-item:hover { background: rgba(255,255,255,0.1); color: white; transform: translateX(4px); }
        .nav-item.active { background: linear-gradient(135deg, var(--primary), var(--primary-dark)); color: white; box-shadow: var(--shadow); }
        .nav-item i { font-size: 18px; width: 24px; text-align: center; }
        .nav-text { font-weight: 500; font-size: 15px; }

        .user-actions { border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px; margin-top: auto; display: flex; flex-direction: column; gap: 8px; }
        .user-info { padding: 0 8px 16px; text-align: center; }
        .user-avatar { width: 40px; height: 40px; background: linear-gradient(135deg, #8b5cf6, #ec4899); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; margin: 0 auto 8px; }
        .user-name { font-weight: 600; font-size: 14px; margin-bottom: 4px; }
        .user-role { font-size: 12px; color: rgba(255,255,255,0.6); }
        .action-btn { display: flex; align-items: center; gap: 12px; padding: 14px 16px; border-radius: 10px; background: transparent; border: none; color: rgba(255,255,255,0.8); font-family: inherit; font-size: 15px; cursor: pointer; transition: var(--transition); width: 100%; text-align: left; }
        .action-btn:hover { background: rgba(255,255,255,0.1); color: white; }
        .action-btn.logout { color: #fca5a5; }
        .action-btn.logout:hover { background: rgba(239,68,68,0.2); }

        /* Main Content */
        .main-content { flex: 1; margin-left: var(--sidebar-width); padding: 24px; transition: var(--transition); }
        .main-content.expanded { margin-left: var(--sidebar-collapsed); }

        .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; background: var(--light); padding: 20px 24px; border-radius: var(--radius-lg); box-shadow: var(--shadow); }
        .welcome-message h1 { font-size: 1.75rem; font-weight: 700; color: var(--darker); margin-bottom: 4px; }
        .welcome-message p { color: var(--gray-600); font-size: 14px; }

        /* Stats Grid */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 32px; }
        .stat-card { background: var(--light); padding: 24px; border-radius: var(--radius-lg); box-shadow: var(--shadow); display: flex; align-items: center; gap: 20px; transition: var(--transition); border: 1px solid var(--gray-100); position: relative; overflow: hidden; }
        .stat-card::before { content: ''; position: absolute; top: 0; left: 0; width: 6px; height: 100%; background: linear-gradient(to bottom, var(--primary), var(--primary-dark)); }
        .stat-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-lg); }
        .stat-icon { width: 56px; height: 56px; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 24px; color: white; }
        .stat-info h3 { font-size: 2.2rem; font-weight: 700; color: var(--darker); margin-bottom: 4px; }
        .stat-info p { color: var(--gray-600); font-size: 14px; font-weight: 500; }

        /* Recent Orders & Top Shops */
        .card { background: var(--light); border-radius: var(--radius-lg); box-shadow: var(--shadow); padding: 24px; margin-bottom: 32px; border: 1px solid var(--gray-100); }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 2px solid var(--gray-100); }
        .card-title { font-size: 1.5rem; font-weight: 700; color: var(--darker); display: flex; align-items: center; gap: 12px; }
        .card-title i { color: var(--primary); }

        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 14px 16px; text-align: left; border-bottom: 1px solid var(--gray-200); }
        th { background: var(--gray-100); font-weight: 600; color: var(--gray-700); text-transform: uppercase; font-size: 0.85rem; }
        tr:hover { background: var(--gray-50); }

        .status-badge { padding: 6px 14px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; }
        .status-pending  { background: #fef3c7; color: #d97706; }
        .status-paid     { background: #d1fae5; color: #065f46; }
        .status-completed { background: #dbeafe; color: #1e40af; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }

        .empty-state { text-align: center; padding: 80px 20px; color: var(--gray-600); }
        .empty-icon { font-size: 5rem; color: var(--gray-300); margin-bottom: 24px; }

        /* Responsive */
        @media (max-width: 992px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; }
            .mobile-toggle { display: block; }
        }

        @media (max-width: 768px) {
            .stats-grid { grid-template-columns: 1fr; }
            .top-bar { flex-direction: column; gap: 16px; text-align: center; }
        }

        .mobile-toggle {
            display: none; position: fixed; top: 20px; left: 20px; z-index: 99;
            background: var(--primary); color: white; border: none;
            width: 40px; height: 40px; border-radius: 10px;
            font-size: 20px; cursor: pointer; box-shadow: var(--shadow);
        }
    </style>
</head>
<body>

<div class="dashboard-container">

    <!-- Mobile Toggle -->
    <button class="mobile-toggle" id="mobileToggle">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Sidebar - Identical to your shop owner pages -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-toggle" id="sidebarToggle">
            <i class="fas fa-chevron-left"></i>
        </div>

        <div class="logo">
            <div class="logo-icon">
                <i class="fas fa-store"></i>
            </div>
            <span class="logo-text">RestoFlow Admin</span>
        </div>

        <nav class="nav-menu">
            <a href="admin_dashboard.php" class="nav-item active">
                <i class="fas fa-tachometer-alt"></i>
                <span class="nav-text">Dashboard</span>
            </a>
            <a href="admin_shops.php" class="nav-item">
                <i class="fas fa-shop"></i>
                <span class="nav-text">Manage Shops</span>
            </a>
            <a href="admin_orders.php" class="nav-item">
                <i class="fas fa-clipboard-list"></i>
                <span class="nav-text">All Orders</span>
            </a>
            <a href="admin_users.php" class="nav-item">
                <i class="fas fa-users"></i>
                <span class="nav-text">Users & Shops</span>
            </a>
            <a href="admin_settings.php" class="nav-item">
                <i class="fas fa-cog"></i>
                <span class="nav-text">Settings</span>
            </a>
        </nav>

        <div class="user-actions">
            <div class="user-info">
                <div class="user-avatar">A</div>
                <div class="user-name">Super Admin</div>
                <div class="user-role">Control Panel</div>
            </div>
            
            <button class="action-btn" onclick="location.href='admin_profile.php'">
                <i class="fas fa-user-circle"></i>
                <span class="nav-text">Profile</span>
            </button>
            
            <button class="action-btn logout" onclick="location.href='admin_logout.php'">
                <i class="fas fa-sign-out-alt"></i>
                <span class="nav-text">Logout</span>
            </button>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content" id="mainContent">

        <!-- Top Bar -->
        <div class="top-bar">
            <div class="welcome-message">
                <h1>Admin Dashboard</h1>
                <p>Overview of all shops, orders & system performance</p>
            </div>
        </div>

        <!-- Stats Overview -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #8b5cf6, var(--primary));">
                    <i class="fas fa-shop"></i>
                </div>
                <div class="stat-info">
                    <h3><?= number_format($total_shops) ?></h3>
                    <p>Total Shops</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, var(--success), var(--secondary));">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <h3><?= number_format($active_shops) ?></h3>
                    <p>Active Shops</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #3b82f6, var(--info));">
                    <i class="fas fa-receipt"></i>
                </div>
                <div class="stat-info">
                    <h3><?= number_format($total_orders) ?></h3>
                    <p>Total Orders</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #10b981, #059669);">
                    <i class="fas fa-rupee-sign"></i>
                </div>
                <div class="stat-info">
                    <h3>₹<?= number_format($total_revenue, 2) ?></h3>
                    <p>Total Revenue</p>
                </div>
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">
                    <i class="fas fa-clock"></i>
                    Recent Orders (Latest 10)
                </h2>
            </div>

            <?php if (empty($recent_orders)): ?>
                <div class="empty-state">
                    <div class="empty-icon"><i class="fas fa-receipt"></i></div>
                    <h3>No recent orders</h3>
                    <p>Orders from all shops will appear here.</p>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Shop</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_orders as $order): ?>
                                <tr>
                                    <td><strong>#<?= $order['order_id'] ?></strong></td>
                                    <td><?= htmlspecialchars($order['shop_name']) ?></td>
                                    <td>₹<?= number_format($order['total'], 2) ?></td>
                                    <td>
                                        <span class="status-badge status-<?= strtolower($order['status']) ?>">
                                            <?= ucfirst($order['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= date('d M Y • h:i A', strtotime($order['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Top Performing Shops -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">
                    <i class="fas fa-trophy"></i>
                    Top Shops by Revenue (Last 30 Days)
                </h2>
            </div>

            <?php if (empty($top_shops)): ?>
                <div class="empty-state">
                    <div class="empty-icon"><i class="fas fa-shop"></i></div>
                    <h3>No revenue data yet</h3>
                    <p>Top shops will appear once orders are placed.</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>Shop Name</th>
                            <th>Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $rank = 1; foreach ($top_shops as $shop): ?>
                            <tr>
                                <td><strong><?= $rank++ ?></strong></td>
                                <td><?= htmlspecialchars($shop['shop_name']) ?></td>
                                <td><strong>₹<?= number_format($shop['revenue'], 2) ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

    </main>
</div>

<script>
// Exact same sidebar toggle script as your other pages
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
</script>

</body>
</html>