<?php
require_once __DIR__ . '/../../app/core/bootstrap.php';
require_admin();

redirect_to('admin/funil.php' . (($_SERVER['QUERY_STRING'] ?? '') !== '' ? '?' . $_SERVER['QUERY_STRING'] : ''));

$stages = ['novo','contactado','qualificado','proposta','negociacao','fechado','perdido'];

function getLeads($conexao, $stage) {
    return mysqli_query($conexao, "
        SELECT * FROM leads WHERE stage='$stage' ORDER BY score DESC
    ");
}
?>

<!doctype html>
<html>
<head>
<title>RG CRM Pipeline</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
.board { display:flex; overflow-x:auto; gap:15px; padding:10px; }
.column { min-width:250px; background:#f5f5f5; padding:10px; border-radius:10px; }
.card { background:white; padding:10px; margin-bottom:10px; border-radius:8px; }
.badge-hot { background:red; }
</style>
</head>

<body class="bg-light">

<h3 class="p-3">📊 RG CRM Pipeline</h3>

<div class="board">

<?php foreach($stages as $stage): ?>

<div class="column">
    <h5><?= ucfirst($stage) ?></h5>

    <?php $leads = getLeads($conexao, $stage); ?>
    <?php while($l = mysqli_fetch_assoc($leads)): ?>

    <div class="card">
        <strong><?= $l['nome'] ?></strong><br>
        📞 <?= $l['telefone'] ?><br>
        ⭐ Score: <?= $l['score'] ?><br>

        <a href="lead.php?id=<?= $l['id'] ?>" class="btn btn-sm btn-primary mt-2">Abrir</a>
    </div>

    <?php endwhile; ?>

</div>

<?php endforeach; ?>

</div>

</body>
</html>
