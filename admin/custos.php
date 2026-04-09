<?php
include("../auth.php");
include("../conexao.php");
include("auth_check.php");
include("admin/includes/db.php");
if (session_status() === PHP_SESSION_NONE) session_start();

// financeiro.php (para recalcular venda após mexer nos custos)
$financeiro_path = __DIR__ . "/includes/financeiro.php";
if (file_exists($financeiro_path)) include($financeiro_path);

$flash = null;

// CSRF (padrão consistente)
if (!isset($_SESSION["csrf_token"])) {
  $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function money($v){ return number_format((float)$v,2,',','.')." MT"; }

function col_exists(mysqli $con, string $table, string $col): bool {
  $table = mysqli_real_escape_string($con, $table);
  $col   = mysqli_real_escape_string($con, $col);
  $q = mysqli_query($con, "SHOW COLUMNS FROM `$table` LIKE '$col'");
  return $q && mysqli_num_rows($q) > 0;
}

// ✅ MODO: custos por venda (se vier venda_id)
$venda_id = (int)($_GET["venda_id"] ?? $_POST["venda_id"] ?? 0);
$modo_venda = $venda_id > 0;

// garante que existe custos.venda_id
$hasVendaId = col_exists($conexao, "custos", "venda_id");
if ($modo_venda && !$hasVendaId) {
  die("Falta a coluna custos.venda_id. Rode: ALTER TABLE custos ADD COLUMN venda_id INT NULL;");
}

// ---------- POST ----------
if ($_SERVER["REQUEST_METHOD"]==="POST") {
  $token = $_POST["token"] ?? "";
  if (!hash_equals($_SESSION["csrf_token"], $token)) {
    $flash=["type"=>"danger","msg"=>"Token inválido."];
  } else {

    $acao = $_POST["acao"] ?? "add";

    // ✅ ADD (geral ou por venda)
    if ($acao === "add") {
      if ($modo_venda) {
        $tipo = $_POST["tipo"] ?? "outros";
        $desc = trim($_POST["descricao"] ?? "");

        // aceita "15.000,00" e "15000"
        $val_raw = (string)($_POST["valor"] ?? "0");
        $val_raw = str_replace([" ", "."], "", $val_raw);
        $val_raw = str_replace(",", ".", $val_raw);
        $val = (float)$val_raw;

        $tipos_validos = ["anuncios","transporte","documentacao","outros"];
        if (!in_array($tipo, $tipos_validos, true)) $tipo = "outros";

        if ($val <= 0) {
          $flash=["type"=>"danger","msg"=>"Preencha valor (maior que 0)."];
        } else {
          $data = date("Y-m-d");

          $stmt=mysqli_prepare($conexao,"
            INSERT INTO custos (data, categoria, descricao, valor, venda_id)
            VALUES (?,?,?,?,?)
          ");
          mysqli_stmt_bind_param($stmt,"sssdi",$data,$tipo,$desc,$val,$venda_id);

          if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);

            // recalcula venda
            if (function_exists("recalcular_venda")) {
              $calc = recalcular_venda($conexao, $venda_id);
              if (!$calc["ok"]) $flash=["type"=>"warning","msg"=>"Custo adicionado, mas falhou recalcular: ".$calc["erro"]];
              else $flash=["type"=>"success","msg"=>"Custo adicionado e venda recalculada."];
            } else {
              $flash=["type"=>"success","msg"=>"Custo adicionado. (financeiro.php não encontrado para recalcular)"];
            }

          } else {
            $flash=["type"=>"danger","msg"=>"Erro: ".mysqli_error($conexao)];
            mysqli_stmt_close($stmt);
          }
        }

      } else {
        // modo geral (teu padrão)
        $data= $_POST["data"] ?? date("Y-m-d");
        $cat = trim($_POST["categoria"] ?? "");
        $desc= trim($_POST["descricao"] ?? "");
        $val = (float)($_POST["valor"] ?? 0);

        if ($cat==="" || $val<=0) $flash=["type"=>"danger","msg"=>"Preencha categoria e valor."];
        else{
          $stmt=mysqli_prepare($conexao,"INSERT INTO custos (data,categoria,descricao,valor) VALUES (?,?,?,?)");
          mysqli_stmt_bind_param($stmt,"sssd",$data,$cat,$desc,$val);
          if (mysqli_stmt_execute($stmt)) $flash=["type"=>"success","msg"=>"Custo registado."];
          else $flash=["type"=>"danger","msg"=>"Erro: ".mysqli_error($conexao)];
          mysqli_stmt_close($stmt);
        }
      }
    }

    // ✅ DEL (apagar custo só no modo venda)
    if ($acao === "del" && $modo_venda) {
      $custo_id = (int)($_POST["custo_id"] ?? 0);
      if ($custo_id <= 0) {
        $flash=["type"=>"danger","msg"=>"Custo inválido."];
      } else {
        // apaga só se for desta venda
        $stmt=mysqli_prepare($conexao,"DELETE FROM custos WHERE id=? AND venda_id=?");
        mysqli_stmt_bind_param($stmt,"ii",$custo_id,$venda_id);

        if (mysqli_stmt_execute($stmt)) {
          mysqli_stmt_close($stmt);

          if (function_exists("recalcular_venda")) {
            $calc = recalcular_venda($conexao, $venda_id);
            if (!$calc["ok"]) $flash=["type"=>"warning","msg"=>"Custo removido, mas falhou recalcular: ".$calc["erro"]];
            else $flash=["type"=>"success","msg"=>"Custo removido e venda recalculada."];
          } else {
            $flash=["type"=>"success","msg"=>"Custo removido. (financeiro.php não encontrado para recalcular)"];
          }

        } else {
          $flash=["type"=>"danger","msg"=>"Erro: ".mysqli_error($conexao)];
          mysqli_stmt_close($stmt);
        }
      }
    }
  }
}

// ---------- DADOS PARA TELA ----------
$inicioMes=date("Y-m-01"); $fimMes=date("Y-m-t");

$venda = null;
$lista = [];
$totalMes = 0.0;
$totalVenda = 0.0;

if ($modo_venda) {
  // Resumo da venda (se estas colunas existirem)
  $selectExtras = "";
  foreach (["valor_venda","valor_proprietario","total_custos","lucro","comissao_vendedor","comissao_rg","comissao_parceiro","precisa_aprovacao"] as $c) {
    if (col_exists($conexao,"vendas",$c)) $selectExtras .= ", $c";
  }

  $stmtV = mysqli_prepare($conexao, "
    SELECT id, marca, modelo, ano, status $selectExtras
    FROM vendas
    WHERE id = ?
    LIMIT 1
  ");
  mysqli_stmt_bind_param($stmtV, "i", $venda_id);
  mysqli_stmt_execute($stmtV);
  $resV = mysqli_stmt_get_result($stmtV);
  $venda = mysqli_fetch_assoc($resV);
  mysqli_stmt_close($stmtV);
  if (!$venda) die("Venda não encontrada.");

  // total custos desta venda
  $stmt=mysqli_prepare($conexao,"SELECT COALESCE(SUM(valor),0) AS total FROM custos WHERE venda_id=?");
  mysqli_stmt_bind_param($stmt,"i",$venda_id);
  mysqli_stmt_execute($stmt);
  $res=mysqli_stmt_get_result($stmt);
  $totalVenda=(float)(mysqli_fetch_assoc($res)["total"] ?? 0);
  mysqli_stmt_close($stmt);

  // lista custos desta venda
  $stmtL = mysqli_prepare($conexao, "
    SELECT id, data, categoria, descricao, valor
    FROM custos
    WHERE venda_id = ?
    ORDER BY id DESC
    LIMIT 50
  ");
  mysqli_stmt_bind_param($stmtL,"i",$venda_id);
  mysqli_stmt_execute($stmtL);
  $resL = mysqli_stmt_get_result($stmtL);
  while($r=mysqli_fetch_assoc($resL)) $lista[]=$r;
  mysqli_stmt_close($stmtL);

} else {
  // total mês (custos gerais)
  $stmt=mysqli_prepare($conexao,"SELECT COALESCE(SUM(valor),0) AS total FROM custos WHERE data BETWEEN ? AND ?");
  mysqli_stmt_bind_param($stmt,"ss",$inicioMes,$fimMes);
  mysqli_stmt_execute($stmt);
  $res=mysqli_stmt_get_result($stmt);
  $totalMes=(float)(mysqli_fetch_assoc($res)["total"] ?? 0);
  mysqli_stmt_close($stmt);

  $resL=mysqli_query($conexao,"SELECT id,data,categoria,descricao,valor,venda_id FROM custos ORDER BY id DESC LIMIT 20");
  if($resL) while($r=mysqli_fetch_assoc($resL)) $lista[]=$r;
}
?>
<!doctype html><html lang="pt"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin | Custos</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>body{background:#f6f7fb}.card{border:0;border-radius:16px}.table-wrap{border-radius:16px;overflow:hidden}</style>
</head><body>
<div class="container py-4">

  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h3 class="mb-0">Custos <?= $modo_venda ? ("— Venda #".(int)$venda_id) : "" ?></h3>
      <small class="text-muted">
        <?php if($modo_venda): ?>
          Total de custos desta venda: <?= money($totalVenda); ?>
        <?php else: ?>
          Total do mês: <?= money($totalMes); ?>
        <?php endif; ?>
      </small>
    </div>

    <div class="d-flex gap-2">
      <a class="btn btn-outline-dark" href="dashboard.php">Dashboard</a>
      <a class="btn btn-outline-dark" href="vendas.php">Vendas</a>
      <?php if($modo_venda): ?>
        <a class="btn btn-outline-primary" href="editar_venda.php?id=<?= (int)$venda_id ?>">Editar venda</a>
        <a class="btn btn-outline-secondary" href="venda_detalhe.php?id=<?= (int)$venda_id ?>">Voltar à venda</a>
      <?php endif; ?>
    </div>
  </div>

  <?php if($flash): ?>
    <div class="alert alert-<?php echo h($flash["type"]); ?> alert-dismissible fade show" role="alert">
      <?php echo h($flash["msg"]); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <?php if($modo_venda && $venda): ?>
    <div class="card shadow-sm mb-3"><div class="card-body">
      <div class="row g-3">
        <div class="col-md-4"><div class="text-muted">Carro</div><div class="fw-bold"><?= h($venda["marca"])." ".h($venda["modelo"])." ".h($venda["ano"]??"") ?></div></div>
        <div class="col-md-2"><div class="text-muted">Status</div><div class="fw-bold"><?= h($venda["status"]) ?></div></div>
        <?php if(isset($venda["valor_venda"])): ?>
          <div class="col-md-3"><div class="text-muted">Valor venda</div><div class="fw-bold"><?= money($venda["valor_venda"]) ?></div></div>
        <?php endif; ?>
        <?php if(isset($venda["lucro"])): ?>
          <div class="col-md-3"><div class="text-muted">Lucro</div><div class="fw-bold"><?= money($venda["lucro"]) ?></div></div>
        <?php endif; ?>
        <?php if(isset($venda["comissao_parceiro"])): ?>
          <div class="col-md-3"><div class="text-muted">Parceiro (10%)</div><div class="fw-bold"><?= money($venda["comissao_parceiro"]) ?></div></div>
        <?php endif; ?>
        <?php if(isset($venda["comissao_vendedor"])): ?>
          <div class="col-md-3"><div class="text-muted">Vendedor (15%)</div><div class="fw-bold"><?= money($venda["comissao_vendedor"]) ?></div></div>
        <?php endif; ?>
        <?php if(isset($venda["comissao_rg"])): ?>
          <div class="col-md-3"><div class="text-muted">RG (75%)</div><div class="fw-bold"><?= money($venda["comissao_rg"]) ?></div></div>
        <?php endif; ?>
        <?php if(isset($venda["precisa_aprovacao"])): ?>
          <div class="col-md-3"><div class="text-muted">Precisa aprovação?</div><div class="fw-bold"><?= ((int)$venda["precisa_aprovacao"]===1) ? "SIM" : "NÃO" ?></div></div>
        <?php endif; ?>
      </div>
    </div></div>
  <?php endif; ?>

  <div class="card shadow-sm mb-3"><div class="card-body">
    <form method="POST" class="row g-2 align-items-end">
      <input type="hidden" name="token" value="<?php echo h($_SESSION["csrf_token"]); ?>">
      <?php if($modo_venda): ?>
        <input type="hidden" name="venda_id" value="<?= (int)$venda_id ?>">
        <input type="hidden" name="acao" value="add">

        <div class="col-md-3">
          <label class="form-label">Tipo</label>
          <select class="form-select" name="tipo" required>
            <option value="anuncios">Anúncios</option>
            <option value="transporte">Transporte</option>
            <option value="documentacao">Documentação</option>
            <option value="outros" selected>Outros</option>
          </select>
        </div>

        <div class="col-md-7">
          <label class="form-label">Descrição</label>
          <input class="form-control" name="descricao" placeholder="Ex: Facebook Ads / táxi / inspeção">
        </div>

        <div class="col-md-2">
          <label class="form-label">Valor (MT)</label>
          <input class="form-control" name="valor" required placeholder="Ex: 15000 ou 15.000,00">
        </div>

        <div class="col-12 d-grid mt-2">
          <button class="btn btn-success">Adicionar custo e recalcular</button>
        </div>

      <?php else: ?>
        <div class="col-md-2">
          <label class="form-label">Data</label>
          <input class="form-control" type="date" name="data" value="<?php echo date("Y-m-d"); ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label">Categoria</label>
          <input class="form-control" name="categoria" placeholder="Ex: Marketing / Combustível" required>
        </div>
        <div class="col-md-5">
          <label class="form-label">Descrição</label>
          <input class="form-control" name="descricao" placeholder="Ex: Impulso IG / Gasolina">
        </div>
        <div class="col-md-2">
          <label class="form-label">Valor (MT)</label>
          <input class="form-control" type="number" step="0.01" name="valor" required>
        </div>
        <div class="col-12 d-grid mt-2">
          <button class="btn btn-success">Adicionar custo</button>
        </div>
      <?php endif; ?>
    </form>
  </div></div>

  <div class="table-wrap shadow-sm bg-white">
    <table class="table table-hover mb-0 align-middle">
      <thead class="table-light">
        <tr>
          <th>Data</th>
          <th><?= $modo_venda ? "Tipo" : "Categoria" ?></th>
          <th>Descrição</th>
          <?php if(!$modo_venda && $hasVendaId): ?><th>Venda</th><?php endif; ?>
          <th class="text-end">Valor</th>
          <?php if($modo_venda): ?><th class="text-end">Ações</th><?php endif; ?>
        </tr>
      </thead>
      <tbody>
      <?php if(!count($lista)): ?>
        <tr><td colspan="<?= $modo_venda ? 6 : (($hasVendaId)?5:4) ?>" class="text-center py-4 text-muted">Sem custos registados.</td></tr>
      <?php else: foreach($lista as $c): ?>
        <tr>
          <td><?php echo h($c["data"]); ?></td>
          <td><?php echo h($c["categoria"]); ?></td>
          <td class="text-muted"><?php echo h($c["descricao"] ?? "—"); ?></td>

          <?php if(!$modo_venda && $hasVendaId): ?>
            <td><?php echo !empty($c["venda_id"]) ? ("#".(int)$c["venda_id"]) : "—"; ?></td>
          <?php endif; ?>

          <td class="text-end"><?php echo money($c["valor"]); ?></td>

          <?php if($modo_venda): ?>
            <td class="text-end">
              <form method="POST" class="d-inline" onsubmit="return confirm('Remover este custo? Vai recalcular a venda.');">
                <input type="hidden" name="token" value="<?= h($_SESSION["csrf_token"]); ?>">
                <input type="hidden" name="venda_id" value="<?= (int)$venda_id ?>">
                <input type="hidden" name="acao" value="del">
                <input type="hidden" name="custo_id" value="<?= (int)$c["id"] ?>">
                <button class="btn btn-sm btn-outline-danger">Remover</button>
              </form>
            </td>
          <?php endif; ?>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
