<?php
include("../auth.php");
include("../conexao.php");
include("auth_check.php");
include("admin/includes/db.php");

$id = (int)($_GET['id'] ?? 0);
$s  = $_GET['s'] ?? '';

$allowed = ['novo','contactado','qualificado','agendado','negociacao','fechado','perdido'];
if ($id <= 0 || !in_array($s, $allowed, true)) die("Parâmetros inválidos.");

$stmt = mysqli_prepare($conexao, "UPDATE leads SET status=? WHERE id=? LIMIT 1");
mysqli_stmt_bind_param($stmt, "si", $s, $id);
mysqli_stmt_execute($stmt);

header("Location: leads.php");
exit;