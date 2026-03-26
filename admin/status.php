<?php
// admin/status.php
include("../auth.php");
include("../conexao.php");

if (session_status() === PHP_SESSION_NONE) session_start();

function back($msg){
  header("Location: dashboard.php?msg=" . urlencode($msg));
  exit;
}

$id     = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$status = $_POST['status'] ?? '';
$token  = $_POST['token'] ?? '';

$allowed = ['NOVO','CONTACTADO','AGENDADO','CONCLUIDO','CANCELADO'];

if ($id <= 0) back("ID inválido.");
if (!in_array($status, $allowed, true)) back("Status inválido.");
if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) back("Token inválido.");

$stmt = mysqli_prepare($conexao, "UPDATE clientes SET status = ? WHERE id = ?");
if (!$stmt) back("Erro no prepare.");

mysqli_stmt_bind_param($stmt, "si", $status, $id);

if (mysqli_stmt_execute($stmt)) {
  mysqli_stmt_close($stmt);
  back("Status atualizado.");
}

$err = mysqli_error($conexao);
mysqli_stmt_close($stmt);
back("Erro ao atualizar: " . $err);
?>
