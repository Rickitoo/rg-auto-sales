<?php
function calcularComissoes(array $venda): array {
    $preco_venda = $venda['preco_venda'] ?? 0;
    $preco_custo = $venda['preco_custo'] ?? 0;
    $status      = strtoupper($venda['status'] ?? 'PENDENTE');
    $tipo_venda  = strtolower($venda['tipo_venda'] ?? 'normal');
    $aprovado    = $venda['aprovado'] ?? 0;

    $lucro = $preco_venda - $preco_custo;
    $comissao_vendedor = 0;
    $comissao_parceiro = 0;
    $comissao_rg = 0;

    if ($status === "CANCELADO") $lucro = 0;

    if ($status === "CONCLUIDO") {
        if ($tipo_venda === "normal") {
            $comissao_vendedor = $lucro * 0.20;
            $comissao_rg       = $lucro * 0.80;
        } elseif ($tipo_venda === "parceria") {
            if ($aprovado == 1) {
                $comissao_parceiro = $lucro * 0.10;
                $comissao_vendedor = $lucro * 0.15;
                $comissao_rg       = $lucro * 0.75;
            }
        }
    }

    $pode_pagar = ($status === "CONCLUIDO") && !($tipo_venda === "parceria" && $aprovado == 0);

    return [
        'lucro' => $lucro,
        'comissao_vendedor' => $comissao_vendedor,
        'comissao_parceiro' => $comissao_parceiro,
        'comissao_rg' => $comissao_rg,
        'pode_pagar' => $pode_pagar
    ];
}
?>