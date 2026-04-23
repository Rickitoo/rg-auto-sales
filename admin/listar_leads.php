<?php
require_once(__DIR__ . "/../init.php");

if (!isset($_SESSION['admin'])) {
    header("Location: /RG_AUTO_SALES/login.php");
    exit();
}

// Buscar leads
$sql = "SELECT * FROM leads ORDER BY id DESC";
$result = mysqli_query($conexao, $sql);
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Leads - RG Auto Sales</title>
    <style>
        body {
            font-family: Arial;
            background: #0f172a;
            color: #fff;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: #1e293b;
        }
        th, td {
            padding: 10px;
            border-bottom: 1px solid #334155;
            text-align: left;
        }
        th {
            background: #020617;
        }
        a.btn {
            padding: 5px 10px;
            background: #3b82f6;
            color: white;
            text-decoration: none;
            margin-right: 5px;
            border-radius: 4px;
        }
        a.btn:hover {
            background: #2563eb;
        }
    </style>
</head>
<body>

<h2>Lista de Leads</h2>

<table>
    <tr>
        <th>ID</th>
        <th>Nome</th>
        <th>Telefone</th>
        <th>Carro</th>
        <th>Status</th>
        <th>Data</th>
        <th>Ações</th>
    </tr>

    <?php while($lead = mysqli_fetch_assoc($result)): ?>
        <tr>
            <td><?= $lead['id'] ?></td>
            <td><?= htmlspecialchars($lead['nome']) ?></td>
            <td><?= htmlspecialchars($lead['telefone']) ?></td>
            <td><?= htmlspecialchars($lead['marca'] . " " . $lead['modelo']) ?></td>
            <td><?= $lead['status'] ?></td>
            <td><?= $lead['created_at'] ?? '-' ?></td>
            <td>

                <!-- VER -->
                <a class="btn" href="ver_lead.php?id=<?= $lead['id'] ?>">Ver</a>

                <!-- STATUS -->
                <a class="btn" href="leads_status.php?id=<?= $lead['id'] ?>&s=contactado">Contactado</a>
                <a class="btn" href="leads_status.php?id=<?= $lead['id'] ?>&s=qualificado">Qualificado</a>
                <a class="btn" href="leads_status.php?id=<?= $lead['id'] ?>&s=fechado">Fechado</a>

            </td>
        </tr>
    <?php endwhile; ?>

</table>

</body>
</html>