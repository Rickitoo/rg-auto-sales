<?php
require_once __DIR__ . '/../../app/core/bootstrap.php';
require_admin();

if ($_SESSION['user']['role'] !== 'admin') {
    redirect_to('auth/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_to('admin/dashboard.php?msg=metodo_invalido');
}

$csrfToken = $_POST['csrf_token'] ?? '';
if (
    !is_string($csrfToken) ||
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $csrfToken)
) {
    redirect_to('admin/dashboard.php?msg=csrf_invalido');
}

$id = (int)($_POST['id'] ?? 0);

function venda_col_exists(mysqli $con, string $table, string $col): bool {
    $table = mysqli_real_escape_string($con, $table);
    $col = mysqli_real_escape_string($con, $col);
    $q = mysqli_query($con, "SHOW COLUMNS FROM `$table` LIKE '$col'");
    return $q && mysqli_num_rows($q) > 0;
}

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

if (function_exists('recalcular_venda')) {
    $calc = recalcular_venda($conexao, $id);
    if (!$calc['ok']) {
        die("Erro ao recalcular venda: " . h($calc['erro'] ?? 'erro desconhecido'));
    }
}

$sets = ["status = 'PAGO'"];
$types = "";
$params = [];

if (venda_col_exists($conexao, 'vendas', 'data_pagamento')) {
    $sets[] = "data_pagamento = NOW()";
}

if (venda_col_exists($conexao, 'vendas', 'pago_por')) {
    $sets[] = "pago_por = ?";
    $types .= "s";
    $params[] = $admin;
}

if (venda_col_exists($conexao, 'vendas', 'pago')) {
    $sets[] = "pago = 1";
}

if (venda_col_exists($conexao, 'vendas', 'status_pagamento')) {
    $sets[] = "status_pagamento = 'PAGO'";
}

$sql = "
    UPDATE vendas
    SET " . implode(", ", $sets) . "
    WHERE id = ?
";

$types .= "i";
$params[] = $id;

$stmt = mysqli_prepare($conexao, $sql);
mysqli_stmt_bind_param($stmt, $types, ...$params);

if (mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);

    redirect_to('admin/dashboard.php?msg=pago_ok');
} else {
    die("Erro ao processar pagamento.");
}
