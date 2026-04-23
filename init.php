<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// CONEXÃO (APENAS UMA VEZ)
require_once(__DIR__ . "/conexao.php");

// 🔐 TIMEOUT
if (isset($_SESSION['ultimo_acesso'])) {
    if (time() - $_SESSION['ultimo_acesso'] > 1800) {
        session_unset();
        session_destroy();
        header("Location: /RG_AUTO_SALES/login.php?expirado=1");
        exit();
    }
}

$_SESSION['ultimo_acesso'] = time();