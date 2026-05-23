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
        <a href="aprovar_venda.php?id=<?= $v['id'] ?>">Aprovar</a>
        |
        <a href="rejeitar_venda.php?id=<?= $v['id'] ?>">Rejeitar</a>
    </td>
</tr>
<?php endwhile; ?>
</table>