<?php
require_once __DIR__ . '/../../app/core/bootstrap.php';
require_admin();
require_post_csrf();

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    $_SESSION['flash'][] = ['type' => 'error', 'message' => 'Parceiro invalido.'];
    redirect_to('admin/parceiros/index.php');
}

$stmt = mysqli_prepare($conexao, "UPDATE parceiros SET estado = 'inativo' WHERE id = ? LIMIT 1");
$ok = false;
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'i', $id);
    $ok = mysqli_stmt_execute($stmt);
}
if ($stmt) {
    mysqli_stmt_close($stmt);
}

$_SESSION['flash'][] = $ok
    ? ['type' => 'success', 'message' => 'Parceiro marcado como inativo.']
    : ['type' => 'error', 'message' => 'Nao foi possivel inativar o parceiro.'];
redirect_to('admin/parceiros/index.php');
