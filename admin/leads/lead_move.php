<?php
require_once __DIR__ . '/../../app/core/bootstrap.php';
require_admin();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Metodo invalido']);
    exit;
}

$id = (int)($_POST['id'] ?? 0);
$status = $_POST['status'] ?? '';
$allowed = ['novo','contactado','qualificado','agendado','negociacao','fechado','perdido'];

if ($id <= 0 || !in_array($status, $allowed, true)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Parametros invalidos']);
    exit;
}

$stmt = mysqli_prepare($conexao, "UPDATE leads SET status = ?, atualizado_em = NOW() WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, 'si', $status, $id);
$ok = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

if (!$ok) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Nao foi possivel atualizar o lead']);
    exit;
}

$response = ['ok' => true, 'id' => $id, 'status' => $status];

if ($status === 'fechado') {
    $response['redirect'] = url('admin/vendas/nova_venda.php?lead_id=' . $id);
}

echo json_encode($response);
