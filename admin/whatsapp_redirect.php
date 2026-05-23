<?php
require_once __DIR__ . '/../app/core/bootstrap.php';
require_admin();

$id = (int)($_GET['id'] ?? 0);

$res = mysqli_query($conexao, "SELECT nome, telefone FROM leads WHERE id=$id");
$l = mysqli_fetch_assoc($res);

if (!$l) exit();

$msg = urlencode("Olá {$l['nome']}, estou a dar seguimento ao seu interesse.");

// registar interação
mysqli_query($conexao, "
INSERT INTO lead_interacoes (lead_id, tipo, mensagem)
VALUES ($id, 'whatsapp', 'Mensagem enviada via WhatsApp')
");

header("Location: https://wa.me/258{$l['telefone']}?text=$msg");
exit();