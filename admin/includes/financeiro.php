<?php
if (!function_exists('col_exists')) {
  function col_exists(mysqli $con, string $table, string $col): bool {
    $table = mysqli_real_escape_string($con, $table);
    $col   = mysqli_real_escape_string($con, $col);
    $q = mysqli_query($con, "SHOW COLUMNS FROM `$table` LIKE '$col'");
    return $q && mysqli_num_rows($q) > 0;
  }
}

if (!function_exists('r2')) {
  function r2($v){ return round((float)$v, 2); }
}

function recalcular_venda(mysqli $con, int $venda_id): array {

  // colunas obrigatórias (modelo novo + parceiro)
  $need = [
    "status",
    "valor_venda","valor_proprietario","total_custos","lucro",
    "comissao_rg","comissao_vendedor","comissao_parceiro",
    "perc_rg","perc_vendedor","perc_parceiro",
    "lucro_minimo","precisa_aprovacao"
  ];

  foreach ($need as $c) {
    if (!col_exists($con, "vendas", $c)) {
      return ["ok"=>false, "erro"=>"Falta a coluna `$c` na tabela vendas."];
    }
  }

  $hasVendId = col_exists($con, "vendas", "vendedor_id");
  $hasCapId  = col_exists($con, "vendas", "captador_id");
  $hasUpd    = col_exists($con, "vendas", "atualizado_em");

  // buscar status também
  $sel = "SELECT id, status, valor_venda, valor_proprietario, lucro_minimo";
  if ($hasVendId) $sel .= ", vendedor_id";
  if ($hasCapId)  $sel .= ", captador_id";
  $sel .= " FROM vendas WHERE id=? LIMIT 1";

  $st = mysqli_prepare($con, $sel);
  mysqli_stmt_bind_param($st, "i", $venda_id);
  mysqli_stmt_execute($st);
  $r = mysqli_stmt_get_result($st);
  $v = mysqli_fetch_assoc($r);
  mysqli_stmt_close($st);

  if (!$v) return ["ok"=>false, "erro"=>"Venda não encontrada."];

  $status      = (string)($v["status"] ?? "PENDENTE");
  $valor_venda = (float)($v["valor_venda"] ?? 0);
  $valor_prop  = (float)($v["valor_proprietario"] ?? 0);
  $lucro_min   = (float)($v["lucro_minimo"] ?? 0);

  if ($valor_venda <= 0) return ["ok"=>false, "erro"=>"valor_venda inválido."];
  if ($valor_prop < 0)   return ["ok"=>false, "erro"=>"valor_proprietario inválido."];

  $temVendedor = $hasVendId ? !empty($v["vendedor_id"]) : false;
  $temParceiro = $hasCapId  ? !empty($v["captador_id"]) : false;

  // total custos por venda
  $total_custos = 0.0;
  if (col_exists($con, "custos", "venda_id")) {
    $q = mysqli_prepare($con, "SELECT COALESCE(SUM(valor),0) AS t FROM custos WHERE venda_id=?");
    mysqli_stmt_bind_param($q, "i", $venda_id);
    mysqli_stmt_execute($q);
    $res = mysqli_stmt_get_result($q);
    $row = mysqli_fetch_assoc($res);
    mysqli_stmt_close($q);
    $total_custos = (float)($row["t"] ?? 0);
  }

  // lucro real (sempre calcula e guarda)
  $lucro = $valor_venda - $valor_prop - $total_custos;

  // aprovação: não zera lucro; só bloqueia pagamento/comissão
  $precisa_aprov = ($lucro <= 0 || $lucro < $lucro_min) ? 1 : 0;

  // percentagens oficiais
  $perc_parceiro = $temParceiro ? 10.0 : 0.0;
  $perc_vendedor = $temVendedor ? 15.0 : 0.0;
  $perc_rg       = 100.0 - ($perc_parceiro + $perc_vendedor);
  if ($perc_rg < 0) $perc_rg = 0.0;

  // comissões só quando PAGO, lucro > 0 e aprovado
  $base = ($status === "PAGO" && $lucro > 0 && $precisa_aprov === 0) ? $lucro : 0.0;

  $com_parceiro = $base * ($perc_parceiro / 100.0);
  $com_vendedor = $base * ($perc_vendedor / 100.0);
  $com_rg       = $base * ($perc_rg / 100.0);

  // arredonda
  $total_custos  = r2($total_custos);
  $lucro         = r2($lucro);
  $com_parceiro  = r2($com_parceiro);
  $com_vendedor  = r2($com_vendedor);
  $com_rg        = r2($com_rg);

  // update
  $sqlUp = "
    UPDATE vendas
    SET
      total_custos=?,
      lucro=?,
      perc_parceiro=?, perc_vendedor=?, perc_rg=?,
      comissao_parceiro=?, comissao_vendedor=?, comissao_rg=?,
      precisa_aprovacao=?
  ";
  if ($hasUpd) $sqlUp .= ", atualizado_em=NOW() ";
  $sqlUp .= " WHERE id=? LIMIT 1";

  $up = mysqli_prepare($con, $sqlUp);

  mysqli_stmt_bind_param(
    $up,
    "ddddddddii",
    $total_custos,
    $lucro,
    $perc_parceiro, $perc_vendedor, $perc_rg,
    $com_parceiro, $com_vendedor, $com_rg,
    $precisa_aprov,
    $venda_id
  );

  if (!mysqli_stmt_execute($up)) {
    $err = mysqli_error($con);
    mysqli_stmt_close($up);
    return ["ok"=>false, "erro"=>$err];
  }
  mysqli_stmt_close($up);

  return ["ok"=>true];
}
