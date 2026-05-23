<?php
require_once __DIR__ . '/../app/core/bootstrap.php';
require_admin();

if ($_SESSION['user']['role'] !== 'admin') {
    redirect_to('auth/login.php');
    exit();
}

$lead_id = (int)($_POST['lead_id'] ?? 0);
$msg = trim($_POST['mensagem'] ?? '');

if ($lead_id <= 0 || $msg == '') {
    exit("Dados inválidos");
}

$stmt = mysqli_prepare($conexao, "
    INSERT INTO lead_interacoes (lead_id, tipo, mensagem)
    VALUES (?, 'nota', ?)
");

mysqli_stmt_bind_param($stmt, "is", $lead_id, $msg);
mysqli_stmt_execute($stmt);

redirect_to('admin/leads/ver_lead.php?id=' . $lead_id);