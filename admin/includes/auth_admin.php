<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifica se é admin
if (!isset($_SESSION['admin_logado']) || $_SESSION['admin_logado'] !== true) {
    header("Location: /RG_AUTO_SALES/login.php");
    exit;
}

// Expiração da sessão (30 min)
$tempo_max = 1800;

if (isset($_SESSION['ultimo_acesso']) && (time() - $_SESSION['ultimo_acesso']) > $tempo_max) {
    session_unset();
    session_destroy();
    header("Location: /RG_AUTO_SALES/login.php");
    exit;
}

$_SESSION['ultimo_acesso'] = time();

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>