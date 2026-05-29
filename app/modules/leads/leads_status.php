<?php
require_once __DIR__ . '/../../core/bootstrap.php';
require_admin();

// segurança
if ($_SESSION['user']['role'] !== 'admin') {
    redirect_to('auth/login.php');
    exit();
}
// garante conexão
if (!isset($conexao)) {
    die("Erro: conexão não inicializada");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_to('admin/leads/listar_leads.php?msg=metodo_invalido');
}

$csrfToken = $_POST['csrf_token'] ?? '';
if (
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $csrfToken)
) {
    http_response_code(403);
    exit('CSRF inválido.');
}

$id = (int)($_POST['lead_id'] ?? 0);
$status = $_POST['status'] ?? '';

$allowed = [
    'novo',
    'contactado',
    'qualificado',
    'agendado',
    'orcamento',
    'aguardando_opcoes',
    'negociacao',
    'pagamento',
    'embarcado',
    'em_transito',
    'desalfandegamento',
    'entregue',
    'fechado',
    'perdido'
];

if ($id <= 0 || !in_array($status, $allowed, true)) {
    die("Parâmetros inválidos.");
}

$stmt = mysqli_prepare($conexao, "UPDATE leads SET status=? WHERE id=? LIMIT 1");

if (!$stmt) {
    die("Nao foi possivel atualizar o lead.");
}

mysqli_stmt_bind_param($stmt, "si", $status, $id);
mysqli_stmt_execute($stmt);

redirect_to('admin/leads/listar_leads.php');
exit;
?>
