<?php
require_once __DIR__ . '/../../app/core/bootstrap.php';
require_admin();

// segurança
if ($_SESSION['user']['role'] !== 'admin') {
    redirect_to('auth/login.php');
    exit();
}
// garante conexão
if (!isset($conexao)) {
    die("Erro: conexão não inicializada");
}

$id = (int)($_GET['id'] ?? 0);
$status = $_GET['s'] ?? '';

$allowed = [
    'novo',
    'contactado',
    'qualificado',
    'agendado',
    'negociacao',
    'fechado',
    'perdido'
];

if ($id <= 0 || !in_array($status, $allowed, true)) {
    die("Parâmetros inválidos.");
}

$stmt = mysqli_prepare($conexao, "UPDATE leads SET status=? WHERE id=? LIMIT 1");

if (!$stmt) {
    die("Erro na query: " . mysqli_error($conexao));
}

mysqli_stmt_bind_param($stmt, "si", $status, $id);
mysqli_stmt_execute($stmt);

redirect_to('admin/leads/listar_leads.php');
exit;
?>