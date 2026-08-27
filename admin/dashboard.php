<?php
session_start();

if (!isset($_SESSION['id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

include '../config/db.php';

$admin_id = $_SESSION['id'];

// Get admin info
$adminInfo = $conn->query("
    SELECT username, email, profile_image 
    FROM users 
    WHERE id = '$admin_id'
")->fetch_assoc();

// Get statistics
$totalUsers = $conn->query("SELECT COUNT(*) as total FROM users")->fetch_assoc()['total'];
$totalArtists = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'artist'")->fetch_assoc()['total'];
$totalCustomers = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'user'")->fetch_assoc()['total'];
$totalArtworks = $conn->query("SELECT COUNT(*) as total FROM artworks")->fetch_assoc()['total'];
$totalOrders = $conn->query("SELECT COUNT(*) as total FROM orders")->fetch_assoc()['total'];
$totalRevenue = $conn->query("SELECT SUM(total_price) as total FROM orders WHERE status = 'Completed'")->fetch_assoc()['total'] ?? 0;

// Get recent orders
$recentOrders = $conn->query("
    SELECT 
        o.*,
        u.username as customer_name,
        a.title as artwork_title
    FROM orders o
    JOIN users u ON o.user_id = u.id
    JOIN artworks a ON o.artwork_id = a.id
    ORDER BY o.order_date DESC
    LIMIT 5
");

// Get recent users
$recentUsers = $conn->query("
    SELECT id, username, email, role, last_activity 
    FROM users 
    ORDER BY id DESC 
    LIMIT 5
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Monet's Atelier</title>
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
            color: var(--monet-deep);
        }

        .container {
            width: 95%;
            max-width: 1400px;
            margin: auto;
        }

        /* HEADER - Same as homepage */
        header {
            margin-top: 20px;
            background: linear-gradient(145deg, #e8ddd2, #d6c8bb);
            padding: 20px 35px;
            border-radius: 60px 20px 60px 20px;
            box-shadow: var(--shadow);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }

        .logo {
            font-size: 2rem;
            font-weight: 700;
            color: var(--monet-deep);
        }

        .logo i {
            color: var(--monet-gold);
        }

        .logo small {
            font-size: 0.8rem;
            font-weight: 400;
            color: #7f8c8d;
            display: block;
        }

        .nav {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .nav a {
            text-decoration: none;
            padding: 10px 18px;
            border-radius: 40px;
            color: var(--monet-deep);
            font-weight: 600;
            transition: .3s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 14px;
            background: rgba(255,255,255,0.5);
        }

        .nav a:hover {
            background: var(--monet-deep);
            color: white;
            transform: translateY(-2px);
        }

        .nav .active {
            background: var(--monet-deep);
            color: white;
        }

        .logout-btn {
            background: #c0392b !important;
            color: white !important;
        }

        .logout-btn:hover {
            background: #a93226 !important;
        }

        /* HERO */
        .hero {
            margin: 30px 0;
            padding: 40px 50px;
            border-radius: 60px 20px 60px 20px;
            background: linear-gradient(145deg, #e8ddd2, #d6c8bb);
            box-shadow: var(--shadow);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }

        .hero-text h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .hero-text h1 i {
            color: var(--monet-gold);
        }

        .hero-text p {
            color: #5d6d75;
            font-size: 1.1rem;
        }

        .hero-icon i {
            font-size: 5rem;
            color: var(--monet-gold);
        }

        /* STATS */
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin-bottom: 35px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 40px 12px 40px 12px;
            text-align: center;
            box-shadow: var(--shadow);
            transition: .3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(44, 75, 90, .15);
        }

        .stat-card i {
            font-size: 2.2rem;
            color: var(--monet-gold);
            margin-bottom: 10px;
        }

        .stat-card .number {
            font-size: 28px;
            font-weight: 700;
            color: var(--monet-deep);
            display: block;
        }

        .stat-card .label {
            color: #7f8c8d;
            font-size: 14px;
            margin-top: 5px;
        }

        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 35px;
        }

        .action-card {
            background: white;
            padding: 25px;
            border-radius: 40px 12px 40px 12px;
            text-align: center;
            box-shadow: var(--shadow);
            text-decoration: none;
            color: var(--monet-deep);
            transition: .3s;
        }

        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(44, 75, 90, .15);
        }

        .action-card i {
            font-size: 2.5rem;
            color: var(--monet-gold);
            margin-bottom: 10px;
            display: block;
        }

        .action-card h4 {
            font-size: 16px;
            margin-bottom: 5px;
        }

        .action-card p {
            font-size: 13px;
            color: #888;
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
            border-radius: 40px 12px 40px 12px;
            padding: 25px;
            box-shadow: var(--shadow);
        }

        .panel h3 {
            color: var(--monet-deep);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.2rem;
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
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .order-info .order-id {
            font-weight: 600;
            color: var(--monet-deep);
            font-size: 13px;
        }

        .order-info .order-details {
            font-size: 13px;
            color: #7f8c8d;
        }

        .order-status {
            padding: 3px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }

        .status-Pending { background: #fff3e0; color: #f39c12; }
        .status-Accepted { background: #e3f2fd; color: #3498db; }
        .status-Completed { background: #e8f5e9; color: #27ae60; }
        .status-Rejected { background: #ffebee; color: #e74c3c; }

        .order-price {
            font-weight: 600;
            color: var(--monet-gold);
            font-size: 14px;
        }

        /* User List */
        .user-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .user-item:last-child {
            border-bottom: none;
        }

        .user-info .username {
            font-weight: 600;
            color: var(--monet-deep);
        }

        .user-info .user-email {
            font-size: 12px;
            color: #888;
        }

        .user-role {
            padding: 3px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }

        .role-admin { background: #ffebee; color: #e74c3c; }
        .role-artist { background: #e3f2fd; color: #3498db; }
        .role-user { background: #e8f5e9; color: #27ae60; }

        .no-items {
            color: #999;
            text-align: center;
            padding: 20px;
        }

        /* Footer */
        footer {
            margin-top: 40px;
            text-align: center;
            padding: 25px;
            border-top: 1px solid #ddd;
            color: #617680;
        }

        footer i {
            color: var(--monet-gold);
        }

        /* Responsive */
        @media (max-width: 992px) {
            .two-col {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            header {
                justify-content: center;
                gap: 15px;
                padding: 20px;
                border-radius: 30px;
            }

            .logo {
                font-size: 1.5rem;
                text-align: center;
            }

            .nav {
                justify-content: center;
            }

            .nav a {
                font-size: 12px;
                padding: 8px 14px;
            }

            .hero {
                flex-direction: column;
                text-align: center;
                padding: 30px;
            }

            .hero-text h1 {
                font-size: 2rem;
            }

            .hero-icon i {
                font-size: 3.5rem;
            }

            .stats {
                grid-template-columns: repeat(2, 1fr);
            }

            .quick-actions {
                grid-template-columns: 1fr 1fr;
            }

            .container {
                width: 98%;
            }
        }

        @media (max-width: 480px) {
            .stats {
                grid-template-columns: 1fr;
            }

            .quick-actions {
                grid-template-columns: 1fr;
            }

            .hero-text h1 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>

<div class="container">

    <!-- HEADER - Same as homepage with palette icon -->
    <header>
        <div class="logo">
            <i class="fas fa-palette"></i>
            Monet's Atelier
            <small>Admin Panel</small>
        </div>
        <div class="nav">
            <a href="dashboard.php" class="active">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="users.php">
                <i class="fas fa-users"></i> Users
            </a>
            <a href="artworks.php">
                <i class="fas fa-paint-brush"></i> Artworks
            </a>
            <a href="orders.php">
                <i class="fas fa-box"></i> Orders
            </a>
            <a href="../logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </header>

    <!-- HERO -->
    <div class="hero">
        <div class="hero-text">
            <h1>
                <i class="fas fa-user-shield"></i>
                Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>
            </h1>
            <p>Manage users, artworks, and orders from one centralized dashboard.</p>
        </div>
        <div class="hero-icon">
            <i class="fas fa-palette"></i>
        </div>
    </div>

    <!-- STATISTICS -->
    <div class="stats">
        <div class="stat-card">
            <i class="fas fa-users"></i>
            <span class="number"><?php echo $totalUsers; ?></span>
            <span class="label">Total Users</span>
        </div>
        <div class="stat-card">
            <i class="fas fa-user-tie"></i>
            <span class="number"><?php echo $totalArtists; ?></span>
            <span class="label">Artists</span>
        </div>
        <div class="stat-card">
            <i class="fas fa-user"></i>
            <span class="number"><?php echo $totalCustomers; ?></span>
            <span class="label">Customers</span>
        </div>
        <div class="stat-card">
            <i class="fas fa-paint-brush"></i>
            <span class="number"><?php echo $totalArtworks; ?></span>
            <span class="label">Artworks</span>
        </div>
        <div class="stat-card">
            <i class="fas fa-shopping-cart"></i>
            <span class="number"><?php echo $totalOrders; ?></span>
            <span class="label">Orders</span>
        </div>
        <div class="stat-card">
            <i class="fas fa-rupee-sign"></i>
            <span class="number">Rs <?php echo number_format($totalRevenue ?? 0, 0); ?></span>
            <span class="label">Total Revenue</span>
        </div>
    </div>

    <!-- QUICK ACTIONS -->
    <div class="quick-actions">
        <a href="users.php" class="action-card">
            <i class="fas fa-user-plus"></i>
            <h4>Manage Users</h4>
            <p>View, edit, or delete users</p>
        </a>
        <a href="artworks.php" class="action-card">
            <i class="fas fa-plus-circle"></i>
            <h4>Manage Artworks</h4>
            <p>View all artworks</p>
        </a>
        <a href="orders.php" class="action-card">
            <i class="fas fa-box"></i>
            <h4>Manage Orders</h4>
            <p>View all orders</p>
        </a>
    </div>

    <!-- RECENT ORDERS & USERS -->
    <div class="two-col">
        <!-- Recent Orders -->
        <div class="panel">
            <h3>
                <i class="fas fa-clock"></i> Recent Orders
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

        <!-- Recent Users -->
        <div class="panel">
            <h3>
                <i class="fas fa-user-plus"></i> Recent Users
                <a href="users.php" class="see-all">See All →</a>
            </h3>
            <?php if ($recentUsers && $recentUsers->num_rows > 0): ?>
                <?php while ($user = $recentUsers->fetch_assoc()): ?>
                    <div class="user-item">
                        <div class="user-info">
                            <span class="username"><?php echo htmlspecialchars($user['username']); ?></span>
                            <br>
                            <span class="user-email"><?php echo htmlspecialchars($user['email']); ?></span>
                        </div>
                        <div>
                            <span class="user-role role-<?php echo $user['role']; ?>">
                                <?php echo ucfirst($user['role']); ?>
                            </span>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-items">No users yet</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- FOOTER -->
    <footer>
        <i class="fas fa-seedling"></i>
        Inspired by Monet • Admin Panel • Manage with Ease
    </footer>

</div>

</body>
</html>