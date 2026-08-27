<?php
session_start();

if (!isset($_SESSION['id']) || $_SESSION['role'] != 'user') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

include '../config/db.php';

if (isset($_GET['order_id'])) {
    $order_id = intval($_GET['order_id']);
    $user_id = $_SESSION['id'];
    
    $result = $conn->query("
        SELECT order_id, quantity, address 
        FROM orders 
        WHERE order_id = $order_id 
        AND user_id = '$user_id' 
        AND status = 'Pending'
    ");
    
    if ($result && $result->num_rows > 0) {
        $order = $result->fetch_assoc();
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'order_id' => $order['order_id'],
            'quantity' => $order['quantity'],
            'address' => $order['address'] ? $order['address'] : ''
        ]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Order not found or cannot be edited']);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>