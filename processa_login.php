<?php
session_start();
include("includes/db.php");

$username = $_POST['username'];
$password = $_POST['password'];

$sql = "SELECT * FROM users WHERE username='$username' LIMIT 1";
$res = mysqli_query($conn, $sql);

if($res && mysqli_num_rows($res) > 0){
    $user = mysqli_fetch_assoc($res);

    if(password_verify($password, $user['password'])){
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];

        echo "ok";
    } else {
        echo "senha_errada";
    }
}else{
    echo "nao_encontrado";
}