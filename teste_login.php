<?php
require_once __DIR__ . '/app/core/bootstrap.php';

$login = 'rickgani2012@gmail.com';

$user = auth_find_user_by_login($conexao, $login);

echo '<pre>';
var_dump($user);
echo '</pre>';