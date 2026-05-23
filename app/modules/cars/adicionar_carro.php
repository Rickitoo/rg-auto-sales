<?php
require_once __DIR__ . '/../../core/bootstrap.php';
require_admin();

if ($_SESSION['user']['role'] !== 'admin') {
    redirect_to('auth/login.php');
    exit();
}

function h($s){
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

$msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // =========================
    // INPUT
    // =========================
    $marca     = trim($_POST['marca'] ?? '');
    $modelo    = trim($_POST['modelo'] ?? '');
    $ano       = intval($_POST['ano'] ?? 0);
    $preco     = floatval($_POST['preco'] ?? 0);
    $descricao = trim($_POST['descricao'] ?? '');

    // =========================
    // VALIDAÇÃO
    // =========================
    if (!$marca || !$modelo) {
        $msg = "Marca e modelo são obrigatórios";
    }
    elseif ($ano < 1900 || $ano > date('Y') + 1) {
        $msg = "Ano inválido";
    }
    elseif ($preco <= 0) {
        $msg = "Preço inválido";
    }
    else {

        // =========================
        // INSERT
        // =========================
        $stmt = mysqli_prepare($conexao, "
            INSERT INTO carros 
            (marca, modelo, ano, preco, descricao, status, criado_em)
            VALUES (?, ?, ?, ?, ?, 'disponivel', NOW())
        ");

        mysqli_stmt_bind_param(
            $stmt,
            "ssids",
            $marca,
            $modelo,
            $ano,
            $preco,
            $descricao
        );

        if (mysqli_stmt_execute($stmt)) {
            $msg = "Carro adicionado com sucesso ✔";
        } else {
            $msg = "Erro ao adicionar carro: " . mysqli_error($conexao);
        }

        mysqli_stmt_close($stmt);
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title>Adicionar Carro - RG Auto Sales</title>

<style>
    body {
        font-family: Arial, sans-serif;
        background: #0f172a;
        color: #fff;
        margin: 0;
    }

    .container {
        max-width: 500px;
        margin: 50px auto;
        background: #1e293b;
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.4);
    }

    h2 {
        margin-bottom: 20px;
        text-align: center;
    }

    label {
        display: block;
        margin-bottom: 5px;
        font-size: 14px;
    }

    input, textarea {
        width: 100%;
        padding: 10px;
        margin-bottom: 15px;
        border: none;
        border-radius: 8px;
        background: #334155;
        color: #fff;
    }

    input:focus, textarea:focus {
        outline: 2px solid #3b82f6;
    }

    button {
        width: 100%;
        padding: 12px;
        background: #3b82f6;
        border: none;
        border-radius: 8px;
        color: white;
        font-weight: bold;
        cursor: pointer;
        transition: 0.3s;
    }

    button:hover {
        background: #2563eb;
    }

    .msg {
        padding: 10px;
        margin-bottom: 15px;
        border-radius: 8px;
        text-align: center;
    }

    .success {
        background: #16a34a;
    }

    .error {
        background: #dc2626;
    }
</style>

</head>
    <body>

    <div class="container">

        <h2>🚗 Adicionar Carro</h2>

        <?php if($msg): ?>
        <div class="msg <?= strpos($msg, 'sucesso') !== false ? 'success' : 'error' ?>">
            <?= h($msg) ?>
        </div>
        <?php endif; ?>

        <form method="POST">

            <label>Marca</label>
            <input type="text" name="marca" placeholder="Ex: Toyota" required>

            <label>Modelo</label>
            <input type="text" name="modelo" placeholder="Ex: Hilux" required>

            <label>Ano</label>
            <input type="number" name="ano" placeholder="Ex: 2020" required>

            <label>Preço (MT)</label>
            <input type="number" step="0.01" name="preco" placeholder="Ex: 850000" required>

            <label>Descrição</label>
            <textarea name="descricao" placeholder="Detalhes do carro..."></textarea>

            <button type="submit">Guardar Carro</button>

        </form>

    </div>

</body>
</html>