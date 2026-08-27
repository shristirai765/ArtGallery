<?php
session_start();

include '../config/db.php';

if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit();
}

// Get response from eSewa
$response_code = isset($_GET['response_code']) ? $_GET['response_code'] : '';
$transaction_uuid = isset($_GET['transaction_uuid']) ? $_GET['transaction_uuid'] : '';
$product_code = isset($_GET['product_code']) ? $_GET['product_code'] : '';
$total_amount = isset($_GET['total_amount']) ? $_GET['total_amount'] : '';
$signature = isset($_GET['signature']) ? $_GET['signature'] : '';

// Verify the response
if ($response_code == 'success') {
    // Verify signature (optional but recommended)
    $secret_key = "8gBm/:&EnhH.1/q";
    $signature_string = "transaction_uuid={$transaction_uuid},product_code={$product_code},total_amount={$total_amount}";
    $computed_signature = base64_encode(hash_hmac('sha256', $signature_string, $secret_key, true));
    
    // Extract order ID from transaction_uuid
    $parts = explode('_', $transaction_uuid);
    $order_id = isset($parts[1]) ? intval($parts[1]) : 0;
    
    if ($order_id) {
        // Update order status to Paid
        $conn->query("UPDATE orders SET status = 'Accepted' WHERE order_id = $order_id AND user_id = '{$_SESSION['id']}'");
        
        $_SESSION['success_message'] = "Payment successful! Your order has been confirmed.";
        header("Location: order_success.php?order_id=" . urlencode($order_id) . "&payment=esewa&success=1");
        exit();
    }
}

// If something went wrong
header("Location: orders.php");
exit();
?>