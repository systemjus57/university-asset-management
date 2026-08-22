<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requireLogin();

$canEdit = hasRole([ROLE_ADMIN, ROLE_OFFICER]);
$isHead  = hasRole([ROLE_HEAD]);

$where  = [];
$params = [];
if ($isHead) {
    $where[] = 'a.department_id = :dept_lock';
    $params['dept_lock'] = $_SESSION['department_id'];
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$sql = "SELECT au.*, a.name AS asset_name, a.department_id, u.name AS audited_by_name
        FROM asset_audits au
        JOIN assets a ON a.asset_id = au.asset_id
        LEFT JOIN users u ON u.user_id = au.audited_by
        $whereSql
        ORDER BY au.audit_date DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$pageTitle  = 'Asset Audits';
$activeMenu = 'audits';
include __DIR__ . '/../../includes/layout/header.php';
?>
<div class="card-header">
    <div></div>
    <?php if ($canEdit): ?>
        <a href="<?= APP_URL ?>/modules/audits/add.php" class="btn btn-primary">+ Record Audit</a>
    <?php endif; ?>
</div>

<div class="table-wrap">
<table id="auditsTable">
    <thead>
        <tr>
            <th data-sort>Asset</th>
            <th data-sort>Audit Date</th>
            <th data-sort>Expected</th>
            <th data-sort>Actual</th>
            <th data-sort>Missing</th>
            <th data-sort>Damaged</th>
            <th data-sort>Result</th>
            <th class="no-sort">Remarks</th>
            <th data-sort>Audited By</th>
        </tr>
    </thead>
    <tbody>
    <?php if (!$rows): ?><tr class="empty-row"><td colspan="9">No audit records found.</td></tr><?php endif; ?>
    <?php foreach ($rows as $r): ?>
        <tr>
            <td><a href="<?= APP_URL ?>/modules/assets/view.php?id=<?= (int) $r['asset_id'] ?>"><?= e($r['asset_name']) ?></a></td>
            <td><?= formatDate($r['audit_date']) ?></td>
            <td><?= $r['expected_quantity'] !== null ? (int) $r['expected_quantity'] : '—' ?></td>
            <td><?= $r['actual_quantity'] !== null ? (int) $r['actual_quantity'] : '—' ?></td>
            <td><?= $r['missing_quantity'] !== null ? (int) $r['missing_quantity'] : '—' ?></td>
            <td><?= $r['damaged_quantity'] !== null ? (int) $r['damaged_quantity'] : '—' ?></td>
            <td><?= statusBadge($r['result']) ?></td>
            <td><?= e($r['remarks'] ?? '—') ?></td>
            <td><?= e($r['audited_by_name'] ?? '—') ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php include __DIR__ . '/../../includes/layout/footer.php'; ?>
