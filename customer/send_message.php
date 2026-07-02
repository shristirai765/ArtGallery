<?php
session_start();
include "../config/db.php";

if(!isset($_SESSION['id'])){
    die("Not logged in");
}

$user = $_SESSION['id'];

$artist = isset($_POST['artist']) ? (int)$_POST['artist'] : 0;
$art = isset($_POST['art']) ? (int)$_POST['art'] : 0;
$message = trim($_POST['message']);

if($message == ""){
    die("Message is empty");
}

$stmt = $conn->prepare("
    INSERT INTO messages
    (sender_id, receiver_id, artwork_id, message)
    VALUES (?, ?, ?, ?)
");

if(!$stmt){
    die("Prepare failed: ".$conn->error);
}

$stmt->bind_param(
    "iiis",
    $user,
    $artist,
    $art,
    $message
);

if($stmt->execute()){
    echo "success";
}else{
    echo "Execute failed: ".$stmt->error;
}