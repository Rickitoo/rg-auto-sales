<?php
require_once __DIR__ . '/../../core/bootstrap.php';
require_admin();

if ($_SESSION['user']['role'] !== 'admin') {
    redirect_to('auth/login.php');
    exit();
}

if (!function_exists('h')) {
function h($s){
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}
}

// ==========================
// PERÍODO (MÊS ATUAL)
// ==========================
$inicio = date('Y-m-01');
$fim = date('Y-m-t');

// ==========================
// KPI PRINCIPAL
// ==========================
$total = (int)mysqli_fetch_row(mysqli_query($conexao,"
    SELECT COUNT(*) FROM leads
    WHERE criado_em BETWEEN '$inicio' AND '$fim'
"))[0];

$fechados = (int)mysqli_fetch_row(mysqli_query($conexao,"
    SELECT COUNT(*) FROM leads
    WHERE status='fechado'
    AND criado_em BETWEEN '$inicio' AND '$fim'
"))[0];

// conversão
$conv = $total > 0 ? round(($fechados / $total) * 100, 2) : 0;

// ==========================
// ORIGEM (QUALIDADE)
// ==========================
$porOrigem = mysqli_query($conexao, "
    SELECT origem,
           COUNT(*) AS total,
           SUM(status='fechado') AS fechados
    FROM leads
    WHERE criado_em BETWEEN '$inicio' AND '$fim'
    GROUP BY origem
    ORDER BY total DESC
");

// ==========================
// VENDAS REAIS (IMPORTANTE)
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
?>
<div class="row g-3 mb-3">

<div class="col-md-3">
<div class="bg-white p-3 rounded shadow-sm">
<div class="text-muted">Leads</div>
<h4><?=$total?></h4>
</div>
</div>

<div class="col-md-3">
<div class="bg-white p-3 rounded shadow-sm">
<div class="text-muted">Conversão</div>
<h4><?=$conv?>%</h4>
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
<h4><?=number_format($lucro,2,',','.')?> MT</h4>
</div>
</div>

</div>
