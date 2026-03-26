<?php

include("../auth.php");
include("../conexao.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// inclui financeiro (se existir)
$financeiro_path = __DIR__ . "/includes/financeiro.php";
if (file_exists($financeiro_path)) {
    include($financeiro_path);
}

// Garante CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$flash = null;

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function money($v){ return number_format((float)$v, 2, ',', '.')." MT"; }
function col_exists(mysqli $con, string $table, string $col): bool {
    $table = mysqli_real_escape_string($con, $table);
    $col   = mysqli_real_escape_string($con, $col);
    $q = mysqli_query($con, "SHOW COLUMNS FROM `$table` LIKE '$col'");
    return $q && mysqli_num_rows($q) > 0;
}

// Detecta colunas novas (pra não quebrar teu banco)
$hasLucro   = col_exists($conexao, "vendas", "lucro");
$hasTCustos = col_exists($conexao, "vendas", "total_custos");
$hasCVend   = col_exists($conexao, "vendas", "comissao_vendedor");
$hasCRG     = col_exists($conexao, "vendas", "comissao_rg");
$hasApv     = col_exists($conexao, "vendas", "precisa_aprovacao");

// ==============================
// AÇÕES (POST): pagar / cancelar / recalcular
// ==============================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao  = $_POST['acao'] ?? '';
    $id    = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $token = $_POST['token'] ?? '';

    if ($id <= 0) {
        $flash = ["type" => "danger", "msg" => "ID inválido."];
    } elseif (!hash_equals($_SESSION['csrf_token'], $token)) {
        $flash = ["type" => "danger", "msg" => "Ação bloqueada (token inválido)."];
    } elseif (!in_array($acao, ['pagar', 'cancelar', 'recalcular'], true)) {
        $flash = ["type" => "danger", "msg" => "Ação inválida."];
    } else {

        // ✅ Recalcular manualmente (sem mudar status)
        if ($acao === "recalcular") {
            if (function_exists("recalcular_venda")) {
                $calc = recalcular_venda($conexao, $id);
                $flash = $calc["ok"]
                    ? ["type"=>"success","msg"=>"Venda recalculada com sucesso."]
                    : ["type"=>"danger","msg"=>"Falhou recalcular: ".$calc["erro"]];
            } else {
                $flash = ["type"=>"warning","msg"=>"financeiro.php não encontrado — não consegui recalcular."];
            }
        }

        // ✅ Pagar / Cancelar
        if ($acao === "pagar" || $acao === "cancelar") {
            $novoStatus = ($acao === 'pagar') ? 'PAGO' : 'CANCELADO';

            // Se tiver regra de aprovação e quiseres travar:
            if ($acao === "pagar" && $hasApv) {
                $chk = mysqli_prepare($conexao, "SELECT precisa_aprovacao FROM vendas WHERE id=? LIMIT 1");
                mysqli_stmt_bind_param($chk, "i", $id);
                mysqli_stmt_execute($chk);
                $r = mysqli_stmt_get_result($chk);
                $row = mysqli_fetch_assoc($r);
                mysqli_stmt_close($chk);

                if ($row && (int)$row["precisa_aprovacao"] === 1) {
                    $flash = ["type"=>"warning","msg"=>"Esta venda precisa de aprovação (lucro abaixo do mínimo). Não foi marcada como PAGA."];
                    $novoStatus = null;
                }
            }

            if ($novoStatus) {
                // Só atualiza se ainda não estiver PAGO/CANCELADO
                $stmt = mysqli_prepare($conexao, "UPDATE vendas SET status = ? WHERE id = ? AND status = 'PENDENTE'");
                mysqli_stmt_bind_param($stmt, "si", $novoStatus, $id);

                if (mysqli_stmt_execute($stmt)) {
                    if (mysqli_stmt_affected_rows($stmt) > 0) {

                        // tenta recalcular após mudar status
                        if (function_exists("recalcular_venda")) {
                            $calc = recalcular_venda($conexao, $id);
                            if (!$calc["ok"]) {
                                $flash = ["type" => "warning", "msg" => "Venda atualizada para {$novoStatus}, mas falhou recalcular: ".$calc["erro"]];
                            } else {
                                $flash = ["type" => "success", "msg" => "Venda atualizada para {$novoStatus} e recalculada."];
                            }
                        } else {
                            $flash = ["type" => "success", "msg" => "Venda atualizada para {$novoStatus}."];
                        }

                    } else {
                        $flash = ["type" => "warning", "msg" => "Nada mudou. Talvez a venda já não esteja PENDENTE."];
                    }
                } else {
                    $flash = ["type" => "danger", "msg" => "Erro ao atualizar: " . mysqli_error($conexao)];
                }
                mysqli_stmt_close($stmt);
            }
        }
    }
}

// ==============================
// FILTROS (GET)
// ==============================
$status   = $_GET['status'] ?? 'TODOS';
$data_de  = $_GET['data_de'] ?? '';
$data_ate = $_GET['data_ate'] ?? '';
$q        = trim($_GET['q'] ?? '');

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$where = " WHERE 1=1 ";
$params = [];
$types  = "";

if (in_array($status, ['PENDENTE','PAGO','CANCELADO'], true)) {
    $where .= " AND v.status = ? ";
    $types .= "s";
    $params[] = $status;
}

if ($data_de !== '') {
    $where .= " AND v.data_venda >= ? ";
    $types .= "s";
    $params[] = $data_de;
}

if ($data_ate !== '') {
    $where .= " AND v.data_venda <= ? ";
    $types .= "s";
    $params[] = $data_ate;
}

if ($q !== '') {
    $where .= " AND (c.nome LIKE ? OR c.telefone LIKE ? OR c.email LIKE ?) ";
    $types .= "sss";
    $like = "%{$q}%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

// ==============================
// COUNT total
// ==============================
$sqlCount = "
    SELECT COUNT(*) AS total
    FROM vendas v
    INNER JOIN clientes c ON c.id = v.cliente_id
    $where
";
$stmtCount = mysqli_prepare($conexao, $sqlCount);
if ($types !== "") mysqli_stmt_bind_param($stmtCount, $types, ...$params);
mysqli_stmt_execute($stmtCount);
$resCount = mysqli_stmt_get_result($stmtCount);
$totalRows = (int)(mysqli_fetch_assoc($resCount)['total'] ?? 0);
mysqli_stmt_close($stmtCount);

$totalPages = max(1, (int)ceil($totalRows / $perPage));

// ==============================
// LISTA vendas (colunas antigas + novas se existirem)
// ==============================
$selectExtras = "";
if ($hasTCustos) $selectExtras .= ", v.total_custos";
if ($hasLucro)   $selectExtras .= ", v.lucro";
if ($hasCVend)   $selectExtras .= ", v.comissao_vendedor";
if ($hasCRG)     $selectExtras .= ", v.comissao_rg";
if ($hasApv)     $selectExtras .= ", v.precisa_aprovacao";

$sqlList = "
    SELECT
        v.id, v.data_venda, v.marca, v.modelo, v.ano,
        v.valor_carro, v.comissao, v.status
        $selectExtras,
        c.nome AS cliente_nome, c.telefone AS cliente_telefone, c.email AS cliente_email
    FROM vendas v
    INNER JOIN clientes c ON c.id = v.cliente_id
    $where
    ORDER BY v.id DESC
    LIMIT ? OFFSET ?
";
$stmtList = mysqli_prepare($conexao, $sqlList);

$typesList = $types . "ii";
$paramsList = $params;
$paramsList[] = $perPage;
$paramsList[] = $offset;

mysqli_stmt_bind_param($stmtList, $typesList, ...$paramsList);
mysqli_stmt_execute($stmtList);
$resList = mysqli_stmt_get_result($stmtList);

$vendas = [];
while ($row = mysqli_fetch_assoc($resList)) $vendas[] = $row;
mysqli_stmt_close($stmtList);

// ==============================
// Totais rápidos (usa comissao_rg se existir; senão usa comissao antiga)
// ==============================
$campoComissao = $hasCRG ? "v.comissao_rg" : "v.comissao";

$sqlTotals = "
    SELECT
        SUM(CASE WHEN v.status='PAGO' THEN $campoComissao ELSE 0 END) AS comissao_paga,
        SUM(CASE WHEN v.status='PENDENTE' THEN $campoComissao ELSE 0 END) AS comissao_pendente,
        COUNT(CASE WHEN v.status='PAGO' THEN 1 END) AS vendas_pagas,
        COUNT(CASE WHEN v.status='PENDENTE' THEN 1 END) AS vendas_pendentes
    FROM vendas v
    INNER JOIN clientes c ON c.id = v.cliente_id
    $where
";
$stmtTot = mysqli_prepare($conexao, $sqlTotals);
if ($types !== "") mysqli_stmt_bind_param($stmtTot, $types, ...$params);
mysqli_stmt_execute($stmtTot);
$resTot = mysqli_stmt_get_result($stmtTot);
$tot = mysqli_fetch_assoc($resTot) ?: [];
mysqli_stmt_close($stmtTot);

$comissaoPaga = (float)($tot['comissao_paga'] ?? 0);
$comissaoPend = (float)($tot['comissao_pendente'] ?? 0);
$vendasPagas  = (int)($tot['vendas_pagas'] ?? 0);
$vendasPend   = (int)($tot['vendas_pendentes'] ?? 0);

function buildQuery(array $extra = []) {
    $base = $_GET;
    foreach ($extra as $k => $v) $base[$k] = $v;
    return http_build_query($base);
}
?>
<!doctype html>
<html lang="pt">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin | Vendas - RG Auto Sales</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background: #f6f7fb; }
    .card-kpi { border: 0; border-radius: 16px; }
    .table-wrap { border-radius: 16px; overflow: hidden; }
  </style>
</head>
<body>
<div class="container py-4">

  <div class="d-flex gap-2 mb-3">
    <a class="btn btn-success" href="nova_venda.php">+ Nova venda</a>
    <a class="btn btn-outline-secondary" href="export_vendas_csv.php">Exportar CSV</a>
  </div>

  <div class="d-flex align-items-center justify-content-between mb-3">
    <div>
      <h3 class="mb-0">Vendas</h3>
      <small class="text-muted">
        <?= $hasLucro ? "Modelo novo: lucro → vendedor/RG (automático)" : "Modelo atual: comissao antiga (ainda sem migração total)" ?>
      </small>
    </div>
    <a class="btn btn-outline-dark" href="dashboard.php">Voltar ao Dashboard</a>
  </div>

  <?php if ($flash): ?>
    <div class="alert alert-<?php echo h($flash['type']); ?> alert-dismissible fade show" role="alert">
      <?php echo h($flash['msg']); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
    </div>
  <?php endif; ?>

  <!-- KPIs -->
  <div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card card-kpi shadow-sm"><div class="card-body">
      <div class="text-muted small">Vendas pagas</div><div class="fs-3 fw-semibold"><?php echo $vendasPagas; ?></div>
    </div></div></div>

    <div class="col-md-3"><div class="card card-kpi shadow-sm"><div class="card-body">
      <div class="text-muted small">Vendas pendentes</div><div class="fs-3 fw-semibold"><?php echo $vendasPend; ?></div>
    </div></div></div>

    <div class="col-md-3"><div class="card card-kpi shadow-sm"><div class="card-body">
      <div class="text-muted small"><?= $hasCRG ? "Comissão RG paga" : "Comissão paga" ?></div>
      <div class="fs-5 fw-semibold"><?php echo number_format($comissaoPaga, 2, ',', '.'); ?> MT</div>
    </div></div></div>

    <div class="col-md-3"><div class="card card-kpi shadow-sm"><div class="card-body">
      <div class="text-muted small"><?= $hasCRG ? "Comissão RG pendente" : "Pendente a receber" ?></div>
      <div class="fs-5 fw-semibold"><?php echo number_format($comissaoPend, 2, ',', '.'); ?> MT</div>
    </div></div></div>
  </div>

  <!-- Filtros -->
  <div class="card shadow-sm border-0 mb-3" style="border-radius: 16px;">
    <div class="card-body">
      <form class="row g-2 align-items-end" method="GET" action="">
        <div class="col-md-2">
          <label class="form-label">Status</label>
          <select name="status" class="form-select">
            <option value="TODOS" <?php echo ($status==='TODOS')?'selected':''; ?>>Todos</option>
            <option value="PENDENTE" <?php echo ($status==='PENDENTE')?'selected':''; ?>>Pendente</option>
            <option value="PAGO" <?php echo ($status==='PAGO')?'selected':''; ?>>Pago</option>
            <option value="CANCELADO" <?php echo ($status==='CANCELADO')?'selected':''; ?>>Cancelado</option>
          </select>
        </div>

        <div class="col-md-2">
          <label class="form-label">Data (de)</label>
          <input type="date" name="data_de" class="form-control" value="<?php echo h($data_de); ?>">
        </div>

        <div class="col-md-2">
          <label class="form-label">Data (até)</label>
          <input type="date" name="data_ate" class="form-control" value="<?php echo h($data_ate); ?>">
        </div>

        <div class="col-md-4">
          <label class="form-label">Pesquisar (nome/telefone/email)</label>
          <input type="text" name="q" class="form-control" placeholder="Ex: Gani / 84... / email" value="<?php echo h($q); ?>">
        </div>

        <div class="col-md-2 d-grid">
          <button class="btn btn-dark" type="submit">Filtrar</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Tabela -->
  <div class="table-wrap shadow-sm bg-white">
    <table class="table table-hover mb-0 align-middle">
      <thead class="table-light">
        <tr>
          <th>ID</th>
          <th>Data</th>
          <th>Cliente</th>
          <th>Carro</th>
          <th class="text-end">Valor</th>
          <th class="text-end"><?= $hasCRG ? "RG" : "Comissão" ?></th>
          <?php if($hasLucro): ?><th class="text-end">Lucro</th><?php endif; ?>
          <th>Status</th>
          <th class="text-end">Ações</th>
        </tr>
      </thead>
      <tbody>
      <?php if (count($vendas) === 0): ?>
        <tr><td colspan="<?php echo $hasLucro?9:8; ?>" class="text-center py-4 text-muted">Nenhuma venda encontrada.</td></tr>
      <?php else: ?>
        <?php foreach ($vendas as $v): ?>
          <tr>
            <td><?php echo (int)$v['id']; ?></td>
            <td><?php echo h($v['data_venda']); ?></td>
            <td>
              <div class="fw-semibold"><?php echo h($v['cliente_nome']); ?></div>
              <div class="text-muted small"><?php echo h($v['cliente_telefone']); ?> · <?php echo h($v['cliente_email']); ?></div>
            </td>
            <td><?php echo h($v['marca']); ?> <?php echo h($v['modelo']); ?> (<?php echo h($v['ano']); ?>)</td>
            <td class="text-end"><?php echo number_format((float)$v['valor_carro'], 2, ',', '.'); ?> MT</td>

            <td class="text-end">
              <?php
                if ($hasCRG && isset($v['comissao_rg'])) echo number_format((float)$v['comissao_rg'], 2, ',', '.') . " MT";
                else echo number_format((float)$v['comissao'], 2, ',', '.') . " MT";
              ?>
            </td>

            <?php if($hasLucro): ?>
              <td class="text-end"><?php echo number_format((float)$v['lucro'], 2, ',', '.'); ?> MT</td>
            <?php endif; ?>

            <td>
              <?php
                $st = $v['status'];
                $badge = 'secondary';
                if ($st === 'PENDENTE') $badge = 'warning';
                if ($st === 'PAGO') $badge = 'success';
                if ($st === 'CANCELADO') $badge = 'danger';
              ?>
              <span class="badge text-bg-<?php echo $badge; ?>"><?php echo h($st); ?></span>

              <?php if ($hasApv && isset($v['precisa_aprovacao']) && (int)$v['precisa_aprovacao'] === 1): ?>
                <span class="badge text-bg-dark ms-1">Precisa aprovação</span>
              <?php endif; ?>
            </td>

            <td class="text-end">
              <a class="btn btn-sm btn-outline-primary" href="venda_detalhe.php?id=<?php echo (int)$v['id']; ?>">Ver</a>

              <!-- ✅ Custos por venda -->
              <a class="btn btn-sm btn-outline-secondary" href="custos.php?venda_id=<?php echo (int)$v['id']; ?>">Custos</a>

              <!-- ✅ Recalcular (opcional) -->
              <?php if (function_exists("recalcular_venda") && $st !== 'CANCELADO'): ?>
                <form class="d-inline" method="POST" action="">
                  <input type="hidden" name="id" value="<?php echo (int)$v['id']; ?>">
                  <input type="hidden" name="acao" value="recalcular">
                  <input type="hidden" name="token" value="<?php echo h($_SESSION['csrf_token']); ?>">
                  <button class="btn btn-sm btn-outline-dark" type="submit">Recalcular</button>
                </form>
              <?php endif; ?>

              <?php if ($st === 'PENDENTE'): ?>
                <form class="d-inline" method="POST" action="">
                  <input type="hidden" name="id" value="<?php echo (int)$v['id']; ?>">
                  <input type="hidden" name="acao" value="pagar">
                  <input type="hidden" name="token" value="<?php echo h($_SESSION['csrf_token']); ?>">
                  <button class="btn btn-sm btn-success" type="submit" onclick="return confirm('Marcar esta venda como PAGA?');">
                    Marcar Pago
                  </button>
                </form>

                <form class="d-inline" method="POST" action="">
                  <input type="hidden" name="id" value="<?php echo (int)$v['id']; ?>">
                  <input type="hidden" name="acao" value="cancelar">
                  <input type="hidden" name="token" value="<?php echo h($_SESSION['csrf_token']); ?>">
                  <button class="btn btn-sm btn-outline-danger" type="submit" onclick="return confirm('Cancelar esta venda?');">
                    Cancelar
                  </button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Paginação -->
  <div class="d-flex justify-content-between align-items-center mt-3">
    <div class="text-muted small">
      Total: <?php echo $totalRows; ?> venda(s) · Página <?php echo $page; ?> de <?php echo $totalPages; ?>
    </div>

    <nav>
      <ul class="pagination mb-0">
        <li class="page-item <?php echo ($page<=1)?'disabled':''; ?>">
          <a class="page-link" href="?<?php echo buildQuery(['page' => $page-1]); ?>">Anterior</a>
        </li>
        <li class="page-item <?php echo ($page>=$totalPages)?'disabled':''; ?>">
          <a class="page-link" href="?<?php echo buildQuery(['page' => $page+1]); ?>">Próxima</a>
        </li>
      </ul>
    </nav>
  </div>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
