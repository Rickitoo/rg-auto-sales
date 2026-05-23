<?php
require_once __DIR__ . '/../../../app/core/bootstrap.php';
require_admin();

require_once __DIR__ . '/lead_engine.php';
require_once __DIR__ . '/send_whatsapp.php';

function processIncomingLead($conexao, $lead, $message) {

    $score = calculateLeadScore($lead);

    // atualizar score
    mysqli_query($conexao, "
        UPDATE leads 
        SET score=$score, updated_at=NOW()
        WHERE id={$lead['id']}
    ");

    // 1. RESPOSTA AUTOMÁTICA INICIAL
    if ($lead['stage'] == 'novo') {

        sendWhatsApp(
            $lead['telefone'],
            "Olá 👋 bem-vindo à RG Auto Sales.\n\nQual é o teu orçamento e tipo de carro que procuras?"
        );

        updateStage($conexao, $lead['id'], 'contactado');
    }

    // 2. QUALIFICAÇÃO AUTOMÁTICA
    if (stripos($message, 'orçamento') !== false) {

        sendWhatsApp(
            $lead['telefone'],
            "Perfeito 👍 vou te mostrar opções dentro do teu orçamento. Preferes sedan, SUV ou compacto?"
        );

        updateStage($conexao, $lead['id'], 'qualificado');
    }

    // 3. SUGESTÃO AUTOMÁTICA DE CARROS
    if ($lead['stage'] == 'qualificado') {

        sendWhatsApp(
            $lead['telefone'],
            "Tenho estas opções para ti 👇\n\n🚗 Toyota Ractis\n🚗 Honda Fit\n🚗 Toyota Vitz\n\nQual queres ver primeiro?"
        );
    }

    // 4. FOLLOW-UP INTELIGENTE
    autoFollowUp($conexao, $lead);

}
