<?php
session_start();

if(!isset($_SESSION['id']) || $_SESSION['role'] != 'artist')
{
    header("Location: ../login.php");
    exit();
}

include '../config/db.php';

$artist_id = $_SESSION['id'];

$totalArtworks = $conn->query("
    SELECT COUNT(*) AS total
    FROM artworks
    WHERE artist_id = '$artist_id'
")->fetch_assoc()['total'];

$artworks = $conn->query("
    SELECT *
    FROM artworks
    WHERE artist_id = '$artist_id'
    ORDER BY id DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Artist Dashboard | Monet's Atelier</title>

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

/* HEADER */

.header{
    margin-top:20px;
    background:white;
    padding:20px 30px;
    border-radius:20px;
    box-shadow:var(--shadow);

    display:flex;
    justify-content:space-between;
    align-items:center;
    flex-wrap:wrap;
}

.logo h2{
    color:var(--monet-deep);
}

.logo small{
    color:#7f8c8d;
}

.header-actions{
    display:flex;
    gap:12px;
}

.profile-btn,
.logout-btn{
    text-decoration:none;
    color:white;
    padding:12px 18px;
    border-radius:12px;
    font-weight:600;
    transition:.3s;
}

.profile-btn{
    background:#2980b9;
}

.logout-btn{
    background:#c0392b;
}

.profile-btn:hover{
    background:#216694;
}

.logout-btn:hover{
    background:#a5281b;
}

/* HERO */

.hero{
    margin:30px 0;
    padding:50px;
    border-radius:25px;
    background:linear-gradient(145deg,#e8ddd2,#d6c8bb);
    box-shadow:var(--shadow);
}

.hero h1{
    color:var(--monet-deep);
    margin-bottom:10px;
}

.hero p{
    color:#5d6d75;
    line-height:1.8;
}

.upload-btn{
    display:inline-block;
    margin-top:20px;
    text-decoration:none;
    background:var(--monet-deep);
    color:white;
    padding:14px 22px;
    border-radius:12px;
    font-weight:600;
}

/* STATS */

.stats{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(250px,1fr));
    gap:20px;
    margin-bottom:35px;
}

.stat-card{
    background:white;
    padding:25px;
    border-radius:20px;
    text-align:center;
    box-shadow:var(--shadow);
}

.stat-card i{
    font-size:2rem;
    color:var(--monet-gold);
    margin-bottom:10px;
}

.stat-card h2{
    color:var(--monet-deep);
    margin-bottom:5px;
}

/* SECTION TITLE */

.section-title{
    margin-bottom:20px;
    color:var(--monet-deep);
}

/* ARTWORK GRID */

.grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(300px,1fr));
    gap:25px;
    margin-bottom:40px;
}

.card{
    background:white;
    border-radius:20px;
    overflow:hidden;
    box-shadow:var(--shadow);
    transition:.3s;
}

.card:hover{
    transform:translateY(-6px);
}

.card img{
    width:100%;
    height:250px;
    object-fit:cover;
}

.content{
    padding:20px;
}

.content h3{
    color:var(--monet-deep);
    margin-bottom:10px;
}

.description{
    color:#7f8c8d;
    line-height:1.6;
}

.price{
    color:var(--monet-gold);
    font-weight:700;
    font-size:1.1rem;
    margin:15px 0;
}

.status{
    display:inline-block;
    background:#d4edda;
    color:#155724;
    padding:6px 12px;
    border-radius:20px;
    font-size:.85rem;
    font-weight:600;
}

.actions{
    margin-top:20px;
    display:flex;
    gap:10px;
}

.actions a{
    flex:1;
    text-align:center;
    text-decoration:none;
    color:white;
    padding:10px;
    border-radius:10px;
    font-weight:600;
}

.edit{
    background:#2980b9;
}

.delete{
    background:#c0392b;
}

/* EMPTY STATE */

.empty-state{
    background:white;
    padding:60px;
    text-align:center;
    border-radius:20px;
    box-shadow:var(--shadow);
}

.empty-state i{
    font-size:4rem;
    color:var(--monet-gold);
    margin-bottom:20px;
}

.empty-state h2{
    margin-bottom:10px;
}

/* MOBILE */

@media(max-width:768px){

    .header{
        flex-direction:column;
        gap:15px;
        text-align:center;
    }

    .hero{
        padding:30px;
        text-align:center;
    }

    .header-actions{
        width:100%;
        justify-content:center;
    }
}

</style>
</head>
<body>

<div class="container">

    <!-- HEADER -->

    <div class="header">

        <div class="logo">
            <h2>
                <i class="fas fa-palette"></i>
                Monet's Atelier
            </h2>
            <small>Artist Dashboard</small>
        </div>

        <div class="header-actions">

            <a href="profile.php" class="profile-btn">
                <i class="fas fa-user-circle"></i>
                Profile
            </a>

            <a href="../logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                Logout
            </a>

        </div>

    </div>

    <!-- HERO -->

    <div class="hero">

        <h1>
            Welcome,
            <?php echo htmlspecialchars($_SESSION['username']); ?>
        </h1>

        <p>
            Manage your portfolio, upload new artwork,
            and showcase your creativity to collectors worldwide.
        </p>

        <a href="add_artwork.php" class="upload-btn">
            <i class="fas fa-plus"></i>
            Upload Artwork
        </a>

    </div>

    <!-- STATS -->

    <div class="stats">

        <div class="stat-card">
            <i class="fas fa-image"></i>
            <h2><?php echo $totalArtworks; ?></h2>
            <p>Total Artworks</p>
        </div>

        <div class="stat-card">
            <i class="fas fa-eye"></i>
            <h2>0</h2>
            <p>Total Views</p>
        </div>

        <div class="stat-card">
            <i class="fas fa-shopping-cart"></i>
            <h2>0</h2>
            <p>Total Sales</p>
        </div>

    </div>

    <!-- ARTWORKS -->

    <h2 class="section-title">
        <i class="fas fa-images"></i>
        My Artworks
    </h2>

    <?php if($artworks->num_rows > 0): ?>

        <div class="grid">

            <?php while($art = $artworks->fetch_assoc()): ?>

                <div class="card">

                    <img src="../uploads/<?php echo $art['image']; ?>">

                    <div class="content">

                        <h3>
                            <?php echo htmlspecialchars($art['title']); ?>
                        </h3>

                        <p class="description">
                            <?php echo substr($art['description'],0,100); ?>...
                        </p>

                        <div class="price">
                            Rs. <?php echo number_format($art['price']); ?>
                        </div>

                        <span class="status">
                            Available
                        </span>

                        <div class="actions">

                            <a href="edit_artwork.php?id=<?php echo $art['id']; ?>"
                               class="edit">
                               <i class="fas fa-edit"></i> Edit
                            </a>

                            <a href="delete_artwork.php?id=<?php echo $art['id']; ?>"
                               class="delete"
                               onclick="return confirm('Delete this artwork?')">
                               <i class="fas fa-trash"></i> Delete
                            </a>

                        </div>

                    </div>

                </div>

            <?php endwhile; ?>

        </div>

    <?php else: ?>

        <div class="empty-state">

            <i class="fas fa-image"></i>

            <h2>No Artworks Yet</h2>

            <p>
                Start building your portfolio by uploading your first artwork.
            </p>

            <br>

            <a href="add_artwork.php" class="upload-btn">
                Upload Artwork
            </a>

        </div>

    <?php endif; ?>

</div>

</body>
</html>