<?php
require_once __DIR__ . '/../../app/core/bootstrap.php';
require_admin();

// admin/vendedor_status.php

if ($_SESSION['user']['role'] !== 'admin') {
    redirect_to('auth/login.php');
    exit();
}
function fail($msg){ die($msg); }

$id    = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$token = $_POST['token'] ?? '';
$status = trim($_POST['status'] ?? '');

$allowed = ['Novo','Em análise','Aprovado','Recusado','Publicado'];

if ($id <= 0) fail("ID inválido.");

if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
  fail("Ação bloqueada (token inválido).");
}

if (!in_array($status, $allowed, true)) {
  fail("Status inválido.");
}

$stmt = mysqli_prepare($conexao, "UPDATE vendedores SET status = ? WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "si", $status, $id);

if (!mysqli_stmt_execute($stmt)) {
  mysqli_stmt_close($stmt);
  fail("Erro ao atualizar: " . mysqli_error($conexao));
}

mysqli_stmt_close($stmt);
mysqli_close($conexao);

redirect_to('admin/vendas/vendedores_pedidos.php?msg=status_ok');
exit;
