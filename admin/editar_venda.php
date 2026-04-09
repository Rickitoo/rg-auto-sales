<?php
include("../auth.php");
include("../conexao.php");
include("auth_check.php");
include("admin/includes/db.php");

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function money($v){ return number_format((float)$v, 2, ',', '.')." MT"; }

function col_exists(mysqli $con, string $table, string $col): bool {
  $table = mysqli_real_escape_string($con, $table);
  $col   = mysqli_real_escape_string($con, $col);
  $q = mysqli_query($con, "SHOW COLUMNS FROM `$table` LIKE '$col'");
  return $q && mysqli_num_rows($q) > 0;
}

// inclui financeiro se existir
$financeiro_path = __DIR__ . "/includes/financeiro.php";
if (file_exists($financeiro_path)) {
  include($financeiro_path);
}

// ID
$id = (int)($_GET["id"] ?? $_POST["id"] ?? 0);
if ($id <= 0) die("ID inválido.");

// Detecta colunas
$hasValorVenda  = col_exists($conexao, "vendas", "valor_venda");
$hasValorProp   = col_exists($conexao, "vendas", "valor_proprietario");
$hasTCustos     = col_exists($conexao, "vendas", "total_custos");
$hasLucro       = col_exists($conexao, "vendas", "lucro");
$hasLucroMin    = col_exists($conexao, "vendas", "lucro_minimo");
$hasApv         = col_exists($conexao, "vendas", "precisa_aprovacao");
$hasPercVend    = col_exists($conexao, "vendas", "perc_vendedor");
$hasPercRG      = col_exists($conexao, "vendas", "perc_rg");

$hasValorCarro  = col_exists($conexao, "vendas", "valor_carro");
$hasComissaoOld = col_exists($conexao, "vendas", "comissao");

$temVendedor    = col_exists($conexao, "vendas", "vendedor_id");
$temCaptador    = col_exists($conexao, "vendas", "captador_id");

$flash = null;

// Carregar pessoas (vendedor/captador)
$pessoas = [];
$resP = mysqli_query($conexao, "SELECT id, nome FROM pessoas WHERE ativo = 1 ORDER BY nome ASC");
if ($resP) while($r=mysqli_fetch_assoc($resP)) $pessoas[] = $r;

// POST: salvar
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $token = $_POST["token"] ?? "";
  if (!hash_equals($_SESSION["csrf_token"], $token)) {
    $flash = ["type"=>"danger","msg"=>"Ação bloqueada (token inválido)."];
  } else {

    $marca  = trim($_POST["marca"] ?? "");
    $modelo = trim($_POST["modelo"] ?? "");
    $ano    = (int)($_POST["ano"] ?? 0);

    $data_venda = trim($_POST["data_venda"] ?? "");
    if ($data_venda === "") $data_venda = date("Y-m-d");

    $forma_pagamento = trim($_POST["forma_pagamento"] ?? "");
    $formas_ok = ['MPESA','E-MOLA','TRANSFERENCIA','CASH','OUTRO'];
    if (!in_array($forma_pagamento, $formas_ok, true)) {
      $flash = ["type"=>"danger","msg"=>"Forma de pagamento inválida."];
    }

    // valores (modelo novo)
    $valor_venda = (float)($_POST["valor_venda"] ?? 0);
    $valor_prop  = (float)($_POST["valor_proprietario"] ?? 0);

    // compat antigo
    $valor_carro = (float)($_POST["valor_carro"] ?? 0);

    // percentagens opcionais (se existir no BD)
    $perc_vendedor = (float)($_POST["perc_vendedor"] ?? 20);
    $perc_rg       = (float)($_POST["perc_rg"] ?? 80);
    $lucro_minimo  = (float)($_POST["lucro_minimo"] ?? 30000);

    $vendedor_id = null;
    $captador_id = null;
    if ($temVendedor) $vendedor_id = (($_POST["vendedor_id"] ?? "") === "") ? null : (int)$_POST["vendedor_id"];
    if ($temCaptador) $captador_id = (($_POST["captador_id"] ?? "") === "") ? null : (int)$_POST["captador_id"];

    // validações mínimas
    if (!$flash) {
      if ($marca === "" || $modelo === "" || $ano <= 0) {
        $flash = ["type"=>"danger","msg"=>"Preencha marca, modelo e ano."];
      } else {
        // Se modelo novo existir, valor_venda é obrigatório
        if ($hasValorVenda) {
          if ($valor_venda <= 0) $flash = ["type"=>"danger","msg"=>"Informe um valor de venda válido."];
          if ($valor_prop < 0)  $flash = ["type"=>"danger","msg"=>"Valor do proprietário não pode ser negativo."];
        } else {
          // banco antigo
          if ($valor_carro <= 0) $flash = ["type"=>"danger","msg"=>"Informe um valor do carro válido."];
        }
      }
    }

    if (!$flash) {
      // monta UPDATE só com colunas existentes (sem quebrar)
      $sets = [];
      $vals = [];
      $types = "";

      $sets[] = "marca=?";  $vals[] = $marca;  $types .= "s";
      $sets[] = "modelo=?"; $vals[] = $modelo; $types .= "s";
      $sets[] = "ano=?";    $vals[] = $ano;    $types .= "i";

      $sets[] = "data_venda=?";       $vals[] = $data_venda;       $types .= "s";
      $sets[] = "forma_pagamento=?";  $vals[] = $forma_pagamento;  $types .= "s";

      if ($hasValorVenda) {
        $sets[] = "valor_venda=?";        $vals[] = $valor_venda; $types .= "d";
      }
      if ($hasValorProp) {
        $sets[] = "valor_proprietario=?"; $vals[] = $valor_prop;  $types .= "d";
      }

      if ($hasPercVend) {
        // normaliza (evita soma doida)
        if ($perc_vendedor < 0) $perc_vendedor = 0;
        if ($perc_vendedor > 100) $perc_vendedor = 100;
        $sets[] = "perc_vendedor=?"; $vals[] = $perc_vendedor; $types .= "d";
      }

      if ($hasPercRG) {
        if ($perc_rg < 0) $perc_rg = 0;
        if ($perc_rg > 100) $perc_rg = 100;
        $sets[] = "perc_rg=?"; $vals[] = $perc_rg; $types .= "d";
      }

      if ($hasLucroMin) {
        if ($lucro_minimo < 0) $lucro_minimo = 0;
        $sets[] = "lucro_minimo=?"; $vals[] = $lucro_minimo; $types .= "d";
      }

      // compat antigo: espelha valor_venda em valor_carro (se existir)
      if ($hasValorCarro) {
        $vc = $hasValorVenda ? $valor_venda : $valor_carro;
        $sets[] = "valor_carro=?"; $vals[] = $vc; $types .= "d";
      }

      if ($temVendedor) {
        $sets[] = "vendedor_id=?"; $vals[] = $vendedor_id; $types .= "i";
      }
      if ($temCaptador) {
        $sets[] = "captador_id=?"; $vals[] = $captador_id; $types .= "i";
      }

      // (não mexemos em status aqui; status é pelo detalhe/lista)

      $sql = "UPDATE vendas SET " . implode(", ", $sets) . " WHERE id=?";
      $vals[] = $id;
      $types .= "i";

      $stmt = mysqli_prepare($conexao, $sql);
      mysqli_stmt_bind_param($stmt, $types, ...$vals);

      if (mysqli_stmt_execute($stmt)) {

        // recalcula logo a seguir (modelo novo)
        if (function_exists("recalcular_venda")) {
          $calc = recalcular_venda($conexao, $id);
          if (!$calc["ok"]) {
            $flash = ["type"=>"warning","msg"=>"Venda salva, mas falhou recalcular: ".$calc["erro"]];
          } else {
            $flash = ["type"=>"success","msg"=>"Venda salva e recalculada."];
          }
        } else {
          $flash = ["type"=>"success","msg"=>"Venda salva."];
        }

      } else {
        $flash = ["type"=>"danger","msg"=>"Erro ao salvar: ".mysqli_error($conexao)];
      }
      mysqli_stmt_close($stmt);
    }
  }
}

// GET: carregar venda atual
$selectExtras = "";
if ($hasValorVenda) $selectExtras .= ", v.valor_venda";
if ($hasValorProp)  $selectExtras .= ", v.valor_proprietario";
if ($hasTCustos)    $selectExtras .= ", v.total_custos";
if ($hasLucro)      $selectExtras .= ", v.lucro";
if ($hasLucroMin)   $selectExtras .= ", v.lucro_minimo";
if ($hasPercVend)   $selectExtras .= ", v.perc_vendedor";
if ($hasPercRG)     $selectExtras .= ", v.perc_rg";
if ($hasApv)        $selectExtras .= ", v.precisa_aprovacao";
if ($temVendedor)   $selectExtras .= ", v.vendedor_id";
if ($temCaptador)   $selectExtras .= ", v.captador_id";

$stmt = mysqli_prepare($conexao, "
  SELECT v.id, v.marca, v.modelo, v.ano, v.status, v.data_venda, v.forma_pagamento,
         v.valor_carro, v.comissao
         $selectExtras,
         c.nome AS cliente_nome, c.telefone AS cliente_telefone, c.email AS cliente_email
  FROM vendas v
  INNER JOIN clientes c ON c.id = v.cliente_id
  WHERE v.id=?
  LIMIT 1
");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$v = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$v) die("Venda não encontrada.");

// avisos (se já existir lucro)
$warningLucro = null;
if ($hasLucro) {
  $lucro = (float)($v["lucro"] ?? 0);
  if ($lucro < 0) $warningLucro = "⚠️ Lucro negativo: verifica valor do proprietário e custos.";
  elseif ($hasLucroMin && $lucro < (float)($v["lucro_minimo"] ?? 0)) $warningLucro = "⚠️ Lucro abaixo do mínimo definido.";
}
?>
<!doctype html>
<html lang="pt">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin | Editar Venda - RG Auto Sales</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body{background:#f6f7fb;}
    .card{border:0;border-radius:16px;}
  </style>
</head>
<body>
<div class="container py-4">

  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h3 class="mb-0">Editar Venda #<?php echo (int)$v["id"]; ?></h3>
      <small class="text-muted">
        Cliente: <?php echo h($v["cliente_nome"]); ?> · <?php echo h($v["cliente_telefone"]); ?>
      </small>
    </div>
    <div class="d-flex gap-2">
      <a class="btn btn-outline-dark" href="venda_detalhe.php?id=<?php echo (int)$v["id"]; ?>">Voltar ao detalhe</a>
      <a class="btn btn-outline-secondary" href="custos.php?venda_id=<?php echo (int)$v["id"]; ?>">Custos</a>
      <a class="btn btn-outline-primary" href="editar_venda.php?id=<?php echo (int)$venda['id']; ?>">
        Editar
      </a>

    </div>
  </div>

  <?php if ($flash): ?>
    <div class="alert alert-<?php echo h($flash["type"]); ?> alert-dismissible fade show" role="alert">
      <?php echo h($flash["msg"]); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <?php if ($warningLucro): ?>
    <div class="alert alert-warning"><?php echo h($warningLucro); ?></div>
  <?php endif; ?>

  <div class="card shadow-sm">
    <div class="card-body">
      <form method="POST" class="row g-3">
        <input type="hidden" name="token" value="<?php echo h($_SESSION["csrf_token"]); ?>">
        <input type="hidden" name="id" value="<?php echo (int)$v["id"]; ?>">

        <div class="col-md-4">
          <label class="form-label">Marca</label>
          <input class="form-control" name="marca" required value="<?php echo h($v["marca"]); ?>">
        </div>

        <div class="col-md-4">
          <label class="form-label">Modelo</label>
          <input class="form-control" name="modelo" required value="<?php echo h($v["modelo"]); ?>">
        </div>

        <div class="col-md-4">
          <label class="form-label">Ano</label>
          <input class="form-control" type="number" name="ano" required value="<?php echo h($v["ano"]); ?>">
        </div>

        <div class="col-md-6">
          <label class="form-label">Data da venda</label>
          <input class="form-control" type="date" name="data_venda" value="<?php echo h($v["data_venda"]); ?>">
        </div>

        <div class="col-md-6">
          <label class="form-label">Forma de pagamento</label>
          <select class="form-select" name="forma_pagamento" required>
            <?php
              $fp = $v["forma_pagamento"] ?? "";
              $ops = ["MPESA"=>"M-Pesa","E-MOLA"=>"E-Mola","TRANSFERENCIA"=>"Transferência","CASH"=>"Cash","OUTRO"=>"Outro"];
              echo '<option value="">-- Selecionar --</option>';
              foreach($ops as $k=>$label){
                $sel = ($fp===$k) ? "selected" : "";
                echo '<option value="'.h($k).'" '.$sel.'>'.h($label).'</option>';
              }
            ?>
          </select>
        </div>

        <?php if ($hasValorVenda): ?>
          <div class="col-md-6">
            <label class="form-label">Valor de venda (MT)</label>
            <input class="form-control" type="number" step="0.01" name="valor_venda"
                   value="<?php echo h($v["valor_venda"] ?? 0); ?>" required>
          </div>
        <?php else: ?>
          <div class="col-md-6">
            <label class="form-label">Valor do carro (MT) (modelo antigo)</label>
            <input class="form-control" type="number" step="0.01" name="valor_carro"
                   value="<?php echo h($v["valor_carro"] ?? 0); ?>" required>
          </div>
        <?php endif; ?>

        <?php if ($hasValorProp): ?>
          <div class="col-md-6">
            <label class="form-label">Valor pago ao proprietário (MT)</label>
            <input class="form-control" type="number" step="0.01" name="valor_proprietario"
                   value="<?php echo h($v["valor_proprietario"] ?? 0); ?>">
          </div>
        <?php endif; ?>

        <?php if ($hasPercVend || $hasPercRG || $hasLucroMin): ?>
          <div class="col-12"><hr></div>
          <div class="col-12"><div class="fw-semibold">Regras de comissão (opcional)</div></div>

          <?php if ($hasPercVend): ?>
            <div class="col-md-4">
              <label class="form-label">% Vendedor</label>
              <input class="form-control" type="number" step="0.01" name="perc_vendedor"
                     value="<?php echo h($v["perc_vendedor"] ?? 20); ?>">
            </div>
          <?php endif; ?>

          <?php if ($hasPercRG): ?>
            <div class="col-md-4">
              <label class="form-label">% RG</label>
              <input class="form-control" type="number" step="0.01" name="perc_rg"
                     value="<?php echo h($v["perc_rg"] ?? 80); ?>">
            </div>
          <?php endif; ?>

          <?php if ($hasLucroMin): ?>
            <div class="col-md-4">
              <label class="form-label">Lucro mínimo (MT)</label>
              <input class="form-control" type="number" step="0.01" name="lucro_minimo"
                     value="<?php echo h($v["lucro_minimo"] ?? 30000); ?>">
            </div>
          <?php endif; ?>
        <?php endif; ?>

        <?php if ($temVendedor || $temCaptador): ?>
          <div class="col-12"><hr></div>
          <div class="col-12"><div class="fw-semibold">Equipa (opcional)</div></div>

          <?php if ($temVendedor): ?>
            <div class="col-md-6">
              <label class="form-label">Vendedor</label>
              <select class="form-select" name="vendedor_id">
                <option value="">-- Nenhum --</option>
                <?php foreach($pessoas as $p):
                  $sel = ((int)($v["vendedor_id"] ?? 0) === (int)$p["id"]) ? "selected" : "";
                ?>
                  <option value="<?php echo (int)$p["id"]; ?>" <?php echo $sel; ?>>
                    <?php echo h($p["nome"]); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          <?php endif; ?>

          <?php if ($temCaptador): ?>
            <div class="col-md-6">
              <label class="form-label">Captador</label>
              <select class="form-select" name="captador_id">
                <option value="">-- Nenhum --</option>
                <?php foreach($pessoas as $p):
                  $sel = ((int)($v["captador_id"] ?? 0) === (int)$p["id"]) ? "selected" : "";
                ?>
                  <option value="<?php echo (int)$p["id"]; ?>" <?php echo $sel; ?>>
                    <?php echo h($p["nome"]); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          <?php endif; ?>
        <?php endif; ?>

        <div class="col-12"><hr></div>

        <?php if ($hasLucro): ?>
          <div class="col-md-4">
            <div class="text-muted small">Total custos</div>
            <div class="fw-semibold"><?php echo $hasTCustos ? money($v["total_custos"] ?? 0) : "—"; ?></div>
          </div>
          <div class="col-md-4">
            <div class="text-muted small">Lucro</div>
            <div class="fw-semibold"><?php echo money($v["lucro"] ?? 0); ?></div>
          </div>
          <div class="col-md-4">
            <div class="text-muted small">Status</div>
            <div class="fw-semibold">
              <?php echo h($v["status"]); ?>
              <?php if ($hasApv && (int)($v["precisa_aprovacao"] ?? 0) === 1): ?>
                <span class="badge text-bg-dark ms-1">Precisa aprovação</span>
              <?php endif; ?>
            </div>
          </div>
        <?php endif; ?>

        <div class="col-12 d-grid">
          <button class="btn btn-success btn-lg" type="submit">Salvar alterações</button>
        </div>

      </form>
    </div>
  </div>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
