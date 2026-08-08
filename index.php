<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (isLoggedIn()) {
    redirect(APP_URL . '/modules/dashboard/index.php');
}
redirect(APP_URL . '/modules/auth/login.php');
