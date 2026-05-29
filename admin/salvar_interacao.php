<?php
require_once __DIR__ . '/../app/core/bootstrap.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_to('admin/leads/leads.php?msg=metodo_invalido');
}

if ($_SESSION['user']['role'] !== 'admin') {
    redirect_to('auth/login.php');
    exit();
}

$csrfToken = $_POST['csrf_token'] ?? '';
if (
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $csrfToken)
) {
    http_response_code(403);
    exit('CSRF invalido.');
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
