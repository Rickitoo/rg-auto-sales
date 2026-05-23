<?php
require_once __DIR__ . '/../../../app/core/bootstrap.php';
require_admin();

function calculateLeadScore($lead) {

    $score = 0;

    // estágio
    switch($lead['stage']) {
        case 'novo': $score += 10; break;
        case 'contactado': $score += 20; break;
        case 'qualificado': $score += 40; break;
        case 'negociacao': $score += 70; break;
        case 'proposta': $score += 85; break;
        case 'fechado': $score += 100; break;
    }

    // tempo sem resposta
    $hours = (time() - strtotime($lead['updated_at'])) / 3600;

    if ($hours > 24) $score += 30;
    if ($hours > 72) $score += 50;

    // follow-up vencido
    if (!empty($lead['next_followup']) && strtotime($lead['next_followup']) <= time()) {
        $score += 40;
    }

    return min($score, 100);
}