<?php
include("auth.php"); // protege
ini_set('display_errors', 1);
error_reporting(E_ALL);

include("conexao.php");

$id = intval($_GET['id'] ?? 0);
$token = $_GET['token'] ?? '';

if ($id <= 0) {
    die("ID inválido.");
}

if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
    die("Ação bloqueada (token inválido).");
}

$stmt = mysqli_prepare($conexao, "DELETE FROM clientes WHERE id = ?");
if (!$stmt) {
    die("Erro ao preparar: " . mysqli_error($conexao));
}

mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

$apagou = (mysqli_stmt_affected_rows($stmt) > 0);

mysqli_stmt_close($stmt);
mysqli_close($conexao);

if ($apagou) {
    header("Location: admin.php?msg=apagado");
    exit;
} else {
    header("Location: admin.php?msg=nao_encontrado");
    exit;
}

