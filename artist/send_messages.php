<?php
session_start();
include "../config/db.php";

$me = $_SESSION['id'];
$other = (int)$_POST['user'];
$message = trim($_POST['message']);

if ($message != "") {

    $stmt = $conn->prepare("
        INSERT INTO messages (sender_id, receiver_id, message)
        VALUES (?, ?, ?)
    ");

    $stmt->bind_param("iis", $me, $other, $message);
    $stmt->execute();

    echo "success";
}
?>