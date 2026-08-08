<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requireRole([ROLE_ADMIN]);

$mysqlVersion = $pdo->query('SELECT VERSION()')->fetchColumn();
$diskFree  = @disk_free_space(APP_ROOT);
$diskTotal = @disk_total_space(APP_ROOT);
$diskUsedPct = ($diskFree !== false && $diskTotal) ? round((1 - $diskFree / $diskTotal) * 100, 1) : null;

function humanSize($bytes)
{
    if ($bytes === false || $bytes === null) {
        return 'Unknown';
    }
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 2) . ' ' . $units[$i];
}

$activeSettingsTab = 'system';
$pageTitle  = 'Settings — System Info';
$activeMenu = 'settings';
include __DIR__ . '/../../includes/layout/header.php';
include __DIR__ . '/_subnav.php';
?>

<div class="card" style="max-width:700px;">
    <h3>Environment</h3>
    <div class="detail-grid">
        <div class="detail-item"><div class="detail-label">PHP Version</div><div class="detail-value"><?= e(PHP_VERSION) ?></div></div>
        <div class="detail-item"><div class="detail-label">MySQL Version</div><div class="detail-value"><?= e($mysqlVersion) ?></div></div>
        <div class="detail-item"><div class="detail-label">Server Software</div><div class="detail-value"><?= e($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') ?></div></div>
        <div class="detail-item"><div class="detail-label">Operating System</div><div class="detail-value"><?= e(PHP_OS) ?></div></div>
        <div class="detail-item"><div class="detail-label">Server Time</div><div class="detail-value"><?= date('d M Y, H:i') ?></div></div>
        <div class="detail-item"><div class="detail-label">Timezone</div><div class="detail-value"><?= e(date_default_timezone_get()) ?></div></div>
    </div>
</div>

<div class="card mt-2" style="max-width:700px;">
    <h3>Storage</h3>
    <div class="detail-grid">
        <div class="detail-item"><div class="detail-label">Free Disk Space</div><div class="detail-value"><?= humanSize($diskFree) ?></div></div>
        <div class="detail-item"><div class="detail-label">Total Disk Space</div><div class="detail-value"><?= humanSize($diskTotal) ?></div></div>
        <div class="detail-item"><div class="detail-label">Disk Used</div><div class="detail-value"><?= $diskUsedPct !== null ? $diskUsedPct . '%' : 'Unknown' ?></div></div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/layout/footer.php'; ?>
