<?php
include_once("includes/conexao.php");
include_once("includes/funcoes_vendas.php");

// Recebe dados do formulário
$venda_id    = $_POST['venda_id'];
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

// Calcula lucro, comissões e pagamento
$result = calcularComissoes($venda);

// Atualiza no banco
$sql = "UPDATE vendas SET 
    preco_custo='{$preco_custo}',
    preco_venda='{$preco_venda}',
    lucro='{$result['lucro']}',
    comissao_vendedor='{$result['comissao_vendedor']}',
    comissao_parceiro='{$result['comissao_parceiro']}',
    comissao_rg='{$result['comissao_rg']}',
    aprovado='{$aprovado}',
    status='{$status}'
    WHERE id='{$venda_id}'";

if(mysqli_query($conexao, $sql)){
    echo "Venda atualizada com sucesso!";
} else {
    echo "Erro ao atualizar venda: " . mysqli_error($conexao);
}

// $result['pode_pagar'] indica se pagamento pode ser liberado
?>