<?php
include("../conexao.php"); // se teu admin usa auth, depois colocamos
include("auth_check.php");
include("admin/includes/db.php");

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$res = mysqli_query($conexao, "SELECT * FROM leads ORDER BY id DESC LIMIT 200");
if(!$res) die("Erro SQL: " . mysqli_error($conexao));
?>
<!doctype html>
<html lang="pt">
<head>
  <meta charset="utf-8">
  <title>Leads - RG</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
  <h3 class="mb-3">Leads</h3>

  <div class="table-responsive bg-white rounded shadow-sm">
    <table class="table table-striped table-hover m-0">
      <thead>
        <tr>
          <th>#</th><th>Data</th><th>Tipo</th><th>Nome</th><th>Telefone</th><th>Carro</th><th>Status</th>
        </tr>
      </thead>
      <tbody>
      <?php while($row = mysqli_fetch_assoc($res)): ?>
        <tr>
          <td><?=h($row['id'])?></td>
          <td><?=h($row['criado_em'])?></td>
          <td><?=h($row['tipo'])?></td>
          <td><?=h($row['nome'])?></td>
          <td><?=h($row['telefone'])?></td>
          <td><?=h(trim(($row['marca']??'').' '.($row['modelo']??'').' '.($row['ano']??'')))?></td>
          <td><?=h($row['status'])?></td>
        </tr>
        <td class="d-flex gap-1 flex-wrap">
            <a class="btn btn-sm btn-outline-success" href="lead_status.php?id=<?=h($row['id'])?>&s=contactado">Contactado</a>
            <a class="btn btn-sm btn-outline-primary" href="lead_status.php?id=<?=h($row['id'])?>&s=qualificado">Qualificado</a>
            <a class="btn btn-sm btn-outline-dark" href="lead_status.php?id=<?=h($row['id'])?>&s=agendado">Agendado</a>
            <a class="btn btn-sm btn-outline-warning" href="lead_status.php?id=<?=h($row['id'])?>&s=negociacao">Negociação</a>
            <a class="btn btn-sm btn-outline-secondary" href="lead_status.php?id=<?=h($row['id'])?>&s=fechado">Fechado</a>
            <a class="btn btn-sm btn-outline-danger" href="lead_status.php?id=<?=h($row['id'])?>&s=perdido">Perdido</a>
        </td>
      <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>
</body>
</html>