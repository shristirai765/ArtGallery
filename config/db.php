<?php

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "monetart";

$conn = new mysqli($host,$user,$pass,$dbname);

if($conn->connect_error){
    die("Connection Failed");
}
?>