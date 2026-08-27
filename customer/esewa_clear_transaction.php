<?php
// Clear stuck eSewa transactions
require_once '../config/db.php';

session_start();

// Only allow admin or developer to access
if (!isset($_SESSION['id']) || $_SESSION['role'] != 'admin') {
    die("Access denied. Admin only.");
}

echo "<h2>Clear eSewa Transactions</h2>";

if (isset($_POST['clear'])) {
    $conn->query("DELETE FROM esewa_transactions WHERE status = 'pending'");
    echo "<p style='color:green;'>✅ Pending transactions cleared!</p>";
}

if (isset($_POST['clear_all'])) {
    $conn->query("TRUNCATE TABLE esewa_transactions");
    echo "<p style='color:green;'>✅ All transactions cleared!</p>";
}

// Show current transactions
$transactions = $conn->query("SELECT * FROM esewa_transactions ORDER BY id DESC LIMIT 10");
?>

<h3>Recent Transactions (Last 10)</h3>
<table border="1" cellpadding="10">
    <tr>
        <th>ID</th>
        <th>Order ID</th>
        <th>Transaction UUID</th>
        <th>Amount</th>
        <th>Status</th>
        <th>Created</th>
    </tr>
    <?php while ($row = $transactions->fetch_assoc()): ?>
    <tr>
        <td><?php echo $row['id']; ?></td>
        <td><?php echo $row['order_id']; ?></td>
        <td><?php echo substr($row['transaction_uuid'], 0, 30); ?>...</td>
        <td>Rs <?php echo number_format($row['amount'], 2); ?></td>
        <td style="color: <?php echo $row['status'] == 'success' ? 'green' : ($row['status'] == 'failed' ? 'red' : 'orange'); ?>">
            <?php echo $row['status']; ?>
        </td>
        <td><?php echo $row['created_at']; ?></td>
    </tr>
    <?php endwhile; ?>
</table>

<form method="POST">
    <button type="submit" name="clear" onclick="return confirm('Clear pending transactions?')">
        Clear Pending Transactions
    </button>
    <button type="submit" name="clear_all" onclick="return confirm('Clear ALL transactions?')" style="background: red; color: white;">
        Clear All Transactions
    </button>
</form>

<p><a href="orders.php">Back to Orders</a></p>