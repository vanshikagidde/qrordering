<?php
// menu.php  (place it inside shopowner folder)
session_start();
include "../config/db.php";

if (!isset($_SESSION['shop_id']) || !isset($_SESSION['logged_in'])) {
    header("Location: login.php");
    exit;
}

$shop_id = (int)$_SESSION['shop_id'];

// =======================================
// AJAX HANDLERS (add / edit / delete)
// =======================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => 'Invalid request'];

    $action = $_POST['action'] ?? '';

    if ($action === 'save_item') {
        $item_id     = (int)($_POST['item_id']     ?? 0);
        $item_name   = trim($_POST['item_name']   ?? '');
        $price       = floatval($_POST['price']    ?? 0);
        $category    = trim($_POST['category']    ?? '');
        $is_available = isset($_POST['is_available']) ? 1 : 0;
        $veg_nonveg  = $_POST['veg_nonveg'] ?? 'veg';

        if ($item_name === '' || $price <= 0) {
            $response['message'] = "Name and valid price are required";
            echo json_encode($response);
            exit;
        }

        if ($item_id > 0) {
            // UPDATE
            $stmt = $conn->prepare("
                UPDATE menu_items 
                SET item_name = ?, price = ?, category = ?, is_available = ?, veg_nonveg = ?
                WHERE id = ? AND shop_id = ?
            ");
            $stmt->bind_param("sdssisi", $item_name, $price, $category, $is_available, $veg_nonveg, $item_id, $shop_id);
        } else {
            // INSERT
            $stmt = $conn->prepare("
                INSERT INTO menu_items 
                (shop_id, item_name, price, category, is_available, veg_nonveg)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("isdssi", $shop_id, $item_name, $price, $category, $is_available, $veg_nonveg);
        }

        if ($stmt->execute()) {
            $response = ['success' => true, 'message' => 'Item saved'];
        } else {
            $response['message'] = "Database error: " . $conn->error;
        }
        $stmt->close();
    }

    elseif ($action === 'delete_item') {
        $item_id = (int)($_POST['item_id'] ?? 0);
        if ($item_id > 0) {
            $stmt = $conn->prepare("DELETE FROM menu_items WHERE id = ? AND shop_id = ?");
            $stmt->bind_param("ii", $item_id, $shop_id);
            if ($stmt->execute()) {
                $response = ['success' => true, 'message' => 'Item deleted'];
            } else {
                $response['message'] = "Delete failed";
            }
            $stmt->close();
        }
    }

    echo json_encode($response);
    exit;
}

// =======================================
// Load current menu items
// =======================================
$stmt = $conn->prepare("
    SELECT id, item_name, price, category, is_available, veg_nonveg
    FROM menu_items
    WHERE shop_id = ?
    ORDER BY category, item_name
");
$stmt->bind_param("i", $shop_id);
$stmt->execute();
$result = $stmt->get_result();
$menu_items = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Menu - <?= htmlspecialchars($_SESSION['shop_name'] ?? 'Shop') ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary: #ff6b6b;
            --dark: #2d3047;
            --gray: #e2e8f0;
            --light: #f8f9fc;
            --success: #28a745;
            --danger: #dc3545;
            --warning: #ffc107;
        }
        body {
            font-family: 'Segoe UI', sans-serif;
            background: var(--light);
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .container { max-width: 1100px; margin: 0 auto; }
        h1, h2 { color: var(--dark); }
        .btn {
            padding: 10px 18px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: 0.2s;
        }
        .btn-primary    { background: var(--primary); color: white; }
        .btn-success    { background: var(--success); color: white; }
        .btn-danger     { background: var(--danger);  color: white; }
        .btn-warning    { background: var(--warning); color: black; }
        .btn:hover      { opacity: 0.92; transform: translateY(-1px); }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            margin-top: 20px;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        th, td { padding: 14px 16px; text-align: left; }
        th { background: var(--dark); color: white; }
        tr:nth-child(even) { background: #f9f9f9; }
        .badge {
            padding: 5px 10px;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .badge-yes { background: #d4edda; color: #155724; }
        .badge-no  { background: #f8d7da; color: #721c24; }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.6);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.25);
        }
        .form-group {
            margin-bottom: 20px;
        }
        label { display: block; margin-bottom: 6px; font-weight: 600; }
        input, select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 1rem;
        }
        .flex-end { display: flex; justify-content: flex-end; gap: 12px; margin-top: 25px; }
    </style>
</head>
<body>

<div class="container">
    <h1>Menu Management</h1>
    <p>Welcome, <?= htmlspecialchars($_SESSION['owner_name'] ?? 'Owner') ?></p>

    <button class="btn btn-success" id="btnAddNew">+ Add New Menu Item</button>

    <?php if (empty($menu_items)): ?>
        <p style="margin-top:30px; color:#666; text-align:center;">
            <i class="fas fa-utensils" style="font-size:2.5rem; opacity:0.4;"></i><br><br>
            No menu items yet. Add your first dish!
        </p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Veg/Non-veg</th>
                    <th>Available</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($menu_items as $item): ?>
                <tr data-id="<?= $item['id'] ?>">
                    <td><?= htmlspecialchars($item['item_name']) ?></td>
                    <td><?= htmlspecialchars($item['category'] ?: '-') ?></td>
                    <td>₹<?= number_format($item['price'], 2) ?></td>
                    <td>
                        <span class="badge <?= $item['veg_nonveg']==='veg' ? 'badge-yes' : 'badge-no' ?>">
                            <?= ucfirst($item['veg_nonveg']) ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge <?= $item['is_available'] ? 'badge-yes' : 'badge-no' ?>">
                            <?= $item['is_available'] ? 'Yes' : 'No' ?>
                        </span>
                    </td>
                    <td>
                        <button class="btn btn-warning btn-sm btn-edit"
                                data-id="<?= $item['id'] ?>"
                                data-name="<?= htmlspecialchars($item['item_name']) ?>"
                                data-price="<?= $item['price'] ?>"
                                data-category="<?= htmlspecialchars($item['category'] ?? '') ?>"
                                data-available="<?= $item['is_available'] ?>"
                                data-vegnonveg="<?= $item['veg_nonveg'] ?>">
                            Edit
                        </button>
                        <button class="btn btn-danger btn-sm btn-delete"
                                data-id="<?= $item['id'] ?>"
                                data-name="<?= htmlspecialchars($item['item_name']) ?>">
                            Delete
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- ──────────────────────────────────────────────
     MODAL - Add / Edit Item
─────────────────────────────────────────────── -->
<div id="itemModal" class="modal">
    <div class="modal-content">
        <h2 id="modalTitle">Add New Menu Item</h2>
        <form id="itemForm">
            <input type="hidden" name="action" value="save_item">
            <input type="hidden" name="item_id" id="item_id" value="">

            <div class="form-group">
                <label>Item Name *</label>
                <input type="text" id="item_name" name="item_name" required>
            </div>

            <div class="form-group">
                <label>Category</label>
                <select id="category" name="category">
                    <option value="">-- Select --</option>
                    <option>Starters</option>
                    <option>Main Course</option>
                    <option>Biryani & Rice</option>
                    <option>Breads</option>
                    <option>Beverages</option>
                    <option>Desserts</option>
                    <option>Others</option>
                </select>
            </div>

            <div class="form-group">
                <label>Price (₹) *</label>
                <input type="number" step="0.01" min="1" id="price" name="price" required>
            </div>

            <div class="form-group">
                <label>Veg / Non-veg *</label>
                <select id="veg_nonveg" name="veg_nonveg" required>
                    <option value="veg">Veg</option>
                    <option value="nonveg">Non-veg</option>
                    <option value="egg">Egg</option>
                </select>
            </div>

            <div class="form-group">
                <label>
                    <input type="checkbox" id="is_available" name="is_available" checked>
                    Available for ordering
                </label>
            </div>

            <div class="flex-end">
                <button type="button" class="btn" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Item</button>
            </div>
        </form>
    </div>
</div>

<!-- ──────────────────────────────────────────────
     DELETE CONFIRMATION MODAL
─────────────────────────────────────────────── -->
<div id="deleteModal" class="modal">
    <div class="modal-content" style="text-align:center; max-width:420px;">
        <h3 style="color:var(--danger);">Delete Item?</h3>
        <p>Are you sure you want to permanently delete<br>
           <strong id="deleteName" style="color:#333;"></strong> ?</p>
        <div class="flex-end" style="justify-content:center; margin-top:25px;">
            <button class="btn" onclick="closeModal('deleteModal')">Cancel</button>
            <button id="btnConfirmDelete" class="btn btn-danger">Yes, Delete</button>
        </div>
    </div>
</div>

<script>
// Modal helpers
function openModal(id)  { document.getElementById(id).style.display = 'flex'; }
function closeModal(id = 'itemModal') { document.getElementById(id).style.display = 'none'; }

// Add new item
document.getElementById('btnAddNew').onclick = () => {
    document.getElementById('modalTitle').textContent = 'Add New Menu Item';
    document.getElementById('itemForm').reset();
    document.getElementById('item_id').value = '';
    document.getElementById('is_available').checked = true;
    openModal('itemModal');
};

// Edit item
document.addEventListener('click', e => {
    if (e.target.classList.contains('btn-edit')) {
        const data = e.target.dataset;
        document.getElementById('modalTitle').textContent = 'Edit Menu Item';
        document.getElementById('item_id').value      = data.id;
        document.getElementById('item_name').value    = data.name;
        document.getElementById('price').value        = data.price;
        document.getElementById('category').value     = data.category;
        document.getElementById('veg_nonveg').value   = data.vegnonveg;
        document.getElementById('is_available').checked = data.available == '1';
        openModal('itemModal');
    }

    if (e.target.classList.contains('btn-delete')) {
        document.getElementById('deleteName').textContent = e.target.dataset.name;
        document.getElementById('btnConfirmDelete').dataset.id = e.target.dataset.id;
        openModal('deleteModal');
    }
});

// Save form (add/edit)
document.getElementById('itemForm').addEventListener('submit', async e => {
    e.preventDefault();
    const formData = new FormData(e.target);

    try {
        const res = await fetch('', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();

        if (data.success) {
            alert(data.message || 'Item saved successfully');
            location.reload();
        } else {
            alert(data.message || 'Something went wrong');
        }
    } catch (err) {
        alert('Network error');
    }
});

// Confirm delete
document.getElementById('btnConfirmDelete').onclick = async function() {
    const id = this.dataset.id;
    try {
        const res = await fetch('', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=delete_item&item_id=${id}`
        });
        const data = await res.json();
        if (data.success) {
            alert('Item deleted');
            location.reload();
        } else {
            alert('Delete failed');
        }
    } catch (err) {
        alert('Network error');
    }
};

// Close modals when clicking outside
window.onclick = function(e) {
    if (e.target.classList.contains('modal')) {
        e.target.style.display = 'none';
    }
};
</script>

</body>
</html>