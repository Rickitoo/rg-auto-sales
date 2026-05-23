<?php
require_once __DIR__ . '/../../core/bootstrap.php';
require_admin();

redirect_to('admin/leads/listar_leads.php' . (($_SERVER['QUERY_STRING'] ?? '') !== '' ? '?' . $_SERVER['QUERY_STRING'] : ''));

if ($_SESSION['user']['role'] !== 'admin') {
    redirect_to('auth/login.php');
    exit();
}

if (!function_exists('h')) {
function h($v){
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
}

// =============================
// BUSCAR LEADS
// =============================
$sql = "
    SELECT 
        l.*,
        c.marca,
        c.modelo
    FROM leads l
    LEFT JOIN carros c 
        ON l.carro_id = c.id
    ORDER BY l.id DESC
";

$result = mysqli_query($conexao, $sql);

if (!$result) {
    die("Erro ao buscar leads: " . mysqli_error($conexao));
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>CRM Leads - RG Auto Sales</title>

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    font-family:Arial, sans-serif;
    background:#0f172a;
    color:white;
    padding:20px;
}

h2{
    margin-bottom:20px;
}

/* =============================
   PESQUISA
============================= */

.search-box{
    margin-bottom:20px;
}

.search-box input{
    width:100%;
    padding:12px;
    border:none;
    border-radius:8px;
    background:#1e293b;
    color:white;
    font-size:14px;
}

/* =============================
   TABELA
============================= */

.table-container{
    overflow-x:auto;
}

table{
    width:100%;
    border-collapse:collapse;
    background:#1e293b;
    border-radius:10px;
    overflow:hidden;
}

th{
    background:#020617;
    padding:14px;
    text-align:left;
    font-size:14px;
}

td{
    padding:14px;
    border-bottom:1px solid #334155;
    font-size:14px;
}

tr:hover{
    background:#273449;
}

/* =============================
   STATUS
============================= */

.status{
    padding:6px 12px;
    border-radius:20px;
    font-size:12px;
    font-weight:bold;
    display:inline-block;
    text-transform:capitalize;
}

.status-novo{
    background:#3b82f6;
}

.status-contactado{
    background:#f59e0b;
    color:black;
}

.status-interessado{
    background:#8b5cf6;
}

.status-negociacao{
    background:#eab308;
    color:black;
}

.status-fechado{
    background:#22c55e;
}

.status-perdido{
    background:#ef4444;
}

/* =============================
   BOTÕES
============================= */

.actions{
    display:flex;
    flex-wrap:wrap;
    gap:6px;
}

.btn{
    padding:7px 10px;
    border-radius:6px;
    text-decoration:none;
    color:white;
    font-size:12px;
    font-weight:bold;
    transition:0.2s;
}

.btn:hover{
    opacity:0.85;
}

.btn-view{
    background:#3b82f6;
}

.btn-whatsapp{
    background:#22c55e;
}

.btn-status{
    background:#f59e0b;
}

.btn-venda{
    background:#ec4899;
}

/* =============================
   RESPONSIVO
============================= */

@media(max-width:768px){

    table{
        font-size:12px;
    }

    th, td{
        padding:10px;
    }

    .btn{
        font-size:11px;
        padding:6px 8px;
    }
}

</style>
</head>

<body>

<h2>CRM Leads - RG Auto Sales</h2>

<!-- PESQUISA -->
<div class="search-box">
    <input 
        type="text" 
        id="searchInput"
        placeholder="Pesquisar por nome, telefone ou carro..."
        onkeyup="searchTable()"
    >
</div>

<div class="table-container">

<table id="leadsTable">

<thead>
<tr>
    <th>ID</th>
    <th>Cliente</th>
    <th>Telefone</th>
    <th>Carro</th>
    <th>Status</th>
    <th>Data</th>
    <th>Ações</th>
</tr>
</thead>

<tbody>

<?php while($lead = mysqli_fetch_assoc($result)): ?>

<?php
$status = strtolower(trim($lead['status'] ?? 'novo'));

$telefone = preg_replace('/\D/', '', $lead['telefone']);

$carro = trim(
    ($lead['marca'] ?? '-') . ' ' .
    ($lead['modelo'] ?? '')
);
?>

<tr>

    <td>#<?= (int)$lead['id'] ?></td>

    <td><?= h($lead['nome']) ?></td>

    <td><?= h($lead['telefone']) ?></td>

    <td><?= h($carro) ?></td>

    <td>
        <span class="status status-<?= h($status) ?>">
            <?= ucfirst(h($status)) ?>
        </span>
    </td>

    <td>
        <?= h(date('d/m/Y H:i', strtotime($lead['created_at'] ?? 'now'))) ?>
    </td>

    <td>

        <div class="actions">

            <!-- VER -->
            <a 
                class="btn btn-view"
                href="ver_lead.php?id=<?= (int)$lead['id'] ?>"
            >
                Ver
            </a>

            <!-- WHATSAPP -->
            <a 
                class="btn btn-whatsapp"
                href="https://wa.me/258<?= $telefone ?>"
                target="_blank"
            >
                WhatsApp
            </a>

            <!-- CONTACTADO -->
            <a 
                class="btn btn-status"
                href="leads_status.php?id=<?= (int)$lead['id'] ?>&s=contactado"
            >
                Contactado
            </a>

            <!-- NEGOCIAÇÃO -->
            <a 
                class="btn btn-status"
                href="leads_status.php?id=<?= (int)$lead['id'] ?>&s=negociacao"
            >
                Negociação
            </a>

            <!-- FECHADO -->
            <a 
                class="btn btn-status"
                href="leads_status.php?id=<?= (int)$lead['id'] ?>&s=fechado"
            >
                Fechado
            </a>

            <!-- PERDIDO -->
            <a 
                class="btn btn-status"
                href="leads_status.php?id=<?= (int)$lead['id'] ?>&s=perdido"
            >
                Perdido
            </a>

            <!-- VENDA -->
            <a 
                class="btn btn-venda"
                href="<?= h(url('admin/vendas/marcar_venda.php?id=' . (int)$lead['id'])) ?>"
            >
                Vender
            </a>

        </div>

    </td>

</tr>

<?php endwhile; ?>

</tbody>
</table>

</div>

<!-- PESQUISA JS -->
<script>

function searchTable(){

    let input = document.getElementById("searchInput");
    let filter = input.value.toLowerCase();

    let table = document.getElementById("leadsTable");
    let tr = table.getElementsByTagName("tr");

    for(let i = 1; i < tr.length; i++){

        let rowText = tr[i].textContent.toLowerCase();

        if(rowText.includes(filter)){
            tr[i].style.display = "";
        } else {
            tr[i].style.display = "none";
        }
    }
}

</script>

</body>
</html>
