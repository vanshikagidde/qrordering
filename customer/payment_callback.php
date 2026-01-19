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

$order_id = (int)$order['order_id'];
$shop_id  = (int)$order['shop_id'];
$table_no = $order['table_no'];

/* =========================
   TOKEN GENERATION
   ========================= */

$q = mysqli_query(
  $conn,
  "SELECT token FROM orders
   WHERE shop_id = $shop_id
   AND token IS NOT NULL
   ORDER BY order_id DESC
   LIMIT 1"
);

if (!$q) {
  die("Token query error: " . mysqli_error($conn));
}

if (mysqli_num_rows($q) > 0) {
  $last_token = mysqli_fetch_assoc($q)['token'];
  $token_number = intval($last_token) + 1;
} else {
  $token_number = 1; // FIRST ORDER FOR THIS SHOP
}

$order_token = str_pad($token_number, 2, "0", STR_PAD_LEFT);

/* =========================
   UPDATE ORDER TO PAID
   ========================= */

$update_sql = "UPDATE orders 
               SET token = '$order_token', 
                   status = 'paid' 
               WHERE order_id = $order_id 
               AND shop_id = $shop_id";

if (!mysqli_query($conn, $update_sql)) {
  die("Order update error: " . mysqli_error($conn));
}

if (mysqli_affected_rows($conn) === 0) {
  die("No order found to update");
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