<?php
require_once(__DIR__ . "/../init.php");

require_once __DIR__ . "/../conexao.php";
require_once __DIR__ . "/includes/config.php";
require_once __DIR__ . "/includes/auth_admin.php";


// Verifica se está logado
if (!isset($_SESSION['admin'])) {
    header("Location: /RG_AUTO_SALES/login.php");
    exit();
}

// Expiração da sessão (30 min)
$tempo_max = 1800;

if (isset($_SESSION['ultimo_acesso']) && (time() - $_SESSION['ultimo_acesso']) > $tempo_max) {
    session_unset();
    session_destroy();
    header("Location: /RG_AUTO_SALES/login.php");
    exit();
}

$_SESSION['ultimo_acesso'] = time();
?>