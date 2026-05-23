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

<h2>💰 Dashboard Financeiro</h2>

<div class="grid-4">

    <div class="dash-card">
        <div class="card-body">
            <div class="kpi-title">Recebido (Mês)</div>
            <div class="kpi-value"><?= money($recebido) ?></div>
        </div>
    </div>

    <div class="dash-card">
        <div class="card-body">
            <div class="kpi-title">Pendente</div>
            <div class="kpi-value"><?= money($pendente) ?></div>
        </div>
    </div>

    <div class="dash-card">
        <div class="card-body">
            <div class="kpi-title">Custos</div>
            <div class="kpi-value"><?= money($custosMes) ?></div>
        </div>
    </div>

    <div class="dash-card">
        <div class="card-body">
            <div class="kpi-title">Lucro Real</div>
            <div class="kpi-value"><?= money($lucro) ?></div>
        </div>
    </div>

</div>

<br>

<div class="dash-card" style="background:#111;color:#fff;">
    <div class="card-body">
        <div class="kpi-title" style="color:#9ca3af;">PREVISÃO</div>
        <div class="kpi-value">
            <?= money($lucroPrevisto) ?>
        </div>
        <div class="small">
            Se todas vendas forem pagas
        </div>
    </div>
</div>

<?php if($pendente > 0): ?>
    <div style="background:#fff3cd;padding:15px;border-radius:10px;margin-top:15px;">
        ⚠️ Tens dinheiro pendente → foco em cobrar clientes
    </div>
<?php endif; ?>

<?php if($lucro < 0): ?>
    <div style="background:#fee2e2;padding:15px;border-radius:10px;margin-top:15px;">
        🚨 Estás no prejuízo este mês
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/layout_bottom.php'; ?>
