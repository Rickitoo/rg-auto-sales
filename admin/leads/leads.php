<?php
require_once __DIR__ . '/../../app/core/bootstrap.php';
require_admin();

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

function lead_col_exists(mysqli $con, string $col): bool {
    $col = mysqli_real_escape_string($con, $col);
    $q = mysqli_query($con, "SHOW COLUMNS FROM leads LIKE '$col'");
    return $q && mysqli_num_rows($q) > 0;
}

$hasProximoFollowup = lead_col_exists($conexao, 'proximo_followup');
$hasProximoContacto = lead_col_exists($conexao, 'proximo_contacto');
$followupExpr = $hasProximoFollowup ? 'proximo_followup' : ($hasProximoContacto ? 'proximo_contacto' : 'NULL');
$followupField = $hasProximoFollowup ? 'proximo_followup' : ($hasProximoContacto ? 'proximo_contacto' : null);

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
        WHEN $followupExpr <= NOW() THEN 50
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
    AND ($followupExpr IS NULL OR $followupExpr <= NOW())
");
$followCount = $follow ? mysqli_fetch_assoc($follow)['total'] : 0;
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

<a class="btn btn-sm btn-primary"
href="<?= h(url('admin/vendas/marcar_venda.php?lead_id=' . (int)$row['id'] . '&carro_id=' . (int)$row['carro_id'])) ?>">💰</a>

<a class="btn btn-sm btn-info"
href="<?= h(url('admin/leads/ver_lead.php?id=' . (int)$row['id'])) ?>">Ver</a>

<a class="btn btn-sm btn-warning"
href="<?= h(url('admin/crm/inbox.php?id=' . (int)$row['id'])) ?>">CRM</a>

</td>

<td>
<?= $followupField && !empty($row[$followupField]) 
    ? date('d/m H:i', strtotime($row[$followupField])) 
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


