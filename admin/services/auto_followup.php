<?php
require_once __DIR__ . '/../../app/core/bootstrap.php';
require_admin();

$agora = date("Y-m-d H:i:s");

// buscar leads que precisam follow-up
$sql = "
SELECT * FROM leads
WHERE status != 'fechado'
AND (
    last_contact IS NULL 
    OR TIMESTAMPDIFF(HOUR, last_contact, NOW()) >= 24
)
LIMIT 10
"; 

$res = mysqli_query($conexao, $sql);

while($lead = mysqli_fetch_assoc($res)){

    $telefone = preg_replace('/[^0-9]/','',$lead['telefone']);

    $mensagem = "Olá {$lead['nome']}, estou a dar seguimento ao seu interesse no carro {$lead['marca']} {$lead['modelo']}. Ainda está interessado?";

    $link = "https://wa.me/258{$telefone}?text=" . urlencode($mensagem);

    // aqui podes guardar log
    echo "<p>{$lead['nome']} → <a target='_blank' href='$link'>Enviar mensagem</a></p>";

    // atualizar último contacto
    $stmt = mysqli_prepare($conexao, "
        UPDATE leads SET last_contact=? WHERE id=?
    ");
    mysqli_stmt_bind_param($stmt, "si", $agora, $lead['id']);
    mysqli_stmt_execute($stmt);
}