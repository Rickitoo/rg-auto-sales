<?php
require_once __DIR__ . '/../../app/core/bootstrap.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Metodo invalido.');
}

$csrfToken = $_POST['csrf_token'] ?? '';
if (
    !is_string($csrfToken) ||
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $csrfToken)
) {
    http_response_code(403);
    exit('CSRF invalido.');
}

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

while ($lead = mysqli_fetch_assoc($res)) {
    $leadId = (int)($lead['id'] ?? 0);

    if ($leadId <= 0) {
        continue;
    }

    $telefone = preg_replace('/[^0-9]/', '', $lead['telefone']);

    $mensagem = "Olá {$lead['nome']}, estou a dar seguimento ao seu interesse no carro {$lead['marca']} {$lead['modelo']}. Ainda está interessado?";

    $link = "https://wa.me/258{$telefone}?text=" . urlencode($mensagem);

    // aqui podes guardar log
    echo "<p>{$lead['nome']} → <a target='_blank' href='$link'>Enviar mensagem</a></p>";

    // atualizar último contacto
    $stmt = mysqli_prepare($conexao, "
        UPDATE leads SET last_contact=? WHERE id=?
    ");
    mysqli_stmt_bind_param($stmt, "si", $agora, $leadId);
    mysqli_stmt_execute($stmt);
}
