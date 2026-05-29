<?php
require_once __DIR__ . '/../app/core/bootstrap.php';
require_admin();

if ($_SESSION['user']['role'] !== 'admin') {
    redirect_to('auth/login.php');
    exit();
}

$result = mysqli_query($conexao, "
    SELECT * FROM vendas 
    WHERE precisa_aprovacao = 1 
    AND status = 'PENDENTE'
    ORDER BY id DESC
");
?>

<h2>Vendas para Aprovação</h2>

<table border="1" cellpadding="10">
<tr>
    <th>ID</th>
    <th>Cliente</th>
    <th>Carro</th>
    <th>Lucro</th>
    <th>Ações</th>
</tr>

<?php while($v = mysqli_fetch_assoc($result)): ?>
<tr>
    <td><?= $v['id'] ?></td>
    <td><?= htmlspecialchars($v['cliente_nome']) ?></td>
    <td><?= htmlspecialchars($v['marca']." ".$v['modelo']) ?></td>
    <td><?= number_format($v['lucro'],2,',','.') ?> MT</td>
    <td>
        <form class="d-inline" method="POST" action="<?= h(url('admin/vendas/aprovar_venda.php')) ?>">
            <?= csrf_input() ?>
            <input type="hidden" name="id" value="<?= (int)$v['id'] ?>">
            <button type="submit" onclick="return confirm('Aprovar esta venda?');">Aprovar</button>
        </form>
        |
        <form class="d-inline" method="POST" action="<?= h(url('admin/vendas/rejeitar_venda.php')) ?>">
            <?= csrf_input() ?>
            <input type="hidden" name="id" value="<?= (int)$v['id'] ?>">
            <button type="submit" onclick="return confirm('Rejeitar esta venda?');">Rejeitar</button>
        </form>
    </td>
</tr>
<?php endwhile; ?>
</table>
