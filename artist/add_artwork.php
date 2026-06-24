<?php
session_start();

if(!isset($_SESSION['id']) || $_SESSION['role'] != 'artist')
{
    header("Location: ../login.php");
    exit();
}

include '../config/db.php';

if(isset($_POST['submit']))
{
    $artist_id = $_SESSION['id'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $price = $_POST['price'];

    $image = $_FILES['image']['name'];
    $tmp = $_FILES['image']['tmp_name'];

    $filename = time() . "_" . $image;

    move_uploaded_file(
        $tmp,
        "../uploads/" . $filename
    );

    $conn->query("
        INSERT INTO artworks
        (artist_id,title,description,price,image)
        VALUES
        ('$artist_id','$title','$description','$price','$filename')
    ");

    $success = "Artwork uploaded successfully!";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Add Artwork | Monet's Atelier</title>

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
    min-height:100vh;
}

.container{
    width:90%;
    max-width:900px;
    margin:40px auto;
}

.card{
    background:white;
    border-radius:25px;
    box-shadow:var(--shadow);
    overflow:hidden;
}

.header{
    background:linear-gradient(145deg,#e8ddd2,#d6c8bb);
    padding:35px;
    text-align:center;
}

.header h1{
    color:var(--monet-deep);
    margin-bottom:10px;
}

.header p{
    color:#5f7078;
}

.form-area{
    padding:35px;
}

.form-group{
    margin-bottom:20px;
}

.form-group label{
    display:block;
    margin-bottom:8px;
    font-weight:600;
    color:var(--monet-deep);
}

.form-group input,
.form-group textarea{
    width:100%;
    padding:14px;
    border:1px solid #ddd;
    border-radius:12px;
    font-size:15px;
}

.form-group textarea{
    min-height:150px;
    resize:vertical;
}

.form-group input:focus,
.form-group textarea:focus{
    outline:none;
    border-color:var(--monet-lily);
}

.file-input{
    padding:12px;
    background:#fafafa;
}

.actions{
    display:flex;
    gap:15px;
    flex-wrap:wrap;
}

.submit-btn{
    flex:1;
    background:var(--monet-deep);
    color:white;
    border:none;
    padding:15px;
    border-radius:12px;
    font-size:16px;
    font-weight:600;
    cursor:pointer;
}

.submit-btn:hover{
    background:#203845;
}

.back-btn{
    flex:1;
    text-align:center;
    text-decoration:none;
    background:#7f8c8d;
    color:white;
    padding:15px;
    border-radius:12px;
    font-weight:600;
}

.success{
    background:#d4edda;
    color:#155724;
    padding:15px;
    border-radius:12px;
    margin-bottom:20px;
}

.icon{
    font-size:3rem;
    color:var(--monet-gold);
    margin-bottom:10px;
}

</style>
</head>
<body>

<div class="container">

    <div class="card">

        <div class="header">

            <div class="icon">
                <i class="fas fa-palette"></i>
            </div>

            <h1>Upload New Artwork</h1>

            <p>
                Share your creativity with collectors and art lovers.
            </p>

        </div>

        <div class="form-area">

            <?php if(isset($success)): ?>
                <div class="success">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">

                <div class="form-group">
                    <label>Artwork Title</label>
                    <input
                        type="text"
                        name="title"
                        required
                    >
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea
                        name="description"
                        required
                    ></textarea>
                </div>

                <div class="form-group">
                    <label>Price (Rs.)</label>
                    <input
                        type="number"
                        name="price"
                        min="0"
                        required
                    >
                </div>

                <div class="form-group">
                    <label>Artwork Image</label>
                    <input
                        type="file"
                        name="image"
                        class="file-input"
                        accept="image/*"
                        required
                    >
                </div>

                <div class="actions">

                    <button
                        type="submit"
                        name="submit"
                        class="submit-btn"
                    >
                        <i class="fas fa-upload"></i>
                        Upload Artwork
                    </button>

                    <a
                        href="dashboard.php"
                        class="back-btn"
                    >
                        <i class="fas fa-arrow-left"></i>
                        Dashboard
                    </a>

                </div>

            </form>

        </div>

    </div>

</div>

</body>
</html>