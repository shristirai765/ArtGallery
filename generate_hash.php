<?php
// generate_hash.php - Run this once to get the correct hash for your password
$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);
echo "Password: " . $password . "<br>";
echo "Hash: " . $hash;
?>