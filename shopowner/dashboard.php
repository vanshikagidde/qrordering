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
$shop_name = $_SESSION['shop_name'] ?? 'My Shop';

// Handle AJAX status update → now updates order_item status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_status') {
        header('Content-Type: application/json');

        $order_id   = (int)($_POST['order_id'] ?? 0);
        $new_status = trim($_POST['new_status'] ?? '');

        $allowed_statuses = ['preparing', 'served', 'cancelled'];

        if ($order_id > 0 && in_array($new_status, $allowed_statuses)) {
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
        $orders[$row['order_id']]['current_status'] = 'pending';
        $order_ids[] = $row['order_id'];
    }
    $stmt->close();

    if (empty($order_ids)) {
        return $orders;
    }

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

    $priority = [
        'cancelled' => 0,
        'served'    => 1,
        'preparing' => 2,
        'pending'   => 3
    ];

    while ($item = $result->fetch_assoc()) {
        $oid = $item['order_id'];
        $orders[$oid]['items'][] = $item;

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
    <title>Live Orders - <?= htmlspecialchars($shop_name) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
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
            --ease-bounce: cubic-bezier(0.68, -0.55, 0.265, 1.55);
            
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

        /* Animated Stats Cards */
        .stats-bar {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
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

        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .stat-card:nth-child(3) { animation-delay: 0.3s; }

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

        .stat-icon.pending { 
            background: linear-gradient(135deg, #FEF3C7, #FDE68A); 
            color: #D97706; 
            box-shadow: 0 8px 20px rgba(217, 119, 6, 0.2);
        }
        .stat-icon.preparing { 
            background: linear-gradient(135deg, #DBEAFE, #BFDBFE); 
            color: #2563EB; 
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.2);
        }
        .stat-icon.served { 
            background: linear-gradient(135deg, #D1FAE5, #A7F3D0); 
            color: #059669; 
            box-shadow: 0 8px 20px rgba(5, 150, 105, 0.2);
        }

        .stat-info h3 {
            font-size: 32px;
            font-weight: 800;
            margin-bottom: 4px;
            background: linear-gradient(135deg, var(--secondary), #475569);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            line-height: 1;
        }

        .stat-info p {
            color: var(--text-secondary);
            font-size: 14px;
            font-weight: 600;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        /* Section Header */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            animation: fadeIn 0.6s var(--ease-expo) 0.4s backwards;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--secondary);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .section-title::after {
            content: '';
            width: 8px;
            height: 8px;
            background: var(--primary);
            border-radius: 50%;
            animation: blink 2s infinite;
        }

        @keyframes blink {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(0.8); }
        }

        .refresh-btn {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s var(--ease-spring);
            box-shadow: 0 4px 15px rgba(249, 115, 22, 0.3);
            position: relative;
            overflow: hidden;
        }

        .refresh-btn::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, transparent, rgba(255,255,255,0.3), transparent);
            transform: translateX(-100%);
            transition: transform 0.6s;
        }

        .refresh-btn:hover {
            transform: translateY(-2px) scale(1.05);
            box-shadow: 0 8px 25px rgba(249, 115, 22, 0.4);
        }

        .refresh-btn:hover::before {
            transform: translateX(100%);
        }

        .refresh-btn:active {
            transform: scale(0.98);
        }

        .refresh-btn i {
            transition: transform 0.5s var(--ease-spring);
        }

        .refresh-btn:hover i {
            transform: rotate(180deg);
        }

        /* Compact Attractive Order Cards */
        .orders-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 24px;
            perspective: 1000px;
        }

        .order-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border-radius: var(--radius);
            box-shadow: var(--shadow), 0 0 0 1px rgba(255,255,255,0.6) inset;
            padding: 20px;
            transition: all 0.4s var(--ease-spring);
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.6);
            animation: cardEnter 0.6s var(--ease-expo) backwards;
            transform-style: preserve-3d;
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
                transform: translateY(40px) rotateX(10deg) scale(0.95); 
            }
            to { 
                opacity: 1; 
                transform: translateY(0) rotateX(0) scale(1); 
            }
        }

        .order-card::before {
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

        .order-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 50% 0%, rgba(249, 115, 22, 0.1), transparent 70%);
            opacity: 0;
            transition: opacity 0.3s;
            pointer-events: none;
        }

        .order-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: var(--shadow-xl), 0 20px 40px -10px rgba(249, 115, 22, 0.2);
            border-color: rgba(249, 115, 22, 0.3);
        }

        .order-card:hover::after {
            opacity: 1;
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }

        .order-id {
            font-size: 18px;
            font-weight: 800;
            color: var(--secondary);
            letter-spacing: -0.02em;
        }

        .order-time {
            font-size: 12px;
            color: var(--text-secondary);
            background: var(--background);
            padding: 4px 10px;
            border-radius: 20px;
            font-weight: 600;
            border: 1px solid var(--border);
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 11px;
            margin-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: 1px solid transparent;
            animation: pulse 2s infinite;
            position: relative;
            overflow: hidden;
        }

        @keyframes pulse {
            0%, 100% { box-shadow: 0 0 0 0 currentColor; }
            50% { box-shadow: 0 0 0 4px transparent; }
        }

        .status-pending { 
            background: #FEF3C7; 
            color: #B45309; 
            border-color: #FCD34D;
        }
        .status-preparing { 
            background: #DBEAFE; 
            color: #1D4ED8; 
            border-color: #93C5FD;
        }
        .status-served { 
            background: #D1FAE5; 
            color: #047857; 
            border-color: #6EE7B7;
        }
        .status-cancelled { 
            background: #FEE2E2; 
            color: #B91C1C; 
            border-color: #FCA5A5;
        }

        .order-meta {
            display: flex;
            gap: 8px;
            margin-bottom: 12px;
            flex-wrap: wrap;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            background: linear-gradient(135deg, var(--background), white);
            border-radius: 8px;
            font-size: 12px;
            color: var(--text-secondary);
            font-weight: 600;
            border: 1px solid var(--border);
            transition: all 0.2s;
        }

        .meta-item:hover {
            transform: translateY(-2px);
            border-color: var(--primary-light);
            color: var(--primary-dark);
        }

        .meta-item i {
            color: var(--primary);
            font-size: 11px;
        }

        .items-list {
            margin: 12px 0;
            max-height: 100px;
            overflow-y: auto;
            padding-right: 4px;
        }

        .items-list::-webkit-scrollbar {
            width: 4px;
        }

        .items-list::-webkit-scrollbar-thumb {
            background: linear-gradient(var(--primary), var(--primary-light));
            border-radius: 2px;
        }

        .item-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 6px 0;
            border-bottom: 1px dashed var(--border);
            transition: all 0.2s;
        }

        .item-row:hover {
            background: var(--surface-hover);
            margin: 0 -8px;
            padding: 6px 8px;
            border-radius: 6px;
            border-bottom-color: transparent;
        }

        .item-row:last-child {
            border-bottom: none;
        }

        .item-name {
            flex: 2;
            font-weight: 600;
            color: var(--text);
            font-size: 13px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .item-qty {
            flex: 0.5;
            text-align: center;
            color: var(--primary);
            font-weight: 800;
            font-size: 12px;
            background: rgba(249, 115, 22, 0.1);
            padding: 2px 8px;
            border-radius: 12px;
        }

        .item-price {
            flex: 1;
            text-align: right;
            font-weight: 700;
            color: var(--secondary);
            font-size: 12px;
        }

        .order-footer {
            margin-top: 12px;
            padding-top: 12px;
            border-top: 2px solid var(--border);
            position: relative;
        }

        .order-total {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 15px;
            font-weight: 800;
            margin-bottom: 12px;
            color: var(--secondary);
        }

        .order-total span:last-child {
            font-size: 18px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .status-selector {
            width: 100%;
            padding: 10px 14px;
            font-size: 13px;
            border: 2px solid var(--border);
            border-radius: 10px;
            background: white;
            cursor: pointer;
            font-weight: 700;
            color: var(--text);
            transition: all 0.3s var(--ease-spring);
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23F97316' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 14px center;
            padding-right: 40px;
        }

        .status-selector:hover {
            border-color: var(--primary-light);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(249, 115, 22, 0.15);
        }

        .status-selector:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(249, 115, 22, 0.1);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 80px 40px;
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(20px);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            grid-column: 1 / -1;
            border: 2px dashed var(--border);
            animation: float 6s ease-in-out infinite;
        }

        .empty-icon {
            font-size: 64px;
            margin-bottom: 24px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: iconFloat 3s ease-in-out infinite;
        }

        @keyframes iconFloat {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .empty-title {
            font-size: 1.5rem;
            color: var(--secondary);
            margin-bottom: 8px;
            font-weight: 700;
        }

        .empty-subtitle {
            color: var(--text-secondary);
            font-size: 15px;
            font-weight: 500;
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

        @media (max-width: 1024px) {
            .orders-grid {
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
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
            .stats-bar {
                width: 100%;
            }
        }

        @media (max-width: 768px) {
            .orders-grid {
                grid-template-columns: 1fr;
            }
            .stats-bar {
                grid-template-columns: 1fr;
            }
            .welcome-message h1 {
                font-size: 1.5rem;
            }
        }

        /* Notification Toast */
        .notification {
            position: fixed;
            top: 24px;
            right: 24px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 16px 24px;
            border-radius: var(--radius);
            box-shadow: var(--shadow-xl), 0 0 0 1px rgba(255,255,255,0.5) inset;
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

        /* Loading Skeleton */
        .skeleton {
            background: linear-gradient(90deg, var(--background) 25%, #E2E8F0 50%, var(--background) 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
            border-radius: 8px;
        }

        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
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
                <a href="#" class="nav-item active">
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
                    <h1>Live Orders</h1>
                    <p>Manage your restaurant in real-time</p>
                </div>
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
                            <h3 class="counter" data-target="<?= $status_counts['pending'] ?>">0</h3>
                            <p>Pending</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon preparing">
                            <i class="fas fa-fire"></i>
                        </div>
                        <div class="stat-info">
                            <h3 class="counter" data-target="<?= $status_counts['preparing'] ?>">0</h3>
                            <p>Cooking</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon served">
                            <i class="fas fa-check"></i>
                        </div>
                        <div class="stat-info">
                            <h3 class="counter" data-target="<?= $status_counts['served'] ?>">0</h3>
                            <p>Ready</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="section-header">
                <h2 class="section-title">Active Orders</h2>
                <button class="refresh-btn" onclick="refreshOrders()">
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
                        <h3 class="empty-title">No active orders</h3>
                        <p class="empty-subtitle">New orders will appear here automatically</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($live_orders as $order): 
                        $current_status = $order['current_status'] ?? 'pending';
                    ?>
                        <div class="order-card" data-order-id="<?= $order['order_id'] ?>">
                            <div class="order-header">
                                <div class="order-id">#<?= $order['order_id'] ?></div>
                                <div class="order-time">
                                    <?= date('h:i A', strtotime($order['created_at'])) ?>
                                </div>
                            </div>

                            <div class="status-badge status-<?= $current_status ?>">
                                <i class="fas fa-circle" style="font-size: 6px;"></i>
                                <?= ucfirst($current_status) ?>
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
                                        <?= htmlspecialchars($order['token']) ?>
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
                                            <div class="item-qty"><?= $item['quantity'] ?></div>
                                            <div class="item-price">₹<?= number_format($item['total_price'], 0) ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="item-row">
                                        <div class="item-name" style="color:var(--text-secondary);">No items</div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="order-footer">
                                <div class="order-total">
                                    <span>Total</span>
                                    <span>₹<?= number_format($order['total'], 0) ?></span>
                                </div>
                                
                                <select class="status-selector" onchange="changeStatus(<?= $order['order_id'] ?>, this.value)">
                                    <option value="" disabled selected>Update Status</option>
                                    <option value="preparing" <?= $current_status === 'preparing' ? 'selected' : '' ?>>
                                        🍳 Preparing
                                    </option>
                                    <option value="served" <?= $current_status === 'served' ? 'selected' : '' ?>>
                                        ✅ Ready
                                    </option>
                                    <option value="cancelled" <?= $current_status === 'cancelled' ? 'selected' : '' ?>>
                                        ❌ Cancel
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
            if (sidebar.classList.contains('active')) {
                mobileToggle.style.transform = 'rotate(90deg)';
            } else {
                mobileToggle.style.transform = 'rotate(0deg)';
            }
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

        // Animated Counter for Stats
        function animateCounters() {
            const counters = document.querySelectorAll('.counter');
            counters.forEach(counter => {
                const target = parseInt(counter.getAttribute('data-target'));
                const duration = 1000;
                const step = target / (duration / 16);
                let current = 0;
                
                const updateCounter = () => {
                    current += step;
                    if (current < target) {
                        counter.textContent = Math.floor(current);
                        requestAnimationFrame(updateCounter);
                    } else {
                        counter.textContent = target;
                    }
                };
                
                updateCounter();
            });
        }
        
        animateCounters();

        // Status Update with visual feedback
        function changeStatus(orderId, newStatus) {
            if (!newStatus) return;

            const card = document.querySelector(`[data-order-id="${orderId}"]`);
            card.style.transform = 'scale(0.95)';
            card.style.opacity = '0.7';

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
                    setTimeout(() => location.reload(), 600);
                } else {
                    showNotification('Error: ' + (data.error || 'Failed'), 'error');
                    card.style.transform = '';
                    card.style.opacity = '';
                }
            })
            .catch(err => {
                showNotification('Network error', 'error');
                card.style.transform = '';
                card.style.opacity = '';
            });
        }

        // Refresh with rotation animation
        function refreshOrders() {
            const btn = document.querySelector('.refresh-btn i');
            btn.style.animation = 'spin 1s linear infinite';
            location.reload();
        }

        // Notification System
        function showNotification(message, type = 'info') {
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
                if (notification.parentElement) {
                    notification.style.animation = 'slideInRight 0.4s ease reverse';
                    setTimeout(() => notification.remove(), 400);
                }
            }, 3000);
        }

        // Auto-refresh every 30 seconds with smooth transition
        setInterval(() => {
            fetch('?refresh=true')
                .then(response => response.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newContainer = doc.querySelector('#live-orders-container');
                    const currentContainer = document.getElementById('live-orders-container');
                    
                    if (newContainer && currentContainer) {
                        const oldCount = currentContainer.querySelectorAll('.order-card').length;
                        const newCount = newContainer.querySelectorAll('.order-card').length;
                        
                        if (newCount !== oldCount) {
                            currentContainer.style.opacity = '0';
                            currentContainer.style.transform = 'translateY(20px)';
                            setTimeout(() => {
                                currentContainer.innerHTML = newContainer.innerHTML;
                                currentContainer.style.transition = 'all 0.4s ease';
                                currentContainer.style.opacity = '1';
                                currentContainer.style.transform = 'translateY(0)';
                                showNotification('New order received!', 'success');
                                animateCounters();
                            }, 300);
                        }
                    }
                });
        }, 30000);

        // Add hover tilt effect to cards
        document.querySelectorAll('.order-card').forEach(card => {
            card.addEventListener('mousemove', (e) => {
                const rect = card.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                
                const centerX = rect.width / 2;
                const centerY = rect.height / 2;
                
                const rotateX = (y - centerY) / 20;
                const rotateY = (centerX - x) / 20;
                
                card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateY(-8px) scale(1.02)`;
            });
            
            card.addEventListener('mouseleave', () => {
                card.style.transform = '';
            });
        });
    </script>
</body>
</html>