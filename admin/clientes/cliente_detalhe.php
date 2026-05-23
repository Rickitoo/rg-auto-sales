<?php
require_once __DIR__ . '/../../app/core/bootstrap.php';
require_admin();

$clienteId = (int)($_GET['id'] ?? 0);
if ($clienteId <= 0) {
    redirect_to('admin/clientes/clientes.php');
}

$stmt = mysqli_prepare($conexao, "SELECT * FROM clientes WHERE id=? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $clienteId);
mysqli_stmt_execute($stmt);
$cliente = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$cliente) {
    redirect_to('admin/clientes/clientes.php');
}

$leads = [];
$stmt = mysqli_prepare($conexao, "
    SELECT id, nome, telefone, email, marca, modelo, ano, status, criado_em
    FROM leads
    WHERE telefone=? OR email=?
    ORDER BY criado_em DESC, id DESC
    LIMIT 20
");
$email = (string)($cliente['email'] ?? '');
mysqli_stmt_bind_param($stmt, "ss", $cliente['telefone'], $email);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($res)) {
    $leads[] = $row;
}
mysqli_stmt_close($stmt);

$telefone = preg_replace('/\D+/', '', (string)$cliente['telefone']);
if ($telefone !== '' && !str_starts_with($telefone, '258')) {
    $telefone = '258' . ltrim($telefone, '0');
}
$carro = trim(($cliente['marca'] ?? '') . ' ' . ($cliente['modelo'] ?? '') . ' ' . ($cliente['ano'] ?? ''));
$msg = rawurlencode("Ola {$cliente['nome']}, aqui e a RG Auto Sales. Estamos a dar seguimento ao seu test-drive para $carro.");
?>
<!doctype html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cliente #<?= h($clienteId) ?> | RG Auto Sales</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1"><?= h($cliente['nome']) ?></h2>
            <div class="text-muted">Cliente / Test-drive #<?= h($clienteId) ?></div>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-dark" href="<?= h(url('admin/clientes/clientes.php')) ?>">Clientes</a>
            <a class="btn btn-dark" href="<?= h(url('admin/crm/inbox.php')) ?>">CRM Inbox</a>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card shadow-sm">
                <div class="card-header fw-bold">Dados do cliente</div>
                <div class="card-body">
                    <p><strong>Telefone:</strong> <?= h($cliente['telefone']) ?></p>
                    <p><strong>Email:</strong> <?= h($cliente['email'] ?? '-') ?></p>
                    <p><strong>Veiculo:</strong> <?= h($carro ?: '-') ?></p>
                    <p><strong>Data test-drive:</strong> <?= h($cliente['data']) ?> <?= h($cliente['hora']) ?></p>
                    <p><strong>Status:</strong> <?= h($cliente['status']) ?></p>
                    <p><strong>Mensagem:</strong><br><?= nl2br(h($cliente['mensagem'] ?? '-')) ?></p>
                    <?php if ($telefone !== ''): ?>
                        <a class="btn btn-success" target="_blank" rel="noopener" href="https://wa.me/<?= h($telefone) ?>?text=<?= h($msg) ?>">WhatsApp</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-header fw-bold">Historico CRM relacionado</div>
                <div class="card-body">
                    <?php if ($leads): ?>
                        <div class="list-group">
                            <?php foreach ($leads as $lead): ?>
                                <a class="list-group-item list-group-item-action" href="<?= h(url('admin/crm/inbox.php?id=' . (int)$lead['id'])) ?>">
                                    <div class="d-flex justify-content-between gap-2">
                                        <strong><?= h($lead['marca'] . ' ' . $lead['modelo'] . ' ' . $lead['ano']) ?></strong>
                                        <span class="badge bg-dark"><?= h($lead['status']) ?></span>
                                    </div>
                                    <small class="text-muted"><?= h(date('d/m/Y H:i', strtotime($lead['criado_em']))) ?></small>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-secondary mb-0">Nenhum lead relacionado encontrado para este cliente.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
