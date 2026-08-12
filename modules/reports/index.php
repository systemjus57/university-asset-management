<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requireLogin();

$isHead = hasRole([ROLE_HEAD]);
$deptId = $_SESSION['department_id'];

$report    = $_GET['report'] ?? 'by_department';
$dateFrom  = $_GET['date_from'] ?? '';
$dateTo    = $_GET['date_to'] ?? '';

$validReports = ['by_department', 'by_category', 'by_status', 'maintenance_cost', 'disposals'];
if (!in_array($report, $validReports, true)) {
    $report = 'by_department';
}

$rows = [];
$totalValue = 0;

if ($report === 'by_department') {
    $sql = "SELECT d.department_name, COUNT(a.asset_id) AS asset_count, COALESCE(SUM(a.purchase_cost),0) AS total_value
            FROM departments d LEFT JOIN assets a ON a.department_id = d.department_id
            " . ($isHead ? 'WHERE d.department_id = :dept' : '') . "
            GROUP BY d.department_id, d.department_name ORDER BY d.department_name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($isHead ? ['dept' => $deptId] : []);
    $rows = $stmt->fetchAll();
} elseif ($report === 'by_category') {
    $where = $isHead ? 'WHERE a.department_id = :dept' : '';
    $sql = "SELECT c.category_name, COUNT(a.asset_id) AS asset_count, COALESCE(SUM(a.purchase_cost),0) AS total_value
            FROM categories c LEFT JOIN assets a ON a.category_id = c.category_id $where
            GROUP BY c.category_id, c.category_name ORDER BY c.category_name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($isHead ? ['dept' => $deptId] : []);
    $rows = $stmt->fetchAll();
} elseif ($report === 'by_status') {
    $where = $isHead ? 'WHERE department_id = :dept' : '';
    $sql = "SELECT status, COUNT(*) AS asset_count, COALESCE(SUM(purchase_cost),0) AS total_value
            FROM assets $where GROUP BY status";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($isHead ? ['dept' => $deptId] : []);
    $rows = $stmt->fetchAll();
} elseif ($report === 'maintenance_cost') {
    $where = ["m.status = 'completed'"];
    $params = [];
    if ($isHead) { $where[] = 'a.department_id = :dept'; $params['dept'] = $deptId; }
    if ($dateFrom !== '') { $where[] = 'm.completed_date >= :from'; $params['from'] = $dateFrom; }
    if ($dateTo !== '')   { $where[] = 'm.completed_date <= :to';   $params['to'] = $dateTo; }
    $whereSql = 'WHERE ' . implode(' AND ', $where);
    $sql = "SELECT a.name AS asset_name, m.completed_date, m.cost, m.technician_vendor
            FROM asset_maintenance m JOIN assets a ON a.asset_id = m.asset_id
            $whereSql ORDER BY m.completed_date DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
    foreach ($rows as $r) { $totalValue += (float) $r['cost']; }
} elseif ($report === 'disposals') {
    $where = [];
    $params = [];
    if ($isHead) { $where[] = 'a.department_id = :dept'; $params['dept'] = $deptId; }
    if ($dateFrom !== '') { $where[] = 'ds.request_date >= :from'; $params['from'] = $dateFrom; }
    if ($dateTo !== '')   { $where[] = 'ds.request_date <= :to';   $params['to'] = $dateTo; }
    $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
    $sql = "SELECT a.name AS asset_name, ds.method, ds.status, ds.request_date, ds.disposal_date, ds.reason
            FROM asset_disposals ds JOIN assets a ON a.asset_id = ds.asset_id
            $whereSql ORDER BY ds.request_date DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
}

$reportLabels = [
    'by_department'    => 'Assets by Department',
    'by_category'      => 'Assets by Category',
    'by_status'        => 'Assets by Status',
    'maintenance_cost' => 'Maintenance Cost Report',
    'disposals'         => 'Disposal Report',
];

$pageTitle  = 'Reports';
$activeMenu = 'reports';
include __DIR__ . '/../../includes/layout/header.php';
?>

<div class="tabs">
    <?php foreach ($reportLabels as $key => $label): ?>
        <a class="tab-link <?= $report === $key ? 'active' : '' ?>" style="text-decoration:none;"
           href="?report=<?= $key ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
</div>

<?php if (in_array($report, ['maintenance_cost', 'disposals'], true)): ?>
<form method="get" class="filter-bar">
    <input type="hidden" name="report" value="<?= e($report) ?>">
    <div class="form-group"><label for="date_from">From</label><input type="date" id="date_from" name="date_from" value="<?= e($dateFrom) ?>"></div>
    <div class="form-group"><label for="date_to">To</label><input type="date" id="date_to" name="date_to" value="<?= e($dateTo) ?>"></div>
    <div class="form-group"><button type="submit" class="btn btn-outline">Apply</button></div>
</form>
<?php endif; ?>

<div class="card-header">
    <h2 style="margin:0;"><?= e($reportLabels[$report]) ?></h2>
    <div class="d-flex gap-1">
        <a class="btn btn-outline btn-sm" href="<?= APP_URL ?>/modules/reports/export_pdf.php?<?= http_build_query($_GET) ?>">Export PDF</a>
        <a class="btn btn-accent btn-sm" href="<?= APP_URL ?>/modules/reports/export_csv.php?<?= http_build_query($_GET) ?>">Export CSV</a>
    </div>
</div>

<div class="table-wrap">
<table>
    <?php if ($report === 'by_department' || $report === 'by_category'): ?>
        <thead><tr><th><?= $report === 'by_department' ? 'Department' : 'Category' ?></th><th>Asset Count</th><th>Total Value</th></tr></thead>
        <tbody>
        <?php if (!$rows): ?><tr class="empty-row"><td colspan="3">No data available.</td></tr><?php endif; ?>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td><?= e($r['department_name'] ?? $r['category_name'] ?? '—') ?></td>
                <td><?= (int) $r['asset_count'] ?></td>
                <td><?= formatMoney($r['total_value']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    <?php elseif ($report === 'by_status'): ?>
        <thead><tr><th>Status</th><th>Asset Count</th><th>Total Value</th></tr></thead>
        <tbody>
        <?php if (!$rows): ?><tr class="empty-row"><td colspan="3">No data available.</td></tr><?php endif; ?>
        <?php foreach ($rows as $r): ?>
            <tr><td><?= statusBadge($r['status']) ?></td><td><?= (int) $r['asset_count'] ?></td><td><?= formatMoney($r['total_value']) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    <?php elseif ($report === 'maintenance_cost'): ?>
        <thead><tr><th>Asset</th><th>Completed</th><th>Cost</th><th>Technician/Vendor</th></tr></thead>
        <tbody>
        <?php if (!$rows): ?><tr class="empty-row"><td colspan="4">No completed maintenance in range.</td></tr><?php endif; ?>
        <?php foreach ($rows as $r): ?>
            <tr><td><?= e($r['asset_name']) ?></td><td><?= formatDate($r['completed_date']) ?></td><td><?= formatMoney($r['cost']) ?></td><td><?= e($r['technician_vendor'] ?? '—') ?></td></tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot><tr><td colspan="2" style="font-weight:700;">Total</td><td style="font-weight:700;"><?= formatMoney($totalValue) ?></td><td></td></tr></tfoot>
    <?php elseif ($report === 'disposals'): ?>
        <thead><tr><th>Asset</th><th>Method</th><th>Status</th><th>Requested</th><th>Disposal Date</th><th>Reason</th></tr></thead>
        <tbody>
        <?php if (!$rows): ?><tr class="empty-row"><td colspan="6">No disposal records in range.</td></tr><?php endif; ?>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td><?= e($r['asset_name']) ?></td>
                <td><?= e(ucfirst($r['method'])) ?></td>
                <td><?= statusBadge($r['status']) ?></td>
                <td><?= formatDate($r['request_date']) ?></td>
                <td><?= formatDate($r['disposal_date']) ?></td>
                <td><?= e($r['reason']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    <?php endif; ?>
</table>
</div>

<?php include __DIR__ . '/../../includes/layout/footer.php'; ?>
