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

$data = getReportRows($pdo, $report, $isHead, $deptId, $dateFrom, $dateTo);

logActivity($pdo, $_SESSION['user_id'], 'Export Report', 'reports', "Exported CSV report: $report.");

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="report_' . $report . '_' . date('Ymd_His') . '.csv"');

$out = fopen('php://output', 'w');
fputcsv($out, $data['header']);
foreach ($data['rows'] as $row) {
    fputcsv($out, $row);
}
fclose($out);
exit;
