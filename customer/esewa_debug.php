<?php
// eSewa Debug - See what eSewa is sending
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>eSewa Callback Debug</h2>";

echo "<h3>GET Parameters:</h3>";
echo "<pre>";
print_r($_GET);
echo "</pre>";

echo "<h3>POST Parameters:</h3>";
echo "<pre>";
print_r($_POST);
echo "</pre>";

echo "<h3>REQUEST Parameters:</h3>";
echo "<pre>";
print_r($_REQUEST);
echo "</pre>";

// Check if it was successful
if (isset($_GET['status']) && $_GET['status'] == 'success' && isset($_GET['response_code']) && $_GET['response_code'] == 'success') {
    echo "<h3 style='color:green;'>✅ PAYMENT SUCCESSFUL!</h3>";
    echo "<p>Order ID: " . (isset($_GET['order_id']) ? $_GET['order_id'] : 'Not set') . "</p>";
    echo "<p>User ID: " . (isset($_GET['user_id']) ? $_GET['user_id'] : 'Not set') . "</p>";
    echo "<p>Transaction UUID: " . (isset($_GET['transaction_uuid']) ? $_GET['transaction_uuid'] : 'Not set') . "</p>";
    echo "<p>Total Amount: " . (isset($_GET['total_amount']) ? $_GET['total_amount'] : 'Not set') . "</p>";
} else {
    echo "<h3 style='color:red;'>❌ PAYMENT FAILED</h3>";
}

echo "<p><a href='orders.php'>Back to Orders</a></p>";
?>