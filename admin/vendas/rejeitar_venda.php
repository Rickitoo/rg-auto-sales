<?php
require_once __DIR__ . '/../../app/core/bootstrap.php';
require_admin();

if ($_SESSION['user']['role'] !== 'admin') {
    redirect_to('auth/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_to('admin/aprovacoes.php?msg=metodo_invalido');
}

$csrfToken = $_POST['csrf_token'] ?? '';
if (
    !is_string($csrfToken) ||
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $csrfToken)
) {
    redirect_to('admin/aprovacoes.php?msg=csrf_invalido');
}

$id = (int)($_POST['id'] ?? 0);

if ($id <= 0) die("ID inválido.");

$stmt = mysqli_prepare($conexao, "
    UPDATE vendas 
    SET status='REJEITADO' 
    WHERE id=?
");

mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

redirect_to('admin/aprovacoes.php');
