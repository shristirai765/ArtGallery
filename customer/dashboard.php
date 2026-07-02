<?php
session_start();

if (!isset($_SESSION['id']) || $_SESSION['role'] != 'user') {
    header("Location: ../login.php");
    exit();
}

include '../config/db.php';
$user = $_SESSION['id'];

/* ---------- Navigation Counts ---------- */

// Cart count
$cartCount = $conn->query("
    SELECT COUNT(*) AS total
    FROM cart
    WHERE user_id='$user'
")->fetch_assoc()['total'];

// Message count (unread messages)
$messageCount = $conn->query("
    SELECT COUNT(*) AS total
    FROM messages
    WHERE receiver_id='$user' AND is_read = 0
")->fetch_assoc()['total'] ?? 0;

// Order count
$orderCount = $conn->query("
    SELECT COUNT(*) AS total
    FROM orders
    WHERE user_id='$user'
")->fetch_assoc()['total'] ?? 0;

// Get artworks with view counts
$artworks = $conn->query("
    SELECT 
        artworks.*, 
        users.username AS artist_name,
        (SELECT COUNT(*) FROM orders o WHERE o.artwork_id = artworks.id AND o.status != 'Rejected') as order_count
    FROM artworks
    JOIN users ON artworks.artist_id = users.id
    ORDER BY artworks.id DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard | Monet's Atelier</title>

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
            max-width: 1400px;
            margin: auto;
        }

        /* ===========================
           HEADER
        ============================ */
        .header {
            margin-top: 20px;
            background: white;
            padding: 20px 30px;
            border-radius: 25px;
            box-shadow: var(--shadow);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
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
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .nav a {
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            color: var(--monet-deep);
            font-weight: 600;
            padding: 10px 16px;
            border-radius: 10px;
            transition: .3s;
        }

        .nav a:hover {
            background: var(--monet-deep);
            color: white;
        }

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

        /* Welcome */
        .welcome {
            margin: 30px 0;
            background: linear-gradient(145deg, #e8ddd2, #d6c8bb);
            padding: 50px;
            border-radius: 30px;
            box-shadow: var(--shadow);
        }

        .welcome h1 {
            color: var(--monet-deep);
            margin-bottom: 10px;
        }

        .welcome p {
            color: #5e7079;
        }

        /* Gallery */
        .section-title {
            margin-bottom: 25px;
            color: var(--monet-deep);
        }

        .art-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
        }

        .art-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: .3s;
        }

        .art-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(44,75,90,.15);
        }

        .art-card img {
            width: 100%;
            height: 250px;
            object-fit: cover;
        }

        .art-content {
            padding: 20px;
        }

        .art-content h3 {
            color: var(--monet-deep);
            margin-bottom: 5px;
            font-size: 18px;
        }

        .artist {
            color: #7f8c8d;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .artist i {
            color: var(--monet-gold);
            margin-right: 5px;
        }

        .price {
            color: var(--monet-gold);
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .art-meta {
            display: flex;
            gap: 15px;
            font-size: 13px;
            color: #888;
            margin-bottom: 12px;
            flex-wrap: wrap;
        }

        .art-meta span {
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .art-meta i {
            color: var(--monet-gold);
        }

        .art-meta .views-count {
            color: #3498db;
            font-weight: 600;
        }

        .art-meta .orders-count {
            color: #27ae60;
            font-weight: 600;
        }

        .btn-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .btn-group a {
            display: block;
            text-align: center;
            text-decoration: none;
            padding: 10px;
            border-radius: 10px;
            font-weight: 600;
            transition: .3s;
        }

        .btn-group a:hover {
            transform: translateY(-2px);
        }

        .view-btn {
            background: var(--monet-deep);
            color: white;
        }

        .view-btn:hover {
            background: #203845;
        }

        .cart-btn {
            background: #27ae60;
            color: white;
        }

        .cart-btn:hover {
            background: #219150;
        }

        .chat-btn {
            background: #3498db;
            color: white;
        }

        .chat-btn:hover {
            background: #2c80b4;
        }

        .empty {
            text-align: center;
            padding: 50px;
            background: white;
            border-radius: 20px;
            box-shadow: var(--shadow);
        }

        .empty i {
            font-size: 4rem;
            color: var(--monet-gold);
            margin-bottom: 15px;
            display: block;
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

            .welcome {
                padding: 30px;
                text-align: center;
            }

            .welcome h1 {
                font-size: 22px;
            }

            .art-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 15px;
            }

            .art-card img {
                height: 180px;
            }

            .container {
                width: 95%;
            }
        }

        @media (max-width: 480px) {
            .art-grid {
                grid-template-columns: 1fr;
            }

            .nav {
                flex-wrap: wrap;
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
            <a href="dashboard.php" class="active">
                <i class="fas fa-home"></i>
                Home
            </a>
            <a href="cart.php">
                <i class="fas fa-shopping-cart"></i>
                My Cart
                <!-- Badge removed -->
            </a>
            <a href="orders.php">
                <i class="fas fa-box-open"></i>
                Orders
                <!-- Badge removed -->
            </a>
            <a href="messages.php">
                <i class="fas fa-comments"></i>
                Messages
                <!-- Badge removed -->
            </a>
            <a href="profile.php">
                <i class="fas fa-user-circle"></i>
                Profile
            </a>
            <a href="../logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                Logout
            </a>
        </div>
    </div>

    <div class="welcome">
        <h1>
            Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>
        </h1>
        <p>
            Explore beautiful artworks from talented artists around the world.
        </p>
    </div>

    <h2 class="section-title">
        <i class="fas fa-image"></i>
        Available Artworks
    </h2>

    <?php if ($artworks && $artworks->num_rows > 0): ?>
        <div class="art-grid">
            <?php while ($art = $artworks->fetch_assoc()): ?>
                <div class="art-card">
                    <?php if (!empty($art['image']) && file_exists("../uploads/" . $art['image'])): ?>
                        <img src="../uploads/<?php echo htmlspecialchars($art['image']); ?>"
                             alt="<?php echo htmlspecialchars($art['title']); ?>">
                    <?php else: ?>
                        <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='400' height='300'%3E%3Crect width='400' height='300' fill='%23eee'/%3E%3Ctext x='200' y='150' text-anchor='middle' font-family='Arial' font-size='20' fill='%23999'%3ENo Image%3C/text%3E%3C/svg%3E"
                             alt="No image available">
                    <?php endif; ?>

                    <div class="art-content">
                        <h3><?php echo htmlspecialchars($art['title']); ?></h3>
                        <p class="artist">
                            <i class="fas fa-user"></i>
                            <?php echo htmlspecialchars($art['artist_name']); ?>
                        </p>
                        <p class="price">
                            <i class="fas fa-tags"></i>
                            Rs <?php echo number_format($art['price'], 2); ?>
                        </p>

                        <div class="art-meta">
                            <span><i class="fas fa-paint-brush"></i> <?php echo htmlspecialchars($art['medium'] ?? 'N/A'); ?></span>
                            <span><i class="fas fa-tag"></i> <?php echo htmlspecialchars($art['genre'] ?? 'N/A'); ?></span>
                            <span class="views-count"><i class="fas fa-eye"></i> <?php echo number_format($art['views'] ?? 0); ?> views</span>
                            <span class="orders-count"><i class="fas fa-shopping-bag"></i> <?php echo $art['order_count'] ?? 0; ?> sold</span>
                        </div>

                        <div class="btn-group">
                            <a href="view_artwork.php?id=<?php echo $art['id']; ?>" class="view-btn">
                                <i class="fas fa-eye"></i> View Artwork
                            </a>
                            <a href="add_to_cart.php?id=<?php echo $art['id']; ?>" class="cart-btn">
                                <i class="fas fa-shopping-cart"></i> Add to Cart
                            </a>
                            <a href="chat.php?artist=<?php echo $art['artist_id']; ?>&art=<?php echo $art['id']; ?>" class="chat-btn">
                                <i class="fas fa-comments"></i> Contact Artist
                            </a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="empty">
            <i class="fas fa-paint-brush"></i>
            <h3>No artworks available yet.</h3>
            <p style="color:#888;margin-top:5px;">Check back soon for new creations!</p>
        </div>
    <?php endif; ?>

</div>

</body>
</html>