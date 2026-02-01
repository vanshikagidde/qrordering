<?php
// analytics.php - Enhanced Shop Owner Analytics Page
session_start();
include "../config/db.php";

if (!isset($_SESSION['shop_id']) || !isset($_SESSION['logged_in'])) {
    header("Location: login.php");
    exit;
}

$shop_id = (int)$_SESSION['shop_id'];

// Time period filter
$period = $_GET['period'] ?? '30days';

$today = date('Y-m-d');
$start_date = $today;
$previous_start_date = $today;

// Calculate current period dates
switch ($period) {
    case 'today':
        $start_date = $today;
        $title = 'Today';
        $previous_start_date = date('Y-m-d', strtotime('-1 day'));
        break;
    case '7days':
        $start_date = date('Y-m-d', strtotime('-7 days'));
        $title = 'Last 7 Days';
        $previous_start_date = date('Y-m-d', strtotime('-14 days'));
        break;
    case '30days':
        $start_date = date('Y-m-d', strtotime('-30 days'));
        $title = 'Last 30 Days';
        $previous_start_date = date('Y-m-d', strtotime('-60 days'));
        break;
    case '3months':
        $start_date = date('Y-m-d', strtotime('-3 months'));
        $title = 'Last 3 Months';
        $previous_start_date = date('Y-m-d', strtotime('-6 months'));
        break;
    case '1year':
        $start_date = date('Y-m-d', strtotime('-1 year'));
        $title = 'Last 1 Year';
        $previous_start_date = date('Y-m-d', strtotime('-2 years'));
        break;
    case 'all':
        $start_date = '1970-01-01';
        $title = 'All Time';
        $previous_start_date = '1970-01-01';
        break;
    default:
        $start_date = date('Y-m-d', strtotime('-30 days'));
        $title = 'Last 30 Days';
        $previous_start_date = date('Y-m-d', strtotime('-60 days'));
}

// 1. Total Revenue (Current Period)
$revenue_query = "SELECT COALESCE(SUM(total), 0) as total_revenue 
                  FROM orders 
                  WHERE shop_id = ? 
                    AND status IN ('paid', 'completed')
                    AND DATE(created_at) >= ?";
$stmt = $conn->prepare($revenue_query);
$stmt->bind_param("is", $shop_id, $start_date);
$stmt->execute();
$revenue = $stmt->get_result()->fetch_assoc()['total_revenue'] ?? 0;
$stmt->close();

// 2. Total Orders
$orders_query = "SELECT COUNT(*) as total_orders 
                 FROM orders 
                 WHERE shop_id = ? 
                   AND DATE(created_at) >= ?";
$stmt = $conn->prepare($orders_query);
$stmt->bind_param("is", $shop_id, $start_date);
$stmt->execute();
$total_orders = $stmt->get_result()->fetch_assoc()['total_orders'] ?? 0;
$stmt->close();

// 3. Average Order Value
$avg_order = $total_orders > 0 ? round($revenue / $total_orders, 2) : 0;

// 4. Most Ordered Items (Top 10)
$top_items_query = "SELECT oi.item_name, SUM(oi.quantity) as total_qty, 
                           SUM(oi.total_price) as total_revenue,
                           ROUND(SUM(oi.total_price) / SUM(oi.quantity), 2) as avg_price
                    FROM order_item oi
                    WHERE oi.shop_id = ? 
                      AND DATE(oi.created_at) >= ?
                    GROUP BY oi.item_name
                    ORDER BY total_qty DESC
                    LIMIT 10";
$stmt = $conn->prepare($top_items_query);
$stmt->bind_param("is", $shop_id, $start_date);
$stmt->execute();
$top_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// 5. Orders per Day (for line chart)
$daily_orders_query = "SELECT DATE(created_at) as date, COUNT(*) as count,
                              COALESCE(SUM(total), 0) as revenue
                       FROM orders
                       WHERE shop_id = ? 
                         AND DATE(created_at) >= ?
                       GROUP BY DATE(created_at)
                       ORDER BY date ASC";
$stmt = $conn->prepare($daily_orders_query);
$stmt->bind_param("is", $shop_id, $start_date);
$stmt->execute();
$daily_data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// 6. Order Status Distribution
$status_query = "SELECT status, COUNT(*) as count
                 FROM orders
                 WHERE shop_id = ? 
                   AND DATE(created_at) >= ?
                 GROUP BY status";
$stmt = $conn->prepare($status_query);
$stmt->bind_param("is", $shop_id, $start_date);
$stmt->execute();
$status_dist = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// 7. Revenue by Category
$category_query = "SELECT 
                    CASE 
                        WHEN LOWER(item_name) LIKE '%coffee%' OR LOWER(item_name) LIKE '%tea%' OR LOWER(item_name) LIKE '%drink%' THEN 'Beverages'
                        WHEN LOWER(item_name) LIKE '%burger%' OR LOWER(item_name) LIKE '%pizza%' OR LOWER(item_name) LIKE '%sandwich%' OR LOWER(item_name) LIKE '%pasta%' THEN 'Main Course'
                        WHEN LOWER(item_name) LIKE '%dessert%' OR LOWER(item_name) LIKE '%ice cream%' OR LOWER(item_name) LIKE '%cake%' THEN 'Desserts'
                        WHEN LOWER(item_name) LIKE '%salad%' OR LOWER(item_name) LIKE '%soup%' THEN 'Starters'
                        ELSE 'Other Items'
                    END as category,
                    SUM(total_price) as total_revenue
                   FROM order_item
                   WHERE shop_id = ? 
                     AND DATE(created_at) >= ?
                   GROUP BY category
                   ORDER BY total_revenue DESC";
$stmt = $conn->prepare($category_query);
$stmt->bind_param("is", $shop_id, $start_date);
$stmt->execute();
$categories_result = $stmt->get_result();
$categories = [];
while ($row = $categories_result->fetch_assoc()) {
    if ($row['total_revenue'] > 0) {
        $categories[] = $row;
    }
}
$stmt->close();

// 8. Peak Hours
$peak_hours_query = "SELECT HOUR(created_at) as hour, COUNT(*) as order_count
                     FROM orders
                     WHERE shop_id = ? 
                       AND DATE(created_at) >= ?
                     GROUP BY HOUR(created_at)
                     ORDER BY order_count DESC
                     LIMIT 5";
$stmt = $conn->prepare($peak_hours_query);
$stmt->bind_param("is", $shop_id, $start_date);
$stmt->execute();
$peak_hours = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Analytics - <?= htmlspecialchars($_SESSION['shop_name'] ?? 'Dashboard') ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet"/>
    
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root {
            --primary: #7c3aed;
            --primary-light: #a78bfa;
            --primary-dark: #5b21b6;
            --secondary: #10b981;
            --secondary-light: #34d399;
            --accent: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;
            --dark: #1f2937;
            --darker: #111827;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --light: #ffffff;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --radius: 12px;
            --radius-lg: 16px;
            --sidebar-width: 260px;
            --sidebar-collapsed: 70px;
            --transition: all 0.3s ease;
        }

        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family: 'Inter', 'Poppins', sans-serif;
            background: #f8fafc;
            color: var(--dark);
            min-height: 100vh;
            overflow-x: hidden;
        }

        .dashboard-container { 
            display: flex; 
            min-height: 100vh; 
        }

        /* Sidebar (Same as orders page) */
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
            box-shadow: var(--shadow-lg);
        }

        .sidebar.collapsed { width: var(--sidebar-collapsed); }
        .sidebar.collapsed .logo-text,
        .sidebar.collapsed .nav-text,
        .sidebar.collapsed .user-info { display: none; }
        .sidebar.collapsed .user-actions { flex-direction: column; gap: 12px; }

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

        .nav-item i { font-size: 18px; width: 24px; text-align: center; }
        .nav-text { font-weight: 500; font-size: 15px; }

        .user-actions {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 20px;
            margin-top: auto;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .user-info { padding: 0 8px 16px; text-align: center; }

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

        .user-name { font-weight: 600; font-size: 14px; margin-bottom: 4px; }
        .user-role { font-size: 12px; color: rgba(255, 255, 255, 0.6); }

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

        .action-btn:hover { background: rgba(255, 255, 255, 0.1); color: white; }
        .action-btn.logout { color: #fca5a5; }
        .action-btn.logout:hover { background: rgba(239, 68, 68, 0.2); }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 24px;
            transition: var(--transition);
        }

        .main-content.expanded { margin-left: var(--sidebar-collapsed); }

        /* Top Bar (Same as orders page) */
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
            background: var(--light);
            padding: 24px 32px;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow);
            border: 1px solid var(--gray-100);
        }

        .welcome-message h1 {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--darker);
            margin-bottom: 8px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .welcome-message p {
            color: var(--gray-600);
            font-size: 15px;
            font-weight: 500;
        }

        .period-info {
            background: linear-gradient(135deg, #f3f4f6, #e5e7eb);
            padding: 8px 20px;
            border-radius: 50px;
            font-weight: 600;
            color: var(--primary-dark);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Filter Period (Like orders page filters) */
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
            background: var(--light);
            padding: 24px;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow);
        }

        .period-btn {
            padding: 12px 20px;
            border-radius: 10px;
            border: 2px solid var(--gray-200);
            background: var(--light);
            color: var(--gray-700);
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            font-size: 14px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .period-btn:hover:not(.active) {
            border-color: var(--primary);
            color: var(--primary);
        }

        .period-btn.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
            box-shadow: 0 4px 12px rgba(124, 58, 237, 0.2);
        }

        /* Stats Cards (Similar to orders page) */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }

        .stat-card {
            background: var(--light);
            padding: 24px;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: var(--transition);
            border: 1px solid var(--gray-100);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 6px;
            height: 100%;
            background: linear-gradient(to bottom, var(--primary), var(--primary-dark));
        }

        .stat-card:hover {
            transform: translateY(-5px);
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
            color: white;
        }

        .stat-info h3 {
            font-size: 28px;
            font-weight: 700;
            color: var(--darker);
            line-height: 1;
            margin-bottom: 4px;
        }

        .stat-info p {
            color: var(--gray-600);
            font-size: 14px;
            font-weight: 500;
        }

        /* Chart Cards */
        .chart-card {
            background: var(--light);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow);
            padding: 24px;
            margin-bottom: 24px;
            border: 1px solid var(--gray-100);
        }

        .chart-title {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 20px;
            color: var(--darker);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .chart-title i {
            color: var(--primary);
            background: rgba(124, 58, 237, 0.1);
            padding: 8px;
            border-radius: 8px;
        }

        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }

        /* Grid Layout */
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 24px;
        }

        /* Table Styling (Like orders page) */
        .table-container {
            overflow-x: auto;
            border-radius: var(--radius);
            border: 1px solid var(--gray-100);
            margin-top: 20px;
        }

        .orders-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .orders-table thead {
            background: linear-gradient(135deg, var(--gray-50), var(--gray-100));
        }

        .orders-table th {
            padding: 16px;
            text-align: left;
            font-weight: 600;
            color: var(--gray-700);
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid var(--gray-200);
        }

        .orders-table tbody tr {
            transition: var(--transition);
        }

        .orders-table tbody tr:hover {
            background: var(--gray-50);
        }

        .orders-table td {
            padding: 16px;
            border-bottom: 1px solid var(--gray-100);
        }

        /* Peak Hours */
        .peak-hours {
            display: flex;
            gap: 12px;
            margin-top: 16px;
            flex-wrap: wrap;
        }

        .peak-hour {
            flex: 1;
            min-width: 120px;
            background: linear-gradient(135deg, var(--primary-light), var(--primary));
            color: white;
            padding: 16px;
            border-radius: 12px;
            text-align: center;
        }

        .peak-hour h4 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .peak-hour p {
            font-size: 13px;
            opacity: 0.9;
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .charts-grid {
                grid-template-columns: 1fr;
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
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .top-bar {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }
            .main-content {
                padding: 16px;
            }
            .filter-grid {
                grid-template-columns: 1fr;
            }
            .charts-grid {
                grid-template-columns: 1fr;
            }
            .chart-container {
                height: 250px;
            }
            .peak-hour {
                min-width: 100px;
            }
        }

        @media (max-width: 480px) {
            .chart-card {
                padding: 16px;
            }
            .stat-card {
                padding: 20px;
                flex-direction: column;
                text-align: center;
                gap: 16px;
            }
            .stat-info h3 {
                font-size: 2rem;
            }
        }

        .mobile-toggle {
            display: none;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 99;
            background: var(--primary);
            color: white;
            border: none;
            width: 50px;
            height: 50px;
            border-radius: 10px;
            font-size: 20px;
            cursor: pointer;
            box-shadow: var(--shadow);
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: var(--transition);
            border: none;
            font-family: inherit;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }
    </style>
</head>
<body>

<div class="dashboard-container">

    <!-- Mobile Toggle -->
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
            <span class="logo-text"><?= htmlspecialchars($_SESSION['shop_name'] ?? 'RestoFlow') ?></span>
        </div>

        <nav class="nav-menu">
            <a href="dashboard.php" class="nav-item">
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
            <a href="analytics.php" class="nav-item active">
                <i class="fas fa-chart-bar"></i>
                <span class="nav-text">Analytics</span>
            </a>
            <a href="settings.php" class="nav-item">
                <i class="fas fa-cog"></i>
                <span class="nav-text">Settings</span>
            </a>
        </nav>

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

        <!-- Top Bar -->
        <div class="top-bar">
            <div class="welcome-message">
                <h1>Analytics Dashboard</h1>
                <p>Business insights and performance metrics</p>
            </div>
            <div class="period-info">
                <i class="fas fa-calendar-alt"></i>
                <span><?= $title ?> (<?= date('M d', strtotime($start_date)) ?> - <?= date('M d') ?>)</span>
            </div>
        </div>

        <!-- Period Filter -->
        <div class="filter-grid">
            <a href="?period=today" class="period-btn <?= $period === 'today' ? 'active' : '' ?>">
                <i class="fas fa-sun"></i> Today
            </a>
            <a href="?period=7days" class="period-btn <?= $period === '7days' ? 'active' : '' ?>">
                <i class="fas fa-calendar-week"></i> 7 Days
            </a>
            <a href="?period=30days" class="period-btn <?= $period === '30days' ? 'active' : '' ?>">
                <i class="fas fa-calendar"></i> 30 Days
            </a>
            <a href="?period=3months" class="period-btn <?= $period === '3months' ? 'active' : '' ?>">
                <i class="fas fa-chart-line"></i> 3 Months
            </a>
            <a href="?period=1year" class="period-btn <?= $period === '1year' ? 'active' : '' ?>">
                <i class="fas fa-calendar-alt"></i> 1 Year
            </a>
            <a href="?period=all" class="period-btn <?= $period === 'all' ? 'active' : '' ?>">
                <i class="fas fa-infinity"></i> All Time
            </a>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, var(--primary), var(--primary-dark));">
                    <i class="fas fa-rupee-sign"></i>
                </div>
                <div class="stat-info">
                    <h3>₹<?= number_format($revenue, 2) ?></h3>
                    <p>Total Revenue</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, var(--info), #0ea5e9);">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <div class="stat-info">
                    <h3><?= number_format($total_orders) ?></h3>
                    <p>Total Orders</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, var(--secondary), #059669);">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-info">
                    <h3>₹<?= number_format($avg_order, 2) ?></h3>
                    <p>Avg. Order Value</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, var(--accent), #f97316);">
                    <i class="fas fa-chart-pie"></i>
                </div>
                <div class="stat-info">
                    <h3><?= count($top_items) ?></h3>
                    <p>Top Selling Items</p>
                </div>
            </div>
        </div>

        <!-- Charts Grid -->
        <div class="charts-grid">
            <!-- Revenue & Orders Trend -->
            <div class="chart-card">
                <h3 class="chart-title"><i class="fas fa-chart-line"></i> Performance Trend</h3>
                <div class="chart-container">
                    <canvas id="trendChart"></canvas>
                </div>
            </div>

            <!-- Order Status Distribution -->
            <div class="chart-card">
                <h3 class="chart-title"><i class="fas fa-chart-pie"></i> Order Status</h3>
                <div class="chart-container">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>

            <!-- Top Selling Items -->
            <div class="chart-card">
                <h3 class="chart-title"><i class="fas fa-star"></i> Top Selling Items</h3>
                <div class="table-container">
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Qty</th>
                                <th>Revenue</th>
                                <th>Avg Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($top_items)): ?>
                                <?php foreach ($top_items as $item): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($item['item_name']) ?></strong></td>
                                        <td><?= $item['total_qty'] ?></td>
                                        <td>₹<?= number_format($item['total_revenue'], 2) ?></td>
                                        <td>₹<?= number_format($item['avg_price'], 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; color: var(--gray-600);">
                                        No sales data available
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Category Revenue -->
            <div class="chart-card">
                <h3 class="chart-title"><i class="fas fa-tags"></i> Category Revenue</h3>
                <div class="chart-container">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>

            <!-- Peak Hours -->
            <div class="chart-card">
                <h3 class="chart-title"><i class="fas fa-clock"></i> Peak Hours</h3>
                <?php if (!empty($peak_hours)): ?>
                    <div class="peak-hours">
                        <?php foreach ($peak_hours as $hour): ?>
                            <div class="peak-hour">
                                <h4><?= $hour['hour'] ?>:00</h4>
                                <p><?= $hour['order_count'] ?> orders</p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="chart-container">
                        <canvas id="peakChart"></canvas>
                    </div>
                <?php else: ?>
                    <p style="color: var(--gray-600); text-align: center; margin-top: 40px;">
                        <i class="fas fa-clock fa-2x" style="margin-bottom: 10px;"></i><br>
                        No peak hour data available
                    </p>
                <?php endif; ?>
            </div>
        </div>

    </main>
</div>

<script>
// Sidebar Toggle (Same as orders page)
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

// Prepare Chart Data
const dailyDates = [<?php
    $dates = [];
    foreach ($daily_data as $row) $dates[] = "'".date('M d', strtotime($row['date']))."'";
    echo implode(',', $dates);
?>];

const dailyOrderCounts = [<?php
    $counts = [];
    foreach ($daily_data as $row) $counts[] = $row['count'];
    echo implode(',', $counts);
?>];

const dailyRevenue = [<?php
    $revs = [];
    foreach ($daily_data as $row) $revs[] = $row['revenue'];
    echo implode(',', $revs);
?>];

const statusLabels = [<?php
    $labels = [];
    foreach ($status_dist as $row) $labels[] = "'".ucfirst($row['status'])."'";
    echo implode(',', $labels);
?>];

const statusValues = [<?php
    $vals = [];
    foreach ($status_dist as $row) $vals[] = $row['count'];
    echo implode(',', $vals);
?>];

const categoryLabels = [<?php
    $catLabels = [];
    foreach ($categories as $cat) $catLabels[] = "'".$cat['category']."'";
    echo implode(',', $catLabels);
?>];

const categoryRevenue = [<?php
    $catRevenue = [];
    foreach ($categories as $cat) $catRevenue[] = $cat['total_revenue'];
    echo implode(',', $catRevenue);
?>];

// Peak Hours Data
const peakHours = [<?php
    $hours = [];
    for ($i = 0; $i < 24; $i++) {
        $found = false;
        foreach ($peak_hours as $hour) {
            if ($hour['hour'] == $i) {
                $hours[] = $hour['order_count'];
                $found = true;
                break;
            }
        }
        if (!$found) $hours[] = 0;
    }
    echo implode(',', $hours);
?>];

// Initialize Charts
document.addEventListener('DOMContentLoaded', function() {
    // 1. Performance Trend Chart
    const trendCanvas = document.getElementById('trendChart');
    if (trendCanvas) {
        const trendCtx = trendCanvas.getContext('2d');
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: dailyDates,
                datasets: [
                    {
                        label: 'Orders',
                        data: dailyOrderCounts,
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Revenue (₹)',
                        data: dailyRevenue,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    // 2. Status Chart
    const statusCanvas = document.getElementById('statusChart');
    if (statusCanvas) {
        const statusCtx = statusCanvas.getContext('2d');
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: statusLabels,
                datasets: [{
                    data: statusValues,
                    backgroundColor: [
                        '#f59e0b', // pending
                        '#3b82f6', // paid
                        '#10b981', // completed
                        '#ef4444'  // cancelled
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }

    // 3. Category Chart
    const categoryCanvas = document.getElementById('categoryChart');
    if (categoryCanvas) {
        const categoryCtx = categoryCanvas.getContext('2d');
        new Chart(categoryCtx, {
            type: 'bar',
            data: {
                labels: categoryLabels,
                datasets: [{
                    label: 'Revenue (₹)',
                    data: categoryRevenue,
                    backgroundColor: 'rgba(124, 58, 237, 0.7)',
                    borderColor: '#7c3aed',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    // 4. Peak Hours Chart
    const peakCanvas = document.getElementById('peakChart');
    if (peakCanvas) {
        const peakCtx = peakCanvas.getContext('2d');
        new Chart(peakCtx, {
            type: 'bar',
            data: {
                labels: Array.from({length: 24}, (_, i) => i + ':00'),
                datasets: [{
                    label: 'Orders per Hour',
                    data: peakHours,
                    backgroundColor: 'rgba(124, 58, 237, 0.6)'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        ticks: {
                            maxTicksLimit: 12
                        }
                    },
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
});

// Auto-refresh every 5 minutes
setTimeout(() => {
    location.reload();
}, 300000);
</script>
</body>
</html>