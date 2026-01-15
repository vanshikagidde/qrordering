<?php
session_start();

$data = json_decode(file_get_contents("php://input"), true);

$_SESSION['order'] = [
  'shop_id'  => $data['shop_id'],
  'table_no' => $data['table_no'], // can be NULL
  'cart'     => $data['cart']
];

$_SESSION['order_id'] = time();

header("Content-Type: application/json");
echo json_encode(["success" => true, "message" => "Order saved successfully"]); 
