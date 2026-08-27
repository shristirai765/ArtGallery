<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit();
}

include "../config/db.php";

$loggedUser = (int)$_SESSION['id'];

// Update user's last activity
$update = $conn->prepare("UPDATE users SET last_activity = NOW() WHERE id = ?");
$update->bind_param("i", $loggedUser);
$update->execute();

// Handle Delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $artworkId = (int)$_GET['delete'];
    
    // Verify artwork belongs to this artist
    $verifyStmt = $conn->prepare("SELECT image FROM artworks WHERE id = ? AND artist_id = ?");
    $verifyStmt->bind_param("ii", $artworkId, $loggedUser);
    $verifyStmt->execute();
    $verifyResult = $verifyStmt->get_result();
    
    if ($verifyResult->num_rows > 0) {
        $artwork = $verifyResult->fetch_assoc();
        
        // Delete image file if exists
        if (!empty($artwork['image']) && file_exists("../uploads/" . $artwork['image'])) {
            unlink("../uploads/" . $artwork['image']);
        }
        
        // Delete from database
        $deleteStmt = $conn->prepare("DELETE FROM artworks WHERE id = ? AND artist_id = ?");
        $deleteStmt->bind_param("ii", $artworkId, $loggedUser);
        $deleteStmt->execute();
        
        header("Location: artworks.php?deleted=1");
        exit();
    }
}

// Get all artworks by this artist
$query = "
    SELECT 
        a.*,
        (SELECT COUNT(*) FROM orders o WHERE o.artwork_id = a.id AND o.status != 'Rejected') as order_count
    FROM artworks a
    WHERE a.artist_id = ?
    ORDER BY a.id DESC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $loggedUser);
$stmt->execute();
$artworksResult = $stmt->get_result();

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
    <title>My Artworks - Artist Dashboard</title>
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
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 20px;
            box-shadow: 0 10px 25px rgba(0,0,0,.08);
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        
        .logo {
            font-size: 28px;
            font-weight: bold;
            color: var(--deep);
        }
        
        .logo i {
            color: var(--gold);
        }
        
        .nav {
            display: flex;
            gap: 12px;
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
        
        /* Page Header */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .page-header h2 {
            color: var(--deep);
            font-size: 28px;
        }
        
        .add-artwork-btn {
            background: var(--deep);
            color: white;
            padding: 12px 24px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 600;
            transition: .3s;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        
        .add-artwork-btn:hover {
            background: #203845;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(44, 75, 90, 0.3);
        }
        
        /* Artworks Grid */
        .artworks-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
        }
        
        .artwork-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,.08);
            transition: .3s;
        }
        
        .artwork-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,.15);
        }
        
        .artwork-image {
            width: 100%;
            height: 220px;
            object-fit: cover;
        }
        
        .artwork-image-placeholder {
            width: 100%;
            height: 220px;
            background: #f0ece8;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ccc;
            font-size: 60px;
        }
        
        .artwork-details {
            padding: 18px;
        }
        
        .artwork-details h3 {
            color: var(--deep);
            font-size: 18px;
            margin-bottom: 5px;
        }
        
        .artwork-details .price {
            font-size: 22px;
            font-weight: bold;
            color: var(--gold);
            margin: 8px 0;
        }
        
        .artwork-details .info-row {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            font-size: 13px;
            color: #888;
            margin-bottom: 10px;
        }
        
        .artwork-details .info-row span {
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .artwork-details .info-row i {
            color: var(--gold);
            width: 16px;
        }
        
        .artwork-details .description {
            font-size: 14px;
            color: #666;
            margin-bottom: 12px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            line-height: 1.5;
        }
        
        .artwork-actions {
            display: flex;
            gap: 8px;
            margin-top: 12px;
            flex-wrap: wrap;
        }
        
        .artwork-actions a {
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 13px;
            text-decoration: none;
            transition: .3s;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-edit {
            background: #e3f2fd;
            color: #1976d2;
        }
        
        .btn-edit:hover {
            background: #bbdefb;
        }
        
        .btn-delete {
            background: #ffebee;
            color: #d32f2f;
        }
        
        .btn-delete:hover {
            background: #ffcdd2;
        }
        
        .btn-orders {
            background: #e8f5e9;
            color: #2e7d32;
        }
        
        .btn-orders:hover {
            background: #c8e6c9;
        }
        
        .no-artworks {
            text-align: center;
            padding: 80px 20px;
            background: white;
            border-radius: 20px;
            grid-column: 1 / -1;
        }
        
        .no-artworks i {
            font-size: 80px;
            color: #c9a87c;
            margin-bottom: 20px;
            display: block;
        }
        
        .no-artworks h3 {
            color: #666;
            margin-bottom: 10px;
            font-size: 24px;
        }
        
        .no-artworks p {
            color: #aaa;
            margin-bottom: 20px;
        }
        
        .no-artworks .btn-add {
            background: var(--deep);
            color: white;
            padding: 12px 30px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 600;
            transition: .3s;
            display: inline-block;
        }
        
        .no-artworks .btn-add:hover {
            background: #203845;
            transform: translateY(-2px);
        }
        
        /* Success Message */
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 12px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: none;
            align-items: center;
            gap: 10px;
        }
        
        .success-message.show {
            display: flex;
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
            
            .page-header {
                flex-direction: column;
                align-items: stretch;
            }
            
            .add-artwork-btn {
                justify-content: center;
            }
            
            .artworks-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">
                <i class="fas fa-paint-brush"></i> Monet's Atelier
            </div>
            <div class="nav">
                <a href="dashboard.php">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="orders.php">
                    <i class="fas fa-box"></i> Orders
                </a>
                <a href="messages.php">
                    <i class="fas fa-envelope"></i> Messages
                </a>
                <a href="artworks.php" class="active">
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
        
        <!-- Success Messages -->
        <div id="successMessage" class="success-message <?php echo isset($_GET['deleted']) ? 'show' : ''; ?>">
            <i class="fas fa-check-circle"></i> Artwork deleted successfully!
        </div>
        <div id="addMessage" class="success-message <?php echo isset($_GET['added']) ? 'show' : ''; ?>">
            <i class="fas fa-check-circle"></i> Artwork added successfully!
        </div>
        
        <!-- Page Header -->
        <div class="page-header">
            <h2><i class="fas fa-palette"></i> My Artworks</h2>
            <a href="add_artwork.php" class="add-artwork-btn">
                <i class="fas fa-plus-circle"></i> Add New Artwork
            </a>
        </div>
        
        <!-- Artworks Grid -->
        <div class="artworks-grid">
            <?php if ($artworksResult->num_rows > 0): ?>
                <?php while ($artwork = $artworksResult->fetch_assoc()): ?>
                    <div class="artwork-card">
                        <?php if (!empty($artwork['image']) && file_exists("../uploads/" . $artwork['image'])): ?>
                            <img src="../uploads/<?php echo htmlspecialchars($artwork['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($artwork['title']); ?>" 
                                 class="artwork-image">
                        <?php else: ?>
                            <div class="artwork-image-placeholder">
                                <i class="fas fa-image"></i>
                            </div>
                        <?php endif; ?>
                        
                        <div class="artwork-details">
                            <h3><?php echo htmlspecialchars($artwork['title']); ?></h3>
                            <div class="price">Rs <?php echo number_format($artwork['price'], 2); ?></div>
                            
                            <div class="info-row">
                                <?php if (!empty($artwork['medium'])): ?>
                                    <span><i class="fas fa-paint-brush"></i> <?php echo htmlspecialchars($artwork['medium']); ?></span>
                                <?php endif; ?>
                                <?php if (!empty($artwork['genre'])): ?>
                                    <span><i class="fas fa-tag"></i> <?php echo htmlspecialchars($artwork['genre']); ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (!empty($artwork['description'])): ?>
                                <div class="description">
                                    <?php echo htmlspecialchars(substr($artwork['description'], 0, 100)) . (strlen($artwork['description']) > 100 ? '...' : ''); ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="artwork-actions">
                                <a href="edit_artwork.php?id=<?php echo $artwork['id']; ?>" class="btn-edit">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="?delete=<?php echo $artwork['id']; ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this artwork? This action cannot be undone.')">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                                <?php if (($artwork['order_count'] ?? 0) > 0): ?>
                                    <a href="orders.php?search=<?php echo $artwork['id']; ?>" class="btn-orders">
                                        <i class="fas fa-shopping-bag"></i> <?php echo $artwork['order_count']; ?> orders
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-artworks">
                    <i class="fas fa-paint-brush"></i>
                    <h3>No artworks yet</h3>
                    <p>Start uploading your artwork to sell!</p>
                    <a href="add_artwork.php" class="btn-add">
                        <i class="fas fa-plus-circle"></i> Add Your First Artwork
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Auto-hide success messages after 5 seconds
        setTimeout(function() {
            document.querySelectorAll('.success-message').forEach(msg => {
                msg.classList.remove('show');
            });
        }, 5000);
    </script>
</body>
</html>