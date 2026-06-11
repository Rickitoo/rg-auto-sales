<?php
require_once __DIR__ . '/../../app/core/bootstrap.php';
require_admin();
require_post_csrf();

function partner_sync_table_columns(mysqli $conexao, string $table): array
{
    $columns = [];
    $res = mysqli_query($conexao, "SHOW COLUMNS FROM `$table`");
    while ($res && ($row = mysqli_fetch_assoc($res))) {
        $columns[$row['Field']] = $row;
    }
    return $columns;
}

function partner_sync_enum_values(string $type): array
{
    if (!str_starts_with(strtolower($type), 'enum(')) {
        return [];
    }
    preg_match_all("/'((?:[^'\\\\]|\\\\.)*)'/", $type, $matches);
    return array_map(fn($value) => stripcslashes($value), $matches[1] ?? []);
}

function partner_sync_pick_enum(array $columns, string $field, string $preferred, string $fallback): string
{
    $values = partner_sync_enum_values((string)($columns[$field]['Type'] ?? ''));
    if (!$values) {
        return $preferred;
    }
    if (in_array($preferred, $values, true)) {
        return $preferred;
    }
    if (in_array($fallback, $values, true)) {
        return $fallback;
    }
    return $values[0];
}

function partner_sync_add_value(array &$data, array $columns, string $field, $value): void
{
    if (isset($columns[$field])) {
        $data[$field] = $value;
    }
}

$partnerLeadId = (int)($_POST['parceiro_lead_id'] ?? 0);
if ($partnerLeadId <= 0) {
    $_SESSION['flash'][] = ['type' => 'error', 'message' => 'Lead de parceiro invalido.'];
    redirect_to('admin/parceiros/leads.php');
}

$stmt = mysqli_prepare($conexao, "
    SELECT pl.*, p.nome AS parceiro_nome, p.tipo AS parceiro_tipo, p.cidade AS parceiro_cidade
    FROM parceiro_leads pl
    INNER JOIN parceiros p ON p.id = pl.parceiro_id
    WHERE pl.id = ?
    LIMIT 1
");
if (!$stmt) {
    $_SESSION['flash'][] = ['type' => 'error', 'message' => 'Nao foi possivel carregar o lead de parceiro.'];
    redirect_to('admin/parceiros/leads.php');
}
mysqli_stmt_bind_param($stmt, 'i', $partnerLeadId);
mysqli_stmt_execute($stmt);
$partnerLead = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$partnerLead) {
    $_SESSION['flash'][] = ['type' => 'error', 'message' => 'Lead de parceiro nao encontrado.'];
    redirect_to('admin/parceiros/leads.php');
}

if ((int)($partnerLead['sincronizado_crm'] ?? 0) === 1) {
    $_SESSION['flash'][] = ['type' => 'warning', 'message' => 'Lead ja sincronizado com CRM.'];
    redirect_to('admin/parceiros/lead_detalhe.php?id=' . $partnerLeadId);
}

$leadColumns = partner_sync_table_columns($conexao, 'leads');
if (!$leadColumns) {
    $_SESSION['flash'][] = ['type' => 'error', 'message' => 'Tabela leads nao encontrada ou indisponivel.'];
    redirect_to('admin/parceiros/lead_detalhe.php?id=' . $partnerLeadId);
}

$status = (string)($partnerLead['status'] ?? 'novo');
if (!in_array($status, partner_sync_enum_values((string)($leadColumns['status']['Type'] ?? '')), true)) {
    $status = 'novo';
}

$modelo = trim((string)($partnerLead['modelo_interesse'] ?? ''));
$observacoes = trim(
    "Lead sincronizado manualmente a partir do RG Partner Network.\n" .
    'Parceiro: ' . ($partnerLead['parceiro_nome'] ?? '-') . ' (#' . (int)$partnerLead['parceiro_id'] . ")\n" .
    'Lead parceiro #' . (int)$partnerLead['id'] . "\n" .
    'Modelo de interesse: ' . ($modelo !== '' ? $modelo : '-') . "\n\n" .
    (string)($partnerLead['observacoes'] ?? '')
);

$insert = [];
partner_sync_add_value($insert, $leadColumns, 'tipo', partner_sync_pick_enum($leadColumns, 'tipo', 'testdrive', 'testdrive'));
partner_sync_add_value($insert, $leadColumns, 'nome', $partnerLead['nome_lead'] ?: 'Lead de parceiro');
partner_sync_add_value($insert, $leadColumns, 'telefone', $partnerLead['telefone_lead'] ?: '');
partner_sync_add_value($insert, $leadColumns, 'modelo', $modelo);
partner_sync_add_value($insert, $leadColumns, 'modelo_interesse', $modelo);
partner_sync_add_value($insert, $leadColumns, 'origem', partner_sync_pick_enum($leadColumns, 'origem', 'parceiro', 'outro'));
partner_sync_add_value($insert, $leadColumns, 'status', $status);
partner_sync_add_value($insert, $leadColumns, 'mensagem', $observacoes);
partner_sync_add_value($insert, $leadColumns, 'notas', $observacoes);
partner_sync_add_value($insert, $leadColumns, 'criado_em', date('Y-m-d H:i:s'));
partner_sync_add_value($insert, $leadColumns, 'created_at', date('Y-m-d H:i:s'));
partner_sync_add_value($insert, $leadColumns, 'atualizado_em', date('Y-m-d H:i:s'));
partner_sync_add_value($insert, $leadColumns, 'updated_at', date('Y-m-d H:i:s'));

if (isset($leadColumns['nome']) && trim((string)$insert['nome']) === '') {
    $_SESSION['flash'][] = ['type' => 'error', 'message' => 'Nome do lead e obrigatorio para sincronizar.'];
    redirect_to('admin/parceiros/lead_detalhe.php?id=' . $partnerLeadId);
}
if (isset($leadColumns['telefone']) && trim((string)$insert['telefone']) === '') {
    $_SESSION['flash'][] = ['type' => 'error', 'message' => 'Telefone do lead e obrigatorio para sincronizar.'];
    redirect_to('admin/parceiros/lead_detalhe.php?id=' . $partnerLeadId);
}

mysqli_begin_transaction($conexao);

try {
    $fields = array_keys($insert);
    $placeholders = implode(', ', array_fill(0, count($fields), '?'));
    $fieldSql = '`' . implode('`, `', $fields) . '`';
    $sql = "INSERT INTO leads ($fieldSql) VALUES ($placeholders)";
    $stmt = mysqli_prepare($conexao, $sql);
    if (!$stmt) {
        throw new RuntimeException('Falha ao preparar insert no CRM.');
    }

    $types = str_repeat('s', count($insert));
    $values = array_values($insert);
    mysqli_stmt_bind_param($stmt, $types, ...$values);
    if (!mysqli_stmt_execute($stmt)) {
        throw new RuntimeException('Falha ao criar lead no CRM.');
    }
    $crmLeadId = mysqli_insert_id($conexao);
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare($conexao, "
        UPDATE parceiro_leads
        SET crm_lead_id = ?, sincronizado_crm = 1, sincronizado_em = NOW()
        WHERE id = ? AND sincronizado_crm = 0
        LIMIT 1
    ");
    if (!$stmt) {
        throw new RuntimeException('Falha ao preparar update de sincronizacao.');
    }
    mysqli_stmt_bind_param($stmt, 'ii', $crmLeadId, $partnerLeadId);
    if (!mysqli_stmt_execute($stmt) || mysqli_stmt_affected_rows($stmt) !== 1) {
        throw new RuntimeException('Lead ja sincronizado com CRM.');
    }
    mysqli_stmt_close($stmt);

    mysqli_commit($conexao);
    $_SESSION['flash'][] = ['type' => 'success', 'message' => 'Lead sincronizado com CRM com sucesso.'];
    redirect_to('admin/parceiros/lead_detalhe.php?id=' . $partnerLeadId);
} catch (Throwable $e) {
    mysqli_rollback($conexao);
    $_SESSION['flash'][] = ['type' => 'error', 'message' => $e->getMessage() === 'Lead ja sincronizado com CRM.' ? 'Lead ja sincronizado com CRM.' : 'Nao foi possivel sincronizar o lead com CRM.'];
    redirect_to('admin/parceiros/lead_detalhe.php?id=' . $partnerLeadId);
}
