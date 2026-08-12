<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once APP_ROOT . '/vendor/fpdf/fpdf.php';
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

$data    = getReportRows($pdo, $report, $isHead, $deptId, $dateFrom, $dateTo);
$uniName = getSetting($pdo, 'university_name', 'Somali National University');

class ReportPDF extends FPDF
{
    public string $reportTitle = '';
    public string $uniName = '';

    function Header(): void
    {
        $this->SetFont('Helvetica', 'B', 14);
        $this->Cell(0, 7, $this->uniName, 0, 1, 'C');
        $this->SetFont('Helvetica', '', 10);
        $this->Cell(0, 6, $this->reportTitle, 0, 1, 'C');
        $this->SetTextColor(120, 130, 140);
        $this->Cell(0, 5, 'Generated ' . date('d M Y, H:i'), 0, 1, 'C');
        $this->SetTextColor(0, 0, 0);
        $this->Ln(3);
    }

    function Footer(): void
    {
        $this->SetY(-15);
        $this->SetFont('Helvetica', 'I', 8);
        $this->SetTextColor(140, 140, 140);
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }

    /** Truncates text with an ellipsis so it never overflows a fixed-width cell. */
    function fitText(string $text, float $width): string
    {
        $maxWidth = $width - 2;
        if ($this->GetStringWidth($text) <= $maxWidth) {
            return $text;
        }
        while ($text !== '' && $this->GetStringWidth($text . '…') > $maxWidth) {
            $text = mb_substr($text, 0, -1);
        }
        return $text . '…';
    }
}

$columnCount = count($data['header']);
$orientation = $columnCount >= 4 ? 'L' : 'P';

$pdf = new ReportPDF($orientation, 'mm', 'A4');
$pdf->uniName     = $uniName;
$pdf->reportTitle = $data['label'];
$pdf->SetAutoPageBreak(true, 18);
$pdf->AliasNbPages();
$pdf->AddPage();

$pageWidth = $pdf->GetPageWidth() - 20; // minus 10mm margins each side
$colWidth  = $pageWidth / max(1, $columnCount);

$pdf->SetFont('Helvetica', 'B', 9);
$pdf->SetFillColor(20, 55, 94);
$pdf->SetTextColor(255, 255, 255);
foreach ($data['header'] as $col) {
    $pdf->Cell($colWidth, 8, $pdf->fitText($col, $colWidth), 1, 0, 'L', true);
}
$pdf->Ln();

$pdf->SetFont('Helvetica', '', 9);
$pdf->SetTextColor(20, 25, 35);
$fill = false;
foreach ($data['rows'] as $row) {
    $pdf->SetFillColor(245, 247, 250);
    foreach ($row as $cell) {
        $pdf->Cell($colWidth, 7, $pdf->fitText((string) $cell, $colWidth), 1, 0, 'L', $fill);
    }
    $pdf->Ln();
    $fill = !$fill;
}
if (!$data['rows']) {
    $pdf->Cell($pageWidth, 8, 'No data available.', 1, 1, 'C');
}

logActivity($pdo, $_SESSION['user_id'], 'Export Report', 'reports', "Exported PDF report: $report.");

$pdf->Output('D', 'report_' . $report . '_' . date('Ymd_His') . '.pdf');
exit;
