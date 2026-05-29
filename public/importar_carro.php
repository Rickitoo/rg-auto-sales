<?php
require_once __DIR__ . '/../app/core/bootstrap.php';

$errors = [];
$success = false;
$old = [
    'nome' => '',
    'telefone' => '',
    'email' => '',
    'orcamento' => '',
    'marca' => '',
    'modelo' => '',
    'ano' => '',
    'porto' => 'Maputo',
    'tipo_compra' => 'importacao',
    'mensagem' => '',
];

$portosPermitidos = ['Maputo', 'Beira'];
$tiposPermitidos = ['importacao', 'consulta', 'orcamento'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($old as $key => $value) {
        $old[$key] = trim((string)($_POST[$key] ?? $value));
    }

    $required = ['nome', 'telefone', 'email', 'orcamento', 'marca', 'modelo', 'porto', 'tipo_compra'];

    foreach ($required as $field) {
        if ($old[$field] === '') {
            $errors[$field] = 'Campo obrigatorio.';
        }
    }

    if ($old['email'] !== '' && !filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Email invalido.';
    }

    if (!in_array($old['porto'], $portosPermitidos, true)) {
        $errors['porto'] = 'Porto invalido.';
    }

    if (!in_array($old['tipo_compra'], $tiposPermitidos, true)) {
        $errors['tipo_compra'] = 'Tipo de compra invalido.';
    }

    $ano = null;
    if ($old['ano'] !== '') {
        $ano = (int)$old['ano'];
        $anoAtual = (int)date('Y') + 1;
        if ($ano < 1980 || $ano > $anoAtual) {
            $errors['ano'] = 'Informe um ano aproximado valido.';
        }
    }

    if (!$errors) {
        $tipo = $old['tipo_compra'];
        $origem = 'importacao';
        $status = 'novo';
        $mensagemResumo = implode("\n", array_filter([
            'Pedido de importacao de carro do Japao',
            'Orcamento disponivel: ' . $old['orcamento'],
            'Marca desejada: ' . $old['marca'],
            'Modelo desejado: ' . $old['modelo'],
            'Ano aproximado: ' . ($old['ano'] !== '' ? $old['ano'] : 'Nao informado'),
            'Porto preferido: ' . $old['porto'],
            'Tipo de compra: ' . $old['tipo_compra'],
            'Mensagem adicional: ' . ($old['mensagem'] !== '' ? $old['mensagem'] : 'Sem mensagem adicional'),
        ]));

        $stmt = mysqli_prepare(
            $conexao,
            "INSERT INTO leads (tipo, nome, telefone, email, mensagem, marca, modelo, ano, origem, status, criado_em)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
        );

        if ($stmt) {
            mysqli_stmt_bind_param(
                $stmt,
                'sssssssiss',
                $tipo,
                $old['nome'],
                $old['telefone'],
                $old['email'],
                $mensagemResumo,
                $old['marca'],
                $old['modelo'],
                $ano,
                $origem,
                $status
            );

            $success = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }

        if ($success) {
            $old = array_map(static fn($value) => '', $old);
            $old['porto'] = 'Maputo';
            $old['tipo_compra'] = 'importacao';
        } else {
            $errors['form'] = 'Nao foi possivel registar o pedido agora. A equipa RG pode ajudar pelo WhatsApp.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="<?= h(asset('ImagensRG/logo.png')) ?>">
    <title>Importar carro do Japao | RG Auto Sales</title>
    <meta name="description" content="Importe carros do Japao para Mocambique com acompanhamento da RG Auto Sales. Pedido, orcamento, opcoes, embarque e entrega.">
    <link rel="stylesheet" href="<?= h(asset('css/style.css')) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@200;300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body.import-page{background:#050b14;color:#fff;padding-top:0;text-align:left}
        .import-hero{background:linear-gradient(120deg,rgba(0,0,0,.88),rgba(1,32,63,.82)),url("<?= h(asset('ImagensRG/Mercedes.jpeg')) ?>") center/cover no-repeat;min-height:680px}
        .import-hero .navbar{background:rgba(1,32,63,.9)}
        .import-hero-inner{display:grid;grid-template-columns:minmax(0,1fr) 440px;gap:34px;align-items:center;padding:70px 25px 54px;max-width:1240px;margin:0 auto}
        .import-copy h1{color:#fff;font-size:52px;line-height:1.05;margin:0 0 18px;text-align:left}
        .import-copy p{color:#d9edff;font-size:17px;line-height:1.7;max-width:680px;text-align:left}
        .import-badges{display:flex;flex-wrap:wrap;gap:10px;margin:24px 0}
        .import-badges span{background:rgba(0,174,239,.16);border:1px solid rgba(0,174,239,.45);border-radius:999px;color:#fff;font-weight:700;padding:9px 13px}
        .import-form-card{background:#fff;border:1px solid rgba(255,255,255,.16);border-radius:8px;box-shadow:0 24px 70px rgba(0,0,0,.38);color:#01203f;padding:22px}
        .import-form-card h2{color:#01203f;font-size:24px;margin:0 0 6px;text-align:left}
        .import-form-card p{color:#536474;font-size:14px;margin:0 0 18px;text-align:left}
        .import-form-grid{display:grid;gap:12px;grid-template-columns:1fr 1fr}
        .import-form-grid .full{grid-column:1 / -1}
        .import-form-card label{color:#01203f;display:block;font-size:13px;font-weight:700;margin:0}
        .import-form-card input,.import-form-card select,.import-form-card textarea{margin-top:6px}
        .import-form-card textarea{min-height:96px}
        .import-error{color:#b42318;display:block;font-size:12px;font-weight:700;margin-top:4px}
        .import-alert{border-radius:8px;font-weight:700;line-height:1.5;margin-bottom:14px;padding:12px}
        .import-alert.success{background:#dcfae6;color:#067647}
        .import-alert.error{background:#fee4e2;color:#b42318}
        .import-submit{width:100%;margin:12px 0 0}
        .import-section{background:#fff;color:#01203f;padding:64px 25px}
        .import-section h2{color:#01203f;margin-bottom:24px;text-align:center}
        .import-steps{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;max-width:1180px;margin:0 auto}
        .import-step{background:#f6fbff;border:1px solid #d8edf8;border-radius:8px;padding:20px}
        .import-step strong{background:#00aeef;border-radius:999px;color:#fff;display:inline-flex;height:34px;align-items:center;justify-content:center;margin-bottom:12px;width:34px}
        .import-step h3{color:#01203f;font-size:18px;margin:0 0 8px;text-align:left}
        .import-step p{color:#536474;font-size:14px;line-height:1.6;text-align:left}
        .import-cta-band{background:#01203f;color:#fff;padding:38px 25px;text-align:center}
        .import-cta-band h2{color:#fff;margin:0 0 10px}
        .import-cta-band p{color:#d9edff;margin:0 auto 14px;max-width:760px;text-align:center}
        @media(max-width:980px){.import-hero-inner{grid-template-columns:1fr}.import-copy h1{font-size:40px}.import-steps{grid-template-columns:repeat(2,minmax(0,1fr))}}
        @media(max-width:620px){.import-hero-inner{padding:36px 18px}.import-copy h1{font-size:32px}.import-form-grid,.import-steps{grid-template-columns:1fr}.import-form-grid .full{grid-column:auto}}
    </style>
</head>
<body class="import-page">
    <header class="import-hero">
        <div class="navbar">
            <div class="logo">
                <a href="<?= h(public_url('index.php')) ?>">
                    <img src="<?= h(asset('ImagensRG/logo.png')) ?>" alt="RG Auto Sales" width="120">
                </a>
            </div>

            <nav>
                <ul id="MenuItems">
                    <li><a href="<?= h(public_url('index.php')) ?>">Inicio</a></li>
                    <li><a href="<?= h(public_url('products.php')) ?>">Carros</a></li>
                    <li><a href="<?= h(public_url('importar_carro.php')) ?>">Importar</a></li>
                    <li><a href="<?= h(public_url('leasing.php')) ?>">Leasing</a></li>
                    <li><a href="<?= h(public_url('vender_carro.php')) ?>">Vender</a></li>
                    <li><a href="<?= h(public_url('contacto.php')) ?>">Contacto</a></li>
                </ul>
            </nav>

            <button class="menu-icon" type="button" onclick="menutoggle()" aria-label="Abrir menu">
                <i class="fa-solid fa-bars"></i>
            </button>
        </div>

        <div class="import-hero-inner">
            <div class="import-copy">
                <h1>Importe o seu carro do Japao para Mocambique com a RG Auto Sales</h1>
                <p>Escolha a marca, defina o seu orcamento e receba apoio comercial desde a procura das melhores opcoes ate ao acompanhamento de embarque, transito e chegada ao porto de Maputo ou Beira.</p>
                <div class="import-badges">
                    <span>Japao para Mocambique</span>
                    <span>Maputo ou Beira</span>
                    <span>Processo acompanhado</span>
                </div>
                <a class="btn" href="#pedido">Pedir orcamento</a>
            </div>

            <form id="pedido" class="import-form-card" method="POST" novalidate>
                <h2>Pedido de importacao</h2>
                <p>Preencha os dados e a equipa RG entra em contacto para alinhar opcoes e proximos passos.</p>

                <?php if ($success): ?>
                    <div class="import-alert success">Pedido registado com sucesso. A equipa RG Auto Sales vai contactar para dar seguimento.</div>
                <?php endif; ?>
                <?php if (!empty($errors['form'])): ?>
                    <div class="import-alert error"><?= h($errors['form']) ?></div>
                <?php endif; ?>

                <div class="import-form-grid">
                    <label class="full">Nome
                        <input type="text" name="nome" value="<?= h($old['nome']) ?>" required>
                        <?php if (!empty($errors['nome'])): ?><span class="import-error"><?= h($errors['nome']) ?></span><?php endif; ?>
                    </label>
                    <label>Telefone / WhatsApp
                        <input type="text" name="telefone" value="<?= h($old['telefone']) ?>" required>
                        <?php if (!empty($errors['telefone'])): ?><span class="import-error"><?= h($errors['telefone']) ?></span><?php endif; ?>
                    </label>
                    <label>Email
                        <input type="email" name="email" value="<?= h($old['email']) ?>" required>
                        <?php if (!empty($errors['email'])): ?><span class="import-error"><?= h($errors['email']) ?></span><?php endif; ?>
                    </label>
                    <label class="full">Orcamento disponivel
                        <input type="text" name="orcamento" value="<?= h($old['orcamento']) ?>" placeholder="Ex.: 900.000 MT ou 15.000 USD" required>
                        <?php if (!empty($errors['orcamento'])): ?><span class="import-error"><?= h($errors['orcamento']) ?></span><?php endif; ?>
                    </label>
                    <label>Marca desejada
                        <input type="text" name="marca" value="<?= h($old['marca']) ?>" required>
                        <?php if (!empty($errors['marca'])): ?><span class="import-error"><?= h($errors['marca']) ?></span><?php endif; ?>
                    </label>
                    <label>Modelo desejado
                        <input type="text" name="modelo" value="<?= h($old['modelo']) ?>" required>
                        <?php if (!empty($errors['modelo'])): ?><span class="import-error"><?= h($errors['modelo']) ?></span><?php endif; ?>
                    </label>
                    <label>Ano aproximado
                        <input type="number" name="ano" value="<?= h($old['ano']) ?>" min="1980" max="<?= h((int)date('Y') + 1) ?>">
                        <?php if (!empty($errors['ano'])): ?><span class="import-error"><?= h($errors['ano']) ?></span><?php endif; ?>
                    </label>
                    <label>Porto preferido
                        <select name="porto" required>
                            <option value="Maputo" <?= $old['porto'] === 'Maputo' ? 'selected' : '' ?>>Maputo</option>
                            <option value="Beira" <?= $old['porto'] === 'Beira' ? 'selected' : '' ?>>Beira</option>
                        </select>
                        <?php if (!empty($errors['porto'])): ?><span class="import-error"><?= h($errors['porto']) ?></span><?php endif; ?>
                    </label>
                    <label class="full">Tipo de compra
                        <select name="tipo_compra" required>
                            <option value="importacao" <?= $old['tipo_compra'] === 'importacao' ? 'selected' : '' ?>>Importacao</option>
                            <option value="consulta" <?= $old['tipo_compra'] === 'consulta' ? 'selected' : '' ?>>Consulta</option>
                            <option value="orcamento" <?= $old['tipo_compra'] === 'orcamento' ? 'selected' : '' ?>>Orcamento</option>
                        </select>
                        <?php if (!empty($errors['tipo_compra'])): ?><span class="import-error"><?= h($errors['tipo_compra']) ?></span><?php endif; ?>
                    </label>
                    <label class="full">Mensagem adicional
                        <textarea name="mensagem" placeholder="Ex.: prefiro automatico, 4x4, baixo consumo, bancos em pele..."><?= h($old['mensagem']) ?></textarea>
                    </label>
                </div>

                <button class="btn import-submit" type="submit">Enviar pedido</button>
            </form>
        </div>
    </header>

    <section class="import-section">
        <h2>Como funciona</h2>
        <div class="import-steps">
            <div class="import-step"><strong>1</strong><h3>Pedido</h3><p>Registamos o seu orcamento, marca, modelo, ano e porto preferido.</p></div>
            <div class="import-step"><strong>2</strong><h3>Opcoes</h3><p>A RG identifica alternativas compativeis e alinha consigo as melhores escolhas.</p></div>
            <div class="import-step"><strong>3</strong><h3>Compra e embarque</h3><p>Depois da decisao comercial, acompanhamos a fase de compra, documentacao e embarque.</p></div>
            <div class="import-step"><strong>4</strong><h3>Chegada</h3><p>Monitoramos transito, desalfandegamento e entrega conforme o processo acordado.</p></div>
        </div>
    </section>

    <section class="import-cta-band">
        <h2>Quer importar com acompanhamento comercial?</h2>
        <p>Envie o pedido ou fale diretamente com a RG Auto Sales no WhatsApp para receber orientacao inicial.</p>
        <a class="btn" href="https://wa.me/258862934721?text=Ola%20RG%20Auto%20Sales,%20quero%20importar%20um%20carro%20do%20Japao." target="_blank" rel="noopener">WhatsApp RG</a>
    </section>

    <div class="footer">
        <div class="container">
            <div class="row">
                <div class="footer-col-2">
                    <img src="<?= h(asset('ImagensRG/logo.png')) ?>" alt="RG Auto Sales">
                    <p>Viaturas, importacao e acompanhamento comercial com transparencia.</p>
                </div>
                <div class="footer-col-1">
                    <h3>Links uteis</h3>
                    <ul>
                        <li><a href="<?= h(public_url('products.php')) ?>">Carros</a></li>
                        <li><a href="<?= h(public_url('test_drive.php')) ?>">Test Drive</a></li>
                        <li><a href="<?= h(public_url('leasing.php')) ?>">Leasing</a></li>
                        <li><a href="<?= h(public_url('contacto.php')) ?>">Contactos</a></li>
                    </ul>
                </div>
            </div>
            <hr>
            <p class="copyright">Copyright 2026 - RG SALES</p>
        </div>
    </div>

    <script>
        const menuItems = document.getElementById("MenuItems");
        function menutoggle(){
            menuItems.classList.toggle("show");
        }
    </script>
</body>
</html>
