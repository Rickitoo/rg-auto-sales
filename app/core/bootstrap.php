<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/helpers/upload_security.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/auth.php';

define('BASE_PATH', dirname(__DIR__, 2));
define('ROOT_PATH', BASE_PATH);

require_once BASE_PATH . '/app/modules/cars/helpers.php';
require_once BASE_PATH . '/app/modules/sales/commissions.php';
require_once BASE_PATH . '/app/modules/finance/helpers.php';

require_once BASE_PATH . '/app/core/auth/session.php';

if (file_exists(BASE_PATH . '/app/core/helpers/security.php')) {
    require_once BASE_PATH . '/app/core/helpers/security.php';
}

auth_sync_legacy_session();
csrf_token();

$sessionTimeout = 1800;

if (isset($_SESSION['ultimo_acesso']) && time() - $_SESSION['ultimo_acesso'] > $sessionTimeout) {
    $_SESSION = [];

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    session_destroy();
    redirect_to('auth/login.php?expirado=1');
}

$_SESSION['ultimo_acesso'] = time();
