<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

include("conexao.php");

function clean($s){
    return trim((string)$s);
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: test_drive.html");
    exit;
}

$nome      = clean($_POST['nome'] ?? '');
$email     = clean($_POST['email'] ?? '');
$telefone  = clean($_POST['telefone'] ?? '');
$sexo      = clean($_POST['sexo'] ?? '');
$data = ($_POST['data_test_drive'] ?? '');
$hora = ($_POST['hora_test_drive'] ?? '');

if (!$data || !$hora) {
    die("Data ou hora não recebidas do formulário.");
}
if ($data < date('Y-m-d')) {
    die("Data inválida");
}
if (empty($hora)) {
    die("Hora obrigatória");
}

$marca     = clean($_POST['marca'] ?? '');
$modelo    = clean($_POST['modelo'] ?? '');
$ano       = (int)($_POST['ano'] ?? 0);
$mensagem  = clean($_POST['mensagem'] ?? '');

if ($nome=='' || $telefone=='' || $marca=='' || $modelo=='' || $ano<=0) {
    die("Preencha os campos obrigatórios.");
}


// 1️⃣ Guardar no banco
$stmt = mysqli_prepare($conexao, "
INSERT INTO clientes 
(tipo, nome, telefone, email, sexo, marca, modelo, ano, data_test_drive, hora_test_drive, mensagem, origem, estado)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'site', 'novo')
");

$tipo = "testdrive";

mysqli_stmt_bind_param(
    $stmt,
    "sssssssisss",
    $tipo,
    $nome,
    $telefone,
    $email,
    $sexo,
    $marca,
    $modelo,
    $ano,
    $data,
    $hora,
    $mensagem
);

if (!mysqli_stmt_execute($stmt)) {
    die("Erro ao salvar lead: " . mysqli_stmt_error($stmt));
}

$lead_id = mysqli_insert_id($conexao);
mysqli_stmt_close($stmt);

// 2️⃣ Redirecionar para WhatsApp
// 2️⃣ Montar mensagem (normal) e URL-encode no final
$numeroRG = "258862934721";

$msg  = "LEAD #$lead_id (Test Drive)%0A";
$msg .= "Nome: $nome%0A";
$msg .= "Telefone: $telefone%0A";
if ($email !== '') $msg .= "Email: $email%0A";
$msg .= "Carro: $marca $modelo ($ano)%0A";
$msg .= "Data: $data às $hora%0A";
if ($mensagem !== '') $msg .= "Obs: $mensagem%0A";

$url = "https://wa.me/$numeroRG?text=$msg";

header("Location: $url");
exit;
?>
