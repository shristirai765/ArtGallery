<?php
session_start();

if (!isset($_SESSION['id']) || $_SESSION['role'] != 'user') {
    header("Location: ../login.php");
    exit();
}

$order_id = isset($_GET['pid']) ? $_GET['pid'] : '';

// Get the order ID from session if not in URL
if (empty($order_id) && isset($_SESSION['esewa_order_id'])) {
    $order_id = $_SESSION['esewa_order_id'];
}

// Clear session data
unset($_SESSION['esewa_order_id']);
unset($_SESSION['esewa_amount']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Failed | Monet's Atelier</title>
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
            max-width: 500px;
            width: 90%;
        }

        .container .icon {
            font-size: 80px;
            color: #e74c3c;
            margin-bottom: 20px;
        }

        .container h1 {
            color: #e74c3c;
            margin-bottom: 10px;
        }

        .container p {
            color: #666;
            margin-bottom: 20px;
        }

        .container .btn-group {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
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

        .container .order-id {
            color: #888;
            font-size: 14px;
            margin: 10px 0 20px;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="icon">
        <i class="fas fa-times-circle"></i>
    </div>
    <h1>Payment Failed</h1>
    <p>Your payment was not successful. Please try again or use a different payment method.</p>
    <?php if (!empty($order_id)): ?>
        <p class="order-id">Order ID: <?php echo htmlspecialchars($order_id); ?></p>
    <?php endif; ?>

    <div class="btn-group">
        <a href="checkout.php" class="btn btn-primary">
            <i class="fas fa-arrow-left"></i> Try Again
        </a>
        <a href="customer_dashboard.php" class="btn btn-secondary">
            <i class="fas fa-home"></i> Home
        </a>
    </div>
</div>

</body>
</html>