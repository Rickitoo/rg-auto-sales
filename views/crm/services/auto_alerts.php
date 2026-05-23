<?php
require_once __DIR__ . '/../../../app/core/bootstrap.php';
require_admin();

require_once __DIR__ . '/lead_engine.php';

function getHotLeads($conexao) {

    $res = mysqli_query($conexao, "SELECT * FROM leads");

    $hot = [];

    while($lead = mysqli_fetch_assoc($res)) {

        $score = calculateLeadScore($lead);

        if ($score >= 70) {
            $lead['score'] = $score;
            $hot[] = $lead;
        }
    }

    return $hot;
}
