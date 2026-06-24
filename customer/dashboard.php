<?php
session_start();

if(!isset($_SESSION['id']) || $_SESSION['role'] != 'user')
{
    header("Location: ../login.php");
    exit();
}

include '../config/db.php';

$artworks = $conn->query("
    SELECT artworks.*, users.name AS artist_name
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

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

<link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&display=swap"
rel="stylesheet">

<style>

:root{
    --monet-deep:#2c4b5a;
    --monet-gold:#c9a87c;
    --monet-lily:#7fa3a8;
    --bg:#f5efe9;
    --shadow:0 12px 28px rgba(44,75,90,.12);
}

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Quicksand',sans-serif;
}

body{
    background:var(--bg);
}

.container{
    width:90%;
    max-width:1400px;
    margin:auto;
}

/* Header */

.header{
    margin-top:20px;
    background:white;
    padding:20px 30px;
    border-radius:25px;
    box-shadow:var(--shadow);

    display:flex;
    justify-content:space-between;
    align-items:center;
    flex-wrap:wrap;
}

.logo{
    font-size:1.8rem;
    font-weight:700;
    color:var(--monet-deep);
}

.logo i{
    color:var(--monet-gold);
}

.logout-btn{
    text-decoration:none;
    background:#c0392b;
    color:white;
    padding:12px 20px;
    border-radius:12px;
    font-weight:600;
}

/* Welcome */

.welcome{
    margin:30px 0;
    background:linear-gradient(145deg,#e8ddd2,#d6c8bb);
    padding:50px;
    border-radius:30px;
    box-shadow:var(--shadow);
}

.welcome h1{
    color:var(--monet-deep);
    margin-bottom:10px;
}

.welcome p{
    color:#5e7079;
}

/* Gallery */

.section-title{
    margin-bottom:25px;
    color:var(--monet-deep);
}

.art-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(280px,1fr));
    gap:25px;
}

.art-card{
    background:white;
    border-radius:20px;
    overflow:hidden;
    box-shadow:var(--shadow);
    transition:.3s;
}

.art-card:hover{
    transform:translateY(-5px);
}

.art-card img{
    width:100%;
    height:250px;
    object-fit:cover;
}

.art-content{
    padding:20px;
}

.art-content h3{
    color:var(--monet-deep);
    margin-bottom:10px;
}

.artist{
    color:#7f8c8d;
    margin-bottom:10px;
}

.price{
    color:var(--monet-gold);
    font-size:1.2rem;
    font-weight:700;
    margin-bottom:15px;
}

.view-btn{
    display:block;
    text-align:center;
    text-decoration:none;
    background:var(--monet-deep);
    color:white;
    padding:12px;
    border-radius:10px;
    font-weight:600;
}

.empty{
    text-align:center;
    padding:50px;
    background:white;
    border-radius:20px;
    box-shadow:var(--shadow);
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

        <a href="../logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i>
            Logout
        </a>

    </div>

    <div class="welcome">

        <h1>
            Welcome,
            <?php echo htmlspecialchars($_SESSION['name']); ?>
        </h1>

        <p>
            Explore beautiful artworks from talented artists around the world.
        </p>

    </div>

    <h2 class="section-title">
        <i class="fas fa-image"></i>
        Available Artworks
    </h2>

    <?php if($artworks->num_rows > 0): ?>

        <div class="art-grid">

            <?php while($art = $artworks->fetch_assoc()): ?>

            <div class="art-card">

                <img src="../uploads/<?php echo $art['image']; ?>">

                <div class="art-content">

                    <h3>
                        <?php echo htmlspecialchars($art['title']); ?>
                    </h3>

                    <p class="artist">
                        By <?php echo htmlspecialchars($art['artist_name']); ?>
                    </p>

                    <p class="price">
                        Rs. <?php echo number_format($art['price']); ?>
                    </p>

                    <a href="view_artwork.php?id=<?php echo $art['id']; ?>"
                       class="view-btn">
                        View Artwork
                    </a>

                </div>

            </div>

            <?php endwhile; ?>

        </div>

    <?php else: ?>

        <div class="empty">
            <h3>No artworks available yet.</h3>
        </div>

    <?php endif; ?>

</div>

</body>
</html>