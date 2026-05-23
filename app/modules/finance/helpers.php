<?php

if (!function_exists('r2')) {
    function r2($v)
    {
        return round((float)$v, 2);
    }
}

if (!function_exists('recalcular_venda')) {
    function recalcular_venda(mysqli $con, int $venda_id): array
    {
        $sql = "SELECT 
                id, status,
                valor_venda, valor_proprietario,
                lucro_minimo,
                vendedor_id, captador_id
            FROM vendas
            WHERE id=? LIMIT 1";

        $st = mysqli_prepare($con, $sql);
        mysqli_stmt_bind_param($st, "i", $venda_id);
        mysqli_stmt_execute($st);
        $res = mysqli_stmt_get_result($st);
        $v = mysqli_fetch_assoc($res);
        mysqli_stmt_close($st);

        if (!$v) {
            return ["ok"=>false, "erro"=>"Venda nÃ£o encontrada"];
        }

        $valor_venda  = (float)$v['valor_venda'];
        $valor_prop   = (float)$v['valor_proprietario'];
        $lucro_min    = (float)$v['lucro_minimo'];

        if ($valor_venda <= 0) {
            return ["ok"=>false, "erro"=>"Valor de venda invÃ¡lido"];
        }

        $temVendedor = !empty($v['vendedor_id']);
        $temParceiro = !empty($v['captador_id']);

        $q = mysqli_prepare($con, "
        SELECT COALESCE(SUM(valor),0) AS total 
        FROM custos 
        WHERE venda_id=?
    ");
        mysqli_stmt_bind_param($q, "i", $venda_id);
        mysqli_stmt_execute($q);
        $r = mysqli_stmt_get_result($q);
        $row = mysqli_fetch_assoc($r);
        mysqli_stmt_close($q);

        $total_custos = (float)$row['total'];
        $lucro = $valor_venda - $valor_prop - $total_custos;
        $precisa_aprovacao = ($lucro <= 0 || $lucro < $lucro_min) ? 1 : 0;

        $perc_parceiro = $temParceiro ? 10.0 : 0.0;
        $perc_vendedor = $temVendedor ? 15.0 : 0.0;

        if (($perc_parceiro + $perc_vendedor) > 100) {
            return ["ok"=>false, "erro"=>"Percentagens invÃ¡lidas"];
        }

        $perc_rg = 100.0 - ($perc_parceiro + $perc_vendedor);

        $base = ($lucro > 0 && $precisa_aprovacao === 0) ? $lucro : 0;

        $com_parceiro = $base * ($perc_parceiro / 100);
        $com_vendedor = $base * ($perc_vendedor / 100);
        $com_rg       = $base * ($perc_rg / 100);
        $pode_pagar = ($base > 0) ? 1 : 0;

        $total_custos = r2($total_custos);
        $lucro        = r2($lucro);
        $com_parceiro = r2($com_parceiro);
        $com_vendedor = r2($com_vendedor);
        $com_rg       = r2($com_rg);

        $sql = "
        UPDATE vendas SET
            total_custos=?,
            lucro=?,
            perc_parceiro=?,
            perc_vendedor=?,
            perc_rg=?,
            comissao_parceiro=?,
            comissao_vendedor=?,
            comissao_rg=?,
            precisa_aprovacao=?,
            pode_pagar=?,
            atualizado_em=NOW()
        WHERE id=?
        LIMIT 1
    ";

        $st = mysqli_prepare($con, $sql);

        mysqli_stmt_bind_param(
            $st,
            "ddddddddiii",
            $total_custos,
            $lucro,
            $perc_parceiro,
            $perc_vendedor,
            $perc_rg,
            $com_parceiro,
            $com_vendedor,
            $com_rg,
            $precisa_aprovacao,
            $pode_pagar,
            $venda_id
        );

        if (!mysqli_stmt_execute($st)) {
            $err = mysqli_error($con);
            mysqli_stmt_close($st);

            return ["ok"=>false, "erro"=>$err];
        }

        mysqli_stmt_close($st);

        return ["ok"=>true];
    }
}
