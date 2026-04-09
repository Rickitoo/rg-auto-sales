<?php
// admin/vendedor_status.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

include("../auth.php"); // remove se não tiveres
include("../conexao.php");
include("auth_check.php");


if (session_status() === PHP_SESSION_NONE) session_start();

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

header("Location: vendedores_pedidos.php?msg=status_ok");
exit;
