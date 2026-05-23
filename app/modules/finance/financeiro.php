<?php
require_once __DIR__ . '/../../core/bootstrap.php';
require_admin();

redirect_to('admin/financeiro/dashboard_financeiro.php' . (($_SERVER['QUERY_STRING'] ?? '') !== '' ? '?' . $_SERVER['QUERY_STRING'] : ''));

if ($_SESSION['user']['role'] !== 'admin') {
    redirect_to('auth/login.php');
    exit();
}

if (!function_exists('h')) { function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); } }
function money($v){ return number_format((float)$v, 2, ',', '.'); }

// ===============================
// FILTRO DATA
// ===============================
$mes = (int)($_GET['mes'] ?? date('m'));
$ano = (int)($_GET['ano'] ?? date('Y'));

if ($mes < 1 || $mes > 12) $mes = date('m');
if ($ano < 2000 || $ano > 2100) $ano = date('Y');

$inicio = sprintf('%04d-%02d-01', $ano, $mes);
$fim = date('Y-m-t', strtotime($inicio));

// ===============================
// FUNÇÃO AUXILIAR
// ===============================
function scalar($con, $sql){
    $r = mysqli_query($con, $sql);
    if(!$r) return 0;
    $row = mysqli_fetch_row($r);
    return $row[0] ?? 0;
}

// ===============================
// RESUMO
// ===============================
$total_vendas = scalar($conexao, "SELECT COUNT(*) FROM vendas WHERE data_venda BETWEEN '$inicio' AND '$fim'");
$total_valor_venda = scalar($conexao, "SELECT COALESCE(SUM(valor_venda),0) FROM vendas WHERE data_venda BETWEEN '$inicio' AND '$fim'");
$total_lucro = scalar($conexao, "SELECT COALESCE(SUM(lucro),0) FROM vendas WHERE data_venda BETWEEN '$inicio' AND '$fim'");
$total_rg = scalar($conexao, "SELECT COALESCE(SUM(comissao_rg),0) FROM vendas WHERE data_venda BETWEEN '$inicio' AND '$fim'");
$total_vendedor = scalar($conexao, "SELECT COALESCE(SUM(comissao_vendedor),0) FROM vendas WHERE data_venda BETWEEN '$inicio' AND '$fim'");
$total_parceiro = scalar($conexao, "SELECT COALESCE(SUM(comissao_parceiro),0) FROM vendas WHERE data_venda BETWEEN '$inicio' AND '$fim'");
$total_custos = scalar($conexao, "SELECT COALESCE(SUM(total_custos),0) FROM vendas WHERE data_venda BETWEEN '$inicio' AND '$fim'");

// ===============================
// STATUS
// ===============================
$pendentes = scalar($conexao, "SELECT COUNT(*) FROM vendas WHERE status='PENDENTE' AND data_venda BETWEEN '$inicio' AND '$fim'");
$pagos = scalar($conexao, "SELECT COUNT(*) FROM vendas WHERE status='PAGO' AND data_venda BETWEEN '$inicio' AND '$fim'");
$cancelados = scalar($conexao, "SELECT COUNT(*) FROM vendas WHERE status='CANCELADO' AND data_venda BETWEEN '$inicio' AND '$fim'");

$rg_pendente = scalar($conexao, "SELECT COALESCE(SUM(comissao_rg),0) FROM vendas WHERE status='PENDENTE' AND data_venda BETWEEN '$inicio' AND '$fim'");
$rg_pago = scalar($conexao, "SELECT COALESCE(SUM(comissao_rg),0) FROM vendas WHERE status='PAGO' AND data_venda BETWEEN '$inicio' AND '$fim'");

// ===============================
// LISTA
// ===============================
$res = mysqli_query($conexao, "
SELECT id, data_venda, status, marca, modelo, ano,
       valor_venda, valor_proprietario, lucro,
       comissao_rg, precisa_aprovacao, lucro_minimo
FROM vendas
WHERE data_venda BETWEEN '$inicio' AND '$fim'
ORDER BY data_venda DESC
LIMIT 200
");

if(!$res) die("Erro SQL: " . mysqli_error($conexao));

// ===============================
// GRÁFICO
// ===============================
$chartRes = mysqli_query($conexao, "
SELECT DATE_FORMAT(data_venda, '%Y-%m') ym,
       SUM(lucro) total
FROM vendas
WHERE data_venda >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH)
GROUP BY ym
ORDER BY ym
");

$labels = [];
$valores = [];

while($r = mysqli_fetch_assoc($chartRes)){
    $labels[] = $r['ym'];
    $valores[] = (float)$r['total'];
}
?>
<?php if ($v['status'] === 'PENDENTE' && (int)$v['precisa_aprovacao'] === 0): ?>
<a class="btn btn-sm btn-success"
href="marcar_pago.php?id=<?=h($v['id'])?>">PAGO</a>
<?php endif; ?>
