<?php
/**
 * Global application configuration.
 * Loaded once at the top of every entry-point script via includes/auth.php.
 */

// Base paths / URL — adjust APP_URL if you deploy under a sub-folder.
define('APP_NAME', 'University Asset Management System');
define('APP_ROOT', dirname(__DIR__));
define('APP_URL', '/asm');

// Session hardening — must run before session_start().
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_httponly', 1);
if (!empty($_SERVER['HTTPS'])) {
    ini_set('session.cookie_secure', 1);
}
ini_set('session.cookie_samesite', 'Lax');

$sessionLifetime = 1440; // fallback default (minutes) before settings table is read
session_set_cookie_params($sessionLifetime * 60);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Africa/Mogadishu');

error_reporting(E_ALL);
ini_set('display_errors', '0'); // never leak errors to the browser in a graded/demo build
ini_set('log_errors', '1');
ini_set('error_log', APP_ROOT . '/storage/php-error.log');
