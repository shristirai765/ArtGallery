<?php
session_start();

if (!isset($_SESSION['id']) || $_SESSION['role'] != 'user') {
    header("Location: ../login.php");
    exit();
}

include "../config/db.php";

if (!isset($_GET['id'])) {
    header("Location: customer_dashboard.php");
    exit();
}

$id = (int)$_GET['id'];

// Update view count - Increment views for this artwork
$viewStmt = $conn->prepare("UPDATE artworks SET views = views + 1 WHERE id = ?");
$viewStmt->bind_param("i", $id);
$viewStmt->execute();

// Get artwork details with artist name
$stmt = $conn->prepare("
    SELECT
        artworks.*,
        users.username AS artist_name
    FROM artworks
    JOIN users
        ON artworks.artist_id = users.id
    WHERE artworks.id = ?
");

$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Artwork not found.");
}

$art = $result->fetch_assoc();

// Check if user already has this artwork in cart
$cartCheck = $conn->prepare("
    SELECT id FROM cart 
    WHERE user_id = ? AND artwork_id = ?
");
$cartCheck->bind_param("ii", $_SESSION['id'], $id);
$cartCheck->execute();
$cartResult = $cartCheck->get_result();
$inCart = $cartResult->num_rows > 0;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($art['title']); ?></title>
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
            max-width: 1100px;
            margin: 40px auto;
        }

        .nav-bar {
            background: white;
            padding: 15px 30px;
            border-radius: 20px;
            box-shadow: var(--shadow);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }

        .nav-bar .logo {
            font-size: 24px;
            font-weight: bold;
            color: var(--monet-deep);
        }

        .nav-bar .logo i {
            color: var(--monet-gold);
        }

        .nav-bar a {
            text-decoration: none;
            color: var(--monet-deep);
            font-weight: 600;
            padding: 8px 16px;
            border-radius: 10px;
            transition: .3s;
        }

        .nav-bar a:hover {
            background: var(--monet-deep);
            color: white;
        }

        .card {
            background: white;
            border-radius: 25px;
            overflow: hidden;
            box-shadow: var(--shadow);
        }

        .image {
            width: 100%;
            background: #f8f8f8;
            text-align: center;
            padding: 30px;
        }

        .image img {
            max-width: 100%;
            max-height: 600px;
            object-fit: contain;
            border-radius: 15px;
        }

        .content {
            padding: 35px;
        }

        h1 {
            color: var(--monet-deep);
            margin-bottom: 15px;
            font-size: 28px;
        }

        .artist {
            color: #666;
            margin-bottom: 20px;
            font-size: 16px;
        }

        .artist i {
            color: var(--monet-gold);
            margin-right: 8px;
        }

        .price {
            color: var(--monet-gold);
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .description {
            line-height: 1.8;
            color: #555;
            margin-bottom: 30px;
        }

        .meta-info {
            display: flex;
            gap: 30px;
            flex-wrap: wrap;
            margin-bottom: 25px;
            padding: 15px 0;
            border-top: 1px solid #eee;
            border-bottom: 1px solid #eee;
        }

        .meta-info span {
            color: #888;
            font-size: 14px;
        }

        .meta-info i {
            margin-right: 5px;
            color: var(--monet-gold);
        }

        .buttons {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .btn {
            text-decoration: none;
            color: white;
            padding: 14px 22px;
            border-radius: 12px;
            font-weight: 600;
            transition: .3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: none;
            cursor: pointer;
        }

        .btn:hover {
            transform: translateY(-2px);
            opacity: .9;
        }

        .btn-cart {
            background: #27ae60;
        }

        .btn-cart.in-cart {
            background: #f39c12;
        }

        .btn-chat {
            background: #3498db;
        }

        .btn-back {
            background: var(--monet-deep);
        }

        /* Toast notification */
        .toast {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: #27ae60;
            color: white;
            padding: 15px 25px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            display: none;
            align-items: center;
            gap: 10px;
            z-index: 1000;
            animation: slideUp 0.3s ease;
        }

        .toast.error {
            background: #e74c3c;
        }

        @keyframes slideUp {
            from {
                transform: translateY(20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        @media (max-width: 768px) {
            .nav-bar {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .meta-info {
                flex-direction: column;
                gap: 10px;
            }

            .buttons {
                flex-direction: column;
            }

            .btn {
                justify-content: center;
            }

            .container {
                width: 95%;
                margin: 20px auto;
            }
        }
    </style>
</head>

<body>

<div class="container">

    <!-- Navigation -->
    <div class="nav-bar">
        <div class="logo">
            <i class="fas fa-palette"></i> ArtHub
        </div>
        <div>
            <a href="customer_dashboard.php">
                <i class="fas fa-home"></i> Gallery
            </a>
            <a href="cart.php">
                <i class="fas fa-shopping-cart"></i> Cart
            </a>
            <a href="orders.php">
                <i class="fas fa-box"></i> Orders
            </a>
            <a href="messages.php">
                <i class="fas fa-envelope"></i> Messages
            </a>
            <a href="../logout.php">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>

    <!-- Artwork Details -->
    <div class="card">
        <div class="image">
            <img src="../uploads/<?php echo htmlspecialchars($art['image']); ?>" 
                 alt="<?php echo htmlspecialchars($art['title']); ?>">
        </div>

        <div class="content">
            <h1><?php echo htmlspecialchars($art['title']); ?></h1>

            <p class="artist">
                <i class="fas fa-user"></i>
                <strong>Artist:</strong> <?php echo htmlspecialchars($art['artist_name']); ?>
            </p>

            <div class="price">Rs <?php echo number_format($art['price'], 2); ?></div>

            <div class="meta-info">
                <span><i class="fas fa-tag"></i> <?php echo htmlspecialchars($art['genre'] ?? 'Uncategorized'); ?></span>
                <span><i class="fas fa-paint-brush"></i> <?php echo htmlspecialchars($art['medium'] ?? 'N/A'); ?></span>
                <span><i class="fas fa-eye"></i> <?php echo $art['views'] ?? 0; ?> views</span>
            </div>

            <div class="description">
                <?php echo nl2br(htmlspecialchars($art['description'])); ?>
            </div>

            <div class="buttons">
                <?php if ($inCart): ?>
                    <a href="cart.php" class="btn btn-cart in-cart">
                        <i class="fas fa-check"></i> Already in Cart
                    </a>
                <?php else: ?>
                    <a href="add_to_cart.php?id=<?php echo $art['id']; ?>" class="btn btn-cart">
                        <i class="fas fa-shopping-cart"></i> Add to Cart
                    </a>
                <?php endif; ?>

                <a href="chat.php?artist=<?php echo $art['artist_id']; ?>&art=<?php echo $art['id']; ?>" class="btn btn-chat">
                    <i class="fas fa-comments"></i> Contact Artist
                </a>

                <a href="customer_dashboard.php" class="btn btn-back">
                    <i class="fas fa-arrow-left"></i> Back to Gallery
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Toast Notification -->
<div id="toast" class="toast">
    <i class="fas fa-check-circle"></i>
    <span id="toastMessage">Added to cart!</span>
</div>

<script>
    // Show toast notification if coming from add_to_cart
    <?php if (isset($_GET['added'])): ?>
        showToast('Artwork added to cart successfully!');
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        showToast('Failed to add to cart. Please try again.', 'error');
    <?php endif; ?>

    function showToast(message, type = 'success') {
        const toast = document.getElementById('toast');
        const toastMessage = document.getElementById('toastMessage');
        toastMessage.textContent = message;
        toast.className = 'toast' + (type === 'error' ? ' error' : '');
        toast.style.display = 'flex';
        
        setTimeout(() => {
            toast.style.display = 'none';
        }, 3000);
    }
</script>

</body>
</html>