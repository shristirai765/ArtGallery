<?php
session_start();

if (!isset($_SESSION['id']) || $_SESSION['role'] != 'user') {
    header("Location: ../login.php");
    exit();
}

include '../config/db.php';

$order_id = isset($_GET['pid']) ? $_GET['pid'] : '';
$ref_id = isset($_GET['refId']) ? $_GET['refId'] : '';
$amt = isset($_GET['amt']) ? $_GET['amt'] : 0;

// Get the order ID from session if not in URL
if (empty($order_id) && isset($_SESSION['esewa_order_id'])) {
    $order_id = $_SESSION['esewa_order_id'];
}

if (!empty($order_id)) {
    // Update order status to 'Processing' (payment confirmed)
    $update = $conn->prepare("
        UPDATE orders 
        SET status = 'Processing' 
        WHERE order_id = ? AND user_id = ?
    ");
    $update->bind_param("si", $order_id, $_SESSION['id']);
    $update->execute();
}

// Clear session data
unset($_SESSION['esewa_order_id']);
unset($_SESSION['esewa_amount']);

// Redirect to success page
header("Location: order_success.php?order_id=" . urlencode($order_id) . "&payment=esewa&ref=" . urlencode($ref_id));
exit();
?>