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

echo json_encode([
    'status' => 'ok',
    'redirect' => is_admin() ? url('admin/dashboard.php') : url('public/dashboard.php'),
]);
