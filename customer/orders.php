<?php
session_start();

if (!isset($_SESSION['id']) || $_SESSION['role'] != 'user') {
    header("Location: ../login.php");
    exit();
}


include '../config/db.php';

$user = $_SESSION['id'];

// Display success/error messages from session
if (isset($_SESSION['success_message'])) {
    $successMessage = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message'])) {
    $errorMessage = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// Handle order cancellation (only for pending orders)
if (isset($_GET['cancel']) && isset($_GET['order_id'])) {
    $order_id = intval($_GET['order_id']);
    
    // Check if order belongs to user and is pending
    $check = $conn->query("
        SELECT id, status 
        FROM orders 
        WHERE id = $order_id AND user_id = '$user' AND status = 'Pending'
    ");
    
    if ($check->num_rows > 0) {
        // Update order status to Cancelled
        $conn->query("UPDATE orders SET status = 'Cancelled' WHERE id = $order_id");
        $cancelMessage = "Order #$order_id has been cancelled successfully.";
    } else {
        $errorMessage = "Order cannot be cancelled. It may have already been processed or doesn't exist.";
    }
}

// Handle order deletion (only for pending orders)
if (isset($_GET['delete']) && isset($_GET['order_id'])) {
    $order_id = intval($_GET['order_id']);
    
    // Check if order belongs to user and is pending
    $check = $conn->query("
        SELECT id, status 
        FROM orders 
        WHERE id = $order_id AND user_id = '$user' AND status = 'Pending'
    ");
    
    if ($check->num_rows > 0) {
        // Delete the order permanently
        $conn->query("DELETE FROM orders WHERE id = $order_id AND user_id = '$user'");
        $deleteMessage = "Order #$order_id has been deleted successfully.";
    } else {
        $errorMessage = "Order cannot be deleted. It may have already been processed or doesn't exist.";
    }
}

// Get orders - EXCLUDE CANCELLED ORDERS from display
$orders = $conn->query("
    SELECT
        orders.*,
        artworks.title,
        artworks.image,
        artworks.price as artwork_price,
        users.username AS artist_name
    FROM orders
    JOIN artworks ON orders.artwork_id = artworks.id
    JOIN users ON orders.artist_id = users.id
    WHERE orders.user_id='$user' AND orders.status != 'Cancelled'
    ORDER BY orders.order_date DESC
");

// Get cancelled orders separately
$cancelledOrders = $conn->query("
    SELECT
        orders.*,
        artworks.title,
        artworks.image,
        artworks.price as artwork_price,
        users.username AS artist_name
    FROM orders
    JOIN artworks ON orders.artwork_id = artworks.id
    JOIN users ON orders.artist_id = users.id
    WHERE orders.user_id='$user' AND orders.status = 'Cancelled'
    ORDER BY orders.order_date DESC
");

// Calculate total spent (excluding cancelled orders) - Only count Accepted and Completed
$totalSpentResult = $conn->query("
    SELECT SUM(total_price) as total 
    FROM orders 
    WHERE user_id='$user' AND status IN ('Accepted', 'Completed')
");
$totalSpent = $totalSpentResult->fetch_assoc()['total'];
$totalSpent = $totalSpent ? $totalSpent : 0;

// If total_spent is in paisa (very large number like 500000), divide by 100
if ($totalSpent > 10000) {
    $totalSpent = $totalSpent / 100;
}

// Function to format amount (handle paisa conversion)
function formatOrderAmount($amount) {
    if ($amount > 10000) {
        $amount = $amount / 100;
    }
    return number_format($amount, 2);
}
?>

<!DOCTYPE html>
<html>
<head>
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
        }

        .container {
            width: 90%;
            max-width: 1400px;
            margin: 20px auto;
        }

        .header {
            background: white;
            padding: 20px 30px;
            border-radius: 25px;
            box-shadow: var(--shadow);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 30px;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--monet-deep);
        }

        .logo i {
            color: var(--monet-gold);
        }

        .nav {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }

        .nav a {
            text-decoration: none;
            color: var(--monet-deep);
            font-weight: 600;
            padding: 10px 16px;
            border-radius: 10px;
            transition: .3s;
        }

        .nav a:hover,
        .nav .active {
            background: var(--monet-deep);
            color: white;
        }

        .logout-btn {
            background: #c0392b;
            color: white !important;
        }

        .logout-btn:hover {
            background: #a93226 !important;
        }

        .page-title {
            color: var(--monet-deep);
            margin-bottom: 25px;
        }

        /* Stats Section */
        .stats-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: var(--shadow);
            text-align: center;
        }

        .stat-card .stat-number {
            font-size: 28px;
            font-weight: 700;
            color: var(--monet-deep);
        }

        .stat-card .stat-label {
            color: #888;
            font-size: 14px;
            margin-top: 5px;
        }

        .stat-card .stat-icon {
            font-size: 24px;
            color: var(--monet-gold);
            margin-bottom: 5px;
        }

        .empty {
            background: white;
            padding: 60px;
            text-align: center;
            border-radius: 20px;
            box-shadow: var(--shadow);
        }

        .empty i {
            font-size: 70px;
            color: var(--monet-gold);
            margin-bottom: 20px;
            display: block;
        }

        .empty h2 {
            color: var(--monet-deep);
            margin-bottom: 10px;
        }

        .empty p {
            color: #888;
        }

        .empty .browse-btn {
            display: inline-block;
            margin-top: 25px;
            text-decoration: none;
            color: white;
            background: var(--monet-deep);
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 600;
            transition: .3s;
        }

        .empty .browse-btn:hover {
            background: #203845;
            transform: translateY(-2px);
        }

        /* Alerts */
        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border-left: 4px solid #17a2b8;
        }

        .order-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            display: flex;
            gap: 25px;
            margin-bottom: 25px;
            box-shadow: 0 8px 20px rgba(0,0,0,.08);
            transition: .3s;
            position: relative;
        }

        .order-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(44,75,90,.15);
        }

        .order-card.cancelled {
            opacity: 0.6;
            background: #f8f9fa;
        }

        .order-card .order-image {
            width: 220px;
            height: 170px;
            object-fit: cover;
            border-radius: 15px;
            flex-shrink: 0;
        }

        .order-card .order-image-placeholder {
            width: 220px;
            height: 170px;
            background: #f0ece8;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ccc;
            font-size: 40px;
            flex-shrink: 0;
        }

        .order-card .details {
            flex: 1;
        }

        .order-card .details h2 {
            color: var(--monet-deep);
            margin-bottom: 10px;
            font-size: 22px;
        }

        .order-card .details .info-row {
            display: flex;
            gap: 30px;
            flex-wrap: wrap;
            margin: 8px 0;
        }

        .order-card .details .info-row p {
            color: #555;
            font-size: 15px;
        }

        .order-card .details .info-row p strong {
            color: var(--monet-deep);
        }

        .order-card .details .info-row p i {
            color: var(--monet-gold);
            margin-right: 5px;
            width: 18px;
        }

        .order-card .details .status {
            display: inline-block;
            margin-top: 12px;
            padding: 8px 20px;
            border-radius: 20px;
            color: white;
            font-weight: 600;
            font-size: 14px;
        }

        .order-card .details .status.pending {
            background: #f39c12;
        }

        .order-card .details .status.accepted {
            background: #27ae60;
        }

        .order-card .details .status.rejected {
            background: #e74c3c;
        }

        .order-card .details .status.completed {
            background: #3498db;
        }

        .order-card .details .status.cancelled {
            background: #95a5a6;
        }

        .order-card .details .order-id {
            font-size: 13px;
            color: #888;
            margin-top: 8px;
        }

        .order-card .details .order-id i {
            color: var(--monet-gold);
        }

        .order-card .details .payment-method {
            display: inline-block;
            margin-top: 8px;
            padding: 4px 15px;
            border-radius: 15px;
            background: #f0f0f0;
            font-size: 12px;
            font-weight: 600;
            color: var(--monet-deep);
        }

        .order-card .details .payment-method i {
            color: var(--monet-gold);
            margin-right: 5px;
        }

        .order-actions {
            margin-top: 15px;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 8px 20px;
            border-radius: 10px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: .3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }

        .btn-primary {
            background: var(--monet-deep);
            color: white;
        }

        .btn-primary:hover {
            background: #203845;
            transform: translateY(-2px);
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
            transform: translateY(-2px);
        }

        .btn-warning {
            background: #ffc107;
            color: #212529;
        }

        .btn-warning:hover {
            background: #e0a800;
            transform: translateY(-2px);
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #218838;
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

        .btn-sm {
            padding: 6px 14px;
            font-size: 13px;
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
        }

        .cancelled-badge {
            display: inline-block;
            background: #dc3545;
            color: white;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            margin-left: 10px;
        }

        .payment-badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            margin-left: 5px;
        }

        .payment-badge.esewa { background: #4CAF50; color: white; }
        .payment-badge.cod { background: #f39c12; color: white; }
        .payment-badge.manual { background: #3498db; color: white; }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
                align-items: stretch;
            }

            .nav {
                justify-content: center;
            }

            .nav a {
                font-size: 12px;
                padding: 8px 12px;
            }

            .order-card {
                flex-direction: column;
                align-items: stretch;
            }

            .order-card .order-image,
            .order-card .order-image-placeholder {
                width: 100%;
                height: 200px;
            }

            .order-card .details .info-row {
                flex-direction: column;
                gap: 5px;
            }

            .container {
                width: 95%;
            }

            .order-actions {
                flex-direction: column;
            }

            .order-actions .btn {
                justify-content: center;
            }

            .stats-section {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 480px) {
            .order-card .details h2 {
                font-size: 18px;
            }
            .stats-section {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success">
        <?php
            echo $_SESSION['success_message'];
            unset($_SESSION['success_message']);
        ?>
    </div>
<?php endif; ?>

<div class="container">

    <div class="header">
        <div class="logo">
            <i class="fas fa-palette"></i>
            Monet's Atelier
        </div>
        <div class="nav">
            <a href="dashboard.php">
                <i class="fas fa-home"></i> Home
            </a>
            <a href="cart.php">
                <i class="fas fa-shopping-cart"></i> Cart
            </a>
            <a href="orders.php" class="active">
                <i class="fas fa-box-open"></i> Orders
            </a>
            <a href="messages.php">
                <i class="fas fa-comments"></i> Messages
            </a>
            <a href="profile.php">
                <i class="fas fa-user-circle"></i> Profile
            </a>
            <a href="../logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>

    <h1 class="page-title">
        <i class="fas fa-box-open"></i>
        My Orders
    </h1>

    <!-- Stats Section -->
    <div class="stats-section">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-shopping-bag"></i></div>
            <div class="stat-number"><?php echo $orders->num_rows + $cancelledOrders->num_rows; ?></div>
            <div class="stat-label">Total Orders</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-check-circle" style="color: #27ae60;"></i></div>
            <div class="stat-number"><?php echo $orders->num_rows; ?></div>
            <div class="stat-label">Active Orders</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-times-circle" style="color: #dc3545;"></i></div>
            <div class="stat-number"><?php echo $cancelledOrders->num_rows; ?></div>
            <div class="stat-label">Cancelled Orders</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-rupee-sign"></i></div>
            <div class="stat-number">Rs <?php echo number_format($totalSpent, 2); ?></div>
            <div class="stat-label">Total Spent</div>
        </div>
    </div>

    <?php if (isset($successMessage)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php echo htmlspecialchars($successMessage); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($deleteMessage)): ?>
        <div class="alert alert-success">
            <i class="fas fa-trash"></i>
            <?php echo htmlspecialchars($deleteMessage); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($errorMessage)): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo htmlspecialchars($errorMessage); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($cancelMessage)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php echo $cancelMessage; ?>
        </div>
    <?php endif; ?>

    <?php if ($orders->num_rows > 0 || $cancelledOrders->num_rows > 0): ?>
        
        <!-- Active Orders -->
        <?php if ($orders->num_rows > 0): ?>
            <h3 style="color: var(--monet-deep); margin-bottom: 15px;">
                <i class="fas fa-check-circle" style="color: #27ae60;"></i> Active Orders
            </h3>
            <?php while ($order = $orders->fetch_assoc()): ?>
                <?php $isPending = strtolower($order['status']) == 'pending'; ?>
                
                <div class="order-card">
                    <?php if (!empty($order['image']) && file_exists("../uploads/" . $order['image'])): ?>
                        <img class="order-image" src="../uploads/<?php echo htmlspecialchars($order['image']); ?>" 
                             alt="<?php echo htmlspecialchars($order['title']); ?>">
                    <?php else: ?>
                        <div class="order-image-placeholder">
                            <i class="fas fa-image"></i>
                        </div>
                    <?php endif; ?>

                    <div class="details">
                        <h2><?php echo htmlspecialchars($order['title']); ?></h2>
                        
                        <div class="info-row">
                            <p><i class="fas fa-user"></i> <strong>Artist:</strong> <?php echo htmlspecialchars($order['artist_name']); ?></p>
                            <p><i class="fas fa-hashtag"></i> <strong>Quantity:</strong> <?php echo $order['quantity']; ?></p>
                            <p><i class="fas fa-rupee-sign"></i> <strong>Total:</strong> 
                                Rs <?php echo formatOrderAmount($order['total_price']); ?>
                            </p>
                        </div>

                        <div class="info-row">
                            <p><i class="fas fa-calendar"></i> <strong>Ordered:</strong> <?php echo date("d M Y", strtotime($order['order_date'])); ?></p>
                            <p><i class="fas fa-clock"></i> <strong>Time:</strong> <?php echo date("h:i A", strtotime($order['order_date'])); ?></p>
                        </div>

                        <div class="order-id">
                            <i class="fas fa-receipt"></i> Order ID: #<?php echo $order['id']; ?>
                        </div>

                        <div class="payment-method">
                            <i class="fas fa-credit-card"></i>
                            <?php 
                            $payment = isset($order['payment_method']) ? $order['payment_method'] : 'cod';
                            if ($payment == 'esewa') {
                                echo 'eSewa <span class="payment-badge esewa">Online</span>';
                            } elseif ($payment == 'manual') {
                                echo 'Pay on Delivery <span class="payment-badge manual">Manual</span>';
                            } else {
                                echo 'Cash on Delivery <span class="payment-badge cod">COD</span>';
                            }
                            ?>
                        </div>

                        <span class="status <?php echo strtolower($order['status']); ?>">
                            <?php echo $order['status']; ?>
                        </span>

                        <?php if ($isPending): ?>
                            <div class="order-actions">
                                <a href="edit_order_form.php?order_id=<?php echo $order['id']; ?>" class="btn btn-warning btn-sm">
                                    <i class="fas fa-edit"></i> Edit Order
                                </a>
                                <a href="?cancel=true&order_id=<?php echo $order['id']; ?>" 
                                class="btn btn-danger btn-sm"
                                onclick="return confirm('Are you sure you want to cancel this order? This action cannot be undone.')">
                                    <i class="fas fa-times"></i> Cancel Order
                                </a>
                                <a href="?delete=true&order_id=<?php echo $order['id']; ?>" 
                                class="btn btn-danger btn-sm"
                                style="background: #dc3545; color: white;"
                                onclick="return confirm('Are you sure you want to DELETE this order? This action cannot be undone.')">
                                    <i class="fas fa-trash"></i> Delete Order
                                </a>
                                <!-- Manual confirm button for eSewa orders -->
                                <?php if (isset($order['payment_method']) && $order['payment_method'] == 'esewa'): ?>
                                <a href="esewa_manual_success.php?order_id=<?php echo $order['id']; ?>" 
                                class="btn btn-success btn-sm"
                                onclick="return confirm('Manually confirm this payment? Only do this if you have completed payment on eSewa.')">
                                    <i class="fas fa-check"></i> Confirm Payment
                                </a>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="order-actions">
                                <span class="btn btn-secondary btn-sm" disabled>
                                    <i class="fas fa-lock"></i> Order Being Processed
                                </span>
                            </div>
                        <?php endif; ?>
        
                    </div>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>

        <!-- Cancelled Orders -->
        <?php if ($cancelledOrders->num_rows > 0): ?>
            <h3 style="color: var(--monet-deep); margin-top: 30px; margin-bottom: 15px;">
                <i class="fas fa-times-circle" style="color: #dc3545;"></i> Cancelled Orders
            </h3>
            <?php while ($order = $cancelledOrders->fetch_assoc()): ?>
                <div class="order-card cancelled">
                    <?php if (!empty($order['image']) && file_exists("../uploads/" . $order['image'])): ?>
                        <img class="order-image" src="../uploads/<?php echo htmlspecialchars($order['image']); ?>" 
                             alt="<?php echo htmlspecialchars($order['title']); ?>">
                    <?php else: ?>
                        <div class="order-image-placeholder">
                            <i class="fas fa-image"></i>
                        </div>
                    <?php endif; ?>

                    <div class="details">
                        <h2>
                            <?php echo htmlspecialchars($order['title']); ?>
                            <span class="cancelled-badge">CANCELLED</span>
                        </h2>
                        
                        <div class="info-row">
                            <p><i class="fas fa-user"></i> <strong>Artist:</strong> <?php echo htmlspecialchars($order['artist_name']); ?></p>
                            <p><i class="fas fa-hashtag"></i> <strong>Quantity:</strong> <?php echo $order['quantity']; ?></p>
                            <p><i class="fas fa-rupee-sign"></i> <strong>Total:</strong> 
                                Rs <?php echo formatOrderAmount($order['total_price']); ?>
                            </p>
                        </div>

                        <div class="info-row">
                            <p><i class="fas fa-calendar"></i> <strong>Ordered:</strong> <?php echo date("d M Y", strtotime($order['order_date'])); ?></p>
                            <p><i class="fas fa-clock"></i> <strong>Time:</strong> <?php echo date("h:i A", strtotime($order['order_date'])); ?></p>
                        </div>

                        <div class="order-id">
                            <i class="fas fa-receipt"></i> Order ID: #<?php echo $order['id']; ?>
                        </div>

                        <span class="status cancelled">
                            Cancelled
                        </span>

                        <div class="order-actions">
                            <a href="?delete=true&order_id=<?php echo $order['id']; ?>" 
                               class="btn btn-danger btn-sm"
                               style="background: #dc3545; color: white;"
                               onclick="return confirm('Are you sure you want to DELETE this cancelled order? This action cannot be undone.')">
                                <i class="fas fa-trash"></i> Delete Order
                            </a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>

    <?php else: ?>
        <div class="empty">
            <i class="fas fa-box-open"></i>
            <h2>No orders yet.</h2>
            <p>You haven't placed any orders yet.</p>
            <a href="dashboard.php" class="browse-btn">
                <i class="fas fa-image"></i> Browse Artworks
            </a>
        </div>
    <?php endif; ?>

</div>

</body>
</html>