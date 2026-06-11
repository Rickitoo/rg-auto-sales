<?php
require_once __DIR__ . '/../../app/core/bootstrap.php';
require_admin();
require_post_csrf();

$statuses = ['novo', 'contactado', 'negociacao', 'fechado', 'perdido'];

function partner_lead_clean(?string $value): ?string
{
    $value = trim((string)$value);
    return $value === '' ? null : $value;
}

$id = (int)($_POST['id'] ?? 0);
$parceiroId = (int)($_POST['parceiro_id'] ?? 0);
$nomeLead = trim((string)($_POST['nome_lead'] ?? ''));
$telefoneLead = partner_lead_clean($_POST['telefone_lead'] ?? null);
$modeloInteresse = partner_lead_clean($_POST['modelo_interesse'] ?? null);
$origem = partner_lead_clean($_POST['origem'] ?? null);
$status = (string)($_POST['status'] ?? 'novo');
$valorInput = trim((string)($_POST['valor_estimado'] ?? ''));
$comissaoInput = trim((string)($_POST['comissao_prevista'] ?? ''));
$valorEstimado = $valorInput === '' ? null : (float)str_replace(',', '.', $valorInput);
$comissaoPrevista = $comissaoInput === '' ? null : (float)str_replace(',', '.', $comissaoInput);
$observacoes = partner_lead_clean($_POST['observacoes'] ?? null);

$back = $id > 0 ? 'admin/parceiros/lead_editar.php?id=' . $id : 'admin/parceiros/lead_adicionar.php';

if ($parceiroId <= 0 || $nomeLead === '') {
    $_SESSION['flash'][] = ['type' => 'error', 'message' => 'Parceiro e nome do lead sao obrigatorios.'];
    redirect_to($back);
}
if (!in_array($status, $statuses, true)) {
    $status = 'novo';
}
if (($valorEstimado !== null && $valorEstimado < 0) || ($comissaoPrevista !== null && $comissaoPrevista < 0)) {
    $_SESSION['flash'][] = ['type' => 'error', 'message' => 'Valores nao podem ser negativos.'];
    redirect_to($back);
}

$stmt = mysqli_prepare($conexao, "SELECT id FROM parceiros WHERE id = ? LIMIT 1");
if (!$stmt) {
    $_SESSION['flash'][] = ['type' => 'error', 'message' => 'Nao foi possivel validar o parceiro.'];
    redirect_to($back);
}
mysqli_stmt_bind_param($stmt, 'i', $parceiroId);
mysqli_stmt_execute($stmt);
$parceiroExiste = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$parceiroExiste) {
    $_SESSION['flash'][] = ['type' => 'error', 'message' => 'Parceiro invalido.'];
    redirect_to($back);
}

if ($id > 0) {
    $stmt = mysqli_prepare($conexao, "
        UPDATE parceiro_leads
        SET parceiro_id = ?, nome_lead = ?, telefone_lead = ?, modelo_interesse = ?, origem = ?, status = ?, valor_estimado = ?, comissao_prevista = ?, observacoes = ?
        WHERE id = ?
        LIMIT 1
    ");
    $ok = false;
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'isssssddsi', $parceiroId, $nomeLead, $telefoneLead, $modeloInteresse, $origem, $status, $valorEstimado, $comissaoPrevista, $observacoes, $id);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    $_SESSION['flash'][] = $ok
        ? ['type' => 'success', 'message' => 'Lead de parceiro atualizado com sucesso.']
        : ['type' => 'error', 'message' => 'Nao foi possivel atualizar o lead de parceiro.'];
    redirect_to($ok ? 'admin/parceiros/lead_detalhe.php?id=' . $id : $back);
}

$stmt = mysqli_prepare($conexao, "
    INSERT INTO parceiro_leads (parceiro_id, nome_lead, telefone_lead, modelo_interesse, origem, status, valor_estimado, comissao_prevista, observacoes)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
");
$ok = false;
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'isssssdds', $parceiroId, $nomeLead, $telefoneLead, $modeloInteresse, $origem, $status, $valorEstimado, $comissaoPrevista, $observacoes);
    $ok = mysqli_stmt_execute($stmt);
    $novoId = $ok ? mysqli_insert_id($conexao) : 0;
    mysqli_stmt_close($stmt);
} else {
    $novoId = 0;
}

$_SESSION['flash'][] = $ok
    ? ['type' => 'success', 'message' => 'Lead de parceiro criado com sucesso.']
    : ['type' => 'error', 'message' => 'Nao foi possivel criar o lead de parceiro.'];
redirect_to($ok ? 'admin/parceiros/lead_detalhe.php?id=' . $novoId : $back);
