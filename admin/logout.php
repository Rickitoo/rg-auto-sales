<?php
require_once __DIR__ . '/../app/core/bootstrap.php';
require_admin();

logout_user();
redirect_to('public/account.php?logout=1');
