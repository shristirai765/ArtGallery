<?php
session_start();
include 'config/db.php';

if(isset($_POST['login']))
{
    $role = $_POST['role'];
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $result = $conn->query(
        "SELECT * FROM users
         WHERE email='$email'
         AND role='$role'"
    );

    if($result && $result->num_rows > 0)
    {
        $user = $result->fetch_assoc();

        if(password_verify($password, $user['password']))
        {
            $_SESSION['id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            switch($user['role'])
            {
                case 'admin':
                    header("Location: admin/dashboard.php");
                    break;

                case 'artist':
                    header("Location: artist/dashboard.php");
                    break;

                case 'user':
                    header("Location: customer/dashboard.php");
                    break;
            }

            exit();
        }
        else
        {
            $error = "Incorrect password.";
        }
    }
    else
    {
        $error = "Account not found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login | Monet's Atelier</title>

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

.login-card{
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
    transition:.3s;
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

.register-link{
    text-align:center;
    margin-top:20px;
}

.register-link a{
    color:var(--monet-deep);
    text-decoration:none;
    font-weight:600;
}

.register-link a:hover{
    text-decoration:underline;
}

</style>
</head>
<body>

<div class="login-card">

    <div class="logo">
        <i class="fas fa-palette"></i>
        <h1>Monet's Atelier</h1>
    </div>

    <p class="subtitle">
        Welcome back. Sign in to continue.
    </p>

    <?php if(isset($error)): ?>
        <div class="error">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <form method="POST">

        <div class="form-group">
            <label>Account Type</label>
            <select name="role" required>
                <option value="">Select Role</option>
                <option value="user">Customer</option>
                <option value="artist">Artist</option>
                <option value="admin">Admin</option>
            </select>
        </div>

        <div class="form-group">
            <label>Email Address</label>
            <input type="email" name="email" required>
        </div>

        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" required>
        </div>

        <button type="submit" name="login" class="btn">
            Login
        </button>

    </form>

    <div class="register-link">
        Don't have an account?
        <a href="register.php">Create Account</a>
    </div>

</div>

</body>
</html>