<?php
session_start();

include '../config/db.php';

if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit();
}

// Get transaction details
$transaction_uuid = isset($_GET['transaction_uuid']) ? $_GET['transaction_uuid'] : '';
$order_id = 0;

// Extract order ID from transaction_uuid
if ($transaction_uuid) {
    $parts = explode('_', $transaction_uuid);
    $order_id = isset($parts[1]) ? intval($parts[1]) : 0;
}

// Update order status to Cancelled (payment failed)
if ($order_id) {
    $conn->query("UPDATE orders SET status = 'Cancelled' WHERE order_id = $order_id AND user_id = '{$_SESSION['id']}'");
}

$_SESSION['error_message'] = "Payment failed or was cancelled. Please try again.";
header("Location: orders.php");
exit();
?>