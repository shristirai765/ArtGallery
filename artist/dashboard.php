<?php
session_start();

if (!isset($_SESSION['id']) || $_SESSION['role'] != 'artist') {
    header("Location: ../login.php");
    exit();
}

include '../config/db.php';

$artist_id = $_SESSION['id'];

// Update user's last activity
$update = $conn->prepare("UPDATE users SET last_activity = NOW() WHERE id = ?");
$update->bind_param("i", $artist_id);
$update->execute();

// Get counts for navigation - kept for reference but badges removed
$orderCount = $conn->query("SELECT COUNT(*) total FROM orders WHERE artist_id = $artist_id")->fetch_assoc()['total'] ?? 0;
$messageCount = $conn->query("
    SELECT COUNT(*) AS total
    FROM messages
    WHERE receiver_id = '$artist_id'
    AND is_read = 0
")->fetch_assoc()['total'] ?? 0;
$artworkCount = $conn->query("
    SELECT COUNT(*) AS total
    FROM artworks
    WHERE artist_id = '$artist_id'
")->fetch_assoc()['total'] ?? 0;

// Get statistics
$totalArtworks = $artworkCount;

// Get total views for all artworks
$totalViews = $conn->query("
    SELECT SUM(views) AS total
    FROM artworks
    WHERE artist_id = '$artist_id'
")->fetch_assoc()['total'] ?? 0;

// Get total sales (completed orders)
$totalSalesResult = $conn->query("
    SELECT COUNT(*) AS total
    FROM orders o
    WHERE o.artist_id = '$artist_id' 
    AND o.status = 'Completed'
");
$totalSales = $totalSalesResult ? $totalSalesResult->fetch_assoc()['total'] : 0;

// Get total revenue - Check if total_price column exists
$totalRevenue = 0;
$revenueResult = $conn->query("
    SELECT SUM(total_price) AS total
    FROM orders o
    WHERE o.artist_id = '$artist_id' 
    AND o.status = 'Completed'
");
if ($revenueResult && $revenueResult->num_rows > 0) {
    $revenueData = $revenueResult->fetch_assoc();
    $totalRevenue = $revenueData['total'] ?? 0;
}

// If total_price doesn't exist, try using artwork price
if ($totalRevenue == 0) {
    $revenueResult2 = $conn->query("
        SELECT SUM(a.price) AS total
        FROM orders o
        JOIN artworks a ON o.artwork_id = a.id
        WHERE o.artist_id = '$artist_id' 
        AND o.status = 'Completed'
    ");
    if ($revenueResult2 && $revenueResult2->num_rows > 0) {
        $revenueData2 = $revenueResult2->fetch_assoc();
        $totalRevenue = $revenueData2['total'] ?? 0;
    }
}

// Get pending orders count
$pendingResult = $conn->query("
    SELECT COUNT(*) AS total
    FROM orders o
    WHERE o.artist_id = '$artist_id' 
    AND o.status = 'Pending'
");
$pendingOrders = $pendingResult ? $pendingResult->fetch_assoc()['total'] : 0;

// Get recent orders
$recentOrders = $conn->query("
    SELECT 
        o.*,
        u.username as customer_name,
        a.title as artwork_title
    FROM orders o
    JOIN users u ON o.user_id = u.id
    JOIN artworks a ON o.artwork_id = a.id
    WHERE o.artist_id = '$artist_id'
    ORDER BY o.order_date DESC
    LIMIT 5
");

// Get recent messages
$recentMessages = $conn->query("
    SELECT 
        m.*,
        u.username as sender_name
    FROM messages m
    JOIN users u ON m.sender_id = u.id
    WHERE m.receiver_id = '$artist_id'
    ORDER BY m.created_at DESC
    LIMIT 5
");

// Get artworks with view counts
$artworks = $conn->query("
    SELECT 
        a.*,
        (SELECT COUNT(*) FROM orders o WHERE o.artwork_id = a.id AND o.status != 'Rejected') as order_count
    FROM artworks a
    WHERE a.artist_id = '$artist_id'
    ORDER BY a.id DESC
    LIMIT 6
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Artist Dashboard | ArtHub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --monet-deep: #2c4b5a;
            --monet-gold: #c9a87c;
            --monet-lily: #7fa3a8;
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
            width: 95%;
            max-width: 1400px;
            margin: auto;
        }

        /* HEADER NAVIGATION */
        .header-nav {
            margin-top: 20px;
            background: white;
            padding: 15px 25px;
            border-radius: 20px;
            box-shadow: var(--shadow);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .logo h2 {
            color: var(--monet-deep);
        }

        .logo small {
            color: #7f8c8d;
        }

        .nav {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .nav a {
            text-decoration: none;
            padding: 10px 16px;
            border-radius: 12px;
            color: var(--monet-deep);
            font-weight: 600;
            transition: .3s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 14px;
            position: relative;
        }

        .nav a:hover {
            background: var(--monet-deep);
            color: white;
        }

        .nav .active {
            background: var(--monet-deep);
            color: white;
        }

        /* Badges completely hidden */
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

        /* HERO */
        .hero {
            margin: 30px 0;
            padding: 50px;
            border-radius: 25px;
            background: linear-gradient(145deg, #e8ddd2, #d6c8bb);
            box-shadow: var(--shadow);
        }

        .hero h1 {
            color: var(--monet-deep);
            margin-bottom: 10px;
            font-size: 32px;
        }

        .hero h1 i {
            color: var(--monet-gold);
        }

        .hero p {
            color: #5d6d75;
            line-height: 1.8;
            font-size: 16px;
        }

        .hero-actions {
            display: flex;
            gap: 15px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            background: var(--monet-deep);
            color: white;
            padding: 14px 24px;
            border-radius: 12px;
            font-weight: 600;
            transition: .3s;
        }

        .btn-primary:hover {
            background: #203845;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(44, 75, 90, 0.3);
        }

        .btn-secondary {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            background: #7f8c8d;
            color: white;
            padding: 14px 24px;
            border-radius: 12px;
            font-weight: 600;
            transition: .3s;
        }

        .btn-secondary:hover {
            background: #6b7a7b;
            transform: translateY(-2px);
        }

        /* STATS */
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 35px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 20px;
            text-align: center;
            box-shadow: var(--shadow);
            transition: .3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(44, 75, 90, .15);
        }

        .stat-card i {
            font-size: 2rem;
            color: var(--monet-gold);
            margin-bottom: 10px;
        }

        .stat-card .number {
            font-size: 32px;
            font-weight: bold;
            color: var(--monet-deep);
            display: block;
        }

        .stat-card .label {
            color: #7f8c8d;
            font-size: 14px;
            margin-top: 5px;
        }

        .stat-card.pending .number { color: #f39c12; }
        .stat-card.sales .number { color: #27ae60; }
        .stat-card.revenue .number { color: #2ecc71; }
        .stat-card.views .number { color: #3498db; }

        /* SECTION TITLE */
        .section-title {
            margin: 30px 0 20px;
            color: var(--monet-deep);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Two Column Layout */
        .two-col {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
            margin-bottom: 40px;
        }

        .panel {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: var(--shadow);
        }

        .panel h3 {
            color: var(--monet-deep);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .panel h3 .see-all {
            margin-left: auto;
            font-size: 13px;
            font-weight: 500;
            color: var(--monet-gold);
            text-decoration: none;
        }

        .panel h3 .see-all:hover {
            text-decoration: underline;
        }

        /* Order List */
        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .order-info {
            display: flex;
            flex-direction: column;
        }

        .order-info .order-id {
            font-weight: 600;
            color: var(--monet-deep);
            font-size: 14px;
        }

        .order-info .order-details {
            font-size: 13px;
            color: #7f8c8d;
        }

        .order-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-Pending {
            background: #fff3e0;
            color: #f39c12;
        }

        .status-Accepted {
            background: #e3f2fd;
            color: #3498db;
        }

        .status-Completed {
            background: #e8f5e9;
            color: #27ae60;
        }

        .status-Rejected {
            background: #ffebee;
            color: #e74c3c;
        }

        .order-price {
            font-weight: 600;
            color: var(--monet-gold);
        }

        /* Message List */
        .message-item {
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .message-item:last-child {
            border-bottom: none;
        }

        .message-item .msg-sender {
            font-weight: 600;
            color: var(--monet-deep);
        }

        .message-item .msg-preview {
            font-size: 13px;
            color: #7f8c8d;
            display: block;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .message-item .msg-time {
            font-size: 11px;
            color: #bbb;
            float: right;
        }

        .message-item .unread {
            background: #e74c3c;
            color: white;
            padding: 1px 8px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: bold;
        }

        /* ARTWORK GRID */
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: .3s;
            display: flex;
            flex-direction: column;
        }

        .card:hover {
            transform: translateY(-6px);
            box-shadow: 0 15px 35px rgba(44, 75, 90, .15);
        }

        .card img {
            width: 100%;
            height: 250px;
            object-fit: cover;
            background: #f8f8f8;
        }

        .card .content {
            padding: 20px;
            display: flex;
            flex-direction: column;
            flex: 1;
        }

        .card .content h3 {
            color: var(--monet-deep);
            margin-bottom: 8px;
            font-size: 18px;
        }

        .card .content .description {
            color: #7f8c8d;
            line-height: 1.6;
            font-size: 14px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .card .content .price {
            color: var(--monet-gold);
            font-weight: 700;
            font-size: 1.2rem;
            margin: 12px 0;
        }

        .card .content .meta {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            color: #888;
            margin-bottom: 10px;
            flex-wrap: wrap;
            gap: 5px;
        }

        .card .content .meta span {
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .card .content .meta i {
            color: var(--monet-gold);
        }

        .card .content .meta .views-count {
            color: #3498db;
            font-weight: 600;
        }

        .card .content .meta .orders-count {
            color: #27ae60;
            font-weight: 600;
        }

        .card .content .actions {
            margin-top: 15px;
            display: flex;
            gap: 10px;
            margin-top: auto;
        }

        .card .content .actions a {
            flex: 1;
            text-align: center;
            text-decoration: none;
            color: white;
            padding: 10px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 14px;
            transition: .3s;
        }

        .card .content .actions a:hover {
            transform: translateY(-2px);
        }

        .edit {
            background: #2980b9;
        }

        .edit:hover {
            background: #216694;
        }

        .delete {
            background: #c0392b;
        }

        .delete:hover {
            background: #a5281b;
        }

        /* EMPTY STATE */
        .empty-state {
            background: white;
            padding: 60px;
            text-align: center;
            border-radius: 20px;
            box-shadow: var(--shadow);
            grid-column: 1 / -1;
        }

        .empty-state i {
            font-size: 4rem;
            color: var(--monet-gold);
            margin-bottom: 20px;
            display: block;
        }

        .empty-state h2 {
            margin-bottom: 10px;
            color: var(--monet-deep);
        }

        .empty-state p {
            color: #7f8c8d;
        }

        .empty-state .btn-primary {
            margin-top: 15px;
        }

        .no-items {
            color: #999;
            text-align: center;
            padding: 20px;
        }

        /* Alert Messages */
        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert-success i {
            font-size: 20px;
        }

        /* RESPONSIVE */
        @media (max-width: 992px) {
            .two-col {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .header-nav {
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

            .hero {
                padding: 30px;
                text-align: center;
            }

            .hero h1 {
                font-size: 24px;
            }

            .hero-actions {
                justify-content: center;
            }

            .stats {
                grid-template-columns: repeat(2, 1fr);
            }

            .grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 15px;
            }

            .card img {
                height: 180px;
            }

            .container {
                width: 98%;
            }
        }

        @media (max-width: 480px) {
            .stats {
                grid-template-columns: 1fr;
            }

            .grid {
                grid-template-columns: 1fr;
            }

            .hero-actions {
                flex-direction: column;
            }

            .hero-actions a {
                justify-content: center;
            }
        }
    </style>
</head>
<body>

<div class="container">

    <!-- Success Messages -->
    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            Artwork deleted successfully.
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['updated'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            Artwork updated successfully.
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['added'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            Artwork added successfully.
        </div>
    <?php endif; ?>

    <!-- HEADER NAVIGATION -->
    <div class="header-nav">
        <div class="logo">
            <h2><i class="fas fa-palette"></i> Monet's Atelier</h2>
            <small>Dashboard</small>
        </div>
        <div class="nav">
            <a href="dashboard.php" class="active">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="orders.php">
                <i class="fas fa-box"></i> Orders
            </a>
            <a href="messages.php">
                <i class="fas fa-envelope"></i> Messages
            </a>
            <a href="artworks.php">
                <i class="fas fa-paint-brush"></i> My Artworks
            </a>
            <a href="add_artwork.php">
                <i class="fas fa-plus-circle"></i> Add Artwork
            </a>
            <a href="profile.php">
                <i class="fas fa-user-circle"></i> Profile
            </a>
            <a href="../logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>

    <!-- HERO -->
    <div class="hero">
        <h1>
            <i class="fas fa-hand-peace"></i>
            Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>
        </h1>
        <p>
            Manage your portfolio, upload new artwork,
            and showcase your creativity to collectors worldwide.
        </p>
        <div class="hero-actions">
            <a href="add_artwork.php" class="btn-primary">
                <i class="fas fa-plus"></i> Upload Artwork
            </a>
            <a href="orders.php" class="btn-secondary">
                <i class="fas fa-box"></i> View Orders
            </a>
        </div>
    </div>

    <!-- STATS -->
    <div class="stats">
        <div class="stat-card">
            <i class="fas fa-image"></i>
            <span class="number"><?php echo $totalArtworks; ?></span>
            <span class="label">Total Artworks</span>
        </div>
        <div class="stat-card views">
            <i class="fas fa-eye"></i>
            <span class="number"><?php echo number_format($totalViews); ?></span>
            <span class="label">Total Views</span>
        </div>
        <div class="stat-card pending">
            <i class="fas fa-clock"></i>
            <span class="number"><?php echo $pendingOrders; ?></span>
            <span class="label">Pending Orders</span>
        </div>
        <div class="stat-card sales">
            <i class="fas fa-shopping-cart"></i>
            <span class="number"><?php echo $totalSales; ?></span>
            <span class="label">Total Sales</span>
        </div>
        <div class="stat-card revenue">
            <i class="fas fa-rupee-sign"></i>
            <span class="number">Rs <?php echo number_format($totalRevenue ?? 0, 0); ?></span>
            <span class="label">Total Revenue</span>
        </div>
    </div>

    <!-- RECENT ORDERS & MESSAGES -->
    <div class="two-col">
        <!-- Recent Orders -->
        <div class="panel">
            <h3>
                <i class="fas fa-box"></i> Recent Orders
                <a href="orders.php" class="see-all">See All →</a>
            </h3>
            <?php if ($recentOrders && $recentOrders->num_rows > 0): ?>
                <?php while ($order = $recentOrders->fetch_assoc()): ?>
                    <div class="order-item">
                        <div class="order-info">
                            <span class="order-id">#<?php echo $order['order_id']; ?></span>
                            <span class="order-details">
                                <?php echo htmlspecialchars($order['customer_name']); ?> • 
                                <?php echo htmlspecialchars($order['artwork_title']); ?>
                            </span>
                        </div>
                        <div style="text-align:right;">
                            <span class="order-price">Rs <?php echo number_format($order['total_price'] ?? 0, 2); ?></span>
                            <br>
                            <span class="order-status status-<?php echo $order['status']; ?>">
                                <?php echo $order['status']; ?>
                            </span>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-items">No orders yet</div>
            <?php endif; ?>
        </div>

        <!-- Recent Messages -->
        <div class="panel">
            <h3>
                <i class="fas fa-envelope"></i> Recent Messages
                <a href="messages.php" class="see-all">See All →</a>
            </h3>
            <?php if ($recentMessages && $recentMessages->num_rows > 0): ?>
                <?php while ($msg = $recentMessages->fetch_assoc()): ?>
                    <div class="message-item">
                        <span class="msg-sender">
                            <?php echo htmlspecialchars($msg['sender_name']); ?>
                            <?php if (!$msg['is_read']): ?>
                                <span class="unread">New</span>
                            <?php endif; ?>
                        </span>
                        <span class="msg-time">
                            <?php echo date('M j, g:i A', strtotime($msg['created_at'])); ?>
                        </span>
                        <span class="msg-preview">
                            <?php echo htmlspecialchars(substr($msg['message'], 0, 60)) . (strlen($msg['message']) > 60 ? '...' : ''); ?>
                        </span>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-items">No messages yet</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ARTWORKS SECTION -->
    <h2 class="section-title">
        <i class="fas fa-images"></i> My Artworks
        <a href="artworks.php" style="margin-left:auto;font-size:14px;color:var(--monet-gold);text-decoration:none;">
            View All →
        </a>
    </h2>

    <?php if ($artworks && $artworks->num_rows > 0): ?>
        <div class="grid">
            <?php while ($art = $artworks->fetch_assoc()): ?>
                <div class="card">
                    <?php if (!empty($art['image']) && file_exists("../uploads/" . $art['image'])): ?>
                        <img src="../uploads/<?php echo $art['image']; ?>" alt="<?php echo htmlspecialchars($art['title']); ?>">
                    <?php else: ?>
                        <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='400' height='300'%3E%3Crect width='400' height='300' fill='%23eee'/%3E%3Ctext x='200' y='150' text-anchor='middle' font-family='Arial' font-size='20' fill='%23999'%3ENo Image%3C/text%3E%3C/svg%3E" alt="No image">
                    <?php endif; ?>
                    <div class="content">
                        <h3><?php echo htmlspecialchars($art['title']); ?></h3>
                        <p class="description">
                            <?php echo htmlspecialchars(substr($art['description'] ?? '', 0, 80)) . (strlen($art['description'] ?? '') > 80 ? '...' : ''); ?>
                        </p>
                        <div class="price">Rs <?php echo number_format($art['price'] ?? 0, 2); ?></div>
                        <div class="meta">
                            <span><i class="fas fa-paint-brush"></i> <?php echo htmlspecialchars($art['medium'] ?? 'N/A'); ?></span>
                            <span><i class="fas fa-tag"></i> <?php echo htmlspecialchars($art['genre'] ?? 'N/A'); ?></span>
                            <span class="views-count"><i class="fas fa-eye"></i> <?php echo number_format($art['views'] ?? 0); ?> views</span>
                            <span class="orders-count"><i class="fas fa-shopping-bag"></i> <?php echo $art['order_count'] ?? 0; ?> orders</span>
                        </div>
                        <div class="actions">
                            <a href="edit_artwork.php?id=<?php echo $art['id']; ?>" class="edit">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="delete_artwork.php?id=<?php echo $art['id']; ?>" class="delete" onclick="return confirm('Delete this artwork?')">
                                <i class="fas fa-trash"></i> Delete
                            </a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-image"></i>
            <h2>No Artworks Yet</h2>
            <p>Start building your portfolio by uploading your first artwork.</p>
            <a href="add_artwork.php" class="btn-primary">
                <i class="fas fa-plus"></i> Upload Artwork
            </a>
        </div>
    <?php endif; ?>

</div>

<script>
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.style.display = 'none';
            }, 500);
        });
    }, 5000);
</script>

</body>
</html>