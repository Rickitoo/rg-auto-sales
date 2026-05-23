<?php
require_once __DIR__ . '/app/core/bootstrap.php';

if (!is_logged_in()) {
    redirect_to('auth/login.php');
}

redirect_to(is_admin() ? 'admin/dashboard.php' : 'public/index.php');
