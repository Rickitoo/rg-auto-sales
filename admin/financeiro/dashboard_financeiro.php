<?php
require_once __DIR__ . '/../../app/core/bootstrap.php';
require_admin();

if ($_SESSION['user']['role'] !== 'admin') {
    redirect_to('auth/login.php');
    exit();
}


// ===============================
// HELPERS
// ===============================
function money($v) { return number_format((float)$v, 2, ',', '.') . " MT"; }
function n($v) { return (int)($v ?? 0); }
function finance_col_exists(mysqli $con, string $table, string $col): bool {
    $table = mysqli_real_escape_string($con, $table);
    $col = mysqli_real_escape_string($con, $col);
    $q = mysqli_query($con, "SHOW COLUMNS FROM `$table` LIKE '$col'");
    return $q && mysqli_num_rows($q) > 0;
}

// ===============================
// DATAS
// ===============================
$inicioMes = date('Y-m-01');
$fimMes    = date('Y-m-t');

// ===============================
// RECEITA (COMISSÕES)
// ===============================
$campoReceita = finance_col_exists($conexao, 'vendas', 'comissao_rg') ? 'comissao_rg' : 'comissao';
$campoLucro = finance_col_exists($conexao, 'vendas', 'lucro') ? 'lucro' : $campoReceita;

$stmt = mysqli_prepare($conexao, "
    SELECT 
        COALESCE(SUM(CASE WHEN status='PAGO' THEN $campoReceita ELSE 0 END), 0) AS pago,
        COALESCE(SUM(CASE WHEN status='PENDENTE' THEN $campoReceita ELSE 0 END), 0) AS pendente,
        COALESCE(SUM($campoReceita), 0) AS total,
        COALESCE(SUM(CASE WHEN status='PAGO' THEN $campoLucro ELSE 0 END), 0) AS lucro_pago,
        COALESCE(SUM($campoLucro), 0) AS lucro_total
    FROM vendas
    WHERE data_venda BETWEEN ? AND ?
");

mysqli_stmt_bind_param($stmt, "ss", $inicioMes, $fimMes);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$dados = mysqli_fetch_assoc($res) ?: [];
mysqli_stmt_close($stmt);

$recebido = (float)($dados['pago'] ?? 0);
$pendente = (float)($dados['pendente'] ?? 0);
$total    = (float)($dados['total'] ?? 0);
$lucroPago = (float)($dados['lucro_pago'] ?? 0);
$lucroTotal = (float)($dados['lucro_total'] ?? 0);

// ===============================
// CUSTOS
// ===============================
$custosMes = 0;
$chk = mysqli_query($conexao, "SHOW TABLES LIKE 'custos'");

if ($chk && mysqli_num_rows($chk) > 0) {
    $stmt = mysqli_prepare($conexao, "
        SELECT SUM(valor) as total 
        FROM custos 
        WHERE data BETWEEN ? AND ?
    ");

    mysqli_stmt_bind_param($stmt, "ss", $inicioMes, $fimMes);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $custosMes = (float)(mysqli_fetch_assoc($res)['total'] ?? 0);
    mysqli_stmt_close($stmt);
}

// ===============================
// LUCRO
// ===============================
$lucro = $lucroPago - $custosMes;

// ===============================
// PREVISÃO
// ===============================
$lucroPrevisto = $lucroTotal - $custosMes;

require_once __DIR__ . '/../../includes/layout_top.php';
?>
<div class="rg-page-hero">
    <div>
        <h2>Dashboard Financeiro</h2>
        <p>Visao do mes atual para recebidos, pendentes, custos e lucro real.</p>
    </div>
    <div class="rg-page-actions">
        <a class="top-btn light" href="<?= h(url('admin/vendas/vendas.php')) ?>">Vendas</a>
        <a class="top-btn primary" href="<?= h(url('admin/vendas/nova_venda.php')) ?>">Nova venda</a>
    </div>
</div>

<div class="rg-kpi-grid">
    <div class="rg-kpi-card is-success">
        <strong><?= money($recebido) ?></strong>
        <span>Recebido no mes</span>
    </div>

    <div class="rg-kpi-card is-warning">
        <strong><?= money($pendente) ?></strong>
        <span>Pendente</span>
    </div>

    <div class="rg-kpi-card is-danger">
        <strong><?= money($custosMes) ?></strong>
        <span>Custos</span>
    </div>

    <div class="rg-kpi-card is-info">
        <strong><?= money($lucro) ?></strong>
        <span>Lucro real</span>
    </div>
</div>

<div class="page-card finance-forecast">
    <span>Previsao</span>
    <strong style="display:block;font-size:32px;line-height:1.1;margin-top:8px;">
        <?= money($lucroPrevisto) ?>
    </strong>
    <small>Se todas vendas forem pagas.</small>
</div>

<?php if($pendente > 0): ?>
    <div class="rg-alert rg-alert-warning">
        Tens dinheiro pendente. Foco em cobrar clientes.
    </div>
<?php endif; ?>

<?php if($lucro < 0): ?>
    <div class="rg-alert rg-alert-danger">
        Estas no prejuizo este mes.
    </div>
<?php endif; ?>
<?php require_once __DIR__ . '/../../includes/layout_bottom.php'; ?>
