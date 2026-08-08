<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requireRole([ROLE_ADMIN]);

$dbSizeRow = $pdo->query(
    "SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
     FROM information_schema.TABLES WHERE table_schema = DATABASE()"
)->fetch();
$dbSizeMb = $dbSizeRow['size_mb'] ?? 0;

$tableCount = (int) $pdo->query(
    "SELECT COUNT(*) FROM information_schema.TABLES WHERE table_schema = DATABASE()"
)->fetchColumn();

$activeSettingsTab = 'backup';
$pageTitle  = 'Settings — Backup & Restore';
$activeMenu = 'settings';
include __DIR__ . '/../../includes/layout/header.php';
include __DIR__ . '/_subnav.php';
?>

<div class="stat-grid">
    <div class="stat-card"><div class="stat-label">Database Size</div><div class="stat-value"><?= e($dbSizeMb) ?> MB</div></div>
    <div class="stat-card accent"><div class="stat-label">Tables</div><div class="stat-value"><?= $tableCount ?></div></div>
</div>

<div class="card mt-2" style="max-width:700px;">
    <h3>Download Full Backup</h3>
    <p class="text-muted">Generates a complete .sql dump of the database (schema + data) that you can store safely or use to restore the system later.</p>
    <a href="<?= APP_URL ?>/modules/settings/backup_download.php" class="btn btn-primary">Download Backup (.sql)</a>
</div>

<div class="card mt-2" style="max-width:700px;">
    <h3 style="color:var(--color-danger);">Restore From Backup</h3>
    <p class="text-muted">Uploading a backup will run its SQL statements against the live database, overwriting existing tables with matching names. This action is irreversible — make sure you have a current backup before proceeding.</p>
    <form method="post" action="<?= APP_URL ?>/modules/settings/backup_restore.php" enctype="multipart/form-data"
          data-confirm="This will overwrite existing data with the contents of the uploaded backup. This cannot be undone. Continue?">
        <?= csrfField() ?>
        <div class="form-group">
            <label for="backup_file">Backup File (.sql)</label>
            <input type="file" id="backup_file" name="backup_file" accept=".sql" required>
        </div>
        <button type="submit" class="btn btn-danger">Restore Database</button>
    </form>
</div>

<?php include __DIR__ . '/../../includes/layout/footer.php'; ?>
