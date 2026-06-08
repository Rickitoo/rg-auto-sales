<?php
require_once __DIR__ . '/../app/core/bootstrap.php';


// ======================
// FUNÇÃO LIMPAR
// ======================

// ======================
// VALIDAR MÉTODO
// ======================

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    redirect_to('public/test_drive.php');
    exit;
}

public_require_form_security('test_drive', 5, 300);

// ======================
// RECEBER DADOS
// ======================

$nome      = clean($_POST['nome'] ?? '');
$email     = clean($_POST['email'] ?? '');
$telefone  = clean($_POST['telefone'] ?? '');
$sexo      = clean($_POST['sexo'] ?? '');

$data = clean($_POST['data_test_drive'] ?? '');
$hora = clean($_POST['hora_test_drive'] ?? '');

$marca     = clean($_POST['marca'] ?? '');
$modelo    = clean($_POST['modelo'] ?? '');
$ano       = (int)($_POST['ano'] ?? 0);

$mensagem  = clean($_POST['mensagem'] ?? '');

// ======================
// VALIDAÇÕES
// ======================

if (
    $nome === '' ||
    $telefone === '' ||
    $sexo === '' ||
    $data === '' ||
    $hora === '' ||
    $marca === '' ||
    $modelo === '' ||
    $ano <= 0
) {
    die("Preencha todos os campos obrigatórios.");
}

if (!public_valid_phone($telefone)) {
    die("Telefone inválido.");
}

if (!public_valid_email($email, false)) {
    die("Email inválido.");
}

if ($data < date('Y-m-d')) {
    die("Data inválida.");
}

// ======================
// CONFIGURAÇÕES CRM
// ======================

$statusCliente = "NOVO";

// ======================
// INSERIR LEAD
// ======================

$stmt = mysqli_prepare($conexao, "
    INSERT INTO clientes (
        nome,
        telefone,
        email,
        sexo,
        data,
        hora,
        marca,
        modelo,
        ano,
        mensagem,
        status
    )
    VALUES (
        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
    )
");

if (!$stmt) {
    http_response_code(500);
    die("Não foi possível guardar o agendamento neste momento. Tente novamente mais tarde.");
}

mysqli_stmt_bind_param(
    $stmt,
    "ssssssssiss",
    $nome,
    $telefone,
    $email,
    $sexo,
    $data,
    $hora,
    $marca,
    $modelo,
    $ano,
    $mensagem,
    $statusCliente
);

if (!mysqli_stmt_execute($stmt)) {
    http_response_code(500);
    die("Não foi possível guardar o agendamento neste momento. Tente novamente mais tarde.");
}

$cliente_id = mysqli_insert_id($conexao);

mysqli_stmt_close($stmt);

$lead_id = $cliente_id;
$leadCriado = false;
$tipoLead = 'testdrive';
$origemLead = 'site';
$statusLead = 'novo';
$notasLead = "Agendamento de test drive #$cliente_id. Sexo: $sexo. Data: $data. Hora: $hora.";
$proximoContacto = "$data $hora:00";

$stmtLead = mysqli_prepare($conexao, "
    INSERT INTO leads (
        tipo,
        nome,
        telefone,
        email,
        mensagem,
        marca,
        modelo,
        ano,
        origem,
        status,
        notas,
        proximo_contacto
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

if ($stmtLead) {
    mysqli_stmt_bind_param(
        $stmtLead,
        "sssssssissss",
        $tipoLead,
        $nome,
        $telefone,
        $email,
        $mensagem,
        $marca,
        $modelo,
        $ano,
        $origemLead,
        $statusLead,
        $notasLead,
        $proximoContacto
    );

    if (mysqli_stmt_execute($stmtLead)) {
        $lead_id = mysqli_insert_id($conexao);
        $leadCriado = true;
    }

    mysqli_stmt_close($stmtLead);
}

// ======================
// MENSAGEM AUTOMÁTICA CRM
// ======================

$mensagemSistema = "Lead criado automaticamente via formulário Test Drive.";

$stmt2 = mysqli_prepare($conexao, "
    INSERT INTO mensagens (
        lead_id,
        mensagem,
        tipo
    )
    VALUES (
        ?, ?, 'sistema'
    )
");

if ($leadCriado && $stmt2) {

    mysqli_stmt_bind_param(
        $stmt2,
        "is",
        $lead_id,
        $mensagemSistema
    );

    mysqli_stmt_execute($stmt2);
    mysqli_stmt_close($stmt2);
}

// ======================
// WHATSAPP
// ======================

$numeroRG = "258862934721";

$referencia = $leadCriado ? "LEAD #$lead_id" : "AGENDAMENTO #$cliente_id";

$msg  = "$referencia (Test Drive)\n";
$msg .= "Nome: $nome\n";
$msg .= "Telefone: $telefone\n";
$msg .= "Sexo: $sexo\n";

if ($email !== '') {
    $msg .= "Email: $email\n";
}

$msg .= "Carro: $marca $modelo ($ano)\n";
$msg .= "Data: $data às $hora\n";

if ($mensagem !== '') {
    $msg .= "Obs: $mensagem\n";
}

$url = "https://wa.me/$numeroRG?text=" . urlencode($msg);

// ======================
// REDIRECIONAR
// ======================

header("Location: $url");
exit;
?>
