<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requireLogin();

$assetId = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare(
    'SELECT a.*, c.category_name, d.department_name, l.location_name, l.building, l.room
     FROM assets a
     LEFT JOIN categories c ON c.category_id = a.category_id
     LEFT JOIN departments d ON d.department_id = a.department_id
     LEFT JOIN locations l ON l.location_id = a.location_id
     WHERE a.asset_id = :id'
);
$stmt->execute(['id' => $assetId]);
$asset = $stmt->fetch();

if (!$asset) {
    flash('error', 'Asset not found.');
    redirect(APP_URL . '/modules/assets/list.php');
}

if (hasRole([ROLE_HEAD]) && (int) $asset['department_id'] !== (int) $_SESSION['department_id']) {
    http_response_code(403);
    require APP_ROOT . '/includes/layout/forbidden.php';
    exit;
}

$canEdit = hasRole([ROLE_ADMIN, ROLE_OFFICER]);

$allocations = $pdo->prepare(
    'SELECT aa.*, d.department_name, u.name AS custodian_name, ub.name AS assigned_by_name
     FROM asset_assigned aa
     LEFT JOIN departments d ON d.department_id = aa.assigned_to
     LEFT JOIN users u ON u.user_id = aa.assigned_user_id
     LEFT JOIN users ub ON ub.user_id = aa.assigned_by
     WHERE aa.asset_id = :id ORDER BY aa.assigned_date DESC'
);
$allocations->execute(['id' => $assetId]);
$allocations = $allocations->fetchAll();

$transfers = $pdo->prepare(
    'SELECT t.*, df.department_name AS from_dept, dt.department_name AS to_dept, u.name AS handled_by_name
     FROM asset_transfers t
     LEFT JOIN departments df ON df.department_id = t.from_department_id
     LEFT JOIN departments dt ON dt.department_id = t.to_department_id
     LEFT JOIN users u ON u.user_id = t.handled_by
     WHERE t.asset_id = :id ORDER BY t.transfer_date DESC'
);
$transfers->execute(['id' => $assetId]);
$transfers = $transfers->fetchAll();

$maintenance = $pdo->prepare(
    'SELECT m.*, u.name AS reported_by_name
     FROM asset_maintenance m
     LEFT JOIN users u ON u.user_id = m.reported_by
     WHERE m.asset_id = :id ORDER BY m.reported_date DESC'
);
$maintenance->execute(['id' => $assetId]);
$maintenance = $maintenance->fetchAll();

$audits = $pdo->prepare(
    'SELECT au.*, u.name AS audited_by_name
     FROM asset_audits au
     LEFT JOIN users u ON u.user_id = au.audited_by
     WHERE au.asset_id = :id ORDER BY au.audit_date DESC'
);
$audits->execute(['id' => $assetId]);
$audits = $audits->fetchAll();

$disposals = $pdo->prepare(
    'SELECT ds.*, u.name AS requested_by_name, ua.name AS approved_by_name
     FROM asset_disposals ds
     LEFT JOIN users u ON u.user_id = ds.requested_by
     LEFT JOIN users ua ON ua.user_id = ds.approved_by
     WHERE ds.asset_id = :id ORDER BY ds.request_date DESC'
);
$disposals->execute(['id' => $assetId]);
$disposals = $disposals->fetchAll();

$pageTitle  = $asset['name'];
$activeMenu = 'assets';
include __DIR__ . '/../../includes/layout/header.php';
?>

<div class="card-header">
    <h2 style="margin:0;">Asset #<?= (int) $asset['asset_id'] ?> — <?= e($asset['name']) ?> <?= statusBadge($asset['status']) ?></h2>
    <?php if ($canEdit): ?>
        <a class="btn btn-outline" href="<?= APP_URL ?>/modules/assets/edit.php?id=<?= (int) $asset['asset_id'] ?>">Edit Asset</a>
    <?php endif; ?>
</div>

<div class="card mt-2">
    <div class="detail-grid">
        <div class="detail-item"><div class="detail-label">Category</div><div class="detail-value"><?= e($asset['category_name'] ?? '—') ?></div></div>
        <div class="detail-item"><div class="detail-label">Serial No.</div><div class="detail-value"><?= e($asset['serial_no'] ?? '—') ?></div></div>
        <div class="detail-item"><div class="detail-label">Custodian Department</div><div class="detail-value"><?= e($asset['department_name'] ?? '—') ?></div></div>
        <div class="detail-item"><div class="detail-label">Location</div><div class="detail-value"><?= e($asset['location_name'] ?? '—') ?></div></div>
        <div class="detail-item"><div class="detail-label">Purchase Date</div><div class="detail-value"><?= formatDate($asset['purchase_date']) ?></div></div>
        <div class="detail-item"><div class="detail-label">Purchase Cost</div><div class="detail-value"><?= formatMoney($asset['purchase_cost']) ?></div></div>
        <div class="detail-item"><div class="detail-label">Warranty Expiry</div><div class="detail-value"><?= formatDate($asset['warranty_expiry']) ?></div></div>
        <div class="detail-item"><div class="detail-label">Registered On</div><div class="detail-value"><?= formatDate($asset['created_at']) ?></div></div>
    </div>
    <?php if (!empty($asset['description'])): ?>
        <div class="mt-2"><div class="detail-label">Description</div><p><?= nl2br(e($asset['description'])) ?></p></div>
    <?php endif; ?>
</div>

<div class="tabs">
    <div class="tab-link active" data-tab="tab-alloc">Allocations (<?= count($allocations) ?>)</div>
    <div class="tab-link" data-tab="tab-transfers">Transfers (<?= count($transfers) ?>)</div>
    <div class="tab-link" data-tab="tab-maintenance">Maintenance (<?= count($maintenance) ?>)</div>
    <div class="tab-link" data-tab="tab-audits">Audits (<?= count($audits) ?>)</div>
    <div class="tab-link" data-tab="tab-disposals">Disposals (<?= count($disposals) ?>)</div>
</div>

<div id="tab-alloc" class="tab-panel active">
    <div class="table-wrap">
    <table>
        <thead><tr><th>Assigned To</th><th>Custodian</th><th>Assigned Date</th><th>Return Date</th><th>Status</th><th>Processed By</th></tr></thead>
        <tbody>
        <?php if (!$allocations): ?><tr class="empty-row"><td colspan="6">No allocation history.</td></tr><?php endif; ?>
        <?php foreach ($allocations as $al): ?>
            <tr>
                <td><?= e($al['department_name'] ?? '—') ?></td>
                <td><?= e($al['custodian_name'] ?? '—') ?></td>
                <td><?= formatDate($al['assigned_date']) ?></td>
                <td><?= formatDate($al['return_date']) ?></td>
                <td><?= statusBadge($al['status']) ?></td>
                <td><?= e($al['assigned_by_name'] ?? '—') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>

<div id="tab-transfers" class="tab-panel">
    <div class="table-wrap">
    <table>
        <thead><tr><th>From</th><th>To</th><th>Transfer Date</th><th>Reason</th><th>Handled By</th></tr></thead>
        <tbody>
        <?php if (!$transfers): ?><tr class="empty-row"><td colspan="5">No transfer history.</td></tr><?php endif; ?>
        <?php foreach ($transfers as $t): ?>
            <tr>
                <td><?= e($t['from_dept'] ?? '—') ?></td>
                <td><?= e($t['to_dept'] ?? '—') ?></td>
                <td><?= formatDate($t['transfer_date']) ?></td>
                <td><?= e($t['reason'] ?? '—') ?></td>
                <td><?= e($t['handled_by_name'] ?? '—') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>

<div id="tab-maintenance" class="tab-panel">
    <div class="table-wrap">
    <table>
        <thead><tr><th>Issue</th><th>Reported By</th><th>Reported</th><th>Status</th><th>Completed</th><th>Cost</th><th>Technician/Vendor</th></tr></thead>
        <tbody>
        <?php if (!$maintenance): ?><tr class="empty-row"><td colspan="7">No maintenance history.</td></tr><?php endif; ?>
        <?php foreach ($maintenance as $m): ?>
            <tr>
                <td><?= e($m['issue_description']) ?></td>
                <td><?= e($m['reported_by_name'] ?? '—') ?></td>
                <td><?= formatDate($m['reported_date']) ?></td>
                <td><?= statusBadge($m['status']) ?></td>
                <td><?= formatDate($m['completed_date']) ?></td>
                <td><?= formatMoney($m['cost']) ?></td>
                <td><?= e($m['technician_vendor'] ?? '—') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>

<div id="tab-audits" class="tab-panel">
    <div class="table-wrap">
    <table>
        <thead><tr><th>Audit Date</th><th>Result</th><th>Remarks</th><th>Audited By</th></tr></thead>
        <tbody>
        <?php if (!$audits): ?><tr class="empty-row"><td colspan="4">No audit history.</td></tr><?php endif; ?>
        <?php foreach ($audits as $au): ?>
            <tr>
                <td><?= formatDate($au['audit_date']) ?></td>
                <td><?= statusBadge($au['result']) ?></td>
                <td><?= e($au['remarks'] ?? '—') ?></td>
                <td><?= e($au['audited_by_name'] ?? '—') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>

<div id="tab-disposals" class="tab-panel">
    <div class="table-wrap">
    <table>
        <thead><tr><th>Requested</th><th>Method</th><th>Reason</th><th>Status</th><th>Approved By</th><th>Disposal Date</th></tr></thead>
        <tbody>
        <?php if (!$disposals): ?><tr class="empty-row"><td colspan="6">No disposal history.</td></tr><?php endif; ?>
        <?php foreach ($disposals as $ds): ?>
            <tr>
                <td><?= formatDate($ds['request_date']) ?></td>
                <td><?= e(ucfirst($ds['method'])) ?></td>
                <td><?= e($ds['reason']) ?></td>
                <td><?= statusBadge($ds['status']) ?></td>
                <td><?= e($ds['approved_by_name'] ?? '—') ?></td>
                <td><?= formatDate($ds['disposal_date']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>

<?php include __DIR__ . '/../../includes/layout/footer.php'; ?>
