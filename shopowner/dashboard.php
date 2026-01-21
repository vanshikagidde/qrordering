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

// Handle AJAX status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    header('Content-Type: application/json');

    $order_id   = (int)($_POST['order_id']   ?? 0);
    $new_status = trim($_POST['new_status'] ?? '');

    $allowed_statuses = ['preparing', 'ready', 'completed', 'cancelled'];

    if ($order_id > 0 && in_array($new_status, $allowed_statuses)) {
        $stmt = $conn->prepare("
            UPDATE orders 
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

// Fetch current live orders
function get_live_orders($conn, $shop_id) {
    $stmt = $conn->prepare("
        SELECT 
            order_id,
            table_no,
            total,
            token,
            status,
            created_at
        FROM orders
        WHERE shop_id = ?
          AND status IN ('pending', 'preparing', 'ready')
        ORDER BY created_at DESC
    ");
    $stmt->bind_param("i", $shop_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $orders = [];
    while ($row = $result->fetch_assoc()) {
        $orders[$row['order_id']] = $row;
        $orders[$row['order_id']]['items'] = [];
    }
    $stmt->close();

    // Load items if we have orders
    if (!empty($orders)) {
        $ids = implode(',', array_map('intval', array_keys($orders)));

        $stmt = $conn->prepare("
            SELECT 
                order_id,
                item_name,
                quantity,
                unit_price,
                total_price,
                notes AS item_notes
            FROM order_items
            WHERE order_id IN ($ids)
            ORDER BY order_id, order_item_id
        ");
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $orders[$row['order_id']]['items'][] = $row;
        }
        $stmt->close();
    }

    return $orders;
}

$live_orders = get_live_orders($conn, $shop_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Live Orders - <?= htmlspecialchars($_SESSION['shop_name'] ?? 'Dashboard') ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <style>
        :root {
            --primary: #ff6b6b;
            --dark: #2d3047;
            --gray: #e2e8f0;
            --light: #f8f9fc;
            --success: #4caf50;
            --warning: #ffb74d;
            --info: #2196f3;
            --danger: #ff5252;
            --shadow: 0 4px 15px rgba(0,0,0,0.08);
        }

        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family: 'Poppins', sans-serif;
            background: var(--light);
            color: #333;
            padding: 20px;
            min-height: 100vh;
        }

        .container { max-width: 1400px; margin: 0 auto; }

        header {
            background: white;
            padding: 20px 30px;
            border-radius: 16px;
            box-shadow: var(--shadow);
            margin-bottom: 35px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        h1 { font-size: 2.1rem; color: var(--dark); font-weight: 700; }

        .btn-logout {
            background: var(--primary);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
        }
        .btn-logout:hover { background: #e65b5b; transform: translateY(-2px); }

        h2.section-title {
            font-size: 1.9rem;
            margin-bottom: 25px;
            color: var(--dark);
        }

        .orders-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 24px;
        }

        .order-card {
            background: white;
            border-radius: 16px;
            box-shadow: var(--shadow);
            padding: 24px;
            transition: transform 0.25s, box-shadow 0.25s;
        }

        .order-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 12px 32px rgba(0,0,0,0.12);
        }

        .order-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .order-token {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
        }

        .order-time {
            font-size: 0.95rem;
            color: #666;
        }

        .status-pill {
            display: inline-block;
            padding: 8px 18px;
            border-radius: 30px;
            font-weight: 600;
            font-size: 0.95rem;
            margin-bottom: 18px;
        }

        .status-pending   { background:#fff3cd; color:#856404; }
        .status-preparing { background:#d1ecf1; color:#0c5460; }
        .status-ready     { background:#d4edda; color:#155724; }
        .status-completed,
        .status-cancelled { background:#e2e3e5; color:#383d41; }

        .meta {
            background: #f8f9fa;
            padding: 14px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 0.96rem;
            line-height: 1.5;
        }

        .items {
            margin: 20px 0;
        }

        .item {
            display: flex;
            justify-content: space-between;
            margin: 10px 0;
            font-size: 0.97rem;
        }

        .item-name   { flex: 3; }
        .item-qty    { flex: 1; text-align:center; }
        .item-amount { flex: 1.5; text-align:right; font-weight:600; }

        .total-row {
            display: flex;
            justify-content: space-between;
            font-size: 1.15rem;
            font-weight: 700;
            border-top: 2px solid var(--gray);
            padding-top: 16px;
            margin-top: 16px;
        }

        .status-control {
            margin-top: 24px;
        }

        select.status-select {
            width: 100%;
            padding: 14px;
            font-size: 1.05rem;
            border: 2px solid var(--gray);
            border-radius: 10px;
            background: white;
            cursor: pointer;
        }

        select.status-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(255,107,107,0.2);
        }

        .empty-state {
            text-align: center;
            padding: 80px 20px;
            color: #777;
            font-size: 1.25rem;
        }

        @media (max-width: 768px) {
            .orders-grid { grid-template-columns: 1fr; }
            header { flex-direction: column; gap: 20px; text-align: center; }
        }
    </style>
</head>
<body>

<div class="container">

    <header>
        <h1><?= htmlspecialchars($_SESSION['shop_name'] ?? 'Your Shop') ?> • Live Orders</h1>
        <button class="btn-logout" onclick="location.href='logout.php'">
            <i class="fas fa-sign-out-alt"></i> Logout
        </button>
    </header>
    <!-- in dashboard.php -->
<a href="menu.php" class="btn btn-primary">
    <i class="fas fa-utensils"></i> Manage Menu
</a>

    <h2 class="section-title">Active Orders</h2>

    <div class="orders-grid" id="live-orders-container">
        <?php if (empty($live_orders)): ?>
            <div class="empty-state">
                <i class="fas fa-coffee" style="font-size:4rem; color:#ddd; margin-bottom:20px;"></i><br>
                No active orders at the moment<br>
                <small>New orders will appear here automatically</small>
            </div>
        <?php else: ?>
            <?php foreach ($live_orders as $order): ?>
                <div class="order-card" data-order-id="<?= $order['order_id'] ?>">
                    <div class="order-top">
                        <div class="order-token">
                            #<?= $order['order_id'] ?>
                            <?php if (!empty($order['token'])): ?>
                                <small>(<?= htmlspecialchars($order['token']) ?>)</small>
                            <?php endif; ?>
                        </div>
                        <div class="order-time">
                            <?= date('h:i A • d M', strtotime($order['created_at'])) ?>
                        </div>
                    </div>

                    <div class="status-pill status-<?= strtolower($order['status']) ?>">
                        <?= ucfirst($order['status']) ?>
                    </div>

                    <div class="meta">
                        <?php if (!empty($order['table_no'])): ?>
                            <div><i class="fas fa-chair"></i> Table <?= htmlspecialchars($order['table_no']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($order['token'])): ?>
                            <div><i class="fas fa-hashtag"></i> Token <?= htmlspecialchars($order['token']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="items">
                        <?php if (!empty($order['items'])): ?>
                            <?php foreach ($order['items'] as $item): ?>
                                <div class="item">
                                    <span class="item-name">
                                        <?= htmlspecialchars($item['item_name']) ?>
                                        <?php if (!empty($item['item_notes'])): ?>
                                            <small style="color:#777;">(<?= htmlspecialchars($item['item_notes']) ?>)</small>
                                        <?php endif; ?>
                                    </span>
                                    <span class="item-qty">× <?= $item['quantity'] ?></span>
                                    <span class="item-amount">₹<?= number_format($item['total_price'], 2) ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div style="color:#999; font-style:italic;">No items recorded</div>
                        <?php endif; ?>
                    </div>

                    <div class="total-row">
                        <span>Total</span>
                        <span>₹<?= number_format($order['total'], 2) ?></span>
                    </div>

                    <div class="status-control">
                        <select class="status-select" onchange="changeStatus(<?= $order['order_id'] ?>, this.value)">
                            <option value="" disabled selected>Change status →</option>
                            <option value="preparing"  <?= $order['status']==='preparing'  ? 'selected' : '' ?>>Preparing</option>
                            <option value="ready"      <?= $order['status']==='ready'      ? 'selected' : '' ?>>Ready to serve</option>
                            <option value="completed"  <?= $order['status']==='completed'  ? 'selected' : '' ?>>Completed</option>
                            <option value="cancelled"  <?= $order['status']==='cancelled'  ? 'selected' : '' ?>>Cancel order</option>
                        </select>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>

<script>
// Simple status update via AJAX
function changeStatus(orderId, newStatus) {
    if (!newStatus) return;

    fetch('', {  // current page (dashboard.php)
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=update_status&order_id=${orderId}&new_status=${encodeURIComponent(newStatus)}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            // Refresh to show updated status + remove completed/cancelled
            window.location.reload();
        } else {
            alert('Failed to update: ' + (data.error || 'server error'));
            // Revert select (optional)
        }
    })
    .catch(err => {
        console.error(err);
        alert('Connection error');
    });
}

// Optional: auto-refresh every 25 seconds
setInterval(() => location.reload(), 25000);
</script>

</body>
</html>