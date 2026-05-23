<?php
require_once __DIR__ . '/../../app/core/bootstrap.php';
require_admin();

if ($_SESSION['user']['role'] !== 'admin') {
    redirect_to('auth/login.php');
    exit();
}

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    exit("ID inválido");
}

// buscar lead
$res = mysqli_query($conexao, "SELECT tentativas_followup FROM leads WHERE id=$id LIMIT 1");
$lead = mysqli_fetch_assoc($res);

if (!$lead) {
    exit("Lead não encontrado");
}

$tentativas = (int)$lead['tentativas_followup'];

// ==========================
// LÓGICA INTELIGENTE
// ==========================
if ($tentativas == 0) {
    $next = date('Y-m-d H:i:s', strtotime('+1 day'));
} elseif ($tentativas == 1) {
    $next = date('Y-m-d H:i:s', strtotime('+3 days'));
} else {
    $next = date('Y-m-d H:i:s', strtotime('+7 days'));
}
if ($tentativas == 0) {
    $msg = "Olá {$nome}, só a confirmar se ainda tens interesse no carro.";
} elseif ($tentativas == 1) {
    $msg = "Ainda tenho o carro disponível. Queres que te envie mais detalhes?";
} else {
    $msg = "Última oportunidade antes de fechar com outro cliente.";
}

// ==========================
// UPDATE
// ==========================
$stmt = mysqli_prepare($conexao, "
    UPDATE leads 
    SET 
        tentativas_followup = tentativas_followup + 1,
        proximo_followup = ?,
        status = 'contactado'
    WHERE id = ?
");

mysqli_stmt_bind_param($stmt, "si", $next, $id);
mysqli_stmt_execute($stmt);

mysqli_query($conexao, "
INSERT INTO lead_interacoes (lead_id, tipo, mensagem)
VALUES ($id, 'sistema', 'Follow-up realizado automaticamente')
");

// voltar para leads
redirect_to('admin/leads/leads.php');