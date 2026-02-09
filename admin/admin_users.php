<?php
// admin_users.php - Manage Shop Owners / Users (Super Admin)
session_start();
include "../config/db.php";

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$message = '';
$message_type = '';

// Handle status change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_status'])) {
    $shop_id = (int)($_POST['shop_id'] ?? 0);
    $new_status = trim($_POST['new_status'] ?? '');

    $allowed = ['pending', 'active', 'rejected'];
    if ($shop_id > 0 && in_array($new_status, $allowed)) {
        $stmt = $conn->prepare("UPDATE shops SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $shop_id);
        if ($stmt->execute()) {
            $message = "User/shop status updated to " . ucfirst($new_status) . "!";
            $message_type = 'success';
        } else {
            $message = "Error: " . $stmt->error;
            $message_type = 'error';
        }
        $stmt->close();
    } else {
        $message = "Invalid request";
        $message_type = 'error';
    }
}

// Filters & search
$status_filter = $_GET['status'] ?? 'all';
$search = trim($_GET['search'] ?? '');

$query = "SELECT id, shop_name, owner_name, email, phone, status, created_at 
          FROM shops WHERE 1=1";
$params = [];
$types = "";

if ($status_filter !== 'all') {
    $query .= " AND status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if ($search !== '') {
    $query .= " AND (shop_name LIKE ? OR owner_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= "ssss";
}

$query .= " ORDER BY created_at DESC";

$stmt = $conn->prepare($query);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Stats
$total_q = $conn->query("SELECT COUNT(*) as cnt FROM shops");
$total_users = $total_q->fetch_assoc()['cnt'] ?? 0;

$active_q = $conn->query("SELECT COUNT(*) as cnt FROM shops WHERE status = 'active'");
$active_users = $active_q->fetch_assoc()['cnt'] ?? 0;

$pending_q = $conn->query("SELECT COUNT(*) as cnt FROM shops WHERE status = 'pending'");
$pending_users = $pending_q->fetch_assoc()['cnt'] ?? 0;

$rejected_q = $conn->query("SELECT COUNT(*) as cnt FROM shops WHERE status = 'rejected'");
$rejected_users = $rejected_q->fetch_assoc()['cnt'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Users & Shops - RestoFlow Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet"/>
    <style>
        :root {
            --primary: #F6921E;
            --primary-dark: #E07E0A;
            --primary-light: #FF8C42;
            --secondary: #FFF5F0;
            --accent: #FFAD87;
            --dark: #2D3436;
            --darker: #1a1e1f;
            --light: #FFFFFF;
            --gray: #F1F2F6;
            --gray-50: #FFF8F5;
            --gray-100: #FFF0EB;
            --gray-200: #FFE4DB;
            --gray-300: #FFD0C0;
            --gray-600: #636E72;
            --gray-700: #4A5052;
            --text: #2D3436;
            --success: #00B894;
            --warning: #FDCB6E;
            --info: #74B9FF;
            --error: #FF5252;
            --shadow: 0 10px 40px rgba(246, 146, 30, 0.15);
            --shadow-hover: 0 20px 60px rgba(246, 146, 30, 0.25);
            --shadow-lg: 0 10px 30px rgba(0, 0, 0, 0.1);
            --gradient: linear-gradient(135deg, #FF6B35 0%, #FF8C42 50%, #FFAD87 100%);
            --gradient-light: linear-gradient(135deg, #FFF5F0 0%, #FFFFFF 100%);
            --radius: 12px;
            --radius-lg: 16px;
            --sidebar-width: 260px;
            --sidebar-collapsed: 70px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * { margin:0; padding:0; box-sizing:border-box; }
        
        body {
            font-family: 'Inter', 'Poppins', sans-serif;
            background: var(--gradient-light);
            color: var(--text);
            min-height: 100vh;
            overflow-x: hidden;
        }

        .dashboard-container { display: flex; min-height: 100vh; position: relative; }

        /* Sidebar - Orange Theme */
        .sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(180deg, var(--darker) 0%, #2d2420 100%);
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

        .logo { 
            display: flex; 
            align-items: center; 
            gap: 12px; 
            padding: 0 8px 24px; 
            border-bottom: 1px solid rgba(255,255,255,0.1); 
            margin-bottom: 24px; 
        }
        
        .logo-icon { 
            width: 40px; 
            height: 40px; 
            background: var(--gradient); 
            border-radius: 10px; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-size: 20px; 
            color: white;
        }
        
        .logo-text { 
            font-size: 1.5rem; 
            font-weight: 700; 
            background: linear-gradient(135deg, #ffffff, var(--primary-light)); 
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
            color: rgba(255,255,255,0.8); 
            text-decoration: none; 
            transition: var(--transition); 
        }
        
        .nav-item:hover { 
            background: rgba(255,255,255,0.1); 
            color: white; 
            transform: translateX(4px); 
        }
        
        .nav-item.active { 
            background: var(--gradient); 
            color: white; 
            box-shadow: 0 4px 15px rgba(246, 146, 30, 0.4); 
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

        .user-actions { 
            border-top: 1px solid rgba(255,255,255,0.1); 
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
            background: var(--gradient); 
            border-radius: 50%; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-weight: 600; 
            margin: 0 auto 8px; 
            color: white;
        }
        
        .user-name { 
            font-weight: 600; 
            font-size: 14px; 
            margin-bottom: 4px; 
        }
        
        .user-role { 
            font-size: 12px; 
            color: rgba(255,255,255,0.6); 
        }

        .action-btn { 
            display: flex; 
            align-items: center; 
            gap: 12px; 
            padding: 14px 16px; 
            border-radius: 10px; 
            background: transparent; 
            border: none; 
            color: rgba(255,255,255,0.8); 
            font-family: inherit; 
            font-size: 15px; 
            cursor: pointer; 
            transition: var(--transition); 
            width: 100%; 
            text-align: left; 
        }
        
        .action-btn:hover { 
            background: rgba(255,255,255,0.1); 
            color: white; 
        }
        
        .action-btn.logout { 
            color: #ff9f9f; 
        }
        
        .action-btn.logout:hover { 
            background: rgba(255, 82, 82, 0.2); 
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
            border: 1px solid var(--gray-200);
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

        /* Stats Grid - Orange Theme */
        .stats-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); 
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
            border: 1px solid var(--gray-200); 
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
            background: var(--gradient); 
        }
        
        .stat-card:hover { 
            transform: translateY(-5px); 
            box-shadow: var(--shadow-hover); 
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
            font-size: 2.2rem; 
            font-weight: 700; 
            color: var(--darker); 
            margin-bottom: 4px; 
        }
        
        .stat-info p { 
            color: var(--gray-600); 
            font-size: 14px; 
            font-weight: 500; 
        }

        /* Card */
        .card { 
            background: var(--light); 
            border-radius: var(--radius-lg); 
            box-shadow: var(--shadow); 
            padding: 28px; 
            margin-bottom: 40px; 
            border: 1px solid var(--gray-200); 
        }
        
        .card-header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 28px; 
            padding-bottom: 16px; 
            border-bottom: 2px solid var(--gray-200); 
        }
        
        .card-title { 
            font-size: 1.6rem; 
            font-weight: 700; 
            color: var(--darker); 
            display: flex; 
            align-items: center; 
            gap: 12px; 
        }

        /* Filter & Search */
        .filter-bar { 
            display: flex; 
            gap: 16px; 
            flex-wrap: wrap; 
            margin-bottom: 28px; 
            align-items: flex-end; 
        }
        
        .filter-group { 
            flex: 1; 
            min-width: 180px; 
        }
        
        .filter-group label { 
            display: block; 
            margin-bottom: 8px; 
            font-weight: 600; 
            color: var(--gray-700); 
            font-size: 14px; 
        }
        
        .filter-input, .filter-select { 
            width: 100%; 
            padding: 12px 16px; 
            border: 2px solid var(--gray-200); 
            border-radius: 10px; 
            font-size: 15px; 
            background: white;
            transition: var(--transition);
        }

        .filter-input:focus, .filter-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(246, 146, 30, 0.1);
        }
        
        .btn-filter { 
            padding: 12px 28px; 
            background: var(--gradient); 
            color: white; 
            border: none; 
            border-radius: 10px; 
            font-weight: 600; 
            cursor: pointer; 
            transition: var(--transition); 
            box-shadow: 0 4px 15px rgba(246, 146, 30, 0.3);
        }
        
        .btn-filter:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 6px 20px rgba(246, 146, 30, 0.4);
        }

        /* Table */
        .table-container { 
            overflow-x: auto; 
            border-radius: var(--radius); 
            border: 1px solid var(--gray-200); 
        }
        
        table { 
            width: 100%; 
            border-collapse: collapse; 
        }
        
        th, td { 
            padding: 16px 20px; 
            text-align: left; 
            border-bottom: 1px solid var(--gray-200); 
        }
        
        th { 
            background: var(--gray-50); 
            font-weight: 600; 
            color: var(--gray-700); 
            text-transform: uppercase; 
            font-size: 0.9rem; 
            letter-spacing: 0.5px; 
        }
        
        tr:hover { 
            background: var(--gray-50); 
        }

        .status-badge { 
            padding: 6px 14px; 
            border-radius: 20px; 
            font-size: 0.85rem; 
            font-weight: 600; 
            display: inline-block;
        }
        
        .status-pending  { 
            background: #fff8e6; 
            color: #b7791f; 
            border: 1px solid #f6e05e;
        }
        
        .status-active   { 
            background: #e6fffa; 
            color: #047857; 
            border: 1px solid #34d399;
        }
        
        .status-rejected { 
            background: #ffe6e6; 
            color: #991b1b; 
            border: 1px solid #f87171;
        }

        /* Action Buttons */
        .table-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn-action { 
            padding: 8px 16px; 
            border-radius: 8px; 
            font-size: 0.85rem; 
            cursor: pointer; 
            transition: var(--transition); 
            border: none; 
            color: white;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .btn-approve { 
            background: var(--success); 
            box-shadow: 0 2px 8px rgba(0, 184, 148, 0.3);
        }
        
        .btn-reject  { 
            background: var(--error); 
            box-shadow: 0 2px 8px rgba(255, 82, 82, 0.3);
        }

        .btn-deactivate {
            background: var(--warning);
            color: #744210;
            box-shadow: 0 2px 8px rgba(253, 203, 110, 0.4);
        }
        
        .btn-action:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        /* Notification */
        .notification {
            position: fixed; 
            top: 30px; 
            right: 30px; 
            z-index: 1000;
            padding: 16px 24px; 
            border-radius: 12px; 
            box-shadow: var(--shadow-lg);
            display: flex; 
            align-items: center; 
            gap: 12px; 
            min-width: 320px;
            transform: translateX(120%); 
            transition: transform 0.4s ease;
        }
        
        .notification.show { 
            transform: translateX(0); 
        }
        
        .notification.success { 
            background: #ecfdf5; 
            color: #065f46; 
            border-left: 5px solid var(--success); 
        }
        
        .notification.error { 
            background: #fef2f2; 
            color: #991b1b; 
            border-left: 5px solid var(--error); 
        }

        .empty-state { 
            text-align: center; 
            padding: 100px 20px; 
            color: var(--gray-600); 
        }
        
        .empty-icon { 
            font-size: 6rem; 
            color: var(--gray-300); 
            margin-bottom: 24px; 
        }

        /* Mobile Toggle */
        .mobile-toggle {
            display: none; 
            position: fixed; 
            top: 20px; 
            left: 20px; 
            z-index: 99;
            background: var(--gradient); 
            color: white; 
            border: none;
            width: 48px; 
            height: 48px; 
            border-radius: 12px;
            font-size: 22px; 
            cursor: pointer; 
            box-shadow: var(--shadow);
        }

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
            .filter-bar { flex-direction: column; }
            .table-actions { flex-direction: column; }
            .btn-action { width: 100%; justify-content: center; }
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
            <span class="logo-text">RestoFlow Admin</span>
        </div>

        <nav class="nav-menu">
            <a href="dashboard.php" class="nav-item">
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
            <a href="admin_users.php" class="nav-item active">
                <i class="fas fa-users"></i>
                <span class="nav-text">Users & Shops</span>
            </a>
            <a href="admin_analytics.php" class="nav-item">
    <i class="fas fa-chart-line"></i>
    <span class="nav-text">Analytics</span>
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
                <h1>Users & Shops</h1>
                <p>Manage all registered shop owners and their accounts</p>
            </div>
        </div>

        <!-- Stats Overview -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background: var(--gradient);">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <h3><?= number_format($total_users) ?></h3>
                    <p>Total Shop Owners</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, var(--success), #00a383);">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <h3><?= number_format($active_users) ?></h3>
                    <p>Active Accounts</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, var(--warning), #f5b041);">
                    <i class="fas fa-hourglass-half"></i>
                </div>
                <div class="stat-info">
                    <h3><?= number_format($pending_users) ?></h3>
                    <p>Pending Approval</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, var(--error), #e74c3c);">
                    <i class="fas fa-ban"></i>
                </div>
                <div class="stat-info">
                    <h3><?= number_format($rejected_users) ?></h3>
                    <p>Rejected Accounts</p>
                </div>
            </div>
        </div>

        <!-- Filter & Search -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">
                    <i class="fas fa-filter" style="color: var(--primary);"></i>
                    Filter Users/Shops
                </h2>
            </div>

            <form method="get" class="filter-bar">
                <div class="filter-group">
                    <label>Status</label>
                    <select name="status" class="filter-select">
                        <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>All Statuses</option>
                        <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="active" <?= $status_filter === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="rejected" <?= $status_filter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label>Search</label>
                    <input type="text" name="search" class="filter-input" 
                           placeholder="Shop name, owner, email, phone..." 
                           value="<?= htmlspecialchars($search) ?>">
                </div>

                <button type="submit" class="btn-filter">
                    <i class="fas fa-search"></i> Filter
                </button>
            </form>
        </div>

        <!-- Users/Shops Table -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">
                    <i class="fas fa-users" style="color: var(--primary);"></i>
                    All Shop Owners (<?= number_format(count($users)) ?>)
                </h2>
            </div>

            <?php if (empty($users)): ?>
                <div class="empty-state">
                    <div class="empty-icon"><i class="fas fa-users-slash"></i></div>
                    <h3>No users found</h3>
                    <p>Try adjusting filters or no shop owners have registered yet.</p>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Shop Name</th>
                                <th>Owner Name</th>
                                <th>Email / Phone</th>
                                <th>Status</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><strong>#<?= $user['id'] ?></strong></td>
                                    <td><?= htmlspecialchars($user['shop_name']) ?></td>
                                    <td><?= htmlspecialchars($user['owner_name'] ?: '—') ?></td>
                                    <td>
                                        <?= htmlspecialchars($user['email']) ?><br>
                                        <small style="color: var(--gray-600);"><?= htmlspecialchars($user['phone'] ?: '—') ?></small>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?= $user['status'] ?>">
                                            <?= ucfirst($user['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?= isset($user['created_at']) ? date('d M Y', strtotime($user['created_at'])) : '—' ?>
                                    </td>
                                    <td>
                                        <form method="post" style="display:inline;">
                                            <input type="hidden" name="shop_id" value="<?= $user['id'] ?>">
                                            <input type="hidden" name="new_status" value="">
                                            <?php if ($user['status'] === 'pending'): ?>
                                                <div class="table-actions">
                                                    <button type="submit" name="change_status" value="1" 
                                                            onclick="this.form.new_status.value='active'" 
                                                            class="btn-action btn-approve">
                                                        <i class="fas fa-check"></i> Approve
                                                    </button>
                                                    <button type="submit" name="change_status" value="1" 
                                                            onclick="this.form.new_status.value='rejected'" 
                                                            class="btn-action btn-reject">
                                                        <i class="fas fa-times"></i> Reject
                                                    </button>
                                                </div>
                                            <?php elseif ($user['status'] === 'active'): ?>
                                                <button type="submit" name="change_status" value="1" 
                                                        onclick="this.form.new_status.value='rejected'" 
                                                        class="btn-action btn-deactivate">
                                                    <i class="fas fa-ban"></i> Deactivate
                                                </button>
                                            <?php elseif ($user['status'] === 'rejected'): ?>
                                                <button type="submit" name="change_status" value="1" 
                                                        onclick="this.form.new_status.value='active'" 
                                                        class="btn-action btn-approve">
                                                    <i class="fas fa-redo"></i> Reactivate
                                                </button>
                                            <?php endif; ?>
                                        </form>
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

<!-- Notification -->
<?php if ($message): ?>
<div id="notification" class="notification <?= $message_type ?> show">
    <i class="fas fa-<?= $message_type === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
    <?= htmlspecialchars($message) ?>
</div>
<?php endif; ?>

<script>
// Sidebar toggle
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

// Auto-hide notification after 5 seconds
setTimeout(() => {
    const notif = document.getElementById('notification');
    if (notif) notif.classList.remove('show');
}, 5000);
</script>

</body>
</html>