<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requireRole([ROLE_ADMIN]);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(APP_URL . '/modules/settings/backup.php');
}
requireCsrf();

if (empty($_FILES['backup_file']['name']) || $_FILES['backup_file']['error'] !== UPLOAD_ERR_OK) {
    flash('error', 'Please choose a valid .sql backup file to restore.');
    redirect(APP_URL . '/modules/settings/backup.php');
}

$ext = strtolower(pathinfo($_FILES['backup_file']['name'], PATHINFO_EXTENSION));
if ($ext !== 'sql') {
    flash('error', 'Only .sql backup files can be restored.');
    redirect(APP_URL . '/modules/settings/backup.php');
}
if ($_FILES['backup_file']['size'] > 50 * 1024 * 1024) {
    flash('error', 'Backup file is too large (max 50MB).');
    redirect(APP_URL . '/modules/settings/backup.php');
}

$sql = file_get_contents($_FILES['backup_file']['tmp_name']);
if ($sql === false || trim($sql) === '') {
    flash('error', 'Could not read the uploaded backup file.');
    redirect(APP_URL . '/modules/settings/backup.php');
}

try {
    $pdo->exec($sql);
    logActivity($pdo, $_SESSION['user_id'], 'Restore Backup', 'settings', 'Restored the database from an uploaded backup file.');
    flash('success', 'Database restored successfully from backup.');
} catch (PDOException $e) {
    flash('error', 'Restore failed: ' . $e->getMessage());
}

redirect(APP_URL . '/modules/settings/backup.php');
