<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requireLogin();

$canRequest = hasRole([ROLE_ADMIN, ROLE_OFFICER]);
$canApprove = hasRole([ROLE_ADMIN, ROLE_TOPMGMT]);
$isHead     = hasRole([ROLE_HEAD]);

$status = $_GET['status'] ?? '';
$where  = [];
$params = [];
if ($isHead) {
    $where[] = 'a.department_id = :dept_lock';
    $params['dept_lock'] = $_SESSION['department_id'];
}
if ($status !== '') {
    $where[] = 'ds.status = :status';
    $params['status'] = $status;
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$sql = "SELECT ds.*, a.name AS asset_name, u.name AS requested_by_name, ua.name AS approved_by_name
        FROM asset_disposals ds
        JOIN assets a ON a.asset_id = ds.asset_id
        LEFT JOIN users u ON u.user_id = ds.requested_by
        LEFT JOIN users ua ON ua.user_id = ds.approved_by
        $whereSql
        ORDER BY ds.request_date DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$pageTitle  = 'Asset Disposals';
$activeMenu = 'disposals';
include __DIR__ . '/../../includes/layout/header.php';
?>
<div class="card-header">
    <form method="get" class="filter-bar" style="margin:0; box-shadow:none; border:none; padding:0;">
        <div class="form-group">
            <label for="status">Status</label>
            <select id="status" name="status" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="approved" <?= $status === 'approved' ? 'selected' : '' ?>>Approved</option>
                <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>Rejected</option>
            </select>
        </div>
    </form>
    <?php if ($canRequest): ?>
        <a href="<?= APP_URL ?>/modules/disposals/add.php" class="btn btn-primary">+ Request Disposal</a>
    <?php endif; ?>
</div>

<div class="table-wrap">
<table id="disposalsTable">
    <thead>
        <tr>
            <th data-sort>Asset</th>
            <th data-sort>Qty</th>
            <th data-sort>Requested</th>
            <th data-sort>Method</th>
            <th class="no-sort">Reason</th>
            <th data-sort>Status</th>
            <th data-sort>Requested By</th>
            <th data-sort>Approved By</th>
            <th data-sort>Disposal Date</th>
            <th class="no-sort">Actions</th>
        </tr>
    </thead>
    <tbody>
    <?php if (!$rows): ?><tr class="empty-row"><td colspan="10">No disposal records found.</td></tr><?php endif; ?>
    <?php foreach ($rows as $r): ?>
        <tr>
            <td><a href="<?= APP_URL ?>/modules/assets/view.php?id=<?= (int) $r['asset_id'] ?>"><?= e($r['asset_name']) ?></a></td>
            <td><?= (int) $r['quantity'] ?></td>
            <td><?= formatDate($r['request_date']) ?></td>
            <td><?= e(ucfirst($r['method'])) ?></td>
            <td><?= e($r['reason']) ?></td>
            <td><?= statusBadge($r['status']) ?></td>
            <td><?= e($r['requested_by_name'] ?? '—') ?></td>
            <td><?= e($r['approved_by_name'] ?? '—') ?></td>
            <td><?= formatDate($r['disposal_date']) ?></td>
            <td>
                <?php if ($canApprove && $r['status'] === 'pending'): ?>
                    <a class="btn btn-sm btn-outline" href="<?= APP_URL ?>/modules/disposals/approve.php?id=<?= (int) $r['disposal_id'] ?>">Review</a>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php include __DIR__ . '/../../includes/layout/footer.php'; ?>
