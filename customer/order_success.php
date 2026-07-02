<?php
session_start();

if (!isset($_SESSION['id']) || $_SESSION['role'] != 'user') {
    header("Location: ../login.php");
    exit();
}

include '../config/db.php';

$order_id = isset($_GET['order_id']) ? $_GET['order_id'] : '';
$payment_method = isset($_GET['payment']) ? $_GET['payment'] : 'cod';
$ref_id = isset($_GET['ref']) ? $_GET['ref'] : '';

// Get order details
$order = $conn->query("
    SELECT 
        o.*,
        a.title as artwork_title,
        u.username as artist_name
    FROM orders o
    JOIN artworks a ON o.artwork_id = a.id
    JOIN users u ON o.artist_id = u.id
    WHERE o.order_id = '$order_id' AND o.user_id = '{$_SESSION['id']}'
    LIMIT 1
")->fetch_assoc();

if (!$order) {
    header("Location: dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Success | Monet's Atelier</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --monet-deep: #2c4b5a;
            --monet-gold: #c9a87c;
            --bg: #f5efe9;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Quicksand', sans-serif;
        }

        body {
            background: var(--bg);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        .container {
            background: white;
            padding: 50px;
            border-radius: 25px;
            box-shadow: 0 12px 28px rgba(44,75,90,.12);
            text-align: center;
            max-width: 600px;
            width: 90%;
        }

        .container .icon {
            font-size: 80px;
            color: #27ae60;
            margin-bottom: 20px;
        }

        .container h1 {
            color: var(--monet-deep);
            margin-bottom: 10px;
        }

        .container .order-id {
            color: #888;
            font-size: 16px;
            margin-bottom: 5px;
        }

        .container .payment-method {
            color: var(--monet-gold);
            font-weight: 600;
            margin-bottom: 20px;
        }

        .container .ref-id {
            color: #888;
            font-size: 13px;
            margin-bottom: 15px;
        }

        .container .details {
            text-align: left;
            background: #f8f5f0;
            padding: 20px;
            border-radius: 12px;
            margin: 20px 0;
        }

        .container .details .row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            border-bottom: 1px solid #eee;
        }

        .container .details .row:last-child {
            border-bottom: none;
        }

        .container .details .label {
            color: #888;
        }

        .container .details .value {
            color: var(--monet-deep);
            font-weight: 600;
        }

        .container .btn-group {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 20px;
        }

        .container .btn {
            display: inline-block;
            padding: 14px 30px;
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            transition: .3s;
        }

        .container .btn-primary {
            background: var(--monet-deep);
        }

        .container .btn-primary:hover {
            background: #203845;
            transform: translateY(-2px);
        }

        .container .btn-secondary {
            background: #7f8c8d;
        }

        .container .btn-secondary:hover {
            background: #667273;
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .container {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="icon">
        <i class="fas fa-check-circle"></i>
    </div>
    <h1>Order Placed Successfully!</h1>
    <p class="order-id">Order ID: #<?php echo htmlspecialchars($order_id); ?></p>
    <p class="payment-method">
        <i class="fas fa-credit-card"></i>
        Payment: <?php echo $payment_method == 'esewa' ? 'eSewa' : 'Cash on Delivery'; ?>
    </p>
    <?php if (!empty($ref_id)): ?>
        <p class="ref-id">Reference ID: <?php echo htmlspecialchars($ref_id); ?></p>
    <?php endif; ?>

    <div class="details">
        <div class="row">
            <span class="label">Artwork</span>
            <span class="value"><?php echo htmlspecialchars($order['artwork_title']); ?></span>
        </div>
        <div class="row">
            <span class="label">Artist</span>
            <span class="value"><?php echo htmlspecialchars($order['artist_name']); ?></span>
        </div>
        <div class="row">
            <span class="label">Quantity</span>
            <span class="value"><?php echo $order['quantity']; ?></span>
        </div>
        <div class="row">
            <span class="label">Total</span>
            <span class="value">Rs <?php echo number_format($order['total_price'], 2); ?></span>
        </div>
        <div class="row">
            <span class="label">Status</span>
            <span class="value" style="color:#f39c12;"><?php echo $order['status']; ?></span>
        </div>
    </div>

    <div class="btn-group">
        <a href="orders.php" class="btn btn-primary">
            <i class="fas fa-box"></i> View My Orders
        </a>
        <a href="customer_dashboard.php" class="btn btn-secondary">
            <i class="fas fa-home"></i> Continue Shopping
        </a>
    </div>
</div>

</body>
</html>