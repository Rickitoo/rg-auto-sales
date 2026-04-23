<?php
require_once(__DIR__ . "/../init.php");

if (!isset($_SESSION['admin'])) {
    header("Location: /RG_AUTO_SALES/login.php");
    exit();
}

// ===============================
// CONFIG BASE
// ===============================
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function money($v) { return number_format((float)$v, 2, ',', '.') . " MT"; }
function n($v) { return (int)($v ?? 0); }
function h($v){ return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }

// ===============================
// DATAS
// ===============================
$inicioMes = date('Y-m-01');
$fimMes    = date('Y-m-t');
$hoje      = date('Y-m-d');

// ===============================
// KPIs VENDAS (MÊS)
// ===============================
$stmt = mysqli_prepare($conexao, "
    SELECT
        COUNT(*) AS vendas_mes,
        SUM(CASE WHEN status='PAGO' THEN comissao_rg ELSE 0 END) AS comissao_paga
    FROM vendas
    WHERE data_venda BETWEEN ? AND ?
");

mysqli_stmt_bind_param($stmt, "ss", $inicioMes, $fimMes);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$kpi = mysqli_fetch_assoc($res) ?: [];
mysqli_stmt_close($stmt);

$vendasMes = n($kpi['vendas_mes']);
$comissaoMes = (float)($kpi['comissao_paga'] ?? 0);

// ===============================
// LEADS
// ===============================
$res = mysqli_query($conexao, "SELECT COUNT(*) as total FROM clientes");
$leadsTotal = (int)(mysqli_fetch_assoc($res)['total'] ?? 0);

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
";

$resF = mysqli_query($conexao, $sqlFollow);
$leadsFollow = [];
if ($resF) while ($r = mysqli_fetch_assoc($resF)) $leadsFollow[] = $r;

// SCORE
function scoreLead($lead){
    $score = 0;
    if (($lead['estado'] ?? '') === 'negociacao') $score += 50;
    if (!empty($lead['proximo_followup'])) $score += 30;
    if (($lead['status'] ?? '') === 'NOVO') $score += 10;
    return $score;
}

usort($leadsFollow, fn($a,$b)=>scoreLead($b)-scoreLead($a));

// ===============================
// ÚLTIMAS VENDAS
// ===============================
$sqlV = "
SELECT v.id, v.marca, v.modelo, v.comissao, v.status,
       c.nome as cliente
FROM vendas v
JOIN clientes c ON c.id = v.cliente_id
ORDER BY v.id DESC LIMIT 5
";
$resV = mysqli_query($conexao, $sqlV);
$vendas = [];
if ($resV) while ($r = mysqli_fetch_assoc($resV)) $vendas[] = $r;

include("includes/layout_top.php");
?>

<h2>📊 Dashboard Geral</h2>

<div class="grid-3">
    <div class="dash-card">
        <div class="card-body">
            <div class="kpi-title">Vendas do mês</div>
            <div class="kpi-value"><?= $vendasMes ?></div>
        </div>
    </div>

    <div class="dash-card">
        <div class="card-body">
            <div class="kpi-title">Comissão</div>
            <div class="kpi-value"><?= money($comissaoMes) ?></div>
        </div>
    </div>

    <div class="dash-card">
        <div class="card-body">
            <div class="kpi-title">Leads</div>
            <div class="kpi-value"><?= $leadsTotal ?></div>
        </div>
    </div>
</div>

<h3>🔥 Leads Prioritários</h3>

<?php foreach($leadsFollow as $l): ?>
    <?php $tel = preg_replace('/[^0-9]/','',$l['telefone']); ?>

    <div style="margin-bottom:10px;">
        <strong><?= h($l['nome']) ?></strong>
        <a target="_blank"
           href="https://wa.me/258<?= $tel ?>?text=<?= urlencode("Olá {$l['nome']}, estou a dar seguimento ao seu pedido.") ?>">
           🚀 Contactar
        </a>
    </div>
<?php endforeach; ?>

<h3>💰 Últimas vendas</h3>

<table class="table">
<tr><th>Cliente</th><th>Carro</th><th>Comissão</th></tr>
<?php foreach($vendas as $v): ?>
<tr>
<td><?= h($v['cliente']) ?></td>
<td><?= h($v['marca'].' '.$v['modelo']) ?></td>
<td><?= money($v['comissao']) ?></td>
</tr>
<?php endforeach; ?>
<?php if ($v['status'] === 'PENDENTE'): ?>
    <a href="pagar_venda.php?id=<?= $v['id'] ?>">
        💰 Marcar como PAGO
    </a>
<?php else: ?>
    <span>✔ PAGO</span>
    <small>
        (<?= $v['pago_por'] ?? 'N/A' ?>)
    </small>
<?php endif; ?>
</table>

<?php include("includes/layout_bottom.php"); ?>
