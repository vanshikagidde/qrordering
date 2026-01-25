<?php
// orders.php - Shop Owner All Orders Page
session_start();
include "../config/db.php";

if (!isset($_SESSION['shop_id']) || !isset($_SESSION['logged_in'])) {
    header("Location: login.php");
    exit;
}

$shop_id = (int)$_SESSION['shop_id'];
$message = '';
$message_type = '';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id   = (int)($_POST['order_id'] ?? 0);
    $new_status = trim($_POST['new_status'] ?? '');

    $allowed_statuses = ['pending', 'paid', 'completed', 'cancelled'];

    if ($order_id > 0 && in_array($new_status, $allowed_statuses)) {
        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE order_id = ? AND shop_id = ?");
        $stmt->bind_param("sii", $new_status, $order_id, $shop_id);
        if ($stmt->execute()) {
            $message = "Order status updated successfully!";
            $message_type = 'success';
        } else {
            $message = "Error updating status: " . $conn->error;
            $message_type = 'error';
        }
        $stmt->close();
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

$query .= " GROUP BY o.order_id
            ORDER BY o.created_at DESC
            LIMIT 200";

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
    <title>All Orders - <?= htmlspecialchars($_SESSION['shop_name'] ?? 'Dashboard') ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet"/>
    <style>
        :root {
            --primary: #7c3aed;
            --primary-light: #a78bfa;
            --primary-dark: #6d28d9;
            --secondary: #10b981;
            --secondary-light: #34d399;
            --dark: #1f2937;
            --darker: #111827;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --light: #ffffff;
            --success: #10b981;
            --success-light: #d1fae5;
            --warning: #f59e0b;
            --warning-light: #fef3c7;
            --info: #3b82f6;
            --info-light: #dbeafe;
            --danger: #ef4444;
            --danger-light: #fee2e2;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --radius: 12px;
            --radius-lg: 16px;
            --radius-xl: 20px;
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

        /* Top Bar */
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
            background: var(--light);
            padding: 24px 32px;
            border-radius: var(--radius-xl);
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

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }

        .stat-card {
            background: var(--light);
            padding: 24px;
            border-radius: var(--radius-xl);
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
            border-radius: 16px;
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

        /* Cards */
        .card {
            background: var(--light);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow);
            padding: 32px;
            margin-bottom: 32px;
            border: 1px solid var(--gray-100);
            transition: var(--transition);
        }

        .card:hover {
            box-shadow: var(--shadow-lg);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--gray-100);
        }

        .card-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--darker);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .card-title i {
            color: var(--primary);
        }

        /* Filter Form */
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 0;
        }

        .form-group {
            margin-bottom: 0;
        }

        .form-label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: var(--gray-700);
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-control {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid var(--gray-200);
            border-radius: 12px;
            font-size: 15px;
            font-family: 'Inter', sans-serif;
            transition: var(--transition);
            background: var(--light);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(124, 58, 237, 0.1);
        }

        .form-select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%234b5563' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 16px center;
            background-size: 16px;
            padding-right: 48px;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 14px 28px;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            transition: var(--transition);
            font-family: 'Inter', sans-serif;
        }

        .btn i { font-size: 16px; }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            box-shadow: 0 4px 14px rgba(124, 58, 237, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(124, 58, 237, 0.4);
        }

        .btn-secondary {
            background: var(--gray-100);
            color: var(--gray-700);
        }

        .btn-secondary:hover {
            background: var(--gray-200);
        }

        .btn-sm {
            padding: 10px 18px;
            font-size: 14px;
        }

        .btn-xs {
            padding: 8px 16px;
            font-size: 13px;
            border-radius: 10px;
        }

        /* Table */
        .table-container {
            overflow-x: auto;
            border-radius: var(--radius-lg);
            border: 1px solid var(--gray-100);
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
            background: var(--light);
        }

        .orders-table tbody tr:hover {
            background: var(--gray-50);
            transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
        }

        .orders-table td {
            padding: 16px;
            border-bottom: 1px solid var(--gray-100);
            vertical-align: middle;
        }

        .order-id {
            font-weight: 700;
            color: var(--primary);
            font-size: 15px;
        }

        .order-details {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .order-meta {
            display: flex;
            gap: 12px;
            font-size: 13px;
            color: var(--gray-600);
        }

        .order-meta i {
            color: var(--primary);
            width: 16px;
        }

        .order-items {
            font-size: 13px;
            color: var(--gray-600);
            margin-top: 4px;
            line-height: 1.5;
            max-width: 300px;
        }

        .order-total {
            font-weight: 700;
            font-size: 16px;
            color: var(--darker);
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border-radius: 18px;
            font-size: 13px;
            font-weight: 600;
        }

        .status-pending {
            background: var(--warning-light);
            color: var(--warning);
        }

        .status-paid {
            background: var(--info-light);
            color: var(--info);
        }

        .status-completed {
            background: var(--success-light);
            color: var(--success);
        }

        .status-cancelled {
            background: var(--danger-light);
            color: var(--danger);
        }

        .order-date {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .order-time {
            font-size: 13px;
            color: var(--gray-600);
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .view-btn {
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 8px 16px;
            font-size: 13px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: var(--transition);
        }

        .view-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        /* Status Select */
        .status-select {
            padding: 6px 10px;
            border: 2px solid var(--gray-200);
            border-radius: 6px;
            background: white;
            font-size: 13px;
            cursor: pointer;
            transition: var(--transition);
            width: 100%;
            margin-top: 8px;
        }

        .status-select:focus {
            outline: none;
            border-color: var(--primary);
        }

        /* Token/Table Display - NORMAL SIZE */
        .token-display {
            font-family: 'Courier New', monospace;
            font-weight: 600;
            color: var(--primary);
            background: var(--gray-50);
            padding: 6px 10px;
            border-radius: 6px;
            border: 1px solid var(--gray-200);
            font-size: 14px;
            display: inline-block;
        }

        .table-display {
            font-family: 'Courier New', monospace;
            font-weight: 600;
            color: var(--info);
            background: var(--gray-50);
            padding: 6px 10px;
            border-radius: 6px;
            border: 1px solid var(--gray-200);
            font-size: 14px;
            display: inline-block;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            color: var(--gray-600);
        }

        .empty-icon {
            font-size: 4rem;
            color: var(--gray-300);
            margin-bottom: 24px;
            opacity: 0.5;
        }

        .empty-title {
            font-size: 1.5rem;
            color: var(--gray-700);
            margin-bottom: 12px;
            font-weight: 600;
        }

        .empty-subtitle {
            color: var(--gray-600);
            max-width: 400px;
            margin: 0 auto;
            line-height: 1.6;
        }

        /* Notification */
        .notification {
            position: fixed;
            top: 30px;
            right: 30px;
            background: var(--light);
            padding: 20px 24px;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            display: flex;
            align-items: center;
            gap: 16px;
            z-index: 1000;
            min-width: 350px;
            max-width: 400px;
            transform: translateX(120%);
            transition: transform 0.4s cubic-bezier(0.68, -0.55, 0.27, 1.55);
            border-left: 5px solid var(--primary);
        }

        .notification.show {
            transform: translateX(0);
        }

        .notification.success {
            border-left-color: var(--success);
        }

        .notification.error {
            border-left-color: var(--danger);
        }

        .notification-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }

        .notification.success .notification-icon {
            background: var(--success-light);
            color: var(--success);
        }

        .notification.error .notification-icon {
            background: var(--danger-light);
            color: var(--danger);
        }

        .notification-content {
            flex: 1;
        }

        .notification-title {
            font-weight: 600;
            margin-bottom: 4px;
            color: var(--darker);
        }

        .notification-message {
            color: var(--gray-600);
            font-size: 14px;
            line-height: 1.5;
        }

        .notification-close {
            background: none;
            border: none;
            color: var(--gray-400);
            cursor: pointer;
            font-size: 18px;
            transition: var(--transition);
        }

        .notification-close:hover {
            color: var(--gray-700);
        }

        /* Export Buttons */
        .export-buttons {
            display: flex;
            gap: 12px;
        }

        .export-btn {
            padding: 10px 20px;
            border: 2px solid var(--primary);
            background: white;
            color: var(--primary);
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: var(--transition);
        }

        .export-btn:hover {
            background: var(--primary);
            color: white;
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .filter-grid {
                grid-template-columns: repeat(2, 1fr);
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
            .filter-grid {
                grid-template-columns: 1fr;
            }
            .notification {
                left: 20px;
                right: 20px;
                min-width: auto;
                max-width: none;
            }
            .card-header {
                flex-direction: column;
                gap: 20px;
                align-items: flex-start;
            }
            .export-buttons {
                width: 100%;
                justify-content: flex-start;
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
            .card {
                padding: 24px;
            }
            .orders-table {
                display: block;
            }
            .orders-table thead {
                display: none;
            }
            .orders-table tbody tr {
                display: block;
                margin-bottom: 20px;
                border: 1px solid var(--gray-200);
                border-radius: var(--radius);
                padding: 20px;
            }
            .orders-table td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 12px 0;
                border-bottom: 1px solid var(--gray-100);
            }
            .orders-table td:last-child {
                border-bottom: none;
            }
            .orders-table td::before {
                content: attr(data-label);
                font-weight: 600;
                color: var(--gray-700);
                font-size: 14px;
                text-transform: uppercase;
                margin-right: 10px;
            }
        }

        .mobile-toggle {
            display: none;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 99;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border: none;
            width: 50px;
            height: 50px;
            border-radius: 12px;
            font-size: 20px;
            cursor: pointer;
            box-shadow: var(--shadow-lg);
            transition: var(--transition);
        }

        .mobile-toggle:hover {
            transform: scale(1.05);
        }

        /* Animations */
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .stat-card, .card {
            animation: slideIn 0.5s ease-out;
        }

        /* Loading State */
        .loading {
            position: relative;
            pointer-events: none;
            opacity: 0.7;
        }

        .loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 20px;
            height: 20px;
            margin: -10px 0 0 -10px;
            border: 2px solid var(--gray-300);
            border-top-color: var(--primary);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            margin-top: 32px;
            padding-top: 24px;
            border-top: 1px solid var(--gray-200);
        }

        .page-btn {
            padding: 10px 16px;
            border: 1px solid var(--gray-300);
            background: white;
            color: var(--gray-700);
            border-radius: 8px;
            cursor: pointer;
            transition: var(--transition);
        }

        .page-btn:hover:not(.disabled) {
            background: var(--gray-100);
        }

        .page-btn.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .page-btn.disabled {
            opacity: 0.5;
            cursor: not-allowed;
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
            <span class="logo-text">RestoFlow</span>
        </div>

        <nav class="nav-menu">
            <a href="dashboard.php" class="nav-item">
                <i class="fas fa-tachometer-alt"></i>
                <span class="nav-text">Dashboard</span>
            </a>
            <a href="menu_management.php" class="nav-item">
                <i class="fas fa-utensils"></i>
                <span class="nav-text">Menu Management</span>
            </a>
            <a href="orders.php" class="nav-item active">
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
                <h1>All Orders</h1>
                <p>View and manage all customer orders</p>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, var(--primary), var(--primary-dark));">
                    <i class="fas fa-receipt"></i>
                </div>
                <div class="stat-info">
                    <h3><?= $total_orders ?></h3>
                    <p>Total Orders</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, var(--warning), #f97316);">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-info">
                    <h3><?= $status_counts['pending'] ?></h3>
                    <p>Pending Orders</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, var(--info), #0ea5e9);">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <h3><?= $status_counts['paid'] ?></h3>
                    <p>Paid Orders</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, var(--success), #059669);">
                    <i class="fas fa-check-double"></i>
                </div>
                <div class="stat-info">
                    <h3><?= $status_counts['completed'] ?></h3>
                    <p>Completed Orders</p>
                </div>
            </div>
        </div>

        <!-- Filters Card -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">
                    <i class="fas fa-filter"></i> Filter Orders
                </h2>
            </div>
            
            <form method="GET" id="filterForm" class="filter-grid">
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control form-select">
                        <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>All Status</option>
                        <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="paid" <?= $status_filter === 'paid' ? 'selected' : '' ?>>Paid</option>
                        <option value="completed" <?= $status_filter === 'completed' ? 'selected' : '' ?>>Completed</option>
                        <option value="cancelled" <?= $status_filter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">From Date</label>
                    <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($date_from) ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">To Date</label>
                    <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($date_to) ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" placeholder="Search by ID, Table, Token or Item..." value="<?= htmlspecialchars($search_query) ?>">
                </div>
                
                <div class="form-group" style="display: flex; gap: 12px; align-items: flex-end;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">
                        <i class="fas fa-search"></i> Apply Filters
                    </button>
                    <button type="button" onclick="resetFilters()" class="btn btn-secondary" style="flex: 1;">
                        <i class="fas fa-redo"></i> Reset
                    </button>
                </div>
            </form>
        </div>

        <!-- Orders Table -->
        <div class="card">
            <?php if (empty($orders)): ?>
                <div class="empty-state">
                    <div class="empty-icon"><i class="fas fa-receipt"></i></div>
                    <h3>No orders found</h3>
                    <p>Try adjusting filters or wait for new orders.</p>
                </div>
            <?php else: ?>
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-list-ul"></i> Recent Orders (<?= $total_orders ?>)
                    </h2>
                    <div class="export-buttons">
                        <button onclick="exportOrders('csv')" class="export-btn">
                            <i class="fas fa-file-csv"></i> Export CSV
                        </button>
                        <button onclick="exportOrders('pdf')" class="export-btn">
                            <i class="fas fa-file-pdf"></i> Export PDF
                        </button>
                    </div>
                </div>
                
                <div class="table-container">
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Token / Table</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Date & Time</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td data-label="Order ID">
                                        <strong class="order-id">#<?= $order['order_id'] ?></strong>
                                    </td>

                                    <!-- NORMAL SIZE Token/Table Display -->
                                    <td data-label="Token / Table">
                                        <?php if (!empty($order['token']) && $order['token'] !== '0'): ?>
                                            <div class="token-display">
                                                Token: <?= htmlspecialchars($order['token']) ?>
                                            </div>
                                        <?php elseif (!empty($order['table_no'])): ?>
                                            <div class="table-display">
                                                Table: <?= htmlspecialchars($order['table_no']) ?>
                                            </div>
                                        <?php else: ?>
                                            <span style="color: var(--gray-400);">—</span>
                                        <?php endif; ?>
                                    </td>

                                    <td data-label="Items">
                                        <div style="font-weight: 600; margin-bottom: 4px;">
                                            <?= $order['item_count'] ?> item(s)
                                        </div>
                                        <div class="order-items">
                                            <?= htmlspecialchars(substr($order['item_summary'] ?? '', 0, 50)) ?>
                                            <?= strlen($order['item_summary'] ?? '') > 50 ? '...' : '' ?>
                                        </div>
                                    </td>

                                    <td data-label="Total">
                                        <strong class="order-total">₹<?= number_format($order['total'], 2) ?></strong>
                                    </td>

                                    <td data-label="Status">
                                        <span class="status-badge status-<?= strtolower($order['status']) ?>">
                                            <i class="fas fa-<?= 
                                                $order['status'] === 'pending' ? 'clock' : 
                                                ($order['status'] === 'paid' ? 'check-circle' : 
                                                ($order['status'] === 'completed' ? 'check-double' : 'times-circle')) 
                                            ?>"></i>
                                            <?= ucfirst($order['status']) ?>
                                        </span>
                                        <form method="POST" onsubmit="updateStatus(event, this)" style="margin-top: 8px;">
                                            <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                                            <input type="hidden" name="update_status" value="1">
                                            <select name="new_status" class="status-select" onchange="this.form.submit()">
                                                <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                                <option value="paid" <?= $order['status'] === 'paid' ? 'selected' : '' ?>>Paid</option>
                                                <option value="completed" <?= $order['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                                                <option value="cancelled" <?= $order['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                            </select>
                                        </form>
                                    </td>

                                    <td data-label="Date & Time" class="order-date">
                                        <?= date('d M Y', strtotime($order['created_at'])) ?><br>
                                        <small class="order-time"><?= date('h:i A', strtotime($order['created_at'])) ?></small>
                                    </td>

                                    <td data-label="Actions">
                                        <button class="btn btn-primary btn-xs" onclick="viewOrderDetails(<?= $order['order_id'] ?>)">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Simple Pagination -->
                <div class="pagination">
                    <button class="page-btn" onclick="location.href='?page=1'">
                        <i class="fas fa-angle-double-left"></i>
                    </button>
                    <button class="page-btn active">1</button>
                    <button class="page-btn">2</button>
                    <button class="page-btn">3</button>
                    <button class="page-btn">
                        <i class="fas fa-angle-double-right"></i>
                    </button>
                </div>
            <?php endif; ?>
        </div>

    </main>
</div>

<!-- Notification Container -->
<div id="notificationContainer"></div>

<script>
// Sidebar Toggle
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

// Notification System
function showNotification(message, type = 'success') {
    const container = document.getElementById('notificationContainer');
    
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    
    const icon = type === 'success' ? 'check-circle' : 'exclamation-circle';
    
    notification.innerHTML = `
        <div class="notification-icon">
            <i class="fas fa-${icon}"></i>
        </div>
        <div class="notification-content">
            <div class="notification-title">${type === 'success' ? 'Success!' : 'Error!'}</div>
            <div class="notification-message">${message}</div>
        </div>
        <button class="notification-close" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    container.appendChild(notification);
    
    // Show notification with animation
    setTimeout(() => notification.classList.add('show'), 10);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentElement) {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 400);
        }
    }, 5000);
}

// Show notification if there's a message from PHP
<?php if (!empty($message)): ?>
    setTimeout(() => {
        showNotification('<?= addslashes($message) ?>', '<?= $message_type ?>');
    }, 500);
<?php endif; ?>

// Update status function
function updateStatus(event, form) {
    event.preventDefault();
    
    const formData = new FormData(form);
    const orderId = formData.get('order_id');
    const newStatus = formData.get('new_status');
    
    // Add loading state
    const select = form.querySelector('select');
    const originalValue = select.value;
    select.disabled = true;
    
    fetch('', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.text())
    .then(() => {
        showNotification(`Order status updated to ${newStatus}`, 'success');
        
        // Reload the page after 1 second to show updated status
        setTimeout(() => {
            location.reload();
        }, 1000);
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Failed to update status. Please try again.', 'error');
        select.value = originalValue;
    })
    .finally(() => {
        select.disabled = false;
    });
}

// View order details
function viewOrderDetails(orderId) {
    // In a real application, this would open a modal or navigate to order details page
    showNotification(`Opening order #${orderId} details...`, 'info');
    
    // Simulate API call
    setTimeout(() => {
        // For now, just show a message
        showNotification(`Viewing details for order #${orderId}`, 'info');
        // You can redirect to a detailed view page:
        // window.location.href = `order_details.php?id=${orderId}`;
    }, 500);
}

// Export orders
function exportOrders(format) {
    showNotification(`Exporting orders as ${format.toUpperCase()}...`, 'info');
    
    // Build export URL with current filters
    const form = document.getElementById('filterForm');
    const formData = new FormData(form);
    const params = new URLSearchParams(formData);
    
    // Simulate export
    setTimeout(() => {
        showNotification(`Orders exported successfully as ${format.toUpperCase()}`, 'success');
        
        // In a real application, this would trigger a download
        if (format === 'csv') {
            window.open(`export_csv.php?${params.toString()}`, '_blank');
        } else if (format === 'pdf') {
            window.open(`export_pdf.php?${params.toString()}`, '_blank');
        }
    }, 1500);
}

// Reset filters
function resetFilters() {
    const form = document.getElementById('filterForm');
    form.reset();
    // Set default dates
    const today = new Date().toISOString().split('T')[0];
    const thirtyDaysAgo = new Date();
    thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);
    const dateFrom = thirtyDaysAgo.toISOString().split('T')[0];
    
    form.querySelector('input[name="date_from"]').value = dateFrom;
    form.querySelector('input[name="date_to"]').value = today;
    form.submit();
}

// Auto-refresh orders every 30 seconds
let refreshInterval = setInterval(() => {
    const currentParams = new URLSearchParams(window.location.search);
    currentParams.set('refresh', 'true');
    
    fetch(`?${currentParams.toString()}`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.text())
    .then(html => {
        // Check if we have any pending orders
        if (html.includes('status-pending')) {
            const pendingCount = (html.match(/status-pending/g) || []).length;
            if (pendingCount > 0 && !document.hidden) {
                showNotification(`You have ${pendingCount} pending order(s)`, 'info');
            }
        }
    })
    .catch(() => {
        // Silent fail for auto-refresh
    });
}, 30000);

// Stop auto-refresh when tab is not active
document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
        clearInterval(refreshInterval);
    } else {
        refreshInterval = setInterval(() => {
            // Refresh logic here
        }, 30000);
    }
});

// Search functionality
const searchInput = document.querySelector('input[name="search"]');
let searchTimeout;
searchInput.addEventListener('input', function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        if (this.value.length >= 2 || this.value.length === 0) {
            document.getElementById('filterForm').submit();
        }
    }, 500);
});

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl/Cmd + F to focus search
    if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
        e.preventDefault();
        searchInput.focus();
    }
    
    // Escape to close sidebar on mobile
    if (e.key === 'Escape' && window.innerWidth <= 992) {
        sidebar.classList.remove('active');
    }
});

// Initialize date pickers with sensible defaults
document.addEventListener('DOMContentLoaded', function() {
    const dateInputs = document.querySelectorAll('input[type="date"]');
    dateInputs.forEach(input => {
        if (!input.value && input.name === 'date_from') {
            // Default to 30 days ago
            const date = new Date();
            date.setDate(date.getDate() - 30);
            input.value = date.toISOString().split('T')[0];
        }
        if (!input.value && input.name === 'date_to') {
            // Default to today
            input.value = new Date().toISOString().split('T')[0];
        }
    });
});
</script>
</body>
</html>