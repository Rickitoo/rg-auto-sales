<?php
require_once __DIR__ . '/../app/core/bootstrap.php';
require_admin();

if ($_SESSION['user']['role'] !== 'admin') {
    redirect_to('auth/login.php');
    exit();
}


// ===============================
// HELPERS
// ===============================
function money($v) { return number_format((float)$v, 2, ',', '.') . " MT"; }
function h($v){ return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }

// ===============================
// KPIs CARROS
// ===============================
$sql = "
SELECT
    COUNT(*) as total,
    SUM(CASE WHEN status='disponivel' THEN 1 ELSE 0 END) as disponiveis,
    SUM(CASE WHEN status='vendido' THEN 1 ELSE 0 END) as vendidos,
    SUM(CASE WHEN status='disponivel' THEN preco ELSE 0 END) as valor_stock
FROM carros
";

$res = mysqli_query($conexao, $sql);
$kpi = mysqli_fetch_assoc($res) ?: [];

$total = (int)($kpi['total'] ?? 0);
$disponiveis = (int)($kpi['disponiveis'] ?? 0);
$vendidos = (int)($kpi['vendidos'] ?? 0);
$valorStock = (float)($kpi['valor_stock'] ?? 0);

// ===============================
// CARROS PARADOS (NÃO VENDEM)
// ===============================
$sqlParados = "
SELECT id, marca, modelo, preco, data_registo
FROM carros
WHERE status='disponivel'
ORDER BY data_registo ASC
LIMIT 5
";

$resP = mysqli_query($conexao, $sqlParados);
$parados = [];
if ($resP) while ($r = mysqli_fetch_assoc($resP)) $parados[] = $r;

// ===============================
// ÚLTIMOS CARROS
// ===============================
$sqlUltimos = "
SELECT id, marca, modelo, preco, status
FROM carros
ORDER BY id DESC
LIMIT 5
";

$resU = mysqli_query($conexao, $sqlUltimos);
$ultimos = [];
if ($resU) while ($r = mysqli_fetch_assoc($resU)) $ultimos[] = $r;

require_once __DIR__ . '/../includes/layout_top.php';
?>

<h2>🚗 Dashboard de Carros</h2>

<div class="grid-4">

    <div class="dash-card">
        <div class="card-body">
            <div class="kpi-title">Total de carros</div>
            <div class="kpi-value"><?= $total ?></div>
        </div>
    </div>

    <div class="dash-card">
        <div class="card-body">
            <div class="kpi-title">Disponíveis</div>
            <div class="kpi-value"><?= $disponiveis ?></div>
        </div>
    </div>

    <div class="dash-card">
        <div class="card-body">
            <div class="kpi-title">Vendidos</div>
            <div class="kpi-value"><?= $vendidos ?></div>
        </div>
    </div>

    <div class="dash-card">
        <div class="card-body">
            <div class="kpi-title">Valor em stock</div>
            <div class="kpi-value"><?= money($valorStock) ?></div>
        </div>
    </div>

</div>

<br>

<div class="dash-card" style="background:#fff3cd;">
    <div class="card-body">
        <div class="kpi-title">⚠️ Carros parados (precisam vender)</div>

        <?php foreach($parados as $c): ?>
            <div>
                <strong><?= h($c['marca'].' '.$c['modelo']) ?></strong>
                (<?= money($c['preco']) ?>)

                <a href="editar_carro.php?id=<?= $c['id'] ?>">
                    ✏️ Editar
                </a>
            </div>
            <hr>
        <?php endforeach; ?>

    </div>
</div>

<br>

<div class="dash-card">
    <div class="card-body">
        <div class="kpi-title">🆕 Últimos carros adicionados</div>

        <?php foreach($ultimos as $c): ?>
            <div>
                <strong><?= h($c['marca'].' '.$c['modelo']) ?></strong>
                - <?= money($c['preco']) ?>
                (<?= h($c['status']) ?>)
            </div>
            <hr>
        <?php endforeach; ?>

    </div>
</div>

<?php if($disponiveis > 10): ?>
    <div style="background:#fee2e2;padding:15px;border-radius:10px;margin-top:15px;">
        ⚠️ Tens muitos carros parados → precisas vender mais
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/layout_bottom.php'; ?>