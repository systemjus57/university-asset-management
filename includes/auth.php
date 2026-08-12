<?php
/**
 * Session / RBAC guards.
 * Every protected page must call requireLogin() and, where relevant,
 * requireRole([...]) BEFORE any output or business logic runs.
 */

define('ROLE_ADMIN', 'Admin');
define('ROLE_OFFICER', 'Asset Officer');
define('ROLE_HEAD', 'Department Head');
define('ROLE_TOPMGMT', 'Top Management');

function isLoggedIn(): bool
{
    return !empty($_SESSION['user_id']);
}

function currentUser(): ?array
{
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'user_id'        => $_SESSION['user_id'],
        'name'            => $_SESSION['name'],
        'email'            => $_SESSION['email'],
        'username'          => $_SESSION['username'] ?? null,
        'role_id'          => $_SESSION['role_id'],
        'role_name'         => $_SESSION['role_name'],
        'department_id'      => $_SESSION['department_id'],
        'department_name'     => $_SESSION['department_name'],
    ];
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '';
        header('Location: ' . APP_URL . '/modules/auth/login.php');
        exit;
    }
}

/**
 * Restrict the current page to a set of role names.
 * Must be called after requireLogin(). Denies with a 403 view rather than
 * a silent redirect, so an unauthorized attempt is visible and logged.
 */
function requireRole(array $allowedRoles): void
{
    requireLogin();
    if (!in_array($_SESSION['role_name'], $allowedRoles, true)) {
        http_response_code(403);
        require APP_ROOT . '/includes/layout/forbidden.php';
        exit;
    }
}

function hasRole(array $roles): bool
{
    return isLoggedIn() && in_array($_SESSION['role_name'], $roles, true);
}

function isAdmin(): bool
{
    return hasRole([ROLE_ADMIN]);
}
