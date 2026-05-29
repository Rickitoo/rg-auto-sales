<?php
require_once __DIR__ . '/../../app/core/bootstrap.php';
require_admin();

if ($_SESSION['user']['role'] !== 'admin') {
    redirect_to('auth/login.php');
    exit();
}

if (session_status() === PHP_SESSION_NONE) {
}

// inclui financeiro (se existir)

// Garante CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$flash = null;

if (!function_exists('h')) { function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); } }
function money($v){ return number_format((float)$v, 2, ',', '.')." MT"; }
function col_exists(mysqli $con, string $table, string $col): bool {
    $table = mysqli_real_escape_string($con, $table);
    $col   = mysqli_real_escape_string($con, $col);
    $q = mysqli_query($con, "SHOW COLUMNS FROM `$table` LIKE '$col'");
    return $q && mysqli_num_rows($q) > 0;
}

// Detecta colunas novas (pra nÃ£o quebrar teu banco)
$hasLucro   = col_exists($conexao, "vendas", "lucro");
$hasTCustos = col_exists($conexao, "vendas", "total_custos");
$hasCVend   = col_exists($conexao, "vendas", "comissao_vendedor");
$hasCRG     = col_exists($conexao, "vendas", "comissao_rg");
$hasApv     = col_exists($conexao, "vendas", "precisa_aprovacao");

// ==============================
// AÃ‡Ã•ES (POST): pagar / cancelar / recalcular
// ==============================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao  = $_POST['acao'] ?? '';
    $id    = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $token = $_POST['token'] ?? '';

    if ($id <= 0) {
        $flash = ["type" => "danger", "msg" => "ID invÃ¡lido."];
    } elseif (!hash_equals($_SESSION['csrf_token'], $token)) {
        $flash = ["type" => "danger", "msg" => "AÃ§Ã£o bloqueada (token invÃ¡lido)."];
    } elseif (!in_array($acao, ['pagar', 'cancelar', 'recalcular'], true)) {
        $flash = ["type" => "danger", "msg" => "AÃ§Ã£o invÃ¡lida."];
    } else {

        // âœ… Recalcular manualmente (sem mudar status)
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

        // âœ… Pagar / Cancelar
        if ($acao === "pagar" || $acao === "cancelar") {
            $novoStatus = ($acao === 'pagar') ? 'PAGO' : 'CANCELADO';

            // Se tiver regra de aprovaÃ§Ã£o e quiseres travar:
            if ($acao === "pagar" && $hasApv) {
                $chk = mysqli_prepare($conexao, "SELECT precisa_aprovacao FROM vendas WHERE id=? LIMIT 1");
                mysqli_stmt_bind_param($chk, "i", $id);
                mysqli_stmt_execute($chk);
                $r = mysqli_stmt_get_result($chk);
                $row = mysqli_fetch_assoc($r);
                mysqli_stmt_close($chk);

                if ($row && (int)$row["precisa_aprovacao"] === 1) {
                    $flash = ["type"=>"warning","msg"=>"Esta venda precisa de aprovaÃ§Ã£o (lucro abaixo do mÃ­nimo). NÃ£o foi marcada como PAGA."];
                    $novoStatus = null;
                }
            }

            if ($novoStatus) {
                // SÃ³ atualiza se ainda nÃ£o estiver PAGO/CANCELADO
                $stmt = mysqli_prepare($conexao, "UPDATE vendas SET status = ? WHERE id = ? AND status = 'PENDENTE'");
                mysqli_stmt_bind_param($stmt, "si", $novoStatus, $id);

                if (mysqli_stmt_execute($stmt)) {
                    if (mysqli_stmt_affected_rows($stmt) > 0) {

                        // tenta recalcular apÃ³s mudar status
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
                        $flash = ["type" => "warning", "msg" => "Nada mudou. Talvez a venda jÃ¡ nÃ£o esteja PENDENTE."];
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
// Totais rÃ¡pidos (usa comissao_rg se existir; senÃ£o usa comissao antiga)
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

$pageTitle = 'Vendas';
$pageSubtitle = 'Gestão comercial, pagamentos e histórico de vendas';
$contentFile = BASE_PATH . '/app/views/admin/vendas/vendas_content.php';

require BASE_PATH . '/app/views/layouts/admin_layout.php';
