<?php
include("../auth.php");
include("../conexao.php");

if (session_status() === PHP_SESSION_NONE) session_start();

function money($v){ return number_format((float)$v, 2, ',', '.') . " MT"; }

$inicioMes = date('Y-m-01');
$fimMes    = date('Y-m-t');

$sql = "
SELECT
  p.id,
  p.nome,
  COUNT(v.id) AS vendas_total,
  SUM(CASE WHEN v.status='PAGO' THEN 1 ELSE 0 END) AS vendas_pagas,
  SUM(CASE WHEN v.status='PENDENTE' THEN 1 ELSE 0 END) AS vendas_pendentes,
  SUM(CASE WHEN v.status='CANCELADO' THEN 1 ELSE 0 END) AS vendas_canceladas,
  COALESCE(SUM(CASE WHEN v.status='PAGO' THEN v.comissao ELSE 0 END),0) AS comissao_paga,
  COALESCE(SUM(CASE WHEN v.status='PENDENTE' THEN v.comissao ELSE 0 END),0) AS comissao_pendente,
  COALESCE(SUM(v.comissao),0) AS comissao_total
FROM pessoas p
LEFT JOIN vendas v
  ON v.vendedor_id = p.id
  AND v.data_venda BETWEEN ? AND ?
WHERE p.ativo = 1
GROUP BY p.id, p.nome
ORDER BY comissao_paga DESC, vendas_pagas DESC, vendas_total DESC
";

$stmt = mysqli_prepare($conexao, $sql);
mysqli_stmt_bind_param($stmt, "ss", $inicioMes, $fimMes);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

$rows = [];
while ($r = mysqli_fetch_assoc($res)) $rows[] = $r;
mysqli_stmt_close($stmt);
?>
<!doctype html>
<html lang="pt">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin | Relatório por Vendedor</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background:#f6f7fb; }
    .card { border:0; border-radius:16px; }
    .table-wrap { border-radius:16px; overflow:hidden; }
  </style>
</head>
<body>
<div class="container py-4">

  <div class="d-flex align-items-center justify-content-between mb-3">
    <div>
      <h3 class="mb-0">Relatório por Vendedor</h3>
      <small class="text-muted">Período: <?php echo htmlspecialchars($inicioMes); ?> a <?php echo htmlspecialchars($fimMes); ?></small>
    </div>
    <div class="d-flex gap-2">
      <a class="btn btn-outline-dark" href="dashboard.php">Dashboard</a>
      <a class="btn btn-outline-dark" href="vendas.php">Vendas</a>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-body table-wrap p-0">
      <table class="table table-hover mb-0 align-middle">
        <thead class="table-light">
          <tr>
            <th>Vendedor</th>
            <th class="text-center">Total</th>
            <th class="text-center">Pagas</th>
            <th class="text-center">Pendentes</th>
            <th class="text-center">Canceladas</th>
            <th class="text-end">Comissão paga</th>
            <th class="text-end">Pendente</th>
            <th class="text-end">Total</th>
          </tr>
        </thead>
        <tbody>
        <?php if (!count($rows)): ?>
          <tr><td colspan="8" class="text-center py-4 text-muted">Sem dados.</td></tr>
        <?php else: foreach ($rows as $r): ?>
          <tr>
            <td class="fw-semibold"><?php echo htmlspecialchars($r['nome']); ?></td>
            <td class="text-center"><?php echo (int)$r['vendas_total']; ?></td>
            <td class="text-center"><?php echo (int)$r['vendas_pagas']; ?></td>
            <td class="text-center"><?php echo (int)$r['vendas_pendentes']; ?></td>
            <td class="text-center"><?php echo (int)$r['vendas_canceladas']; ?></td>
            <td class="text-end"><?php echo money($r['comissao_paga']); ?></td>
            <td class="text-end"><?php echo money($r['comissao_pendente']); ?></td>
            <td class="text-end"><?php echo money($r['comissao_total']); ?></td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
