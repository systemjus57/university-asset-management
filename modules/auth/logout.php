<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

if (isLoggedIn()) {
    logActivity($pdo, $_SESSION['user_id'], 'Logout', 'auth', ($_SESSION['name'] ?? '') . ' logged out.');
}

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}
session_destroy();

header('Location: ' . APP_URL . '/modules/auth/login.php');
exit;
