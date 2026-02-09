<?php
// admin_orders.php - Super Admin: View & Manage All Orders
session_start();
include "../config/db.php";

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$message = '';
$message_type = '';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id   = (int)($_POST['order_id'] ?? 0);
    $new_status = trim($_POST['new_status'] ?? '');

    $allowed = ['pending', 'paid', 'completed', 'cancelled'];
    if ($order_id > 0 && in_array($new_status, $allowed)) {
        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
        $stmt->bind_param("si", $new_status, $order_id);
        if ($stmt->execute()) {
            $message = "Order #{$order_id} status updated to " . ucfirst($new_status) . "!";
            $message_type = 'success';
        } else {
            $message = "Error: " . $stmt->error;
            $message_type = 'error';
        }
        $stmt->close();
    }
}

// Filters
$status_filter = $_GET['status'] ?? 'all';
$date_from     = $_GET['date_from'] ?? '';
$date_to       = $_GET['date_to'] ?? '';
$search        = trim($_GET['search'] ?? '');

// Build query
$query = "SELECT o.order_id, s.shop_name, o.table_no, o.token, o.total, o.status, o.created_at
          FROM orders o
          JOIN shops s ON s.id = o.shop_id
          WHERE 1=1";
$params = [];
$types  = "";

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

if ($search !== '') {
    $query .= " AND (o.order_id LIKE ? OR s.shop_name LIKE ? OR o.token LIKE ? OR o.table_no LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types   .= "ssss";
}

$query .= " ORDER BY o.created_at DESC LIMIT 200";

$stmt = $conn->prepare($query);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ────────────────────────────────────────────────
// Analytics / Stats (platform-wide)
// ────────────────────────────────────────────────

// Total Revenue
$revenue_q = $conn->query("SELECT COALESCE(SUM(total), 0) as rev 
                           FROM orders WHERE status IN ('paid','completed')");
$total_revenue = $revenue_q->fetch_assoc()['rev'] ?? 0;

// Total Orders
$total_orders_q = $conn->query("SELECT COUNT(*) as cnt FROM orders");
$total_orders = $total_orders_q->fetch_assoc()['cnt'] ?? 0;

// Today's Revenue & Orders
$today = date('Y-m-d');
$today_rev_q = $conn->query("SELECT COALESCE(SUM(total), 0) as rev 
                             FROM orders 
                             WHERE DATE(created_at) = '$today' 
                               AND status IN ('paid','completed')");
$today_revenue = $today_rev_q->fetch_assoc()['rev'] ?? 0;

$today_orders_q = $conn->query("SELECT COUNT(*) as cnt FROM orders WHERE DATE(created_at) = '$today'");
$today_orders = $today_orders_q->fetch_assoc()['cnt'] ?? 0;

// Average Order Value
$avg_order = $total_orders > 0 ? round($total_revenue / $total_orders, 2) : 0;

// Status counts for pie chart / quick view
$status_counts = ['pending' => 0, 'paid' => 0, 'completed' => 0, 'cancelled' => 0];
foreach ($orders as $o) {
    if (isset($status_counts[$o['status']])) $status_counts[$o['status']]++;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>All Orders - RestoFlow Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet"/>
    <style>
        /* === ORANGE THEME STYLES === */
        :root {
            --primary: #F6921E;
            --primary-dark: #E07E0A;
            --primary-light: #FF8C42;
            --secondary: #FFF5F0;
            --accent: #FFAD87;
            --dark: #2D3436;
            --darker: #1A1A1A;
            --gray-50: #FFF5F0;
            --gray-100: #FFE8E0;
            --gray-200: #FFD4C4;
            --gray-300: #FFC4B0;
            --gray-600: #6C5CE7;
            --gray-700: #2D3436;
            --light: #FFFFFF;
            --success: #00B894;
            --warning: #FDCB6E;
            --info: #74B9FF;
            --danger: #FF5252;
            --shadow-sm: 0 1px 2px 0 rgba(255, 107, 53, 0.05);
            --shadow: 0 4px 6px -1px rgba(255, 107, 53, 0.1), 0 2px 4px -1px rgba(255, 107, 53, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(255, 107, 53, 0.1), 0 4px 6px -2px rgba(255, 107, 53, 0.05);
            --radius: 12px;
            --radius-lg: 16px;
            --sidebar-width: 260px;
            --sidebar-collapsed: 70px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family: 'Inter', 'Poppins', sans-serif;
            background: linear-gradient(135deg, #FFF5F0 0%, #FFFFFF 100%);
            color: var(--dark);
            min-height: 100vh;
            overflow-x: hidden;
        }

        .dashboard-container { display: flex; min-height: 100vh; position: relative; }

        /* Sidebar - Orange Theme */
        .sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(180deg, var(--darker) 0%, #2D3436 100%);
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

        .logo { display: flex; align-items: center; gap: 12px; padding: 0 8px 24px; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 24px; }
        .logo-icon { width: 40px; height: 40px; background: linear-gradient(135deg, var(--primary), var(--primary-light)); border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px; }
        .logo-text { font-size: 1.5rem; font-weight: 700; background: linear-gradient(135deg, #ffffff, var(--accent)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }

        .sidebar-toggle { position: absolute; right: -12px; top: 24px; width: 24px; height: 24px; background: var(--light); border: 2px solid var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; color: var(--primary); font-size: 12px; transition: var(--transition); z-index: 101; }
        .sidebar-toggle:hover { transform: scale(1.1); }

        .nav-menu { flex: 1; display: flex; flex-direction: column; gap: 8px; }
        .nav-item { display: flex; align-items: center; gap: 12px; padding: 14px 16px; border-radius: 10px; color: rgba(255,255,255,0.8); text-decoration: none; transition: var(--transition); }
        .nav-item:hover { background: rgba(255,255,255,0.1); color: white; transform: translateX(4px); }
        .nav-item.active { background: linear-gradient(135deg, var(--primary), var(--primary-dark)); color: white; box-shadow: var(--shadow); }
        .nav-item i { font-size: 18px; width: 24px; text-align: center; }
        .nav-text { font-weight: 500; font-size: 15px; }

        .user-actions { border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px; margin-top: auto; display: flex; flex-direction: column; gap: 8px; }
        .user-info { padding: 0 8px 16px; text-align: center; }
        .user-avatar { width: 40px; height: 40px; background: linear-gradient(135deg, var(--primary), var(--primary-light)); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; margin: 0 auto 8px; }
        .user-name { font-weight: 600; font-size: 14px; margin-bottom: 4px; }
        .user-role { font-size: 12px; color: rgba(255,255,255,0.6); }

        .action-btn { display: flex; align-items: center; gap: 12px; padding: 14px 16px; border-radius: 10px; background: transparent; border: none; color: rgba(255,255,255,0.8); font-family: inherit; font-size: 15px; cursor: pointer; transition: var(--transition); width: 100%; text-align: left; }
        .action-btn:hover { background: rgba(255,255,255,0.1); color: white; }
        .action-btn.logout { color: #FFAD87; }
        .action-btn.logout:hover { background: rgba(255, 82, 82, 0.2); }

        /* Main Content */
        .main-content { flex: 1; margin-left: var(--sidebar-width); padding: 24px; transition: var(--transition); }
        .main-content.expanded { margin-left: var(--sidebar-collapsed); }

        .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; background: var(--light); padding: 20px 24px; border-radius: var(--radius-lg); box-shadow: var(--shadow); border: 1px solid var(--gray-200); }
        .welcome-message h1 { font-size: 1.75rem; font-weight: 700; color: var(--darker); margin-bottom: 4px; }
        .welcome-message p { color: var(--gray-600); font-size: 14px; }

        /* Stats Grid */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 32px; }
        .stat-card { background: var(--light); padding: 24px; border-radius: var(--radius-lg); box-shadow: var(--shadow); display: flex; align-items: center; gap: 20px; transition: var(--transition); border: 1px solid var(--gray-200); position: relative; overflow: hidden; }
        .stat-card::before { content: ''; position: absolute; top: 0; left: 0; width: 6px; height: 100%; background: linear-gradient(to bottom, var(--primary), var(--primary-dark)); }
        .stat-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-lg); }
        .stat-icon { width: 56px; height: 56px; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 24px; color: white; }
        .stat-info h3 { font-size: 2.2rem; font-weight: 700; color: var(--darker); margin-bottom: 4px; }
        .stat-info p { color: var(--gray-600); font-size: 14px; font-weight: 500; }

        /* Card */
        .card { background: var(--light); border-radius: var(--radius-lg); box-shadow: var(--shadow); padding: 28px; margin-bottom: 40px; border: 1px solid var(--gray-200); }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 28px; padding-bottom: 16px; border-bottom: 2px solid var(--gray-200); }
        .card-title { font-size: 1.6rem; font-weight: 700; color: var(--darker); display: flex; align-items: center; gap: 12px; }
        .card-title i { color: var(--primary); }

        /* Filter Bar */
        .filter-bar { display: flex; gap: 16px; flex-wrap: wrap; margin-bottom: 28px; align-items: flex-end; }
        .filter-group { flex: 1; min-width: 180px; }
        .filter-group label { display: block; margin-bottom: 8px; font-weight: 600; color: var(--gray-700); font-size: 14px; }
        .filter-input, .filter-select { width: 100%; padding: 12px 16px; border: 2px solid var(--gray-200); border-radius: 10px; font-size: 15px; background: white; }
        .filter-input:focus, .filter-select:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(246, 146, 30, 0.1); }
        .btn-filter { padding: 12px 28px; background: var(--primary); color: white; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; transition: var(--transition); }
        .btn-filter:hover { background: var(--primary-dark); transform: translateY(-2px); box-shadow: 0 4px 12px rgba(246, 146, 30, 0.3); }

        /* Table */
        .table-container { overflow-x: auto; border-radius: var(--radius); border: 1px solid var(--gray-200); }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 16px 20px; text-align: left; border-bottom: 1px solid var(--gray-200); }
        th { background: var(--gray-50); font-weight: 600; color: var(--gray-700); text-transform: uppercase; font-size: 0.9rem; }
        tr:hover { background: var(--gray-50); }

        .token-big {
            font-size: 2.8rem;
            font-weight: 900;
            color: var(--primary);
            line-height: 1;
            text-align: center;
        }
        .token-label { font-size: 0.9rem; color: var(--gray-600); font-weight: 600; margin-top: 4px; }

        .status-badge { padding: 6px 14px; border-radius: 20px; font-size: 0.9rem; font-weight: 600; }
        .status-pending  { background: #FFF5E6; color: #E07E0A; }
        .status-paid     { background: #E6F7F4; color: #00B894; }
        .status-completed { background: #E6F0FF; color: #0984E3; }
        .status-cancelled { background: #FFE6E6; color: #FF5252; }

        .action-select {
            padding: 8px 12px;
            border: 2px solid var(--gray-200);
            border-radius: 8px;
            background: white;
            font-size: 0.95rem;
            cursor: pointer;
        }
        .action-select:focus { outline: none; border-color: var(--primary); }

        .empty-state { text-align: center; padding: 100px 20px; color: var(--gray-600); }
        .empty-icon { font-size: 6rem; color: var(--gray-300); margin-bottom: 24px; }

        /* Notification */
        .notification {
            position: fixed; top: 30px; right: 30px; z-index: 1000;
            padding: 16px 24px; border-radius: 12px; box-shadow: var(--shadow-lg);
            display: flex; align-items: center; gap: 12px; min-width: 320px;
            transform: translateX(120%); transition: transform 0.4s ease;
        }
        .notification.show { transform: translateX(0); }
        .notification.success { background: #E6F7F4; color: #00B894; border-left: 5px solid var(--success); }
        .notification.error   { background: #FFE6E6; color: #FF5252; border-left: 5px solid var(--danger); }

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
        }

        .mobile-toggle {
            display: none; position: fixed; top: 20px; left: 20px; z-index: 99;
            background: var(--primary); color: white; border: none;
            width: 48px; height: 48px; border-radius: 12px;
            font-size: 22px; cursor: pointer; box-shadow: var(--shadow);
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
            <a href="admin_orders.php" class="nav-item active">
                <i class="fas fa-clipboard-list"></i>
                <span class="nav-text">All Orders</span>
            </a>
            <a href="admin_users.php" class="nav-item">
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
                <h1>All Orders (All Shops)</h1>
                <p>Platform-wide order overview & analytics</p>
            </div>
        </div>

        <!-- Analytics / Stats Overview -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, var(--info), #74B9FF);">
                    <i class="fas fa-receipt"></i>
                </div>
                <div class="stat-info">
                    <h3><?= number_format($total_orders) ?></h3>
                    <p>Total Orders</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, var(--success), #00D2A0);">
                    <i class="fas fa-rupee-sign"></i>
                </div>
                <div class="stat-info">
                    <h3>₹<?= number_format($total_revenue, 2) ?></h3>
                    <p>Total Revenue</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, var(--warning), #FDCB6E);">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <div class="stat-info">
                    <h3>₹<?= number_format($today_revenue, 2) ?></h3>
                    <p>Today's Revenue</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, var(--primary), var(--primary-light));">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-info">
                    <h3>₹<?= number_format($avg_order, 2) ?></h3>
                    <p>Avg. Order Value</p>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">
                    <i class="fas fa-filter"></i>
                    Filter Orders
                </h2>
            </div>

            <form method="get" class="filter-bar">
                <div class="filter-group">
                    <label>Status</label>
                    <select name="status" class="filter-select">
                        <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>All Statuses</option>
                        <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="paid" <?= $status_filter === 'paid' ? 'selected' : '' ?>>Paid</option>
                        <option value="completed" <?= $status_filter === 'completed' ? 'selected' : '' ?>>Completed</option>
                        <option value="cancelled" <?= $status_filter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label>Date From</label>
                    <input type="date" name="date_from" class="filter-input" value="<?= htmlspecialchars($date_from) ?>">
                </div>

                <div class="filter-group">
                    <label>Date To</label>
                    <input type="date" name="date_to" class="filter-input" value="<?= htmlspecialchars($date_to) ?>">
                </div>

                <div class="filter-group">
                    <label>Search</label>
                    <input type="text" name="search" class="filter-input" 
                           placeholder="Order ID, Shop, Token..." value="<?= htmlspecialchars($search) ?>">
                </div>

                <button type="submit" class="btn-filter">
                    <i class="fas fa-search"></i> Filter
                </button>
            </form>
        </div>

        <!-- All Orders Table -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">
                    <i class="fas fa-list"></i>
                    All Orders (<?= number_format(count($orders)) ?>)
                </h2>
            </div>

            <?php if (empty($orders)): ?>
                <div class="empty-state">
                    <div class="empty-icon"><i class="fas fa-receipt"></i></div>
                    <h3>No orders found</h3>
                    <p>Try adjusting filters or no orders have been placed yet.</p>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Shop</th>
                                <th>Token / Table</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Date & Time</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><strong>#<?= $order['order_id'] ?></strong></td>
                                    <td><?= htmlspecialchars($order['shop_name']) ?></td>
                                    <td style="text-align:center;">
                                        <?php if (!empty($order['token']) && $order['token'] !== '0'): ?>
                                            <div class="token-big"><?= htmlspecialchars($order['token']) ?></div>
                                            <div class="token-label">Token</div>
                                        <?php elseif (!empty($order['table_no'])): ?>
                                            <div style="font-size:2.2rem; font-weight:800; color:var(--info);">
                                                <?= htmlspecialchars($order['table_no']) ?>
                                            </div>
                                            <div class="token-label">Table</div>
                                        <?php else: ?>
                                            <span style="color:var(--gray-400); font-size:1.8rem;">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong>₹<?= number_format($order['total'], 2) ?></strong></td>
                                    <td>
                                        <span class="status-badge status-<?= strtolower($order['status']) ?>">
                                            <?= ucfirst($order['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?= date('d M Y', strtotime($order['created_at'])) ?><br>
                                        <small><?= date('h:i A', strtotime($order['created_at'])) ?></small>
                                    </td>
                                    <td>
                                        <form method="post" style="display:inline;">
                                            <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                                            <select name="new_status" class="action-select" onchange="this.form.submit()">
                                                <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                                <option value="paid" <?= $order['status'] === 'paid' ? 'selected' : '' ?>>Paid</option>
                                                <option value="completed" <?= $order['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                                                <option value="cancelled" <?= $order['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                            </select>
                                            <input type="hidden" name="update_status" value="1">
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
// Sidebar toggle functionality
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

// Auto-hide notification
setTimeout(() => {
    const notif = document.getElementById('notification');
    if (notif) notif.classList.remove('show');
}, 5000);
</script>

</body>
</html>