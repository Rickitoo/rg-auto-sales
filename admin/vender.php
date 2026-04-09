<?php
include_once("includes/conexao.php");
include_once("includes/funcoes_vendas.php");

// Recebe os dados do formulário
$preco_venda = $_POST['preco_venda'];
$preco_custo = $_POST['preco_custo'];
$status      = $_POST['status'];      // "CONCLUIDO", "CANCELADO", etc.
$tipo_venda  = $_POST['tipo_venda'];  // "normal" ou "parceria"
$aprovado    = $_POST['aprovado'];    // 1 ou 0

// Monta array da venda
$venda = [
    'preco_venda' => $preco_venda,
    'preco_custo' => $preco_custo,
    'status'      => $status,
    'tipo_venda'  => $tipo_venda,
    'aprovado'    => $aprovado
];

// Chama a função que calcula lucro, comissões e pagamento
$result = calcularComissoes($venda);

// Insere no banco
$sql = "INSERT INTO vendas 
    (preco_venda, preco_custo, lucro, comissao_vendedor, comissao_parceiro, comissao_rg, tipo_venda, aprovado, status)
    VALUES
    ('{$preco_venda}', '{$preco_custo}', '{$result['lucro']}', '{$result['comissao_vendedor']}', '{$result['comissao_parceiro']}', '{$result['comissao_rg']}', '{$tipo_venda}', '{$aprovado}', '{$status}')";

if(mysqli_query($conexao, $sql)){
    echo "Venda registrada com sucesso!";
} else {
    echo "Erro ao registrar venda: " . mysqli_error($conexao);
}

// Pode usar $result['pode_pagar'] para controlar pagamento no front-end ou admin
?>