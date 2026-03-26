<?php
// admin/dashboard.php
include("../auth.php");
include("../conexao.php");
include("includes/config.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Helpers
function money($v) { return number_format((float)$v, 2, ',', '.') . " MT"; }
function n($v) { return (int)($v ?? 0); }
function col_exists($con, $table, $col) {
    $table = mysqli_real_escape_string($con, $table);
    $col   = mysqli_real_escape_string($con, $col);
    $q = mysqli_query($con, "SHOW COLUMNS FROM `$table` LIKE '$col'");
    return $q && mysqli_num_rows($q) > 0;
}

// Datas
$inicioMes = date('Y-m-01');
$fimMes    = date('Y-m-t');
$hoje      = date('Y-m-d');
$diasNoMes = (int)date('t');
$mesLabel  = date('m/Y');

// Config atual
$cfg = rg_get_config($conexao);
$percentLabel = round($cfg["percent"] * 100, 2) . "%";
$minimoLabel  = money($cfg["minimo"]);

// ===============================
// KPIs de CARROS
// ===============================
$sqlCarros = "
    SELECT
        COUNT(*) AS total_carros,
        SUM(CASE WHEN status = 'disponivel' THEN 1 ELSE 0 END) AS carros_disponiveis,
        SUM(CASE WHEN status = 'vendido' THEN 1 ELSE 0 END) AS carros_vendidos,
        SUM(CASE WHEN status = 'disponivel' THEN preco ELSE 0 END) AS valor_stock,
        SUM(CASE WHEN status = 'vendido' THEN preco_venda ELSE 0 END) AS valor_vendido
    FROM carros
";
$resCarros = mysqli_query($conexao, $sqlCarros);
$kpiCarros = $resCarros ? (mysqli_fetch_assoc($resCarros) ?: []) : [];

$totalCarros       = n($kpiCarros['total_carros'] ?? 0);
$carrosDisponiveis = n($kpiCarros['carros_disponiveis'] ?? 0);
$carrosVendidos    = n($kpiCarros['carros_vendidos'] ?? 0);
$valorStock        = (float)($kpiCarros['valor_stock'] ?? 0);
$valorVendido      = (float)($kpiCarros['valor_vendido'] ?? 0);

// ===============================
// KPIs do mês (VENDAS)
// ===============================
$sqlKpisMes = "
    SELECT
        COUNT(*) AS vendas_mes,
        SUM(CASE WHEN status='PAGO' THEN 1 ELSE 0 END) AS pagas_mes,
        SUM(CASE WHEN status='PENDENTE' THEN 1 ELSE 0 END) AS pendentes_mes,
        SUM(CASE WHEN status='CANCELADO' THEN 1 ELSE 0 END) AS canceladas_mes,

        SUM(CASE WHEN status='PAGO' THEN comissao ELSE 0 END) AS comissao_paga_mes,
        SUM(CASE WHEN status='PENDENTE' THEN comissao ELSE 0 END) AS comissao_pendente_mes,
        SUM(comissao) AS comissao_total_mes
    FROM vendas
    WHERE data_venda BETWEEN ? AND ?
";
$stmt = mysqli_prepare($conexao, $sqlKpisMes);
mysqli_stmt_bind_param($stmt, "ss", $inicioMes, $fimMes);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$kpiMes = mysqli_fetch_assoc($res) ?: [];
mysqli_stmt_close($stmt);

$vendasMes        = n($kpiMes['vendas_mes'] ?? 0);
$pagasMes         = n($kpiMes['pagas_mes'] ?? 0);
$pendentesMes     = n($kpiMes['pendentes_mes'] ?? 0);
$canceladasMes    = n($kpiMes['canceladas_mes'] ?? 0);

$comissaoPagaMes  = (float)($kpiMes['comissao_paga_mes'] ?? 0);
$comissaoPendMes  = (float)($kpiMes['comissao_pendente_mes'] ?? 0);
$comissaoTotalMes = (float)($kpiMes['comissao_total_mes'] ?? 0);

// ===============================
// KPIs gerais (VENDAS)
// ===============================
$sqlKpisGerais = "
    SELECT
        COUNT(*) AS vendas_total,
        SUM(CASE WHEN status='PAGO' THEN comissao ELSE 0 END) AS comissao_paga_total,
        SUM(CASE WHEN status='PENDENTE' THEN comissao ELSE 0 END) AS comissao_pendente_total
    FROM vendas
";
$resG = mysqli_query($conexao, $sqlKpisGerais);
$kpiGeral = $resG ? (mysqli_fetch_assoc($resG) ?: []) : [];

$vendasTotal       = n($kpiGeral['vendas_total'] ?? 0);
$comissaoPagaTotal = (float)($kpiGeral['comissao_paga_total'] ?? 0);
$comissaoPendTotal = (float)($kpiGeral['comissao_pendente_total'] ?? 0);

// ===============================
// LEADS (CLIENTES)
// ===============================
$temDataRegisto = col_exists($conexao, 'clientes', 'data_registo');

$leadsMes = 0;
$leadsHoje = 0;

if ($temDataRegisto) {
    $stmt = mysqli_prepare($conexao, "SELECT COUNT(*) AS total FROM clientes WHERE DATE(data_registo) BETWEEN ? AND ?");
    mysqli_stmt_bind_param($stmt, "ss", $inicioMes, $fimMes);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $leadsMes = (int)(mysqli_fetch_assoc($res)['total'] ?? 0);
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare($conexao, "SELECT COUNT(*) AS total FROM clientes WHERE DATE(data_registo) = ?");
    mysqli_stmt_bind_param($stmt, "s", $hoje);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $leadsHoje = (int)(mysqli_fetch_assoc($res)['total'] ?? 0);
    mysqli_stmt_close($stmt);

    $orderLead = "ORDER BY data_registo DESC";
} else {
    $res = mysqli_query($conexao, "SELECT COUNT(*) AS total FROM clientes");
    $leadsMes = (int)(mysqli_fetch_assoc($res)['total'] ?? 0);
    $leadsHoje = 0;
    $orderLead = "ORDER BY id DESC";
}

// Conversão do mês
$conversao = 0.0;
if ($leadsMes > 0) {
    $conversao = ($vendasMes / $leadsMes) * 100.0;
}

// ===============================
// CUSTOS do mês + LUCRO REAL
// ===============================
$existeCustos = false;
$custosMes = 0.0;

$chk = mysqli_query($conexao, "SHOW TABLES LIKE 'custos'");
if ($chk && mysqli_num_rows($chk) > 0) {
    $existeCustos = true;

    $stmt = mysqli_prepare($conexao, "SELECT SUM(valor) AS total FROM custos WHERE data BETWEEN ? AND ?");
    mysqli_stmt_bind_param($stmt, "ss", $inicioMes, $fimMes);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $custosMes = (float)(mysqli_fetch_assoc($res)['total'] ?? 0);
    mysqli_stmt_close($stmt);
}

$lucroRealMes = $comissaoPagaMes - $custosMes;

// ===============================
// ÚLTIMAS VENDAS
// ===============================
$sqlUltimasVendas = "
    SELECT
        v.id, v.data_venda, v.marca, v.modelo, v.ano,
        v.valor_carro, v.comissao, v.status,
        c.nome AS cliente_nome, c.telefone AS cliente_telefone
    FROM vendas v
    INNER JOIN clientes c ON c.id = v.cliente_id
    ORDER BY v.id DESC
    LIMIT 8
";
$resU = mysqli_query($conexao, $sqlUltimasVendas);
$ultimasVendas = [];
if ($resU) while ($r = mysqli_fetch_assoc($resU)) $ultimasVendas[] = $r;

// ===============================
// ÚLTIMOS LEADS
// ===============================
$leadCols = ["id","nome","telefone","email","data","hora","marca","modelo","ano","status","data_registo"];
$colsExist = [];
foreach ($leadCols as $c) {
    if (col_exists($conexao, 'clientes', $c)) $colsExist[] = $c;
}
$selectLeads = (count($colsExist) > 0)
    ? implode(",", array_map(fn($x)=>"`$x`", $colsExist))
    : "`id`,`nome`,`telefone`,`email`";

$sqlLeads = "SELECT $selectLeads FROM clientes $orderLead LIMIT 8";
$resL = mysqli_query($conexao, $sqlLeads);
$ultimosLeads = [];
if ($resL) while ($r = mysqli_fetch_assoc($resL)) $ultimosLeads[] = $r;

// ===============================
// ÚLTIMOS CARROS
// ===============================
$sqlUltimosCarros = "
    SELECT id, marca, modelo, ano, preco, preco_venda, status, data_registo
    FROM carros
    ORDER BY id DESC
    LIMIT 8
";
$resUC = mysqli_query($conexao, $sqlUltimosCarros);
$ultimosCarros = [];
if ($resUC) while ($r = mysqli_fetch_assoc($resUC)) $ultimosCarros[] = $r;

// ===============================
// GRÁFICO: vendas por dia + leads por dia
// ===============================
$labels = [];
for ($d=1; $d<=$diasNoMes; $d++) $labels[] = str_pad((string)$d, 2, "0", STR_PAD_LEFT);

$vendasPorDia = array_fill(1, $diasNoMes, 0);
$leadsPorDia  = array_fill(1, $diasNoMes, 0);

// Vendas por dia
$stmt = mysqli_prepare($conexao, "
    SELECT DAY(data_venda) AS dia, COUNT(*) AS total
    FROM vendas
    WHERE data_venda BETWEEN ? AND ?
    GROUP BY DAY(data_venda)
");
mysqli_stmt_bind_param($stmt, "ss", $inicioMes, $fimMes);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
while ($r = mysqli_fetch_assoc($res)) {
    $dia = (int)$r['dia'];
    if ($dia >= 1 && $dia <= $diasNoMes) $vendasPorDia[$dia] = (int)$r['total'];
}
mysqli_stmt_close($stmt);

// Leads por dia
if ($temDataRegisto) {
    $stmt = mysqli_prepare($conexao, "
        SELECT DAY(DATE(data_registo)) AS dia, COUNT(*) AS total
        FROM clientes
        WHERE DATE(data_registo) BETWEEN ? AND ?
        GROUP BY DAY(DATE(data_registo))
    ");
    mysqli_stmt_bind_param($stmt, "ss", $inicioMes, $fimMes);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($r = mysqli_fetch_assoc($res)) {
        $dia = (int)$r['dia'];
        if ($dia >= 1 && $dia <= $diasNoMes) $leadsPorDia[$dia] = (int)$r['total'];
    }
    mysqli_stmt_close($stmt);
}

$vendasSeries = [];
$leadsSeries  = [];
for ($d=1; $d<=$diasNoMes; $d++) {
    $vendasSeries[] = $vendasPorDia[$d];
    $leadsSeries[]  = $leadsPorDia[$d];
}

include("includes/layout_top.php");
?>

<style>
    .dash-card{
        border:0;
        border-radius:16px;
        background:#fff;
        box-shadow:0 4px 18px rgba(0,0,0,.08);
        height:100%;
    }
    .dash-card .card-body{
        padding:18px;
    }
    .kpi-title{
        font-size:.85rem;
        color:#6c757d;
        margin-bottom:6px;
    }
    .kpi-value{
        font-size:1.5rem;
        font-weight:700;
        color:#111827;
    }
    .section-head{
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:12px;
        flex-wrap:wrap;
        margin-bottom:14px;
    }
    .section-head h2{
        margin:0;
        font-size:24px;
    }
    .section-sub{
        color:#6b7280;
        font-size:14px;
        margin-top:4px;
    }
    .quick-actions{
        display:flex;
        gap:10px;
        flex-wrap:wrap;
    }
    .quick-btn{
        display:inline-block;
        padding:10px 14px;
        border-radius:10px;
        text-decoration:none;
        font-weight:bold;
        color:#fff;
    }
    .quick-dark{ background:#212529; }
    .quick-success{ background:#198754; }
    .quick-outline{
        background:#fff;
        color:#111827;
        border:1px solid #d1d5db;
    }
    .table-card{
        background:#fff;
        border-radius:16px;
        box-shadow:0 4px 18px rgba(0,0,0,.08);
        overflow:hidden;
    }
    .table-responsive{
        overflow-x:auto;
    }
    .table{
        width:100%;
        border-collapse:collapse;
        min-width:760px;
        margin:0;
    }
    .table th,
    .table td{
        padding:14px 12px;
        border-bottom:1px solid #e5e7eb;
        text-align:left;
        vertical-align:middle;
    }
    .table th{
        background:#f9fafb;
        font-size:13px;
    }
    .text-end{ text-align:right; }
    .text-center{ text-align:center; }
    .text-muted{ color:#6b7280; }
    .small{ font-size:12px; }
    .fw-semibold{ font-weight:600; }
    .badge{
        display:inline-block;
        padding:6px 10px;
        border-radius:999px;
        font-size:12px;
        font-weight:bold;
    }
    .badge-warning{ background:#fef3c7; color:#92400e; }
    .badge-success{ background:#dcfce7; color:#166534; }
    .badge-danger{ background:#fee2e2; color:#991b1b; }
    .badge-secondary{ background:#e5e7eb; color:#374151; }
    .badge-primary{ background:#dbeafe; color:#1d4ed8; }
    .inline-form{
        display:inline-block;
        margin:0;
    }
    .status-select{
        padding:7px 10px;
        border:1px solid #d1d5db;
        border-radius:8px;
        background:#fff;
        font-size:13px;
    }
    .action-link{
        display:inline-block;
        padding:8px 10px;
        border-radius:8px;
        text-decoration:none;
        font-size:13px;
        font-weight:bold;
    }
    .action-green{ background:#198754; color:#fff; }
    .action-blue{ background:#0d6efd; color:#fff; }
    .chart-wrap{
        background:#fff;
        border-radius:16px;
        box-shadow:0 4px 18px rgba(0,0,0,.08);
        padding:20px;
    }
    .grid-4{
        display:grid;
        grid-template-columns:repeat(4, 1fr);
        gap:16px;
    }
    .grid-2{
        display:grid;
        grid-template-columns:repeat(2, 1fr);
        gap:16px;
    }
    .grid-1{
        display:grid;
        grid-template-columns:1fr;
        gap:16px;
    }
    @media (max-width: 1100px){
        .grid-4{ grid-template-columns:repeat(2, 1fr); }
        .grid-2{ grid-template-columns:1fr; }
    }
    @media (max-width: 640px){
        .grid-4{ grid-template-columns:1fr; }
    }
</style>

<div class="page-card">
    <div class="section-head">
        <div>
            <h2>Dashboard</h2>
            <div class="section-sub">
                RG Auto Sales · Mês: <?= h($mesLabel) ?> · Comissão: <?= h($percentLabel) ?> ou mínimo <?= h($minimoLabel) ?>
            </div>
        </div>

        <div class="quick-actions">
            <a class="quick-btn quick-dark" href="carro_add.php">+ Adicionar carro</a>
            <a class="quick-btn quick-success" href="nova_venda.php">+ Nova venda</a>
            <a class="quick-btn quick-outline" href="listar_carros.php">Carros</a>
            <a class="quick-btn quick-outline" href="vendas.php">Vendas</a>
            <a class="quick-btn quick-outline" href="clientes.php">Clientes</a>
            <a class="quick-btn quick-outline" href="custos.php">Custos</a>
        </div>
    </div>
</div>

<div class="grid-4">
    <div class="dash-card"><div class="card-body">
        <div class="kpi-title">Leads (hoje)</div>
        <div class="kpi-value"><?= $leadsHoje ?></div>
        <div class="text-muted small"><?= $temDataRegisto ? "Baseado em data_registo" : "Sem data_registo" ?></div>
    </div></div>

    <div class="dash-card"><div class="card-body">
        <div class="kpi-title">Leads (mês)</div>
        <div class="kpi-value"><?= $leadsMes ?></div>
        <div class="text-muted small">Agendamentos / Clientes</div>
    </div></div>

    <div class="dash-card"><div class="card-body">
        <div class="kpi-title">Vendas (mês)</div>
        <div class="kpi-value"><?= $vendasMes ?></div>
        <div class="text-muted small">Pagas: <?= $pagasMes ?> · Pendentes: <?= $pendentesMes ?> · Canceladas: <?= $canceladasMes ?></div>
    </div></div>

    <div class="dash-card"><div class="card-body">
        <div class="kpi-title">Conversão (mês)</div>
        <div class="kpi-value"><?= number_format($conversao, 1, ',', '.') ?>%</div>
        <div class="text-muted small">Vendas ÷ Leads</div>
    </div></div>
</div>

<div style="height:16px;"></div>

<div class="grid-4">
    <div class="dash-card"><div class="card-body">
        <div class="kpi-title">Comissão paga (mês)</div>
        <div class="kpi-value"><?= money($comissaoPagaMes) ?></div>
        <div class="text-muted small">Receita confirmada</div>
    </div></div>

    <div class="dash-card"><div class="card-body">
        <div class="kpi-title">Pendente a receber (mês)</div>
        <div class="kpi-value"><?= money($comissaoPendMes) ?></div>
        <div class="text-muted small">Ainda não pagas</div>
    </div></div>

    <div class="dash-card"><div class="card-body">
        <div class="kpi-title">Custos (mês)</div>
        <div class="kpi-value"><?= money($custosMes) ?></div>
        <div class="text-muted small"><?= $existeCustos ? "Despesas registadas" : "Tabela custos não existe" ?></div>
    </div></div>

    <div class="dash-card"><div class="card-body">
        <div class="kpi-title">Lucro real (mês)</div>
        <div class="kpi-value"><?= money($lucroRealMes) ?></div>
        <div class="text-muted small">Comissão paga − custos</div>
    </div></div>
</div>

<div style="height:16px;"></div>

<div class="grid-4">
    <div class="dash-card"><div class="card-body">
        <div class="kpi-title">Total de carros</div>
        <div class="kpi-value"><?= $totalCarros ?></div>
        <div class="text-muted small">Todos os carros cadastrados</div>
    </div></div>

    <div class="dash-card"><div class="card-body">
        <div class="kpi-title">Carros disponíveis</div>
        <div class="kpi-value"><?= $carrosDisponiveis ?></div>
        <div class="text-muted small">Prontos para venda</div>
    </div></div>

    <div class="dash-card"><div class="card-body">
        <div class="kpi-title">Carros vendidos</div>
        <div class="kpi-value"><?= $carrosVendidos ?></div>
        <div class="text-muted small">Registados na tabela carros</div>
    </div></div>

    <div class="dash-card"><div class="card-body">
        <div class="kpi-title">Valor do stock</div>
        <div class="kpi-value"><?= money($valorStock) ?></div>
        <div class="text-muted small">Soma dos carros disponíveis</div>
    </div></div>
</div>

<div style="height:16px;"></div>

<div class="grid-1">
    <div class="dash-card"><div class="card-body">
        <div class="kpi-title">Valor vendido</div>
        <div class="kpi-value"><?= money($valorVendido) ?></div>
        <div class="text-muted small">Baseado em preco_venda</div>
    </div></div>
</div>

<div style="height:20px;"></div>

<div class="chart-wrap">
    <div class="section-head" style="margin-bottom:10px;">
        <div class="fw-semibold">Leads e Vendas por dia (<?= h($mesLabel) ?>)</div>
        <div class="text-muted small">Atualiza automaticamente</div>
    </div>
    <canvas id="graficoMes" height="90"></canvas>
</div>

<div style="height:20px;"></div>

<div class="grid-2">
    <div>
        <div class="section-head">
            <div class="fw-semibold">Últimos leads</div>
        </div>

        <div class="table-card">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Cliente</th>
                            <th>Contato</th>
                            <th>Agendamento</th>
                            <th>Carro</th>
                            <th>Status</th>
                            <th class="text-end">Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!count($ultimosLeads)): ?>
                        <tr><td colspan="7" class="text-center text-muted">Ainda não há leads.</td></tr>
                    <?php else: foreach ($ultimosLeads as $l): ?>
                        <tr>
                            <td><?= (int)($l["id"] ?? 0) ?></td>
                            <td>
                                <div class="fw-semibold"><?= h($l["nome"] ?? "-") ?></div>
                                <div class="text-muted small"><?= h($l["data_registo"] ?? "") ?></div>
                            </td>
                            <td class="text-muted small">
                                <?= h($l["telefone"] ?? "-") ?><br>
                                <?= h($l["email"] ?? "-") ?>
                            </td>
                            <td class="text-muted small">
                                <?= h(($l["data"] ?? "-") . " " . ($l["hora"] ?? "")) ?>
                            </td>
                            <td class="text-muted small">
                                <?php
                                    $carroLead = trim(
                                        ($l["marca"] ?? "") . " " .
                                        ($l["modelo"] ?? "") . " " .
                                        (($l["ano"] ?? "") ? "(".$l["ano"].")" : "")
                                    );
                                    echo $carroLead !== "" ? h($carroLead) : "—";
                                ?>
                            </td>
                            <td>
                                <?php $stLead = $l["status"] ?? "NOVO"; ?>
                                <form method="POST" action="status.php" class="inline-form">
                                    <input type="hidden" name="id" value="<?= (int)($l["id"] ?? 0) ?>">
                                    <input type="hidden" name="token" value="<?= h($_SESSION['csrf_token']) ?>">
                                    <select name="status" class="status-select" onchange="this.form.submit()">
                                        <?php
                                            $opts = ['NOVO','CONTACTADO','AGENDADO','CONCLUIDO','CANCELADO'];
                                            foreach ($opts as $op) {
                                                $sel = ($stLead === $op) ? 'selected' : '';
                                                echo "<option value='$op' $sel>$op</option>";
                                            }
                                        ?>
                                    </select>
                                </form>
                            </td>
                            <td class="text-end">
                                <a class="action-link action-green" href="nova_venda.php?cliente_id=<?= (int)($l["id"] ?? 0) ?>">
                                    Criar venda
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div>
        <div class="section-head">
            <div class="fw-semibold">Últimas vendas</div>
            <a href="vendas.php" style="text-decoration:none;">Ver todas →</a>
        </div>

        <div class="table-card">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Cliente</th>
                            <th>Carro</th>
                            <th class="text-end">Comissão</th>
                            <th>Status</th>
                            <th class="text-end">Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!count($ultimasVendas)): ?>
                        <tr><td colspan="6" class="text-center text-muted">Ainda não há vendas registadas.</td></tr>
                    <?php else: foreach ($ultimasVendas as $v): ?>
                        <?php
                            $st = $v['status'];
                            $badge = 'secondary';
                            if ($st === 'PENDENTE') $badge = 'warning';
                            if ($st === 'PAGO') $badge = 'success';
                            if ($st === 'CANCELADO') $badge = 'danger';
                        ?>
                        <tr>
                            <td><?= (int)$v["id"] ?></td>
                            <td>
                                <div class="fw-semibold"><?= h($v["cliente_nome"]) ?></div>
                                <div class="text-muted small"><?= h($v["cliente_telefone"]) ?></div>
                            </td>
                            <td class="text-muted small"><?= h($v["marca"]." ".$v["modelo"]." (".$v["ano"].")") ?></td>
                            <td class="text-end"><?= money($v["comissao"]) ?></td>
                            <td><span class="badge badge-<?= $badge ?>"><?= h($st) ?></span></td>
                            <td class="text-end">
                                <a class="action-link action-blue" href="venda_detalhe.php?id=<?= (int)$v["id"] ?>">Ver</a>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div style="height:20px;"></div>

<div>
    <div class="section-head">
        <div class="fw-semibold">Últimos carros</div>
        <a href="listar_carros.php" style="text-decoration:none;">Ver todos →</a>
    </div>

    <div class="table-card">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Carro</th>
                        <th>Ano</th>
                        <th class="text-end">Preço</th>
                        <th class="text-end">Preço Venda</th>
                        <th>Status</th>
                        <th class="text-end">Ação</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!count($ultimosCarros)): ?>
                    <tr><td colspan="7" class="text-center text-muted">Ainda não há carros registados.</td></tr>
                <?php else: foreach ($ultimosCarros as $c): ?>
                    <?php $badge = ($c['status'] === 'vendido') ? 'success' : 'primary'; ?>
                    <tr>
                        <td><?= (int)$c['id'] ?></td>
                        <td class="fw-semibold"><?= h($c['marca'] . ' ' . $c['modelo']) ?></td>
                        <td><?= h($c['ano']) ?></td>
                        <td class="text-end"><?= money($c['preco']) ?></td>
                        <td class="text-end"><?= !empty($c['preco_venda']) ? money($c['preco_venda']) : '—' ?></td>
                        <td><span class="badge badge-<?= $badge ?>"><?= h($c['status']) ?></span></td>
                        <td class="text-end">
                            <a class="action-link action-blue" href="editar_carro.php?id=<?= (int)$c['id'] ?>">Editar</a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
const labels = <?php echo json_encode($labels, JSON_UNESCAPED_UNICODE); ?>;
const vendas = <?php echo json_encode($vendasSeries, JSON_UNESCAPED_UNICODE); ?>;
const leads  = <?php echo json_encode($leadsSeries, JSON_UNESCAPED_UNICODE); ?>;

new Chart(document.getElementById('graficoMes'), {
    type: 'line',
    data: {
        labels,
        datasets: [
            { label: 'Leads', data: leads, tension: 0.3 },
            { label: 'Vendas', data: vendas, tension: 0.3 }
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: true } },
        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
    }
});
</script>

<?php include("includes/layout_bottom.php"); ?>