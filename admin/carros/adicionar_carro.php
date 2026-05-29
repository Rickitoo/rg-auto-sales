<?php
require_once __DIR__ . '/../../app/core/bootstrap.php';
require_admin();

$msg = '';
$formData = [
    'marca' => '',
    'modelo' => '',
    'ano' => '',
    'preco' => '',
    'descricao' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';

    $formData = [
        'marca' => trim($_POST['marca'] ?? ''),
        'modelo' => trim($_POST['modelo'] ?? ''),
        'ano' => trim($_POST['ano'] ?? ''),
        'preco' => trim($_POST['preco'] ?? ''),
        'descricao' => trim($_POST['descricao'] ?? ''),
    ];

    $marca = $formData['marca'];
    $modelo = $formData['modelo'];
    $ano = (int)$formData['ano'];
    $preco = (float)$formData['preco'];
    $descricao = $formData['descricao'];

    if (!csrf_verify($csrf)) {
        $msg = 'CSRF invalido. Atualize a pagina e tente novamente.';
    } elseif ($marca === '' || $modelo === '') {
        $msg = 'Marca e modelo sao obrigatorios';
    } elseif ($ano < 1900 || $ano > date('Y') + 1) {
        $msg = 'Ano invalido';
    } elseif ($preco <= 0) {
        $msg = 'Preco invalido';
    } else {
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
            $msg = 'Carro adicionado com sucesso';
            $formData = [
                'marca' => '',
                'modelo' => '',
                'ano' => '',
                'preco' => '',
                'descricao' => '',
            ];
        } else {
            $msg = 'Erro ao adicionar carro: ' . mysqli_error($conexao);
        }

        mysqli_stmt_close($stmt);
    }
}

$pageTitle = 'Adicionar Carro';
$pageSubtitle = 'Cadastro de nova viatura no estoque';
$contentFile = BASE_PATH . '/app/views/admin/carros/adicionar_carro_content.php';

require BASE_PATH . '/app/views/layouts/admin_layout.php';
