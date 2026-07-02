<?php
session_start();

if (!isset($_SESSION['id']) || $_SESSION['role'] != 'user') {
    header("Location: ../login.php");
    exit();
}

include '../config/db.php';

$user = $_SESSION['id'];

// Cart count
$cartCount = $conn->query("
SELECT COUNT(*) AS total
FROM cart
WHERE user_id='$user'
")->fetch_assoc()['total'];

// Message count
$messageCount = 0;

// Order count
$orderCount = $conn->query("
SELECT COUNT(*) AS total
FROM orders
WHERE user_id='$user'
")->fetch_assoc()['total'];

$orders = $conn->query("
SELECT
    orders.*,
    artworks.title,
    artworks.image,
    users.username AS artist_name
FROM orders
JOIN artworks ON orders.artwork_id = artworks.id
JOIN users ON orders.artist_id = users.id
WHERE orders.user_id='$user'
ORDER BY orders.order_date DESC
");
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

        /* Header */
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

        /* Badge completely hidden */
        .badge {
            display: none !important;
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

        .order-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            display: flex;
            gap: 25px;
            margin-bottom: 25px;
            box-shadow: 0 8px 20px rgba(0,0,0,.08);
            transition: .3s;
        }

        .order-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(44,75,90,.15);
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

        .order-card .details .order-id {
            font-size: 13px;
            color: #888;
            margin-top: 8px;
        }

        .order-card .details .order-id i {
            color: var(--monet-gold);
        }

        /* Responsive */
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
        }

        @media (max-width: 480px) {
            .order-card .details h2 {
                font-size: 18px;
            }
        }
    </style>
</head>
<body>

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
                <i class="fas fa-shopping-cart"></i> My Cart
                <!-- Badge removed -->
            </a>
            <a href="orders.php" class="active">
                <i class="fas fa-box-open"></i> Orders
                <!-- Badge removed -->
            </a>
            <a href="messages.php">
                <i class="fas fa-comments"></i> Messages
                <!-- Badge removed -->
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

    <?php if ($orders->num_rows > 0): ?>
        <?php while ($order = $orders->fetch_assoc()): ?>
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
                        <p><i class="fas fa-rupee-sign"></i> <strong>Total:</strong> Rs <?php echo number_format($order['total_price'], 2); ?></p>
                    </div>

                    <div class="info-row">
                        <p><i class="fas fa-calendar"></i> <strong>Ordered:</strong> <?php echo date("d M Y", strtotime($order['order_date'])); ?></p>
                        <p><i class="fas fa-clock"></i> <strong>Time:</strong> <?php echo date("h:i A", strtotime($order['order_date'])); ?></p>
                    </div>

                    <div class="order-id">
                        <i class="fas fa-receipt"></i> Order ID: #<?php echo $order['order_id']; ?>
                    </div>

                    <span class="status <?php echo strtolower($order['status']); ?>">
                        <?php echo $order['status']; ?>
                    </span>
                </div>
            </div>
        <?php endwhile; ?>
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