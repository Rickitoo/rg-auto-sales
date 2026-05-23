<?php
require_once __DIR__ . '/../app/core/bootstrap.php';
require_admin();

$data = file_get_contents("php://input");
$json = json_decode($data, true);

// exemplo simplificado
$telefone = $json['messages'][0]['from'];
$mensagem = $json['messages'][0]['text']['body'];

// 1. verificar se lead existe
// 2. criar ou atualizar lead
// 3. guardar mensagem


$stmt = $pdo->prepare("SELECT id FROM leads WHERE telefone = ?");
$stmt->execute([$telefone]);
$lead = $stmt->fetch();

if (!$lead) {
    $stmt = $pdo->prepare("INSERT INTO leads (telefone, status, origem) VALUES (?, 'novo', 'whatsapp')");
    $stmt->execute([$telefone]);
    $lead_id = $pdo->lastInsertId();
} else {
    $lead_id = $lead['id'];
}

$stmt = $pdo->prepare("INSERT INTO mensagens_whatsapp (lead_id, mensagem, tipo) VALUES (?, ?, 'entrada')");
$stmt->execute([$lead_id, $mensagem]);

echo "OK";