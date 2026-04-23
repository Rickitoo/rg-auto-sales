<?php
require_once(__DIR__ . "/../init.php");

if (!isset($_SESSION['admin'])) {
    header("Location: /RG_AUTO_SALES/login.php");
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
$admin = $_SESSION['username'] ?? 'admin';

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

    header("Location: dashboard.php?msg=pago_ok");
    exit();
} else {
    die("Erro ao processar pagamento.");
}