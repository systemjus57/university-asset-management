<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requireLogin();

$isHead = hasRole([ROLE_HEAD]);
$deptId = $_SESSION['department_id'];

$report   = $_GET['report'] ?? 'by_department';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo   = $_GET['date_to'] ?? '';

$validReports = ['by_department', 'by_category', 'by_status', 'maintenance_cost', 'disposals'];
if (!in_array($report, $validReports, true)) {
    $report = 'by_department';
}

$rows = [];
$header = [];

if ($report === 'by_department') {
    $header = ['Department', 'Asset Count', 'Total Value'];
    $sql = "SELECT d.department_name, COUNT(a.asset_id) AS asset_count, COALESCE(SUM(a.purchase_cost),0) AS total_value
            FROM departments d LEFT JOIN assets a ON a.department_id = d.department_id
            " . ($isHead ? 'WHERE d.department_id = :dept' : '') . "
            GROUP BY d.department_id, d.department_name ORDER BY d.department_name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($isHead ? ['dept' => $deptId] : []);
    foreach ($stmt->fetchAll() as $r) {
        $rows[] = [$r['department_name'], $r['asset_count'], number_format((float) $r['total_value'], 2)];
    }
} elseif ($report === 'by_category') {
    $header = ['Category', 'Asset Count', 'Total Value'];
    $where = $isHead ? 'WHERE a.department_id = :dept' : '';
    $sql = "SELECT c.category_name, COUNT(a.asset_id) AS asset_count, COALESCE(SUM(a.purchase_cost),0) AS total_value
            FROM categories c LEFT JOIN assets a ON a.category_id = c.category_id $where
            GROUP BY c.category_id, c.category_name ORDER BY c.category_name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($isHead ? ['dept' => $deptId] : []);
    foreach ($stmt->fetchAll() as $r) {
        $rows[] = [$r['category_name'], $r['asset_count'], number_format((float) $r['total_value'], 2)];
    }
} elseif ($report === 'by_status') {
    $header = ['Status', 'Asset Count', 'Total Value'];
    $where = $isHead ? 'WHERE department_id = :dept' : '';
    $sql = "SELECT status, COUNT(*) AS asset_count, COALESCE(SUM(purchase_cost),0) AS total_value FROM assets $where GROUP BY status";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($isHead ? ['dept' => $deptId] : []);
    foreach ($stmt->fetchAll() as $r) {
        $rows[] = [$r['status'], $r['asset_count'], number_format((float) $r['total_value'], 2)];
    }
} elseif ($report === 'maintenance_cost') {
    $header = ['Asset', 'Completed Date', 'Cost', 'Technician/Vendor'];
    $where = ["m.status = 'completed'"];
    $params = [];
    if ($isHead) { $where[] = 'a.department_id = :dept'; $params['dept'] = $deptId; }
    if ($dateFrom !== '') { $where[] = 'm.completed_date >= :from'; $params['from'] = $dateFrom; }
    if ($dateTo !== '')   { $where[] = 'm.completed_date <= :to';   $params['to'] = $dateTo; }
    $whereSql = 'WHERE ' . implode(' AND ', $where);
    $sql = "SELECT a.name AS asset_name, m.completed_date, m.cost, m.technician_vendor
            FROM asset_maintenance m JOIN assets a ON a.asset_id = m.asset_id $whereSql ORDER BY m.completed_date DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    foreach ($stmt->fetchAll() as $r) {
        $rows[] = [$r['asset_name'], $r['completed_date'], number_format((float) $r['cost'], 2), $r['technician_vendor']];
    }
} elseif ($report === 'disposals') {
    $header = ['Asset', 'Method', 'Status', 'Requested Date', 'Disposal Date', 'Reason'];
    $where = [];
    $params = [];
    if ($isHead) { $where[] = 'a.department_id = :dept'; $params['dept'] = $deptId; }
    if ($dateFrom !== '') { $where[] = 'ds.request_date >= :from'; $params['from'] = $dateFrom; }
    if ($dateTo !== '')   { $where[] = 'ds.request_date <= :to';   $params['to'] = $dateTo; }
    $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
    $sql = "SELECT a.name AS asset_name, ds.method, ds.status, ds.request_date, ds.disposal_date, ds.reason
            FROM asset_disposals ds JOIN assets a ON a.asset_id = ds.asset_id $whereSql ORDER BY ds.request_date DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    foreach ($stmt->fetchAll() as $r) {
        $rows[] = [$r['asset_name'], $r['method'], $r['status'], $r['request_date'], $r['disposal_date'], $r['reason']];
    }
}

logActivity($pdo, $_SESSION['user_id'], 'Export Report', 'reports', "Exported CSV report: $report.");

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="report_' . $report . '_' . date('Ymd_His') . '.csv"');

$out = fopen('php://output', 'w');
fputcsv($out, $header);
foreach ($rows as $row) {
    fputcsv($out, $row);
}
fclose($out);
exit;
