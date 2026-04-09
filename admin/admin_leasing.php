<?php
session_start();
include("auth_check.php"); 
include("admin/includes/db.php");

if(!isset($_SESSION['user_id'])){
    header("Location: account.php");
    exit;
}

$sql = "SELECT pl.*, c.marca, c.modelo 
        FROM pedidos_leasing pl
        LEFT JOIN carros c ON pl.carro_id = c.id
        ORDER BY pl.criado_em DESC";

$res = mysqli_query($conn, $sql);
?>

<h2 style="text-align:center;">📊 Pedidos de Leasing</h2>

<table border="1" cellpadding="10" style="width:100%; border-collapse:collapse;">
    <tr>
        <th>ID</th>
        <th>Cliente</th>
        <th>Telefone</th>
        <th>Carro</th>
        <th>Preço</th>
        <th>Entrada</th>
        <th>Meses</th>
        <th>Prestação</th>
        <th>Status</th>
        <th>Lucro</th>
        <th>Ações</th>
    </tr>

    <?php while($row = mysqli_fetch_assoc($res)): 
        $lucro = ($row['preco'] * 0.15); // 15%
    ?>
    <tr>
        <td><?= $row['id'] ?></td>
        <td><?= htmlspecialchars($row['nome']) ?></td>
        <td><?= htmlspecialchars($row['telefone']) ?></td>
        <td><?= htmlspecialchars($row['marca'].' '.$row['modelo']) ?></td>
        <td><?= number_format($row['preco'], 2) ?> MT</td>
        <td><?= number_format($row['entrada'], 2) ?> MT</td>
        <td><?= $row['meses'] ?></td>
        <td><?= number_format($row['prestacao'], 2) ?> MT</td>

        <td>
            <form method="POST" action="update_status.php">
                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                <select name="status">
                    <option value="novo" <?= $row['status']=="novo"?'selected':'' ?>>Novo</option>
                    <option value="contactado" <?= $row['status']=="contactado"?'selected':'' ?>>Contactado</option>
                    <option value="fechado" <?= $row['status']=="fechado"?'selected':'' ?>>Fechado</option>
                </select>
                <button type="submit">OK</button>
            </form>
        </td>

        <td><?= number_format($lucro,2) ?> MT</td>

        <td>
            <a target="_blank"
               href="https://wa.me/<?= $row['telefone'] ?>?text=<?= urlencode("Olá ".$row['nome'].", vi o seu pedido de leasing na RG Auto Sales. Vamos avançar?") ?>">
               WhatsApp
            </a>
        </td>
    </tr>
    <?php endwhile; ?>

</table>