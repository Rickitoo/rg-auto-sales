<?php
require_once __DIR__ . '/../../app/core/bootstrap.php';
require_admin();

// admin/nova_venda.php (MODELO NOVO: LUCRO REAL)
// Desliga modelo antigo: NÃƒO grava valor_carro nem comissao (7%)
// A comissÃ£o Ã© calculada em app/modules/finance/helpers.php via recalcular_venda()

if ($_SESSION['user']['role'] !== 'admin') {
    redirect_to('auth/login.php');
    exit();
}



if (!isset($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!function_exists('h')) { function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); } }

if (!function_exists('col_exists')) {
  function col_exists(mysqli $con, string $table, string $col): bool {
    $table = mysqli_real_escape_string($con, $table);
    $col   = mysqli_real_escape_string($con, $col);
    $q = mysqli_query($con, "SHOW COLUMNS FROM `$table` LIKE '$col'");
    return $q && mysqli_num_rows($q) > 0;
  }
}


// inclui financeiro se existir

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
    die("Modelo novo nÃ£o estÃ¡ completo. Falta a coluna <b>".h($c)."</b> na tabela <b>vendas</b>.");
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
    $flash = ["type" => "danger", "msg" => "Cliente nÃ£o encontrado."];
  }
}

// =========================
// SUBMIT (POST): criar venda
// =========================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $token = $_POST['token'] ?? '';
  if (!hash_equals($_SESSION['csrf_token'], $token)) {
    $flash = ["type" => "danger", "msg" => "AÃ§Ã£o bloqueada (token invÃ¡lido)."];
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
      $flash = ["type" => "danger", "msg" => "Selecione uma forma de pagamento vÃ¡lida."];
    } elseif ($cliente_id <= 0) {
      $flash = ["type" => "danger", "msg" => "Selecione um cliente vÃ¡lido."];
    } elseif ($marca === '' || $modelo === '' || $ano <= 0) {
      $flash = ["type" => "danger", "msg" => "Preencha marca, modelo e ano."];
    } elseif ($valor_venda <= 0) {
      $flash = ["type" => "danger", "msg" => "Informe um valor de venda vÃ¡lido."];
    } elseif ($valor_prop < 0) {
      $flash = ["type" => "danger", "msg" => "Valor do proprietÃ¡rio nÃ£o pode ser negativo."];
    } else {

      $status = 'PENDENTE';

      // lucro mÃ­nimo default (podes trocar para config depois)
      $lucro_minimo = 30000.00;

      // 1) INSERT sÃ³ do MODELO NOVO (dinÃ¢mico)
      $cols  = ["cliente_id","marca","modelo","ano","status","forma_pagamento","data_venda","valor_venda","valor_proprietario","lucro_minimo"];
      $vals  = [$cliente_id,$marca,$modelo,$ano,$status,$forma_pagamento,$data_venda,$valor_venda,$valor_prop,$lucro_minimo];
      $types = "ississsddd";

      // vendedor/captador: sÃ³ grava se foi escolhido (para evitar 0)
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

          // 2) Recalcular venda (custos/lucro/comissÃµes)
          if (function_exists("recalcular_venda")) {
            $calc = recalcular_venda($conexao, $novoId);
            if (!$calc["ok"]) {
              // nÃ£o bloqueia criaÃ§Ã£o, mas avisa
              $flash = ["type"=>"warning","msg"=>"Venda criada, mas falhou recalcular: ".$calc["erro"]];
            }
          }

          // âœ… IMPORTANTE: NÃƒO marcar cliente como CONCLUIDO aqui.
          // O cliente deve virar CONCLUIDO apenas quando a venda for marcada como PAGO (no venda_detalhe.php).

          redirect_to('admin/vendas/venda_detalhe.php?id=' . $novoId."&msg=criada");
          exit;

        } else {
          $flash = ["type" => "danger", "msg" => "Erro ao criar venda: " . mysqli_error($conexao)];
          mysqli_stmt_close($stmt);
        }
      }
    }
  }
}

$pageTitle = 'Nova Venda';
$pageSubtitle = 'Criação de venda com lucro real e comissões';
$contentFile = BASE_PATH . '/app/views/admin/vendas/nova_venda_content.php';

require BASE_PATH . '/app/views/layouts/admin_layout.php';
