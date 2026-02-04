<?php
// profile.php - Shop Owner Profile Page
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

// Get current shop information
$stmt = $conn->prepare("SELECT * FROM shops WHERE id = ?");
$stmt->bind_param("i", $shop_id);
$stmt->execute();
$shop = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $new_shop_name = trim($_POST['shop_name']);
        $owner_name = trim($_POST['owner_name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        
        $check_stmt = $conn->prepare("SELECT id FROM shops WHERE shop_name = ? AND id != ?");
        $check_stmt->bind_param("si", $new_shop_name, $shop_id);
        $check_stmt->execute();
        
        if ($check_stmt->get_result()->num_rows > 0) {
            $message = "Shop name already exists!";
            $message_type = 'error';
        } else {
            $update_stmt = $conn->prepare("UPDATE shops SET shop_name = ?, owner_name = ?, email = ?, phone = ? WHERE id = ?");
            $update_stmt->bind_param("ssssi", $new_shop_name, $owner_name, $email, $phone, $shop_id);
            
            if ($update_stmt->execute()) {
                $_SESSION['shop_name'] = $new_shop_name;
                $message = "Profile updated successfully!";
                $message_type = 'success';
                
                // Refresh data
                $stmt = $conn->prepare("SELECT * FROM shops WHERE id = ?");
                $stmt->bind_param("i", $shop_id);
                $stmt->execute();
                $shop = $stmt->get_result()->fetch_assoc();
                $stmt->close();
            } else {
                $message = "Error updating profile!";
                $message_type = 'error';
            }
            $update_stmt->close();
        }
        $check_stmt->close();
    }
    
    // Handle password change
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (password_verify($current_password, $shop['password_hash'])) {
            if ($new_password === $confirm_password && strlen($new_password) >= 6) {
                $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $pass_stmt = $conn->prepare("UPDATE shops SET password_hash = ? WHERE id = ?");
                $pass_stmt->bind_param("si", $new_hash, $shop_id);
                
                if ($pass_stmt->execute()) {
                    $message = "Password changed successfully!";
                    $message_type = 'success';
                } else {
                    $message = "Error changing password!";
                    $message_type = 'error';
                }
                $pass_stmt->close();
            } else {
                $message = "Passwords must match and be 6+ characters!";
                $message_type = 'error';
            }
        } else {
            $message = "Current password is incorrect!";
            $message_type = 'error';
        }
    }
}

// Get statistics
$stats = [];
$orders_stmt = $conn->prepare("SELECT COUNT(*) as count, COALESCE(SUM(total), 0) as revenue FROM orders WHERE shop_id = ?");
$orders_stmt->bind_param("i", $shop_id);
$orders_stmt->execute();
$orders_result = $orders_stmt->get_result()->fetch_assoc();
$stats['total_orders'] = $orders_result['count'] ?? 0;
$stats['total_revenue'] = $orders_result['revenue'] ?? 0;
$orders_stmt->close();

$menu_stmt = $conn->prepare("SELECT COUNT(*) as count FROM menu WHERE shop_id = ?");
$menu_stmt->bind_param("i", $shop_id);
$menu_stmt->execute();
$stats['menu_items'] = $menu_stmt->get_result()->fetch_assoc()['count'] ?? 0;
$menu_stmt->close();

// QR Code
$qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode("http://" . $_SERVER['HTTP_HOST'] . "/customer/menu.php?shop=" . $shop_name);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - <?= htmlspecialchars($shop_name) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
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
            --danger: #EF4444;
            
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1);
            --shadow-orange: 0 10px 40px -10px rgba(249, 115, 22, 0.4);
            
            --ease-spring: cubic-bezier(0.34, 1.56, 0.64, 1);
            --ease-smooth: cubic-bezier(0.4, 0, 0.2, 1);
            --ease-expo: cubic-bezier(0.16, 1, 0.3, 1);
            --radius: 20px;
            --radius-sm: 12px;
            --sidebar-width: 280px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: linear-gradient(135deg, #F8FAFC 0%, #FFF7ED 100%);
            color: var(--text);
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Background Shapes */
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
            margin-bottom: 32px;
            animation: slideDown 0.6s var(--ease-expo) backwards;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .welcome-message h1 {
            font-size: 2rem;
            font-weight: 800;
            color: var(--secondary);
            letter-spacing: -0.02em;
        }

        .welcome-message p {
            color: var(--text-secondary);
            font-size: 15px;
            font-weight: 500;
            margin-top: 4px;
        }

        /* Profile Hero */
        .profile-hero {
            background: linear-gradient(135deg, var(--secondary), #1e293b);
            border-radius: var(--radius);
            padding: 40px;
            margin-bottom: 32px;
            position: relative;
            overflow: hidden;
            animation: slideUp 0.6s var(--ease-expo) 0.1s backwards;
            box-shadow: var(--shadow-xl);
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .profile-hero::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(249, 115, 22, 0.3), transparent 70%);
            animation: pulse-glow 4s infinite;
        }

        @keyframes pulse-glow {
            0%, 100% { opacity: 0.5; transform: scale(1); }
            50% { opacity: 0.8; transform: scale(1.1); }
        }

        .profile-header {
            display: flex;
            align-items: center;
            gap: 32px;
            position: relative;
            z-index: 1;
        }

        .profile-avatar-large {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            font-weight: 800;
            color: white;
            border: 4px solid rgba(255,255,255,0.2);
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            animation: float-avatar 6s ease-in-out infinite;
        }

        @keyframes float-avatar {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .profile-info h2 {
            font-size: 2.5rem;
            font-weight: 800;
            color: white;
            margin-bottom: 8px;
        }

        .profile-meta {
            display: flex;
            gap: 24px;
            margin-top: 16px;
            flex-wrap: wrap;
        }

        .profile-meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: rgba(255,255,255,0.8);
            font-size: 15px;
            font-weight: 500;
        }

        .profile-meta-item i {
            color: var(--primary-light);
        }

        .status-badge-large {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: rgba(16, 185, 129, 0.2);
            color: #6EE7B7;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 700;
            margin-top: 12px;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .status-badge-large.pending {
            background: rgba(245, 158, 11, 0.2);
            color: #FCD34D;
            border-color: rgba(245, 158, 11, 0.3);
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 32px;
            animation: slideUp 0.6s var(--ease-expo) 0.2s backwards;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            padding: 24px;
            border-radius: var(--radius-sm);
            box-shadow: var(--shadow-sm);
            border: 1px solid rgba(255,255,255,0.6);
            transition: all 0.4s var(--ease-spring);
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-4px) scale(1.02);
            box-shadow: var(--shadow-lg), var(--shadow-orange);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--primary-light));
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-bottom: 16px;
            color: white;
        }

        .stat-icon.orders { background: linear-gradient(135deg, var(--primary), var(--primary-light)); }
        .stat-icon.revenue { background: linear-gradient(135deg, #10B981, #34D399); }
        .stat-icon.menu { background: linear-gradient(135deg, #3B82F6, #60A5FA); }
        .stat-icon.rating { background: linear-gradient(135deg, #F59E0B, #FBBF24); }

        .stat-value {
            font-size: 28px;
            font-weight: 800;
            color: var(--secondary);
            margin-bottom: 4px;
        }

        .stat-label {
            font-size: 13px;
            color: var(--text-secondary);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Content Grid */
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 24px;
            animation: slideUp 0.6s var(--ease-expo) 0.3s backwards;
        }

        .card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
            border: 1px solid rgba(255,255,255,0.6);
            overflow: hidden;
            transition: all 0.4s var(--ease-spring);
        }

        .card:hover {
            box-shadow: var(--shadow-lg);
        }

        .card-header {
            padding: 24px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--secondary);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .card-title i {
            color: var(--primary);
        }

        .card-body {
            padding: 24px;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 24px;
        }

        .form-group:last-child {
            margin-bottom: 0;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text);
            font-size: 14px;
        }

        .form-control {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid var(--border);
            border-radius: 12px;
            font-size: 15px;
            font-family: inherit;
            transition: all 0.3s;
            background: white;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(249, 115, 22, 0.1);
        }

        .form-control:disabled {
            background: #F1F5F9;
            color: var(--text-secondary);
            cursor: not-allowed;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
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
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s var(--ease-spring);
            font-family: inherit;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            box-shadow: 0 4px 15px rgba(249, 115, 22, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(249, 115, 22, 0.4);
        }

        .btn-secondary {
            background: var(--background);
            color: var(--text);
            border: 2px solid var(--border);
        }

        .btn-secondary:hover {
            border-color: var(--primary);
            color: var(--primary);
        }

        .btn-danger {
            background: linear-gradient(135deg, #EF4444, #DC2626);
            color: white;
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(239, 68, 68, 0.3);
        }

        /* QR Section */
        .qr-container {
            text-align: center;
            padding: 20px;
        }

        .qr-code-wrapper {
            background: white;
            padding: 20px;
            border-radius: 16px;
            box-shadow: var(--shadow);
            display: inline-block;
            margin-bottom: 20px;
            position: relative;
        }

        .qr-code-wrapper::before {
            content: '';
            position: absolute;
            inset: -3px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            border-radius: 20px;
            z-index: -1;
            opacity: 0.5;
        }

        .qr-code-wrapper img {
            width: 180px;
            height: 180px;
            border-radius: 8px;
        }

        .qr-info {
            color: var(--text-secondary);
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        /* Danger Zone */
        .danger-zone {
            background: linear-gradient(135deg, #FEF2F2, #FEE2E2);
            border: 1px solid #FECACA;
            border-radius: var(--radius);
            padding: 24px;
            margin-top: 24px;
        }

        .danger-zone .card-title {
            color: #DC2626;
        }

        .danger-zone .card-title i {
            color: #DC2626;
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

        /* Responsive */
        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .content-grid {
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
                padding: 20px;
            }
            .mobile-toggle {
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .profile-header {
                flex-direction: column;
                text-align: center;
            }
            .profile-meta {
                justify-content: center;
            }
        }

        @media (max-width: 640px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .form-row {
                grid-template-columns: 1fr;
            }
            .profile-hero {
                padding: 24px;
            }
            .profile-avatar-large {
                width: 80px;
                height: 80px;
                font-size: 32px;
            }
            .profile-info h2 {
                font-size: 1.75rem;
            }
        }

        /* Notification */
        .notification {
            position: fixed;
            top: 24px;
            right: 24px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 20px 24px;
            border-radius: var(--radius-sm);
            box-shadow: var(--shadow-xl);
            display: flex;
            align-items: center;
            gap: 16px;
            z-index: 1000;
            animation: slideInRight 0.4s var(--ease-spring);
            border-left: 4px solid var(--primary);
            font-weight: 600;
            max-width: 400px;
        }

        @keyframes slideInRight {
            from { transform: translateX(100%) scale(0.9); opacity: 0; }
            to { transform: translateX(0) scale(1); opacity: 1; }
        }

        .notification.success { border-left-color: var(--success); }
        .notification.error { border-left-color: var(--danger); }

        .notification-icon {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }

        .notification.success .notification-icon {
            background: #D1FAE5;
            color: var(--success);
        }

        .notification.error .notification-icon {
            background: #FEE2E2;
            color: var(--danger);
        }

        .notification-content h4 {
            margin-bottom: 4px;
            color: var(--secondary);
        }

        .notification-content p {
            color: var(--text-secondary);
            font-size: 14px;
            font-weight: 500;
        }

        .notification-close {
            background: none;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            margin-left: auto;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        .notification-close:hover {
            background: var(--background);
            color: var(--text);
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
                <a href="analytics.php" class="nav-item">
                    <i class="fas fa-chart-bar"></i>
                    <span class="nav-text">Analytics</span>
                </a>
                <a href="profile.php" class="nav-item active">
                    <i class="fas fa-user"></i>
                    <span class="nav-text">Profile</span>
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
                
                <button class="action-btn" onclick="location.href='settings.php'">
                    <i class="fas fa-cog"></i>
                    <span class="nav-text">Settings</span>
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
                    <h1>Profile</h1>
                    <p>Manage your restaurant profile and settings</p>
                </div>
            </div>

            <!-- Profile Hero -->
            <div class="profile-hero">
                <div class="profile-header">
                    <div class="profile-avatar-large">
                        <?= strtoupper(substr($shop['shop_name'], 0, 1)) ?>
                    </div>
                    <div class="profile-info">
                        <h2><?= htmlspecialchars($shop['shop_name']) ?></h2>
                        <div class="profile-meta">
                            <div class="profile-meta-item">
                                <i class="fas fa-user"></i>
                                <?= htmlspecialchars($shop['owner_name']) ?>
                            </div>
                            <div class="profile-meta-item">
                                <i class="fas fa-envelope"></i>
                                <?= htmlspecialchars($shop['email']) ?>
                            </div>
                            <div class="profile-meta-item">
                                <i class="fas fa-phone"></i>
                                <?= htmlspecialchars($shop['phone']) ?>
                            </div>
                        </div>
                        <span class="status-badge-large <?= $shop['status'] ?>">
                            <i class="fas fa-circle" style="font-size: 8px;"></i>
                            <?= ucfirst($shop['status']) ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon orders">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <div class="stat-value"><?= number_format($stats['total_orders']) ?></div>
                    <div class="stat-label">Total Orders</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon revenue">
                        <i class="fas fa-rupee-sign"></i>
                    </div>
                    <div class="stat-value">₹<?= number_format($stats['total_revenue'], 0) ?></div>
                    <div class="stat-label">Revenue</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon menu">
                        <i class="fas fa-utensils"></i>
                    </div>
                    <div class="stat-value"><?= number_format($stats['menu_items']) ?></div>
                    <div class="stat-label">Menu Items</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon rating">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="stat-value">4.8</div>
                    <div class="stat-label">Rating</div>
                </div>
            </div>

            <!-- Content Grid -->
            <div class="content-grid">
                <!-- Left Column -->
                <div>
                    <!-- Edit Profile -->
                    <div class="card" style="margin-bottom: 24px;">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-user-edit"></i>
                                Edit Profile
                            </h3>
                        </div>
                        <div class="card-body">
                            <form method="POST" id="profileForm">
                                <input type="hidden" name="update_profile" value="1">
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">Shop Name</label>
                                        <input type="text" name="shop_name" class="form-control" 
                                               value="<?= htmlspecialchars($shop['shop_name']) ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Owner Name</label>
                                        <input type="text" name="owner_name" class="form-control" 
                                               value="<?= htmlspecialchars($shop['owner_name']) ?>" required>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">Email Address</label>
                                        <input type="email" name="email" class="form-control" 
                                               value="<?= htmlspecialchars($shop['email']) ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Phone Number</label>
                                        <input type="tel" name="phone" class="form-control" 
                                               value="<?= htmlspecialchars($shop['phone']) ?>" required>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">Account Status</label>
                                        <input type="text" class="form-control" 
                                               value="<?= ucfirst($shop['status']) ?>" disabled>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Member Since</label>
                                        <input type="text" class="form-control" 
                                               value="<?= date('F d, Y', strtotime($shop['created_at'] ?? 'now')) ?>" disabled>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary" style="width: 100%;">
                                    <i class="fas fa-save"></i>
                                    Save Changes
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Change Password -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-lock"></i>
                                Change Password
                            </h3>
                        </div>
                        <div class="card-body">
                            <form method="POST" id="passwordForm">
                                <input type="hidden" name="change_password" value="1">
                                
                                <div class="form-group">
                                    <label class="form-label">Current Password</label>
                                    <input type="password" name="current_password" class="form-control" required>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">New Password</label>
                                        <input type="password" name="new_password" class="form-control" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Confirm Password</label>
                                        <input type="password" name="confirm_password" class="form-control" required>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary" style="width: 100%;">
                                    <i class="fas fa-key"></i>
                                    Update Password
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div>
                    <!-- QR Code -->
                    <div class="card" style="margin-bottom: 24px;">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-qrcode"></i>
                                QR Code
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="qr-container">
                                <div class="qr-code-wrapper">
                                    <img src="<?= $qr_url ?>" alt="QR Code">
                                </div>
                                <p class="qr-info">
                                    Customers can scan this code to view your menu and place orders directly.
                                </p>
                                <button onclick="downloadQR()" class="btn btn-primary" style="width: 100%;">
                                    <i class="fas fa-download"></i>
                                    Download QR Code
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="card" style="margin-bottom: 24px;">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-bolt"></i>
                                Quick Actions
                            </h3>
                        </div>
                        <div class="card-body">
                            <div style="display: flex; flex-direction: column; gap: 12px;">
                                <button onclick="location.href='menu.php'" class="btn btn-secondary" style="justify-content: flex-start;">
                                    <i class="fas fa-plus"></i>
                                    Add Menu Items
                                </button>
                                <button onclick="location.href='orders.php'" class="btn btn-secondary" style="justify-content: flex-start;">
                                    <i class="fas fa-list"></i>
                                    View Orders
                                </button>
                                <button onclick="location.href='analytics.php'" class="btn btn-secondary" style="justify-content: flex-start;">
                                    <i class="fas fa-chart-line"></i>
                                    View Analytics
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Danger Zone -->
                    <div class="danger-zone">
                        <h3 class="card-title" style="margin-bottom: 16px;">
                            <i class="fas fa-exclamation-triangle"></i>
                            Danger Zone
                        </h3>
                        <p style="color: #991B1B; font-size: 14px; margin-bottom: 16px;">
                            These actions cannot be undone. Please proceed with caution.
                        </p>
                        <button onclick="deleteAccount()" class="btn btn-danger" style="width: 100%;">
                            <i class="fas fa-trash-alt"></i>
                            Delete Account
                        </button>
                    </div>
                </div>
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

        // Notification
        function showNotification(title, message, type = 'success') {
            const existing = document.querySelector('.notification');
            if (existing) existing.remove();

            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.innerHTML = `
                <div class="notification-icon">
                    <i class="fas fa-${type === 'success' ? 'check' : 'exclamation'}"></i>
                </div>
                <div class="notification-content">
                    <h4>${title}</h4>
                    <p>${message}</p>
                </div>
                <button class="notification-close" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            `;
            document.body.appendChild(notification);

            setTimeout(() => {
                if (notification.parentElement) notification.remove();
            }, 5000);
        }

        <?php if (!empty($message)): ?>
            showNotification('<?= $message_type === 'success' ? 'Success' : 'Error' ?>', '<?= addslashes($message) ?>', '<?= $message_type ?>');
        <?php endif; ?>

        // Download QR
        function downloadQR() {
            const link = document.createElement('a');
            link.href = '<?= $qr_url ?>';
            link.download = '<?= preg_replace('/[^A-Za-z0-9]/', '-', $shop['shop_name']) ?>-qr-code.png';
            link.click();
            showNotification('Downloaded!', 'QR Code saved successfully');
        }

        // Delete Account
        function deleteAccount() {
            if (confirm('WARNING: This will permanently delete your account and all data. This cannot be undone!\n\nType "DELETE" to confirm:')) {
                const input = prompt('Type "DELETE" to confirm:');
                if (input === 'DELETE') {
                    showNotification('Processing', 'Account deletion request submitted', 'success');
                }
            }
        }

        // Form submissions
        document.getElementById('profileForm').addEventListener('submit', function(e) {
            const btn = this.querySelector('button[type="submit"]');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            btn.disabled = true;
        });

        document.getElementById('passwordForm').addEventListener('submit', function(e) {
            const newPass = this.querySelector('[name="new_password"]').value;
            const confirmPass = this.querySelector('[name="confirm_password"]').value;
            
            if (newPass !== confirmPass) {
                e.preventDefault();
                showNotification('Error', 'Passwords do not match!', 'error');
                return;
            }
            
            const btn = this.querySelector('button[type="submit"]');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
            btn.disabled = true;
        });
    </script>
</body>
</html>