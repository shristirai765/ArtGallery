<?php
session_start();

if (!isset($_SESSION['id']) || $_SESSION['role'] != 'user') {
    header("Location: ../login.php");
    exit();
}

include '../config/db.php';

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
$payment_type = isset($_GET['payment']) ? $_GET['payment'] : 'cod';
$success = isset($_GET['success']) ? $_GET['success'] : 0;

if (!$order_id) {
    header("Location: dashboard.php");
    exit();
}

// Get order details
$order = $conn->query("
    SELECT orders.*, artworks.title, users.username as artist_name
    FROM orders
    JOIN artworks ON orders.artwork_id = artworks.id
    JOIN users ON orders.artist_id = users.id
    WHERE orders.id = $order_id AND orders.user_id = '{$_SESSION['id']}'
")->fetch_assoc();

if (!$order) {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Order Success</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
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
            padding: 50px;
            border-radius: 25px;
            box-shadow: 0 12px 28px rgba(44,75,90,.12);
            text-align: center;
            max-width: 550px;
            width: 100%;
        }
        .success-icon {
            font-size: 80px;
            color: #27ae60;
            margin-bottom: 20px;
        }
        .cod-icon {
            font-size: 80px;
            color: #f39c12;
            margin-bottom: 20px;
        }
        h2 {
            color: #2c4b5a;
            margin-bottom: 10px;
        }
        p {
            color: #666;
            margin-bottom: 10px;
            line-height: 1.6;
        }
        .order-details {
            background: #f8f4f0;
            padding: 20px;
            border-radius: 15px;
            margin: 20px 0;
            text-align: left;
        }
        .order-details .row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .order-details .row:last-child {
            border-bottom: none;
        }
        .order-details .label {
            color: #888;
        }
        .order-details .value {
            color: #2c4b5a;
            font-weight: 600;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: #2c4b5a;
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            transition: .3s;
            margin: 5px;
        }
        .btn:hover {
            background: #203845;
            transform: translateY(-2px);
        }
        .btn-outline {
            background: transparent;
            color: #2c4b5a;
            border: 2px solid #2c4b5a;
        }
        .btn-outline:hover {
            background: #2c4b5a;
            color: white;
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
        <?php if ($payment_type == 'esewa' && $success): ?>
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h2>Payment Successful!</h2>
            <p>Your order has been confirmed and payment has been received.</p>
        <?php elseif ($payment_type == 'cod'): ?>
            <div class="cod-icon">
                <i class="fas fa-truck"></i>
            </div>
            <h2>Order Placed Successfully!</h2>
            <p>Your order has been placed. You will pay when you receive the artwork.</p>
        <?php else: ?>
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h2>Order Placed Successfully!</h2>
            <p>Your order has been placed successfully.</p>
        <?php endif; ?>

       