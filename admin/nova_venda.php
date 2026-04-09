<?php
// admin/nova_venda.php (MODELO NOVO: LUCRO REAL)
// Desliga modelo antigo: NÃO grava valor_carro nem comissao (7%)
// A comissão é calculada em includes/financeiro.php via recalcular_venda()

include("../auth.php");
include("../conexao.php");
include("auth_check.php");
include("admin/includes/db.php");

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

if (!function_exists('col_exists')) {
  function col_exists(mysqli $con, string $table, string $col): bool {
    $table = mysqli_real_escape_string($con, $table);
    $col   = mysqli_real_escape_string($con, $col);
    $q = mysqli_query($con, "SHOW COLUMNS FROM `$table` LIKE '$col'");
    return $q && mysqli_num_rows($q) > 0;
  }
}


// inclui financeiro se existir
$financeiro_path = __DIR__ . "/includes/financeiro.php";
if (file_exists($financeiro_path)) {
  include($financeiro_path);
}

// --- Valida que o MODELO NOVO existe (desliga antigo) ---
$requiredCols = [
  "valor_venda",
  "valor_proprietario",
  "lucro_minimo",
  "status",
  "forma_pagamento",
  "data_venda"
];

foreach ($requiredCols as $c) {
  if (!col_exists($conexao, "vendas", $c)) {
    die("Modelo novo não está completo. Falta a coluna <b>".h($c)."</b> na tabela <b>vendas</b>.");
  }
}

// vendedor/captador existentes?
$hasVendId = col_exists($conexao, "vendas", "vendedor_id");
$hasCapId  = col_exists($conexao, "vendas", "captador_id");

// Flash msg
$flash = null;

// =========================
// Carregar clientes
// =========================
$clientes = [];
$resC = mysqli_query($conexao, "SELECT id, nome FROM clientes ORDER BY id DESC LIMIT 200");
if ($resC) while ($r = mysqli_fetch_assoc($resC)) $clientes[] = $r;

// =========================
// Carregar pessoas (vendedores/captadores)
// =========================
$pessoas = [];
$resP = mysqli_query($conexao, "SELECT id, nome FROM pessoas WHERE ativo = 1 ORDER BY nome ASC");
if ($resP) while ($r = mysqli_fetch_assoc($resP)) $pessoas[] = $r;

// =========================
// Cliente selecionado (GET)
// =========================
$clienteSelecionadoId = isset($_GET['cliente_id']) ? (int)$_GET['cliente_id'] : 0;
$cliente_pre = $clienteSelecionadoId;

// Buscar dados do cliente
$clienteDados = null;
if ($clienteSelecionadoId > 0) {
  $stmt = mysqli_prepare($conexao, "
    SELECT id, nome, telefone, email, marca, modelo, ano
    FROM clientes
    WHERE id = ?
  ");
  mysqli_stmt_bind_param($stmt, "i", $clienteSelecionadoId);
  mysqli_stmt_execute($stmt);
  $res = mysqli_stmt_get_result($stmt);
  $clienteDados = mysqli_fetch_assoc($res);
  mysqli_stmt_close($stmt);

  if (!$clienteDados) {
    $clienteSelecionadoId = 0;
    $cliente_pre = 0;
    $flash = ["type" => "danger", "msg" => "Cliente não encontrado."];
  }
}

// =========================
// SUBMIT (POST): criar venda
// =========================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $token = $_POST['token'] ?? '';
  if (!hash_equals($_SESSION['csrf_token'], $token)) {
    $flash = ["type" => "danger", "msg" => "Ação bloqueada (token inválido)."];
  } else {

    $cliente_id = (int)($_POST['cliente_id'] ?? 0);
    $marca  = trim($_POST['marca'] ?? '');
    $modelo = trim($_POST['modelo'] ?? '');
    $ano    = (int)($_POST['ano'] ?? 0);

    $valor_venda = (float)($_POST['valor_venda'] ?? 0);
    $valor_prop  = (float)($_POST['valor_proprietario'] ?? 0);

    $data_venda = trim($_POST['data_venda'] ?? date('Y-m-d'));
    $forma_pagamento = trim($_POST['forma_pagamento'] ?? '');
    $formas_ok = ['MPESA','E-MOLA','TRANSFERENCIA','CASH','OUTRO'];

    $vendedor_id = ($_POST['vendedor_id'] ?? '') === '' ? null : (int)$_POST['vendedor_id'];
    $captador_id = ($_POST['captador_id'] ?? '') === '' ? null : (int)$_POST['captador_id'];

    if (!in_array($forma_pagamento, $formas_ok, true)) {
      $flash = ["type" => "danger", "msg" => "Selecione uma forma de pagamento válida."];
    } elseif ($cliente_id <= 0) {
      $flash = ["type" => "danger", "msg" => "Selecione um cliente válido."];
    } elseif ($marca === '' || $modelo === '' || $ano <= 0) {
      $flash = ["type" => "danger", "msg" => "Preencha marca, modelo e ano."];
    } elseif ($valor_venda <= 0) {
      $flash = ["type" => "danger", "msg" => "Informe um valor de venda válido."];
    } elseif ($valor_prop < 0) {
      $flash = ["type" => "danger", "msg" => "Valor do proprietário não pode ser negativo."];
    } else {

      $status = 'PENDENTE';

      // lucro mínimo default (podes trocar para config depois)
      $lucro_minimo = 30000.00;

      // 1) INSERT só do MODELO NOVO (dinâmico)
      $cols  = ["cliente_id","marca","modelo","ano","status","forma_pagamento","data_venda","valor_venda","valor_proprietario","lucro_minimo"];
      $vals  = [$cliente_id,$marca,$modelo,$ano,$status,$forma_pagamento,$data_venda,$valor_venda,$valor_prop,$lucro_minimo];
      $types = "ississsddd";

      // vendedor/captador: só grava se foi escolhido (para evitar 0)
      if ($hasVendId && $vendedor_id !== null) {
        $cols[]  = "vendedor_id";
        $vals[]  = $vendedor_id;
        $types  .= "i";
      }
      if ($hasCapId && $captador_id !== null) {
        $cols[]  = "captador_id";
        $vals[]  = $captador_id;
        $types  .= "i";
      }

      $placeholders = implode(",", array_fill(0, count($cols), "?"));
      $sql = "INSERT INTO vendas (".implode(",", $cols).") VALUES ($placeholders)";
      $stmt = mysqli_prepare($conexao, $sql);

      if (!$stmt) {
        $flash = ["type"=>"danger","msg"=>"Erro ao preparar SQL: ".mysqli_error($conexao)];
      } else {

        mysqli_stmt_bind_param($stmt, $types, ...$vals);

        if (mysqli_stmt_execute($stmt)) {
          $novoId = (int)mysqli_insert_id($conexao);
          mysqli_stmt_close($stmt);

          // 2) Recalcular venda (custos/lucro/comissões)
          if (function_exists("recalcular_venda")) {
            $calc = recalcular_venda($conexao, $novoId);
            if (!$calc["ok"]) {
              // não bloqueia criação, mas avisa
              $flash = ["type"=>"warning","msg"=>"Venda criada, mas falhou recalcular: ".$calc["erro"]];
            }
          }

          // ✅ IMPORTANTE: NÃO marcar cliente como CONCLUIDO aqui.
          // O cliente deve virar CONCLUIDO apenas quando a venda for marcada como PAGO (no venda_detalhe.php).

          header("Location: venda_detalhe.php?id=".$novoId."&msg=criada");
          exit;

        } else {
          $flash = ["type" => "danger", "msg" => "Erro ao criar venda: " . mysqli_error($conexao)];
          mysqli_stmt_close($stmt);
        }
      }
    }
  }
}
?>
<!doctype html>
<html lang="pt">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin | Nova Venda - RG Auto Sales</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background:#f6f7fb; }
    .card { border:0; border-radius:16px; }
  </style>
</head>
<body>
<div class="container py-4">

  <div class="d-flex align-items-center justify-content-between mb-3">
    <div>
      <h3 class="mb-0">Nova Venda</h3>
      <small class="text-muted">
        Modelo novo: lucro real → vendedor (15%) / RG (restante) · lucro mínimo: 30.000 MT
      </small>
    </div>
    <div class="d-flex gap-2">
      <a class="btn btn-outline-dark" href="vendas.php">Vendas</a>
      <a class="btn btn-outline-dark" href="dashboard.php">Dashboard</a>
    </div>
  </div>

  <?php if ($flash): ?>
    <div class="alert alert-<?php echo h($flash['type']); ?> alert-dismissible fade show" role="alert">
      <?php echo h($flash['msg']); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <div class="card shadow-sm">
    <div class="card-body">

      <!-- Selecionar cliente (GET) -->
      <form class="row g-2 align-items-end mb-4" method="GET" action="nova_venda.php">
        <div class="col-md-8">
          <label class="form-label">Selecionar cliente</label>
          <select class="form-select" name="cliente_id" required>
            <option value="">-- Escolher --</option>
            <?php foreach ($clientes as $c): ?>
              <option value="<?php echo (int)$c['id']; ?>"
                <?php echo ($cliente_pre > 0 && (int)$c['id'] === $cliente_pre) ? 'selected' : ''; ?>>
                <?php echo h($c['nome']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4 d-grid">
          <button class="btn btn-dark" type="submit">Carregar dados</button>
        </div>
      </form>

      <!-- Form criar venda (POST) -->
      <form class="row g-3" method="POST" action="">
        <input type="hidden" name="token" value="<?php echo h($_SESSION['csrf_token']); ?>">
        <input type="hidden" name="cliente_id" value="<?php echo (int)$clienteSelecionadoId; ?>">

        <div class="col-12">
          <div class="p-3 bg-light rounded-3">
            <div class="fw-semibold">Cliente selecionado</div>
            <?php if ($clienteDados): ?>
              <div class="text-muted small">
                <?php echo h($clienteDados['nome']); ?> ·
                <?php echo h($clienteDados['telefone']); ?> ·
                <?php echo h($clienteDados['email']); ?>
              </div>
            <?php else: ?>
              <div class="text-muted small">Selecione um cliente acima para continuar.</div>
            <?php endif; ?>
          </div>
        </div>

        <div class="col-md-4">
          <label class="form-label">Marca</label>
          <input type="text" class="form-control" name="marca" required
                 value="<?php echo h($clienteDados['marca'] ?? ''); ?>"
                 <?php echo $clienteDados ? '' : 'disabled'; ?>>
        </div>

        <div class="col-md-4">
          <label class="form-label">Modelo</label>
          <input type="text" class="form-control" name="modelo" required
                 value="<?php echo h($clienteDados['modelo'] ?? ''); ?>"
                 <?php echo $clienteDados ? '' : 'disabled'; ?>>
        </div>

        <div class="col-md-4">
          <label class="form-label">Ano</label>
          <input type="number" class="form-control" name="ano" required
                 value="<?php echo h($clienteDados['ano'] ?? ''); ?>"
                 <?php echo $clienteDados ? '' : 'disabled'; ?>>
        </div>

        <div class="col-md-6">
          <label class="form-label">Valor de venda (MT)</label>
          <input type="number" step="0.01" class="form-control" name="valor_venda" required
                 placeholder="Ex: 700000"
                 <?php echo $clienteDados ? '' : 'disabled'; ?>>
          <div class="form-text">Quanto o cliente final pagou.</div>
        </div>

        <div class="col-md-6">
          <label class="form-label">Valor pago ao proprietário (MT)</label>
          <input type="number" step="0.01" class="form-control" name="valor_proprietario"
                 placeholder="Ex: 650000"
                 <?php echo $clienteDados ? '' : 'disabled'; ?>>
          <div class="form-text">Se ainda não pagaste, podes deixar 0 e ajustar depois.</div>
        </div>

        <div class="col-md-6">
          <label class="form-label">Data da venda</label>
          <input type="date" class="form-control" name="data_venda"
                 value="<?php echo h(date('Y-m-d')); ?>"
                 <?php echo $clienteDados ? '' : 'disabled'; ?>>
        </div>

        <div class="col-md-6">
          <label class="form-label">Forma de pagamento</label>
          <select class="form-select" name="forma_pagamento" required <?php echo $clienteDados ? '' : 'disabled'; ?>>
            <option value="">-- Selecionar --</option>
            <option value="MPESA">M-Pesa</option>
            <option value="E-MOLA">E-Mola</option>
            <option value="TRANSFERENCIA">Transferência</option>
            <option value="CASH">Cash</option>
            <option value="OUTRO">Outro</option>
          </select>
        </div>

        <div class="col-md-6">
          <label class="form-label">Vendedor (opcional)</label>
          <select class="form-select" name="vendedor_id" <?php echo $clienteDados ? '' : 'disabled'; ?>>
            <option value="">-- Nenhum --</option>
            <?php foreach ($pessoas as $p): ?>
              <option value="<?php echo (int)$p['id']; ?>"><?php echo h($p['nome']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-6">
          <label class="form-label">Captador (opcional)</label>
          <select class="form-select" name="captador_id" <?php echo $clienteDados ? '' : 'disabled'; ?>>
            <option value="">-- Nenhum --</option>
            <?php foreach ($pessoas as $p): ?>
              <option value="<?php echo (int)$p['id']; ?>"><?php echo h($p['nome']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-12 d-grid">
          <button class="btn btn-success btn-lg" type="submit" <?php echo $clienteDados ? '' : 'disabled'; ?>>
            Criar Venda
          </button>
        </div>

      </form>

    </div>
  </div>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
