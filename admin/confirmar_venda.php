<?php
// admin/confirmar_venda.php
include("../auth.php");
include("../conexao.php");
include("auth_check.php");
include("admin/includes/db.php");

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function money($v){ return number_format((float)$v, 2, ',', '.'); }

$lead_id = (int)($_GET['lead_id'] ?? 0);
if ($lead_id <= 0) die("lead_id inválido.");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $lead_id = (int)($_POST['lead_id'] ?? 0);
  $valor_venda = (float)($_POST['valor_venda'] ?? 0);
  $valor_proprietario = (float)($_POST['valor_proprietario'] ?? 0);
  $forma_pagamento = trim((string)($_POST['forma_pagamento'] ?? ''));
  $vendedor_id = (int)($_POST['vendedor_id'] ?? 0);   // opcional
  $captador_id = (int)($_POST['captador_id'] ?? 0);   // opcional

  if ($lead_id <= 0 || $valor_venda <= 0 || $valor_proprietario <= 0 || $forma_pagamento === '') {
    die("Preencha os campos obrigatórios.");
  }

  // Buscar lead com cliente_id e carro_id
  $q = mysqli_prepare($conexao, "SELECT id, cliente_id, carro_id, marca, modelo, ano FROM leads WHERE id=? LIMIT 1");
  mysqli_stmt_bind_param($q, "i", $lead_id);
  mysqli_stmt_execute($q);
  $lead = mysqli_fetch_assoc(mysqli_stmt_get_result($q));
  if (!$lead) die("Lead não encontrado.");

  $cliente_id = (int)($lead['cliente_id'] ?? 0);
  $carro_id   = (int)($lead['carro_id'] ?? 0);
  if ($cliente_id <= 0) die("Este lead não tem cliente_id.");
  if ($carro_id <= 0) die("Este lead não tem carro_id associado.");

  // Calcular lucro e comissões (modelo atual: 20/0/80 no teu print)
  // Se já tens includes/financeiro.php com função pronta, dá para chamar aqui.
  $lucro = $valor_venda - $valor_proprietario;

  // percentagens padrão (ajusta se tua regra oficial for 15/10/restante)
  $perc_vendedor = 20.00;
  $perc_parceiro = 0.00;
  $perc_rg       = 80.00;

  $comissao_vendedor = ($vendedor_id > 0) ? ($lucro * ($perc_vendedor/100)) : 0;
  $comissao_parceiro = ($captador_id > 0) ? ($lucro * ($perc_parceiro/100)) : 0;
  $comissao_rg       = $lucro - $comissao_vendedor - $comissao_parceiro;

  $total_custos = 0.00;           // se usas tabela venda_custos depois, deixa 0 aqui
  $lucro_minimo = 30000.00;       // pelo teu print
  $precisa_aprovacao = ($lucro < $lucro_minimo) ? 1 : 0;

  // Procurar cliente pelo telefone
  $q = mysqli_prepare($conexao, "SELECT id FROM clientes WHERE telefone=? LIMIT 1");
  mysqli_stmt_bind_param($q, "s", $lead['telefone']);
  mysqli_stmt_execute($q);
  $resCliente = mysqli_stmt_get_result($q);
  $cliente = mysqli_fetch_assoc($resCliente);

  if ($cliente) {
      $cliente_id = (int)$cliente['id'];
  } else {
      // Criar cliente novo
      $stmtCliente = mysqli_prepare($conexao, "
          INSERT INTO clientes (nome, telefone, email)
          VALUES (?, ?, ?)
      ");
      mysqli_stmt_bind_param($stmtCliente, "sss",
          $lead['nome'],
          $lead['telefone'],
          $lead['email']
      );
      mysqli_stmt_execute($stmtCliente);
      $cliente_id = mysqli_insert_id($conexao);
  }

  // Inserir venda
  $stmt = mysqli_prepare($conexao, "
    INSERT INTO vendas (
      cliente_id, marca, modelo, ano,
      valor_carro, comissao, status, forma_pagamento, data_venda,
      vendedor_id, captador_id,
      rg_valor, vendedor_valor, captador_valor,
      valor_venda, valor_proprietario,
      perc_vendedor, perc_parceiro, perc_rg,
      total_custos, lucro,
      comissao_vendedor, comissao_parceiro, comissao_rg,
      lucro_minimo, precisa_aprovacao
    ) VALUES (
      ?, ?, ?, ?,
      ?, ?, 'PENDENTE', ?, CURDATE(),
      ?, ?,
      ?, ?, ?,
      ?, ?,
      ?, ?, ?,
      ?, ?,
      ?, ?, ?,
      ?, ?
    )
  ");

  if (!$stmt) die("Erro prepare vendas: " . mysqli_error($conexao));

  $marca  = (string)($lead['marca'] ?? '');
  $modelo = (string)($lead['modelo'] ?? '');
  $ano    = (string)($lead['ano'] ?? ''); // na tabela vendas é varchar(10)

  // valor_carro e comissao (campos antigos) — manter consistência
  $valor_carro = $valor_venda;
  $comissao = $comissao_rg;

  mysqli_stmt_bind_param(
    $stmt,
    "isssddsiidddddddddddddddii",
    $cliente_id, $marca, $modelo, $ano,
    $valor_carro, $comissao, $forma_pagamento,
    $vendedor_id, $captador_id,
    $comissao_rg, $comissao_vendedor, $comissao_parceiro,
    $valor_venda, $valor_proprietario,
    $perc_vendedor, $perc_parceiro, $perc_rg,
    $total_custos, $lucro,
    $comissao_vendedor, $comissao_parceiro, $comissao_rg,
    $lucro_minimo, $precisa_aprovacao
  );

  if (!mysqli_stmt_execute($stmt)) {
    die("Erro ao criar venda: " . mysqli_stmt_error($stmt));
  }

  $venda_id = mysqli_insert_id($conexao);
  mysqli_stmt_close($stmt);

  // Marcar carro como vendido
  $up = mysqli_prepare($conexao, "UPDATE carros SET status='vendido', data_venda=NOW(), preco_venda=? WHERE id=? LIMIT 1");
  mysqli_stmt_bind_param($up, "di", $valor_venda, $carro_id);
  mysqli_stmt_execute($up);

  // (Opcional) marcar lead como fechado (já deve estar)
  // mysqli_query($conexao, "UPDATE leads SET status='fechado' WHERE id=$lead_id");

  header("Location: venda_detalhe.php?id=" . $venda_id);
  exit;
}

// GET: buscar lead para mostrar formulário
$q = mysqli_prepare($conexao, "SELECT id, cliente_id, carro_id, nome, telefone, marca, modelo, ano, status FROM leads WHERE id=? LIMIT 1");
mysqli_stmt_bind_param($q, "i", $lead_id);
mysqli_stmt_execute($q);
$lead = mysqli_fetch_assoc(mysqli_stmt_get_result($q));
if (!$lead) die("Lead não encontrado.");
?>
<!doctype html>
<html lang="pt">
<head>
  <meta charset="utf-8">
  <title>Confirmar Venda</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="m-0">Confirmar Venda (Lead #<?=h($lead['id'])?>)</h3>
    <a class="btn btn-outline-dark" href="funil.php">Voltar</a>
  </div>

  <div class="bg-white rounded shadow-sm p-3 mb-3">
    <div><b>Cliente:</b> <?=h($lead['nome'])?> — <?=h($lead['telefone'])?></div>
    <div><b>Carro:</b> <?=h($lead['marca'].' '.$lead['modelo'].' ('.$lead['ano'].')')?> | <b>carro_id:</b> <?=h($lead['carro_id'])?></div>
    <div class="text-muted"><b>cliente_id:</b> <?=h($lead['cliente_id'])?> | <b>status lead:</b> <?=h($lead['status'])?></div>
  </div>

  <form method="POST" class="bg-white rounded shadow-sm p-3">
    <input type="hidden" name="lead_id" value="<?=h($lead_id)?>">

    <div class="row g-3">
      <div class="col-md-4">
        <label class="form-label"><b>Valor de Venda</b></label>
        <input type="number" step="0.01" name="valor_venda" class="form-control" required>
      </div>
      <div class="col-md-4">
        <label class="form-label"><b>Valor do Proprietário</b></label>
        <input type="number" step="0.01" name="valor_proprietario" class="form-control" required>
      </div>
      <div class="col-md-4">
        <label class="form-label"><b>Forma de Pagamento</b></label>
        <input type="text" name="forma_pagamento" class="form-control" placeholder="Ex.: M-Pesa / Transferência / Cash" required>
      </div>

      <div class="col-md-6">
        <label class="form-label">vendedor_id (opcional)</label>
        <input type="number" name="vendedor_id" class="form-control" placeholder="0 se não houver">
      </div>
      <div class="col-md-6">
        <label class="form-label">captador_id (opcional)</label>
        <input type="number" name="captador_id" class="form-control" placeholder="0 se não houver">
      </div>
    </div>

    <div class="d-flex gap-2 mt-3">
      <button class="btn btn-success">Criar Venda</button>
      <a class="btn btn-outline-secondary" href="funil.php">Cancelar</a>
    </div>
  </form>
</div>
</body>
</html>