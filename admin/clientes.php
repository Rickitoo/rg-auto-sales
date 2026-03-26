<?php
include("../auth.php");
include("../conexao.php");

if (session_status() === PHP_SESSION_NONE) session_start();

function h($s){
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

$sql = "SELECT * FROM clientes ORDER BY data_registo DESC, id DESC";
$res = mysqli_query($conexao, $sql);

if (!$res) {
    die("Erro ao buscar clientes: " . mysqli_error($conexao));
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clientes | RG Auto Sales</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Clientes / Test Drives</h2>
        <a href="dashboard.php" class="btn btn-dark">Voltar ao Dashboard</a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Telefone</th>
                        <th>Email</th>
                        <th>Veículo</th>
                        <th>Data Test Drive</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($res) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($res)): ?>
                            <?php
                                $telefoneLimpo = preg_replace('/\D+/', '', $row['telefone']);
                                $msg = rawurlencode("Olá " . $row['nome'] . ", aqui é da RG Auto Sales. Estamos a dar seguimento ao seu pedido de test drive para o veículo " . $row['marca'] . " " . $row['modelo'] . ".");
                            ?>
                            <tr>
                                <td><?= h($row['id']) ?></td>
                                <td><?= h($row['nome']) ?></td>
                                <td><?= h($row['telefone']) ?></td>
                                <td><?= h($row['email']) ?></td>
                                <td>
                                    <?= h($row['marca']) ?> <?= h($row['modelo']) ?>
                                    <br>
                                    <small class="text-muted">Ano: <?= h($row['ano']) ?></small>
                                </td>
                                <td>
                                    <?= h($row['data']) ?><br>
                                    <small class="text-muted"><?= h($row['hora']) ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-<?= ($row['status'] === 'CONCLUIDO') ? 'success' : 'warning text-dark' ?>">
                                        <?= h($row['status']) ?>
                                    </span>
                                </td>
                                <td class="d-flex gap-2 flex-wrap">
                                    <a href="https://wa.me/<?= h($telefoneLimpo) ?>?text=<?= $msg ?>" target="_blank" class="btn btn-success btn-sm">
                                        WhatsApp
                                    </a>
                                    <a href="cliente_detalhe.php?id=<?= h($row['id']) ?>" class="btn btn-primary btn-sm">
                                        Ver
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center">Nenhum cliente encontrado.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>