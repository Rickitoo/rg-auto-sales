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
// DADOS BASE
// ===============================
$hoje = date("Y-m-d");
$inicioMes = date('Y-m-01');
$fimMes = date('Y-m-t');

// ===============================
// LEADS EM ATRASO (FOLLOW-UP)
// ===============================
$agora = date("Y-m-d H:i:s");
$sqlFollow = "
    SELECT id, nome, telefone
    FROM clientes
    WHERE proximo_followup IS NOT NULL
    AND proximo_followup <= '$agora'
";
$res = mysqli_query($conexao, $sqlFollow);
$leadsFollow = [];
if($res) while($r = mysqli_fetch_assoc($res)) $leadsFollow[] = $r;

// ===============================
// NEGOCIAÇÕES
// ===============================
$sqlNeg = "
    SELECT id, nome, telefone
    FROM clientes
    WHERE estado='negociacao' AND status='ativo'
";
$res = mysqli_query($conexao, $sqlNeg);
$negociacoes = [];
if($res) while($r = mysqli_fetch_assoc($res)) $negociacoes[] = $r;

// ===============================
// CARROS PARADOS
// ===============================
$sqlCarros = "
    SELECT id, marca, modelo
    FROM carros
    WHERE status='disponivel'
    ORDER BY data_registo ASC
    LIMIT 5
";
$res = mysqli_query($conexao, $sqlCarros);
$carros = [];
if($res) while($r = mysqli_fetch_assoc($res)) $carros[] = $r;

// ===============================
// DINHEIRO PENDENTE
// ===============================
$stmt = mysqli_prepare($conexao, "
    SELECT SUM(comissao) as total
    FROM vendas
    WHERE status='PENDENTE'
    AND data_venda BETWEEN ? AND ?
");
mysqli_stmt_bind_param($stmt, "ss", $inicioMes, $fimMes);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$pendente = (float)(mysqli_fetch_assoc($res)['total'] ?? 0);
mysqli_stmt_close($stmt);

// ===============================
// MISSÕES
// ===============================
$missoes = [];

if(count($leadsFollow) > 0){
    $missoes[] = [
        'tipo' => 'lead',
        'texto' => "Contactar " . count($leadsFollow) . " leads em atraso"
    ];
}

if(count($negociacoes) > 0){
    $missoes[] = [
        'tipo' => 'negociacao',
        'texto' => "Fechar pelo menos 1 negociação hoje"
    ];
}

if(count($carros) > 0){
    $missoes[] = [
        'tipo' => 'carro',
        'texto' => "Promover " . count($carros) . " carros parados"
    ];
}

if($pendente > 0){
    $missoes[] = [
        'tipo' => 'financeiro',
        'texto' => "Cobrar clientes (" . money($pendente) . ")"
    ];
}

// ===============================
// PRIORIDADE
// ===============================
$prioridade = [
    'financeiro' => 1,
    'lead' => 2,
    'negociacao' => 3,
    'carro' => 4
];

usort($missoes, function($a,$b) use ($prioridade){
    return $prioridade[$a['tipo']] - $prioridade[$b['tipo']];
});

require_once __DIR__ . '/../includes/layout_top.php';
?>

<h2>🧠 Painel Inteligente</h2>

<?php if(empty($missoes)): ?>
    <div style="background:#d4edda;padding:15px;border-radius:10px;">
        ✅ Tudo em dia! Continua assim.
    </div>
<?php endif; ?>

<?php foreach($missoes as $m): ?>
    <div style="background:#111;color:#fff;padding:15px;margin-bottom:10px;border-radius:10px;">
        <?= h($m['texto']) ?>
    </div>
<?php endforeach; ?>

<hr>

<h3>⚡ Ações rápidas</h3>

<!-- LEADS -->
<?php foreach($leadsFollow as $l): ?>
    <?php $tel = preg_replace('/[^0-9]/','',$l['telefone']); ?>
    <div>
        <?= h($l['nome']) ?>
        <a target="_blank"
           href="https://wa.me/258<?= $tel ?>?text=<?= urlencode("Olá {$l['nome']}, estou a dar seguimento ao seu pedido.") ?>">
           🚀 Contactar
        </a>
    </div>
<?php endforeach; ?>

<br> 

<!-- NEGOCIAÇÕES -->
<?php foreach($negociacoes as $l): ?>
    <?php $tel = preg_replace('/[^0-9]/','',$l['telefone']); ?>
    <div>
        <?= h($l['nome']) ?>
        <a target="_blank" href="https://wa.me/258<?= $tel ?>">
            💰 Fechar
        </a>
    </div>
<?php endforeach; ?>

<?php require_once __DIR__ . '/../includes/layout_bottom.php'; ?>
