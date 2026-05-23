<?php
require_once __DIR__ . '/../../../core/bootstrap.php';
require_admin();

ini_set('display_errors', 1);
error_reporting(E_ALL);


// Segurança: só POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Método inválido.");
}

$id = intval($_POST['id'] ?? 0);
$token = $_POST['token'] ?? '';

if ($id <= 0) {
    die("ID inválido.");
}

// CSRF
if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
    die("Ação bloqueada (token inválido).");
}

// Soft delete
$stmt = mysqli_prepare($conexao, "UPDATE clientes SET status='inativo' WHERE id = ?");
if (!$stmt) {
    die("Erro ao preparar: " . mysqli_error($conexao));
}

mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

$afetou = (mysqli_stmt_affected_rows($stmt) > 0);

mysqli_stmt_close($stmt);
mysqli_close($conexao);

if ($afetou) {
    redirect_to('admin/admin.php?msg=desativado');
} else {
    redirect_to('admin/admin.php?msg=erro');
}
exit;

