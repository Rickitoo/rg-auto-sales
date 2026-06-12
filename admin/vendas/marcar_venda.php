<?php
require_once __DIR__ . '/../../app/core/bootstrap.php';
require_admin();

if ($_SESSION['user']['role'] !== 'admin') {
    redirect_to('auth/login.php');
    exit();
}

if (!function_exists('h')) { function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); } }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_to('admin/leads/leads.php?msg=metodo_invalido');
}

$csrfToken = $_POST['csrf_token'] ?? '';
if (
    !is_string($csrfToken) ||
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $csrfToken)
) {
    http_response_code(403);
    die("Ação bloqueada (token inválido).");
}

$lead_id = (int)($_POST['id'] ?? $_POST['lead_id'] ?? 0);

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

if (isset($_POST['preco_venda'], $_POST['data_venda'])) {

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
<html lang="pt">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Marcar Venda</title>
<style>
    :root {
        --rg-navy: #07192f;
        --rg-blue: #00aeef;
        --rg-bg: #eef3f8;
        --rg-line: #dde5ef;
        --rg-text: #101828;
        --rg-muted: #667085;
        --rg-danger: #b42318;
    }
    * { box-sizing: border-box; }
    body {
        margin: 0;
        min-height: 100vh;
        background: var(--rg-bg);
        color: var(--rg-text);
        font-family: Arial, sans-serif;
    }
    .sale-page {
        width: min(980px, calc(100% - 32px));
        margin: 0 auto;
        padding: 34px 0;
    }
    .sale-header { margin-bottom: 22px; }
    .sale-header a {
        color: var(--rg-blue);
        display: inline-flex;
        font-weight: 700;
        margin-bottom: 14px;
        text-decoration: none;
    }
    .sale-header h1 {
        color: var(--rg-navy);
        font-size: clamp(28px, 4vw, 40px);
        line-height: 1.1;
        margin: 0 0 8px;
    }
    .sale-header p {
        color: var(--rg-muted);
        font-size: 15px;
        margin: 0;
    }
    .sale-card {
        background: #fff;
        border: 1px solid var(--rg-line);
        border-radius: 12px;
        box-shadow: 0 16px 42px rgba(16, 24, 40, .08);
        overflow: hidden;
    }
    .sale-summary {
        display: grid;
        gap: 14px;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        padding: 24px;
    }
    .sale-summary__item {
        background: #f8fbff;
        border: 1px solid var(--rg-line);
        border-radius: 10px;
        padding: 16px;
    }
    .sale-summary__item span,
    .sale-form label {
        color: var(--rg-muted);
        display: block;
        font-size: 13px;
        font-weight: 700;
        margin-bottom: 7px;
    }
    .sale-summary__item strong {
        color: var(--rg-navy);
        display: block;
        font-size: 18px;
        line-height: 1.35;
    }
    .sale-form {
        border-top: 1px solid var(--rg-line);
        display: grid;
        gap: 16px;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        padding: 24px;
    }
    .sale-form input {
        border: 1px solid #cfd8e3;
        border-radius: 10px;
        color: var(--rg-text);
        font-size: 16px;
        min-height: 48px;
        padding: 12px 14px;
        width: 100%;
    }
    .sale-form input:focus {
        border-color: var(--rg-blue);
        box-shadow: 0 0 0 3px rgba(0, 174, 239, .14);
        outline: none;
    }
    .sale-actions {
        align-items: center;
        display: flex;
        gap: 12px;
        grid-column: 1 / -1;
        justify-content: flex-end;
        margin-top: 4px;
    }
    .sale-actions button {
        background: var(--rg-blue);
        border: 0;
        border-radius: 10px;
        color: #fff;
        cursor: pointer;
        font-size: 15px;
        font-weight: 800;
        min-height: 48px;
        padding: 0 22px;
    }
    .sale-alert {
        background: #fee4e2;
        border: 1px solid #fecdca;
        border-radius: 10px;
        color: var(--rg-danger);
        font-weight: 700;
        margin: 0 0 18px;
        padding: 13px 15px;
    }
    @media (max-width: 720px) {
        .sale-page {
            width: 343px;
            margin-left: 16px;
            margin-right: 0;
        }

        .sale-summary,
        .sale-form {
            grid-template-columns: 1fr;
            padding: 18px;
        }
        .sale-actions { justify-content: stretch; }
        .sale-actions button { width: 100%; }
    }
</style>
</head>
<body>

<main class="sale-page">
    <header class="sale-header">
        <a href="<?= h(url('admin/leads/leads.php')) ?>">Voltar aos leads</a>
        <h1>Marcar Venda</h1>
        <p>Confirme os dados do cliente e registe a venda deste carro.</p>
    </header>

<?php if ($erro): ?>
    <p class="sale-alert"><?= h($erro) ?></p>
<?php endif; ?>

    <section class="sale-card">
        <div class="sale-summary">
            <div class="sale-summary__item">
                <span>Cliente</span>
                <strong><?= h($lead['nome']) ?></strong>
            </div>
            <div class="sale-summary__item">
                <span>Carro</span>
                <strong><?= h($carro['marca']) ?> <?= h($carro['modelo']) ?></strong>
            </div>
        </div>

        <form class="sale-form" method="POST">
            <?= csrf_input() ?>
            <input type="hidden" name="lead_id" value="<?= (int)$lead_id ?>">

            <label>Preço de Venda
                <input type="number" step="0.01" name="preco_venda" value="<?= h($carro['preco']) ?>" required>
            </label>

            <label>Data da Venda
                <input type="datetime-local" name="data_venda" required>
            </label>

            <div class="sale-actions">
                <button type="submit">Confirmar Venda</button>
            </div>

        </form>
    </section>
</main>

</body>
</html>
