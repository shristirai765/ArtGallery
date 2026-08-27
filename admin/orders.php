<?php
session_start();

if (!isset($_SESSION['id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

include '../config/db.php';

// Get all orders
$orders = $conn->query("
    SELECT 
        o.*,
        u.username as customer_name,
        a.title as artwork_title,
        art.username as artist_name
    FROM orders o
    JOIN users u ON o.user_id = u.id
    JOIN artworks a ON o.artwork_id = a.id
    JOIN users art ON o.artist_id = art.id
    ORDER BY o.order_date DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders | Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --monet-deep: #2c4b5a;
            --monet-gold: #c9a87c;
            --bg: #f5efe9;
            --shadow: 0 12px 28px rgba(44,75,90,.12);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Quicksand', sans-serif; }
        body { background: var(--bg); }

        .container { width: 95%; max-width: 1200px; margin: 20px auto; }

        /* Header - Same as homepage with palette logo */
        .header-nav {
            margin-top: 20px;
            background: linear-gradient(145deg, #e8ddd2, #d6c8bb);
            padding: 20px 35px;
            border-radius: 60px 20px 60px 20px;
            box-shadow: var(--shadow);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 30px;
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

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .page-header h2 { color: var(--monet-deep); }

        .table-container {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        table th {
            background: #f8f5f0;
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
            color: var(--monet-deep);
        }

        table td {
            padding: 12px 15px;
            border-bottom: 1px solid #f0f0f0;
            vertical-align: middle;
        }

        table tr:hover td { background: #faf8f5; }

        .order-status {
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }

        .status-Pending { background: #fff3e0; color: #f39c12; }
        .status-Accepted { background: #e3f2fd; color: #3498db; }
        .status-Completed { background: #e8f5e9; color: #27ae60; }
        .status-Rejected { background: #ffebee; color: #e74c3c; }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #999;
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

        @media (max-width: 768px) {
            .header-nav {
                flex-direction: column;
                gap: 15px;
                align-items: stretch;
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

            table { font-size: 12px; }
            table th, table td { padding: 8px 10px; }
        }
    </style>
</head>
<body>

<div class="container">

    <div class="header-nav">
        <div class="logo">
            <i class="fas fa-palette"></i>
            Monet's Atelier
            <small>Admin Panel</small>
        </div>
        <div class="nav">
            <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="users.php"><i class="fas fa-users"></i> Users</a>
            <a href="artworks.php"><i class="fas fa-paint-brush"></i> Artworks</a>
            <a href="orders.php" class="active"><i class="fas fa-box"></i> Orders</a>
            <a href="../logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="page-header">
        <h2><i class="fas fa-box"></i> Manage Orders</h2>
        <span style="color:#888;font-size:14px;">Total: <?php echo $orders->num_rows; ?> orders</span>
    </div>

    <div class="table-container">
        <?php if ($orders->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Artist</th>
                        <th>Artwork</th>
                        <th>Total</th>
                        <th>Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($order = $orders->fetch_assoc()): ?>
                        <tr>
                            <td><strong>#<?php echo $order['order_id']; ?></strong></td>
                            <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                            <td><?php echo htmlspecialchars($order['artist_name']); ?></td>
                            <td><?php echo htmlspecialchars($order['artwork_title']); ?></td>
                            <td>Rs <?php echo number_format($order['total_price'], 2); ?></td>
                            <td><?php echo date('M j, Y', strtotime($order['order_date'])); ?></td>
                            <td>
                                <span class="order-status status-<?php echo $order['status']; ?>">
                                    <?php echo $order['status']; ?>
                                </span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-data">No orders found.</div>
        <?php endif; ?>
    </div>

    <!-- FOOTER -->
    <footer>
        <i class="fas fa-seedling"></i>
        Inspired by Monet • Admin Panel • Manage with Ease
    </footer>

</div>

</body>
</html>