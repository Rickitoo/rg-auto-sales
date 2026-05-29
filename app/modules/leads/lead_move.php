<?php
require_once __DIR__ . '/../../core/bootstrap.php';
require_admin();

// admin/lead_move.php

if ($_SESSION['user']['role'] !== 'admin') {
    redirect_to('auth/login.php');
    exit();
}


header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok' => false, 'error' => 'Método inválido']);
  exit;
}

$csrfToken = $_POST['csrf_token'] ?? '';
if (
  empty($_SESSION['csrf_token']) ||
  !hash_equals($_SESSION['csrf_token'], $csrfToken)
) {
  http_response_code(403);
  echo json_encode(['ok' => false, 'error' => 'CSRF inválido']);
  exit;
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
    'perdido',
];

if ($id <= 0 || !in_array($status, $allowed, true)) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'Parâmetros inválidos']);
  exit;
}

$stmt = mysqli_prepare($conexao, "UPDATE leads SET status=? WHERE id=? LIMIT 1");
if (!$stmt) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => 'Prepare falhou']);
  exit;
}

mysqli_stmt_bind_param($stmt, "si", $status, $id);

if (!mysqli_stmt_execute($stmt)) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => 'Execute falhou']);
  exit;
}

// Depois de atualizar o status...
if ($status === 'fechado') {
  echo json_encode([
    'ok' => true,
    'redirect' => url('admin/vendas/confirmar_venda.php'),
    'lead_id' => $id
  ]);
  exit;
}

echo json_encode(['ok' => true, 'id' => $id, 'status' => $status]);
exit;
