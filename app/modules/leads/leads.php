<?php
require_once __DIR__ . '/../../core/bootstrap.php';
require_admin();

redirect_to('admin/leads/leads.php' . (($_SERVER['QUERY_STRING'] ?? '') !== '' ? '?' . $_SERVER['QUERY_STRING'] : ''));

if ($_SESSION['user']['role'] !== 'admin') {
    redirect_to('auth/login.php');
    exit();
}

if (!function_exists('h')) {
function h($v){
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
}

function gerarMensagem($lead) {
    $nome = $lead['nome'];

    switch($lead['status']) {
        case 'novo':
            return "Olá $nome, viu o carro que enviámos?";
        case 'contactado':
            return "Queria saber se ainda tem interesse.";
        case 'negociacao':
            return "Tenho alguém interessado hoje, quer garantir?";
        default:
            return "Posso ajudar em algo?";
    }
}

// filtros
$filtro = $_GET['status'] ?? '';
$q = $_GET['q'] ?? '';

// contadores
$countRes = mysqli_query($conexao, "
    SELECT 
        COUNT(*) as total,
        SUM(status='novo') as novo,
        SUM(status='contactado') as contactado,
        SUM(status='negociacao') as negociacao,
        SUM(status='fechado') as fechado,
        SUM(status='perdido') as perdido
    FROM leads
");
$count = mysqli_fetch_assoc($countRes);

// query principal
$sql = "
SELECT *,
(
    CASE status
        WHEN 'novo' THEN 10
        WHEN 'contactado' THEN 20
        WHEN 'negociacao' THEN 50
        ELSE 0
    END
    +
    CASE 
        WHEN proximo_followup <= NOW() THEN 50
        ELSE 0
    END
    +
    CASE
        WHEN TIMESTAMPDIFF(HOUR, criado_em, NOW()) <= 24 THEN 20
        ELSE 0
    END
) AS lead_score
FROM leads
WHERE 1
";

if ($filtro) {
    $f = mysqli_real_escape_string($conexao, $filtro);
    $sql .= " AND status='$f'";
}

if ($q) {
    $s = mysqli_real_escape_string($conexao, $q);
    $sql .= " AND (nome LIKE '%$s%' OR telefone LIKE '%$s%')";
}

$sql .= " ORDER BY lead_score DESC, id DESC LIMIT 200";

$res = mysqli_query($conexao, $sql);

// follow-up alert
$follow = mysqli_query($conexao, "
    SELECT COUNT(*) as total
    FROM leads
    WHERE status NOT IN ('fechado','perdido')
    AND (proximo_followup IS NULL OR proximo_followup <= NOW())
");
$followCount = mysqli_fetch_assoc($follow)['total'];
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

<h3>📋 Leads</h3>

<?php if ($followCount > 0): ?>
<div class="alert alert-warning">
🔥 <?= $followCount ?> leads precisam de follow-up!
</div>
<?php endif; ?>

<div class="mb-3 d-flex gap-2 flex-wrap">
<span class="badge bg-dark">Total: <?= $count['total'] ?></span>
<span class="badge bg-primary">Novos: <?= $count['novo'] ?></span>
<span class="badge bg-success">Contactados: <?= $count['contactado'] ?></span>
<span class="badge bg-warning">Negociação: <?= $count['negociacao'] ?></span>
<span class="badge bg-secondary">Fechados: <?= $count['fechado'] ?></span>
<span class="badge bg-danger">Perdidos: <?= $count['perdido'] ?></span>
</div>

<form method="GET" class="mb-3 d-flex gap-2">
<input type="text" name="q" value="<?= h($q) ?>" class="form-control" placeholder="Buscar">
<button class="btn btn-primary">Buscar</button>
</form>

<div class="table-responsive bg-white rounded shadow-sm">

<table class="table table-hover align-middle m-0">

<thead class="table-light">
<tr>
<th>#</th>
<th>Nome</th>
<th>Telefone</th>
<th>Carro</th>
<th>Status</th>
<th>Score</th>
<th>Ações</th>
<th>Follow-up</th>
</tr>
</thead>

<tbody>

<?php while($row = mysqli_fetch_assoc($res)): ?>

<?php
$status = $row['status'];

$badge = match($status) {
    'novo' => 'secondary',
    'contactado' => 'primary',
    'negociacao' => 'warning',
    'fechado' => 'success',
    'perdido' => 'danger',
    default => 'dark'
};

$tel = preg_replace('/[^0-9]/','',$row['telefone']);
$msg = urlencode(gerarMensagem($row));
?>

<tr style="<?= $row['lead_score'] >= 50 ? 'background:#ffe4e6;' : '' ?>">

<td><?=h($row['id'])?></td>
<td><?=h($row['nome'])?></td>
<td><?=h($row['telefone'])?></td>
<td><?=h(($row['marca']??'').' '.($row['modelo']??''))?></td>

<td><span class="badge bg-<?= $badge ?>"><?= h($status) ?></span></td>

<td><span class="badge bg-dark"><?= (int)$row['lead_score'] ?></span></td>

<td class="d-flex gap-1 flex-wrap">

<a class="btn btn-sm btn-success" target="_blank"
href="https://wa.me/<?= $tel ?>?text=<?= $msg ?>">💬</a>

<form method="POST" action="<?= h(url('admin/vendas/marcar_venda.php')) ?>" style="display:inline;">
<?= csrf_input() ?>
<input type="hidden" name="lead_id" value="<?= (int)$row['id'] ?>">
<input type="hidden" name="carro_id" value="<?= (int)$row['carro_id'] ?>">
<button class="btn btn-sm btn-primary" type="submit">Venda</button>
</form>

<a class="btn btn-sm btn-info"
href="ver_lead.php?id=<?=h($row['id'])?>">👁</a>

<form method="POST" action="<?= h(url('admin/services/follow_up.php')) ?>" style="display:inline;">
<?= csrf_input() ?>
<input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
<button class="btn btn-sm btn-warning" type="submit">Follow-up</button>
</form>

</td>

<td>
<?= $row['proximo_followup'] 
    ? date('d/m H:i', strtotime($row['proximo_followup'])) 
    : '-' ?>
</td>

</tr>

<?php endwhile; ?>

</tbody>
</table>
</div>
</div>
</body>
</html>
