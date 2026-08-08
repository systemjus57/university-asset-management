<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requireLogin();

$canRequest = hasRole([ROLE_ADMIN, ROLE_HEAD]);
$canReview  = hasRole([ROLE_ADMIN, ROLE_OFFICER]);
$isHead     = hasRole([ROLE_HEAD]);

$status = $_GET['status'] ?? '';

$where  = [];
$params = [];
if ($isHead) {
    $where[] = 'r.department_id = :dept_lock';
    $params['dept_lock'] = $_SESSION['department_id'];
}
if ($status !== '') {
    $where[] = 'r.status = :status';
    $params['status'] = $status;
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$sql = "SELECT r.*, d.department_name, c.category_name, u.name AS requester_name, ur.name AS reviewer_name
        FROM requisitions r
        LEFT JOIN departments d ON d.department_id = r.department_id
        LEFT JOIN categories c ON c.category_id = r.category_id
        LEFT JOIN users u ON u.user_id = r.requester_id
        LEFT JOIN users ur ON ur.user_id = r.reviewed_by
        $whereSql
        ORDER BY r.requisition_date DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$pageTitle  = 'Requisitions';
$activeMenu = 'requisitions';
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
                <option value="issued" <?= $status === 'issued' ? 'selected' : '' ?>>Issued</option>
            </select>
        </div>
    </form>
    <?php if ($canRequest): ?>
        <a href="<?= APP_URL ?>/modules/requisitions/add.php" class="btn btn-primary">+ New Requisition</a>
    <?php endif; ?>
</div>

<div class="table-wrap">
<table id="requisitionsTable">
    <thead>
        <tr>
            <th data-sort>Department</th>
            <th data-sort>Requester</th>
            <th data-sort>Category</th>
            <th data-sort>Qty</th>
            <th data-sort>Date</th>
            <th class="no-sort">Purpose</th>
            <th data-sort>Status</th>
            <th data-sort>Reviewed By</th>
            <th class="no-sort">Actions</th>
        </tr>
    </thead>
    <tbody>
    <?php if (!$rows): ?><tr class="empty-row"><td colspan="9">No requisitions found.</td></tr><?php endif; ?>
    <?php foreach ($rows as $r): ?>
        <tr>
            <td><?= e($r['department_name'] ?? '—') ?></td>
            <td><?= e($r['requester_name'] ?? '—') ?></td>
            <td><?= e($r['category_name'] ?? '—') ?></td>
            <td><?= (int) $r['quantity'] ?></td>
            <td><?= formatDate($r['requisition_date']) ?></td>
            <td><?= e($r['purpose']) ?></td>
            <td><?= statusBadge($r['status']) ?></td>
            <td><?= e($r['reviewer_name'] ?? '—') ?></td>
            <td>
                <?php if ($canReview && $r['status'] === 'pending'): ?>
                    <a class="btn btn-sm btn-outline" href="<?= APP_URL ?>/modules/requisitions/review.php?id=<?= (int) $r['requisition_id'] ?>">Review</a>
                <?php elseif ($canReview && $r['status'] === 'approved'): ?>
                    <a class="btn btn-sm btn-outline" href="<?= APP_URL ?>/modules/requisitions/review.php?id=<?= (int) $r['requisition_id'] ?>">Mark Issued</a>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php include __DIR__ . '/../../includes/layout/footer.php'; ?>
