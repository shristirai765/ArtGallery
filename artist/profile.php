<?php
session_start();

if(!isset($_SESSION['id']) || $_SESSION['role'] != 'artist')
{
    header("Location: ../login.php");
    exit();
}

include '../config/db.php';

$id = $_SESSION['id'];

$user = $conn->query("
    SELECT *
    FROM users
    WHERE id='$id'
")->fetch_assoc();

$totalArtworks = $conn->query("
    SELECT COUNT(*) AS total
    FROM artworks
    WHERE artist_id='$id'
")->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Artist Profile | Monet's Atelier</title>

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
    max-width:1200px;
    margin:auto;
}

/* Banner */

.banner{
    margin-top:25px;
    height:220px;
    border-radius:30px;
    background:linear-gradient(135deg,#e8ddd2,#d6c8bb);
    box-shadow:var(--shadow);
}

/* Profile Card */

.profile-card{
    background:white;
    margin-top:-80px;
    border-radius:30px;
    padding:40px;
    box-shadow:var(--shadow);
}

.profile-top{
    display:flex;
    flex-wrap:wrap;
    gap:30px;
    align-items:center;
}

.avatar{
    width:170px;
    height:170px;
    border-radius:50%;
    overflow:hidden;
    border:6px solid white;
    box-shadow:var(--shadow);
}

.avatar img{
    width:100%;
    height:100%;
    object-fit:cover;
}

.avatar-placeholder{
    width:100%;
    height:100%;
    background:var(--monet-lily);
    display:flex;
    align-items:center;
    justify-content:center;
    color:white;
    font-size:4rem;
}

.info{
    flex:1;
}

.info h1{
    color:var(--monet-deep);
    margin-bottom:10px;
}

.info p{
    color:#6c7c84;
    margin-bottom:8px;
}

/* Buttons */

.buttons{
    margin-top:20px;
    display:flex;
    gap:15px;
    flex-wrap:wrap;
}

.btn{
    text-decoration:none;
    padding:12px 20px;
    border-radius:12px;
    font-weight:600;
    transition:.3s;
}

.edit-btn{
    background:var(--monet-deep);
    color:white;
}

.edit-btn:hover{
    background:#1f3945;
}

.dashboard-btn{
    background:#7f8c8d;
    color:white;
}

.dashboard-btn:hover{
    background:#667273;
}

/* Stats */

.stats{
    margin-top:35px;
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
    gap:20px;
}

.stat{
    background:#fff;
    padding:25px;
    text-align:center;
    border-radius:20px;
    box-shadow:var(--shadow);
}

.stat i{
    font-size:2rem;
    color:var(--monet-gold);
    margin-bottom:10px;
}

.stat h2{
    color:var(--monet-deep);
}

/* Bio */

.bio-section{
    margin-top:35px;
    background:white;
    padding:30px;
    border-radius:25px;
    box-shadow:var(--shadow);
}

.bio-section h2{
    color:var(--monet-deep);
    margin-bottom:15px;
}

.bio-section p{
    color:#5f6f77;
    line-height:1.8;
}

@media(max-width:768px){

    .profile-top{
        flex-direction:column;
        text-align:center;
    }

    .buttons{
        justify-content:center;
    }

    .profile-card{
        padding:25px;
    }
}

</style>
</head>
<body>

<div class="container">

    <div class="banner"></div>

    <div class="profile-card">

        <div class="profile-top">

            <div class="avatar">

                <?php if(!empty($user['profile_image'])): ?>

                    <img src="../uploads/<?php echo $user['profile_image']; ?>">

                <?php else: ?>

                    <div class="avatar-placeholder">
                        <i class="fas fa-user"></i>
                    </div>

                <?php endif; ?>

            </div>

            <div class="info">

                <h1>
                    <?php echo htmlspecialchars($user['username']); ?>
                </h1>

                <p>
                    <i class="fas fa-envelope"></i>
                    <?php echo htmlspecialchars($user['email']); ?>
                </p>

                <p>
                    <i class="fas fa-paint-brush"></i>
                    Artist Account
                </p>

                <div class="buttons">

                    <a href="edit_profile.php" class="btn edit-btn">
                        <i class="fas fa-user-edit"></i>
                        Edit Profile
                    </a>

                    <a href="dashboard.php" class="btn dashboard-btn">
                        <i class="fas fa-arrow-left"></i>
                        Dashboard
                    </a>

                </div>

            </div>

        </div>

        <div class="stats">

            <div class="stat">
                <i class="fas fa-image"></i>
                <h2><?php echo $totalArtworks; ?></h2>
                <p>Artworks</p>
            </div>

            <div class="stat">
                <i class="fas fa-eye"></i>
                <h2>0</h2>
                <p>Views</p>
            </div>

            <div class="stat">
                <i class="fas fa-shopping-cart"></i>
                <h2>0</h2>
                <p>Sales</p>
            </div>

        </div>

    </div>

    <div class="bio-section">

        <h2>
            <i class="fas fa-feather-alt"></i>
            About the Artist
        </h2>

        <p>

            <?php
            if(!empty($user['bio']))
            {
                echo nl2br(htmlspecialchars($user['bio']));
            }
            else
            {
                echo "No artist biography has been added yet.";
            }
            ?>

        </p>

    </div>

</div>

</body>
</html>