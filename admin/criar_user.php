<?php
require_once __DIR__ . '/../app/core/bootstrap.php';
require_admin();

$msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = $_POST['username'];
    $email    = $_POST['email'];
    $senha    = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role     = $_POST['role'];

    $stmt = mysqli_prepare($conexao, "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "ssss", $username, $email, $senha, $role);

    if (mysqli_stmt_execute($stmt)) {
        $msg = "Usuário criado com sucesso!";
    } else {
        $msg = "Erro ao criar usuário.";
    }

    mysqli_stmt_close($stmt);
}
?>

<h2>Criar Usuário</h2>

<p><?php echo $msg; ?></p>

<form method="POST">
    <input type="text" name="username" placeholder="Nome" required><br>
    <input type="email" name="email" placeholder="Email" required><br>
    <input type="password" name="password" placeholder="Senha" required><br>

    <select name="role">
        <option value="admin">Admin</option>
        <option value="vendedor">Vendedor</option>
    </select><br><br>

    <button type="submit">Criar</button>
</form>