<?php
require_once(__DIR__ . "/../init.php");

if (!isset($_SESSION['admin'])) {
    header("Location: /RG_AUTO_SALES/login.php");
    exit();
}

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    die("ID inválido.");
}

$result = mysqli_query($conexao, "SELECT * FROM leads WHERE id=$id LIMIT 1");
$lead = mysqli_fetch_assoc($result);

if (!$lead) {
    die("Lead não encontrado.");
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title>Lead #<?= $lead['id'] ?></title>

<style>
body {
    font-family: Arial;
    background: #0f172a;
    color: #fff;
    padding: 20px;
}

.box {
    background: #1e293b;
    padding: 20px;
    border-radius: 10px;
    max-width: 600px;
}

a.btn {
    display: inline-block;
    margin-top: 10px;
    padding: 10px 15px;
    background: #22c55e;
    color: white;
    text-decoration: none;
    border-radius: 5px;
}

button {
    margin-top: 10px;
    padding: 10px;
    background: #3b82f6;
    color: white;
    border: none;
    border-radius: 5px;
}
</style>
</head>

<body>

<div class="box">

<h2>Lead #<?= $lead['id'] ?></h2>

<p><strong>Nome:</strong> <?= htmlspecialchars($lead['nome']) ?></p>
<p><strong>Telefone:</strong> <?= htmlspecialchars($lead['telefone']) ?></p>
<p><strong>Carro:</strong> <?= htmlspecialchars($lead['marca']." ".$lead['modelo']) ?></p>
<p><strong>Status:</strong> <?= $lead['status'] ?></p>
<p><strong>Data:</strong> <?= $lead['created_at'] ?? '-' ?></p>

<!-- WhatsApp -->
<a class="btn" href="https://wa.me/258<?= $lead['telefone'] ?>" target="_blank">
    Falar no WhatsApp
</a>

<!-- Atualizar status -->
<form method="GET" action="leads_status.php">
    <input type="hidden" name="id" value="<?= $lead['id'] ?>">

    <select name="s">
        <option value="novo">Novo</option>
        <option value="contactado">Contactado</option>
        <option value="qualificado">Qualificado</option>
        <option value="agendado">Agendado</option>
        <option value="negociacao">Negociação</option>
        <option value="fechado">Fechado</option>
        <option value="perdido">Perdido</option>
    </select>

    <button type="submit">Atualizar Status</button>
</form>

<!-- Fechar venda -->
<a class="btn" style="background:#f59e0b;" href="marcar_venda.php?lead_id=<?= $lead['id'] ?>">
    Marcar como Venda
</a>

</div>

</body>
</html>