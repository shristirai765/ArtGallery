<?php
include 'config/db.php';

$artworks = $conn->query("
    SELECT artworks.*,
           users.username AS artist_name
    FROM artworks
    JOIN users
    ON artworks.artist_id = users.id
    ORDER BY artworks.id DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Gallery | Monet's Atelier</title>

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

<link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&display=swap"
rel="stylesheet">

<style>

:root{
    --monet-water:#b5d1d6;
    --monet-lily:#7fa3a8;
    --monet-gold:#c9a87c;
    --monet-deep:#2c4b5a;
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

header{
    margin:20px 0 40px;
    background:linear-gradient(145deg,#e8ddd2,#d6c8bb);
    padding:20px 35px;
    border-radius:60px 20px 60px 20px;
    box-shadow:var(--shadow);

    display:flex;
    justify-content:space-between;
    align-items:center;
    flex-wrap:wrap;
}

.logo{
    font-size:2rem;
    font-weight:700;
    color:var(--monet-deep);
}

.logo i{
    color:var(--monet-gold);
}

.back-btn{
    text-decoration:none;
    background:var(--monet-deep);
    color:white;
    padding:12px 20px;
    border-radius:40px;
    font-weight:600;
}

/* Hero */

.hero{
    text-align:center;
    margin-bottom:50px;
}

.hero h1{
    font-size:3rem;
    color:var(--monet-deep);
}

.hero p{
    margin-top:15px;
    color:#617680;
}

/* Gallery */

.gallery-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(300px,1fr));
    gap:25px;
}

.card{
    background:white;
    border-radius:25px;
    overflow:hidden;
    box-shadow:var(--shadow);
    transition:.3s;
}

.card:hover{
    transform:translateY(-8px);
}

.card img{
    width:100%;
    height:260px;
    object-fit:cover;
}

.content{
    padding:20px;
}

.content h3{
    color:var(--monet-deep);
    margin-bottom:10px;
}

.artist{
    color:var(--monet-lily);
    font-weight:600;
    margin-bottom:10px;
}

.price{
    color:var(--monet-gold);
    font-weight:700;
    font-size:1.1rem;
    margin-top:10px;
}

.view-btn{
    margin-top:15px;
    display:block;
    text-align:center;
    text-decoration:none;
    background:var(--monet-deep);
    color:white;
    padding:12px;
    border-radius:10px;
}

.empty{
    background:white;
    padding:60px;
    border-radius:25px;
    text-align:center;
    box-shadow:var(--shadow);
}

</style>
</head>
<body>

<div class="container">

<header>

    <div class="logo">
        <i class="fas fa-palette"></i>
        Monet's Atelier
    </div>

    <a href="index.php" class="back-btn">
        <i class="fas fa-arrow-left"></i>
        Home
    </a>

</header>

<div class="hero">

    <h1>Explore The Gallery</h1>

    <p>
        Discover original artwork from talented artists around the world.
    </p>

</div>

<?php if($artworks->num_rows > 0): ?>

<div class="gallery-grid">

<?php while($art = $artworks->fetch_assoc()): ?>

<div class="card">

    <img src="uploads/<?php echo $art['image']; ?>">

    <div class="content">

        <h3>
            <?php echo htmlspecialchars($art['title']); ?>
        </h3>

        <p class="artist">
            <i class="fas fa-paint-brush"></i>
            <?php echo htmlspecialchars($art['artist_name']); ?>
        </p>

        <p>
            <?php echo substr($art['description'],0,100); ?>...
        </p>

        <div class="price">
            Rs. <?php echo number_format($art['price']); ?>
        </div>

        <a href="login.php" class="view-btn">
            Login to View Details
        </a>

    </div>

</div>

<?php endwhile; ?>

</div>

<?php else: ?>

<div class="empty">

    <h2>No Artworks Available</h2>

    <p>
        Artists haven't uploaded any artwork yet.
    </p>

</div>

<?php endif; ?>

</div>

</body>
</html>