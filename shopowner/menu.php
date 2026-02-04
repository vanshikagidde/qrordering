<?php
// menu.php
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

/* ================= FORM HANDLING ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    /* -------- ADD ITEM -------- */
    if (isset($_POST['add_item'])) {
        $item_name = trim($_POST['item_name'] ?? '');
        $price = floatval($_POST['price'] ?? 0);

        if ($item_name === '' || $price <= 0) {
            $message = "Item name and valid price are required";
            $message_type = "error";
        } else {
            $stmt = $conn->prepare("INSERT INTO menu (shop_id, item_name, price) VALUES (?, ?, ?)");
            $stmt->bind_param("isd", $shop_id, $item_name, $price);

            if ($stmt->execute()) {
                $message = "Item added successfully!";
                $message_type = "success";
            } else {
                $message = "Error: " . $stmt->error;
                $message_type = "error";
            }
            $stmt->close();
        }
    }

    /* -------- EDIT ITEM -------- */
    elseif (isset($_POST['edit_item'])) {
        $item_id = (int)$_POST['item_id'];
        $item_name = trim($_POST['item_name'] ?? '');
        $price = floatval($_POST['price'] ?? 0);

        if ($item_id <= 0 || $item_name === '' || $price <= 0) {
            $message = "Invalid update data";
            $message_type = "error";
        } else {
            $stmt = $conn->prepare("UPDATE menu SET item_name=?, price=? WHERE id=? AND shop_id=?");
            $stmt->bind_param("sdii", $item_name, $price, $item_id, $shop_id);

            if ($stmt->execute()) {
                $message = "Item updated successfully!";
                $message_type = "success";
            } else {
                $message = "Update error: " . $stmt->error;
                $message_type = "error";
            }
            $stmt->close();
        }
    }

    /* -------- DELETE ITEM -------- */
    elseif (isset($_POST['delete_item'])) {
        $item_id = (int)$_POST['item_id'];
        $stmt = $conn->prepare("DELETE FROM menu WHERE id=? AND shop_id=?");
        $stmt->bind_param("ii", $item_id, $shop_id);
        
        if ($stmt->execute()) {
            $message = "Item deleted successfully!";
            $message_type = "success";
        } else {
            $message = "Delete error: " . $stmt->error;
            $message_type = "error";
        }
        $stmt->close();
    }
}

/* ================= LOAD MENU ITEMS ================= */
$stmt = $conn->prepare("SELECT id, item_name, price FROM menu WHERE shop_id=? ORDER BY item_name ASC");
$stmt->bind_param("i", $shop_id);
$stmt->execute();
$result = $stmt->get_result();
$menu_items = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$total_items = count($menu_items);
$avg_price = $total_items > 0 ? array_sum(array_column($menu_items, 'price')) / $total_items : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Menu Management - <?= htmlspecialchars($shop_name) ?></title>
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

        /* Animated Background */
        .bg-shapes {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            pointer-events: none; z-index: 0; overflow: hidden;
        }
        .shape {
            position: absolute; border-radius: 50%; filter: blur(80px);
            opacity: 0.4; animation: float 20s infinite ease-in-out;
        }
        .shape-1 {
            width: 400px; height: 400px;
            background: linear-gradient(135deg, rgba(249, 115, 22, 0.3), rgba(251, 146, 60, 0.1));
            top: -100px; right: -100px;
        }
        .shape-2 {
            width: 300px; height: 300px;
            background: linear-gradient(135deg, rgba(255, 237, 213, 0.6), rgba(254, 215, 170, 0.2));
            bottom: 10%; left: -50px; animation-delay: -5s;
        }
        @keyframes float {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(30px, -30px) scale(1.1); }
            66% { transform: translate(-20px, 20px) scale(0.9); }
        }

        .dashboard-container {
            display: flex; min-height: 100vh;
            position: relative; z-index: 1;
        }

        /* Glassmorphism Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(20px);
            color: white; position: fixed; height: 100vh;
            padding: 24px 20px; display: flex; flex-direction: column;
            z-index: 100; transition: all 0.4s var(--ease-expo);
            border-right: 1px solid rgba(255,255,255,0.05);
            box-shadow: 4px 0 24px rgba(0,0,0,0.1);
        }
        .sidebar.collapsed { width: var(--sidebar-collapsed); padding: 24px 16px; }
        .sidebar.collapsed .logo-text,
        .sidebar.collapsed .nav-text,
        .sidebar.collapsed .user-info { opacity: 0; transform: translateX(-10px); pointer-events: none; }

        .sidebar-toggle {
            position: absolute; right: -12px; top: 32px;
            width: 28px; height: 28px; background: var(--primary);
            border: 3px solid var(--surface); border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; color: white; font-size: 11px;
            transition: all 0.3s var(--ease-spring); z-index: 101;
            box-shadow: 0 4px 12px rgba(249, 115, 22, 0.4);
        }
        .sidebar-toggle:hover { transform: scale(1.15) rotate(180deg); background: var(--primary-light); }

        .logo {
            display: flex; align-items: center; gap: 14px;
            padding: 8px 4px 24px; margin-bottom: 24px; position: relative;
        }
        .logo::after {
            content: ''; position: absolute; bottom: 0; left: 0; right: 0;
            height: 1px; background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
        }
        .logo-icon {
            width: 44px; height: 44px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            border-radius: 12px; display: flex; align-items: center; justify-content: center;
            font-size: 20px; flex-shrink: 0;
            box-shadow: 0 8px 20px rgba(249, 115, 22, 0.3);
            animation: iconPulse 3s infinite; position: relative; overflow: hidden;
        }
        .logo-icon::before {
            content: ''; position: absolute; inset: 0;
            background: linear-gradient(135deg, transparent, rgba(255,255,255,0.3));
        }
        @keyframes iconPulse {
            0%, 100% { box-shadow: 0 8px 20px rgba(249, 115, 22, 0.3); transform: scale(1); }
            50% { box-shadow: 0 12px 30px rgba(249, 115, 22, 0.5); transform: scale(1.02); }
        }
        .logo-text {
            font-size: 1.5rem; font-weight: 800;
            background: linear-gradient(135deg, #ffffff, #cbd5e1);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            transition: all 0.3s var(--ease-smooth); white-space: nowrap;
        }

        .nav-menu { flex: 1; display: flex; flex-direction: column; gap: 6px; }
        .nav-item {
            display: flex; align-items: center; gap: 14px;
            padding: 14px 16px; border-radius: 12px;
            color: rgba(255,255,255,0.6); text-decoration: none;
            transition: all 0.3s var(--ease-spring); position: relative;
            font-weight: 500; font-size: 15px; overflow: hidden;
        }
        .nav-item::before {
            content: ''; position: absolute; left: 0; top: 50%;
            transform: translateY(-50%); width: 3px; height: 0;
            background: var(--primary); border-radius: 0 4px 4px 0;
            transition: height 0.3s var(--ease-spring);
        }
        .nav-item:hover { color: white; background: rgba(255,255,255,0.05); transform: translateX(6px); }
        .nav-item:hover::before { height: 60%; }
        .nav-item.active {
            background: linear-gradient(135deg, rgba(249, 115, 22, 0.2), rgba(249, 115, 22, 0.05));
            color: var(--primary-light); font-weight: 600;
        }
        .nav-item.active::before { height: 80%; }
        .nav-item i { font-size: 18px; width: 24px; text-align: center; transition: transform 0.3s var(--ease-spring); }
        .nav-item:hover i { transform: scale(1.1) rotate(-5deg); }
        .nav-text { transition: all 0.3s var(--ease-smooth); white-space: nowrap; }

        .user-actions { margin-top: auto; padding-top: 20px; position: relative; }
        .user-actions::before {
            content: ''; position: absolute; top: 0; left: 0; right: 0;
            height: 1px; background: linear-gradient(90deg, transparent, rgba(255,255,255,0.15), transparent);
        }
        .user-info { padding: 0 8px 16px; text-align: center; transition: all 0.3s var(--ease-smooth); }
        .user-avatar {
            width: 48px; height: 48px;
            background: linear-gradient(135deg, var(--primary), #fbbf24);
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            font-weight: 800; font-size: 18px; margin: 0 auto 10px;
            box-shadow: 0 4px 15px rgba(249, 115, 22, 0.3);
            border: 3px solid rgba(255,255,255,0.1);
            transition: all 0.3s var(--ease-spring); position: relative; overflow: hidden;
        }
        .user-avatar::after {
            content: ''; position: absolute; inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.2), transparent);
        }
        .user-avatar:hover { transform: scale(1.1) rotate(10deg); }
        .user-name { font-weight: 700; font-size: 15px; margin-bottom: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .user-role { font-size: 12px; color: rgba(255,255,255,0.4); font-weight: 500; }

        .action-btn {
            display: flex; align-items: center; gap: 12px;
            padding: 12px 16px; border-radius: 10px; background: transparent;
            border: none; color: rgba(255,255,255,0.6); font-family: inherit;
            font-size: 14px; cursor: pointer; transition: all 0.3s var(--ease-spring);
            width: 100%; text-align: left; font-weight: 500; position: relative; overflow: hidden;
        }
        .action-btn::before {
            content: ''; position: absolute; inset: 0;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            transform: translateX(-100%); transition: transform 0.5s;
        }
        .action-btn:hover::before { transform: translateX(100%); }
        .action-btn:hover { color: white; background: rgba(255,255,255,0.08); transform: translateX(4px); }
        .action-btn.logout { color: #fca5a5; margin-top: 4px; }
        .action-btn.logout:hover { background: rgba(239, 68, 68, 0.15); color: #fecaca; }

        /* Main Content */
        .main-content {
            flex: 1; margin-left: var(--sidebar-width);
            padding: 32px; transition: all 0.4s var(--ease-expo);
        }
        .main-content.expanded { margin-left: var(--sidebar-collapsed); }

        /* Top Bar */
        .top-bar {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 32px; background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(20px); padding: 24px 32px;
            border-radius: var(--radius);
            box-shadow: var(--shadow), 0 0 0 1px rgba(255,255,255,0.5) inset;
            border: 1px solid rgba(255,255,255,0.6);
            animation: slideDown 0.6s var(--ease-expo) backwards;
        }
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .welcome-message h1 { font-size: 1.75rem; font-weight: 800; color: var(--secondary); margin-bottom: 6px; letter-spacing: -0.02em; }
        .welcome-message p { color: var(--text-secondary); font-size: 15px; font-weight: 500; }

        /* Stats Cards */
        .stats-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px; margin-bottom: 32px;
        }
        .stat-card {
            background: var(--surface); padding: 24px; border-radius: var(--radius);
            box-shadow: var(--shadow); display: flex; align-items: center; gap: 16px;
            border: 1px solid var(--border); transition: all 0.4s var(--ease-spring);
            position: relative; overflow: hidden; animation: slideUp 0.6s var(--ease-expo) backwards;
        }
        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .stat-card:nth-child(3) { animation-delay: 0.3s; }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        .stat-card::before {
            content: ''; position: absolute; top: 0; left: 0; right: 0;
            height: 4px; background: linear-gradient(90deg, var(--primary), var(--primary-light));
            transform: scaleX(0); transition: transform 0.4s var(--ease-expo);
        }
        .stat-card:hover { transform: translateY(-4px) scale(1.02); box-shadow: var(--shadow-xl), var(--shadow-orange); border-color: rgba(249, 115, 22, 0.3); }
        .stat-card:hover::before { transform: scaleX(1); }
        .stat-icon {
            width: 56px; height: 56px; border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 24px; transition: all 0.4s var(--ease-spring);
            position: relative; overflow: hidden;
        }
        .stat-card:hover .stat-icon { transform: scale(1.1) rotate(-5deg); }
        .stat-icon::after {
            content: ''; position: absolute; inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.4), transparent);
        }
        .stat-icon.items { background: linear-gradient(135deg, #FEF3C7, #FDE68A); color: #D97706; box-shadow: 0 8px 20px rgba(217, 119, 6, 0.2); }
        .stat-icon.price { background: linear-gradient(135deg, #FCE7F3, #FBCFE8); color: #DB2777; box-shadow: 0 8px 20px rgba(219, 39, 119, 0.2); }
        
        .stat-info h3 {
            font-size: 32px; font-weight: 800; margin-bottom: 4px;
            background: linear-gradient(135deg, var(--secondary), #475569);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent; line-height: 1;
        }
        .stat-info p { color: var(--text-secondary); font-size: 14px; font-weight: 600; letter-spacing: 0.05em; text-transform: uppercase; }

        /* Cards */
        .card {
            background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(20px);
            border-radius: var(--radius);
            box-shadow: var(--shadow), 0 0 0 1px rgba(255,255,255,0.6) inset;
            padding: 32px; margin-bottom: 32px; border: 1px solid rgba(255,255,255,0.6);
            transition: all 0.4s var(--ease-spring); animation: cardEnter 0.6s var(--ease-expo) backwards;
            position: relative; overflow: hidden;
        }
        .card:nth-of-type(1) { animation-delay: 0.4s; }
        .card:nth-of-type(2) { animation-delay: 0.5s; }
        @keyframes cardEnter {
            from { opacity: 0; transform: translateY(40px) rotateX(10deg) scale(0.95); }
            to { opacity: 1; transform: translateY(0) rotateX(0) scale(1); }
        }
        .card::before {
            content: ''; position: absolute; top: 0; left: 0; right: 0;
            height: 4px; background: linear-gradient(90deg, var(--primary), var(--primary-light), var(--primary));
            background-size: 200% 100%; animation: shimmer 3s infinite linear;
        }
        @keyframes shimmer { 0% { background-position: 100% 0; } 100% { background-position: -100% 0; } }
        .card:hover { transform: translateY(-4px); box-shadow: var(--shadow-xl), 0 20px 40px -10px rgba(249, 115, 22, 0.2); }
        
        .card-header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 32px; padding-bottom: 20px; border-bottom: 2px solid var(--border);
        }
        .card-title { font-size: 1.5rem; font-weight: 800; color: var(--secondary); display: flex; align-items: center; gap: 12px; }
        .card-title i { color: var(--primary); animation: iconBounce 2s infinite; }
        @keyframes iconBounce { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-5px); } }

        /* Form Styles */
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 24px; }
        .form-group { margin-bottom: 0; position: relative; }
        .form-label {
            display: block; margin-bottom: 10px; font-weight: 700;
            color: var(--text); font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;
        }
        .form-control {
            width: 100%; padding: 14px 18px; border: 2px solid var(--border);
            border-radius: 12px; font-size: 15px; font-family: 'Plus Jakarta Sans', sans-serif;
            transition: all 0.3s var(--ease-spring); background: rgba(255,255,255,0.8);
        }
        .form-control:hover { border-color: var(--primary-light); transform: translateY(-2px); box-shadow: 0 4px 12px rgba(249, 115, 22, 0.1); }
        .form-control:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 4px rgba(249, 115, 22, 0.1), 0 4px 12px rgba(249, 115, 22, 0.15); transform: translateY(-2px); }

        /* Buttons */
        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 10px;
            padding: 14px 28px; border: none; border-radius: 12px; font-weight: 700;
            font-size: 15px; cursor: pointer; transition: all 0.3s var(--ease-spring);
            font-family: 'Plus Jakarta Sans', sans-serif; position: relative; overflow: hidden;
        }
        .btn::before {
            content: ''; position: absolute; inset: 0;
            background: linear-gradient(135deg, transparent, rgba(255,255,255,0.3), transparent);
            transform: translateX(-100%); transition: transform 0.6s;
        }
        .btn:hover::before { transform: translateX(100%); }
        .btn i { font-size: 16px; transition: transform 0.3s var(--ease-spring); }
        .btn:hover i { transform: scale(1.1) rotate(-5deg); }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white; box-shadow: 0 4px 15px rgba(249, 115, 22, 0.3);
        }
        .btn-primary:hover { transform: translateY(-2px) scale(1.05); box-shadow: 0 8px 25px rgba(249, 115, 22, 0.4); }
        .btn-secondary {
            background: linear-gradient(135deg, var(--background), var(--border));
            color: var(--text-secondary); border: 2px solid var(--border);
        }
        .btn-secondary:hover { transform: translateY(-2px); border-color: var(--primary-light); color: var(--primary); }
        .btn-success {
            background: linear-gradient(135deg, var(--success), #059669);
            color: white; box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }
        .btn-success:hover { transform: translateY(-2px) scale(1.05); box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4); }
        .btn-danger {
            background: linear-gradient(135deg, var(--danger), #dc2626);
            color: white; box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
        }
        .btn-danger:hover { transform: translateY(-2px) scale(1.05); box-shadow: 0 8px 25px rgba(239, 68, 68, 0.4); }
        .btn-sm { padding: 10px 18px; font-size: 14px; }
        .btn-xs { padding: 8px 16px; font-size: 13px; border-radius: 10px; }

        /* Table */
        .table-container {
            overflow-x: auto; border-radius: var(--radius);
            border: 1px solid var(--border); background: rgba(255,255,255,0.5);
            backdrop-filter: blur(10px);
        }
        .menu-table { width: 100%; border-collapse: separate; border-spacing: 0; }
        .menu-table thead {
            background: linear-gradient(135deg, rgba(249, 115, 22, 0.1), rgba(251, 146, 60, 0.05));
        }
        .menu-table th {
            padding: 20px; text-align: left; font-weight: 700; color: var(--secondary);
            font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;
            border-bottom: 2px solid var(--primary-light);
        }
        .menu-table tbody tr {
            transition: all 0.3s var(--ease-spring); background: transparent;
            border-bottom: 1px solid var(--border);
        }
        .menu-table tbody tr:hover {
            background: rgba(249, 115, 22, 0.05); transform: translateX(8px);
            box-shadow: -4px 0 12px rgba(249, 115, 22, 0.1);
        }
        .menu-table td { padding: 20px; vertical-align: middle; }
        
        .item-name { font-weight: 700; color: var(--secondary); font-size: 16px; }
        .item-price {
            font-weight: 800; font-size: 18px; color: var(--primary-dark);
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }
        .actions-group { display: flex; gap: 10px; }

        /* Edit Form */
        .edit-form-row { background: rgba(249, 115, 22, 0.05) !important; }
        .edit-form-row td { padding: 30px !important; }
        .edit-form-row .form-grid { margin-bottom: 20px; }
        
        /* Empty State */
        .empty-state { text-align: center; padding: 80px 40px; color: var(--text-secondary); animation: float 6s ease-in-out infinite; }
        .empty-icon {
            font-size: 64px; margin-bottom: 24px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            animation: iconFloat 3s ease-in-out infinite;
        }
        @keyframes iconFloat { 0%, 100% { transform: translateY(0) rotate(0deg); } 50% { transform: translateY(-10px) rotate(5deg); } }
        .empty-title { font-size: 1.5rem; color: var(--secondary); margin-bottom: 8px; font-weight: 800; }
        .empty-subtitle { color: var(--text-secondary); font-size: 15px; font-weight: 500; }

        /* Notification */
        .notification {
            position: fixed; top: 24px; right: 24px;
            background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(20px);
            padding: 20px 24px; border-radius: var(--radius);
            box-shadow: var(--shadow-xl), 0 0 0 1px rgba(255,255,255,0.5) inset;
            display: flex; align-items: center; gap: 16px; z-index: 1000;
            min-width: 350px; max-width: 400px;
            transform: translateX(120%) scale(0.9); transition: all 0.4s var(--ease-spring);
            border-left: 4px solid var(--primary);
        }
        .notification.show { transform: translateX(0) scale(1); }
        .notification.success { border-left-color: var(--success); }
        .notification.error { border-left-color: var(--danger); }
        .notification-icon {
            width: 44px; height: 44px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px; flex-shrink: 0; animation: iconPop 0.4s var(--ease-spring);
        }
        @keyframes iconPop { 0% { transform: scale(0) rotate(-45deg); } 100% { transform: scale(1) rotate(0deg); } }
        .notification.success .notification-icon { background: linear-gradient(135deg, #D1FAE5, #A7F3D0); color: #059669; }
        .notification.error .notification-icon { background: linear-gradient(135deg, #FEE2E2, #FECACA); color: #DC2626; }
        .notification-content { flex: 1; }
        .notification-title { font-weight: 800; margin-bottom: 4px; color: var(--secondary); }
        .notification-message { color: var(--text-secondary); font-size: 14px; font-weight: 500; }
        .notification-close {
            background: none; border: none; color: var(--text-secondary);
            cursor: pointer; font-size: 20px; width: 36px; height: 36px;
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            transition: all 0.2s var(--ease-spring);
        }
        .notification-close:hover { background: var(--background); color: var(--text); transform: rotate(90deg); }

        /* Mobile */
        .mobile-toggle {
            display: none; position: fixed; top: 20px; left: 20px; z-index: 99;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white; border: none; width: 48px; height: 48px;
            border-radius: 14px; font-size: 20px; cursor: pointer;
            box-shadow: var(--shadow-lg), 0 0 0 1px rgba(255,255,255,0.3) inset;
            transition: all 0.3s var(--ease-spring);
        }
        .mobile-toggle:hover { transform: scale(1.1) rotate(5deg); }

        @media (max-width: 992px) {
            .sidebar { transform: translateX(-100%); box-shadow: 10px 0 40px rgba(0,0,0,0.2); }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 20px; }
            .mobile-toggle { display: flex; align-items: center; justify-content: center; }
            .top-bar { margin-top: 60px; flex-direction: column; gap: 20px; text-align: center; }
            .notification { left: 20px; right: 20px; min-width: auto; max-width: none; }
        }
        @media (max-width: 768px) {
            .stats-grid { grid-template-columns: 1fr; }
            .welcome-message h1 { font-size: 1.5rem; }
            .card { padding: 24px; }
            .form-grid { grid-template-columns: 1fr; }
            .menu-table thead { display: none; }
            .menu-table tbody tr {
                display: block; margin-bottom: 20px;
                border: 1px solid var(--border); border-radius: var(--radius);
                padding: 20px; background: rgba(255,255,255,0.8);
            }
            .menu-table td { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid var(--border); }
            .menu-table td:last-child { border-bottom: none; }
            .menu-table td::before {
                content: attr(data-label); font-weight: 700; color: var(--secondary);
                font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;
            }
        }

        /* Loading */
        .loading { position: relative; pointer-events: none; opacity: 0.7; }
        .loading::after {
            content: ''; position: absolute; top: 50%; left: 50%;
            width: 24px; height: 24px; margin: -12px 0 0 -12px;
            border: 3px solid var(--border); border-top-color: var(--primary);
            border-radius: 50%; animation: spin 0.8s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
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
                <a href="menu.php" class="nav-item active">
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

        <!-- Main Content -->
        <main class="main-content" id="mainContent">
            <!-- Top Bar -->
            <div class="top-bar">
                <div class="welcome-message">
                    <h1>Menu Management</h1>
                    <p>Add, edit or remove menu items</p>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon items">
                        <i class="fas fa-utensils"></i>
                    </div>
                    <div class="stat-info">
                        <h3 class="counter" data-target="<?= $total_items ?>">0</h3>
                        <p>Total Items</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon price">
                        <i class="fas fa-rupee-sign"></i>
                    </div>
                    <div class="stat-info">
                        <h3>₹<span class="counter" data-target="<?= round($avg_price) ?>">0</span></h3>
                        <p>Average Price</p>
                    </div>
                </div>
            </div>

            <!-- Add New Item Card -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-plus-circle"></i>
                        Add New Item
                    </h2>
                </div>
                
                <form method="post" id="addItemForm">
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Item Name *</label>
                            <input type="text" name="item_name" class="form-control" required 
                                   placeholder="e.g., Chicken Burger" autocomplete="off">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Price (₹) *</label>
                            <input type="number" name="price" class="form-control" 
                                   step="0.01" min="1" required placeholder="0.00" autocomplete="off">
                        </div>
                    </div>
                    
                    <div style="margin-top: 32px;">
                        <button type="submit" name="add_item" class="btn btn-primary" id="addItemBtn">
                            <i class="fas fa-plus"></i> Add Item
                        </button>
                        <button type="reset" class="btn btn-secondary" style="margin-left: 12px;">
                            <i class="fas fa-redo"></i> Clear
                        </button>
                    </div>
                </form>
            </div>

            <!-- Menu Items Card -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-list"></i>
                        All Items (<?= $total_items ?>)
                    </h2>
                </div>

                <?php if (empty($menu_items)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i class="fas fa-utensils"></i>
                        </div>
                        <h3 class="empty-title">No items yet</h3>
                        <p class="empty-subtitle">Add your first menu item above</p>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <table class="menu-table">
                            <thead>
                                <tr>
                                    <th>Item Name</th>
                                    <th>Price</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="menuTableBody">
                                <?php foreach ($menu_items as $item): ?>
                                    <tr id="row-<?= $item['id'] ?>">
                                        <td data-label="Item Name">
                                            <div class="item-name"><?= htmlspecialchars($item['item_name']) ?></div>
                                        </td>
                                        <td data-label="Price">
                                            <span class="item-price">₹<?= number_format($item['price'], 2) ?></span>
                                        </td>
                                        <td data-label="Actions" class="actions-cell">
                                            <div class="actions-group">
                                                <button type="button" class="btn btn-success btn-xs" onclick="editItem(<?= $item['id'] ?>, '<?= htmlspecialchars(addslashes($item['item_name']), ENT_QUOTES) ?>', <?= $item['price'] ?>)">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                                <form method="post" style="display: inline;" onsubmit="return confirm('Delete this item?');">
                                                    <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                                    <button type="submit" name="delete_item" class="btn btn-danger btn-xs">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
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
            mobileToggle.style.transform = sidebar.classList.contains('active') ? 'rotate(90deg)' : 'rotate(0deg)';
        });

        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 992 && !sidebar.contains(e.target) && !mobileToggle.contains(e.target)) {
                sidebar.classList.remove('active');
                mobileToggle.style.transform = 'rotate(0deg)';
            }
        });

        updateSidebar();

        // Animated Counters
        function animateCounters() {
            document.querySelectorAll('.counter').forEach(counter => {
                const target = parseInt(counter.getAttribute('data-target')) || 0;
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

        // Notification System
        function showNotification(message, type = 'success') {
            const container = document.getElementById('notificationContainer');
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            const icon = type === 'success' ? 'check-circle' : 'exclamation-circle';
            
            notification.innerHTML = `
                <div class="notification-icon"><i class="fas fa-${icon}"></i></div>
                <div class="notification-content">
                    <div class="notification-title">${type === 'success' ? 'Success!' : 'Error!'}</div>
                    <div class="notification-message">${message}</div>
                </div>
                <button class="notification-close" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
            `;
            
            container.appendChild(notification);
            setTimeout(() => notification.classList.add('show'), 10);
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.classList.remove('show');
                    setTimeout(() => notification.remove(), 400);
                }
            }, 5000);
        }

        <?php if (!empty($message)): ?>
        setTimeout(() => showNotification("<?= addslashes($message) ?>", "<?= $message_type ?>"), 500);
        <?php endif; ?>

        // Inline Edit
        function editItem(id, name, price) {
            const row = document.getElementById('row-' + id);
            if (!row) return;
            
            // Remove any existing edit rows
            document.querySelectorAll('.edit-form-row').forEach(r => {
                const originalId = r.getAttribute('data-original-id');
                location.reload(); // Simple refresh to restore
            });
            
            const cells = row.cells;
            cells[0].innerHTML = `
                <form method="post" class="edit-form" style="display: contents;">
                    <input type="hidden" name="item_id" value="${id}">
                    <input type="text" name="item_name" class="form-control" value="${name.replace(/"/g, '&quot;')}" required style="width: 100%;">
            `;
            cells[1].innerHTML = `
                    <input type="number" name="price" class="form-control" value="${price}" step="0.01" min="1" required style="width: 150px;">
            `;
            cells[2].innerHTML = `
                    <div class="actions-group">
                        <button type="submit" name="edit_item" class="btn btn-success btn-xs"><i class="fas fa-save"></i> Save</button>
                        <button type="button" class="btn btn-secondary btn-xs" onclick="location.reload()"><i class="fas fa-times"></i> Cancel</button>
                    </div>
                </form>
            `;
            
            row.classList.add('edit-form-row');
            row.setAttribute('data-original-id', id);
        }

        // Form loading state
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const btn = this.querySelector('button[type="submit"]');
                if (btn && !btn.classList.contains('btn-secondary')) {
                    btn.classList.add('loading');
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
                }
            });
        });
    </script>
</body>
</html>