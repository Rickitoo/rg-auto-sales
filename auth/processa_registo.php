<?php
require_once __DIR__ . '/../app/core/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'erro', 'message' => 'Metodo invalido']);
    exit;
}

auth_ensure_users_table($conexao);

$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($username === '' || $email === '' || $password === '') {
    http_response_code(422);
    echo json_encode(['status' => 'erro', 'message' => 'Preencha todos os campos.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['status' => 'erro', 'message' => 'Email invalido.']);
    exit;
}

if (strlen($password) < 6) {
    http_response_code(422);
    echo json_encode(['status' => 'erro', 'message' => 'A senha deve ter pelo menos 6 caracteres.']);
    exit;
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$role = 'vendedor';

$stmt = mysqli_prepare($conexao, "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
mysqli_stmt_bind_param($stmt, 'ssss', $username, $email, $hash, $role);
$ok = mysqli_stmt_execute($stmt);
$error = mysqli_error($conexao);
mysqli_stmt_close($stmt);

if (!$ok) {
    http_response_code(409);
    echo json_encode(['status' => 'erro', 'message' => str_contains($error, 'Duplicate') ? 'Este email ja existe.' : 'Nao foi possivel criar a conta.']);
    exit;
}

echo json_encode(['status' => 'ok', 'message' => 'Conta criada com sucesso.']);
