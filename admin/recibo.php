<?php
// admin/recibo.php
include("../auth.php");
include("../conexao.php");
include("auth_check.php");
include("admin/includes/db.php");

if (session_status() === PHP_SESSION_NONE) session_start();

function money($v){ return number_format((float)$v, 2, ',', '.') . " MT"; }
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function col_exists(mysqli $con, string $table, string $col): bool {
  $table = mysqli_real_escape_string($con, $table);
  $col   = mysqli_real_escape_string($con, $col);
  $q = mysqli_query($con, "SHOW COLUMNS FROM `$table` LIKE '$col'");
  return $q && mysqli_num_rows($q) > 0;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) die("ID inválido.");

// Detecta colunas novas
$hasValorVenda = col_exists($conexao, "vendas", "valor_venda");
$hasCRG       = col_exists($conexao, "vendas", "comissao_rg");
$temVendedor  = col_exists($conexao, "vendas", "vendedor_id");

// Extras do SELECT
$selectExtras = "";
if ($hasValorVenda) $selectExtras .= ", v.valor_venda";
if ($hasCRG)       $selectExtras .= ", v.comissao_rg";

// JOIN vendedor só se existir vendedor_id
$joinVendedor = "";
$selectVendedor = "NULL AS vendedor_nome";
if ($temVendedor) {
  $joinVendedor = "LEFT JOIN pessoas p ON p.id = v.vendedor_id";
  $selectVendedor = "p.nome AS vendedor_nome";
}

$stmt = mysqli_prepare($conexao, "
  SELECT
    v.id, v.cliente_id, v.marca, v.modelo, v.ano,
    v.valor_carro, v.comissao, v.status, v.forma_pagamento, v.data_venda, v.criado_em
    $selectExtras,
    $selectVendedor,
    c.nome AS cliente_nome, c.telefone AS cliente_telefone, c.email AS cliente_email
  FROM vendas v
  INNER JOIN clientes c ON c.id = v.cliente_id
  $joinVendedor
  WHERE v.id = ?
  LIMIT 1
");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$venda = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$venda) die("Venda não encontrada.");

// Só permite recibo quando PAGO
if (($venda['status'] ?? '') !== 'PAGO') {
  die("Recibo disponível apenas para vendas com status PAGO.");
}

// TOTAL: preferir comissão RG (modelo novo), senão comissão antiga
$totalPago = $hasCRG ? (float)($venda['comissao_rg'] ?? 0) : (float)($venda['comissao'] ?? 0);

// referência do valor do carro/venda (não é o total pago)
$valorRef = $hasValorVenda ? (float)($venda['valor_venda'] ?? 0) : (float)($venda['valor_carro'] ?? 0);

// dados da empresa
$empresaNome = "RG Auto Sales";
$empresaCidade = "Maputo - Moçambique";
$empresaContato = "+258 862934721";
$empresaEmail = "RGSolutions420@gmail.com";

// Logo
$logoPath = "../ImagensRG/Logo_moderno_RG_Auto_Sales.png";

// Nº do recibo
$nrRecibo = "RG-" . str_pad((string)$venda['id'], 6, "0", STR_PAD_LEFT);
$emitidoEm = date("Y-m-d H:i");

// forma de pagamento amigável
$fp = $venda['forma_pagamento'] ?? '';
$map = [
  'MPESA' => 'M-Pesa',
  'E-MOLA' => 'E-Mola',
  'TRANSFERENCIA' => 'Transferência',
  'CASH' => 'Cash',
  'OUTRO' => 'Outro'
];
$formaLabel = $map[$fp] ?? ($fp !== '' ? $fp : '—');

// nome do vendedor (se não existir, mostra —)
$vendedorNome = $venda['vendedor_nome'] ?? null;
?>
<!doctype html>
<html lang="pt">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Recibo <?php echo h($nrRecibo); ?> - RG Auto Sales</title>
  <style>
    body { font-family: Arial, sans-serif; color:#111; background:#fff; }
    .wrap { max-width: 820px; margin: 0 auto; padding: 24px; }
    .top { display:flex; justify-content:space-between; align-items:flex-start; gap:16px; }
    .brand { display:flex; gap:12px; align-items:center; }
    .brand img { width: 70px; height:auto; object-fit:contain; }
    .box { border:1px solid #ddd; border-radius:10px; padding:16px; }
    .muted { color:#666; font-size: 12px; }
    h1 { margin:0; font-size: 22px; }
    h2 { margin:0 0 6px 0; font-size: 16px; }
    .grid { display:grid; grid-template-columns: 1fr 1fr; gap:12px; margin-top:12px; }
    table { width:100%; border-collapse: collapse; margin-top: 12px; }
    th, td { border-bottom: 1px solid #eee; padding: 10px 6px; text-align:left; }
    th { background:#fafafa; }
    .right { text-align:right; }
    .total { font-size: 18px; font-weight:700; }
    .actions { margin-top: 16px; display:flex; gap:8px; }
    .btn { padding:10px 14px; border-radius:10px; border:1px solid #111; background:#111; color:#fff; cursor:pointer; }
    .btn2 { padding:10px 14px; border-radius:10px; border:1px solid #111; background:#fff; color:#111; cursor:pointer; text-decoration:none; display:inline-block; }

    @media print {
      .actions { display:none; }
      body { margin:0; }
      .box { border-color:#ccc; }
      a { color:#111; text-decoration:none; }
    }
  </style>
</head>
<body>
<div class="wrap">

  <div class="top">
    <div>
      <div class="brand">
        <img src="<?php echo h($logoPath); ?>" alt="RG Logo" onerror="this.style.display='none'">
        <div>
          <h1><?php echo h($empresaNome); ?></h1>
          <div class="muted"><?php echo h($empresaCidade); ?></div>
          <div class="muted"><?php echo h($empresaContato); ?> · <?php echo h($empresaEmail); ?></div>
        </div>
      </div>
    </div>

    <div class="box" style="min-width:280px;">
      <h2>RECIBO</h2>
      <div class="muted">Nº: <b><?php echo h($nrRecibo); ?></b></div>
      <div class="muted">Emitido em: <b><?php echo h($emitidoEm); ?></b></div>
      <div class="muted">Data da venda: <b><?php echo h($venda['data_venda']); ?></b></div>
      <div class="muted">Forma de pagamento: <b><?php echo h($formaLabel); ?></b></div>
      <div class="muted">Status: <b><?php echo h($venda['status']); ?></b></div>
    </div>
  </div>

  <div class="grid">
    <div class="box">
      <h2>Cliente</h2>
      <div><b><?php echo h($venda['cliente_nome']); ?></b></div>
      <div class="muted">Tel: <?php echo h($venda['cliente_telefone'] ?? '-'); ?></div>
      <div class="muted">Email: <?php echo h($venda['cliente_email'] ?? '-'); ?></div>
    </div>

    <div class="box">
      <h2>Atendimento</h2>
      <div class="muted">Vendedor: <b><?php echo h($vendedorNome ?: '—'); ?></b></div>
      <div class="muted">Ref. Venda: <b>#<?php echo (int)$venda['id']; ?></b></div>
      <div class="muted">Criado em: <b><?php echo h($venda['criado_em']); ?></b></div>
    </div>
  </div>

  <div class="box" style="margin-top:12px;">
    <h2>Detalhes</h2>
    <table>
      <thead>
        <tr>
          <th>Descrição</th>
          <th class="right">Valor</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>
            Comissão de intermediação (RG) — <?php echo h($venda['marca']." ".$venda['modelo']." (".$venda['ano'].")"); ?>
          </td>
          <td class="right"><?php echo money($totalPago); ?></td>
        </tr>
        <tr>
          <td class="muted"><?php echo $hasValorVenda ? "Valor de venda (referência)" : "Valor do carro (referência)"; ?></td>
          <td class="right muted"><?php echo money($valorRef); ?></td>
        </tr>
      </tbody>
      <tfoot>
        <tr>
          <td class="right total">TOTAL PAGO</td>
          <td class="right total"><?php echo money($totalPago); ?></td>
        </tr>
      </tfoot>
    </table>

    <div class="muted" style="margin-top:10px;">
      Observação: Este recibo comprova o pagamento da comissão de intermediação à RG Auto Sales.
      Não substitui documentos legais de compra e venda do veículo.
    </div>

    <div class="muted" style="margin-top:14px;">
      Assinatura/Carimbo: ________________________________________
    </div>
  </div>

  <div class="actions">
    <button class="btn" onclick="window.print()">Imprimir / Guardar PDF</button>
    <a class="btn2" href="venda_detalhe.php?id=<?php echo (int)$venda['id']; ?>">Voltar</a>
  </div>

</div>
</body>
</html>
