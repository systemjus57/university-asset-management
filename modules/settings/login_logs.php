<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requireRole([ROLE_ADMIN]);

$status = $_GET['status'] ?? '';
$page    = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 25;
$offset  = ($page - 1) * $perPage;

$where  = [];
$params = [];
if ($status !== '') {
    $where[] = 'll.status = :status';
    $params['status'] = $status;
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM login_logs ll $whereSql");
$countStmt->execute($params);
$totalPages = max(1, (int) ceil($countStmt->fetchColumn() / $perPage));

$sql = "SELECT ll.*, u.name AS user_name FROM login_logs ll
        LEFT JOIN users u ON u.user_id = ll.user_id
        $whereSql ORDER BY ll.created_at DESC LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) { $stmt->bindValue(':' . $k, $v); }
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$logs = $stmt->fetchAll();

$activeSettingsTab = 'login_logs';
$pageTitle  = 'Settings — Login Logs';
$activeMenu = 'settings';
include __DIR__ . '/../../includes/layout/header.php';
include __DIR__ . '/_subnav.php';
?>

<form method="get" class="filter-bar">
    <div class="form-group">
        <label for="status">Status</label>
        <select id="status" name="status" onchange="this.form.submit()">
            <option value="">All</option>
            <option value="success" <?= $status === 'success' ? 'selected' : '' ?>>Success</option>
            <option value="failed" <?= $status === 'failed' ? 'selected' : '' ?>>Failed</option>
        </select>
    </div>
</form>

<div class="table-wrap">
<table>
    <thead><tr><th>User</th><th>Email Attempted</th><th>Status</th><th>IP Address</th><th>User Agent</th><th>When</th></tr></thead>
    <tbody>
    <?php if (!$logs): ?><tr class="empty-row"><td colspan="6">No login attempts recorded yet.</td></tr><?php endif; ?>
    <?php foreach ($logs as $l): ?>
        <tr>
            <td><?= e($l['user_name'] ?? '—') ?></td>
            <td><?= e($l['email_attempted'] ?? '—') ?></td>
            <td><?= statusBadge($l['status']) ?></td>
            <td><?= e($l['ip_address'] ?? '—') ?></td>
            <td style="max-width:260px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?= e($l['user_agent'] ?? '—') ?></td>
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
