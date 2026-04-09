<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include("admin/includes/db.php");

// Verifica login
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: /RG_AUTO_SALES/account.php");
    exit;
}

// Expiração da sessão (30 min)
$tempo_max = 1800;

if (isset($_SESSION['ultimo_acesso']) && (time() - $_SESSION['ultimo_acesso']) > $tempo_max) {
    session_unset();
    session_destroy();
    header("Location: /RG_AUTO_SALES/account.php");
    exit;
}

$_SESSION['ultimo_acesso'] = time();
?>