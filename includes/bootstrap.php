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

// Maintenance mode gate — everyone except Admin is blocked while it's on.
if (isLoggedIn() && ($_SESSION['role_name'] ?? '') !== ROLE_ADMIN) {
    if (getSetting($pdo, 'maintenance_mode', '0') === '1') {
        http_response_code(503);
        die('<h1 style="font-family:sans-serif;color:#123524;">System is under maintenance. Please check back shortly.</h1>');
    }
}
