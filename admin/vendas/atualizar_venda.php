<?php
require_once __DIR__ . '/../../app/core/bootstrap.php';
require_admin();

if ($_SESSION['user']['role'] !== 'admin') {
    redirect_to('auth/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_to('admin/vendas/vendas.php?msg=metodo_invalido');
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

// =========================
// VALIDAR INPUT
// =========================
$venda_id    = intval($_POST['venda_id'] ?? 0);
$preco_venda = floatval($_POST['preco_venda'] ?? 0);
$preco_custo = floatval($_POST['preco_custo'] ?? 0);
$status      = $_POST['status'] ?? '';
$tipo_venda  = $_POST['tipo_venda'] ?? 'normal';
$aprovado    = intval($_POST['aprovado'] ?? 0);

// Validação básica
if ($venda_id <= 0) {
    die("ID da venda inválido.");
}

if ($preco_venda <= 0 || $preco_custo <= 0) {
    die("Valores inválidos.");
}

// =========================
// MONTAR VENDA
// =========================
$venda = [
    'preco_venda' => $preco_venda,
    'preco_custo' => $preco_custo,
    'status'      => $status,
    'tipo_venda'  => $tipo_venda,
    'aprovado'    => $aprovado
];

// =========================
// CALCULAR COMISSÕES
// =========================
$result = calcularComissoes($venda);

// BLOQUEIO: lucro negativo
if ($result['lucro'] < 0) {
    die("Erro: Venda com prejuízo não permitida.");
}

// =========================
// PREPARED STATEMENT (SEGURO)
// =========================
$stmt = $conexao->prepare("
    UPDATE vendas SET 
        preco_custo=?,
        preco_venda=?,
        lucro=?,
        comissao_vendedor=?,
        comissao_parceiro=?,
        comissao_rg=?,
        aprovado=?,
        status=?
    WHERE id=?
");

$stmt->bind_param(
    "ddddddisi",
    $preco_custo,
    $preco_venda,
    $result['lucro'],
    $result['comissao_vendedor'],
    $result['comissao_parceiro'],
    $result['comissao_rg'],
    $aprovado,
    $status,
    $venda_id
);

if ($stmt->execute()) {
    echo "Venda atualizada com sucesso!";
} else {
    echo "Erro: " . $stmt->error;
}

$stmt->close();
?>
