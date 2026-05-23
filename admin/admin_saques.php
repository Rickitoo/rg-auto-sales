<?php
require_once __DIR__ . '/../app/core/bootstrap.php';
require_admin();

$mensagem = '';
$saques = [];

if (!db_table_exists($conexao, 'saques') || !db_table_exists($conexao, 'wallet')) {
    $mensagem = 'Modulo de saques ainda nao tem tabelas criadas na base de dados.';
} else {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify($_POST['csrf_token'] ?? null)) {
        $id = (int)($_POST['id'] ?? 0);
        $acao = $_POST['acao'] ?? '';

        if ($id > 0 && in_array($acao, ['APROVADO', 'REJEITADO'], true)) {
            $saque = mysqli_fetch_assoc(mysqli_query($conexao, "SELECT * FROM saques WHERE id = $id LIMIT 1"));

            if ($saque) {
                mysqli_query($conexao, "UPDATE saques SET status = '$acao', processado_em = NOW() WHERE id = $id");

                if ($acao === 'REJEITADO') {
                    $valor = (float)$saque['valor'];
                    $userId = (int)$saque['user_id'];
                    mysqli_query($conexao, "UPDATE wallet SET saldo_disponivel = saldo_disponivel + $valor WHERE user_id = $userId");
                }

                $mensagem = 'Saque atualizado com sucesso.';
            }
        }
    }

    $res = mysqli_query($conexao, "
        SELECT s.*, COALESCE(u.username, u.email, CONCAT('Utilizador ', s.user_id)) AS nome
        FROM saques s
        LEFT JOIN users u ON u.id = s.user_id
        WHERE s.status = 'PENDENTE'
        ORDER BY s.id DESC
    ");

    while ($res && ($row = mysqli_fetch_assoc($res))) {
        $saques[] = $row;
    }
}

require_once __DIR__ . '/../includes/layout_top.php';
?>

<div class="page-card">
    <h2>Saques Pendentes</h2>

    <?php if ($mensagem): ?>
        <p><?= h($mensagem) ?></p>
    <?php endif; ?>

    <?php if (!$saques): ?>
        <p>Nenhum saque pendente.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Utilizador</th>
                    <th>Valor</th>
                    <th>Data</th>
                    <th>Acao</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($saques as $saque): ?>
                    <tr>
                        <td><?= (int)$saque['id'] ?></td>
                        <td><?= h($saque['nome']) ?></td>
                        <td><?= number_format((float)$saque['valor'], 2, ',', '.') ?> MT</td>
                        <td><?= h($saque['criado_em'] ?? '') ?></td>
                        <td>
                            <form method="post" style="display:inline-flex;gap:8px;flex-wrap:wrap">
                                <?= csrf_input() ?>
                                <input type="hidden" name="id" value="<?= (int)$saque['id'] ?>">
                                <button class="btn" type="submit" name="acao" value="APROVADO">Aprovar</button>
                                <button class="btn" type="submit" name="acao" value="REJEITADO">Rejeitar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/layout_bottom.php'; ?>
