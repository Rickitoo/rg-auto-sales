<?php
require_once __DIR__ . '/../app/core/bootstrap.php';

$sucesso = false;
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    public_require_form_security('vender_carro_simples', 5, 300);

    $nome = trim($_POST['nome'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $marca = trim($_POST['marca'] ?? '');
    $modelo = trim($_POST['modelo'] ?? '');
    $ano = (int)($_POST['ano'] ?? 0);
    $preco = (float)($_POST['preco'] ?? 0);
    $descricao = trim($_POST['descricao'] ?? '');

    if ($nome === '' || $telefone === '' || $marca === '' || $modelo === '') {
        $erro = 'Preencha pelo menos nome, telefone, marca e modelo.';
    } elseif (!public_valid_phone($telefone)) {
        $erro = 'Telefone invalido.';
    } elseif (!public_valid_email($email, false)) {
        $erro = 'Email invalido.';
    } else {
        $mensagem = "Cliente quer vender carro: {$marca} {$modelo}, ano {$ano}, preço desejado {$preco}. {$descricao}";

        mysqli_begin_transaction($conexao);

        try {
            $stmtCarro = mysqli_prepare($conexao, "
                INSERT INTO carros (marca, modelo, ano, preco, descricao, status, data_registo)
                VALUES (?, ?, ?, ?, ?, 'disponivel', NOW())
            ");

            if (!$stmtCarro) {
                throw new Exception('Erro ao preparar carro: ' . mysqli_error($conexao));
            }

            mysqli_stmt_bind_param($stmtCarro, "ssids", $marca, $modelo, $ano, $preco, $descricao);

            if (!mysqli_stmt_execute($stmtCarro)) {
                throw new Exception('Erro ao gravar carro: ' . mysqli_error($conexao));
            }

            $carroId = mysqli_insert_id($conexao);
            mysqli_stmt_close($stmtCarro);

            $stmt = mysqli_prepare($conexao, "
                INSERT INTO vendedores (nome, telefone, email, marca, modelo, ano, preco, mensagem, carro_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            if (!$stmt) {
                throw new Exception('Erro ao preparar pedido: ' . mysqli_error($conexao));
            }

            mysqli_stmt_bind_param($stmt, "sssssidsi", $nome, $telefone, $email, $marca, $modelo, $ano, $preco, $descricao, $carroId);

            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception('Erro ao gravar pedido: ' . mysqli_error($conexao));
            }

            mysqli_stmt_close($stmt);

            $stmtLead = mysqli_prepare($conexao, "
                INSERT INTO leads (tipo, nome, telefone, email, mensagem, marca, modelo, ano, carro_id, origem, status, criado_em)
                VALUES ('venda', ?, ?, ?, ?, ?, ?, ?, ?, 'site', 'novo', NOW())
            ");

            if (!$stmtLead) {
                throw new Exception('Erro ao preparar lead: ' . mysqli_error($conexao));
            }

            mysqli_stmt_bind_param($stmtLead, "ssssssii", $nome, $telefone, $email, $mensagem, $marca, $modelo, $ano, $carroId);

            if (!mysqli_stmt_execute($stmtLead)) {
                throw new Exception('Erro ao gravar lead: ' . mysqli_error($conexao));
            }

            mysqli_stmt_close($stmtLead);
            mysqli_commit($conexao);
            $sucesso = true;
        } catch (Exception $e) {
            mysqli_rollback($conexao);
            $erro = 'Erro ao enviar pedido. Tente novamente.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Vender o Meu Carro | RG Auto Sales</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= h(asset('css/style.css')) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        .sell-page,
        .sell-page * {
            box-sizing: border-box;
        }

        .sell-page * {
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
            background: #050b14;
            color: #fff;
        }

        .sell-page {
            min-height: 100vh;
            padding: 40px 20px;
            background:
                linear-gradient(rgba(5, 11, 20, .92), rgba(5, 11, 20, .96)),
                url('assets/ImagensRG/Mercedes.jpeg') center/cover no-repeat;
        }

        .sell-container {
            max-width: 1100px;
            margin: auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            align-items: center;
        }

        .sell-badge {
            display: inline-block;
            background: #007bff;
            color: #fff;
            padding: 8px 14px;
            border-radius: 999px;
            font-size: 13px;
            margin-bottom: 20px;
            font-weight: bold;
        }

        .sell-text h1 {
            font-size: 46px;
            line-height: 1.1;
            margin-bottom: 20px;
        }

        .sell-text h1 span {
            color: #007bff;
        }

        .sell-text p {
            color: #cbd5e1;
            font-size: 17px;
            line-height: 1.6;
            margin-bottom: 16px;
        }

        .benefits {
            margin-top: 25px;
        }

        .benefits div {
            background: rgba(255,255,255,.06);
            border: 1px solid rgba(255,255,255,.08);
            padding: 14px;
            border-radius: 14px;
            margin-bottom: 12px;
        }

        .form-box {
            background: #0f172a;
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 22px;
            padding: 30px;
            box-shadow: 0 20px 60px rgba(0,0,0,.35);
        }

        .form-box h2 {
            margin-bottom: 10px;
            font-size: 26px;
        }

        .form-box p {
            color: #94a3b8;
            margin-bottom: 22px;
        }

        .field {
            margin-bottom: 15px;
        }

        .form-box label {
            display: block;
            margin-bottom: 7px;
            color: #cbd5e1;
            font-size: 14px;
        }

        .form-box input,
        .form-box textarea {
            width: 100%;
            padding: 14px;
            border-radius: 12px;
            border: 1px solid #1e293b;
            background: #020617;
            color: #fff;
            outline: none;
        }

        .form-box input:focus,
        .form-box textarea:focus {
            border-color: #007bff;
        }

        .form-box textarea {
            min-height: 110px;
            resize: vertical;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        .form-box button {
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 14px;
            background: #007bff;
            color: #fff;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 8px;
        }

        .form-box button:hover {
            background: #005fd1;
        }

        .alert {
            padding: 14px;
            border-radius: 12px;
            margin-bottom: 18px;
            font-size: 14px;
        }

        .success {
            background: rgba(22, 163, 74, .15);
            border: 1px solid #16a34a;
            color: #bbf7d0;
        }

        .error {
            background: rgba(220, 38, 38, .15);
            border: 1px solid #dc2626;
            color: #fecaca;
        }

        .whatsapp {
            margin-top: 18px;
            text-align: center;
        }

        .whatsapp a {
            color: #22c55e;
            text-decoration: none;
            font-weight: bold;
        }

        @media (max-width: 850px) {
            .sell-container {
                grid-template-columns: 1fr;
                max-width: 100%;
                overflow: hidden;
            }

            .sell-text h1 {
                font-size: 30px;
                overflow-wrap: anywhere;
            }

            .grid-2 {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 600px) {
            .sell-page {
                padding-left: 16px;
                padding-right: 16px;
            }

            .sell-container,
            .sell-text,
            .sell-text h1,
            .sell-text p,
            .form-box,
            .benefits div {
                width: 100% !important;
                max-width: 343px !important;
            }

            .sell-container {
                margin-left: 0 !important;
                margin-right: 0 !important;
            }
        }
    </style>
</head>

<body>

<?php require_once __DIR__ . '/../includes/header_public.php'; ?>

<div class="sell-page">
    <div class="sell-container">

        <div class="sell-text">
            <div class="sell-badge">RG Auto Sales</div>

            <h1>Venda o seu carro com <span>segurança e rapidez</span></h1>

            <p>
                A RG Auto Sales ajuda proprietários a venderem os seus carros de forma mais profissional,
                com apoio comercial, divulgação e acompanhamento até encontrar um comprador sério.
            </p>

            <p>
                Preencha o formulário e a nossa equipa entrará em contacto para avaliar a viatura e orientar
                os próximos passos.
            </p>

            <div class="benefits">
                <div>✔ Avaliação inicial do carro</div>
                <div>✔ Divulgação para compradores interessados</div>
                <div>✔ Apoio na negociação</div>
                <div>✔ Processo mais organizado e seguro</div>
            </div>
        </div>

        <div class="form-box">
            <h2>Quero vender o meu carro</h2>
            <p>Preencha os dados abaixo.</p>

            <?php if ($sucesso): ?>
                <div class="alert success">
                    Pedido enviado com sucesso. A RG Auto Sales entrará em contacto consigo.
                </div>
            <?php endif; ?>

            <?php if ($erro): ?>
                <div class="alert error">
                    <?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <?= csrf_input() ?>
                <?= public_honeypot_input() ?>
                <div class="field">
                    <label>Nome completo *</label>
                    <input type="text" name="nome" required>
                </div>

                <div class="grid-2">
                    <div class="field">
                        <label>Telefone / WhatsApp *</label>
                        <input type="text" name="telefone" required>
                    </div>

                    <div class="field">
                        <label>Email</label>
                        <input type="email" name="email">
                    </div>
                </div>

                <div class="grid-2">
                    <div class="field">
                        <label>Marca *</label>
                        <input type="text" name="marca" required>
                    </div>

                    <div class="field">
                        <label>Modelo *</label>
                        <input type="text" name="modelo" required>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="field">
                        <label>Ano</label>
                        <input type="number" name="ano" min="1980" max="<?= date('Y') + 1 ?>">
                    </div>

                    <div class="field">
                        <label>Preço desejado</label>
                        <input type="number" name="preco" step="0.01">
                    </div>
                </div>

                <div class="field">
                    <label>Descrição do carro</label>
                    <textarea name="descricao" placeholder="Estado do carro, quilometragem, documentos, localização, etc."></textarea>
                </div>

                <button type="submit">Enviar pedido</button>
            </form>

            <div class="whatsapp">
                Ou fale diretamente pelo WhatsApp
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer_public.php'; ?>
<?php require_once __DIR__ . '/includes/wa_float.php'; ?>

</body>
</html>
