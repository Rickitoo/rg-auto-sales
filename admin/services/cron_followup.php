<?php
require_once __DIR__ . '/../../app/core/bootstrap.php';
require_admin();

// buscar leads que precisam de ação
$res = mysqli_query($conexao, "
    SELECT * FROM leads
    WHERE status NOT IN ('fechado','perdido')
    AND proximo_followup <= NOW()
    LIMIT 50
");

while($lead = mysqli_fetch_assoc($res)) {

    $nome = $lead['nome'];
    $telefone = preg_replace('/[^0-9]/','',$lead['telefone']);
    $lead_id = $lead['id'];

    // escolher mensagem automaticamente
    $msg = "Olá $nome, estou a dar seguimento ao seu interesse no carro.";

    if ($lead['status'] == 'novo') {
        $msg = "Olá $nome, recebeu a minha mensagem anterior?";
    }

    if ($lead['status'] == 'negociacao') {
        $msg = "Tenho uma proposta especial para si hoje.";
    }

    // guardar no CRM
    $stmt = mysqli_prepare($conexao, "
        INSERT INTO mensagens (lead_id, mensagem, tipo)
        VALUES (?, ?, 'enviada')
    ");
    mysqli_stmt_bind_param($stmt, "is", $lead_id, $msg);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // reagendar follow-up (24h depois)
    $stmt = mysqli_prepare($conexao, "
        UPDATE leads 
        SET proximo_followup = DATE_ADD(NOW(), INTERVAL 1 DAY)
        WHERE id=?
    ");
    mysqli_stmt_bind_param($stmt, "i", $lead_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // 🔥 LINK WHATSAPP (manual trigger depois)
    echo "Lead {$lead_id} pronto para contacto<br>";
}