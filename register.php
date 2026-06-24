
<?php
include 'config/db.php';

if(isset($_POST['register']))
{
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    $role = $_POST['role'];

    if($password !== $confirmPassword)
    {
        $error = "Passwords do not match.";
    }
    else
    {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO users(username,email,password,role)
                VALUES('$name','$email','$hashedPassword','$role')";

        if($conn->query($sql))
        {
            header("Location: login.php");
            exit();
        }
        else
        {
            $error = "Registration failed.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create Account | Monet's Atelier</title>

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

<link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&display=swap"
rel="stylesheet">

<style>

:root{
    --monet-deep:#2c4b5a;
    --monet-gold:#c9a87c;
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
    display:flex;
    justify-content:center;
    align-items:center;
    padding:20px;
}

.register-card{
    width:100%;
    max-width:500px;
    background:white;
    padding:40px;
    border-radius:30px;
    box-shadow:var(--shadow);
}

.logo{
    text-align:center;
    margin-bottom:20px;
}

.logo i{
    font-size:3rem;
    color:var(--monet-gold);
}

.logo h1{
    margin-top:10px;
    color:var(--monet-deep);
}

.subtitle{
    text-align:center;
    color:#6b7d85;
    margin-bottom:25px;
}

.form-group{
    margin-bottom:18px;
}

.form-group label{
    display:block;
    margin-bottom:8px;
    font-weight:600;
    color:var(--monet-deep);
}

.form-group input,
.form-group select{
    width:100%;
    padding:14px;
    border:1px solid #ddd;
    border-radius:12px;
    font-size:15px;
}

.form-group input:focus,
.form-group select:focus{
    outline:none;
    border-color:var(--monet-gold);
}

.btn{
    width:100%;
    padding:15px;
    border:none;
    border-radius:12px;
    background:var(--monet-deep);
    color:white;
    font-size:16px;
    font-weight:600;
    cursor:pointer;
}

.btn:hover{
    background:#1f3b47;
}

.error{
    background:#ffe5e5;
    color:#c0392b;
    padding:12px;
    border-radius:10px;
    margin-bottom:15px;
}

.login-link{
    text-align:center;
    margin-top:20px;
}

.login-link a{
    color:var(--monet-deep);
    text-decoration:none;
    font-weight:600;
}

</style>
</head>

<body>

<div class="register-card">

    <div class="logo">
        <i class="fas fa-palette"></i>
        <h1>Monet's Atelier</h1>
    </div>

    <p class="subtitle">
        Create your account and join our art community.
    </p>

    <?php if(isset($error)): ?>
        <div class="error">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <form method="POST">

        <div class="form-group">
            <label>Full Name</label>
            <input type="text" name="name" required>
        </div>

        <div class="form-group">
            <label>Email Address</label>
            <input type="email" name="email" required>
        </div>

        <div class="form-group">
            <label>Account Type</label>
            <select name="role" required>
                <option value="">Select Role</option>
                <option value="user">Customer</option>
                <option value="artist">Artist</option>
            </select>
        </div>

        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" required>
        </div>

        <div class="form-group">
            <label>Confirm Password</label>
            <input type="password" name="confirm_password" required>
        </div>

        <button type="submit" name="register" class="btn">
            Create Account
        </button>

    </form>

    <div class="login-link">
        Already have an account?
        <a href="login.php">Login Here</a>
    </div>

</div>

</body>
</html>
