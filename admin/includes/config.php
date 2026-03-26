<?php

// Lê config do DB e devolve array com percent/minimo e shares.

function rg_get_config(mysqli $con): array {
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

function rg_calc_comissao(float $valor_carro, array $cfg): float {
    $percent = $cfg["percent"];
    $minimo  = $cfg["minimo"];
    return max($valor_carro * $percent, $minimo);
}

function rg_split_comissao(float $comissao, array $cfg): array {
    $rg  = $comissao * $cfg["rg_share"];
    $vd  = $comissao * $cfg["vend_share"];
    $cap = $comissao * $cfg["cap_share"];

    // Ajuste de arredondamento para fechar exatamente
    $soma = $rg + $vd + $cap;
    $diff = $comissao - $soma;
    $rg += $diff;

    return ["rg" => $rg, "vendedor" => $vd, "captador" => $cap];
}
