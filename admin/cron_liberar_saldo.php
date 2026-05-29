<?php
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Acesso negado.');
}

require_once __DIR__ . '/../app/core/bootstrap.php';
require_admin();

// pegar vendas pagas ainda não processadas
$res = mysqli_query($conexao,"
SELECT id, user_id, comissao_vendedor 
FROM vendas
WHERE status='PAGO' AND processado=0
");

while($v = mysqli_fetch_assoc($res)) {

    $user_id = (int)$v['user_id'];
    $valor = (float)$v['comissao_vendedor'];

    // mover dinheiro
    mysqli_query($conexao,"
    UPDATE wallet
    SET saldo_pendente = saldo_pendente - $valor,
        saldo_disponivel = saldo_disponivel + $valor
    WHERE user_id = $user_id
    ");

    // marcar como processado
    mysqli_query($conexao,"
    UPDATE vendas SET processado=1 WHERE id=".$v['id']
    );
}

$res = mysqli_query($conexao,"
SELECT id, nome, telefone 
FROM leads
WHERE status IN ('novo','contactado')
AND TIMESTAMPDIFF(HOUR, criado_em, NOW()) >= 24
");

while($l = mysqli_fetch_assoc($res)) {

    // aqui podes integrar WhatsApp API no futuro

    echo "Follow-up necessário: ".$l['nome']."\n";
}
if ($valor < 1000) {
    $erro = "Valor mínimo de saque: 1000 MT";
}
$alertas = mysqli_fetch_row(mysqli_query($conexao,"
SELECT COUNT(*) FROM leads
WHERE status!='fechado'
AND TIMESTAMPDIFF(HOUR, criado_em, NOW()) > 48
"))[0];
$res = mysqli_query($conexao,"
SELECT user_id, COUNT(*) vendas, SUM(lucro) lucro
FROM vendas
WHERE status='PAGO'
GROUP BY user_id
ORDER BY lucro DESC
LIMIT 5
");
