<?php
require_once __DIR__ . '/../../app/core/bootstrap.php';
require_admin();

if ($_SESSION['user']['role'] !== 'admin') {
    redirect_to('auth/login.php');
    exit();
}

$erro = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (
        !is_string($csrfToken) ||
        empty($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $csrfToken)
    ) {
        http_response_code(403);
        exit('CSRF invalido.');
    }

    $nome = trim($_POST['nome'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $carro_id = (int)($_POST['carro_id'] ?? 0);

    if ($nome == "" || $telefone == "") {
        $erro = "Nome e telefone são obrigatórios.";
    } else {

        $stmt = mysqli_prepare($conexao, "
            INSERT INTO leads (nome, telefone, carro_id, status, created_at)
            VALUES (?, ?, ?, 'novo', NOW())
        ");

        mysqli_stmt_bind_param($stmt, "ssi", $nome, $telefone, $carro_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        redirect_to('admin/leads/listar_leads.php');
        exit();
    }
}

// carros para seleção
$carros = mysqli_query($conexao, "SELECT id, marca, modelo FROM carros");

$pageTitle = 'Adicionar Lead';
$pageSubtitle = 'Cadastro de nova oportunidade comercial';
$contentFile = BASE_PATH . '/app/views/admin/leads/adicionar_lead_content.php';

require BASE_PATH . '/app/views/layouts/admin_layout.php';
