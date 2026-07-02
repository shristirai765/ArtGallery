<?php
session_start();

if (!isset($_SESSION['id']) || $_SESSION['role'] != 'artist') {
    header("Location: ../login.php");
    exit();
}

include "../config/db.php";

$artist_id = $_SESSION['id'];

if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$id = (int)$_GET['id'];

$stmt = $conn->prepare("
    SELECT *
    FROM artworks
    WHERE id = ?
    AND artist_id = ?
");

$stmt->bind_param("ii", $id, $artist_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Artwork not found.");
}

$art = $result->fetch_assoc();

if (isset($_POST['update'])) {

    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $price = $_POST['price'];

    $image = $art['image'];

    if (!empty($_FILES['image']['name'])) {

        $newImage = time() . "_" . basename($_FILES['image']['name']);

        move_uploaded_file(
            $_FILES['image']['tmp_name'],
            "../uploads/" . $newImage
        );

        if (
            !empty($art['image']) &&
            file_exists("../uploads/" . $art['image'])
        ) {
            unlink("../uploads/" . $art['image']);
        }

        $image = $newImage;
    }

    $stmt = $conn->prepare("
        UPDATE artworks
        SET
            title=?,
            description=?,
            price=?,
            image=?
        WHERE id=?
        AND artist_id=?
    ");

    $stmt->bind_param(
        "ssdsii",
        $title,
        $description,
        $price,
        $image,
        $id,
        $artist_id
    );

    $stmt->execute();

    header("Location: dashboard.php?updated=1");
    exit();
}
?>

<!DOCTYPE html>
<html>

<head>

<meta charset="UTF-8">

<title>Edit Artwork</title>

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

<style>

body{
    margin:0;
    background:#f5efe9;
    font-family:Arial;
}

.container{
    width:90%;
    max-width:700px;
    margin:40px auto;
}

.card{
    background:white;
    padding:30px;
    border-radius:20px;
    box-shadow:0 10px 25px rgba(0,0,0,.08);
}

h1{
    color:#2c4b5a;
    margin-bottom:25px;
}

label{
    display:block;
    margin-top:20px;
    margin-bottom:8px;
    font-weight:bold;
}

input,
textarea{

    width:100%;
    padding:12px;

    border:1px solid #ddd;

    border-radius:10px;

    font-size:15px;

}

textarea{
    resize:vertical;
    min-height:150px;
}

img{

    width:250px;

    margin-top:15px;

    border-radius:15px;

}

.buttons{

    margin-top:30px;

    display:flex;

    gap:15px;

}

button,
a{

    flex:1;

    text-align:center;

    padding:14px;

    border:none;

    border-radius:10px;

    text-decoration:none;

    color:white;

    font-size:16px;

    cursor:pointer;

}

button{
    background:#2c4b5a;
}

.cancel{
    background:#e74c3c;
}

</style>

</head>

<body>

<div class="container">

<div class="card">

<h1>
<i class="fas fa-edit"></i>
Edit Artwork
</h1>

<form method="POST" enctype="multipart/form-data">

<label>Artwork Title</label>

<input
type="text"
name="title"
value="<?php echo htmlspecialchars($art['title']); ?>"
required>

<label>Description</label>

<textarea
name="description"
required><?php echo htmlspecialchars($art['description']); ?></textarea>

<label>Price (Rs.)</label>

<input
type="number"
name="price"
value="<?php echo $art['price']; ?>"
required>

<label>Current Image</label>

<img src="../uploads/<?php echo htmlspecialchars($art['image']); ?>">

<label>Upload New Image (Optional)</label>

<input
type="file"
name="image"
accept="image/*">

<div class="buttons">

<button
type="submit"
name="update">

<i class="fas fa-save"></i>
Update Artwork

</button>

<a
href="dashboard.php"
class="cancel">

<i class="fas fa-times"></i>
Cancel

</a>

</div>

</form>

</div>

</div>

</body>
</html>