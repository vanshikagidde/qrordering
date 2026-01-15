<?php
session_start();
include "../config/db.php";

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['order'])) {
  die("No order in session");
}

$order  = $_SESSION['order'];
$status = $_GET['status'] ?? '';


if ($status !== 'success') {
  die("Payment failed");
}

$shop_id  = $order['shop_id'];
$table_no = $order['table_no'];

$total = 0;
foreach ($order['cart'] as $menu_id => $qty) {
  $q = mysqli_query($conn, "SELECT price FROM menu WHERE id='$menu_id'");
  $item = mysqli_fetch_assoc($q);
  $total += $item['price'] * $qty;
}

/* =========================
   TOKEN GENERATION (FIXED)
   ========================= */

$q = mysqli_query(
  $conn,
  "SELECT order_token FROM orders
   WHERE shop_id = '$shop_id'
   AND order_token IS NOT NULL
   ORDER BY id DESC
   LIMIT 1"
);

if (!$q) {
  die("Token query error: " . mysqli_error($conn));
}

if (mysqli_num_rows($q) > 0) {
  $last_token = mysqli_fetch_assoc($q)['order_token'];
  $token_number = intval($last_token) + 1;
} else {
  $token_number = 1; // FIRST ORDER FOR THIS SHOP
}

$order_token = str_pad($token_number, 2, "0", STR_PAD_LEFT);

/* =========================
   SAVE ORDER (FIXED)
   ========================= */

if ($table_no) {
  $sql = "INSERT INTO orders (shop_id, table_no, order_token, payment_status, total)
          VALUES ('$shop_id', '$table_no', '$order_token', 'paid', '$total')";
} else {
  $sql = "INSERT INTO orders (shop_id, order_token, payment_status, total)
          VALUES ('$shop_id', '$order_token', 'paid', '$total')";
}

if (!mysqli_query($conn, $sql)) {
  die("Order insert error: " . mysqli_error($conn));
}

$order_id = mysqli_insert_id($conn);

/* =========================
   SAVE ITEMS
   ========================= */

foreach ($order['cart'] as $menu_id => $qty) {
  mysqli_query(
    $conn,
    "INSERT INTO order_items (order_id, menu_id, quantity)
     VALUES ('$order_id', '$menu_id', '$qty')"
  );
}

/* =========================
   CLEAN SESSION
   ========================= */

unset($_SESSION['order']);

/* =========================
   REDIRECT
   ========================= */

header("Location: order_success.php?token=$order_token&shop_id=$shop_id&order_id=$order_id");
exit;
