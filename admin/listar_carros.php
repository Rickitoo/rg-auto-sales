<?php
// admin/listar_carros.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

include("../auth.php");
include("../conexao.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function money($v) {
    return number_format((float)$v, 2, ',', '.') . " MT";
}

$busca  = trim($_GET['busca'] ?? '');
$status = trim($_GET['status'] ?? '');

$where = [];

if ($busca !== '') {
    $buscaEsc = mysqli_real_escape_string($conexao, $busca);
    $where[] = "(c.marca LIKE '%$buscaEsc%' OR c.modelo LIKE '%$buscaEsc%')";
}

if ($status !== '' && in_array($status, ['disponivel', 'vendido'], true)) {
    $statusEsc = mysqli_real_escape_string($conexao, $status);
    $where[] = "c.status = '$statusEsc'";
}

$sql = "
    SELECT 
        c.*,
        COUNT(cf.id) AS total_fotos
    FROM carros c
    LEFT JOIN carros_fotos cf ON cf.carro_id = c.id
";

if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " GROUP BY c.id ORDER BY c.id DESC";

$res = mysqli_query($conexao, $sql);

include("includes/layout_top.php");
?>

<style>
    .list-card{
        background:#fff;
        border-radius:16px;
        padding:20px;
        box-shadow:0 4px 18px rgba(0,0,0,.08);
        margin-bottom:20px;
    }
    .section-head{
        display:flex;
        justify-content:space-between;
        align-items:flex-start;
        gap:15px;
        flex-wrap:wrap;
        margin-bottom:16px;
    }
    .section-head h2{
        margin:0;
        font-size:24px;
    }
    .section-sub{
        color:#6b7280;
        font-size:14px;
        margin-top:6px;
    }
    .top-actions{
        display:flex;
        gap:10px;
        flex-wrap:wrap;
    }
    .btn{
        display:inline-block;
        padding:10px 16px;
        border:none;
        border-radius:10px;
        text-decoration:none;
        cursor:pointer;
        font-weight:bold;
        text-align:center;
    }
    .btn-primary{ background:#0d6efd; color:#fff; }
    .btn-secondary{ background:#6c757d; color:#fff; }
    .btn-success{ background:#198754; color:#fff; }
    .btn-danger{ background:#dc3545; color:#fff; }
    .btn-dark{ background:#212529; color:#fff; }

    form.filtros{
        display:grid;
        grid-template-columns:2fr 1fr auto auto;
        gap:12px;
    }

    input, select{
        width:100%;
        padding:12px 14px;
        border:1px solid #d1d5db;
        border-radius:10px;
        font-size:14px;
        background:#fff;
    }

    .table-wrap{
        overflow-x:auto;
    }

    table{
        width:100%;
        border-collapse:collapse;
        min-width:1100px;
    }

    th, td{
        padding:14px 12px;
        border-bottom:1px solid #e5e7eb;
        text-align:left;
        vertical-align:middle;
    }

    th{
        background:#f9fafb;
        font-size:14px;
    }

    .thumb{
        width:90px;
        height:65px;
        object-fit:cover;
        border-radius:8px;
        border:1px solid #ddd;
        background:#f3f4f6;
    }

    .thumb-empty{
        display:flex;
        align-items:center;
        justify-content:center;
        color:#666;
        font-size:12px;
    }

    .badge{
        display:inline-block;
        padding:6px 10px;
        border-radius:999px;
        font-size:12px;
        font-weight:bold;
    }

    .badge-disponivel{
        background:#dcfce7;
        color:#166534;
    }

    .badge-vendido{
        background:#fee2e2;
        color:#991b1b;
    }

    .mini-badge{
        display:inline-block;
        padding:5px 8px;
        border-radius:999px;
        font-size:12px;
        font-weight:bold;
        background:#e0ecff;
        color:#0d47a1;
    }

    .actions{
        display:flex;
        gap:8px;
        flex-wrap:wrap;
    }

    .actions a{
        padding:8px 10px;
        border-radius:8px;
        color:#fff;
        text-decoration:none;
        font-size:13px;
        font-weight:bold;
    }

    .empty{
        text-align:center;
        color:#6b7280;
        padding:30px 10px;
    }

    @media (max-width: 900px){
        form.filtros{
            grid-template-columns:1fr;
        }
    }
</style>

<div class="list-card">
    <div class="section-head">
        <div>
            <h2>Carros Cadastrados</h2>
            <div class="section-sub">Gestão completa dos veículos da RG Auto Sales</div>
        </div>

        <div class="top-actions">
            <a href="dashboard.php" class="btn btn-secondary">Dashboard</a>
            <a href="adicionar_carro.php" class="btn btn-primary">+ Adicionar Carro</a>
        </div>
    </div>
</div>

<div class="list-card">
    <form method="GET" class="filtros">
        <input type="text" name="busca" placeholder="Buscar por marca ou modelo..." value="<?= h($busca) ?>">

        <select name="status">
            <option value="">Todos os status</option>
            <option value="disponivel" <?= $status === 'disponivel' ? 'selected' : '' ?>>Disponível</option>
            <option value="vendido" <?= $status === 'vendido' ? 'selected' : '' ?>>Vendido</option>
        </select>

        <button type="submit" class="btn btn-primary">Filtrar</button>
        <a href="listar_carros.php" class="btn btn-secondary">Limpar</a>
    </form>
</div>

<div class="list-card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Imagem</th>
                    <th>Marca</th>
                    <th>Modelo</th>
                    <th>Ano</th>
                    <th>Preço</th>
                    <th>Fotos</th>
                    <th>Status</th>
                    <th>Registo</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($res && mysqli_num_rows($res) > 0): ?>
                <?php while ($carro = mysqli_fetch_assoc($res)): ?>
                    <?php
                    $idCarro = (int)$carro['id'];
                    $capa = $carro['imagem'] ?? '';

                    $resFoto = mysqli_query(
                        $conexao,
                        "SELECT foto FROM carros_fotos WHERE carro_id = $idCarro ORDER BY ordem ASC, id ASC LIMIT 1"
                    );

                    if ($resFoto && mysqli_num_rows($resFoto) > 0) {
                        $fotoRow = mysqli_fetch_assoc($resFoto);
                        if (!empty($fotoRow['foto'])) {
                            $capa = $fotoRow['foto'];
                        }
                    }

                    $imgSrc = !empty($capa) ? "../uploads/" . $capa : "";
                    $statusClasse = $carro['status'] === 'vendido' ? 'badge-vendido' : 'badge-disponivel';
                    $totalFotos = (int)($carro['total_fotos'] ?? 0);
                    ?>

                    <tr>
                        <td><?= $idCarro ?></td>

                        <td>
                            <?php if ($imgSrc !== ''): ?>
                                <img src="<?= h($imgSrc) ?>" alt="Capa" class="thumb">
                            <?php else: ?>
                                <div class="thumb thumb-empty">
                                    Sem foto
                                </div>
                            <?php endif; ?>
                        </td>

                        <td><?= h($carro['marca']) ?></td>
                        <td><?= h($carro['modelo']) ?></td>
                        <td><?= h($carro['ano']) ?></td>
                        <td><?= money($carro['preco']) ?></td>

                        <td>
                            <span class="mini-badge"><?= $totalFotos ?> foto<?= $totalFotos === 1 ? '' : 's' ?></span>
                        </td>

                        <td>
                            <span class="badge <?= h($statusClasse) ?>">
                                <?= ucfirst(h($carro['status'])) ?>
                            </span>
                        </td>

                        <td>
                            <?= !empty($carro['data_registo']) ? date('d/m/Y H:i', strtotime($carro['data_registo'])) : '-' ?>
                        </td>

                        <td>
                            <div class="actions">
                                <a href="editar_carro.php?id=<?= $idCarro ?>" class="btn-primary">Editar</a>
                                <a href="gerir_fotos.php?id=<?= $idCarro ?>" class="btn-dark">Fotos</a>

                                <?php if ($carro['status'] !== 'vendido'): ?>
                                    <a href="marcar_venda.php?id=<?= $idCarro ?>" class="btn-success">Marcar Venda</a>
                                <?php endif; ?>

                                <a href="apagar_carro.php?id=<?= $idCarro ?>&csrf_token=<?= h($_SESSION['csrf_token']) ?>"
                                   class="btn-danger"
                                   onclick="return confirm('Tens certeza que queres apagar este carro?')">
                                   Apagar
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="10" class="empty">Nenhum carro encontrado.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include("includes/layout_bottom.php"); ?>