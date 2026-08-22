<?php
/**
 * Streams a full SQL dump of the database (schema + data) generated in pure
 * PHP via SHOW CREATE TABLE / SELECT — no shell_exec / mysqldump dependency.
 */
require_once __DIR__ . '/../../includes/bootstrap.php';
requireRole([ROLE_ADMIN]);

$filename = 'backup_' . DB_NAME . '_' . date('Ymd_His') . '.sql';

header('Content-Type: application/sql; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

echo generateDatabaseDump($pdo);

logActivity($pdo, $_SESSION['user_id'], 'Download Backup', 'settings', "Generated database backup: $filename.");
exit;
