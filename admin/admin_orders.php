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
    $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
    $types   .= "ssss";
}

$query .= " ORDER BY o.created_at DESC LIMIT 200";

$stmt = $conn->prepare($query);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Analytics
$revenue_q = $conn->query("SELECT COALESCE(SUM(total), 0) as rev FROM orders WHERE status IN ('paid','completed')");
$total_revenue = $revenue_q->fetch_assoc()['rev'] ?? 0;

$total_orders = $conn->query("SELECT COUNT(*) as cnt FROM orders")->fetch_assoc()['cnt'] ?? 0;

$today = date('Y-m-d');
$today_revenue = $conn->query("SELECT COALESCE(SUM(total), 0) as rev 
                               FROM orders WHERE DATE(created_at) = '$today' AND status IN ('paid','completed')")
                      ->fetch_assoc()['rev'] ?? 0;

$today_orders = $conn->query("SELECT COUNT(*) as cnt FROM orders WHERE DATE(created_at) = '$today'")->fetch_assoc()['cnt'] ?? 0;

$avg_order = $total_orders > 0 ? round($total_revenue / $total_orders, 2) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>All Orders - RestoFlow Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet"/>
    
    <style>
        :root {
            --primary: #F6921E;
            --primary-dark: #E07E0A;
            --dark: #2D3436;
            --light: #FFFFFF;
            --gray-100: #F8F9FA;
            --gray-200: #E9ECEF;
            --shadow: 0 4px 15px rgba(0,0,0,0.08);
            --radius: 16px;
        }

        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #FFF5F0 0%, #F8F9FA 100%);
            color: var(--dark);
            line-height: 1.6;
        }

        .dashboard-container { display: flex; min-height: 100vh; }

        /* Sidebar */
        .sidebar {
            width: 260px;
            background: linear-gradient(180deg, #1F2529 0%, #2D3436 100%);
            color: white;
            position: fixed;
            height: 100vh;
            padding: 24px 16px;
            box-shadow: var(--shadow);
            z-index: 100;
        }
        .sidebar.collapsed { width: 72px; }
        .sidebar.collapsed .nav-text,
        .sidebar.collapsed .logo-text { display: none; }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0 12px 28px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 28px;
        }
        .logo-icon {
            width: 48px; height: 48px;
            background: linear-gradient(135deg, var(--primary), #FFAD87);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        .logo-text { font-size: 1.65rem; font-weight: 700; }

        .nav-menu { flex: 1; display: flex; flex-direction: column; gap: 6px; }
        .nav-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 16px;
            border-radius: 12px;
            color: rgba(255,255,255,0.85);
            text-decoration: none;
        }
        .nav-item:hover, .nav-item.active {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        .nav-item.active { background: linear-gradient(135deg, var(--primary), var(--primary-dark)); }

        .main-content {
            flex: 1;
            margin-left: 260px;
            padding: 32px;
        }
        .main-content.expanded { margin-left: 72px; }

        .top-bar {
            background: white;
            padding: 24px 32px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            margin-bottom: 32px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        .stat-card {
            background: white;
            padding: 24px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            gap: 18px;
        }
        .stat-icon {
            width: 60px; height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            color: white;
        }

        .card {
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 28px;
        }
        .card-header {
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 2px solid #f1f3f5;
        }
        .card-title {
            font-size: 1.65rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .filter-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 28px;
        }
        .filter-group {
            flex: 1;
            min-width: 160px;
        }
        .filter-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #495057;
        }
        .filter-input, .filter-select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
        }
        .filter-input:focus, .filter-select:focus {
            border-color: var(--primary);
            outline: none;
        }
        .btn-filter {
            padding: 12px 28px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
        }

        /* Responsive Table - No Horizontal Scroll */
        .table-container {
            overflow-x: auto;
            border-radius: 12px;
            border: 1px solid #e9ecef;
        }
        table {
            width: 100%;
            min-width: 800px;           /* Prevents too much squeezing */
            border-collapse: collapse;
        }
        th, td {
            padding: 16px 14px;
            text-align: left;
            border-bottom: 1px solid #f1f3f5;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #495057;
            text-transform: uppercase;
            font-size: 0.85rem;
        }
        tr:hover { background: #fffaf0; }

        .token-big {
            font-size: 2.2rem;
            font-weight: 900;
            color: var(--primary);
        }
        .status-badge {
            padding: 6px 16px;
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: 600;
        }
        .status-pending  { background: #fff4e6; color: #e67e22; }
        .status-paid     { background: #e6f7f4; color: #00b894; }
        .status-completed{ background: #e3f2fd; color: #1976d2; }
        .status-cancelled{ background: #ffebee; color: #d32f2f; }

        .empty-state {
            text-align: center;
            padding: 100px 20px;
            color: #6c757d;
        }
        .empty-icon { font-size: 6rem; color: #ddd; margin-bottom: 20px; }

        /* Notification */
        .notification {
            position: fixed; top: 30px; right: 30px; z-index: 2000;
            padding: 16px 24px;
            border-radius: 12px;
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 320px;
            transform: translateX(150%);
            transition: all 0.4s ease;
        }
        .notification.show { transform: translateX(0); }

        @media (max-width: 992px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 20px; }
        }

        @media (max-width: 768px) {
            .stats-grid { grid-template-columns: 1fr; }
            .filter-bar { flex-direction: column; }
            table { min-width: 700px; }
        }
    </style>
</head>
<body>

<div class="dashboard-container">

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="logo">
            <div class="logo-icon"><i class="fas fa-store"></i></div>
            <span class="logo-text">RestoFlow</span>
        </div>

        <nav class="nav-menu">
            <a href="dashboard.php" class="nav-item"><i class="fas fa-tachometer-alt"></i><span class="nav-text">Dashboard</span></a>
            <a href="admin_shops.php" class="nav-item"><i class="fas fa-shop"></i><span class="nav-text">Manage Shops</span></a>
            <a href="admin_orders.php" class="nav-item active"><i class="fas fa-clipboard-list"></i><span class="nav-text">All Orders</span></a>
            <a href="admin_users.php" class="nav-item"><i class="fas fa-users"></i><span class="nav-text">Users & Shops</span></a>
            <a href="admin_analytics.php" class="nav-item"><i class="fas fa-chart-line"></i><span class="nav-text">Analytics</span></a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-content" id="mainContent">

        <div class="top-bar">
            <div>
                <h1 style="font-size: 2rem; font-weight: 700;">All Orders (All Shops)</h1>
                <p style="color: #6c757d;">Platform-wide order overview</p>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #74b9ff, #0984e3);"><i class="fas fa-receipt"></i></div>
                <div>
                    <h3 style="font-size: 2.3rem; font-weight: 700;"><?= number_format($total_orders) ?></h3>
                    <p>Total Orders</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #00b894, #00a085);"><i class="fas fa-rupee-sign"></i></div>
                <div>
                    <h3 style="font-size: 2.3rem; font-weight: 700;">₹<?= number_format($total_revenue, 2) ?></h3>
                    <p>Total Revenue</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #fdcb6e, #f39c12);"><i class="fas fa-calendar-day"></i></div>
                <div>
                    <h3 style="font-size: 2.3rem; font-weight: 700;">₹<?= number_format($today_revenue, 2) ?></h3>
                    <p>Today's Revenue</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, var(--primary), #FFAD87);"><i class="fas fa-chart-line"></i></div>
                <div>
                    <h3 style="font-size: 2.3rem; font-weight: 700;">₹<?= number_format($avg_order, 2) ?></h3>
                    <p>Avg. Order Value</p>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><i class="fas fa-filter"></i> Filter Orders</h2>
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
                    <input type="text" name="search" class="filter-input" placeholder="Order ID, Shop, Token..." value="<?= htmlspecialchars($search) ?>">
                </div>

                <button type="submit" class="btn-filter">Apply Filter</button>
            </form>
        </div>

        <!-- Orders Table -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><i class="fas fa-list"></i> Recent Orders (<?= count($orders) ?>)</h2>
            </div>

            <?php if (empty($orders)): ?>
                <div class="empty-state">
                    <div class="empty-icon"><i class="fas fa-receipt"></i></div>
                    <h3>No orders found</h3>
                    <p>Try adjusting your filters</p>
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
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><strong>#<?= $order['order_id'] ?></strong></td>
                                    <td><?= htmlspecialchars($order['shop_name']) ?></td>
                                    <td style="text-align: center;">
                                        <?php if (!empty($order['token']) && $order['token'] !== '0'): ?>
                                            <div class="token-big"><?= htmlspecialchars($order['token']) ?></div>
                                        <?php elseif (!empty($order['table_no'])): ?>
                                            <div style="font-size: 2rem; font-weight: 800; color: #0984e3;">
                                                <?= htmlspecialchars($order['table_no']) ?>
                                            </div>
                                        <?php else: ?>
                                            <span style="color:#adb5bd;">—</span>
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
// Simple sidebar collapse (optional)
const sidebar = document.getElementById('sidebar');
const mainContent = document.getElementById('mainContent');

let collapsed = false; // You can connect to localStorage if needed

// Notification auto hide
setTimeout(() => {
    const notif = document.getElementById('notification');
    if (notif) notif.classList.remove('show');
}, 5000);
</script>

</body>
</html>