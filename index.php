<?php
session_start();

include 'config/db.php';

// Get featured artworks (latest 6 artworks from all artists)
$artworksQuery = "
    SELECT 
        a.*,
        u.username as artist_name
    FROM artworks a
    JOIN users u ON a.artist_id = u.id
    ORDER BY a.id DESC
    LIMIT 6
";
$artworksResult = $conn->query($artworksQuery);

// Get total artworks count
$totalArtworks = $conn->query("SELECT COUNT(*) as total FROM artworks")->fetch_assoc()['total'];
$totalArtists = $conn->query("SELECT COUNT(DISTINCT artist_id) as total FROM artworks")->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monet's Atelier</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --monet-water: #b5d1d6;
            --monet-lily: #7fa3a8;
            --monet-gold: #c9a87c;
            --monet-deep: #2c4b5a;
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
            width: 90%;
            max-width: 1400px;
            margin: auto;
        }

        /* Header */
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
        }

        .logo i {
            color: var(--monet-gold);
        }

        .nav-buttons {
            display: flex;
            gap: 10px;
        }

        .nav-buttons a {
            text-decoration: none;
            background: var(--monet-deep);
            color: white;
            padding: 12px 20px;
            border-radius: 40px;
            font-weight: 600;
            transition: .3s;
        }

        .nav-buttons a:hover {
            background: #1f3b47;
        }

        /* Hero */
        .hero {
            margin: 40px 0;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            align-items: center;
            background: linear-gradient(145deg, #e8ddd2, #d6c8bb);
            padding: 60px;
            border-radius: 60px 20px 60px 20px;
            box-shadow: var(--shadow);
        }

        .hero-text h1 {
            font-size: 3.5rem;
            margin-bottom: 20px;
        }

        .hero-text p {
            font-size: 1.1rem;
            line-height: 1.8;
            color: #617680;
            margin-bottom: 30px;
        }

        .hero-btn {
            display: inline-block;
            text-decoration: none;
            background: var(--monet-deep);
            color: white;
            padding: 15px 30px;
            border-radius: 50px;
            font-weight: 700;
            transition: .3s;
        }

        .hero-btn:hover {
            background: #1f3b47;
            transform: translateY(-2px);
        }

        .hero-icon {
            text-align: center;
        }

        .hero-icon i {
            font-size: 10rem;
            color: var(--monet-gold);
        }

        /* Section Title */
        .section-title {
            text-align: center;
            margin-bottom: 30px;
            font-size: 2rem;
        }

        /* Info Grid */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
        }

        .info-card {
            background: white;
            padding: 35px;
            border-radius: 40px 12px 40px 12px;
            text-align: center;
            box-shadow: var(--shadow);
            transition: .3s;
        }

        .info-card:hover {
            transform: translateY(-5px);
        }

        .info-card i {
            font-size: 3rem;
            color: var(--monet-lily);
            margin-bottom: 15px;
        }

        .info-card h3 {
            margin-bottom: 10px;
        }

        /* Featured Gallery */
        .featured-gallery {
            margin-top: 50px;
        }

        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
        }

        .art-card {
            background: white;
            border-radius: 25px;
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: .3s;
        }

        .art-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 35px rgba(44, 75, 90, .2);
        }

        .art-card img {
            width: 100%;
            height: 250px;
            object-fit: cover;
            background: #f0ece8;
        }

        .art-content {
            padding: 20px;
        }

        .art-content h3 {
            font-size: 18px;
            color: var(--monet-deep);
            margin-bottom: 5px;
        }

        .artist {
            color: var(--monet-lily);
            margin: 5px 0 10px;
            font-weight: 600;
            font-size: 14px;
        }

        .artist i {
            margin-right: 5px;
        }

        .price {
            color: var(--monet-gold);
            font-weight: 700;
            font-size: 18px;
        }

        .no-artworks {
            text-align: center;
            padding: 60px;
            background: white;
            border-radius: 25px;
            grid-column: 1 / -1;
        }

        .no-artworks i {
            font-size: 4rem;
            color: var(--monet-gold);
            margin-bottom: 20px;
            display: block;
        }

        .no-artworks h3 {
            color: #666;
            margin-bottom: 10px;
        }

        .no-artworks p {
            color: #999;
        }

        /* Features */
        .features {
            margin-top: 50px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .feature {
            background: white;
            padding: 25px;
            border-radius: 25px;
            text-align: center;
            box-shadow: var(--shadow);
            transition: .3s;
        }

        .feature:hover {
            transform: translateY(-5px);
        }

        .feature i {
            font-size: 2.5rem;
            color: var(--monet-gold);
            margin-bottom: 15px;
        }

        /* CTA Section */
        .cta-section {
            margin-top: 60px;
            text-align: center;
            background: linear-gradient(145deg, #e8ddd2, #d6c8bb);
            padding: 60px;
            border-radius: 30px;
            box-shadow: var(--shadow);
        }

        .cta-section h2 {
            margin-bottom: 20px;
        }

        .cta-section p {
            margin-bottom: 30px;
            color: #617680;
        }

        /* Stats */
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 40px 0;
        }

        .stat-card {
            background: white;
            padding: 30px;
            border-radius: 20px;
            text-align: center;
            box-shadow: var(--shadow);
        }

        .stat-card .number {
            font-size: 36px;
            font-weight: 700;
            color: var(--monet-deep);
            display: block;
        }

        .stat-card .label {
            color: #888;
            font-size: 14px;
            margin-top: 5px;
        }

        .stat-card i {
            font-size: 2rem;
            color: var(--monet-gold);
            margin-bottom: 10px;
        }

        /* Footer */
        footer {
            margin-top: 50px;
            text-align: center;
            padding: 25px;
            border-top: 1px solid #ddd;
            color: #617680;
        }

        footer i {
            color: var(--monet-gold);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero {
                grid-template-columns: 1fr;
                text-align: center;
                padding: 35px;
            }

            .hero-text h1 {
                font-size: 2.2rem;
            }

            .hero-icon i {
                font-size: 6rem;
            }

            header {
                justify-content: center;
                gap: 15px;
            }

            .nav-buttons {
                flex-wrap: wrap;
                justify-content: center;
            }

            .gallery-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }

            .art-card img {
                height: 180px;
            }

            .stats {
                grid-template-columns: repeat(2, 1fr);
            }

            .cta-section {
                padding: 35px;
            }
        }

        @media (max-width: 480px) {
            .stats {
                grid-template-columns: 1fr;
            }

            .gallery-grid {
                grid-template-columns: 1fr;
            }

            .hero-btn {
                display: block;
                text-align: center;
            }
        }
    </style>
</head>

<body>

<div class="container">

    <!-- HEADER -->
    <header>
        <div class="logo">
            <i class="fas fa-palette"></i>
            Monet's Atelier
        </div>
        <div class="nav-buttons">
            <a href="gallery.php">
                <i class="fas fa-images"></i>
                Gallery
            </a>
            <?php if (isset($_SESSION['id'])): ?>
                <a href="artist/dashboard.php">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </a>
                <a href="logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            <?php else: ?>
                <a href="login.php">
                    <i class="fas fa-sign-in-alt"></i>
                    Login
                </a>
                <a href="register.php">
                    <i class="fas fa-user-plus"></i>
                    Register
                </a>
            <?php endif; ?>
        </div>
    </header>

    <!-- HERO -->
    <section class="hero">
        <div class="hero-text">
            <h1>Where Art Finds Its Voice</h1>
            <p>
                Discover unique artwork, connect with talented artists,
                and explore a vibrant marketplace built for creativity,
                inspiration, and artistic expression.
            </p>
            <a href="gallery.php" class="hero-btn">
                <i class="fas fa-images"></i>
                Explore Gallery
            </a>
        </div>
        <div class="hero-icon">
            <i class="fas fa-palette"></i>
        </div>
    </section>

    <!-- STATS -->
    <div class="stats">
        <div class="stat-card">
            <i class="fas fa-image"></i>
            <span class="number"><?php echo $totalArtworks; ?></span>
            <span class="label">Artworks Available</span>
        </div>
        <div class="stat-card">
            <i class="fas fa-users"></i>
            <span class="number"><?php echo $totalArtists; ?></span>
            <span class="label">Artists</span>
        </div>
        <div class="stat-card">
            <i class="fas fa-heart"></i>
            <span class="number">0</span>
            <span class="label">Art Lovers</span>
        </div>
        <div class="stat-card">
            <i class="fas fa-star"></i>
            <span class="number">0</span>
            <span class="label">Ratings</span>
        </div>
    </div>

    <!-- FEATURED ARTWORKS -->
    <section class="featured-gallery">
        <h2 class="section-title">Featured Artworks</h2>

        <div class="gallery-grid">
            <?php if ($artworksResult && $artworksResult->num_rows > 0): ?>
                <?php while ($artwork = $artworksResult->fetch_assoc()): ?>
                    <div class="art-card">
                        <?php if (!empty($artwork['image']) && file_exists("uploads/" . $artwork['image'])): ?>
                            <img src="uploads/<?php echo htmlspecialchars($artwork['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($artwork['title']); ?>">
                        <?php else: ?>
                            <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='400' height='300'%3E%3Crect width='400' height='300' fill='%23f0ece8'/%3E%3Ctext x='200' y='150' text-anchor='middle' font-family='Arial' font-size='20' fill='%23999'%3ENo Image%3C/text%3E%3C/svg%3E" 
                                 alt="No image available">
                        <?php endif; ?>
                        <div class="art-content">
                            <h3><?php echo htmlspecialchars($artwork['title']); ?></h3>
                            <p class="artist">
                                <i class="fas fa-user"></i>
                                <?php echo htmlspecialchars($artwork['artist_name']); ?>
                            </p>
                            <span class="price">Rs <?php echo number_format($artwork['price'], 2); ?></span>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-artworks">
                    <i class="fas fa-paint-brush"></i>
                    <h3>No Artworks Available</h3>
                    <p>Be the first to upload artwork!</p>
                </div>
            <?php endif; ?>
        </div>

        <div style="text-align:center;margin-top:30px;">
            <a href="gallery.php" class="hero-btn">
                <i class="fas fa-images"></i>
                View Full Gallery
            </a>
        </div>
    </section>

    <!-- WHY US -->
    <section style="margin-top:50px;">
        <h2 class="section-title">Why Monet's Atelier?</h2>
        <div class="info-grid">
            <div class="info-card">
                <i class="fas fa-image"></i>
                <h3>Discover Art</h3>
                <p>Explore original paintings from talented artists.</p>
            </div>
            <div class="info-card">
                <i class="fas fa-paint-brush"></i>
                <h3>Support Artists</h3>
                <p>Help creators showcase and sell their artwork.</p>
            </div>
            <div class="info-card">
                <i class="fas fa-comments"></i>
                <h3>Build Connections</h3>
                <p>Connect artists and collectors worldwide.</p>
            </div>
        </div>
    </section>

    <!-- HOW IT WORKS -->
    <section style="margin-top:60px;">
        <h2 class="section-title">How It Works</h2>
        <div class="info-grid">
            <div class="info-card">
                <i class="fas fa-user-plus"></i>
                <h3>1. Create Account</h3>
                <p>Register as a collector or artist.</p>
            </div>
            <div class="info-card">
                <i class="fas fa-upload"></i>
                <h3>2. Share Artwork</h3>
                <p>Artists upload and showcase their work.</p>
            </div>
            <div class="info-card">
                <i class="fas fa-shopping-cart"></i>
                <h3>3. Buy & Connect</h3>
                <p>Collectors browse and purchase artwork.</p>
            </div>
        </div>
    </section>

    <!-- FEATURES -->
    <section class="features">
        <div class="feature">
            <i class="fas fa-store"></i>
            <h3>Marketplace</h3>
            <p>Buy and sell original artwork securely.</p>
        </div>
        <div class="feature">
            <i class="fas fa-users"></i>
            <h3>Community</h3>
            <p>Join a growing network of artists and collectors.</p>
        </div>
        <div class="feature">
            <i class="fas fa-star"></i>
            <h3>Quality Art</h3>
            <p>Discover carefully curated artwork.</p>
        </div>
    </section>

    <!-- CTA -->
    <section class="cta-section">
        <h2>Ready to Join the Art Community?</h2>
        <p>Start your artistic journey today.</p>
        <?php if (isset($_SESSION['id'])): ?>
            <a href="artist/dashboard.php" class="hero-btn">
                <i class="fas fa-tachometer-alt"></i>
                Go to Dashboard
            </a>
        <?php else: ?>
            <a href="register.php" class="hero-btn">
                <i class="fas fa-user-plus"></i>
                Create Account
            </a>
        <?php endif; ?>
    </section>

    <!-- FOOTER -->
    <footer>
        <i class="fas fa-seedling"></i>
        Inspired by Monet • Art • Creativity • Community
    </footer>

</div>

</body>
</html>