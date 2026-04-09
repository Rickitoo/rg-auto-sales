<?php
// admin/lead_move.php
include("../auth.php");
include("../conexao.php");
include("auth_check.php");
include("admin/includes/db.php");

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok' => false, 'error' => 'Método inválido']);
  exit;
}

$id = (int)($_POST['id'] ?? 0);
$status = $_POST['status'] ?? '';

$allowed = ['novo','contactado','qualificado','agendado','negociacao','fechado','perdido'];

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

echo json_encode(['ok' => true, 'id' => $id, 'status' => $status]);

// Depois de atualizar o status...
if ($status === 'fechado') {
  echo json_encode([
    'ok' => true,
    'redirect' => 'confirmar_venda.php?lead_id=' . $id
  ]);
  exit;
}

echo json_encode(['ok' => true, 'id' => $id, 'status' => $status]);
exit;