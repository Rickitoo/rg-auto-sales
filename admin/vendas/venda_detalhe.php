<?php
require_once __DIR__ . '/../../app/core/bootstrap.php';
require_admin();

// admin/venda_detalhe.php  (MODELO NOVO: LUCRO REAL)
// - Desliga totalmente o modelo antigo (valor_carro / comissao 7%)
// - Mostra apenas valor_venda, valor_proprietario, custos, lucro e comissÃµes por lucro real
// - ComissÃµes devem ser calculadas em app/modules/finance/helpers.php (recalcular_venda)

if ($_SESSION['user']['role'] !== 'admin') {
    redirect_to('auth/login.php');
    exit();
}




if (!isset($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!function_exists('h')) { function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); } }
function money($v){ return number_format((float)$v, 2, ',', '.')." MT"; }

// evita redeclare se financeiro.php tambÃ©m tiver
if (!function_exists('col_exists')) {
  function col_exists(mysqli $con, string $table, string $col): bool {
    $table = mysqli_real_escape_string($con, $table);
    $col   = mysqli_real_escape_string($con, $col);
    $q = mysqli_query($con, "SHOW COLUMNS FROM `$table` LIKE '$col'");
    return $q && mysqli_num_rows($q) > 0;
  }
}

// tenta descobrir id do admin logado (ajusta se no teu auth.php usar outro nome)
function current_admin_id(): ?int {
  $keys = ['user_id','id','admin_id','usuario_id','utilizador_id'];
  foreach ($keys as $k) {
    if (isset($_SESSION[$k]) && is_numeric($_SESSION[$k])) return (int)$_SESSION[$k];
  }
  return null;
}

// inclui financeiro se existir

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) die("ID invÃ¡lido.");

$flash = null;

// =========================
// Garante colunas do MODELO NOVO
// =========================
$requiredCols = [
  "valor_venda",
  "valor_proprietario",
  "total_custos",
  "lucro",
  "comissao_vendedor",
  "comissao_rg",
  "perc_vendedor",
  "perc_rg",
  "lucro_minimo",
  "precisa_aprovacao",
  "status"
];

foreach ($requiredCols as $c) {
  if (!col_exists($conexao, "vendas", $c)) {
    die("Modelo novo nÃ£o estÃ¡ completo. Falta a coluna <b>".h($c)."</b> na tabela <b>vendas</b>.");
  }
}

$hasAtualizado  = col_exists($conexao, "vendas", "atualizado_em");

// parceiro/captador Ã© opcional
$hasCParc   = col_exists($conexao, "vendas", "comissao_parceiro");
$hasPP      = col_exists($conexao, "vendas", "perc_parceiro");
$hasCapId   = col_exists($conexao, "vendas", "captador_id");

// aprovaÃ§Ã£o (auditoria) Ã© opcional
$hasAprovPor = col_exists($conexao, "vendas", "aprovado_por");
$hasAprovEm  = col_exists($conexao, "vendas", "aprovado_em");

// =========================
// AÃ‡Ã•ES POST
// =========================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $acao  = $_POST['acao'] ?? '';
  $token = $_POST['token'] ?? '';

  if (!hash_equals($_SESSION['csrf_token'], $token)) {
    $flash = ["type" => "danger", "msg" => "AÃ§Ã£o bloqueada (token invÃ¡lido)."];
  } elseif (!in_array($acao, ['pagar', 'cancelar', 'recalcular', 'aprovar'], true)) {
    $flash = ["type" => "danger", "msg" => "AÃ§Ã£o invÃ¡lida."];
  } else {

    // âœ… Recalcular sem mudar status
    if ($acao === "recalcular") {
      if (function_exists("recalcular_venda")) {
        $calc = recalcular_venda($conexao, $id);
        $flash = $calc["ok"]
          ? ["type"=>"success","msg"=>"Venda recalculada com sucesso."]
          : ["type"=>"danger","msg"=>"Falhou recalcular: ".$calc["erro"]];
      } else {
        $flash = ["type"=>"warning","msg"=>"financeiro.php nÃ£o encontrado â€” nÃ£o consegui recalcular."];
      }
    }

    // âœ… Aprovar (tira o bloqueio)
    if ($acao === "aprovar") {
      // confere se realmente precisa de aprovaÃ§Ã£o e estÃ¡ pendente
      $chk = mysqli_prepare($conexao, "SELECT status, precisa_aprovacao FROM vendas WHERE id=? LIMIT 1");
      mysqli_stmt_bind_param($chk, "i", $id);
      mysqli_stmt_execute($chk);
      $r = mysqli_stmt_get_result($chk);
      $row = mysqli_fetch_assoc($r);
      mysqli_stmt_close($chk);

      if (!$row) {
        $flash = ["type"=>"danger","msg"=>"Venda nÃ£o encontrada."];
      } elseif ($row['status'] !== 'PENDENTE') {
        $flash = ["type"=>"warning","msg"=>"SÃ³ Ã© possÃ­vel aprovar quando a venda estÃ¡ PENDENTE."];
      } elseif ((int)$row['precisa_aprovacao'] !== 1) {
        $flash = ["type"=>"info","msg"=>"Esta venda nÃ£o precisa de aprovaÃ§Ã£o."];
      } else {
        $adminId = current_admin_id();
        if (($hasAprovPor || $hasAprovEm) && $adminId === null && $hasAprovPor) {
          $flash = ["type"=>"danger","msg"=>"NÃ£o consegui identificar o utilizador logado para registrar 'aprovado_por'. Ajusta o nome do id no auth.php (session)."];
        } else {
          // monta update dinÃ¢mico conforme colunas existentes
          $sql = "UPDATE vendas SET precisa_aprovacao=0";
          if ($hasAprovPor) $sql .= ", aprovado_por=?";
          if ($hasAprovEm)  $sql .= ", aprovado_em=NOW()";
          if ($hasAtualizado) $sql .= ", atualizado_em=NOW()";
          $sql .= " WHERE id=? LIMIT 1";

          $st = mysqli_prepare($conexao, $sql);
          if (!$st) {
            $flash = ["type"=>"danger","msg"=>"Erro ao preparar aprovaÃ§Ã£o: ".mysqli_error($conexao)];
          } else {
            if ($hasAprovPor) {
              mysqli_stmt_bind_param($st, "ii", $adminId, $id);
            } else {
              mysqli_stmt_bind_param($st, "i", $id);
            }

            if (mysqli_stmt_execute($st)) {
              $flash = ["type"=>"success","msg"=>"Venda aprovada. Agora jÃ¡ pode ser marcada como PAGA."];
              // recalcula para atualizar percentuais/comissÃµes (ainda ficam 0 atÃ© PAGO)
              if (function_exists("recalcular_venda")) {
                recalcular_venda($conexao, $id);
              }
            } else {
              $flash = ["type"=>"danger","msg"=>"Erro ao aprovar: ".mysqli_error($conexao)];
            }
            mysqli_stmt_close($st);
          }
        }
      }
    }

    // âœ… Pagar/Cancelar
    if ($acao === "pagar" || $acao === "cancelar") {
      $novoStatus = ($acao === 'pagar') ? 'PAGO' : 'CANCELADO';

      // trava por aprovaÃ§Ã£o (modelo novo)
      if ($acao === "pagar") {
        $chk = mysqli_prepare($conexao, "SELECT precisa_aprovacao FROM vendas WHERE id=? LIMIT 1");
        mysqli_stmt_bind_param($chk, "i", $id);
        mysqli_stmt_execute($chk);
        $r = mysqli_stmt_get_result($chk);
        $row = mysqli_fetch_assoc($r);
        mysqli_stmt_close($chk);

        if ($row && (int)$row["precisa_aprovacao"] === 1) {
          $flash = ["type"=>"warning","msg"=>"Esta venda precisa de aprovaÃ§Ã£o (lucro abaixo do mÃ­nimo / lucro <= 0). NÃ£o foi marcada como PAGA."];
          $novoStatus = null;
        }
      }

      if ($novoStatus) {
        if ($hasAtualizado) {
          $stmt = mysqli_prepare($conexao, "
            UPDATE vendas
            SET status = ?, atualizado_em = NOW()
            WHERE id = ? AND status = 'PENDENTE'
          ");
        } else {
          $stmt = mysqli_prepare($conexao, "
            UPDATE vendas
            SET status = ?
            WHERE id = ? AND status = 'PENDENTE'
          ");
        }

        mysqli_stmt_bind_param($stmt, "si", $novoStatus, $id);

        if (mysqli_stmt_execute($stmt)) {
          if (mysqli_stmt_affected_rows($stmt) > 0) {

            // recalcular apÃ³s mudar status (importante para comissÃµes)
            if (function_exists("recalcular_venda")) {
              $calc = recalcular_venda($conexao, $id);
              if (!$calc["ok"]) {
                $flash = ["type"=>"warning","msg"=>"Status atualizado para {$novoStatus}, mas falhou recalcular: ".$calc["erro"]];
              } else {
                $flash = ["type"=>"success","msg"=>"Status atualizado para {$novoStatus} e recalculado."];
              }
            } else {
              $flash = ["type"=>"success","msg"=>"Status atualizado para {$novoStatus}."];
            }

            // se marcou como PAGO, marca lead como CONCLUIDO
            if ($novoStatus === 'PAGO' && col_exists($conexao, 'clientes', 'status')) {
              $q = mysqli_query($conexao, "SELECT cliente_id FROM vendas WHERE id=".(int)$id." LIMIT 1");
              $row = $q ? mysqli_fetch_assoc($q) : null;
              $cid = (int)($row['cliente_id'] ?? 0);

              if ($cid > 0) {
                $stmt2 = mysqli_prepare($conexao, "UPDATE clientes SET status='CONCLUIDO' WHERE id=?");
                mysqli_stmt_bind_param($stmt2, "i", $cid);
                mysqli_stmt_execute($stmt2);
                mysqli_stmt_close($stmt2);
              }
            }

          } else {
            $flash = ["type"=>"warning","msg"=>"Nada mudou. Talvez jÃ¡ nÃ£o esteja PENDENTE."];
          }
        } else {
          $flash = ["type"=>"danger","msg"=>"Erro ao atualizar: ".mysqli_error($conexao)];
        }
        mysqli_stmt_close($stmt);
      }
    }
  }
}

// =========================
// Carregar venda + cliente (MODELO NOVO)
// =========================
$selectExtras = "";
if ($hasAtualizado) $selectExtras .= ", v.atualizado_em";
if ($hasCParc) $selectExtras .= ", v.comissao_parceiro";
if ($hasPP)    $selectExtras .= ", v.perc_parceiro";
if ($hasCapId) $selectExtras .= ", v.captador_id";
if ($hasAprovPor) $selectExtras .= ", v.aprovado_por";
if ($hasAprovEm)  $selectExtras .= ", v.aprovado_em";

$stmt = mysqli_prepare($conexao, "
  SELECT
    v.id, v.cliente_id, v.marca, v.modelo, v.ano,
    v.status, v.data_venda, v.criado_em
    $selectExtras,
    v.valor_venda, v.valor_proprietario, v.total_custos, v.lucro,
    v.perc_vendedor, v.perc_rg,
    v.comissao_vendedor, v.comissao_rg,
    v.lucro_minimo, v.precisa_aprovacao,
    c.nome AS cliente_nome, c.telefone AS cliente_telefone, c.email AS cliente_email
  FROM vendas v
  INNER JOIN clientes c ON c.id = v.cliente_id
  WHERE v.id = ?
  LIMIT 1
");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$venda = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$venda) die("Venda nÃ£o encontrada.");

// msg na querystring
if (isset($_GET['msg']) && $_GET['msg'] === 'criada') {
  $flash = ["type"=>"success","msg"=>"Venda criada com sucesso."];
}

// Normaliza dados para mostrar
$valorVendaShow = (float)($venda["valor_venda"] ?? 0);
$valorPropShow  = (float)($venda["valor_proprietario"] ?? 0);
$totalCustos    = (float)($venda["total_custos"] ?? 0);
$lucroShow      = (float)($venda["lucro"] ?? 0);

$comVendShow    = (float)($venda["comissao_vendedor"] ?? 0);
$comRGShow      = (float)($venda["comissao_rg"] ?? 0);

$percVend       = (float)($venda["perc_vendedor"] ?? 0);
$percRG         = (float)($venda["perc_rg"] ?? 0);

$precisaApv     = (int)($venda["precisa_aprovacao"] ?? 0);
$lucroMin = (float)($venda["lucro_minimo"] ?? 0);

$motivoApv = '';
if ($precisaApv === 1) {
  if ($lucroShow <= 0) {
    $motivoApv = 'Lucro â‰¤ 0 (nÃ£o comissiona).';
  } elseif ($lucroShow < $lucroMin) {
    $motivoApv = 'Lucro abaixo do mÃ­nimo (mÃ­nimo: '.money($lucroMin).').';
  } else {
    $motivoApv = 'Precisa aprovaÃ§Ã£o.';
  }
}

$lucroMin = (float)($venda["lucro_minimo"] ?? 0);

$motivoApv = '';
if ($precisaApv === 1) {
  if ($lucroShow <= 0) {
    $motivoApv = 'Motivo: lucro â‰¤ 0 (nÃ£o comissiona).';
  } elseif ($lucroShow < $lucroMin) {
    $motivoApv = 'Motivo: lucro abaixo do mÃ­nimo ('.money($lucroMin).').';
  } else {
    $motivoApv = 'Motivo: precisa de aprovaÃ§Ã£o.';
  }
}


$hasParceiro = ($hasCapId && !empty($venda["captador_id"])) && $hasCParc;
$comParcShow = $hasParceiro ? (float)($venda["comissao_parceiro"] ?? 0) : 0.0;
$percParc    = ($hasParceiro && $hasPP) ? (float)($venda["perc_parceiro"] ?? 0) : 0.0;

$pageTitle = 'Detalhe da Venda';
$pageSubtitle = 'Informações comerciais, financeiras e acompanhamento da venda';
$contentFile = BASE_PATH . '/app/views/admin/vendas/detalhe_venda_content.php';

require BASE_PATH . '/app/views/layouts/admin_layout.php';
