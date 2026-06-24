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

if(isset($_POST['update']))
{
    $name = $_POST['name'];
    $email = $_POST['email'];
    $bio = $_POST['bio'];

    $profile_image = $user['profile_image'];

    if(!empty($_FILES['profile_image']['name']))
    {
        $image = time().'_'.$_FILES['profile_image']['name'];

        move_uploaded_file(
            $_FILES['profile_image']['tmp_name'],
            "../uploads/".$image
        );

        $profile_image = $image;
    }

    $conn->query("
        UPDATE users
        SET
        username='$name',
        email='$email',
        bio='$bio',
        profile_image='$profile_image'
        WHERE id='$id'
    ");

    if(!empty($_POST['password']))
    {
        $password = password_hash(
            $_POST['password'],
            PASSWORD_DEFAULT
        );

        $conn->query("
            UPDATE users
            SET password='$password'
            WHERE id='$id'
        ");
    }

    $_SESSION['name'] = $name;

    $success = "Profile updated successfully.";

    $user = $conn->query("
        SELECT *
        FROM users
        WHERE id='$id'
    ")->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Edit Profile | Monet's Atelier</title>

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
    max-width:900px;
    margin:40px auto;
}

.card{
    background:white;
    border-radius:30px;
    overflow:hidden;
    box-shadow:var(--shadow);
}

.header{
    background:linear-gradient(145deg,#e8ddd2,#d6c8bb);
    padding:35px;
    text-align:center;
}

.header h1{
    color:var(--monet-deep);
}

.form-section{
    padding:35px;
}

.profile-preview{
    text-align:center;
    margin-bottom:30px;
}

.avatar{
    width:140px;
    height:140px;
    border-radius:50%;
    overflow:hidden;
    margin:auto;
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
    justify-content:center;
    align-items:center;
    color:white;
    font-size:3rem;
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

.success{
    background:#d4edda;
    color:#155724;
    padding:15px;
    border-radius:12px;
    margin-bottom:20px;
}

.actions{
    display:flex;
    gap:15px;
    flex-wrap:wrap;
}

.save-btn{
    flex:1;
    border:none;
    background:var(--monet-deep);
    color:white;
    padding:15px;
    border-radius:12px;
    font-weight:600;
    cursor:pointer;
}

.back-btn{
    flex:1;
    text-decoration:none;
    text-align:center;
    background:#7f8c8d;
    color:white;
    padding:15px;
    border-radius:12px;
    font-weight:600;
}

.save-btn:hover{
    background:#203845;
}

.back-btn:hover{
    background:#6c7778;
}

</style>
</head>
<body>

<div class="container">

    <div class="card">

        <div class="header">
            <h1>
                <i class="fas fa-user-edit"></i>
                Edit Artist Profile
            </h1>
        </div>

        <div class="form-section">

            <?php if(isset($success)): ?>
                <div class="success">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <div class="profile-preview">

                <div class="avatar">

                    <?php if(!empty($user['profile_image'])): ?>

                        <img src="../uploads/<?php echo $user['profile_image']; ?>">

                    <?php else: ?>

                        <div class="avatar-placeholder">
                            <i class="fas fa-user"></i>
                        </div>

                    <?php endif; ?>

                </div>

            </div>

            <form method="POST" enctype="multipart/form-data">

                <div class="form-group">
                    <label>Full Name</label>
                    <input
                        type="text"
                        name="name"
                        value="<?php echo htmlspecialchars($user['username']); ?>"
                        required>
                </div>

                <div class="form-group">
                    <label>Email Address</label>
                    <input
                        type="email"
                        name="email"
                        value="<?php echo htmlspecialchars($user['email']); ?>"
                        required>
                </div>

                <div class="form-group">
                    <label>Artist Bio</label>
                    <textarea
                        name="bio"><?php echo htmlspecialchars($user['bio']); ?></textarea>
                </div>

                <div class="form-group">
                    <label>Profile Image</label>
                    <input
                        type="file"
                        name="profile_image"
                        accept="image/*">
                </div>

                <div class="form-group">
                    <label>New Password (Optional)</label>
                    <input
                        type="password"
                        name="password"
                        placeholder="Leave blank to keep current password">
                </div>

                <div class="actions">

                    <button
                        type="submit"
                        name="update"
                        class="save-btn">

                        <i class="fas fa-save"></i>
                        Save Changes

                    </button>

                    <a
                        href="profile.php"
                        class="back-btn">

                        <i class="fas fa-arrow-left"></i>
                        Back to Profile

                    </a>

                </div>

            </form>

        </div>

    </div>

</div>

</body>
</html>