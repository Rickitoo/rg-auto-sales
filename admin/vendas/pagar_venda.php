<?php
require_once __DIR__ . '/../../app/core/bootstrap.php';
require_admin();

if ($_SESSION['user']['role'] !== 'admin') {
    redirect_to('auth/login.php');
    exit();
}

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    die("ID inválido.");
}

/* ===============================
   BUSCAR VENDA
=============================== */
$stmt = mysqli_prepare($conexao, "
    SELECT * FROM vendas WHERE id = ?
");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

if (!$res || mysqli_num_rows($res) === 0) {
    die("Venda não encontrada.");
}

$venda = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

/* ===============================
   SE JÁ ESTÁ PAGA
=============================== */
if ($venda['status'] === 'PAGO') {
    die("Esta venda já foi paga.");
}

/* ===============================
   MARCAR COMO PAGO
=============================== */
$user = current_user();
$admin = $user['nome'] ?? 'admin';

$stmt = mysqli_prepare($conexao, "
    UPDATE vendas 
    SET status = 'PAGO',
        data_pagamento = NOW(),
        pago_por = ?
    WHERE id = ?
");

mysqli_stmt_bind_param($stmt, "si", $admin, $id);

if (mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);

    redirect_to('admin/dashboard.php?msg=pago_ok');
} else {
    die("Erro ao processar pagamento.");
}
