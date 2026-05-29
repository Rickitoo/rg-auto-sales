<?php
require_once __DIR__ . '/../../app/core/bootstrap.php';
require_admin();

if ($_SESSION['user']['role'] !== 'admin') {
    redirect_to('auth/login.php');
    exit();
}


if (!isset($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!function_exists('h')) { function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); } }
function money($v){ return number_format((float)$v, 2, ',', '.')." MT"; }

function col_exists(mysqli $con, string $table, string $col): bool {
  $table = mysqli_real_escape_string($con, $table);
  $col   = mysqli_real_escape_string($con, $col);
  $q = mysqli_query($con, "SHOW COLUMNS FROM `$table` LIKE '$col'");
  return $q && mysqli_num_rows($q) > 0;
}

// inclui financeiro se existir

// ID
$id = (int)($_GET["id"] ?? $_POST["id"] ?? 0);
if ($id <= 0) die("ID invÃ¡lido.");

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
    $flash = ["type"=>"danger","msg"=>"AÃ§Ã£o bloqueada (token invÃ¡lido)."];
  } else {

    $marca  = trim($_POST["marca"] ?? "");
    $modelo = trim($_POST["modelo"] ?? "");
    $ano    = (int)($_POST["ano"] ?? 0);

    $data_venda = trim($_POST["data_venda"] ?? "");
    if ($data_venda === "") $data_venda = date("Y-m-d");

    $forma_pagamento = trim($_POST["forma_pagamento"] ?? "");
    $formas_ok = ['MPESA','E-MOLA','TRANSFERENCIA','CASH','OUTRO'];
    if (!in_array($forma_pagamento, $formas_ok, true)) {
      $flash = ["type"=>"danger","msg"=>"Forma de pagamento invÃ¡lida."];
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

    // validaÃ§Ãµes mÃ­nimas
    if (!$flash) {
      if ($marca === "" || $modelo === "" || $ano <= 0) {
        $flash = ["type"=>"danger","msg"=>"Preencha marca, modelo e ano."];
      } else {
        // Se modelo novo existir, valor_venda Ã© obrigatÃ³rio
        if ($hasValorVenda) {
          if ($valor_venda <= 0) $flash = ["type"=>"danger","msg"=>"Informe um valor de venda vÃ¡lido."];
          if ($valor_prop < 0)  $flash = ["type"=>"danger","msg"=>"Valor do proprietÃ¡rio nÃ£o pode ser negativo."];
        } else {
          // banco antigo
          if ($valor_carro <= 0) $flash = ["type"=>"danger","msg"=>"Informe um valor do carro vÃ¡lido."];
        }
      }
    }

    if (!$flash) {
      // monta UPDATE sÃ³ com colunas existentes (sem quebrar)
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

      // (nÃ£o mexemos em status aqui; status Ã© pelo detalhe/lista)

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

if (!$v) die("Venda nÃ£o encontrada.");

// avisos (se jÃ¡ existir lucro)
$warningLucro = null;
if ($hasLucro) {
  $lucro = (float)($v["lucro"] ?? 0);
  if ($lucro < 0) $warningLucro = "âš ï¸ Lucro negativo: verifica valor do proprietÃ¡rio e custos.";
  elseif ($hasLucroMin && $lucro < (float)($v["lucro_minimo"] ?? 0)) $warningLucro = "âš ï¸ Lucro abaixo do mÃ­nimo definido.";
}

$pageTitle = 'Editar Venda';
$pageSubtitle = 'Atualização comercial e financeira da venda';
$contentFile = BASE_PATH . '/app/views/admin/vendas/editar_venda_content.php';

require BASE_PATH . '/app/views/layouts/admin_layout.php';
