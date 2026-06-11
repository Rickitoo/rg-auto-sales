<?php
require_once __DIR__ . '/../../app/core/bootstrap.php';
require_admin();
require_post_csrf();

$tipos = ['captador', 'revendedor', 'importacao', 'marketing', 'fornecedor', 'outro'];
$estados = ['ativo', 'inativo', 'pendente'];
$niveis = ['principal', 'regular', 'comunidade'];

function parceiro_clean(?string $value): ?string
{
    $value = trim((string)$value);
    return $value === '' ? null : $value;
}

$id = (int)($_POST['id'] ?? 0);
$nome = trim((string)($_POST['nome'] ?? ''));
$telefone = parceiro_clean($_POST['telefone'] ?? null);
$whatsapp = parceiro_clean($_POST['whatsapp'] ?? null);
$email = parceiro_clean($_POST['email'] ?? null);
$cidade = parceiro_clean($_POST['cidade'] ?? null);
$tipo = (string)($_POST['tipo'] ?? 'captador');
$origem = parceiro_clean($_POST['origem'] ?? null);
$estado = (string)($_POST['estado'] ?? 'ativo');
$nivel = (string)($_POST['nivel'] ?? 'regular');
$comissaoInput = trim((string)($_POST['comissao_padrao'] ?? ''));
$comissao = $comissaoInput === '' ? null : (float)str_replace(',', '.', $comissaoInput);
$notas = parceiro_clean($_POST['notas'] ?? null);

if ($nome === '') {
    $_SESSION['flash'][] = ['type' => 'error', 'message' => 'O nome do parceiro e obrigatorio.'];
    redirect_to($id > 0 ? 'admin/parceiros/editar.php?id=' . $id : 'admin/parceiros/adicionar.php');
}
if (!in_array($tipo, $tipos, true)) {
    $tipo = 'captador';
}
if (!in_array($estado, $estados, true)) {
    $estado = 'ativo';
}
if (!in_array($nivel, $niveis, true)) {
    $nivel = 'regular';
}
if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['flash'][] = ['type' => 'error', 'message' => 'Email invalido.'];
    redirect_to($id > 0 ? 'admin/parceiros/editar.php?id=' . $id : 'admin/parceiros/adicionar.php');
}
if ($comissao !== null && $comissao < 0) {
    $_SESSION['flash'][] = ['type' => 'error', 'message' => 'A comissao padrao nao pode ser negativa.'];
    redirect_to($id > 0 ? 'admin/parceiros/editar.php?id=' . $id : 'admin/parceiros/adicionar.php');
}

if ($id > 0) {
    $stmt = mysqli_prepare($conexao, "
        UPDATE parceiros
        SET nome = ?, telefone = ?, whatsapp = ?, email = ?, cidade = ?, tipo = ?, origem = ?, estado = ?, nivel = ?, comissao_padrao = ?, notas = ?
        WHERE id = ?
        LIMIT 1
    ");
    $ok = false;
    if ($stmt) {
        $types = str_repeat('s', 9) . 'dsi';
        mysqli_stmt_bind_param($stmt, $types, $nome, $telefone, $whatsapp, $email, $cidade, $tipo, $origem, $estado, $nivel, $comissao, $notas, $id);
        $ok = mysqli_stmt_execute($stmt);
    }
    if ($stmt) {
        mysqli_stmt_close($stmt);
    }

    $_SESSION['flash'][] = $ok
        ? ['type' => 'success', 'message' => 'Parceiro atualizado com sucesso.']
        : ['type' => 'error', 'message' => 'Nao foi possivel atualizar o parceiro.'];
    redirect_to($ok ? 'admin/parceiros/detalhe.php?id=' . $id : 'admin/parceiros/editar.php?id=' . $id);
}

$stmt = mysqli_prepare($conexao, "
    INSERT INTO parceiros (nome, telefone, whatsapp, email, cidade, tipo, origem, estado, nivel, comissao_padrao, notas)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");
$ok = false;
if ($stmt) {
    $types = str_repeat('s', 9) . 'ds';
    mysqli_stmt_bind_param($stmt, $types, $nome, $telefone, $whatsapp, $email, $cidade, $tipo, $origem, $estado, $nivel, $comissao, $notas);
    $ok = mysqli_stmt_execute($stmt);
}
$novoId = $ok ? mysqli_insert_id($conexao) : 0;
if ($stmt) {
    mysqli_stmt_close($stmt);
}

$_SESSION['flash'][] = $ok
    ? ['type' => 'success', 'message' => 'Parceiro criado com sucesso.']
    : ['type' => 'error', 'message' => 'Nao foi possivel criar o parceiro.'];
redirect_to($ok ? 'admin/parceiros/detalhe.php?id=' . $novoId : 'admin/parceiros/adicionar.php');
