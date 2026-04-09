<?php
session_start();

include("../auth.php");
include("../conexao.php");
include("../auth_check.php");
include("admin/includes/auth_user.php");

$user_id = $_SESSION['user_id'] ?? 0;

// =======================
// FUNÇÕES
// =======================
function money($v) {
    return number_format((float)$v, 2, ',', '.') . " MT";
}

// =======================
// VENDAS DO USER
// =======================
$sql = "
    SELECT
        COUNT(*) as total_vendas,
        SUM(CASE WHEN status='PAGO' THEN comissao ELSE 0 END) as comissao_paga,
        SUM(CASE WHEN status='PENDENTE' THEN comissao ELSE 0 END) as comissao_pendente
    FROM vendas
    WHERE user_id = ?
";

$stmt = mysqli_prepare($conexao, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($res);

$totalVendas = $data['total_vendas'] ?? 0;
$comissaoPaga = $data['comissao_paga'] ?? 0;
$comissaoPendente = $data['comissao_pendente'] ?? 0;

mysqli_stmt_close($stmt);

// =======================
// TOTAL LEADS (REAL)
// =======================
$sqlTotalLeads = "
    SELECT COUNT(*) as total
    FROM clientes
    WHERE user_id = ?
";

$stmt = mysqli_prepare($conexao, $sqlTotalLeads);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$totalLeads = mysqli_fetch_assoc($res)['total'] ?? 0;
mysqli_stmt_close($stmt);

// =======================
// CONVERSÃO
// =======================
$conversao = 0;
if ($totalLeads > 0) {
    $conversao = ($totalVendas / $totalLeads) * 100;
}

// =======================
// LEADS RECENTES
// =======================
$sqlLeads = "
    SELECT id, nome, telefone, status
    FROM clientes
    WHERE user_id = ?
    ORDER BY id DESC
    LIMIT 10
";

$stmt = mysqli_prepare($conexao, $sqlLeads);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

$leads = [];
while ($row = mysqli_fetch_assoc($res)) {
    $leads[] = $row;
}
mysqli_stmt_close($stmt);

// =======================
// LEADS SEM RESPOSTA
// =======================
$sqlSemResposta = "
    SELECT COUNT(*) as total
    FROM clientes
    WHERE user_id = ? AND status = 'NOVO'
";

$stmt = mysqli_prepare($conexao, $sqlSemResposta);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$semResposta = mysqli_fetch_assoc($res)['total'] ?? 0;
mysqli_stmt_close($stmt);

// =======================
// FOLLOW-UPS HOJE
// =======================
$sqlFollowups = "
    SELECT COUNT(*) as total
    FROM followups
    WHERE user_id = ? AND proxima_data <= NOW()
";

$stmt = mysqli_prepare($conexao, $sqlFollowups);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$totalFollowups = mysqli_fetch_assoc($res)['total'] ?? 0;
mysqli_stmt_close($stmt);
?>

<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title>User Dashboard</title>

<style>
body { font-family: Arial; background:#f5f6fa; }

.grid {
    display:grid;
    grid-template-columns: repeat(3,1fr);
    gap:15px;
}

.card {
    background:#fff;
    padding:15px;
    border-radius:10px;
    box-shadow:0 2px 10px rgba(0,0,0,0.1);
}

table {
    width:100%;
    margin-top:20px;
    background:#fff;
    border-radius:10px;
}

th, td {
    padding:10px;
    border-bottom:1px solid #eee;
    text-align:left;
}

button {
    margin-top:15px;
    padding:10px;
}
</style>
</head>

<body>

<h2>Dashboard do Vendedor</h2>

<button onclick="location.reload()">Atualizar</button>

<div class="grid">

<div class="card">
    <h3>Vendas</h3>
    <p><?= $totalVendas ?></p>
</div>

<div class="card">
    <h3>Comissão Recebida</h3>
    <p><?= money($comissaoPaga) ?></p>
</div>

<div class="card">
    <h3>Pendente</h3>
    <p><?= money($comissaoPendente) ?></p>
</div>

<div class="card">
    <h3>Conversão</h3>
    <p><?= number_format($conversao,1,',','.') ?>%</p>
</div>

<div class="card">
    <h3>Leads sem resposta</h3>
    <p><?= $semResposta ?></p>
</div>

<div class="card">
    <h3>Follow-ups hoje</h3>
    <p><?= $totalFollowups ?></p>
</div>

</div>

<h3>Leads Recentes</h3>

<table>
<tr>
    <th>Nome</th>
    <th>Telefone</th>
    <th>Status</th>
    <th>Ação</th>
</tr>

<?php foreach($leads as $l): ?>
<tr>
    <td><?= htmlspecialchars($l['nome']) ?></td>
    <td><?= htmlspecialchars($l['telefone']) ?></td>
    <td><?= htmlspecialchars($l['status']) ?></td>
    <td>
        <a target="_blank"
        href="https://wa.me/258<?= preg_replace('/\D/','',$l['telefone']) ?>?text=<?= urlencode('Olá '.$l['nome'].', estou a dar seguimento ao seu interesse. Ainda está interessado?') ?>">
        WhatsApp
        </a>
    </td>
</tr>
<?php endforeach; ?>

</table>

</body>
</html>