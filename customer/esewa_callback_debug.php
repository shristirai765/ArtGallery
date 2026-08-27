<?php
// Debug version of eSewa callback
session_start();
require_once '../config/db.php';

echo "<h2>eSewa Callback Debug</h2>";

// Show session data
echo "<h3>Session Data:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Show GET data
echo "<h3>GET Data:</h3>";
echo "<pre>";
print_r($_GET);
echo "</pre>";

// Show POST data
echo "<h3>POST Data:</h3>";
echo "<pre>";
print_r($_POST);
echo "</pre>";

// Show REQUEST data
echo "<h3>REQUEST Data:</h3>";
echo "<pre>";
print_r($_REQUEST);
echo "</pre>";

// Try to extract order_id from transaction_uuid
$transaction_uuid = isset($_REQUEST['transaction_uuid']) ? $_REQUEST['transaction_uuid'] : '';
$order_id = 0;

if (!empty($transaction_uuid)) {
    $parts = explode('_', $transaction_uuid);
    if (count($parts) >= 3) {
        $order_id = intval(end($parts));
        echo "<p><strong>Extracted Order ID:</strong> " . $order_id . "</p>";
    }
}

// Check if payment was successful
$response_code = isset($_REQUEST['response_code']) ? $_REQUEST['response_code'] : '';
$status = isset($_REQUEST['status']) ? $_REQUEST['status'] : '';

if ($response_code == 'success' || $status == 'success') {
    echo "<h3 style='color:green;'>✅ PAYMENT SUCCESSFUL</h3>";
    
    // Try to update the order
    if ($order_id > 0) {
        // Get user_id from order
        $check = $conn->query("SELECT user_id FROM orders WHERE id = $order_id");
        if ($check && $check->num_rows > 0) {
            $orderData = $check->fetch_assoc();
            $user_id = $orderData['user_id'];
            
            // Update order
            $conn->query("UPDATE orders SET status = 'Accepted' WHERE id = $order_id");
            echo "<p style='color:green;'>✅ Order #$order_id updated to Accepted</p>";
            
            // Set session
            $_SESSION['id'] = $user_id;
            echo "<p>Session user_id set to: $user_id</p>";
        }
    }
} else {
    echo "<h3 style='color:red;'>❌ PAYMENT FAILED</h3>";
}

echo "<p><a href='orders.php'>Go to Orders</a></p>";
?>