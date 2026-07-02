<?php
session_start();

if (!isset($_SESSION['id']) || $_SESSION['role'] != 'user') {
    header("Location: ../login.php");
    exit();
}

include '../config/db.php';

$order_id = isset($_POST['order_id']) ? $_POST['order_id'] : '';
$amount = isset($_POST['amount']) ? (float)$_POST['amount'] : 0;

if (empty($order_id)) {
    header("Location: cart.php");
    exit();
}

// Check if simulation was successful or failed
if (isset($_POST['simulate_success'])) {
    // Update order status to 'Processing'
    $update = $conn->prepare("
        UPDATE orders 
        SET status = 'Processing' 
        WHERE order_id = ? AND user_id = ?
    ");
    $update->bind_param("si", $order_id, $_SESSION['id']);
    $update->execute();
    
    // Redirect to success page
    header("Location: order_success.php?order_id=" . urlencode($order_id) . "&payment=esewa&sim=1");
    exit();
} elseif (isset($_POST['simulate_failure'])) {
    // Update order status to 'Rejected'
    $update = $conn->prepare("
        UPDATE orders 
        SET status = 'Rejected' 
        WHERE order_id = ? AND user_id = ?
    ");
    $update->bind_param("si", $order_id, $_SESSION['id']);
    $update->execute();
    
    // Redirect to failure page
    header("Location: esewa_failure.php?pid=" . urlencode($order_id));
    exit();
}
?>