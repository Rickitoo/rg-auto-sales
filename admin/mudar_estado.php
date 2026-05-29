<?php
require_once __DIR__ . '/../app/core/bootstrap.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_to('admin/leads/leads.php?msg=metodo_invalido');
}

$csrfToken = $_POST['csrf_token'] ?? '';
if (
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $csrfToken)
) {
    http_response_code(403);
    exit('CSRF inválido.');
}

$id = intval($_POST['id'] ?? 0);
$estado = $_POST['estado'] ?? '';
$allowedEstados = ['novo', 'negociacao'];

if ($id <= 0 || !in_array($estado, $allowedEstados, true)) {
    http_response_code(400);
    exit('Parâmetros inválidos.');
}

$stmt = mysqli_prepare($conexao, "UPDATE clientes SET estado = ? WHERE id = ?");
mysqli_stmt_bind_param($stmt, "si", $estado, $id);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

redirect_to('admin/leads/leads.php');
