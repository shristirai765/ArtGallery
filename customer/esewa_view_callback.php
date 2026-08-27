<?php
// eSewa Callback Viewer - See what eSewa is sending
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>eSewa Callback Data Viewer</h2>";

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

echo "<h3>Server Variables:</h3>";
echo "<pre>";
print_r($_SERVER);
echo "</pre>";

// Check if it was successful
if (isset($_REQUEST['response_code']) && $_REQUEST['response_code'] == 'success') {
    echo "<h3 style='color:green;'>✅ PAYMENT SUCCESSFUL! (response_code=success)</h3>";
} elseif (isset($_REQUEST['status']) && $_REQUEST['status'] == 'success') {
    echo "<h3 style='color:green;'>✅ PAYMENT SUCCESSFUL! (status=success)</h3>";
} else {
    echo "<h3 style='color:red;'>❌ PAYMENT FAILED or UNKNOWN</h3>";
}

echo "<p><strong>All data received:</strong></p>";
echo "<pre>";
print_r(array_merge($_GET, $_POST));
echo "</pre>";

// Show order info if available
if (isset($_REQUEST['transaction_uuid'])) {
    $uuid = $_REQUEST['transaction_uuid'];
    $parts = explode('_', $uuid);
    if (count($parts) >= 3) {
        $order_id = intval(end($parts));
        echo "<p><strong>Extracted Order ID from transaction_uuid:</strong> " . $order_id . "</p>";
    }
}

echo "<p><a href='orders.php'>Back to Orders</a></p>";
?>