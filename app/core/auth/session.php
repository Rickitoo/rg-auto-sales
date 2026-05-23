<?php

if (!function_exists('auth_sync_legacy_session')) {
    function auth_sync_legacy_session(): void
    {
        if (!isset($_SESSION['user']) && isset($_SESSION['user_id'])) {
            $_SESSION['user'] = [
                'id' => (int)$_SESSION['user_id'],
                'nome' => $_SESSION['username'] ?? 'Utilizador',
                'email' => $_SESSION['email'] ?? '',
                'role' => !empty($_SESSION['admin']) || !empty($_SESSION['admin_logado']) ? 'admin' : 'vendedor',
            ];
        }

        unset($_SESSION['user_id'], $_SESSION['username'], $_SESSION['admin'], $_SESSION['admin_logado']);
    }
}

if (!function_exists('current_user')) {
    function current_user(): ?array
    {
        auth_sync_legacy_session();
        return $_SESSION['user'] ?? null;
    }
}

if (!function_exists('is_logged_in')) {
    function is_logged_in(): bool
    {
        return current_user() !== null;
    }
}

if (!function_exists('is_admin')) {
    function is_admin(): bool
    {
        $user = current_user();
        return $user && ($user['role'] ?? '') === 'admin';
    }
}

if (!function_exists('login_user')) {
    function login_user(array $user): void
    {
        session_regenerate_id(true);

        $_SESSION['user'] = [
            'id' => (int)($user['id'] ?? 0),
            'nome' => $user['nome'] ?? $user['username'] ?? $user['email'] ?? 'Utilizador',
            'email' => $user['email'] ?? '',
            'role' => $user['role'] ?? 'vendedor',
        ];

        $_SESSION['ultimo_acesso'] = time();
        auth_sync_legacy_session();
        csrf_token();
    }
}

if (!function_exists('logout_user')) {
    function logout_user(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', $params['secure'], $params['httponly']);
        }

        session_destroy();
    }
}

if (!function_exists('require_login')) {
    function require_login(): void
    {
        auth_sync_legacy_session();

        if (!is_logged_in()) {
            redirect_to('auth/login.php');
        }

        if (isset($_SESSION['ultimo_acesso']) && time() - (int)$_SESSION['ultimo_acesso'] > 1800) {
            logout_user();
            redirect_to('auth/login.php?expirado=1');
        }

        $_SESSION['ultimo_acesso'] = time();
    }
}

if (!function_exists('require_admin')) {
    function require_admin(): void
    {
        require_login();

        if (!is_admin()) {
            http_response_code(403);
            exit('Acesso negado.');
        }
    }
}

if (!function_exists('auth_ensure_users_table')) {
    function auth_ensure_users_table(mysqli $conexao): void
    {
        mysqli_query($conexao, "
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(100) NOT NULL,
                email VARCHAR(150) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                role ENUM('admin','vendedor') NOT NULL DEFAULT 'vendedor',
                ativo TINYINT(1) NOT NULL DEFAULT 1,
                criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }
}

if (!function_exists('auth_table_columns')) {
    function auth_table_columns(mysqli $conexao, string $table): array
    {
        $columns = [];
        $res = mysqli_query($conexao, "SHOW COLUMNS FROM `$table`");

        while ($res && ($row = mysqli_fetch_assoc($res))) {
            $columns[] = $row['Field'];
        }

        return $columns;
    }
}

if (!function_exists('auth_detect_users_table')) {
    function auth_detect_users_table(mysqli $conexao): string
    {
        if (db_table_exists($conexao, 'users')) {
            return 'users';
        }

        if (db_table_exists($conexao, 'usuarios')) {
            return 'usuarios';
        }

        auth_ensure_users_table($conexao);
        return 'users';
    }
}

if (!function_exists('auth_find_user_by_login')) {
    function auth_find_user_by_login(mysqli $conexao, string $login): ?array
    {
        $table = auth_detect_users_table($conexao);
        $columns = auth_table_columns($conexao, $table);

        $loginColumns = array_values(array_intersect(['email', 'username', 'nome', 'usuario'], $columns));
        if (!$loginColumns) {
            return null;
        }

        $where = implode(' OR ', array_map(fn($column) => "`$column` = ?", $loginColumns));
        $sql = "SELECT * FROM `$table` WHERE $where LIMIT 1";
        $stmt = mysqli_prepare($conexao, $sql);

        $types = str_repeat('s', count($loginColumns));
        $values = array_fill(0, count($loginColumns), $login);
        mysqli_stmt_bind_param($stmt, $types, ...$values);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);
        $row = $result ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($stmt);

        if (!$row) {
            return null;
        }

        return [
            'id' => (int)($row['id'] ?? 0),
            'username' => $row['username'] ?? $row['nome'] ?? $row['usuario'] ?? $row['email'] ?? 'Utilizador',
            'email' => $row['email'] ?? '',
            'password' => $row['password'] ?? $row['senha'] ?? '',
            'role' => $row['role'] ?? $row['perfil'] ?? 'vendedor',
            'ativo' => (int)($row['ativo'] ?? 1),
            'raw' => $row,
        ];
    }
}
