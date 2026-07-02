<?php
// check_online.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    echo json_encode(['online' => false]);
    exit();
}

include "../config/db.php";

$loggedUser = (int)$_SESSION['id'];
$otherUser = isset($_GET['user']) ? (int)$_GET['user'] : 0;

if ($otherUser === 0) {
    echo json_encode(['online' => false]);
    exit();
}

$stmt = $conn->prepare("SELECT last_activity FROM users WHERE id = ?");
$stmt->bind_param("i", $otherUser);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $lastActivity = strtotime($row['last_activity']);
    $currentTime = time();
    $isOnline = ($currentTime - $lastActivity) < (5 * 60);
    
    echo json_encode(['online' => $isOnline]);
} else {
    echo json_encode(['online' => false]);
}
?>