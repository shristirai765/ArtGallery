<?php
session_start();

include '../config/db.php';

$user = $_SESSION['id'];
$artwork = $_GET['id'];

$check = $conn->query("SELECT * FROM cart
WHERE user_id='$user' AND artwork_id='$artwork'");

if($check->num_rows==0){

    $conn->query("INSERT INTO cart(user_id,artwork_id)
    VALUES('$user','$artwork')");
}

header("Location: dashboard.php");
?>