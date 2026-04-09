<?php
include("../auth.php");
include("../conexao.php");
include("auth_check.php");
include("admin/includes/db.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// helper: verificar se coluna existe
function col_exists(mysqli $con, string $table, string $col): bool {
    $table = mysqli_real_escape_string($con, $table);
    $col   = mysqli_real_escape_string($con, $col);
    $q = mysqli_query($con, "SHOW COLUMNS FROM `$table` LIKE '$col'");
    return $q && mysqli_num_rows($q) > 0;
}

// detectar modelo novo
$hasValorVenda  = col_exists($conexao, "vendas", "valor_venda");
$hasValorProp   = col_exists($conexao, "vendas", "valor_proprietario");
$hasTCustos     = col_exists($conexao, "vendas", "total_custos");
$hasLucro       = col_exists($conexao, "vendas", "lucro");
$hasCVend       = col_exists($conexao, "vendas", "comissao_vendedor");
$hasCRG         = col_exists($conexao, "vendas", "comissao_rg");

// headers CSV
$filename = "vendas_rg_autosales_" . date("Ymd_His") . ".csv";
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="'.$filename.'"');

// BOM UTF-8 (Excel)
echo "\xEF\xBB\xBF";

$out = fopen('php://output', 'w');
$sep = ";";

// cabeçalho base (igual ao teu)
$header = [
    'ID',
    'Data',
    'Cliente',
    'Telefone',
    'Carro',
    'Valor (antigo)',
    'Comissão (antiga)',
    'Status'
];

// colunas novas (se existirem)
if ($hasValorVenda) $header[] = 'Valor venda';
if ($hasValorProp)  $header[] = 'Valor proprietário';
if ($hasTCustos)    $header[] = 'Total custos';
if ($hasLucro)      $header[] = 'Lucro';
if ($hasCVend)      $header[] = 'Comissão vendedor';
if ($hasCRG)        $header[] = 'Comissão RG';

fputcsv($out, $header, $sep);

// montar SELECT dinâmico
$selectExtras = "";
if ($hasValorVenda) $selectExtras .= ", v.valor_venda";
if ($hasValorProp)  $selectExtras .= ", v.valor_proprietario";
if ($hasTCustos)    $selectExtras .= ", v.total_custos";
if ($hasLucro)      $selectExtras .= ", v.lucro";
if ($hasCVend)      $selectExtras .= ", v.comissao_vendedor";
if ($hasCRG)        $selectExtras .= ", v.comissao_rg";

$sql = "
  SELECT
    v.id, v.data_venda, v.marca, v.modelo, v.ano,
    v.valor_carro, v.comissao, v.status,
    c.nome, c.telefone
    $selectExtras
  FROM vendas v
  INNER JOIN clientes c ON c.id = v.cliente_id
  ORDER BY v.id DESC
";

$res = mysqli_query($conexao, $sql);

while ($r = mysqli_fetch_assoc($res)) {

    $row = [
        $r['id'],
        $r['data_venda'],
        $r['nome'],
        $r['telefone'],
        $r['marca'].' '.$r['modelo'].' ('.$r['ano'].')',
        $r['valor_carro'],
        $r['comissao'],
        $r['status']
    ];

    if ($hasValorVenda) $row[] = $r['valor_venda'] ?? '';
    if ($hasValorProp)  $row[] = $r['valor_proprietario'] ?? '';
    if ($hasTCustos)    $row[] = $r['total_custos'] ?? '';
    if ($hasLucro)      $row[] = $r['lucro'] ?? '';
    if ($hasCVend)      $row[] = $r['comissao_vendedor'] ?? '';
    if ($hasCRG)        $row[] = $r['comissao_rg'] ?? '';

    fputcsv($out, $row, $sep);
}

fclose($out);
exit;
