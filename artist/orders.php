<?php
session_start();

if (!isset($_SESSION['id']) || $_SESSION['role'] != 'artist') {
    header("Location: ../login.php");
    exit();
}

include "../config/db.php";

$loggedUser = (int)$_SESSION['id'];

// Update user's last activity
$update = $conn->prepare("UPDATE users SET last_activity = NOW() WHERE id = ?");
$update->bind_param("i", $loggedUser);
$update->execute();

// Get artist info
$artistInfo = $conn->prepare("SELECT username FROM users WHERE id = ?");
$artistInfo->bind_param("i", $loggedUser);
$artistInfo->execute();
$artistResult = $artistInfo->get_result();
$artist = $artistResult->fetch_assoc();

// Get filter parameters
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build the query for orders with filters
$query = "
    SELECT 
        o.*,
        u.username as customer_name,
        u.email as customer_email,
        a.title as artwork_title,
        a.price as artwork_price,
        a.image as artwork_image
    FROM orders o
    JOIN users u ON o.user_id = u.id
    JOIN artworks a ON o.artwork_id = a.id
    WHERE o.artist_id = ?
";

$params = [$loggedUser];
$types = "i";

// Add status filter
if ($statusFilter !== 'all') {
    $query .= " AND o.status = ?";
    $params[] = ucfirst($statusFilter);
    $types .= "s";
}

// Add search filter
if (!empty($searchTerm)) {
    $query .= " AND (u.username LIKE ? OR a.title LIKE ? OR o.order_id LIKE ?)";
    $searchParam = "%$searchTerm%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= "sss";
}

$query .= " ORDER BY o.order_date DESC";

// Prepare and execute the query
$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$ordersResult = $stmt->get_result();

// Get order statistics
$statsQuery = "
    SELECT 
        COUNT(*) as total_orders,
        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_orders,
        SUM(CASE WHEN status = 'Accepted' THEN 1 ELSE 0 END) as accepted_orders,
        SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed_orders,
        SUM(CASE WHEN status != 'Rejected' AND status != 'Pending' THEN o.total_price ELSE 0 END) as total_revenue
    FROM orders o
    WHERE o.artist_id = ?
";

$statsStmt = $conn->prepare($statsQuery);
$statsStmt->bind_param("i", $loggedUser);
$statsStmt->execute();
$stats = $statsStmt->get_result()->fetch_assoc();

// Process status update
if (isset($_POST['update_status']) && isset($_POST['order_id']) && isset($_POST['new_status'])) {
    $orderId = (int)$_POST['order_id'];
    $newStatus = $_POST['new_status'];
    
    // Verify the order belongs to this artist
    $verifyStmt = $conn->prepare("
        SELECT id FROM orders 
        WHERE id = ? AND artist_id = ?
    ");
    $verifyStmt->bind_param("ii", $orderId, $loggedUser);
    $verifyStmt->execute();
    $verifyResult = $verifyStmt->get_result();
    
    if ($verifyResult->num_rows > 0) {
        $updateStmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $updateStmt->bind_param("si", $newStatus, $orderId);
        $updateStmt->execute();
        
        // Redirect to refresh the page
        header("Location: orders.php?status=$statusFilter&updated=1");
        exit();
    }
}

// Navigation counts - kept for reference but badges removed
$orderCount = $conn->query("SELECT COUNT(*) total FROM orders WHERE artist_id = $loggedUser")->fetch_assoc()['total'];
$messageCount = $conn->query("
    SELECT COUNT(*) total 
    FROM messages 
    WHERE receiver_id = $loggedUser AND is_read = 0
")->fetch_assoc()['total'];
$artworkCount = $conn->query("SELECT COUNT(*) total FROM artworks WHERE artist_id = $loggedUser")->fetch_assoc()['total'];
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders - Artist Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --deep: #2c4b5a;
            --gold: #c9a87c;
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
        }
        
        .container {
            width: 95%;
            max-width: 1200px;
            margin: 20px auto;
        }
        
        .header {
            background: white;
            padding: 18px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 20px;
            box-shadow: 0 10px 25px rgba(0,0,0,.08);
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: var(--deep);
        }
        
        .logo i {
            color: var(--gold);
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
            border-radius: 10px;
            color: var(--deep);
            font-weight: 600;
            transition: .3s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 14px;
        }
        
        .nav a:hover,
        .nav .active {
            background: var(--deep);
            color: white;
        }
        
        /* Badges completely hidden */
        .badge {
            display: none !important;
        }
        
        .logout {
            background: #c0392b;
            color: white!important;
        }
        
        .logout:hover {
            background: #a93226!important;
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,.05);
            transition: .3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,.1);
        }
        
        .stat-card .number {
            font-size: 28px;
            font-weight: bold;
            color: var(--deep);
            display: block;
        }
        
        .stat-card .label {
            font-size: 14px;
            color: #888;
            margin-top: 5px;
        }
        
        .stat-card .icon {
            font-size: 24px;
            color: var(--gold);
            margin-bottom: 10px;
        }
        
        /* Orders Table */
        .orders-container {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 10px 25px rgba(0,0,0,.08);
        }
        
        .orders-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .orders-header h2 {
            color: var(--deep);
            font-size: 24px;
        }
        
        .orders-header h2 i {
            color: var(--gold);
            margin-right: 8px;
        }
        
        .filters {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .filters a {
            text-decoration: none;
            padding: 6px 14px;
            border-radius: 20px;
            color: #666;
            font-weight: 500;
            transition: .3s;
            background: #f5f5f5;
            font-size: 13px;
        }
        
        .filters a:hover {
            background: #e8e8e8;
        }
        
        .filters a.active {
            background: var(--deep);
            color: white;
        }
        
        .search-box {
            display: flex;
            gap: 8px;
        }
        
        .search-box input {
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 20px;
            outline: none;
            font-family: 'Quicksand', sans-serif;
            font-size: 13px;
        }
        
        .search-box input:focus {
            border-color: var(--deep);
        }
        
        .search-box button {
            padding: 8px 16px;
            background: var(--deep);
            color: white;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            transition: .3s;
        }
        
        .search-box button:hover {
            background: #203845;
        }
        
        .table-responsive {
            overflow-x: auto;
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
            color: var(--deep);
            white-space: nowrap;
            font-size: 13px;
        }
        
        table td {
            padding: 12px 15px;
            border-bottom: 1px solid #f0f0f0;
            vertical-align: middle;
        }
        
        table tr:hover td {
            background: #faf8f5;
        }
        
        .order-id {
            font-weight: 600;
            color: var(--deep);
            font-size: 13px;
        }
        
        .customer-info {
            display: flex;
            flex-direction: column;
        }
        
        .customer-name {
            font-weight: 500;
            color: var(--deep);
        }
        
        .customer-email {
            font-size: 12px;
            color: #888;
        }
        
        .artwork-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .artwork-thumb {
            width: 45px;
            height: 45px;
            object-fit: cover;
            border-radius: 8px;
            background: #f5f5f5;
        }
        
        .artwork-title {
            font-weight: 500;
            color: var(--deep);
        }
        
        .status-badge {
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
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
        
        .status-select {
            padding: 5px 10px;
            border-radius: 20px;
            border: 1px solid #ddd;
            font-family: 'Quicksand', sans-serif;
            font-size: 12px;
            cursor: pointer;
            background: white;
            outline: none;
        }
        
        .status-select:focus {
            border-color: var(--deep);
        }
        
        .update-btn {
            padding: 5px 12px;
            background: var(--deep);
            color: white;
            border: none;
            border-radius: 15px;
            cursor: pointer;
            font-size: 12px;
            transition: .3s;
            font-family: 'Quicksand', sans-serif;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .update-btn:hover {
            background: #203845;
        }
        
        .price {
            font-weight: 600;
            color: var(--gold);
        }
        
        .date {
            font-size: 12px;
            color: #888;
        }
        
        .no-orders {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        
        .no-orders i {
            font-size: 60px;
            color: var(--gold);
            margin-bottom: 20px;
            display: block;
        }
        
        .no-orders h3 {
            color: #666;
            margin-bottom: 10px;
        }
        
        .no-orders p {
            color: #aaa;
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 12px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: none;
            align-items: center;
            gap: 10px;
            border-left: 4px solid #28a745;
        }
        
        .success-message.show {
            display: flex;
        }
        
        .action-cell {
            min-width: 170px;
        }
        
        .quantity {
            font-weight: 600;
            color: var(--deep);
        }
        
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
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .orders-header {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filters {
                justify-content: center;
            }
            
            .search-box {
                width: 100%;
            }
            
            .search-box input {
                flex: 1;
            }
            
            table {
                font-size: 12px;
            }
            
            table th,
            table td {
                padding: 8px 10px;
            }
            
            .artwork-thumb {
                width: 35px;
                height: 35px;
            }
            
            .action-cell {
                min-width: 150px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="logo">
                <i class="fas fa-paint-brush"></i> Monet's Atelier
            </div>
            <div class="nav">
                <a href="dashboard.php">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="orders.php" class="active">
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
                <a href="../logout.php" class="logout">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
        
        <!-- Success Message -->
        <div id="successMessage" class="success-message <?php echo isset($_GET['updated']) ? 'show' : ''; ?>">
            <i class="fas fa-check-circle"></i> Order status updated successfully!
        </div>
        
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="icon"><i class="fas fa-shopping-bag"></i></div>
                <span class="number"><?php echo $stats['total_orders'] ?? 0; ?></span>
                <span class="label">Total Orders</span>
            </div>
            <div class="stat-card">
                <div class="icon"><i class="fas fa-clock"></i></div>
                <span class="number" style="color:#f39c12;"><?php echo $stats['pending_orders'] ?? 0; ?></span>
                <span class="label">Pending</span>
            </div>
            <div class="stat-card">
                <div class="icon"><i class="fas fa-check-circle"></i></div>
                <span class="number" style="color:#27ae60;"><?php echo $stats['completed_orders'] ?? 0; ?></span>
                <span class="label">Completed</span>
            </div>
            <div class="stat-card">
                <div class="icon"><i class="fas fa-rupee-sign"></i></div>
                <span class="number" style="color:#2ecc71;">Rs <?php echo number_format($stats['total_revenue'] ?? 0, 0); ?></span>
                <span class="label">Total Revenue</span>
            </div>
        </div>
        
        <!-- Orders Table -->
        <div class="orders-container">
            <div class="orders-header">
                <h2><i class="fas fa-list"></i> Orders</h2>
                <div class="filters">
                    <a href="orders.php?status=all" class="<?php echo $statusFilter == 'all' ? 'active' : ''; ?>">All</a>
                    <a href="orders.php?status=pending" class="<?php echo $statusFilter == 'pending' ? 'active' : ''; ?>">Pending</a>
                    <a href="orders.php?status=accepted" class="<?php echo $statusFilter == 'accepted' ? 'active' : ''; ?>">Accepted</a>
                    <a href="orders.php?status=completed" class="<?php echo $statusFilter == 'completed' ? 'active' : ''; ?>">Completed</a>
                    <a href="orders.php?status=rejected" class="<?php echo $statusFilter == 'rejected' ? 'active' : ''; ?>">Rejected</a>
                </div>
                <form class="search-box" method="GET">
                    <input type="hidden" name="status" value="<?php echo $statusFilter; ?>">
                    <input type="text" name="search" placeholder="Search orders..." value="<?php echo htmlspecialchars($searchTerm); ?>">
                    <button type="submit"><i class="fas fa-search"></i></button>
                </form>
            </div>
            
            <div class="table-responsive">
                <?php if ($ordersResult->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Artwork</th>
                                <th>Qty</th>
                                <th>Total</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($order = $ordersResult->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <span class="order-id">#<?php echo $order['order_id']; ?></span>
                                    </td>
                                    <td>
                                        <div class="customer-info">
                                            <span class="customer-name"><?php echo htmlspecialchars($order['customer_name']); ?></span>
                                            <span class="customer-email"><?php echo htmlspecialchars($order['customer_email']); ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="artwork-info">
                                            <?php if (!empty($order['artwork_image']) && file_exists("../uploads/" . $order['artwork_image'])): ?>
                                                <img src="../uploads/<?php echo htmlspecialchars($order['artwork_image']); ?>" 
                                                     alt="<?php echo htmlspecialchars($order['artwork_title']); ?>" 
                                                     class="artwork-thumb">
                                            <?php else: ?>
                                                <div class="artwork-thumb" style="background:#eee;display:flex;align-items:center;justify-content:center;color:#ccc;font-size:18px;">
                                                    <i class="fas fa-image"></i>
                                                </div>
                                            <?php endif; ?>
                                            <span class="artwork-title"><?php echo htmlspecialchars($order['artwork_title']); ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="quantity"><?php echo $order['quantity'] ?? 1; ?></span>
                                    </td>
                                    <td>
                                        <span class="price">Rs <?php echo number_format($order['total_price'], 2); ?></span>
                                    </td>
                                    <td>
                                        <span class="date"><?php echo date('M j, Y', strtotime($order['order_date'])); ?></span>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $order['status']; ?>">
                                            <?php echo $order['status']; ?>
                                        </span>
                                    </td>
                                    <td class="action-cell">
                                        <?php if ($order['status'] != 'Rejected' && $order['status'] != 'Completed'): ?>
                                            <form method="POST" style="display:flex;gap:5px;flex-wrap:wrap;align-items:center;">
                                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                <select name="new_status" class="status-select">
                                                    <option value="Pending" <?php echo $order['status'] == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                                    <option value="Accepted" <?php echo $order['status'] == 'Accepted' ? 'selected' : ''; ?>>Accepted</option>
                                                    <option value="Completed" <?php echo $order['status'] == 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                                    <option value="Rejected">Reject</option>
                                                </select>
                                                <button type="submit" name="update_status" class="update-btn">
                                                    <i class="fas fa-sync"></i>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span style="color:#888;font-size:12px;">
                                                <?php echo $order['status'] == 'Completed' ? '✅ Completed' : '❌ Rejected'; ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-orders">
                        <i class="fas fa-box-open"></i>
                        <h3>No orders found</h3>
                        <p>
                            <?php if (!empty($searchTerm)): ?>
                                No orders matching "<strong><?php echo htmlspecialchars($searchTerm); ?></strong>"
                            <?php elseif ($statusFilter !== 'all'): ?>
                                No <?php echo $statusFilter; ?> orders yet
                            <?php else: ?>
                                You haven't received any orders yet
                            <?php endif; ?>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        // Auto-hide success message after 5 seconds
        setTimeout(function() {
            const message = document.getElementById('successMessage');
            if (message) {
                message.classList.remove('show');
            }
        }, 5000);
        
        // Confirm before rejecting order
        document.querySelectorAll('.update-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                const select = this.closest('form').querySelector('select[name="new_status"]');
                if (select && select.value === 'Rejected') {
                    if (!confirm('Are you sure you want to reject this order? This action cannot be undone.')) {
                        e.preventDefault();
                    }
                }
            });
        });
    </script>
</body>
</html>