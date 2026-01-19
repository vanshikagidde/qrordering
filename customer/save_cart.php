<?php
session_start();

include "../config/db.php";

$data = json_decode(file_get_contents("php://input"), true);

$shop_id = (int)$data['shop_id'];
$table_no = $data['table_no']; // can be NULL or 'N/A'
$cart = $data['cart'];

if (empty($cart)) {
    echo json_encode(["success" => false, "message" => "Cart is empty"]);
    exit;
}

// Calculate total and prepare items
$total = 0.00;
$items = [];
foreach ($cart as $item_id => $quantity) {
    $item_id = (int)$item_id;
    $quantity = (int)$quantity;
    if ($quantity <= 0) continue;

    $item_q = mysqli_query($conn, "SELECT item_name, price FROM menu WHERE id = $item_id AND shop_id = $shop_id");
    $item = mysqli_fetch_assoc($item_q);
    if (!$item) {
        echo json_encode(["success" => false, "message" => "Invalid item in cart"]);
        exit;
    }

    $item_total = $item['price'] * $quantity;
    $total += $item_total;

    $items[] = [
        'item_id' => $item_id,
        'item_name' => $item['item_name'],
        'quantity' => $quantity,
        'price' => $item['price'],
        'total_price' => $item_total
    ];
}

if (empty($items)) {
    echo json_encode(["success" => false, "message" => "No valid items in cart"]);
    exit;
}

// Handle table_no: if 'N/A' or non-numeric, set to NULL for int field
$table_no_int = is_numeric($table_no) ? (int)$table_no : null;
$table_no_str = $table_no_int !== null ? (string)$table_no_int : null;

// Insert into orders
$token = bin2hex(random_bytes(16)); // Optional unique token
$insert_order = "INSERT INTO orders (shop_id, table_no, total, token, status) 
                 VALUES ($shop_id, " . ($table_no_str ? "'$table_no_str'" : 'NULL') . ", $total, '$token', 'pending')";

if (!mysqli_query($conn, $insert_order)) {
    echo json_encode(["success" => false, "message" => "Failed to create order: " . mysqli_error($conn)]);
    exit;
}

$order_id = mysqli_insert_id($conn);

// Insert order items
foreach ($items as $item) {
    $insert_item = "INSERT INTO order_item (order_id, shop_id, table_no, item_id, item_name, quantity, price, total_price, status) 
                    VALUES ($order_id, $shop_id, " . ($table_no_int !== null ? $table_no_int : 'NULL') . ", {$item['item_id']}, 
                    '" . mysqli_real_escape_string($conn, $item['item_name']) . "', {$item['quantity']}, {$item['price']}, 
                    {$item['total_price']}, 'pending')";

    if (!mysqli_query($conn, $insert_item)) {
        // Optional: rollback if using transactions
        echo json_encode(["success" => false, "message" => "Failed to add item: " . mysqli_error($conn)]);
        exit;
    }
}

// Save to session
$_SESSION['order'] = [
    'order_id' => $order_id,
    'shop_id'  => $shop_id,
    'table_no' => $table_no,
    'cart'     => $cart
];

header("Content-Type: application/json");
echo json_encode(["success" => true, "message" => "Order saved successfully"]);