<?php
require_once __DIR__ . '/../../app/core/bootstrap.php';
require_admin();

require_once __DIR__ . '/send_whatsapp.php';

function runFollowUps($conexao) {

    $sql = "
        SELECT * FROM leads
        WHERE status NOT IN ('fechado','perdido')
        AND (proximo_followup IS NULL OR proximo_followup <= NOW())
        LIMIT 50
    ";

    $res = mysqli_query($conexao, $sql);

    while ($lead = mysqli_fetch_assoc($res)) {

        $msg = "Olá 👋 ainda tens interesse nos carros da RG Auto Sales?";

        sendWhatsApp($lead['telefone'], $msg);

        mysqli_query($conexao, "
            UPDATE leads 
            SET proximo_followup = DATE_ADD(NOW(), INTERVAL 2 DAY)
            WHERE id = {$lead['id']}
        ");
    }
}
