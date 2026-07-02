<?php
// get_unread_count.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    echo json_encode(['count' => 0]);
    exit();
}

include "../config/db.php";

$loggedUser = (int)$_SESSION['id'];

$result = $conn->query("
    SELECT COUNT(*) as total 
    FROM messages 
    WHERE receiver_id = $loggedUser AND is_read = 0
");

$count = $result->fetch_assoc()['total'];
echo json_encode(['count' => $count]);
?>