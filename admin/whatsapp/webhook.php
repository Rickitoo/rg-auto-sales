<?php
require_once __DIR__ . '/../../app/core/bootstrap.php';
require_admin();

$data = json_decode(file_get_contents("php://input"), true);

// segurança básica
if (!$data) exit;

$phone = $data['messages'][0]['from'] ?? null;
$text  = $data['messages'][0]['text']['body'] ?? '';

if (!$phone) exit;

// 1. ver se lead existe
$stmt = $pdo->prepare("SELECT id FROM leads WHERE telefone = ?");
$stmt->execute([$phone]);
$lead = $stmt->fetch();

if (!$lead) {
    $stmt = $pdo->prepare("
        INSERT INTO leads (telefone, origem, status, stage, updated_at)
        VALUES (?, 'whatsapp', 'novo', 'novo', NOW())
    ");
    $stmt->execute([$phone]);
    $lead_id = $pdo->lastInsertId();
} else {
    $lead_id = $lead['id'];
}

// 2. guardar mensagem
$stmt = $pdo->prepare("
    INSERT INTO mensagens_whatsapp (lead_id, direction, message)
    VALUES (?, 'in', ?)
");
$stmt->execute([$lead_id, $text]);

// 3. atualizar lead
$stmt = $pdo->prepare("
    UPDATE leads 
    SET last_message = ?, updated_at = NOW()
    WHERE id = ?
");
$stmt->execute([$text, $lead_id]);

echo "OK";