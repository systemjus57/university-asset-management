<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(APP_URL . '/modules/dashboard/index.php');
}
requireCsrf();

if (!empty($_POST['mark_all'])) {
    markAllNotificationsRead($pdo, (int) $_SESSION['user_id']);
} else {
    $notificationId = (int) ($_POST['notification_id'] ?? 0);
    if ($notificationId > 0) {
        markNotificationRead($pdo, $notificationId, (int) $_SESSION['user_id']);
    }
}

$back = $_POST['back'] ?? '';
$safeBack = ($back !== '' && str_starts_with($back, APP_URL . '/')) ? $back : (APP_URL . '/modules/dashboard/index.php');
redirect($safeBack);
