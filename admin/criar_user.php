<?php
require_once __DIR__ . '/../app/core/bootstrap.php';
require_admin();

$msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (
        !is_string($csrfToken) ||
        empty($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $csrfToken)
    ) {
        http_response_code(403);
        exit('CSRF invalido.');
    }

    $username = trim((string)($_POST['username'] ?? ''));
    $email    = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $role     = (string)($_POST['role'] ?? '');

    if ($username === '' || $email === '' || $password === '' || !in_array($role, ['admin', 'vendedor'], true)) {
        $msg = "Preencha os campos obrigatorios.";
    } else {
        $senha = password_hash($password, PASSWORD_DEFAULT);

        $stmt = mysqli_prepare($conexao, "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "ssss", $username, $email, $senha, $role);

        if (mysqli_stmt_execute($stmt)) {
            $msg = "Usuário criado com sucesso!";
        } else {
            $msg = "Erro ao criar usuário.";
        }

        mysqli_stmt_close($stmt);
    }
}
?>

<h2>Criar Usuário</h2>

<p><?php echo h($msg); ?></p>

<form method="POST">
    <?= csrf_input() ?>
    <input type="text" name="username" placeholder="Nome" required><br>
    <input type="email" name="email" placeholder="Email" required><br>
    <input type="password" name="password" placeholder="Senha" required><br>

    <select name="role">
        <option value="admin">Admin</option>
        <option value="vendedor">Vendedor</option>
    </select><br><br>

    <button type="submit">Criar</button>
</form>
