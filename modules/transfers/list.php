<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requireLogin();

$canEdit = hasRole([ROLE_ADMIN, ROLE_OFFICER]);
$isHead  = hasRole([ROLE_HEAD]);

$where  = [];
$params = [];
if ($isHead) {
    $where[] = '(t.from_department_id = :dept_lock OR t.to_department_id = :dept_lock2)';
    $params['dept_lock']  = $_SESSION['department_id'];
    $params['dept_lock2'] = $_SESSION['department_id'];
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$sql = "SELECT t.*, a.name AS asset_name, df.department_name AS from_dept, dt.department_name AS to_dept, u.name AS handled_by_name
        FROM asset_transfers t
        JOIN assets a ON a.asset_id = t.asset_id
        LEFT JOIN departments df ON df.department_id = t.from_department_id
        LEFT JOIN departments dt ON dt.department_id = t.to_department_id
        LEFT JOIN users u ON u.user_id = t.handled_by
        $whereSql
        ORDER BY t.transfer_date DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$pageTitle  = 'Asset Transfers';
$activeMenu = 'transfers';
include __DIR__ . '/../../includes/layout/header.php';
?>
<div class="card-header">
    <div></div>
    <?php if ($canEdit): ?>
        <a href="<?= APP_URL ?>/modules/transfers/add.php" class="btn btn-primary">+ Transfer Asset</a>
    <?php endif; ?>
</div>

<div class="table-wrap">
<table id="transfersTable">
    <thead>
        <tr>
            <th data-sort>Asset</th>
            <th data-sort>Qty</th>
            <th data-sort>From</th>
            <th data-sort>To</th>
            <th data-sort>Transfer Date</th>
            <th data-sort>Reason</th>
            <th data-sort>Handled By</th>
        </tr>
    </thead>
    <tbody>
    <?php if (!$rows): ?><tr class="empty-row"><td colspan="7">No transfer records found.</td></tr><?php endif; ?>
    <?php foreach ($rows as $r): ?>
        <tr>
            <td><a href="<?= APP_URL ?>/modules/assets/view.php?id=<?= (int) $r['asset_id'] ?>"><?= e($r['asset_name']) ?></a></td>
            <td><?= (int) $r['quantity'] ?></td>
            <td><?= e($r['from_dept'] ?? '—') ?></td>
            <td><?= e($r['to_dept'] ?? '—') ?></td>
            <td><?= formatDate($r['transfer_date']) ?></td>
            <td><?= e($r['reason'] ?? '—') ?></td>
            <td><?= e($r['handled_by_name'] ?? '—') ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php include __DIR__ . '/../../includes/layout/footer.php'; ?>
