<?php
/**
 * Single include point for every module entry file:
 *   require_once __DIR__ . '/../../includes/bootstrap.php';
 * Loads config, DB connection, auth guards and shared helpers, in order.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/i18n.php';

setActiveLanguage(getSetting($pdo, 'language', 'en'));

// Session inactivity timeout — makes the Settings > General "Session Timeout
// (minutes)" field actually take effect (it was previously saved to the DB
// but nothing ever read it back). Sliding window: any request resets the
// clock, so only genuinely idle sessions expire.
if (isLoggedIn()) {
    $sessionTimeoutMinutes = max(1, (int) getSetting($pdo, 'session_timeout_minutes', '60'));
    if (!empty($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $sessionTimeoutMinutes * 60) {
        $_SESSION = [];
        session_regenerate_id(true);
        header('Location: ' . APP_URL . '/modules/auth/login.php?expired=1');
        exit;
    }
    $_SESSION['last_activity'] = time();
}

// Maintenance mode gate — everyone except Admin is blocked while it's on.
if (isLoggedIn() && ($_SESSION['role_name'] ?? '') !== ROLE_ADMIN) {
    if (getSetting($pdo, 'maintenance_mode', '0') === '1') {
        http_response_code(503);
        die('<h1 style="font-family:sans-serif;color:#123524;">System is under maintenance. Please check back shortly.</h1>');
    }
}
