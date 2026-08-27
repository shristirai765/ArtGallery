<?php
session_start();

if (!isset($_SESSION['id']) || $_SESSION['role'] != 'user') {
    header("Location: ../login.php");
    exit();
}

include '../config/db.php';

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if (!$order_id) {
    header("Location: orders.php");
    exit();
}

// Process manual payment
if (isset($_POST['confirm_manual'])) {
    $stmt = $conn->prepare("
        UPDATE orders 
        SET status = 'Accepted' 
        WHERE id = ? AND user_id = ? AND status = 'Pending'
    ");
    $stmt->bind_param("ii", $order_id, $_SESSION['id']);
    $stmt->execute();
    $stmt->close();
    
    $_SESSION['success_message'] = "Order confirmed successfully!";
    header("Location: order_success.php?order_id=" . urlencode($order_id) . "&payment=manual");
    exit();
}

// Get order details
$order = $conn->query("
    SELECT o.*, a.title 
    FROM orders o
    JOIN artworks a ON o.artwork_id = a.id
    WHERE o.id = $order_id AND o.user_id = '{$_SESSION['id']}'
")->fetch_assoc();

if (!$order) {
    header("Location: orders.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manual Payment</title>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Quicksand', sans-serif;
            background: #f5efe9;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 25px;
            box-shadow: 0 12px 28px rgba(44,75,90,.12);
            max-width: 500px;
            width: 100%;
            text-align: center;
        }
        .warning {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: left;
        }
        .warning i {
            color: #ffc107;
            margin-right: 10px;
        }
        .order-details {
            background: #f8f4f0;
            padding: 15px;
            border-radius: 12px;
            margin: 20px 0;
            text-align: left;
        }
        .order-details p {
            margin: 5px 0;
        }
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: .3s;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
        }
        .btn-success {
            background: #27ae60;
            color: white;
        }
        .btn-success:hover {
            background: #219150;
            transform: translateY(-2px);
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }
        .btn-group {
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2><i class="fas fa-exclamation-triangle" style="color: #ffc107;"></i> eSewa Unavailable</h2>
        
        <div class="warning">
            <i class="fas fa-info-circle"></i>
            <strong>eSewa test server is currently unavailable.</strong><br>
            You can manually confirm your order and pay later.
        </div>

        <div class="order-details">
            <p><strong>Order #<?php echo $order['id']; ?></strong></p>
            <p><strong>Artwork:</strong> <?php echo htmlspecialchars($order['title']); ?></p>
            <p><strong>Quantity:</strong> <?php echo $order['quantity']; ?></p>
            <p><strong>Total:</strong> Rs <?php echo number_format($order['total_price'], 2); ?></p>
        </div>

        <form method="POST">
            <div class="btn-group">
                <a href="orders.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Orders
                </a>
                <button type="submit" name="confirm_manual" class="btn btn-success">
                    <i class="fas fa-check"></i> Confirm Order (Pay Later)
                </button>
            </div>
        </form>

        <p style="margin-top: 20px; font-size: 13px; color: #888;">
            <i class="fas fa-credit-card"></i> You can pay via Cash on Delivery or try eSewa again later.
        </p>
    </div>
</body>
</html>