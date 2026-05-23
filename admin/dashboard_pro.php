<?php
require_once __DIR__ . '/../app/core/bootstrap.php';
require_admin();

if ($_SESSION['user']['role'] !== 'admin') {
    redirect_to('auth/login.php');
    exit();
}

function money($v){
    return number_format((float)$v, 2, ',', '.') . " MT";
}

// ==========================
// PERÍODO
// ==========================
$inicio = date('Y-m-01');
$fim = date('Y-m-t');

// ==========================
// LEADS
// ==========================
$total_leads = (int)mysqli_fetch_row(mysqli_query($conexao,"
SELECT COUNT(*) FROM leads
WHERE criado_em BETWEEN '$inicio' AND '$fim'
"))[0];

$fechados = (int)mysqli_fetch_row(mysqli_query($conexao,"
SELECT COUNT(*) FROM leads
WHERE status='fechado'
AND criado_em BETWEEN '$inicio' AND '$fim'
"))[0];

$conversao = $total_leads > 0
? round(($fechados/$total_leads)*100,2)
: 0;

// ==========================
// VENDAS
// ==========================
$vendas = (int)mysqli_fetch_row(mysqli_query($conexao,"
SELECT COUNT(*) FROM vendas
WHERE status='PAGO'
AND data_venda BETWEEN '$inicio' AND '$fim'
"))[0];

$faturamento = (float)mysqli_fetch_row(mysqli_query($conexao,"
SELECT COALESCE(SUM(valor_venda),0)
FROM vendas
WHERE status='PAGO'
AND data_venda BETWEEN '$inicio' AND '$fim'
"))[0];

$lucro = (float)mysqli_fetch_row(mysqli_query($conexao,"
SELECT COALESCE(SUM(lucro),0)
FROM vendas
WHERE status='PAGO'
AND data_venda BETWEEN '$inicio' AND '$fim'
"))[0];

$rg = (float)mysqli_fetch_row(mysqli_query($conexao,"
SELECT COALESCE(SUM(comissao_rg),0)
FROM vendas
WHERE status='PAGO'
AND data_venda BETWEEN '$inicio' AND '$fim'
"))[0];

// ==========================
// ORIGEM MAIS FORTE
// ==========================
$origem = mysqli_query($conexao,"
SELECT origem,
COUNT(*) total,
SUM(status='fechado') fechados
FROM leads
WHERE criado_em BETWEEN '$inicio' AND '$fim'
GROUP BY origem
ORDER BY total DESC
LIMIT 5
");

// ==========================
// CARROS MAIS VENDIDOS
// ==========================
$carros = mysqli_query($conexao,"
SELECT marca, modelo, COUNT(*) total
FROM vendas
WHERE status='PAGO'
AND data_venda BETWEEN '$inicio' AND '$fim'
GROUP BY marca, modelo
ORDER BY total DESC
LIMIT 5
");
?>

<!doctype html>
<html lang="pt">
<head>
<meta charset="utf-8">
<title>Dashboard Pro - RG Auto Sales</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body class="bg-light">

<div class="container py-4">

<h3 class="mb-3">📊 Dashboard Pro</h3>

<!-- KPI PRINCIPAL -->
<div class="row g-3 mb-4">

<div class="col-md-3">
<div class="bg-white p-3 rounded shadow-sm">
<div class="text-muted">Leads</div>
<h4><?=$total_leads?></h4>
</div>
</div>

<div class="col-md-3">
<div class="bg-white p-3 rounded shadow-sm">
<div class="text-muted">Conversão</div>
<h4><?=$conversao?>%</h4>
</div>
</div>

<div class="col-md-3">
<div class="bg-white p-3 rounded shadow-sm">
<div class="text-muted">Vendas</div>
<h4><?=$vendas?></h4>
</div>
</div>

<div class="col-md-3">
<div class="bg-white p-3 rounded shadow-sm">
<div class="text-muted">Lucro</div>
<h4><?=money($lucro)?></h4>
</div>
</div>

</div>

<!-- FINANCEIRO -->
<div class="row g-3 mb-4">

<div class="col-md-6">
<div class="bg-white p-3 rounded shadow-sm">
<div class="text-muted">Faturamento</div>
<h4><?=money($faturamento)?></h4>
</div>
</div>

<div class="col-md-6">
<div class="bg-white p-3 rounded shadow-sm">
<div class="text-muted">Comissão RG</div>
<h4><?=money($rg)?></h4>
</div>
</div>

</div>

<!-- GRÁFICO -->
<div class="bg-white p-3 rounded shadow-sm mb-4">
<h5>📈 Crescimento (simples base mensal)</h5>
<canvas id="chart"></canvas>
</div>

<!-- ORIGEM -->
<div class="row g-3">

<div class="col-md-6">
<div class="bg-white p-3 rounded shadow-sm">
<h5>🔥 Origem dos Leads</h5>
<table class="table">
<tr><th>Origem</th><th>Total</th><th>Fechados</th></tr>
<?php while($o = mysqli_fetch_assoc($origem)): ?>
<tr>
<td><?=$o['origem']?></td>
<td><?=$o['total']?></td>
<td><?=$o['fechados']?></td>
</tr>
<?php endwhile; ?>
</table>
</div>
</div>

<div class="col-md-6">
<div class="bg-white p-3 rounded shadow-sm">
<h5>🚗 Carros Mais Vendidos</h5>
<table class="table">
<tr><th>Carro</th><th>Vendas</th></tr>
<?php while($c = mysqli_fetch_assoc($carros)): ?>
<tr>
<td><?=$c['marca'].' '.$c['modelo']?></td>
<td><?=$c['total']?></td>
</tr>
<?php endwhile; ?>
</table>
</div>
</div>

</div>

</div>

<script>
const ctx = document.getElementById('chart');

new Chart(ctx, {
    type: 'bar',
    data: {
        labels: ['Leads', 'Fechados', 'Vendas'],
        datasets: [{
            label: 'Performance',
            data: [<?=$total_leads?>, <?=$fechados?>, <?=$vendas?>]
        }]
    }
});
</script>

</body>
</html>