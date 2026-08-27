<?php
// eSewa Test Callback Handler
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>eSewa Test Callback</h2>";
echo "<pre>";
echo "GET Parameters:\n";
print_r($_GET);
echo "\nPOST Parameters:\n";
print_r($_POST);
echo "</pre>";

// Check if payment was successful
if (isset($_GET['status']) && $_GET['status'] == 'success') {
    echo "<h3 style='color:green;'>✅ Payment Successful!</h3>";
    echo "<p>Transaction completed successfully.</p>";
    echo "<p><a href='orders.php'>View My Orders</a></p>";
} else {
    echo "<h3 style='color:red;'>❌ Payment Failed or Cancelled</h3>";
    echo "<p>Please try again or use Cash on Delivery.</p>";
    echo "<p><a href='dashboard.php'>Return to Dashboard</a></p>";
}
?>