<?php
include 'connect.php';
if (!isLoggedIn()) { echo json_encode(['success'=>false]); exit(); }

$uid = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD']==='POST' && !empty($_POST['product_ids'])) {
    $payment = $conn->real_escape_string($_POST['payment']??'place');
    $address = $conn->real_escape_string($_POST['address']??'');
    $ids = array_map('intval',(array)$_POST['product_ids']);

    foreach ($ids as $pid) {
        $prod = $conn->query("SELECT * FROM products WHERE id=$pid AND status='approved' AND (order_status IS NULL OR order_status='available')")->fetch_assoc();
        if (!$prod) continue;

        $seller_id = (int)$prod['added_by'];
        $price = (float)$prod['price'];

        $conn->query("INSERT INTO sale_orders (product_id, buyer_id, seller_id, price, payment_method, address, delivery_status) VALUES ($pid, $uid, $seller_id, $price, '$payment', '$address', 'pending')");
        $oid = $conn->insert_id;

        $conn->query("UPDATE products SET order_status='ordered', sold_to=$uid WHERE id=$pid");

        $buyer_name = $conn->real_escape_string($_SESSION['name']??'کریار');
        $pname = $conn->real_escape_string($prod['brand'].' '.$prod['name']);
        $pay_label = $payment==='fib' ? 'FIB' : 'لەشوێن';
        $conn->query("INSERT INTO notifications (user_id, type, title, message, product_id) VALUES ($seller_id, 'order', '🛒 داواکارییەکی نوێ!', '$buyer_name داوای کڕینی $pname کردووە — پارەدان بە $pay_label — \$$price', $pid)");

        $admin = $conn->query("SELECT id FROM users WHERE is_admin=1 LIMIT 1")->fetch_assoc();
        if ($admin) {
            $conn->query("INSERT INTO notifications (user_id, type, title, message, product_id) VALUES ({$admin['id']}, 'order', '💳 داواکارییەکی نوێ', '$buyer_name داوای $pname کردووە بە $pay_label — \$$price', $pid)");
        }
    }

    $_SESSION['cart'] = [];
    echo json_encode(['success'=>true]);
    exit();
}
echo json_encode(['success'=>false]);
