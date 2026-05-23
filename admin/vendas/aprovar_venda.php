<?php
require_once __DIR__ . '/../../app/core/bootstrap.php';
require_admin();

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) die("ID inválido.");

$stmt = mysqli_prepare($conexao, "
    UPDATE vendas 
    SET precisa_aprovacao = 0, status='APROVADO' 
    WHERE id=?
");

mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

redirect_to('admin/aprovacoes.php');