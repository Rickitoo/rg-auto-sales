<?php
require_once __DIR__ . '/../app/core/bootstrap.php';
require_admin();

if ($_SESSION['user']['role'] !== 'admin') {
    redirect_to('auth/login.php');
    exit();
}


// CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Helpers
function money($v) { return number_format((float)$v, 2, ',', '.') . " MT"; }
function n($v) { return (int)($v ?? 0); }
function h($v){ return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }

function scoreLead($lead){
    $score = 0;
    if (($lead['estado'] ?? '') === 'negociacao') $score += 50;
    if (!empty($lead['proximo_followup'])) $score += 30;
    if (($lead['status'] ?? '') === 'NOVO') $score += 10;
    return $score;
}

// Datas
$inicioMes = date('Y-m-01');
$fimMes    = date('Y-m-t');
$hoje      = date('Y-m-d');
$diasNoMes = (int)date('t');

// ===============================
// FOLLOW UPS
// ===============================
$hojeAgora = date("Y-m-d H:i:s");

$sqlFollow = "
    SELECT id, nome, telefone, status, estado, proximo_followup
    FROM clientes 
    WHERE proximo_followup IS NOT NULL
    AND proximo_followup <= '$hojeAgora'
    ORDER BY proximo_followup ASC
    LIMIT 10
";

$resF = mysqli_query($conexao, $sqlFollow);
$leadsFollow = [];
if ($resF) while ($r = mysqli_fetch_assoc($resF)) $leadsFollow[] = $r;

// ORDENAR POR SCORE (AGRESSIVO)
usort($leadsFollow, function($a, $b){
    return scoreLead($b) - scoreLead($a);
});

// ===============================
// LEADS NEGOCIAÇÃO
// ===============================
$sqlNegociacao = "
    SELECT id, nome, telefone 
    FROM clientes
    WHERE status='ativo' AND estado='negociacao'
    ORDER BY ultimo_contacto DESC
    LIMIT 5
";
$resNegociacao = mysqli_query($conexao, $sqlNegociacao);
$leadsNegociacao = [];
if ($resNegociacao) while ($r = mysqli_fetch_assoc($resNegociacao)) $leadsNegociacao[] = $r;

// ===============================
// LEADS NOVOS
// ===============================
$sqlNovos = "
    SELECT id, nome, telefone 
    FROM clientes
    WHERE status='ativo' AND estado='novo'
    ORDER BY data_registo DESC
    LIMIT 5
";
$resNovos = mysqli_query($conexao, $sqlNovos);
$leadsNovos = [];
if ($resNovos) while ($r = mysqli_fetch_assoc($resNovos)) $leadsNovos[] = $r;

require_once __DIR__ . '/../includes/layout_top.php';
?>

<div class="dash-card" style="background:#111;color:#fff;">
    <div class="card-body">
        <div class="kpi-title" style="color:#9ca3af;"> MISSÃO DO DIA</div>

        <div class="kpi-value">
            Contactar <?= count($leadsFollow) ?> leads prioritários
        </div>

        <div class="small">
            Foca primeiro nos de maior score
        </div>
    </div>
</div>

<div class="grid-3">

    <!-- FOLLOW-UP PRIORITÁRIO -->
    <div class="dash-card" style="background:#fff3cd;">
        <div class="card-body">
            <div class="kpi-title">🔥 PRIORIDADE MÁXIMA</div>

            <?php foreach ($leadsFollow as $l): ?>
                <?php $tel = preg_replace('/[^0-9]/','',$l['telefone']); ?>

                <div>
                    <strong><?= h($l['nome']) ?></strong><br>

                    <a class="quick-btn quick-success"
                        target="_blank"
                        href="https://wa.me/258<?= $tel ?>?text=<?= urlencode("Olá {$l['nome']}, estou a dar seguimento ao seu interesse em carro. Ainda está disponível para avançar?") ?>">
                        🚀 Fechar agora
                    </a> 
                </div>
                <hr>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- NEGOCIAÇÃO -->
    <div class="dash-card" style="background:#d4edda;">
        <div class="card-body">
            <div class="kpi-title">💰 Quase a fechar</div>

            <?php foreach ($leadsNegociacao as $l): ?>
                <?php $tel = preg_replace('/[^0-9]/','',$l['telefone']); ?>
                <div>
                    <strong><?= h($l['nome']) ?></strong><br>

                    <a target="_blank"
                       href="https://wa.me/258<?= $tel ?>">
                       📲 WhatsApp
                    </a>
                </div>
                <hr>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- NOVOS -->
    <div class="dash-card" style="background:#cce5ff;">
        <div class="card-body">
            <div class="kpi-title">🆕 Entraram agora</div>

            <?php foreach ($leadsNovos as $l): ?>
                <?php $tel = preg_replace('/[^0-9]/','',$l['telefone']); ?>
                <div>
                    <strong><?= h($l['nome']) ?></strong><br>

                    <a target="_blank"
                       href="https://wa.me/258<?= $tel ?>?text=<?= urlencode("Olá {$l['nome']}, recebi o seu pedido. Posso ajudar a encontrar o carro ideal?") ?>">
                       ⚡ Responder rápido
                    </a>
                </div>
                <hr>
            <?php endforeach; ?>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../includes/layout_bottom.php'; ?>
