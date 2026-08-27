<?php
// create_admin.php - Run this once to create admin user
include 'config/db.php';

$username = 'admin';
$email = 'admin@monetsatelier.com';
$password = 'admin123';
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$role = 'admin';
$bio = 'System Administrator';

// Check if admin already exists
$check = $conn->query("SELECT id, email FROM users WHERE username = '$username' OR email = '$email'");

if ($check->num_rows > 0) {
    echo "⚠️ Admin user already exists!<br><br>";
    $admin = $check->fetch_assoc();
    echo "Admin ID: " . $admin['id'] . "<br>";
    echo "Email: " . $admin['email'] . "<br>";
    echo "Username: admin<br>";
    echo "Password: admin123<br><br>";
    echo "🔗 <a href='login.php'>Go to Login Page</a>";
} else {
    $stmt = $conn->prepare("
        INSERT INTO users (username, email, password, role, bio, last_activity) 
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param("sssss", $username, $email, $hashed_password, $role, $bio);
    
    if ($stmt->execute()) {
        echo "✅ Admin user created successfully!<br><br>";
        echo "📋 Login Credentials:<br>";
        echo "Username: <strong>admin</strong><br>";
        echo "Email: <strong>admin@monetsatelier.com</strong><br>";
        echo "Password: <strong>admin123</strong><br>";
        echo "Role: <strong>Admin</strong><br><br>";
        echo "🔗 <a href='login.php'>Go to Login Page</a>";
    } else {
        echo "❌ Error creating admin: " . $conn->error;
    }
}
?>