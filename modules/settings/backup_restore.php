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

// Basic sanity check — this endpoint deliberately runs arbitrary SQL (that's
// what a restore is), so it's restricted to Admin only; this just rejects an
// obviously-wrong upload (e.g. an image or text file renamed to .sql) before
// touching the live database.
if (!preg_match('/\b(CREATE TABLE|INSERT INTO)\b/i', $sql)) {
    flash('error', 'This file does not look like a valid database backup.');
    redirect(APP_URL . '/modules/settings/backup.php');
}

// Safety backup — taken immediately before the destructive restore, so a bad
// upload can always be undone from storage/backups/, mirroring the same
// pure-PHP dump format used by Settings > Backup > Download.
$safetyDir = APP_ROOT . '/storage/backups';
if (!is_dir($safetyDir)) {
    mkdir($safetyDir, 0755, true);
}
$safetyFile = $safetyDir . '/pre_restore_' . date('Ymd_His') . '.sql';
file_put_contents($safetyFile, generateDatabaseDump($pdo));

try {
    $pdo->exec($sql);
    logActivity($pdo, $_SESSION['user_id'], 'Restore Backup', 'settings', 'Restored the database from an uploaded backup file. Safety backup: ' . basename($safetyFile) . '.');
    flash('success', 'Database restored successfully from backup. A safety backup of the previous state was saved as ' . basename($safetyFile) . '.');
} catch (PDOException $e) {
    logActivity($pdo, $_SESSION['user_id'], 'Restore Backup Failed', 'settings', 'Restore failed: ' . $e->getMessage() . '. Safety backup: ' . basename($safetyFile) . '.');
    flash('error', 'Restore failed: ' . $e->getMessage() . ' The database may be partially restored — a safety backup from just before this attempt was saved as ' . basename($safetyFile) . '.');
}

redirect(APP_URL . '/modules/settings/backup.php');
