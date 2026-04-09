<?php
// admin/status.php
include("../auth.php");
include("../conexao.php");
include("auth_check.php");
include("admin/includes/db.php");

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

$proximo = null;

if ($status == "NOVO") {
    $proximo = date("Y-m-d H:i:s", strtotime("+10 minutes"));
}
elseif ($status == "CONTACTADO") {
    $proximo = date("Y-m-d H:i:s", strtotime("+1 day"));
}
elseif ($status == "AGENDADO") {
    $proximo = date("Y-m-d H:i:s", strtotime("+2 days"));
}

$sql = "UPDATE clientes 
        SET status=?, proximo_followup=? 
        WHERE id=?";
?>
