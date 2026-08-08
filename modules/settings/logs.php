<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requireRole([ROLE_ADMIN]);

$module = $_GET['module'] ?? '';
$page    = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 25;
$offset  = ($page - 1) * $perPage;

$where  = [];
$params = [];
if ($module !== '') {
    $where[] = 'al.module = :module';
    $params['module'] = $module;
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM activity_logs al $whereSql");
$countStmt->execute($params);
$totalPages = max(1, (int) ceil($countStmt->fetchColumn() / $perPage));

$sql = "SELECT al.*, u.name AS user_name FROM activity_logs al
        LEFT JOIN users u ON u.user_id = al.user_id
        $whereSql ORDER BY al.created_at DESC LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) { $stmt->bindValue(':' . $k, $v); }
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$logs = $stmt->fetchAll();

$modules = $pdo->query('SELECT DISTINCT module FROM activity_logs WHERE module IS NOT NULL ORDER BY module')->fetchAll(PDO::FETCH_COLUMN);

$activeSettingsTab = 'logs';
$pageTitle  = 'Settings — Activity Logs';
$activeMenu = 'settings';
include __DIR__ . '/../../includes/layout/header.php';
include __DIR__ . '/_subnav.php';
?>

<form method="get" class="filter-bar">
    <div class="form-group">
        <label for="module">Module</label>
        <select id="module" name="module" onchange="this.form.submit()">
            <option value="">All Modules</option>
            <?php foreach ($modules as $m): ?>
                <option value="<?= e($m) ?>" <?= $module === $m ? 'selected' : '' ?>><?= e(ucfirst($m)) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</form>

<div class="table-wrap">
<table>
    <thead><tr><th>Action</th><th>Module</th><th>Description</th><th>User</th><th>IP Address</th><th>When</th></tr></thead>
    <tbody>
    <?php if (!$logs): ?><tr class="empty-row"><td colspan="6">No activity logged yet.</td></tr><?php endif; ?>
    <?php foreach ($logs as $l): ?>
        <tr>
            <td><?= e($l['action']) ?></td>
            <td><?= e(ucfirst($l['module'] ?? '—')) ?></td>
            <td><?= e($l['description'] ?? '—') ?></td>
            <td><?= e($l['user_name'] ?? 'System') ?></td>
            <td><?= e($l['ip_address'] ?? '—') ?></td>
            <td><?= formatDateTime($l['created_at']) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>

<?php if ($totalPages > 1): ?>
<div class="pagination">
    <?php for ($p = 1; $p <= $totalPages; $p++): $qs = $_GET; $qs['page'] = $p; ?>
        <?php if ($p === $page): ?><span class="current"><?= $p ?></span>
        <?php else: ?><a href="?<?= http_build_query($qs) ?>"><?= $p ?></a><?php endif; ?>
    <?php endfor; ?>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../../includes/layout/footer.php'; ?>
