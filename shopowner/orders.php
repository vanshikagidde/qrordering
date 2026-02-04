<?php
// orders.php - Shop Owner All Orders Page
session_start();
include "../config/db.php";

if (!isset($_SESSION['shop_id']) || !isset($_SESSION['logged_in'])) {
    header("Location: login.php");
    exit;
}

$shop_id = (int)$_SESSION['shop_id'];
$shop_name = $_SESSION['shop_name'] ?? 'My Shop';
$message = '';
$message_type = '';

// Handle AJAX status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'update_status') {
        $order_id   = (int)($_POST['order_id'] ?? 0);
        $new_status = trim($_POST['new_status'] ?? '');

        $allowed_statuses = ['pending', 'paid', 'completed', 'cancelled'];

        if ($order_id > 0 && in_array($new_status, $allowed_statuses)) {
            $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE order_id = ? AND shop_id = ?");
            $stmt->bind_param("sii", $new_status, $order_id, $shop_id);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Status updated']);
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

// Filters
$status_filter = $_GET['status'] ?? 'all';
$date_from     = $_GET['date_from'] ?? '';
$date_to       = $_GET['date_to'] ?? '';
$search_query  = $_GET['search'] ?? '';

// Build query
$query = "SELECT o.order_id, o.table_no, o.total, o.token, o.status, o.created_at,
                 COUNT(oi.order_item_id) as item_count,
                 GROUP_CONCAT(CONCAT(oi.quantity, '× ', oi.item_name) SEPARATOR ', ') as item_summary
          FROM orders o
          LEFT JOIN order_item oi ON oi.order_id = o.order_id
          WHERE o.shop_id = ?";

$params = [$shop_id];
$types  = "i";

if ($status_filter !== 'all') {
    $query .= " AND o.status = ?";
    $params[] = $status_filter;
    $types   .= "s";
}

if ($date_from) {
    $query .= " AND DATE(o.created_at) >= ?";
    $params[] = $date_from;
    $types   .= "s";
}

if ($date_to) {
    $query .= " AND DATE(o.created_at) <= ?";
    $params[] = $date_to;
    $types   .= "s";
}

if ($search_query) {
    $query .= " AND (o.order_id LIKE ? OR o.table_no LIKE ? OR o.token LIKE ? OR oi.item_name LIKE ?)";
    $search_param = "%$search_query%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types   .= "ssss";
}

$query .= " GROUP BY o.order_id ORDER BY o.created_at DESC LIMIT 200";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Status counts
$status_counts = ['pending' => 0, 'paid' => 0, 'completed' => 0, 'cancelled' => 0];
foreach ($orders as $o) {
    if (isset($status_counts[$o['status']])) {
        $status_counts[$o['status']]++;
    }
}
$total_orders = count($orders);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Orders - <?= htmlspecialchars($shop_name) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
    <style>
        :root {
            --primary: #F97316;
            --primary-light: #FB923C;
            --primary-dark: #EA580C;
            --secondary: #0F172A;
            --surface: #FFFFFF;
            --background: #F8FAFC;
            --text: #1E293B;
            --text-secondary: #64748B;
            --border: #E2E8F0;
            --success: #10B981;
            --warning: #F59E0B;
            --info: #3B82F6;
            --danger: #EF4444;
            
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1);
            --shadow-orange: 0 10px 40px -10px rgba(249, 115, 22, 0.5);
            
            --ease-spring: cubic-bezier(0.34, 1.56, 0.64, 1);
            --ease-smooth: cubic-bezier(0.4, 0, 0.2, 1);
            --ease-expo: cubic-bezier(0.16, 1, 0.3, 1);
            --radius: 16px;
            --radius-sm: 12px;
            --sidebar-width: 280px;
        }

        * { margin:0; padding:0; box-sizing:border-box; }
        
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: linear-gradient(135deg, #F8FAFC 0%, #FFF7ED 100%);
            color: var(--text);
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Background Animation */
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
            box-shadow: 0 8px 20px rgba(249, 115, 22, 0.3);
            animation: iconPulse 3s infinite;
        }

        @keyframes iconPulse {
            0%, 100% { box-shadow: 0 8px 20px rgba(249, 115, 22, 0.3); }
            50% { box-shadow: 0 12px 30px rgba(249, 115, 22, 0.5); }
        }

        .logo-text {
            font-size: 1.4rem;
            font-weight: 800;
            background: linear-gradient(135deg, #ffffff, #cbd5e1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
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
            font-weight: 500;
            font-size: 15px;
        }

        .nav-item:hover {
            color: white;
            background: rgba(255,255,255,0.05);
            transform: translateX(6px);
        }

        .nav-item.active {
            background: linear-gradient(135deg, rgba(249, 115, 22, 0.2), rgba(249, 115, 22, 0.05));
            color: var(--primary-light);
            font-weight: 600;
        }

        .nav-item i {
            font-size: 18px;
            width: 24px;
            text-align: center;
        }

        .user-actions {
            margin-top: auto;
            padding-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
        }

        .user-info {
            padding: 0 8px 16px;
            text-align: center;
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
        }

        .user-name {
            font-weight: 700;
            font-size: 15px;
        }

        .user-role {
            font-size: 12px;
            color: rgba(255,255,255,0.4);
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
            transition: all 0.3s;
            width: 100%;
            text-align: left;
            font-weight: 500;
        }

        .action-btn:hover {
            color: white;
            background: rgba(255,255,255,0.08);
        }

        .action-btn.logout {
            color: #fca5a5;
            margin-top: 4px;
        }

        .action-btn.logout:hover {
            background: rgba(239, 68, 68, 0.15);
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 32px;
            transition: all 0.4s var(--ease-expo);
        }

        /* Top Bar */
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
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
            letter-spacing: -0.02em;
        }

        .welcome-message p {
            color: var(--text-secondary);
            font-size: 14px;
            font-weight: 500;
            margin-top: 4px;
        }

        .live-indicator {
            display: flex;
            align-items: center;
            gap: 8px;
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 700;
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .live-indicator::before {
            content: '';
            width: 8px;
            height: 8px;
            background: var(--success);
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(0.8); }
        }

        /* Stats Pills */
        .stats-pills {
            display: flex;
            gap: 12px;
            margin-bottom: 24px;
            flex-wrap: wrap;
            animation: slideUp 0.6s var(--ease-expo) 0.1s backwards;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .stat-pill {
            background: var(--surface);
            padding: 12px 20px;
            border-radius: 50px;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
            transition: all 0.3s var(--ease-spring);
            cursor: pointer;
        }

        .stat-pill:hover, .stat-pill.active {
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .stat-pill.active {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            border-color: transparent;
        }

        .stat-pill i {
            font-size: 14px;
        }

        .stat-pill span {
            font-weight: 700;
            font-size: 14px;
        }

        .stat-pill small {
            font-size: 12px;
            opacity: 0.8;
            margin-left: 4px;
        }

        /* Search & Filter Bar */
        .filter-bar {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(20px);
            padding: 16px 24px;
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
            border: 1px solid rgba(255,255,255,0.6);
            margin-bottom: 24px;
            display: flex;
            gap: 16px;
            align-items: center;
            flex-wrap: wrap;
            animation: slideUp 0.6s var(--ease-expo) 0.2s backwards;
        }

        .search-box {
            flex: 1;
            min-width: 300px;
            position: relative;
        }

        .search-box input {
            width: 100%;
            padding: 12px 16px 12px 44px;
            border: 2px solid var(--border);
            border-radius: 12px;
            font-size: 14px;
            font-family: inherit;
            transition: all 0.3s;
            background: white;
        }

        .search-box input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(249, 115, 22, 0.1);
        }

        .search-box i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
        }

        .date-filters {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .date-filters input {
            padding: 10px 14px;
            border: 2px solid var(--border);
            border-radius: 10px;
            font-size: 13px;
            font-family: inherit;
        }

        .clear-btn {
            background: var(--surface);
            border: 2px solid var(--border);
            color: var(--text-secondary);
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .clear-btn:hover {
            border-color: var(--primary);
            color: var(--primary);
        }

        /* Orders Grid */
        .orders-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
            animation: slideUp 0.6s var(--ease-expo) 0.3s backwards;
        }

        .order-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
            border: 1px solid rgba(255,255,255,0.6);
            overflow: hidden;
            transition: all 0.4s var(--ease-spring);
            animation: cardEnter 0.6s var(--ease-expo) backwards;
            position: relative;
        }

        .order-card:nth-child(1) { animation-delay: 0.1s; }
        .order-card:nth-child(2) { animation-delay: 0.15s; }
        .order-card:nth-child(3) { animation-delay: 0.2s; }
        .order-card:nth-child(4) { animation-delay: 0.25s; }
        .order-card:nth-child(5) { animation-delay: 0.3s; }
        .order-card:nth-child(6) { animation-delay: 0.35s; }

        @keyframes cardEnter {
            from { 
                opacity: 0; 
                transform: translateY(30px) scale(0.95); 
            }
            to { 
                opacity: 1; 
                transform: translateY(0) scale(1); 
            }
        }

        .order-card:hover {
            transform: translateY(-6px) scale(1.02);
            box-shadow: var(--shadow-xl), var(--shadow-orange);
        }

        .order-card.new-order {
            animation: newOrderPulse 2s infinite;
        }

        @keyframes newOrderPulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(249, 115, 22, 0.4); }
            50% { box-shadow: 0 0 0 15px rgba(249, 115, 22, 0); }
        }

        .order-header {
            background: linear-gradient(135deg, var(--secondary), #1e293b);
            color: white;
            padding: 16px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .order-id {
            font-size: 18px;
            font-weight: 800;
        }

        .order-time {
            font-size: 12px;
            opacity: 0.8;
            font-weight: 500;
        }

        .order-body {
            padding: 20px;
        }

        .order-meta {
            display: flex;
            gap: 12px;
            margin-bottom: 16px;
        }

        .meta-badge {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            background: rgba(249, 115, 22, 0.1);
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            color: var(--primary-dark);
        }

        .meta-badge i {
            font-size: 12px;
        }

        .order-items {
            margin-bottom: 16px;
        }

        .item-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px dashed var(--border);
            font-size: 14px;
        }

        .item-row:last-child {
            border-bottom: none;
        }

        .item-name {
            font-weight: 600;
            color: var(--text);
        }

        .item-qty {
            background: var(--primary);
            color: white;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 700;
        }

        .order-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 16px;
            border-top: 2px solid var(--border);
        }

        .order-total {
            font-size: 20px;
            font-weight: 800;
            color: var(--secondary);
        }

        .order-total small {
            font-size: 12px;
            color: var(--text-secondary);
            display: block;
            font-weight: 500;
        }

        .status-select {
            padding: 10px 16px;
            border: 2px solid var(--border);
            border-radius: 10px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            background: white;
            min-width: 140px;
        }

        .status-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(249, 115, 22, 0.1);
        }

        .status-select option {
            font-weight: 600;
        }

        /* Status Colors */
        .status-pending { color: var(--warning); background: #FEF3C7; }
        .status-paid { color: var(--info); background: #DBEAFE; }
        .status-completed { color: var(--success); background: #D1FAE5; }
        .status-cancelled { color: var(--danger); background: #FEE2E2; }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 80px 40px;
            grid-column: 1 / -1;
            animation: fadeIn 0.6s var(--ease-expo) backwards;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .empty-icon {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, rgba(249, 115, 22, 0.1), rgba(251, 146, 60, 0.05));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            font-size: 48px;
            color: var(--primary);
            animation: float 6s ease-in-out infinite;
        }

        .empty-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--secondary);
            margin-bottom: 8px;
        }

        .empty-subtitle {
            color: var(--text-secondary);
            font-size: 15px;
        }

        /* Mobile Toggle */
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
            box-shadow: var(--shadow-lg);
            transition: all 0.3s var(--ease-spring);
        }

        .mobile-toggle:hover {
            transform: scale(1.1) rotate(5deg);
        }

        /* Notification */
        .notification {
            position: fixed;
            top: 24px;
            right: 24px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 16px 24px;
            border-radius: var(--radius);
            box-shadow: var(--shadow-xl);
            display: flex;
            align-items: center;
            gap: 12px;
            z-index: 1000;
            animation: slideInRight 0.4s var(--ease-spring);
            border-left: 4px solid var(--primary);
            font-weight: 600;
            font-size: 14px;
            min-width: 300px;
        }

        @keyframes slideInRight {
            from { transform: translateX(100%) scale(0.9); opacity: 0; }
            to { transform: translateX(0) scale(1); opacity: 1; }
        }

        .notification.success { border-left-color: var(--success); }
        .notification.error { border-left-color: var(--danger); }

        .notification button {
            background: none;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            margin-left: auto;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        .notification button:hover {
            background: var(--background);
            color: var(--text);
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .orders-grid {
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
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
                gap: 16px;
                text-align: center;
            }
            .filter-bar {
                flex-direction: column;
                align-items: stretch;
            }
            .search-box {
                min-width: auto;
            }
            .date-filters {
                width: 100%;
                justify-content: space-between;
            }
        }

        @media (max-width: 640px) {
            .orders-grid {
                grid-template-columns: 1fr;
            }
            .stats-pills {
                justify-content: center;
            }
        }

        /* Loading Overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255,255,255,0.8);
            backdrop-filter: blur(4px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s;
        }

        .loading-overlay.active {
            opacity: 1;
            pointer-events: all;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid var(--border);
            border-top-color: var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="bg-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
    </div>

    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner"></div>
    </div>

    <button class="mobile-toggle" id="mobileToggle">
        <i class="fas fa-bars"></i>
    </button>

    <div class="dashboard-container">
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
                <a href="orders.php" class="nav-item active">
                    <i class="fas fa-clipboard-list"></i>
                    <span class="nav-text">Orders</span>
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

        <main class="main-content" id="mainContent">
            <div class="top-bar">
                <div class="welcome-message">
                    <h1>All Orders</h1>
                    <p>Manage and track customer orders</p>
                </div>
                <div class="live-indicator">
                    <span>LIVE</span>
                    <small>Auto-refresh</small>
                </div>
            </div>

            <!-- Stats Pills -->
            <div class="stats-pills">
                <div class="stat-pill active" onclick="filterOrders('all')">
                    <i class="fas fa-layer-group"></i>
                    <span><?= $total_orders ?></span>
                    <small>All</small>
                </div>
                <div class="stat-pill" onclick="filterOrders('pending')" style="color: var(--warning);">
                    <i class="fas fa-clock"></i>
                    <span><?= $status_counts['pending'] ?></span>
                    <small>Pending</small>
                </div>
                <div class="stat-pill" onclick="filterOrders('paid')" style="color: var(--info);">
                    <i class="fas fa-check-circle"></i>
                    <span><?= $status_counts['paid'] ?></span>
                    <small>Paid</small>
                </div>
                <div class="stat-pill" onclick="filterOrders('completed')" style="color: var(--success);">
                    <i class="fas fa-check-double"></i>
                    <span><?= $status_counts['completed'] ?></span>
                    <small>Done</small>
                </div>
                <div class="stat-pill" onclick="filterOrders('cancelled')" style="color: var(--danger);">
                    <i class="fas fa-times-circle"></i>
                    <span><?= $status_counts['cancelled'] ?></span>
                    <small>Cancelled</small>
                </div>
            </div>

            <!-- Filter Bar -->
            <div class="filter-bar">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Search orders by ID, table, token or items..." 
                           value="<?= htmlspecialchars($search_query) ?>">
                </div>
                <div class="date-filters">
                    <input type="date" id="dateFrom" value="<?= htmlspecialchars($date_from) ?>">
                    <span style="color: var(--text-secondary);">to</span>
                    <input type="date" id="dateTo" value="<?= htmlspecialchars($date_to) ?>">
                </div>
                <button class="clear-btn" onclick="clearFilters()">
                    <i class="fas fa-redo"></i> Reset
                </button>
            </div>

            <!-- Orders Grid -->
            <div class="orders-grid" id="ordersGrid">
                <?php if (empty($orders)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i class="fas fa-receipt"></i>
                        </div>
                        <h3 class="empty-title">No orders found</h3>
                        <p class="empty-subtitle">Try adjusting your filters or wait for new orders</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($orders as $order): ?>
                        <div class="order-card" data-order-id="<?= $order['order_id'] ?>" data-status="<?= $order['status'] ?>">
                            <div class="order-header">
                                <div class="order-id">#<?= $order['order_id'] ?></div>
                                <div class="order-time">
                                    <?= date('d M Y', strtotime($order['created_at'])) ?><br>
                                    <?= date('h:i A', strtotime($order['created_at'])) ?>
                                </div>
                            </div>
                            
                            <div class="order-body">
                                <div class="order-meta">
                                    <?php if (!empty($order['token']) && $order['token'] !== '0'): ?>
                                        <div class="meta-badge">
                                            <i class="fas fa-hashtag"></i>
                                            Token <?= htmlspecialchars($order['token']) ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($order['table_no'])): ?>
                                        <div class="meta-badge" style="background: rgba(59, 130, 246, 0.1); color: #1D4ED8;">
                                            <i class="fas fa-chair"></i>
                                            Table <?= htmlspecialchars($order['table_no']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="order-items">
                                    <?php 
                                    $items = explode(', ', $order['item_summary'] ?? '');
                                    foreach (array_slice($items, 0, 3) as $item): 
                                        if (empty($item)) continue;
                                        list($qty, $name) = explode('× ', $item, 2);
                                    ?>
                                        <div class="item-row">
                                            <span class="item-name"><?= htmlspecialchars($name ?? $item) ?></span>
                                            <span class="item-qty"><?= $qty ?? 1 ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                    <?php if (count($items) > 3): ?>
                                        <div style="text-align: center; padding-top: 8px; color: var(--text-secondary); font-size: 12px; font-weight: 600;">
                                            +<?= count($items) - 3 ?> more items
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="order-footer">
                                    <div class="order-total">
                                        <small>Total</small>
                                        ₹<?= number_format($order['total'], 0) ?>
                                    </div>
                                    <select class="status-select status-<?= $order['status'] ?>" 
                                            onchange="updateStatus(<?= $order['order_id'] ?>, this.value)"
                                            data-current="<?= $order['status'] ?>">
                                        <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : '' ?>>⏳ Pending</option>
                                        <option value="paid" <?= $order['status'] === 'paid' ? 'selected' : '' ?>>💳 Paid</option>
                                        <option value="completed" <?= $order['status'] === 'completed' ? 'selected' : '' ?>>✅ Completed</option>
                                        <option value="cancelled" <?= $order['status'] === 'cancelled' ? 'selected' : '' ?>>❌ Cancelled</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        // Sidebar Toggle
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const mobileToggle = document.getElementById('mobileToggle');

        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            if (sidebar.classList.contains('collapsed')) {
                sidebarToggle.innerHTML = '<i class="fas fa-chevron-right"></i>';
            } else {
                sidebarToggle.innerHTML = '<i class="fas fa-chevron-left"></i>';
            }
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

        // Notification System
        function showNotification(message, type = 'success') {
            const existing = document.querySelector('.notification');
            if (existing) existing.remove();

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
                if (notification.parentElement) notification.remove();
            }, 3000);
        }

        // Update Status
        function updateStatus(orderId, newStatus) {
            const select = document.querySelector(`[data-order-id="${orderId}"] .status-select`);
            const oldStatus = select.dataset.current;
            
            // Visual feedback
            select.style.pointerEvents = 'none';
            select.style.opacity = '0.6';

            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=update_status&order_id=${orderId}&new_status=${encodeURIComponent(newStatus)}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showNotification(`Order #${orderId} updated to ${newStatus}`);
                    select.dataset.current = newStatus;
                    select.className = `status-select status-${newStatus}`;
                    
                    // Update stats after delay
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showNotification(data.error || 'Update failed', 'error');
                    select.value = oldStatus;
                }
            })
            .catch(() => {
                showNotification('Network error', 'error');
                select.value = oldStatus;
            })
            .finally(() => {
                select.style.pointerEvents = '';
                select.style.opacity = '';
            });
        }

        // Filter Orders
        function filterOrders(status) {
            document.querySelectorAll('.stat-pill').forEach(pill => pill.classList.remove('active'));
            event.currentTarget.classList.add('active');
            
            const url = new URL(window.location);
            if (status === 'all') {
                url.searchParams.delete('status');
            } else {
                url.searchParams.set('status', status);
            }
            window.location.href = url.toString();
        }

        // Clear Filters
        function clearFilters() {
            document.getElementById('searchInput').value = '';
            document.getElementById('dateFrom').value = '';
            document.getElementById('dateTo').value = '';
            window.location.href = 'orders.php';
        }

        // Search with debounce
        let searchTimeout;
        document.getElementById('searchInput').addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const url = new URL(window.location);
                if (this.value) {
                    url.searchParams.set('search', this.value);
                } else {
                    url.searchParams.delete('search');
                }
                window.location.href = url.toString();
            }, 500);
        });

        // Date filters
        document.getElementById('dateFrom').addEventListener('change', function() {
            const url = new URL(window.location);
            if (this.value) url.searchParams.set('date_from', this.value);
            else url.searchParams.delete('date_from');
            window.location.href = url.toString();
        });

        document.getElementById('dateTo').addEventListener('change', function() {
            const url = new URL(window.location);
            if (this.value) url.searchParams.set('date_to', this.value);
            else url.searchParams.delete('date_to');
            window.location.href = url.toString();
        });

        // AUTO REFRESH every 3 seconds
        let lastOrderCount = document.querySelectorAll('.order-card').length;
        
        setInterval(() => {
            fetch(window.location.href + '?ajax=1')
                .then(response => response.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newOrders = doc.querySelectorAll('.order-card');
                    const currentOrders = document.querySelectorAll('.order-card');
                    
                    if (newOrders.length !== currentOrders.length) {
                        // New orders detected!
                        const newCount = newOrders.length - currentOrders.length;
                        showNotification(`${newCount} new order(s) received!`, 'success');
                        
                        // Highlight new orders
                        document.getElementById('ordersGrid').innerHTML = doc.getElementById('ordersGrid').innerHTML;
                        
                        // Add pulse animation to new orders
                        document.querySelectorAll('.order-card').forEach((card, index) => {
                            if (index < newCount) {
                                card.classList.add('new-order');
                            }
                        });
                        
                        lastOrderCount = newOrders.length;
                    }
                })
                .catch(() => {});
        }, 3000);

        // Set default dates
        if (!document.getElementById('dateFrom').value) {
            const thirtyDaysAgo = new Date();
            thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);
            document.getElementById('dateFrom').value = thirtyDaysAgo.toISOString().split('T')[0];
        }
        if (!document.getElementById('dateTo').value) {
            document.getElementById('dateTo').value = new Date().toISOString().split('T')[0];
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey && e.key === 'k') {
                e.preventDefault();
                document.getElementById('searchInput').focus();
            }
            if (e.key === 'Escape') {
                sidebar.classList.remove('active');
            }
        });

        // 3D Tilt effect on cards
        document.querySelectorAll('.order-card').forEach(card => {
            card.addEventListener('mousemove', (e) => {
                const rect = card.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                const centerX = rect.width / 2;
                const centerY = rect.height / 2;
                const rotateX = (y - centerY) / 20;
                const rotateY = (centerX - x) / 20;
                
                card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateY(-6px) scale(1.02)`;
            });
            
            card.addEventListener('mouseleave', () => {
                card.style.transform = '';
            });
        });
    </script>
</body>
</html>