<?php
require_once __DIR__ . '/../app/core/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'erro', 'message' => 'Metodo invalido']);
    exit;
}

$login = trim($_POST['username'] ?? $_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$next = $_POST['next'] ?? '';

if ($login === '' || $password === '') {
    http_response_code(422);
    echo json_encode(['status' => 'erro', 'message' => 'Preencha o utilizador e a senha.']);
    exit;
}

$user = auth_find_user_by_login($conexao, $login);

if (!$user || (int)$user['ativo'] !== 1 || !password_verify($password, $user['password'])) {
    http_response_code(401);
    echo json_encode(['status' => 'erro', 'message' => 'Login invalido.']);
    exit;
}

login_user($user);

$safeNext = is_string($next) && str_starts_with($next, app_base_url() . '/') ? $next : '';

echo json_encode([
    'status' => 'ok',
    'redirect' => $safeNext !== '' ? $safeNext : (is_admin() ? url('admin/dashboard.php') : url('public/dashboard.php')),
]);
