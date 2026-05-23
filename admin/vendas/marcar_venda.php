<?php
require_once __DIR__ . '/../../app/core/bootstrap.php';
require_admin();

if ($_SESSION['user']['role'] !== 'admin') {
    redirect_to('auth/login.php');
    exit();
}

if (!function_exists('h')) { function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); } }

$lead_id = (int)($_GET['id'] ?? $_GET['lead_id'] ?? 0);

if ($lead_id <= 0) {
    die("ID inválido.");
}

/*
🔴 FIX PRINCIPAL:
LEFT JOIN para não matar o lead se carro estiver vazio
*/
$stmt = mysqli_prepare($conexao, "
    SELECT 
        l.*,
        c.id AS carro_id,
        c.marca,
        c.modelo,
        c.preco,
        c.status AS carro_status
    FROM leads l
    LEFT JOIN carros c ON l.carro_id = c.id
    WHERE l.id = ?
");

mysqli_stmt_bind_param($stmt, "i", $lead_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$data) {
    die("Lead não encontrado.");
}

$lead = $data;
$carro = $data;

/*
🔴 FIX SEGURANÇA
Lead pode existir sem carro
*/
if (!empty($carro['carro_id'])) {
    $preco_compra = (float)$carro['preco'];
} else {
    $preco_compra = 0;
    $carro['marca'] = "N/A";
    $carro['modelo'] = "N/A";
}
/*
🔴 carro vendido
*/
if ($carro['carro_status'] === 'vendido') {
    die("Este carro já foi vendido.");
}

$erro = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $preco_venda = (float)($_POST['preco_venda'] ?? 0);
    $data_venda  = $_POST['data_venda'] ?? '';

    if ($preco_venda <= 0) {
        $erro = "Preço inválido.";
    } elseif (empty($data_venda)) {
        $erro = "Data inválida.";
    } else {

        $preco_compra = (float)$carro['preco'];
        $lucro = $preco_venda - $preco_compra;

        if ($lucro < 0) {
            $erro = "Venda abaixo do custo!";
        } else {

            mysqli_begin_transaction($conexao);

            try {

                $user = current_user();
                $user_id = (int)($user['id'] ?? 0);

                /*
                🔴 CLIENTE
                */
                $stmt = mysqli_prepare($conexao, "SELECT id FROM clientes WHERE telefone=? LIMIT 1");
                mysqli_stmt_bind_param($stmt, "s", $lead['telefone']);
                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);
                $cliente = mysqli_fetch_assoc($res);
                mysqli_stmt_close($stmt);

                if ($cliente) {
                    $cliente_id = $cliente['id'];
                } else {
                    $stmt = mysqli_prepare($conexao, "INSERT INTO clientes (nome, telefone) VALUES (?, ?)");
                    mysqli_stmt_bind_param($stmt, "ss", $lead['nome'], $lead['telefone']);
                    mysqli_stmt_execute($stmt);
                    $cliente_id = mysqli_insert_id($conexao);
                    mysqli_stmt_close($stmt);
                }

                /*
                🔴 COMISSÕES
                */
                $comissao_vendedor = $lucro * 0.15;
                $comissao_parceiro = $lucro * 0.10;
                $comissao_rg = $lucro - ($comissao_vendedor + $comissao_parceiro);

                /*
                🔴 VENDA
                */
                $stmt = mysqli_prepare($conexao, "
                    INSERT INTO vendas (
                        cliente_id, lead_id, carro_id,
                        cliente_nome, telefone,
                        marca, modelo,
                        valor_venda, preco_custo,
                        lucro,
                        comissao_vendedor,
                        comissao_parceiro,
                        comissao_rg,
                        status,
                        data_venda
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'PENDENTE', ?)
                ");

                mysqli_stmt_bind_param(
                    $stmt,
                    "iiissssdddddds",
                    $cliente_id,
                    $lead_id,
                    $carro['carro_id'],
                    $lead['nome'],
                    $lead['telefone'],
                    $carro['marca'],
                    $carro['modelo'],
                    $preco_venda,
                    $preco_compra,
                    $lucro,
                    $comissao_vendedor,
                    $comissao_parceiro,
                    $comissao_rg,
                    $data_venda
                );

                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);

                /*
                🔴 UPDATE CARRO
                */
                $stmt = mysqli_prepare($conexao, "UPDATE carros SET status='vendido' WHERE id=?");
                mysqli_stmt_bind_param($stmt, "i", $carro['carro_id']);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);

                /*
                🔴 UPDATE LEAD
                */
                $stmt = mysqli_prepare($conexao, "
                    UPDATE leads 
                    SET status='fechado', proximo_followup=NULL 
                    WHERE id=?
                ");
                mysqli_stmt_bind_param($stmt, "i", $lead_id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);

                mysqli_commit($conexao);

                redirect_to('admin/dashboard.php?sucesso=venda');

            } catch (Exception $e) {
                mysqli_rollback($conexao);
                die("Erro ao processar venda.");
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Marcar Venda</title>
</head>
<body>

<h2>Marcar Venda</h2>

<?php if ($erro): ?>
<p style="color:red"><?= h($erro) ?></p>
<?php endif; ?>

<p><strong>Cliente:</strong> <?= h($lead['nome']) ?></p>
<p><strong>Carro:</strong> <?= h($carro['marca']) ?> <?= h($carro['modelo']) ?></p>

<form method="POST">

<label>Preço de Venda</label>
<input type="number" step="0.01" name="preco_venda" value="<?= h($carro['preco']) ?>" required>

<br><br>

<label>Data da Venda</label>
<input type="datetime-local" name="data_venda" required>

<br><br>

<button type="submit">Confirmar Venda</button>

</form>

</body>
</html>
