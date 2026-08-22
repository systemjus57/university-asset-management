<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requireLogin();

$canManage = hasRole([ROLE_ADMIN, ROLE_OFFICER]);
$canReport = hasRole([ROLE_ADMIN, ROLE_OFFICER, ROLE_HEAD]);
$isHead    = hasRole([ROLE_HEAD]);

$status = $_GET['status'] ?? '';

$where  = [];
$params = [];
if ($isHead) {
    $where[] = 'a.department_id = :dept_lock';
    $params['dept_lock'] = $_SESSION['department_id'];
}
if ($status !== '') {
    $where[] = 'm.status = :status';
    $params['status'] = $status;
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$sql = "SELECT m.*, a.name AS asset_name, a.department_id, u.name AS reported_by_name
        FROM asset_maintenance m
        JOIN assets a ON a.asset_id = m.asset_id
        LEFT JOIN users u ON u.user_id = m.reported_by
        $whereSql
        ORDER BY m.reported_date DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$pageTitle  = 'Maintenance';
$activeMenu = 'maintenance';
include __DIR__ . '/../../includes/layout/header.php';
?>
<div class="card-header">
    <form method="get" class="filter-bar" style="margin:0; box-shadow:none; border:none; padding:0;">
        <div class="form-group">
            <label for="status">Status</label>
            <select id="status" name="status" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="in_progress" <?= $status === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Completed</option>
            </select>
        </div>
    </form>
    <?php if ($canReport): ?>
        <a href="<?= APP_URL ?>/modules/maintenance/add.php" class="btn btn-primary">+ Report Issue</a>
    <?php endif; ?>
</div>

<div class="table-wrap">
<table id="maintenanceTable">
    <thead>
        <tr>
            <th data-sort>Asset</th>
            <th data-sort>Qty</th>
            <th class="no-sort">Issue</th>
            <th data-sort>Reported</th>
            <th data-sort>Reported By</th>
            <th data-sort>Status</th>
            <th data-sort>Completed</th>
            <th data-sort>Cost</th>
            <th class="no-sort">Actions</th>
        </tr>
    </thead>
    <tbody>
    <?php if (!$rows): ?><tr class="empty-row"><td colspan="9">No maintenance records found.</td></tr><?php endif; ?>
    <?php foreach ($rows as $r): ?>
        <tr>
            <td><a href="<?= APP_URL ?>/modules/assets/view.php?id=<?= (int) $r['asset_id'] ?>"><?= e($r['asset_name']) ?></a></td>
            <td><?= (int) $r['quantity'] ?></td>
            <td><?= e($r['issue_description']) ?></td>
            <td><?= formatDate($r['reported_date']) ?></td>
            <td><?= e($r['reported_by_name'] ?? '—') ?></td>
            <td><?= statusBadge($r['status']) ?></td>
            <td><?= formatDate($r['completed_date']) ?></td>
            <td><?= formatMoney($r['cost']) ?></td>
            <td>
                <?php if ($canManage): ?>
                    <a class="btn btn-sm btn-outline" href="<?= APP_URL ?>/modules/maintenance/update.php?id=<?= (int) $r['maintenance_id'] ?>">Update</a>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php include __DIR__ . '/../../includes/layout/footer.php'; ?>
