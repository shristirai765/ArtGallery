<?php
// update_activity.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['id'])) {
    include "../config/db.php";
    $loggedUser = (int)$_SESSION['id'];
    
    $update = $conn->prepare("UPDATE users SET last_activity = NOW() WHERE id = ?");
    $update->bind_param("i", $loggedUser);
    $update->execute();
}
?>