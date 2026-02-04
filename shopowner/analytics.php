<?php
// analytics.php - Enhanced Shop Owner Analytics Page (Orange Theme)
session_start();
include "../config/db.php";

if (!isset($_SESSION['shop_id']) || !isset($_SESSION['logged_in'])) {
    header("Location: login.php");
    exit;
}

$shop_id = (int)$_SESSION['shop_id'];
$shop_name = $_SESSION['shop_name'] ?? 'My Shop';

// Time period filter
$period = $_GET['period'] ?? '30days';

$today = date('Y-m-d');
$start_date = $today;

switch ($period) {
    case 'today':
        $start_date = $today;
        $title = 'Today';
        break;
    case '7days':
        $start_date = date('Y-m-d', strtotime('-7 days'));
        $title = 'Last 7 Days';
        break;
    case '30days':
        $start_date = date('Y-m-d', strtotime('-30 days'));
        $title = 'Last 30 Days';
        break;
    case '3months':
        $start_date = date('Y-m-d', strtotime('-3 months'));
        $title = 'Last 3 Months';
        break;
    case '1year':
        $start_date = date('Y-m-d', strtotime('-1 year'));
        $title = 'Last 1 Year';
        break;
    case 'all':
        $start_date = '1970-01-01';
        $title = 'All Time';
        break;
    default:
        $start_date = date('Y-m-d', strtotime('-30 days'));
        $title = 'Last 30 Days';
}

// Total Revenue
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

// Total Orders
$orders_query = "SELECT COUNT(*) as total_orders 
                 FROM orders 
                 WHERE shop_id = ? 
                   AND DATE(created_at) >= ?";
$stmt = $conn->prepare($orders_query);
$stmt->bind_param("is", $shop_id, $start_date);
$stmt->execute();
$total_orders = $stmt->get_result()->fetch_assoc()['total_orders'] ?? 0;
$stmt->close();

// Average Order Value
$avg_order = $total_orders > 0 ? round($revenue / $total_orders, 2) : 0;

// Top Selling Items
$top_items_query = "SELECT oi.item_name, SUM(oi.quantity) as total_qty, 
                           SUM(oi.total_price) as total_revenue,
                           ROUND(SUM(oi.total_price) / SUM(oi.quantity), 2) as avg_price
                    FROM order_item oi
                    WHERE oi.shop_id = ? 
                      AND DATE(oi.created_at) >= ?
                    GROUP BY oi.item_name
                    ORDER BY total_qty DESC
                    LIMIT 5";
$stmt = $conn->prepare($top_items_query);
$stmt->bind_param("is", $shop_id, $start_date);
$stmt->execute();
$top_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Daily Orders for Chart
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

// Order Status Distribution
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Analytics - <?= htmlspecialchars($shop_name) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root {
            --primary: #F97316;
            --primary-light: #FB923C;
            --primary-dark: #EA580C;
            --secondary: #0F172A;
            --surface: #FFFFFF;
            --surface-hover: #FFF7ED;
            --background: #F8FAFC;
            --text: #1E293B;
            --text-secondary: #64748B;
            --border: #E2E8F0;
            --success: #10B981;
            --warning: #F59E0B;
            --info: #3B82F6;
            --danger: #EF4444;
            
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
            --shadow-orange: 0 10px 40px -10px rgba(249, 115, 22, 0.5);
            
            --ease-spring: cubic-bezier(0.34, 1.56, 0.64, 1);
            --ease-smooth: cubic-bezier(0.4, 0, 0.2, 1);
            --ease-expo: cubic-bezier(0.16, 1, 0.3, 1);
            
            --radius: 16px;
            --radius-sm: 12px;
            --sidebar-width: 280px;
            --sidebar-collapsed: 80px;
        }

        * { margin:0; padding:0; box-sizing:border-box; }
        
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: linear-gradient(135deg, #F8FAFC 0%, #FFF7ED 100%);
            color: var(--text);
            min-height: 100vh;
            overflow-x: hidden;
            line-height: 1.5;
        }

        /* Animated Background Elements */
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
            filter: blur(80px);
            opacity: 0.4;
            animation: float 20s infinite ease-in-out;
        }

        .shape-1 {
            width: 400px;
            height: 400px;
            background: linear-gradient(135deg, rgba(249, 115, 22, 0.3), rgba(251, 146, 60, 0.1));
            top: -100px;
            right: -100px;
            animation-delay: 0s;
        }

        .shape-2 {
            width: 300px;
            height: 300px;
            background: linear-gradient(135deg, rgba(255, 237, 213, 0.6), rgba(254, 215, 170, 0.2));
            bottom: 10%;
            left: -50px;
            animation-delay: -5s;
        }

        @keyframes float {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(30px, -30px) scale(1.1); }
            66% { transform: translate(-20px, 20px) scale(0.9); }
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
            position: relative;
            z-index: 1;
        }

        /* Glassmorphism Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(20px);
            color: white;
            position: fixed;
            height: 100vh;
            padding: 24px 20px;
            display: flex;
            flex-direction: column;
            z-index: 100;
            transition: all 0.4s var(--ease-expo);
            border-right: 1px solid rgba(255,255,255,0.05);
            box-shadow: 4px 0 24px rgba(0,0,0,0.1);
        }

        .sidebar.collapsed {
            width: var(--sidebar-collapsed);
            padding: 24px 16px;
        }

        .sidebar.collapsed .logo-text,
        .sidebar.collapsed .nav-text,
        .sidebar.collapsed .user-info {
            opacity: 0;
            transform: translateX(-10px);
            pointer-events: none;
        }

        .sidebar-toggle {
            position: absolute;
            right: -12px;
            top: 32px;
            width: 28px;
            height: 28px;
            background: var(--primary);
            border: 3px solid var(--surface);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: white;
            font-size: 11px;
            transition: all 0.3s var(--ease-spring);
            z-index: 101;
            box-shadow: 0 4px 12px rgba(249, 115, 22, 0.4);
        }

        .sidebar-toggle:hover {
            transform: scale(1.15) rotate(180deg);
            background: var(--primary-light);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 8px 4px 24px;
            margin-bottom: 24px;
            position: relative;
        }

        .logo::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
        }

        .logo-icon {
            width: 44px;
            height: 44px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
            box-shadow: 0 8px 20px rgba(249, 115, 22, 0.3);
            animation: iconPulse 3s infinite;
            position: relative;
            overflow: hidden;
        }

        .logo-icon::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, transparent, rgba(255,255,255,0.3));
        }

        @keyframes iconPulse {
            0%, 100% { box-shadow: 0 8px 20px rgba(249, 115, 22, 0.3); transform: scale(1); }
            50% { box-shadow: 0 12px 30px rgba(249, 115, 22, 0.5); transform: scale(1.02); }
        }

        .logo-text {
            font-size: 1.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #ffffff, #cbd5e1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            transition: all 0.3s var(--ease-smooth);
            white-space: nowrap;
        }

        .nav-menu {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 16px;
            border-radius: 12px;
            color: rgba(255,255,255,0.6);
            text-decoration: none;
            transition: all 0.3s var(--ease-spring);
            position: relative;
            font-weight: 500;
            font-size: 15px;
            overflow: hidden;
        }

        .nav-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 3px;
            height: 0;
            background: var(--primary);
            border-radius: 0 4px 4px 0;
            transition: height 0.3s var(--ease-spring);
        }

        .nav-item:hover {
            color: white;
            background: rgba(255,255,255,0.05);
            transform: translateX(6px);
        }

        .nav-item:hover::before {
            height: 60%;
        }

        .nav-item.active {
            background: linear-gradient(135deg, rgba(249, 115, 22, 0.2), rgba(249, 115, 22, 0.05));
            color: var(--primary-light);
            font-weight: 600;
        }

        .nav-item.active::before {
            height: 80%;
        }

        .nav-item i {
            font-size: 18px;
            width: 24px;
            text-align: center;
            transition: transform 0.3s var(--ease-spring);
        }

        .nav-item:hover i {
            transform: scale(1.1) rotate(-5deg);
        }

        .nav-text {
            transition: all 0.3s var(--ease-smooth);
            white-space: nowrap;
        }

        .user-actions {
            margin-top: auto;
            padding-top: 20px;
            position: relative;
        }

        .user-actions::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.15), transparent);
        }

        .user-info {
            padding: 0 8px 16px;
            text-align: center;
            transition: all 0.3s var(--ease-smooth);
        }

        .user-avatar {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, var(--primary), #fbbf24);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 18px;
            margin: 0 auto 10px;
            box-shadow: 0 4px 15px rgba(249, 115, 22, 0.3);
            border: 3px solid rgba(255,255,255,0.1);
            transition: all 0.3s var(--ease-spring);
            position: relative;
            overflow: hidden;
        }

        .user-avatar::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.2), transparent);
        }

        .user-avatar:hover {
            transform: scale(1.1) rotate(10deg);
        }

        .user-name {
            font-weight: 700;
            font-size: 15px;
            margin-bottom: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .user-role {
            font-size: 12px;
            color: rgba(255,255,255,0.4);
            font-weight: 500;
        }

        .action-btn {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            border-radius: 10px;
            background: transparent;
            border: none;
            color: rgba(255,255,255,0.6);
            font-family: inherit;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s var(--ease-spring);
            width: 100%;
            text-align: left;
            font-weight: 500;
            position: relative;
            overflow: hidden;
        }

        .action-btn::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            transform: translateX(-100%);
            transition: transform 0.5s;
        }

        .action-btn:hover::before {
            transform: translateX(100%);
        }

        .action-btn:hover {
            color: white;
            background: rgba(255,255,255,0.08);
            transform: translateX(4px);
        }

        .action-btn.logout {
            color: #fca5a5;
            margin-top: 4px;
        }

        .action-btn.logout:hover {
            background: rgba(239, 68, 68, 0.15);
            color: #fecaca;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 32px;
            transition: all 0.4s var(--ease-expo);
        }

        .main-content.expanded {
            margin-left: var(--sidebar-collapsed);
        }

        /* Glassmorphism Top Bar */
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(20px);
            padding: 24px 32px;
            border-radius: var(--radius);
            box-shadow: var(--shadow), 0 0 0 1px rgba(255,255,255,0.5) inset;
            border: 1px solid rgba(255,255,255,0.6);
            animation: slideDown 0.6s var(--ease-expo) backwards;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .welcome-message h1 {
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--secondary);
            margin-bottom: 6px;
            letter-spacing: -0.02em;
        }

        .welcome-message p {
            color: var(--text-secondary);
            font-size: 15px;
            font-weight: 500;
        }

        .period-info {
            background: linear-gradient(135deg, var(--surface-hover), rgba(251, 146, 60, 0.2));
            padding: 10px 20px;
            border-radius: 50px;
            font-weight: 700;
            color: var(--primary-dark);
            display: flex;
            align-items: center;
            gap: 10px;
            border: 2px solid rgba(249, 115, 22, 0.2);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(249, 115, 22, 0.2); }
            50% { box-shadow: 0 0 0 10px rgba(249, 115, 22, 0); }
        }

        /* Period Filter Buttons */
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 16px;
            margin-bottom: 32px;
            animation: slideUp 0.6s var(--ease-expo) 0.1s backwards;
        }

        .period-btn {
            padding: 14px 20px;
            border-radius: 12px;
            border: 2px solid var(--border);
            background: rgba(255,255,255,0.8);
            color: var(--text-secondary);
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s var(--ease-spring);
            font-size: 14px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-family: inherit;
        }

        .period-btn:hover:not(.active) {
            border-color: var(--primary-light);
            color: var(--primary);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(249, 115, 22, 0.15);
        }

        .period-btn.active {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border-color: var(--primary);
            box-shadow: 0 8px 20px rgba(249, 115, 22, 0.3);
            transform: translateY(-2px);
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }

        .stat-card {
            background: var(--surface);
            padding: 24px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            gap: 16px;
            border: 1px solid var(--border);
            transition: all 0.4s var(--ease-spring);
            position: relative;
            overflow: hidden;
            animation: slideUp 0.6s var(--ease-expo) backwards;
        }

        .stat-card:nth-child(1) { animation-delay: 0.2s; }
        .stat-card:nth-child(2) { animation-delay: 0.3s; }
        .stat-card:nth-child(3) { animation-delay: 0.4s; }
        .stat-card:nth-child(4) { animation-delay: 0.5s; }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--primary-light));
            transform: scaleX(0);
            transition: transform 0.4s var(--ease-expo);
        }

        .stat-card:hover {
            transform: translateY(-4px) scale(1.02);
            box-shadow: var(--shadow-xl), var(--shadow-orange);
            border-color: rgba(249, 115, 22, 0.3);
        }

        .stat-card:hover::before {
            transform: scaleX(1);
        }

        .stat-icon {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            transition: all 0.4s var(--ease-spring);
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover .stat-icon {
            transform: scale(1.1) rotate(-5deg);
        }

        .stat-icon::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.4), transparent);
        }

        .stat-icon.revenue { 
            background: linear-gradient(135deg, #FEF3C7, #FDE68A); 
            color: #D97706; 
            box-shadow: 0 8px 20px rgba(217, 119, 6, 0.2);
        }
        .stat-icon.orders { 
            background: linear-gradient(135deg, #DBEAFE, #BFDBFE); 
            color: #2563EB; 
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.2);
        }
        .stat-icon.avg { 
            background: linear-gradient(135deg, #D1FAE5, #A7F3D0); 
            color: #059669; 
            box-shadow: 0 8px 20px rgba(5, 150, 105, 0.2);
        }
        .stat-icon.top { 
            background: linear-gradient(135deg, #FCE7F3, #FBCFE8); 
            color: #DB2777; 
            box-shadow: 0 8px 20px rgba(219, 39, 119, 0.2);
        }

        .stat-info h3 {
            font-size: 28px;
            font-weight: 800;
            margin-bottom: 4px;
            background: linear-gradient(135deg, var(--secondary), #475569);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            line-height: 1;
        }

        .stat-info p {
            color: var(--text-secondary);
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        /* Chart Cards */
        .chart-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border-radius: var(--radius);
            box-shadow: var(--shadow), 0 0 0 1px rgba(255,255,255,0.6) inset;
            padding: 24px;
            margin-bottom: 24px;
            border: 1px solid rgba(255,255,255,0.6);
            transition: all 0.4s var(--ease-spring);
            animation: cardEnter 0.6s var(--ease-expo) backwards;
            position: relative;
            overflow: hidden;
        }

        .chart-card:nth-of-type(1) { animation-delay: 0.6s; }
        .chart-card:nth-of-type(2) { animation-delay: 0.7s; }

        @keyframes cardEnter {
            from { opacity: 0; transform: translateY(40px) rotateX(10deg) scale(0.95); }
            to { opacity: 1; transform: translateY(0) rotateX(0) scale(1); }
        }

        .chart-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--primary-light), var(--primary));
            background-size: 200% 100%;
            animation: shimmer 3s infinite linear;
        }

        @keyframes shimmer {
            0% { background-position: 100% 0; }
            100% { background-position: -100% 0; }
        }

        .chart-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-xl), 0 20px 40px -10px rgba(249, 115, 22, 0.2);
        }

        .chart-title {
            font-size: 1.25rem;
            font-weight: 800;
            margin-bottom: 20px;
            color: var(--secondary);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .chart-title i {
            color: var(--primary);
            background: rgba(249, 115, 22, 0.1);
            padding: 10px;
            border-radius: 10px;
            animation: iconBounce 2s infinite;
        }

        @keyframes iconBounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }

        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }

        /* Charts Grid */
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 24px;
        }

        /* Table Styling */
        .table-container {
            overflow-x: auto;
            border-radius: var(--radius-sm);
            border: 1px solid var(--border);
            background: rgba(255,255,255,0.5);
            backdrop-filter: blur(10px);
        }

        .orders-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .orders-table thead {
            background: linear-gradient(135deg, rgba(249, 115, 22, 0.1), rgba(251, 146, 60, 0.05));
        }

        .orders-table th {
            padding: 16px;
            text-align: left;
            font-weight: 700;
            color: var(--secondary);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid var(--primary-light);
        }

        .orders-table tbody tr {
            transition: all 0.3s var(--ease-spring);
        }

        .orders-table tbody tr:hover {
            background: rgba(249, 115, 22, 0.05);
            transform: translateX(8px);
        }

        .orders-table td {
            padding: 16px;
            border-bottom: 1px solid var(--border);
            font-weight: 600;
        }

        .orders-table tr:last-child td {
            border-bottom: none;
        }

        /* Mobile */
        .mobile-toggle {
            display: none;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 99;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            border: none;
            width: 48px;
            height: 48px;
            border-radius: 14px;
            font-size: 20px;
            cursor: pointer;
            box-shadow: var(--shadow-lg), 0 0 0 1px rgba(255,255,255,0.3) inset;
            transition: all 0.3s var(--ease-spring);
        }

        .mobile-toggle:hover {
            transform: scale(1.1) rotate(5deg);
        }

        @media (max-width: 1200px) {
            .charts-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
                box-shadow: 10px 0 40px rgba(0,0,0,0.2);
            }
            .sidebar.active {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
                padding: 20px;
            }
            .mobile-toggle {
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .top-bar {
                margin-top: 60px;
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .filter-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .welcome-message h1 {
                font-size: 1.5rem;
            }
            .chart-container {
                height: 250px;
            }
        }

        @media (max-width: 480px) {
            .filter-grid {
                grid-template-columns: 1fr;
            }
            .chart-card {
                padding: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="bg-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
    </div>

    <button class="mobile-toggle" id="mobileToggle">
        <i class="fas fa-bars"></i>
    </button>

    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-toggle" id="sidebarToggle">
                <i class="fas fa-chevron-left"></i>
            </div>

            <div class="logo">
                <div class="logo-icon">
                    <i class="fas fa-store"></i>
                </div>
                <span class="logo-text"><?= htmlspecialchars($shop_name) ?></span>
            </div>

            <nav class="nav-menu">
                <a href="dashboard.php" class="nav-item">
                    <i class="fas fa-tachometer-alt"></i>
                    <span class="nav-text">Dashboard</span>
                </a>
                <a href="menu.php" class="nav-item">
                    <i class="fas fa-utensils"></i>
                    <span class="nav-text">Menu</span>
                </a>
                <a href="orders.php" class="nav-item">
                    <i class="fas fa-clipboard-list"></i>
                    <span class="nav-text">Orders</span>
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
                        <?= strtoupper(substr($shop_name, 0, 1)) ?>
                    </div>
                    <div class="user-name"><?= htmlspecialchars($shop_name) ?></div>
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
                    <p>Track your business performance</p>
                </div>
                <div class="period-info">
                    <i class="fas fa-calendar-alt"></i>
                    <span><?= $title ?></span>
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
                    <div class="stat-icon revenue">
                        <i class="fas fa-rupee-sign"></i>
                    </div>
                    <div class="stat-info">
                        <h3>₹<?= number_format($revenue, 2) ?></h3>
                        <p>Total Revenue</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon orders">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= number_format($total_orders) ?></h3>
                        <p>Total Orders</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon avg">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-info">
                        <h3>₹<?= number_format($avg_order, 2) ?></h3>
                        <p>Avg Order Value</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon top">
                        <i class="fas fa-trophy"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= count($top_items) ?></h3>
                        <p>Top Items</p>
                    </div>
                </div>
            </div>

            <!-- Charts Grid -->
            <div class="charts-grid">
                <!-- Revenue Trend -->
                <div class="chart-card">
                    <h3 class="chart-title"><i class="fas fa-chart-line"></i> Revenue Trend</h3>
                    <div class="chart-container">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>

                <!-- Order Status -->
                <div class="chart-card">
                    <h3 class="chart-title"><i class="fas fa-chart-pie"></i> Order Status</h3>
                    <div class="chart-container">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>

                <!-- Top Items Table -->
                <div class="chart-card">
                    <h3 class="chart-title"><i class="fas fa-star"></i> Top Selling Items</h3>
                    <div class="table-container">
                        <table class="orders-table">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Qty Sold</th>
                                    <th>Revenue</th>
                                    <th>Avg Price</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($top_items)): ?>
                                    <?php foreach ($top_items as $item): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($item['item_name']) ?></td>
                                            <td><?= $item['total_qty'] ?></td>
                                            <td>₹<?= number_format($item['total_revenue'], 2) ?></td>
                                            <td>₹<?= number_format($item['avg_price'], 2) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" style="text-align: center; color: var(--text-secondary); padding: 40px;">
                                            <i class="fas fa-inbox fa-2x" style="margin-bottom: 10px; display: block;"></i>
                                            No data available for this period
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Orders Chart -->
                <div class="chart-card">
                    <h3 class="chart-title"><i class="fas fa-chart-bar"></i> Daily Orders</h3>
                    <div class="chart-container">
                        <canvas id="ordersChart"></canvas>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Sidebar Toggle with smooth animation
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
            mobileToggle.style.transform = sidebar.classList.contains('active') ? 'rotate(90deg)' : 'rotate(0deg)';
        });

        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 992 && 
                !sidebar.contains(e.target) && 
                !mobileToggle.contains(e.target)) {
                sidebar.classList.remove('active');
                mobileToggle.style.transform = 'rotate(0deg)';
            }
        });

        updateSidebar();

        // Chart Data
        const dailyDates = [<?php
            $dates = [];
            foreach ($daily_data as $row) $dates[] = "'".date('M d', strtotime($row['date']))."'";
            echo implode(',', $dates);
        ?>];

        const dailyRevenue = [<?php
            $revs = [];
            foreach ($daily_data as $row) $revs[] = $row['revenue'];
            echo implode(',', $revs);
        ?>];

        const dailyOrders = [<?php
            $ords = [];
            foreach ($daily_data as $row) $ords[] = $row['count'];
            echo implode(',', $ords);
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

        // Chart Configuration
        Chart.defaults.font.family = "'Plus Jakarta Sans', sans-serif";
        Chart.defaults.color = '#64748B';

        // Revenue Line Chart
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: dailyDates,
                datasets: [{
                    label: 'Revenue (₹)',
                    data: dailyRevenue,
                    borderColor: '#F97316',
                    backgroundColor: 'rgba(249, 115, 22, 0.1)',
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#F97316',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0,0,0,0.05)' }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });

        // Status Doughnut Chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: statusLabels,
                datasets: [{
                    data: statusValues,
                    backgroundColor: [
                        '#F97316', // pending - orange
                        '#10B981', // paid - green
                        '#3B82F6', // completed - blue
                        '#EF4444'  // cancelled - red
                    ],
                    borderWidth: 0,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    }
                }
            }
        });

        // Orders Bar Chart
        const ordersCtx = document.getElementById('ordersChart').getContext('2d');
        new Chart(ordersCtx, {
            type: 'bar',
            data: {
                labels: dailyDates,
                datasets: [{
                    label: 'Orders',
                    data: dailyOrders,
                    backgroundColor: 'rgba(249, 115, 22, 0.8)',
                    borderRadius: 8,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0,0,0,0.05)' }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });
    </script>
</body>
</html>