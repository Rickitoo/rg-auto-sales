<?php
require_once __DIR__ . '/../../../app/core/bootstrap.php';
require_admin();

require_once __DIR__ . '/send_whatsapp.php';

function autoFollowUp($conexao, $lead) {

    $hours = (time() - strtotime($lead['updated_at'])) / 3600;

    // 🔥 LEAD QUENTE
    if ($lead['score'] >= 70 && $hours > 6) {

        sendWhatsApp(
            $lead['telefone'],
            "🔥 Ainda está disponível o carro que viste. Quer reservar hoje?"
        );

        updateStage($conexao, $lead['id'], 'negociacao');
    }

    // 😴 LEAD FRIO
    if ($hours > 24 && $lead['stage'] == 'contactado') {

        sendWhatsApp(
            $lead['telefone'],
            "Olá 👋 só a confirmar se ainda estás interessado nos carros da RG Auto Sales."
        );
    }

    // 🧊 LEAD MUITO FRIO
    if ($hours > 72) {

        sendWhatsApp(
            $lead['telefone'],
            "Temos novas opções de carros a entrar hoje 🚗 Queres ver?"
        );
    }
}
