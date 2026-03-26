<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['admin_logado'])) {
    header("Location: /RG_AUTO_SALES/login.php");
    exit;
}

// Gera token CSRF (se ainda não existir)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
