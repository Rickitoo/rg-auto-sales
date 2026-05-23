<?php
require_once __DIR__ . '/../../app/core/bootstrap.php';

echo password_hash("123456", PASSWORD_DEFAULT);
// SELECT * FROM users WHERE email=?
