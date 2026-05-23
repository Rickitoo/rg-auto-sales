<?php

if (!function_exists('calcularComissoes')) {
    function calcularComissoes(array $venda): array
    {
        $preco_venda = $venda['preco_venda'] ?? 0;
        $preco_custo = $venda['preco_custo'] ?? 0;
        $status      = strtoupper($venda['status'] ?? 'PENDENTE');
        $tipo_venda  = strtolower($venda['tipo_venda'] ?? 'normal');
        $aprovado    = $venda['aprovado'] ?? 0;

        $lucro = $preco_venda - $preco_custo;
        $comissao_vendedor = 0;
        $comissao_parceiro = 0;
        $comissao_rg = 0;

        if ($status === "CANCELADO") {
            $lucro = 0;
        }

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
}

if (!function_exists('rg_get_config')) {
    function rg_get_config(mysqli $con): array
    {
        $cfg = [
            "percent" => 0.07,
            "minimo"  => 20000,
            "rg_share" => 0.40,
            "vend_share" => 0.30,
            "cap_share" => 0.30
        ];

        $res = mysqli_query($con, "SELECT * FROM config ORDER BY id DESC LIMIT 1");
        if ($res && ($row = mysqli_fetch_assoc($res))) {
            $cfg["percent"] = (float)$row["percent_comissao"];
            $cfg["minimo"]  = (float)$row["minimo_comissao"];
            $cfg["rg_share"]   = (float)$row["rg_share"];
            $cfg["vend_share"] = (float)$row["vendedor_share"];
            $cfg["cap_share"]  = (float)$row["captador_share"];
        }

        return $cfg;
    }
}

if (!function_exists('rg_calc_comissao')) {
    function rg_calc_comissao(float $valor_carro, array $cfg): float
    {
        $percent = $cfg["percent"];
        $minimo  = $cfg["minimo"];

        return max($valor_carro * $percent, $minimo);
    }
}

if (!function_exists('rg_split_comissao')) {
    function rg_split_comissao(float $comissao, array $cfg): array
    {
        $rg  = $comissao * $cfg["rg_share"];
        $vd  = $comissao * $cfg["vend_share"];
        $cap = $comissao * $cfg["cap_share"];

        $soma = $rg + $vd + $cap;
        $diff = $comissao - $soma;
        $rg += $diff;

        return ["rg" => $rg, "vendedor" => $vd, "captador" => $cap];
    }
}
