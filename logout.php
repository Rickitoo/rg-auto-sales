<?php
session_start();

// Limpa todas variáveis de sessão
$_SESSION = [];

// Apaga o cookie da sessão (importante)
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

// Destrói sessão
session_destroy();

// Redireciona
header("Location: login.php");
exit;