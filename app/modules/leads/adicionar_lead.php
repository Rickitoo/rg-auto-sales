<?php
require_once __DIR__ . '/../../core/bootstrap.php';
require_admin();

if ($_SESSION['user']['role'] !== 'admin') {
    redirect_to('auth/login.php');
    exit();
}

$erro = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nome = trim($_POST['nome'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $carro_id = (int)($_POST['carro_id'] ?? 0);

    if ($nome == "" || $telefone == "") {
        $erro = "Nome e telefone são obrigatórios.";
    } else {

        $stmt = mysqli_prepare($conexao, "
            INSERT INTO leads (nome, telefone, carro_id, status, created_at)
            VALUES (?, ?, ?, 'novo', NOW())
        ");

        mysqli_stmt_bind_param($stmt, "ssi", $nome, $telefone, $carro_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        redirect_to('app/modules/leads/listar_leads.php');
        exit();
    }
}

// carros para seleção
$carros = mysqli_query($conexao, "SELECT id, marca, modelo FROM carros");
?>

<h2>Adicionar Lead</h2>

<?php if ($erro): ?>
<p style="color:red"><?= $erro ?></p>
<?php endif; ?>

<form method="POST">

<label>Nome</label><br>
<input type="text" name="nome" required><br><br>

<label>Telefone</label><br>
<input type="text" name="telefone" required><br><br>

<label>Carro (opcional)</label><br>
<select name="carro_id">
    <option value="0">-- Nenhum --</option>
    <?php while($c = mysqli_fetch_assoc($carros)): ?>
        <option value="<?= $c['id'] ?>">
            <?= $c['marca'] ?> <?= $c['modelo'] ?>
        </option>
    <?php endwhile; ?>
</select>

<br><br>

<button type="submit">Criar Lead</button>

</form>