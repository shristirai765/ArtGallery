<?php
session_start();

if(!isset($_SESSION['id']) || $_SESSION['role'] != 'admin')
{
    header("Location: ../login.php");
    exit();
}

include '../config/db.php';

$totalUsers =
$conn->query("SELECT COUNT(*) total FROM users WHERE role='user'")
->fetch_assoc()['total'];

$totalArtists =
$conn->query("SELECT COUNT(*) total FROM users WHERE role='artist'")
->fetch_assoc()['total'];

$totalArtworks =
$conn->query("SELECT COUNT(*) total FROM artworks")
->fetch_assoc()['total'];

$recentArtworks =
$conn->query("
SELECT artworks.*, users.name artist
FROM artworks
JOIN users ON artworks.artist_id = users.id
ORDER BY artworks.id DESC
LIMIT 6
");
?>
<!DOCTYPE html>
<html>
<head>
<title>Admin Dashboard</title>

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

<style>

body{
    background:#f5efe9;
    font-family:'Quicksand',sans-serif;
    margin:0;
}

.container{
    width:90%;
    margin:auto;
}

.header{
    margin-top:20px;
    background:white;
    padding:20px;
    border-radius:20px;
    display:flex;
    justify-content:space-between;
    box-shadow:0 10px 25px rgba(0,0,0,.08);
}

.hero{
    margin:30px 0;
    background:linear-gradient(145deg,#e8ddd2,#d6c8bb);
    padding:40px;
    border-radius:25px;
}

.stats{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
    gap:20px;
}

.stat{
    background:white;
    padding:30px;
    border-radius:20px;
    text-align:center;
    box-shadow:0 10px 25px rgba(0,0,0,.08);
}

.stat h2{
    color:#2c4b5a;
}

.grid{
    margin-top:30px;
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(280px,1fr));
    gap:20px;
}

.card{
    background:white;
    border-radius:20px;
    overflow:hidden;
    box-shadow:0 10px 25px rgba(0,0,0,.08);
}

.card img{
    width:100%;
    height:220px;
    object-fit:cover;
}

.content{
    padding:20px;
}
</style>
</head>
<body>

<div class="container">

<div class="header">
    <h2>🛡 Admin Dashboard</h2>
    <a href="../logout.php">Logout</a>
</div>

<div class="hero">
    <h1>Welcome Admin</h1>
    <p>Manage artists, users and artworks.</p>
</div>

<div class="stats">

    <div class="stat">
        <h2><?php echo $totalUsers; ?></h2>
        <p>Customers</p>
    </div>

    <div class="stat">
        <h2><?php echo $totalArtists; ?></h2>
        <p>Artists</p>
    </div>

    <div class="stat">
        <h2><?php echo $totalArtworks; ?></h2>
        <p>Artworks</p>
    </div>

</div>

<h2 style="margin-top:40px;">Recent Artworks</h2>

<div class="grid">

<?php while($art = $recentArtworks->fetch_assoc()): ?>

<div class="card">

    <img src="../uploads/<?php echo $art['image']; ?>">

    <div class="content">

        <h3><?php echo $art['title']; ?></h3>

        <p>Artist: <?php echo $art['artist']; ?></p>

        <p>Rs. <?php echo $art['price']; ?></p>

    </div>

</div>

<?php endwhile; ?>

</div>

</div>

</body>
</html>