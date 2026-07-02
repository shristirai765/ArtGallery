<?php
session_start();

if (!isset($_SESSION['id']) || $_SESSION['role'] != 'artist') {
    header("Location: ../login.php");
    exit();
}

include '../config/db.php';

$id = $_SESSION['id'];

// Get user data
$user = $conn->query("
    SELECT *
    FROM users
    WHERE id='$id'
")->fetch_assoc();

if (!$user) {
    header("Location: ../logout.php");
    exit();
}

// Get statistics
$totalArtworks = $conn->query("
    SELECT COUNT(*) AS total
    FROM artworks
    WHERE artist_id='$id'
")->fetch_assoc()['total'];

$totalViews = $conn->query("
    SELECT SUM(views) AS total
    FROM artworks
    WHERE artist_id='$id'
")->fetch_assoc()['total'] ?? 0;

$totalSales = $conn->query("
    SELECT COUNT(*) AS total
    FROM orders
    WHERE artist_id='$id' AND status = 'Completed'
")->fetch_assoc()['total'] ?? 0;

$totalRevenue = $conn->query("
    SELECT SUM(total_price) AS total
    FROM orders
    WHERE artist_id='$id' AND status = 'Completed'
")->fetch_assoc()['total'] ?? 0;

// If total_price doesn't exist, try using artwork price
if ($totalRevenue == 0) {
    $revenueResult = $conn->query("
        SELECT SUM(a.price) AS total
        FROM orders o
        JOIN artworks a ON o.artwork_id = a.id
        WHERE o.artist_id='$id' AND o.status = 'Completed'
    ");
    if ($revenueResult && $revenueResult->num_rows > 0) {
        $totalRevenue = $revenueResult->fetch_assoc()['total'] ?? 0;
    }
}

// Navigation counts
$orderCount = $conn->query("SELECT COUNT(*) total FROM orders WHERE artist_id = $id")->fetch_assoc()['total'] ?? 0;
$messageCount = $conn->query("
    SELECT COUNT(*) total 
    FROM messages 
    WHERE receiver_id = $id AND is_read = 0
")->fetch_assoc()['total'] ?? 0;
$artworkCount = $totalArtworks;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Artist Profile | ArtHub</title>
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
            width: 90%;
            max-width: 1200px;
            margin: auto;
        }

        /* Header Navigation */
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
            margin-bottom: 30px;
        }

        .logo {
            font-size: 24px;
            font-weight: 700;
            color: var(--monet-deep);
        }

        .logo i {
            color: var(--monet-gold);
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
        }

        .nav a:hover {
            background: var(--monet-deep);
            color: white;
        }

        .nav .active {
            background: var(--monet-deep);
            color: white;
        }

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

        /* Banner */
        .banner {
            height: 180px;
            border-radius: 30px;
            background: linear-gradient(135deg, #e8ddd2, #d6c8bb);
            box-shadow: var(--shadow);
            position: relative;
            overflow: hidden;
        }

        .banner::after {
            content: '🎨';
            position: absolute;
            right: 40px;
            bottom: 20px;
            font-size: 60px;
            opacity: 0.3;
        }

        /* Profile Card */
        .profile-card {
            background: white;
            margin-top: -60px;
            border-radius: 30px;
            padding: 40px;
            box-shadow: var(--shadow);
            position: relative;
            z-index: 1;
        }

        .profile-top {
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
            align-items: center;
        }

        .avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            overflow: hidden;
            border: 6px solid white;
            box-shadow: var(--shadow);
            flex-shrink: 0;
            background: #f0ece8;
        }

        .avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .avatar-placeholder {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, var(--monet-lily), var(--monet-deep));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 4rem;
        }

        .info {
            flex: 1;
        }

        .info h1 {
            color: var(--monet-deep);
            margin-bottom: 8px;
            font-size: 28px;
        }

        .info .role-badge {
            display: inline-block;
            background: var(--monet-gold);
            color: white;
            padding: 4px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .info p {
            color: #6c7c84;
            margin-bottom: 6px;
            font-size: 15px;
        }

        .info p i {
            color: var(--monet-gold);
            width: 20px;
        }

        .info .status {
            font-size: 14px;
            margin-top: 5px;
        }

        .info .status .online {
            color: #27ae60;
        }

        .info .status .offline {
            color: #999;
        }

        .buttons {
            margin-top: 15px;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .btn {
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 600;
            transition: .3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .edit-btn {
            background: var(--monet-deep);
            color: white;
        }

        .edit-btn:hover {
            background: #1f3945;
            box-shadow: 0 5px 15px rgba(44, 75, 90, 0.3);
        }

        .dashboard-btn {
            background: #7f8c8d;
            color: white;
        }

        .dashboard-btn:hover {
            background: #667273;
        }

        /* Stats */
        .stats {
            margin-top: 30px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
        }

        .stat {
            background: #f8f5f0;
            padding: 25px;
            text-align: center;
            border-radius: 20px;
            transition: .3s;
        }

        .stat:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow);
        }

        .stat i {
            font-size: 2rem;
            color: var(--monet-gold);
            margin-bottom: 8px;
        }

        .stat .number {
            font-size: 28px;
            font-weight: 700;
            color: var(--monet-deep);
            display: block;
        }

        .stat .label {
            color: #888;
            font-size: 14px;
        }

        .stat.revenue .number {
            color: #2ecc71;
        }

        /* Bio Section */
        .bio-section {
            margin-top: 30px;
            background: white;
            padding: 30px;
            border-radius: 25px;
            box-shadow: var(--shadow);
        }

        .bio-section h2 {
            color: var(--monet-deep);
            margin-bottom: 15px;
            font-size: 20px;
        }

        .bio-section h2 i {
            color: var(--monet-gold);
            margin-right: 8px;
        }

        .bio-section p {
            color: #5f6f77;
            line-height: 1.8;
            font-size: 15px;
        }

        .bio-section .empty-bio {
            color: #aaa;
            font-style: italic;
        }

        /* Responsive */
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

            .profile-top {
                flex-direction: column;
                text-align: center;
            }

            .buttons {
                justify-content: center;
            }

            .profile-card {
                padding: 25px;
                margin-top: -40px;
            }

            .avatar {
                width: 120px;
                height: 120px;
            }

            .stats {
                grid-template-columns: repeat(2, 1fr);
            }

            .container {
                width: 95%;
            }

            .banner {
                height: 120px;
            }

            .banner::after {
                font-size: 40px;
                right: 20px;
                bottom: 10px;
            }
        }

        @media (max-width: 480px) {
            .stats {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<div class="container">

    <!-- Navigation -->
    <div class="header-nav">
        <div class="logo">
            <i class="fas fa-palette"></i> ArtHub Artist
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
            <a href="artworks.php">
                <i class="fas fa-paint-brush"></i> My Artworks
            </a>
            <a href="add_artwork.php">
                <i class="fas fa-plus-circle"></i> Add Artwork
            </a>
            <a href="profile.php" class="active">
                <i class="fas fa-user-circle"></i> Profile
            </a>
            <a href="../logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>

    <!-- Banner -->
    <div class="banner"></div>

    <!-- Profile Card -->
    <div class="profile-card">

        <div class="profile-top">

            <div class="avatar">
                <?php if (!empty($user['profile_image']) && file_exists("../uploads/profile/" . $user['profile_image'])): ?>
                    <img src="../uploads/profile/<?php echo htmlspecialchars($user['profile_image']); ?>" 
                         alt="<?php echo htmlspecialchars($user['username']); ?>">
                <?php elseif (!empty($user['profile_image']) && file_exists("../uploads/" . $user['profile_image'])): ?>
                    <img src="../uploads/<?php echo htmlspecialchars($user['profile_image']); ?>" 
                         alt="<?php echo htmlspecialchars($user['username']); ?>">
                <?php else: ?>
                    <div class="avatar-placeholder">
                        <i class="fas fa-user"></i>
                    </div>
                <?php endif; ?>
            </div>

            <div class="info">
                <h1><?php echo htmlspecialchars($user['username']); ?></h1>
                <span class="role-badge">Artist</span>

                <p>
                    <i class="fas fa-envelope"></i>
                    <?php echo htmlspecialchars($user['email']); ?>
                </p>

                <p>
                    <i class="fas fa-paint-brush"></i>
                    Artist Account
                </p>

                <?php
                // Check if user is online
                $lastActivity = strtotime($user['last_activity'] ?? '');
                $currentTime = time();
                $isOnline = ($lastActivity && ($currentTime - $lastActivity) < (5 * 60));
                ?>
                <p class="status">
                    <i class="fas fa-circle <?php echo $isOnline ? 'online' : 'offline'; ?>"></i>
                    <?php echo $isOnline ? 'Online' : 'Offline'; ?>
                </p>

                <div class="buttons">
                    <a href="edit_profile.php" class="btn edit-btn">
                        <i class="fas fa-user-edit"></i> Edit Profile
                    </a>
                    <a href="dashboard.php" class="btn dashboard-btn">
                        <i class="fas fa-arrow-left"></i> Dashboard
                    </a>
                </div>
            </div>

        </div>

        <!-- Stats -->
        <div class="stats">
            <div class="stat">
                <i class="fas fa-image"></i>
                <span class="number"><?php echo $totalArtworks; ?></span>
                <span class="label">Artworks</span>
            </div>

            <div class="stat">
                <i class="fas fa-eye"></i>
                <span class="number"><?php echo number_format($totalViews); ?></span>
                <span class="label">Total Views</span>
            </div>

            <div class="stat">
                <i class="fas fa-shopping-cart"></i>
                <span class="number"><?php echo $totalSales; ?></span>
                <span class="label">Sales</span>
            </div>

            <div class="stat revenue">
                <i class="fas fa-rupee-sign"></i>
                <span class="number">Rs <?php echo number_format($totalRevenue ?? 0, 0); ?></span>
                <span class="label">Revenue</span>
            </div>
        </div>

    </div>

    <!-- Bio Section -->
    <div class="bio-section">
        <h2>
            <i class="fas fa-feather-alt"></i>
            About the Artist
        </h2>

        <p>
            <?php if (!empty($user['bio'])): ?>
                <?php echo nl2br(htmlspecialchars($user['bio'])); ?>
            <?php else: ?>
                <span class="empty-bio">No artist biography has been added yet.</span>
            <?php endif; ?>
        </p>
    </div>

</div>

</body>
</html>