<?php

if (!function_exists('h')) {
    function h($v): string
    {
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('app_base_url')) {
function app_base_url(): string
{
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $parts = array_values(array_filter(explode('/', $script), 'strlen'));
    $entryDirs = ['admin', 'app', 'actions', 'auth', 'public', 'views', 'storage'];

    foreach ($parts as $index => $part) {
        if (in_array($part, $entryDirs, true)) {
            return $index === 0 ? '' : '/' . implode('/', array_slice($parts, 0, $index));
        }
    }

    return count($parts) > 1 ? '/' . $parts[0] : '';
}
}

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        return rtrim(app_base_url(), '/') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('public_url')) {
    function public_url(string $path = ''): string
    {
        return url('public/' . ltrim($path, '/'));
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        return public_url('assets/' . ltrim($path, '/'));
    }
}

if (!function_exists('redirect_to')) {
    function redirect_to(string $path): void
    {
        header('Location: ' . url($path));
        exit;
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('csrf_input')) {
    function csrf_input(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . h(csrf_token()) . '">';
    }
}

if (!function_exists('csrf_verify')) {
    function csrf_verify(?string $token): bool
    {
        return is_string($token) && hash_equals(csrf_token(), $token);
    }
}

if (!function_exists('require_post_csrf')) {
    function require_post_csrf(string $tokenField = 'csrf_token'): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            http_response_code(405);
            exit('Metodo invalido');
        }

        $token = $_POST[$tokenField] ?? null;
        if (!csrf_verify(is_string($token) ? $token : null)) {
            http_response_code(403);
            exit('CSRF invalido');
        }
    }
}

if (!function_exists('db_table_exists')) {
    function db_table_exists(mysqli $conexao, string $table): bool
    {
        $table = mysqli_real_escape_string($conexao, $table);

        $sql = "SHOW TABLES LIKE '$table'";
        $result = mysqli_query($conexao, $sql);

        return $result && mysqli_num_rows($result) > 0;
    }
}
