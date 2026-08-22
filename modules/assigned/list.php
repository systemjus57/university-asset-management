<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requireLogin();

$canEdit = hasRole([ROLE_ADMIN, ROLE_OFFICER]);
$isHead  = hasRole([ROLE_HEAD]);

$departmentId = $_GET['department_id'] ?? '';
$userId       = $_GET['user_id'] ?? '';
$status       = $_GET['status'] ?? '';

$where  = [];
$params = [];

if ($isHead) {
    $where[] = 'aa.assigned_to = :dept_lock';
    $params['dept_lock'] = $_SESSION['department_id'];
} elseif ($departmentId !== '') {
    $where[] = 'aa.assigned_to = :department_id';
    $params['department_id'] = $departmentId;
}
if ($userId !== '') {
    $where[] = 'aa.assigned_user_id = :user_id';
    $params['user_id'] = $userId;
}
if ($status !== '') {
    $where[] = 'aa.status = :status';
    $params['status'] = $status;
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$sql = "SELECT aa.*, a.name AS asset_name, d.department_name, u.name AS custodian_name, ub.name AS assigned_by_name
        FROM asset_assigned aa
        JOIN assets a ON a.asset_id = aa.asset_id
        LEFT JOIN departments d ON d.department_id = aa.assigned_to
        LEFT JOIN users u ON u.user_id = aa.assigned_user_id
        LEFT JOIN users ub ON ub.user_id = aa.assigned_by
        $whereSql
        ORDER BY aa.assigned_date DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$departments = $pdo->query('SELECT department_id, department_name FROM departments ORDER BY department_name')->fetchAll();
$users       = $pdo->query('SELECT user_id, name FROM users ORDER BY name')->fetchAll();

$pageTitle  = 'Asset Allocations';
$activeMenu = 'assigned';
include __DIR__ . '/../../includes/layout/header.php';
?>

<div class="card-header">
    <div></div>
    <?php if ($canEdit): ?>
        <a href="<?= APP_URL ?>/modules/assigned/add.php" class="btn btn-primary">+ Assign Asset</a>
    <?php endif; ?>
</div>

<form method="get" class="filter-bar">
    <?php if (!$isHead): ?>
    <div class="form-group">
        <label for="department_id">Department</label>
        <select id="department_id" name="department_id">
            <option value="">All Departments</option>
            <?php foreach ($departments as $d): ?>
                <option value="<?= $d['department_id'] ?>" <?= (string) $departmentId === (string) $d['department_id'] ? 'selected' : '' ?>><?= e($d['department_name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>
    <div class="form-group">
        <label for="user_id">Custodian</label>
        <select id="user_id" name="user_id">
            <option value="">All Users</option>
            <?php foreach ($users as $u): ?>
                <option value="<?= $u['user_id'] ?>" <?= (string) $userId === (string) $u['user_id'] ? 'selected' : '' ?>><?= e($u['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label for="status">Status</label>
        <select id="status" name="status">
            <option value="">All</option>
            <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
            <option value="returned" <?= $status === 'returned' ? 'selected' : '' ?>>Returned</option>
            <option value="repair" <?= $status === 'repair' ? 'selected' : '' ?>>Repair</option>
        </select>
    </div>
    <div class="form-group"><button type="submit" class="btn btn-outline">Filter</button></div>
</form>

<div class="table-wrap">
<table id="assignedTable">
    <thead>
        <tr>
            <th data-sort>Asset</th>
            <th data-sort>Qty</th>
            <th data-sort>Department</th>
            <th data-sort>Custodian</th>
            <th data-sort>Assigned Date</th>
            <th data-sort>Return Date</th>
            <th data-sort>Status</th>
            <th class="no-sort">Actions</th>
        </tr>
    </thead>
    <tbody>
    <?php if (!$rows): ?><tr class="empty-row"><td colspan="8">No allocation records found.</td></tr><?php endif; ?>
    <?php foreach ($rows as $r): ?>
        <tr>
            <td><a href="<?= APP_URL ?>/modules/assets/view.php?id=<?= (int) $r['asset_id'] ?>"><?= e($r['asset_name']) ?></a></td>
            <td><?= (int) $r['quantity'] ?></td>
            <td><?= e($r['department_name'] ?? '—') ?></td>
            <td><?= e($r['custodian_name'] ?? '—') ?></td>
            <td><?= formatDate($r['assigned_date']) ?></td>
            <td><?= formatDate($r['return_date']) ?></td>
            <td><?= statusBadge($r['status']) ?></td>
            <td class="table-actions">
                <?php if ($canEdit && $r['status'] === 'active'): ?>
                    <form method="post" action="<?= APP_URL ?>/modules/assigned/return.php" style="display:inline;" data-confirm="Mark this asset as returned?">
                        <?= csrfField() ?>
                        <input type="hidden" name="assign_id" value="<?= (int) $r['assign_id'] ?>">
                        <input type="hidden" name="outcome" value="returned">
                        <button type="submit" class="btn btn-sm btn-outline">Mark Returned</button>
                    </form>
                    <form method="post" action="<?= APP_URL ?>/modules/assigned/return.php" style="display:inline;" data-confirm="Send this asset for repair?">
                        <?= csrfField() ?>
                        <input type="hidden" name="assign_id" value="<?= (int) $r['assign_id'] ?>">
                        <input type="hidden" name="outcome" value="repair">
                        <button type="submit" class="btn btn-sm btn-accent">Send to Repair</button>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>

<?php include __DIR__ . '/../../includes/layout/footer.php'; ?>
