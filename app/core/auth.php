<?php

function is_logged_in(): bool
{
    return isset($_SESSION['user']) && is_array($_SESSION['user']);
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_admin(): bool
{
    return is_logged_in()
        && isset($_SESSION['user']['role'])
        && $_SESSION['user']['role'] === 'admin'
        && (!isset($_SESSION['user']['ativo']) || (int)$_SESSION['user']['ativo'] === 1);
}

function require_login(): void
{
    if (!is_logged_in()) {
        $next = $_SERVER['REQUEST_URI'] ?? '';
        $loginPath = 'auth/login.php';

        if ($next !== '') {
            $loginPath .= '?next=' . urlencode($next);
        }

        redirect_to($loginPath);
    }
}

function require_admin(): void
{
    require_login();

    if (!is_admin()) {
        http_response_code(403);
        die("Acesso negado.");
    }
}
