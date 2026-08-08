<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requireRole([ROLE_ADMIN]);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(APP_URL . '/modules/users/list.php');
}
requireCsrf();

$userId = (int) ($_POST['user_id'] ?? 0);

if ($userId === (int) $_SESSION['user_id']) {
    flash('error', 'You cannot deactivate your own account.');
    redirect(APP_URL . '/modules/users/list.php');
}

$stmt = $pdo->prepare('SELECT status FROM users WHERE user_id = :id');
$stmt->execute(['id' => $userId]);
$current = $stmt->fetchColumn();

if ($current !== false) {
    $newStatus = $current === 'active' ? 'inactive' : 'active';
    $pdo->prepare('UPDATE users SET status = :status WHERE user_id = :id')->execute(['status' => $newStatus, 'id' => $userId]);
    logActivity($pdo, $_SESSION['user_id'], 'Toggle User Status', 'users', "User #$userId set to $newStatus.");
    flash('success', "User account $newStatus.");
} else {
    flash('error', 'User not found.');
}

redirect(APP_URL . '/modules/users/list.php');
