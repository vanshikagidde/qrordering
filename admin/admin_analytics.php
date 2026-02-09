<?php
// admin_analytics.php - Super Admin Analytics Dashboard
session_start();
include "../config/db.php";

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

// Time range (default: last 30 days)
$days = isset($_GET['days']) ? max(7, min(90, (int)$_GET['days'])) : 30;
$start_date = date('Y-m-d', strtotime("-$days days"));

// 1. Platform-wide stats
$total_revenue_q = $conn->query("SELECT COALESCE(SUM(total), 0) as rev 
                                 FROM orders WHERE status IN ('paid','completed')");
$total_revenue = $total_revenue_q->fetch_assoc()['rev'] ?? 0;

$total_orders_q = $conn->query("SELECT COUNT(*) as cnt FROM orders");
$total_orders = $total_orders_q->fetch_assoc()['cnt'] ?? 0;

$avg_order = $total_orders > 0 ? round($total_revenue / $total_orders, 2) : 0;

$active_shops_q = $conn->query("SELECT COUNT(*) as cnt FROM shops WHERE status = 'active'");
$active_shops = $active_shops_q->fetch_assoc()['cnt'] ?? 0;

$today = date('Y-m-d');
$today_rev_q = $conn->query("SELECT COALESCE(SUM(total), 0) as rev 
                             FROM orders WHERE DATE(created_at) = '$today' 
                               AND status IN ('paid','completed')");
$today_revenue = $today_rev_q->fetch_assoc()['rev'] ?? 0;

$today_orders_q = $conn->query("SELECT COUNT(*) as cnt FROM orders WHERE DATE(created_at) = '$today'");
$today_orders = $today_orders_q->fetch_assoc()['cnt'] ?? 0;

// 2. Daily sales trend (for line chart)
$chart_query = "SELECT DATE(created_at) as date, COALESCE(SUM(total), 0) as daily_rev
                FROM orders 
                WHERE created_at >= '$start_date' 
                  AND status IN ('paid','completed')
                GROUP BY DATE(created_at)
                ORDER BY date ASC";
$chart_data = $conn->query($chart_query)->fetch_all(MYSQLI_ASSOC);

$chart_labels = [];
$chart_values = [];
$current = new DateTime($start_date);
$end = new DateTime();
while ($current <= $end) {
    $date_str = $current->format('Y-m-d');
    $chart_labels[] = $current->format('d M');
    $found = false;
    foreach ($chart_data as $row) {
        if ($row['date'] === $date_str) {
            $chart_values[] = (float)$row['daily_rev'];
            $found = true;
            break;
        }
    }
    if (!$found) $chart_values[] = 0;
    $current->modify('+1 day');
}

// 3. Order status distribution (pie chart)
$status_q = $conn->query("SELECT status, COUNT(*) as cnt 
                          FROM orders 
                          WHERE created_at >= '$start_date'
                          GROUP BY status");
$status_data = $status_q->fetch_all(MYSQLI_ASSOC);

$pie_labels = [];
$pie_values = [];
foreach ($status_data as $row) {
    $pie_labels[] = ucfirst($row['status']);
    $pie_values[] = (int)$row['cnt'];
}

// 4. Top 5 shops by revenue
$top_shops_q = $conn->query("SELECT s.shop_name, COALESCE(SUM(o.total), 0) as rev
                             FROM shops s
                             LEFT JOIN orders o ON o.shop_id = s.id 
                               AND o.status IN ('paid','completed')
                               AND o.created_at >= '$start_date'
                             GROUP BY s.id
                             ORDER BY rev DESC
                             LIMIT 5");
$top_shops = $top_shops_q->fetch_all(MYSQLI_ASSOC);

// 5. Top 10 most ordered items
$top_items_q = $conn->query("SELECT oi.item_name, SUM(oi.quantity) as qty, SUM(oi.total_price) as rev
                             FROM order_item oi
                             JOIN orders o ON o.order_id = oi.order_id
                             WHERE o.created_at >= '$start_date'
                             GROUP BY oi.item_name
                             ORDER BY qty DESC
                             LIMIT 10");
$top_items = $top_items_q->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Analytics - RestoFlow Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet"/>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* === ORANGE THEME ANALYTICS STYLES === */
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

        /* Charts & Cards */
        .card { background: var(--light); border-radius: var(--radius-lg); box-shadow: var(--shadow); padding: 28px; margin-bottom: 40px; border: 1px solid var(--gray-200); }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 28px; padding-bottom: 16px; border-bottom: 2px solid var(--gray-200); }
        .card-title { font-size: 1.6rem; font-weight: 700; color: var(--darker); display: flex; align-items: center; gap: 12px; }
        .card-title i { color: var(--primary); }

        .chart-container { height: 320px; margin-bottom: 20px; }
        .top-list { margin-top: 20px; }
        .top-item { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid var(--gray-200); }
        .top-item:last-child { border-bottom: none; }

        .range-selector { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 24px; }
        .range-btn { padding: 10px 18px; border: 2px solid var(--gray-200); border-radius: 10px; background: white; cursor: pointer; transition: var(--transition); text-decoration: none; color: var(--dark); }
        .range-btn:hover { border-color: var(--primary); color: var(--primary); }
        .range-btn.active { background: var(--primary); color: white; border-color: var(--primary); }

        .empty-state { text-align: center; padding: 80px 20px; color: var(--gray-600); }
        .empty-icon { font-size: 5rem; color: var(--gray-300); margin-bottom: 24px; }

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
            .range-selector { justify-content: center; }
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
            <a href="admin_orders.php" class="nav-item">
                <i class="fas fa-clipboard-list"></i>
                <span class="nav-text">All Orders</span>
            </a>
            <a href="admin_users.php" class="nav-item">
                <i class="fas fa-users"></i>
                <span class="nav-text">Users & Shops</span>
            </a>
            <a href="admin_analytics.php" class="nav-item active">
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
                <h1>Platform Analytics</h1>
                <p>Overview of all shops, orders, and performance trends</p>
            </div>
        </div>

        <!-- Time Range Selector -->
        <div class="range-selector">
            <a href="?days=7"   class="range-btn <?= $days == 7   ? 'active' : '' ?>">Last 7 Days</a>
            <a href="?days=30"  class="range-btn <?= $days == 30  ? 'active' : '' ?>">Last 30 Days</a>
            <a href="?days=90"  class="range-btn <?= $days == 90  ? 'active' : '' ?>">Last 90 Days</a>
        </div>

        <!-- Key Metrics -->
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
                <div class="stat-icon" style="background: linear-gradient(135deg, var(--primary), var(--primary-light));">
                    <i class="fas fa-shop"></i>
                </div>
                <div class="stat-info">
                    <h3><?= number_format($active_shops) ?></h3>
                    <p>Active Shops</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, var(--warning), #FDCB6E);">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-info">
                    <h3>₹<?= number_format($avg_order, 2) ?></h3>
                    <p>Avg. Order Value</p>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));">
            <!-- Daily Sales Trend -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="fas fa-chart-line"></i> Daily Sales Trend (Last <?= $days ?> Days)</h2>
                </div>
                <div class="chart-container">
                    <canvas id="salesChart"></canvas>
                </div>
            </div>

            <!-- Order Status Distribution -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="fas fa-chart-pie"></i> Order Status Breakdown</h2>
                </div>
                <div class="chart-container">
                    <canvas id="statusPie"></canvas>
                </div>
            </div>
        </div>

        <!-- Top Shops & Items -->
        <div class="stats-grid">
            <!-- Top Shops -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="fas fa-trophy"></i> Top Shops by Revenue</h2>
                </div>
                <?php if (empty($top_shops)): ?>
                    <div class="empty-state">
                        <div class="empty-icon"><i class="fas fa-shop"></i></div>
                        <h3>No data yet</h3>
                    </div>
                <?php else: ?>
                    <div class="top-list">
                        <?php foreach ($top_shops as $index => $shop): ?>
                            <div class="top-item">
                                <div><strong><?= $index + 1 ?>. <?= htmlspecialchars($shop['shop_name']) ?></strong></div>
                                <div><strong>₹<?= number_format($shop['rev'], 2) ?></strong></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Top Items -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="fas fa-utensils"></i> Top Ordered Items</h2>
                </div>
                <?php if (empty($top_items)): ?>
                    <div class="empty-state">
                        <div class="empty-icon"><i class="fas fa-utensils"></i></div>
                        <h3>No items ordered yet</h3>
                    </div>
                <?php else: ?>
                    <div class="top-list">
                        <?php foreach ($top_items as $index => $item): ?>
                            <div class="top-item">
                                <div><strong><?= $index + 1 ?>. <?= htmlspecialchars($item['item_name']) ?></strong></div>
                                <div><strong><?= $item['qty'] ?> ×</strong> (₹<?= number_format($item['rev'], 2) ?>)</div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </main>
</div>

<!-- Charts.js Scripts -->
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

// === Daily Sales Line Chart ===
const salesCtx = document.getElementById('salesChart').getContext('2d');
new Chart(salesCtx, {
    type: 'line',
    data: {
        labels: <?= json_encode($chart_labels) ?>,
        datasets: [{
            label: 'Revenue (₹)',
            data: <?= json_encode($chart_values) ?>,
            borderColor: '#F6921E',
            backgroundColor: 'rgba(246, 146, 30, 0.15)',
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#F6921E',
            pointBorderWidth: 2,
            pointRadius: 4,
            pointHoverRadius: 8
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: 'rgba(45, 52, 54, 0.9)',
                titleFont: { size: 14 },
                bodyFont: { size: 14 },
                padding: 12,
                callbacks: { label: ctx => `₹${ctx.raw.toLocaleString()}` }
            }
        },
        scales: {
            x: { grid: { display: false } },
            y: { beginAtZero: true, ticks: { callback: v => '₹' + v.toLocaleString() } }
        }
    }
});

// === Status Pie Chart ===
const pieCtx = document.getElementById('statusPie').getContext('2d');
new Chart(pieCtx, {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($pie_labels) ?>,
        datasets: [{
            data: <?= json_encode($pie_values) ?>,
            backgroundColor: ['#FDCB6E', '#74B9FF', '#00B894', '#FF5252'],
            borderWidth: 3,
            borderColor: 'var(--light)'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '65%',
        plugins: {
            legend: { position: 'bottom', labels: { font: { size: 14 }, padding: 20 } },
            tooltip: {
                backgroundColor: 'rgba(45, 52, 54, 0.9)',
                padding: 12,
                callbacks: {
                    label: ctx => `${ctx.label}: ${ctx.raw} orders (${((ctx.raw / <?= $total_orders ?: 1 ?>) * 100).toFixed(1)}%)`
                }
            }
        }
    }
});
</script>

</body>
</html>