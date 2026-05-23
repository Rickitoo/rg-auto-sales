<?php
require_once __DIR__ . '/../app/core/bootstrap.php';
require_admin();

// =========================
// HELPERS
// =========================
function money($v){ return number_format((float)$v, 2, ',', '.') . " MT"; }
function n($v){ return (int)($v ?? 0); }

// =========================
// DATAS
// =========================
$inicioMes = date('Y-m-01');
$fimMes    = date('Y-m-t'); 

// =========================
// KPIs
// =========================
$totalLeads = n(mysqli_fetch_assoc(mysqli_query($conexao,
    "SELECT COUNT(*) as total FROM leads"
))['total']);

$leadsFechados = n(mysqli_fetch_assoc(mysqli_query($conexao,
    "SELECT COUNT(*) as total FROM leads WHERE status='fechado'"
))['total']);

$taxaConversao = $totalLeads > 0 ? ($leadsFechados / $totalLeads) * 100 : 0;

// =========================
// VENDAS DO MÊS
// =========================
$stmt = mysqli_prepare($conexao, "
    SELECT COUNT(*) as total,
           SUM(CASE WHEN status='PAGO' THEN comissao_rg ELSE 0 END) as lucro
    FROM vendas
    WHERE DATE(data_venda) BETWEEN ? AND ?
");

mysqli_stmt_bind_param($stmt, "ss", $inicioMes, $fimMes);
mysqli_stmt_execute($stmt);
$r = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

$vendasMes = n($r['total']);
$lucroMes  = (float)($r['lucro'] ?? 0);

// =========================
// FOLLOW-UP REAL (ORDENADO POR URGÊNCIA)
// =========================
$leadsFollow = [];
$resFollow = mysqli_query($conexao, "
    SELECT *,
    proximo_contacto AS proximo_followup,
    CASE
        WHEN proximo_contacto IS NULL THEN 1
        WHEN proximo_contacto <= NOW() THEN 2
        ELSE 0
    END as prioridade
    FROM leads
    WHERE status NOT IN ('fechado','perdido')
    ORDER BY prioridade DESC, proximo_contacto ASC, id DESC
    LIMIT 20
");

if ($resFollow) {
    while($row = mysqli_fetch_assoc($resFollow)) {
        $leadsFollow[] = $row;
    }
}

// =========================
// TOP CARROS
// =========================
$topCarros = [];
$resTop = mysqli_query($conexao, "
    SELECT marca, modelo, COUNT(*) as total
    FROM vendas
    GROUP BY marca, modelo
    ORDER BY total DESC
    LIMIT 5
");

while($r = mysqli_fetch_assoc($resTop)){
    $topCarros[] = $r;
}

// =========================
// ALERTAS
// =========================
$alertas = [];

if ($taxaConversao < 10) $alertas[] = "Taxa de conversão baixa";

$pendentes = n(mysqli_fetch_assoc(mysqli_query($conexao,
    "SELECT COUNT(*) as total FROM vendas WHERE status='PENDENTE'"
))['total']);

if ($pendentes > 5) $alertas[] = "Muitas vendas pendentes";

if (count($leadsFollow) > 5) $alertas[] = "Muitos leads sem resposta";

// =========================
// ÚLTIMAS VENDAS
// =========================
$vendas = [];
$resV = mysqli_query($conexao, "
    SELECT v.id, v.marca, v.modelo, v.comissao_rg, v.status, c.nome as cliente
    FROM vendas v
    JOIN clientes c ON c.id = v.cliente_id
    ORDER BY v.id DESC
    LIMIT 5
");

while($row = mysqli_fetch_assoc($resV)){
    $vendas[] = $row;
}

require_once __DIR__ . '/../includes/layout_top.php';
?>

<h2>📊 Dashboard RG Auto Sales</h2>

<!-- KPIs -->
<div style="display:flex;gap:20px;flex-wrap:wrap;">
    <div style="background:#0d6efd;color:#fff;padding:15px;border-radius:10px;">
        <h4>Vendas</h4>
        <p><?= $vendasMes ?></p>
    </div>

    <div style="background:#16a34a;color:#fff;padding:15px;border-radius:10px;">
        <h4>Lucro</h4>
        <p><?= money($lucroMes) ?></p>
    </div>

    <div style="background:#9333ea;color:#fff;padding:15px;border-radius:10px;">
        <h4>Conversão</h4>
        <p><?= number_format($taxaConversao,1) ?>%</p>
    </div>
</div>

<!-- ALERTAS -->
<h3 class="mt-4">⚠️ Alertas</h3>

<?php if(empty($alertas)): ?>
<p>Tudo sob controlo ✔</p>
<?php else: ?>
<?php foreach($alertas as $a): ?>
<div style="color:red;">⚠ <?= h($a) ?></div>
<?php endforeach; ?>
<?php endif; ?>

<!-- FOLLOW-UP -->
<h3 class="mt-4">🔥 Follow-up Prioritário</h3>

<?php foreach($leadsFollow as $l): ?>
<?php
$tel = preg_replace('/[^0-9]/','',$l['telefone']);
$msg = urlencode("Olá {$l['nome']}, estou a dar seguimento ao seu interesse.");
?>

<div style="margin-bottom:8px;">
<strong><?= h($l['nome']) ?></strong>

<a target="_blank"
href="https://wa.me/<?= h(str_starts_with($tel, '258') ? $tel : '258' . ltrim($tel, '0')) ?>?text=<?= $msg ?>">
💬 WhatsApp
</a>

<a href="<?= h(url('admin/leads/ver_lead.php?id=' . (int)$l['id'])) ?>">
👁 Ver
</a>
</div>

<?php endforeach; ?>

<!-- TOP CARROS -->
<h3 class="mt-4">🚗 Top Carros</h3>

<?php foreach($topCarros as $c): ?>
<div><?= h($c['marca'].' '.$c['modelo']) ?> — <?= $c['total'] ?></div>
<?php endforeach; ?>

<!-- VENDAS -->
<h3 class="mt-4">💰 Últimas vendas</h3>

<table border="1" cellpadding="10">
<tr>
<th>Cliente</th>
<th>Carro</th>
<th>Comissão</th>
<th>Status</th>
</tr>

<?php foreach($vendas as $v): ?>
<tr>
<td><?= h($v['cliente']) ?></td>
<td><?= h($v['marca'].' '.$v['modelo']) ?></td>
<td><?= money($v['comissao_rg']) ?></td>
<td>
<?php if($v['status']=='PENDENTE'): ?>
<a href="<?= h(url('admin/vendas/pagar_venda.php?id=' . (int)$v['id'])) ?>">Marcar pago</a>
<?php else: ?>
<a class="btn btn-sm btn-success"
href="<?= h(url('admin/vendas/venda_detalhe.php?id=' . (int)$v['id'])) ?>">PAGO</a>
<?php endif; ?>
</td>
</tr>
<?php endforeach; ?>

</table>

<?php require_once __DIR__ . '/../includes/layout_bottom.php'; ?>
