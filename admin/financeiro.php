<?php
include("../auth.php");
include("../conexao.php");

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function money($v){ return number_format((float)$v, 2, ',', '.'); }

$ym = date('Y-m'); // mês atual
// --- FILTRO MÊS/ANO ---
$mes = isset($_GET['mes']) ? (int)$_GET['mes'] : (int)date('m');
$ano = isset($_GET['ano']) ? (int)$_GET['ano'] : (int)date('Y');

if ($mes < 1 || $mes > 12) $mes = (int)date('m');
if ($ano < 2000 || $ano > 2100) $ano = (int)date('Y');

$inicio = sprintf('%04d-%02d-01', $ano, $mes);
$fim    = date('Y-m-t', strtotime($inicio)); // último dia do mês

// Helpers para queries rápidas
function scalar($con, $sql){
  $r = mysqli_query($con, $sql);
  if(!$r) return 0;
  $row = mysqli_fetch_row($r);
  return $row ? ($row[0] ?? 0) : 0;
}

// Resumo do mês (todas as vendas do mês)
$total_vendas = (int)scalar($conexao, "
  SELECT COUNT(*) FROM vendas
  WHERE data_venda BETWEEN '$inicio' AND '$fim'
");

$total_valor_venda = (float)scalar($conexao, "
  SELECT COALESCE(SUM(valor_venda),0) FROM vendas
  WHERE data_venda BETWEEN '$inicio' AND '$fim'
");

$total_lucro = (float)scalar($conexao, "
  SELECT COALESCE(SUM(lucro),0) FROM vendas
  WHERE data_venda BETWEEN '$inicio' AND '$fim'
");

$total_rg = (float)scalar($conexao, "
  SELECT COALESCE(SUM(comissao_rg),0) FROM vendas
  WHERE data_venda BETWEEN '$inicio' AND '$fim'
");

$total_vendedor = (float)scalar($conexao, "
  SELECT COALESCE(SUM(comissao_vendedor),0) FROM vendas
  WHERE data_venda BETWEEN '$inicio' AND '$fim'
");

$total_parceiro = (float)scalar($conexao, "
  SELECT COALESCE(SUM(comissao_parceiro),0) FROM vendas
  WHERE data_venda BETWEEN '$inicio' AND '$fim'
");

$total_custos = (float)scalar($conexao, "
  SELECT COALESCE(SUM(total_custos),0) FROM vendas
  WHERE data_venda BETWEEN '$inicio' AND '$fim'
");

// Por status (mês)
$pendentes = (int)scalar($conexao, "
  SELECT COUNT(*) FROM vendas
  WHERE status='PENDENTE' AND data_venda BETWEEN '$inicio' AND '$fim'
");
$pagos = (int)scalar($conexao, "
  SELECT COUNT(*) FROM vendas
  WHERE status='PAGO' AND data_venda BETWEEN '$inicio' AND '$fim'
");
$cancelados = (int)scalar($conexao, "
  SELECT COUNT(*) FROM vendas
  WHERE status='CANCELADO' AND data_venda BETWEEN '$inicio' AND '$fim'
");

// Totais por status (para RG)
$rg_pendente = (float)scalar($conexao, "
  SELECT COALESCE(SUM(comissao_rg),0) FROM vendas
  WHERE status='PENDENTE' AND data_venda BETWEEN '$inicio' AND '$fim'
");
$rg_pago = (float)scalar($conexao, "
  SELECT COALESCE(SUM(comissao_rg),0) FROM vendas
  WHERE status='PAGO' AND data_venda BETWEEN '$inicio' AND '$fim'
");

// Lista das vendas do mês (últimas 200)
$res = mysqli_query($conexao, "
  SELECT id, data_venda, status, forma_pagamento,
         marca, modelo, ano,
         valor_venda, valor_proprietario, lucro,
         comissao_vendedor, comissao_parceiro, comissao_rg,
         precisa_aprovacao, lucro_minimo
  FROM vendas
  WHERE data_venda BETWEEN '$inicio' AND '$fim'
  ORDER BY data_venda DESC, id DESC
  LIMIT 200
");
if(!$res) die("Erro SQL vendas: " . mysqli_error($conexao));

// --- GRÁFICO: LUCRO ÚLTIMOS 6 MESES ---
$chartRes = mysqli_query($conexao, "
  SELECT DATE_FORMAT(data_venda, '%Y-%m') ym,
         COALESCE(SUM(lucro),0) total_lucro
  FROM vendas
  WHERE data_venda >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH)
  GROUP BY ym
  ORDER BY ym ASC
");

$labels = [];
$valores = [];

if ($chartRes) {
  while($r = mysqli_fetch_assoc($chartRes)){
    $labels[] = $r['ym'];
    $valores[] = (float)$r['total_lucro'];
  }
}

?>
<!doctype html>
<html lang="pt">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Financeiro - RG</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">

  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h3 class="m-0">Painel Financeiro</h3>
      <form class="row g-2 mt-2 mb-3">
        <div class="col-auto">
          <select name="mes" class="form-select">
            <?php for($m=1;$m<=12;$m++):
              $sel = ($m==$mes) ? 'selected' : '';
            ?>
              <option value="<?=$m?>" <?=$sel?>><?=sprintf('%02d',$m)?></option>
            <?php endfor; ?>
          </select>
        </div>
        <div class="col-auto">
          <select name="ano" class="form-select">
            <?php for($y=(int)date('Y')-2; $y<=(int)date('Y')+1; $y++):
              $sel = ($y==$ano) ? 'selected' : '';
            ?>
              <option value="<?=$y?>" <?=$sel?>><?=$y?></option>
            <?php endfor; ?>
          </select>
        </div>
        <div class="col-auto">
          <button class="btn btn-primary">Ver</button>
        </div>
      </form>
      <div class="text-muted">Período: <?=h($inicio)?> até <?=h($fim)?></div>
    </div>
    <div class="d-flex gap-2">
      <a class="btn btn-outline-dark" href="dashboard.php">Dashboard</a>
      <a class="btn btn-outline-dark" href="relatorio.php">Conversão</a>
      <a class="btn btn-outline-dark" href="funil.php">Funil</a>
    </div>
  </div>

  <!-- Resumo -->
  <div class="row g-3 mb-3">
    <div class="col-md-3">
      <div class="bg-white rounded shadow-sm p-3">
        <div class="text-muted">Vendas (mês)</div>
        <div class="fs-3 fw-bold"><?=h($total_vendas)?></div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="bg-white rounded shadow-sm p-3">
        <div class="text-muted">Valor Venda (mês)</div>
        <div class="fs-5 fw-bold"><?=money($total_valor_venda)?> MT</div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="bg-white rounded shadow-sm p-3">
        <div class="text-muted">Lucro (mês)</div>
        <div class="fs-5 fw-bold"><?=money($total_lucro)?> MT</div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="bg-white rounded shadow-sm p-3">
        <div class="text-muted">Comissão RG (mês)</div>
        <div class="fs-5 fw-bold"><?=money($total_rg)?> MT</div>
      </div>
    </div>
  </div>

  <!-- Status -->
  <div class="row g-3 mb-3">
    <div class="col-md-4">
      <div class="bg-white rounded shadow-sm p-3">
        <div class="text-muted mb-1">Status (mês)</div>
        <div class="d-flex justify-content-between"><span>PENDENTE</span><b><?=h($pendentes)?></b></div>
        <div class="d-flex justify-content-between"><span>PAGO</span><b><?=h($pagos)?></b></div>
        <div class="d-flex justify-content-between"><span>CANCELADO</span><b><?=h($cancelados)?></b></div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="bg-white rounded shadow-sm p-3">
        <div class="text-muted mb-1">RG (mês)</div>
        <div class="d-flex justify-content-between"><span>RG PENDENTE</span><b><?=money($rg_pendente)?> MT</b></div>
        <div class="d-flex justify-content-between"><span>RG PAGO</span><b><?=money($rg_pago)?> MT</b></div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="bg-white rounded shadow-sm p-3">
        <div class="text-muted mb-1">Distribuição (mês)</div>
        <div class="d-flex justify-content-between"><span>Vendedor</span><b><?=money($total_vendedor)?> MT</b></div>
        <div class="d-flex justify-content-between"><span>Parceiro</span><b><?=money($total_parceiro)?> MT</b></div>
        <div class="d-flex justify-content-between"><span>Custos</span><b><?=money($total_custos)?> MT</b></div>
      </div>
    </div>
  </div>
  <div class="bg-white rounded shadow-sm p-3 mb-3">
    <h5 class="mb-3">Lucro por mês (últimos 6 meses)</h5>
    <canvas id="lucroChart" height="90"></canvas>
  </div>

  <!-- Lista -->
  <div class="bg-white rounded shadow-sm p-3">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <h5 class="m-0">Vendas do mês</h5>
      <div class="text-muted small">Últimas 200</div>
    </div>

    <div class="table-responsive">
      <table class="table table-striped table-hover m-0 align-middle">
        <thead>
          <tr>
            <th>#</th>
            <th>Data</th>
            <th>Status</th>
            <th>Carro</th>
            <th>Venda</th>
            <th>Proprietário</th>
            <th>Lucro</th>
            <th>RG</th>
            <th>Aprovação</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php while($v = mysqli_fetch_assoc($res)): 
            $carro = trim($v['marca'].' '.$v['modelo'].' '.$v['ano']);
            $ap = ((int)$v['precisa_aprovacao'] === 1);
          ?>
            <tr>
              <td><?=h($v['id'])?></td>
              <td><?=h($v['data_venda'])?></td>
              <td>
                <?php
                  $st = $v['status'];
                  $badge = 'bg-secondary';
                  if ($st === 'PAGO') $badge = 'bg-success';
                  if ($st === 'PENDENTE') $badge = 'bg-warning text-dark';
                  if ($st === 'CANCELADO') $badge = 'bg-danger';
                ?>
                <span class="badge <?=$badge?>"><?=h($st)?></span>
              </td>
              <td><?=h($carro)?></td>
              <td><?=money($v['valor_venda'])?> MT</td>
              <td><?=money($v['valor_proprietario'])?> MT</td>
              <td><b><?=money($v['lucro'])?> MT</b></td>
              <td><?=money($v['comissao_rg'])?> MT</td>
              <td>
                <?php if($ap): ?>
                  <span class="badge bg-danger">Precisa aprovação</span>
                  <div class="text-muted small">Min: <?=money($v['lucro_minimo'])?> MT</div>
                <?php else: ?>
                  <span class="badge bg-success">OK</span>
                <?php endif; ?>
              </td>
              <td>
                <a class="btn btn-sm btn-outline-dark" href="venda_detalhe.php?id=<?=h($v['id'])?>">Detalhe</a>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
  const labels = <?= json_encode($labels) ?>;
  const valores = <?= json_encode($valores) ?>;

  const ctx = document.getElementById('lucroChart');
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels,
      datasets: [{
        label: 'Lucro (MT)',
        data: valores
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { display: true }
      },
      scales: {
        y: { beginAtZero: true }
      }
    }
  });
</script>
</body>
</html>