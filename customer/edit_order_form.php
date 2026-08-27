<?php
session_start();

if (!isset($_SESSION['id']) || $_SESSION['role'] != 'user') {
    header("Location: ../login.php");
    exit();
}

include '../config/db.php';

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
$user_id = $_SESSION['id'];

if ($order_id == 0) {
    $_SESSION['error_message'] = "Invalid order ID.";
    header("Location: orders.php");
    exit();
}

// Get full order details - using 'id' as the primary key
$result = $conn->query("
    SELECT o.*, a.title, a.price as artwork_price 
    FROM orders o
    JOIN artworks a ON o.artwork_id = a.id
    WHERE o.id = $order_id 
    AND o.user_id = '$user_id'
    AND o.status = 'Pending'
");

if (!$result || $result->num_rows == 0) {
    $_SESSION['error_message'] = "Order not found or cannot be edited.";
    header("Location: orders.php");
    exit();
}

$order = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Order</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --monet-deep: #2c4b5a;
            --monet-gold: #c9a87c;
            --bg: #f5efe9;
            --shadow: 0 12px 28px rgba(44,75,90,.12);
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
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 25px;
            box-shadow: var(--shadow);
            max-width: 600px;
            width: 100%;
        }
        h2 {
            color: var(--monet-deep);
            margin-bottom: 20px;
        }
        h2 i {
            color: var(--monet-gold);
            margin-right: 10px;
        }
        .order-info {
            background: #f8f4f0;
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 20px;
        }
        .order-info p {
            margin: 5px 0;
            color: #555;
        }
        .order-info p strong {
            color: var(--monet-deep);
        }
        .form-group {
            margin-bottom: 18px;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            color: var(--monet-deep);
            margin-bottom: 6px;
            font-size: 14px;
        }
        .form-group label i {
            color: var(--monet-gold);
            margin-right: 5px;
        }
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e8e8e8;
            border-radius: 12px;
            font-size: 15px;
            transition: .3s;
            font-family: 'Quicksand', sans-serif;
        }
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--monet-deep);
            box-shadow: 0 0 0 3px rgba(44, 75, 90, 0.1);
        }
        .form-group textarea {
            height: 80px;
            resize: vertical;
        }
        .form-actions {
            display: flex;
            gap: 12px;
            margin-top: 20px;
        }
        .btn {
            padding: 12px 24px;
            border-radius: 12px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: .3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 15px;
        }
        .btn-primary {
            background: var(--monet-deep);
            color: white;
        }
        .btn-primary:hover {
            background: #203845;
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
        .btn-success {
            background: #27ae60;
            color: white;
        }
        .btn-success:hover {
            background: #219150;
            transform: translateY(-2px);
        }
        .btn-block {
            flex: 1;
            justify-content: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2><i class="fas fa-edit"></i> Edit Order #<?php echo $order['id']; ?></h2>
        
        <div class="order-info">
            <p><strong>Artwork:</strong> <?php echo htmlspecialchars($order['title']); ?></p>
            <p><strong>Price per item:</strong> Rs <?php echo number_format($order['artwork_price'], 2); ?></p>
            <p><strong>Current Total:</strong> Rs <?php echo number_format($order['total_price'], 2); ?></p>
            <p><strong>Status:</strong> <?php echo $order['status']; ?></p>
            <?php if (!empty($order['order_id'])): ?>
                <p><strong>Order Reference:</strong> #<?php echo htmlspecialchars($order['order_id']); ?></p>
            <?php endif; ?>
        </div>

        <form method="POST" action="edit_order.php">
            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
            
            <div class="form-group">
                <label><i class="fas fa-hashtag"></i> Quantity</label>
                <input type="number" name="quantity" value="<?php echo $order['quantity']; ?>" min="1" required>
                <div style="font-size: 13px; color: #888; margin-top: 4px;">
                    Current quantity: <?php echo $order['quantity']; ?>
                </div>
            </div>

            <div class="form-group">
                <label><i class="fas fa-map-pin"></i> Delivery Address</label>
                <textarea name="address" required><?php echo htmlspecialchars($order['address'] ?? ''); ?></textarea>
                <div style="font-size: 13px; color: #888; margin-top: 4px;">
                    Update your delivery address if needed
                </div>
            </div>

            <div class="form-actions">
                <a href="orders.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
                <button type="submit" class="btn btn-success btn-block">
                    <i class="fas fa-save"></i> Update Order
                </button>
            </div>
        </form>
    </div>
</body>
</html>