<?php
require_once(__DIR__ . "/../init.php");

if (!isset($_SESSION['admin'])) {
    header("Location: /RG_AUTO_SALES/login.php");
    exit();
}

// limpar sessão
$_SESSION = [];

// apagar cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"] ?? '',
        $params["secure"],
        $params["httponly"]
    );
}

session_destroy();

header("Location: /RG_AUTO_SALES/login.php?logout=1");
exit;