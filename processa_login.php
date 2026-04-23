<?php
require_once(__DIR__ . "/init.php");
require_once("includes/db.php");

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

$sql = "SELECT * FROM users WHERE username='$username' LIMIT 1";
$res = mysqli_query($conn, $sql);

if($res && mysqli_num_rows($res) > 0){
    $user = mysqli_fetch_assoc($res);

    if(password_verify($password, $user['password'])){

        $_SESSION['admin'] = true;
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['ultimo_acesso'] = time();

        header("Location: admin/dashboard.php");
        exit();
    }
}

// se falhar
header("Location: account.php?erro=1");
exit();
        
        