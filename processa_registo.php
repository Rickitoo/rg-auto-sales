<?php
include("includes/db.php");

$username = $_POST['username'];
$email = $_POST['email'];
$password = password_hash($_POST['password'], PASSWORD_DEFAULT);

$sql = "INSERT INTO users (username, email, password)
VALUES ('$username','$email','$password')";

if(mysqli_query($conn, $sql)){
    echo "ok";
}else{
    echo "erro";
}