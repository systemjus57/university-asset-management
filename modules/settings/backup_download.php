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

echo "-- University Asset Management System — database backup\n";
echo "-- Generated: " . date('Y-m-d H:i:s') . "\n";
echo "SET FOREIGN_KEY_CHECKS=0;\n\n";

$tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);

foreach ($tables as $table) {
    $createStmt = $pdo->query('SHOW CREATE TABLE `' . $table . '`')->fetch();
    echo "DROP TABLE IF EXISTS `$table`;\n";
    echo $createStmt['Create Table'] . ";\n\n";

    $rowCount = (int) $pdo->query('SELECT COUNT(*) FROM `' . $table . '`')->fetchColumn();
    if ($rowCount === 0) {
        continue;
    }

    $rowsStmt = $pdo->query('SELECT * FROM `' . $table . '`');
    $columns = null;
    while ($row = $rowsStmt->fetch(PDO::FETCH_ASSOC)) {
        if ($columns === null) {
            $columns = array_keys($row);
        }
        $values = array_map(function ($v) use ($pdo) {
            return $v === null ? 'NULL' : $pdo->quote((string) $v);
        }, array_values($row));
        echo "INSERT INTO `$table` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $values) . ");\n";
    }
    echo "\n";
}

echo "SET FOREIGN_KEY_CHECKS=1;\n";

logActivity($pdo, $_SESSION['user_id'], 'Download Backup', 'settings', "Generated database backup: $filename.");
exit;
