<?php
// salvar_testdrive.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

include("conexao.php");

function clean($s){ return trim((string)$s); }

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  header("Location: test_drive.html");
  exit;
}

$nome     = clean($_POST['nome'] ?? '');
$telefone = clean($_POST['telefone'] ?? '');
$email    = clean($_POST['email'] ?? '');
$marca    = clean($_POST['marca'] ?? '');
$modelo   = clean($_POST['modelo'] ?? '');
$ano      = (int)($_POST['ano'] ?? 0);
$mensagem = clean($_POST['mensagem'] ?? '');
$origem   = clean($_POST['origem'] ?? 'site'); // opcional (podes passar hidden no form)

if ($nome === '' || $telefone === '' || $marca === '' || $modelo === '' || $ano <= 0) {
  die("Preencha os campos obrigatórios.");
}

// 1) grava lead no banco
$stmt = mysqli_prepare($conexao, "
  INSERT INTO leads (tipo, nome, telefone, email, mensagem, marca, modelo, ano, origem, status)
  VALUES ('testdrive', ?, ?, ?, ?, ?, ?, ?, ?, 'novo')
");

if (!$stmt) die("Erro prepare: " . mysqli_error($conexao));

mysqli_stmt_bind_param($stmt, "ssssssis", $nome, $telefone, $email, $mensagem, $marca, $modelo, $ano, $origem);

if (!mysqli_stmt_execute($stmt)) {
  die("Erro ao salvar lead: " . mysqli_stmt_error($stmt));
}

$lead_id = mysqli_insert_id($conexao);
mysqli_stmt_close($stmt);

// 2) monta mensagem para WhatsApp (mantém tua operação rápida)
$numeroRG = "258862934721"; // <-- número oficial da RG (sem +)
$txt = "LEAD #$lead_id (Test Drive)%0A";
$txt .= "Nome: $nome%0A";
$txt .= "Tel: $telefone%0A";
if ($email !== '') $txt .= "Email: $email%0A";
$txt .= "Carro: $marca $modelo ($ano)%0A";
if ($mensagem !== '') $txt .= "Msg: $mensagem%0A";
$txt .= "Origem: $origem";

header("Location: https://wa.me/$258862934721?text=$txt");
exit;