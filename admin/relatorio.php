<?php
include("../auth.php");
include("../conexao.php");
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$q1 = mysqli_query($conexao, "SELECT COUNT(*) total FROM leads WHERE YEAR(criado_em)=YEAR(CURDATE()) AND MONTH(criado_em)=MONTH(CURDATE())");
$total = (int)mysqli_fetch_row($q1)[0];

$q2 = mysqli_query($conexao, "SELECT COUNT(*) fechados FROM leads WHERE status='fechado' AND YEAR(criado_em)=YEAR(CURDATE()) AND MONTH(criado_em)=MONTH(CURDATE())");
$fechados = (int)mysqli_fetch_row($q2)[0];

$conv = ($total > 0) ? round(($fechados/$total)*100, 2) : 0;

$porOrigem = mysqli_query($conexao, "
  SELECT origem,
         COUNT(*) total,
         SUM(status='fechado') fechados
  FROM leads
  WHERE YEAR(criado_em)=YEAR(CURDATE()) AND MONTH(criado_em)=MONTH(CURDATE())
  GROUP BY origem
  ORDER BY total DESC
");
?>
<!doctype html>
<html lang="pt">
<head>
  <meta charset="utf-8">
  <title>Relatório - RG</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="m-0">Relatório (Mês Atual)</h3>
    <a class="btn btn-outline-dark" href="dashboard.php">Voltar</a>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-md-4">
      <div class="bg-white rounded shadow-sm p-3">
        <div class="text-muted">Leads do mês</div>
        <div class="fs-3 fw-bold"><?=h($total)?></div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="bg-white rounded shadow-sm p-3">
        <div class="text-muted">Fechados do mês</div>
        <div class="fs-3 fw-bold"><?=h($fechados)?></div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="bg-white rounded shadow-sm p-3">
        <div class="text-muted">Conversão</div>
        <div class="fs-3 fw-bold"><?=h($conv)?>%</div>
      </div>
    </div>
  </div>

  <div class="bg-white rounded shadow-sm p-3">
    <h5 class="mb-3">Por origem</h5>
    <div class="table-responsive">
      <table class="table table-striped m-0">
        <thead><tr><th>Origem</th><th>Total</th><th>Fechados</th><th>Conv.</th></tr></thead>
        <tbody>
          <?php while($r = mysqli_fetch_assoc($porOrigem)):
            $t = (int)$r['total'];
            $f = (int)$r['fechados'];
            $c = ($t>0) ? round(($f/$t)*100,2) : 0;
          ?>
            <tr>
              <td><?=h($r['origem'])?></td>
              <td><?=h($t)?></td>
              <td><?=h($f)?></td>
              <td><?=h($c)?>%</td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>
</body>
</html>