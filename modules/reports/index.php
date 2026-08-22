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
$assetDetails = []; // grouping-key => [asset rows], backs the clickable Asset Count for by_department/by_category/by_status

if ($report === 'by_department') {
    $sql = "SELECT d.department_id, d.department_name, COUNT(a.asset_id) AS asset_count, COALESCE(SUM(a.quantity),0) AS total_quantity, COALESCE(SUM(a.quantity * a.purchase_cost),0) AS total_value
            FROM departments d LEFT JOIN assets a ON a.department_id = d.department_id
            " . ($isHead ? 'WHERE d.department_id = :dept' : '') . "
            GROUP BY d.department_id, d.department_name ORDER BY d.department_name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($isHead ? ['dept' => $deptId] : []);
    $rows = $stmt->fetchAll();

    // Same scoping as the aggregate query above, so the assets shown per row always match that row's Asset Count.
    $detailSql = "SELECT a.asset_id, a.name, a.status, a.department_id, a.quantity, a.purchase_cost, c.category_name, l.location_name
                  FROM assets a
                  LEFT JOIN categories c ON c.category_id = a.category_id
                  LEFT JOIN locations l ON l.location_id = a.location_id
                  " . ($isHead ? 'WHERE a.department_id = :dept' : '') . "
                  ORDER BY a.name";
    $stmt = $pdo->prepare($detailSql);
    $stmt->execute($isHead ? ['dept' => $deptId] : []);
    foreach ($stmt->fetchAll() as $ad) {
        $assetDetails[$ad['department_id']][] = $ad;
    }
} elseif ($report === 'by_category') {
    $where = $isHead ? 'WHERE a.department_id = :dept' : '';
    $sql = "SELECT c.category_id, c.category_name, COUNT(a.asset_id) AS asset_count, COALESCE(SUM(a.quantity),0) AS total_quantity, COALESCE(SUM(a.quantity * a.purchase_cost),0) AS total_value
            FROM categories c LEFT JOIN assets a ON a.category_id = c.category_id $where
            GROUP BY c.category_id, c.category_name ORDER BY c.category_name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($isHead ? ['dept' => $deptId] : []);
    $rows = $stmt->fetchAll();

    $detailSql = "SELECT a.asset_id, a.name, a.status, a.category_id, a.quantity, a.purchase_cost, c.category_name, l.location_name
                  FROM assets a
                  LEFT JOIN categories c ON c.category_id = a.category_id
                  LEFT JOIN locations l ON l.location_id = a.location_id
                  $where ORDER BY a.name";
    $stmt = $pdo->prepare($detailSql);
    $stmt->execute($isHead ? ['dept' => $deptId] : []);
    foreach ($stmt->fetchAll() as $ad) {
        $assetDetails[$ad['category_id']][] = $ad;
    }
} elseif ($report === 'by_status') {
    $where = $isHead ? 'WHERE department_id = :dept' : '';
    $sql = "SELECT status, COUNT(*) AS asset_count, COALESCE(SUM(quantity),0) AS total_quantity, COALESCE(SUM(quantity * purchase_cost),0) AS total_value
            FROM assets $where GROUP BY status";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($isHead ? ['dept' => $deptId] : []);
    $rows = $stmt->fetchAll();

    $detailWhere = $isHead ? 'WHERE a.department_id = :dept' : '';
    $detailSql = "SELECT a.asset_id, a.name, a.status, a.quantity, a.purchase_cost, c.category_name, l.location_name
                  FROM assets a
                  LEFT JOIN categories c ON c.category_id = a.category_id
                  LEFT JOIN locations l ON l.location_id = a.location_id
                  $detailWhere ORDER BY a.name";
    $stmt = $pdo->prepare($detailSql);
    $stmt->execute($isHead ? ['dept' => $deptId] : []);
    foreach ($stmt->fetchAll() as $ad) {
        $assetDetails[$ad['status']][] = $ad;
    }
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

/**
 * Renders an Asset Count cell: a plain number when there's nothing to show,
 * or a clickable count that opens a modal listing the exact asset records
 * behind that number (same rows the count was computed from — no separate
 * "first N assets" query).
 */
function renderAssetCountCell(string $modalId, int $count, array $assets): string
{
    if ($count <= 0) {
        return (string) $count;
    }

    $html = '<button type="button" class="link-count" data-modal-target="' . e($modalId) . '">' . $count . '</button>';
    $html .= '<div class="modal-overlay" id="' . e($modalId) . '">';
    $html .= '<div class="modal">';
    $html .= '<div class="modal-header"><h3>Assets (' . $count . ')</h3><button type="button" class="modal-close" data-modal-close>&times;</button></div>';
    $html .= '<div class="modal-body"><div class="table-wrap"><table><thead><tr><th>ID</th><th>Name</th><th>Category</th><th>Location</th><th>Qty</th><th>Status</th><th>Value</th></tr></thead><tbody>';
    foreach ($assets as $a) {
        $html .= '<tr>'
               . '<td>#' . (int) $a['asset_id'] . '</td>'
               . '<td>' . e($a['name']) . '</td>'
               . '<td>' . e($a['category_name'] ?? '—') . '</td>'
               . '<td>' . e($a['location_name'] ?? '—') . '</td>'
               . '<td>' . (int) $a['quantity'] . '</td>'
               . '<td>' . statusBadge($a['status']) . '</td>'
               . '<td>' . formatMoney($a['quantity'] * $a['purchase_cost']) . '</td>'
               . '</tr>';
    }
    $html .= '</tbody></table></div></div>';
    $html .= '</div></div>';

    return $html;
}

/**
 * Asset Count trigger for the Department report only: just the button (or
 * plain number). The modal markup is rendered separately via
 * renderAssetDetailModal() below the table, not nested inside the row —
 * because the Department report also makes the whole <tr> a click target
 * (see below), and a modal nested inside its own trigger row would have its
 * close button's click bubble back up into the row and immediately reopen it.
 */
function renderAssetCountTrigger(string $modalId, int $count): string
{
    if ($count <= 0) {
        return (string) $count;
    }

    return '<button type="button" class="link-count" data-modal-target="' . e($modalId) . '">' . $count . '</button>';
}

/** Department report's asset detail modal — same fields as renderAssetCountCell()'s modal, plus each asset's Value. */
function renderAssetDetailModal(string $modalId, int $count, array $assets): string
{
    $html = '<div class="modal-overlay" id="' . e($modalId) . '">';
    $html .= '<div class="modal">';
    $html .= '<div class="modal-header"><h3>Assets (' . $count . ')</h3><button type="button" class="modal-close" data-modal-close>&times;</button></div>';
    $html .= '<div class="modal-body"><div class="table-wrap"><table><thead><tr><th>ID</th><th>Name</th><th>Category</th><th>Location</th><th>Qty</th><th>Status</th><th>Value</th></tr></thead><tbody>';
    foreach ($assets as $a) {
        $html .= '<tr>'
               . '<td>#' . (int) $a['asset_id'] . '</td>'
               . '<td>' . e($a['name']) . '</td>'
               . '<td>' . e($a['category_name'] ?? '—') . '</td>'
               . '<td>' . e($a['location_name'] ?? '—') . '</td>'
               . '<td>' . (int) $a['quantity'] . '</td>'
               . '<td>' . statusBadge($a['status']) . '</td>'
               . '<td>' . formatMoney($a['quantity'] * $a['purchase_cost']) . '</td>'
               . '</tr>';
    }
    $html .= '</tbody></table></div></div>';
    $html .= '</div></div>';

    return $html;
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
    <?php if ($report === 'by_department'): ?>
        <thead><tr><th>Department</th><th>Asset Records</th><th>Total Quantity</th><th>Total Value</th></tr></thead>
        <tbody>
        <?php if (!$rows): ?><tr class="empty-row"><td colspan="4">No data available.</td></tr><?php endif; ?>
        <?php $departmentModals = []; ?>
        <?php foreach ($rows as $r): ?>
            <?php
                $deptCount = (int) $r['asset_count'];
                $deptModalId = 'assetsModal_by_department_' . $r['department_id'];
                $deptRowAttrs = $deptCount > 0 ? ' class="row-clickable" data-modal-target="' . e($deptModalId) . '"' : '';
                if ($deptCount > 0) {
                    $departmentModals[] = renderAssetDetailModal($deptModalId, $deptCount, $assetDetails[$r['department_id']] ?? []);
                }
            ?>
            <tr<?= $deptRowAttrs ?>>
                <td><?= e($r['department_name']) ?></td>
                <td><?= renderAssetCountTrigger($deptModalId, $deptCount) ?></td>
                <td><?= (int) $r['total_quantity'] ?></td>
                <td><?= formatMoney($r['total_value']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    <?php elseif ($report === 'by_category'): ?>
        <thead><tr><th>Category</th><th>Asset Records</th><th>Total Quantity</th><th>Total Value</th></tr></thead>
        <tbody>
        <?php if (!$rows): ?><tr class="empty-row"><td colspan="4">No data available.</td></tr><?php endif; ?>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td><?= e($r['category_name']) ?></td>
                <td><?= renderAssetCountCell('assetsModal_by_category_' . $r['category_id'], (int) $r['asset_count'], $assetDetails[$r['category_id']] ?? []) ?></td>
                <td><?= (int) $r['total_quantity'] ?></td>
                <td><?= formatMoney($r['total_value']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    <?php elseif ($report === 'by_status'): ?>
        <thead><tr><th>Status</th><th>Asset Records</th><th>Total Quantity</th><th>Total Value</th></tr></thead>
        <tbody>
        <?php if (!$rows): ?><tr class="empty-row"><td colspan="4">No data available.</td></tr><?php endif; ?>
        <?php foreach ($rows as $r): ?>
            <tr><td><?= statusBadge($r['status']) ?></td><td><?= renderAssetCountCell('assetsModal_status_' . $r['status'], (int) $r['asset_count'], $assetDetails[$r['status']] ?? []) ?></td><td><?= (int) $r['total_quantity'] ?></td><td><?= formatMoney($r['total_value']) ?></td></tr>
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

<?php if ($report === 'by_department'): ?>
    <?php foreach ($departmentModals as $modalHtml): ?>
        <?= $modalHtml ?>
    <?php endforeach; ?>
<?php endif; ?>

<?php include __DIR__ . '/../../includes/layout/footer.php'; ?>
