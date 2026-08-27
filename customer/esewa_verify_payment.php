<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] != 'user') {
    header("Location: ../login.php");
    exit();
}

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
$user_id = $_SESSION['id'];

if (!$order_id) {
    header("Location: orders.php");
    exit();
}

// Check if order exists and is pending
$check = $conn->query("SELECT id, status FROM orders WHERE id = $order_id AND user_id = $user_id AND status = 'Pending'");

if ($check && $check->num_rows > 0) {
    // Update order to Accepted
    $conn->query("UPDATE orders SET status = 'Accepted' WHERE id = $order_id AND user_id = $user_id");
    $_SESSION['success_message'] = "Order #$order_id has been confirmed successfully!";
    header("Location: order_success.php?order_id=" . urlencode($order_id) . "&payment=manual&success=1");
    exit();
} else {
    $_SESSION['error_message'] = "Order not found or already processed.";
    header("Location: orders.php");
    exit();
}
?>