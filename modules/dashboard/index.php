<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requireLogin();

$isHead = hasRole([ROLE_HEAD]);
$deptId = $_SESSION['department_id'];

$deptFilterAssets = $isHead ? 'WHERE department_id = :dept' : '';
$deptParamsAssets = $isHead ? ['dept' => $deptId] : [];
$stmt = $pdo->prepare("SELECT COUNT(*) AS records, COALESCE(SUM(quantity),0) AS quantity FROM assets $deptFilterAssets");
$stmt->execute($deptParamsAssets);
$assetTotals       = $stmt->fetch();
$totalAssetRecords  = (int) $assetTotals['records'];
$totalAssets        = (int) $assetTotals['quantity']; // total physical quantity — the headline number everywhere below

$deptFilterAlloc = $isHead ? "AND assigned_to = :dept" : '';
$stmt = $pdo->prepare("SELECT COUNT(*) FROM asset_assigned WHERE status = 'active' $deptFilterAlloc");
$stmt->execute($isHead ? ['dept' => $deptId] : []);
$activeAllocations = (int) $stmt->fetchColumn();

if ($isHead) {
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM asset_maintenance m JOIN assets a ON a.asset_id = m.asset_id
         WHERE m.status IN ('pending','in_progress') AND a.department_id = :dept"
    );
    $stmt->execute(['dept' => $deptId]);
} else {
    $stmt = $pdo->query("SELECT COUNT(*) FROM asset_maintenance WHERE status IN ('pending','in_progress')");
}
$pendingMaintenance = (int) $stmt->fetchColumn();

$pendingDisposals = 0;
if (hasRole([ROLE_ADMIN, ROLE_OFFICER, ROLE_TOPMGMT])) {
    $pendingDisposals = (int) $pdo->query("SELECT COUNT(*) FROM asset_disposals WHERE status = 'pending'")->fetchColumn();
}

$pendingRequisitions = 0;
if (hasRole([ROLE_ADMIN, ROLE_OFFICER])) {
    $pendingRequisitions = (int) $pdo->query("SELECT COUNT(*) FROM requisitions WHERE status = 'pending'")->fetchColumn();
} elseif ($isHead) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM requisitions WHERE status = 'pending' AND department_id = :dept");
    $stmt->execute(['dept' => $deptId]);
    $pendingRequisitions = (int) $stmt->fetchColumn();
}

$totalUsers = hasRole([ROLE_ADMIN]) ? (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() : null;

// Asset status breakdown, for the "Assets by Status" donut + tile grid — summed by
// quantity (physical units), not row count, so a 50-chair record counts as 50.
$stmt = $pdo->prepare("SELECT status, COALESCE(SUM(quantity),0) c FROM assets $deptFilterAssets GROUP BY status");
$stmt->execute($deptParamsAssets);
$assetStatusCounts = ['active' => 0, 'under_repair' => 0, 'disposed' => 0];
foreach ($stmt->fetchAll() as $row) {
    $assetStatusCounts[$row['status']] = (int) $row['c'];
}

// Allocation status breakdown, for the "Allocations by Status" donut.
$stmt = $pdo->prepare("SELECT status, COUNT(*) c FROM asset_assigned " . ($isHead ? 'WHERE assigned_to = :dept' : '') . ' GROUP BY status');
$stmt->execute($isHead ? ['dept' => $deptId] : []);
$allocStatusCounts = ['active' => 0, 'returned' => 0, 'repair' => 0];
foreach ($stmt->fetchAll() as $row) {
    $allocStatusCounts[$row['status']] = (int) $row['c'];
}
$allocTotal = array_sum($allocStatusCounts);

// Recent activity feed — dept heads only see asset-lifecycle events, not admin/user-management noise.
$activityModules = ['assets', 'assigned', 'transfers', 'maintenance', 'audits', 'requisitions'];
if ($isHead) {
    $stmt = $pdo->prepare(
        "SELECT al.*, u.name AS user_name FROM activity_logs al
         LEFT JOIN users u ON u.user_id = al.user_id
         WHERE al.module IN ('assets','assigned','transfers','maintenance','audits','requisitions')
         ORDER BY al.created_at DESC LIMIT 12"
    );
    $stmt->execute();
} else {
    $stmt = $pdo->query(
        "SELECT al.*, u.name AS user_name FROM activity_logs al
         LEFT JOIN users u ON u.user_id = al.user_id
         ORDER BY al.created_at DESC LIMIT 12"
    );
}
$recentActivity = $stmt->fetchAll();

// Activity volume for the last 7 days, for the mini bar chart.
$activityDeptFilter = $isHead ? "AND module IN ('assets','assigned','transfers','maintenance','audits','requisitions')" : '';
$stmt = $pdo->query(
    "SELECT DATE(created_at) d, COUNT(*) c FROM activity_logs
     WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) $activityDeptFilter
     GROUP BY DATE(created_at)"
);
$activityByDate = [];
foreach ($stmt->fetchAll() as $row) {
    $activityByDate[$row['d']] = (int) $row['c'];
}
$activityChart = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i day"));
    $activityChart[] = ['label' => tWeekday($d), 'count' => $activityByDate[$d] ?? 0];
}
$maxActivity = max(1, max(array_column($activityChart, 'count')));

/** Renders a CSS conic-gradient donut with a centered value and a legend. */
function renderDonut(array $segments, string $centerValue, string $centerLabel): string
{
    $total = array_sum(array_column($segments, 'value'));
    $gradientParts = [];
    $cursor = 0;
    foreach ($segments as $seg) {
        $pct = $total > 0 ? ($seg['value'] / $total) * 100 : 0;
        $gradientParts[] = $seg['color'] . ' ' . round($cursor, 2) . '% ' . round($cursor + $pct, 2) . '%';
        $cursor += $pct;
    }
    if ($total === 0) {
        $gradientParts[] = 'var(--color-gray-200) 0% 100%';
    }
    $gradient = 'conic-gradient(' . implode(', ', $gradientParts) . ')';

    $html = '<div class="donut" style="background:' . e($gradient) . '">'
          . '<div class="donut-center"><span class="donut-center-value">' . e($centerValue) . '</span><span class="donut-center-label">' . e($centerLabel) . '</span></div>'
          . '</div><div class="donut-legend">';
    foreach ($segments as $seg) {
        $html .= '<span class="donut-legend-item"><span class="donut-legend-dot" style="background:' . e($seg['color']) . '"></span>' . e($seg['label']) . ' (' . (int) $seg['value'] . ')</span>';
    }
    return $html . '</div>';
}

$pageTitle  = t('nav.dashboard');
$activeMenu = 'dashboard';
include __DIR__ . '/../../includes/layout/header.php';
?>

<p class="text-muted mt-1"><?= e(t('dash.welcome')) ?> <strong><?= e($_SESSION['name']) ?></strong> — <?= e($_SESSION['role_name']) ?><?= $_SESSION['department_name'] ? ' · ' . e($_SESSION['department_name']) : '' ?>.</p>

<div class="stat-grid mt-2">
    <div class="stat-card">
        <div class="stat-card-icon"><?= icon('assets') ?></div>
        <div class="stat-label"><?= $isHead ? e(t('dash.department_assets')) : e(t('dash.total_assets')) ?></div>
        <div class="stat-value"><?= $totalAssets ?></div>
        <div class="text-muted" style="font-size:.8rem;"><?= $totalAssetRecords ?> asset record<?= $totalAssetRecords === 1 ? '' : 's' ?></div>
    </div>
    <div class="stat-card accent">
        <div class="stat-card-icon"><?= icon('allocations') ?></div>
        <div class="stat-label"><?= e(t('dash.active_allocations')) ?></div>
        <div class="stat-value"><?= $activeAllocations ?></div>
    </div>
    <div class="stat-card warning">
        <div class="stat-card-icon"><?= icon('maintenance') ?></div>
        <div class="stat-label"><?= e(t('dash.pending_maintenance')) ?></div>
        <div class="stat-value"><?= $pendingMaintenance ?></div>
    </div>
    <?php if (hasRole([ROLE_ADMIN, ROLE_OFFICER, ROLE_TOPMGMT])): ?>
    <div class="stat-card danger">
        <div class="stat-card-icon"><?= icon('disposals') ?></div>
        <div class="stat-label"><?= e(t('dash.pending_disposals')) ?></div>
        <div class="stat-value"><?= $pendingDisposals ?></div>
    </div>
    <?php endif; ?>
    <?php if (hasRole([ROLE_ADMIN, ROLE_OFFICER, ROLE_HEAD])): ?>
    <div class="stat-card">
        <div class="stat-card-icon"><?= icon('requisitions') ?></div>
        <div class="stat-label"><?= e(t('dash.pending_requisitions')) ?></div>
        <div class="stat-value"><?= $pendingRequisitions ?></div>
    </div>
    <?php endif; ?>
    <?php if ($totalUsers !== null): ?>
    <div class="stat-card">
        <div class="stat-card-icon"><?= icon('users') ?></div>
        <div class="stat-label"><?= e(t('dash.total_users')) ?></div>
        <div class="stat-value"><?= $totalUsers ?></div>
    </div>
    <?php endif; ?>
</div>

<div class="insight-grid">
    <div class="donut-card">
        <h4><?= e(t('dash.assets_by_status')) ?></h4>
        <?= renderDonut([
            ['label' => t('dash.status.active'), 'value' => $assetStatusCounts['active'], 'color' => 'var(--color-accent)'],
            ['label' => t('dash.status.under_repair'), 'value' => $assetStatusCounts['under_repair'], 'color' => 'var(--color-warning)'],
            ['label' => t('dash.status.disposed'), 'value' => $assetStatusCounts['disposed'], 'color' => 'var(--color-danger)'],
        ], (string) $totalAssets, t('assets')) ?>
    </div>

    <div class="tile-grid-2x2">
        <div class="tile-block tile-navy">
            <span class="tile-icon"><?= icon('assets') ?></span>
            <span class="tile-label"><?= e(t('dash.tile.active_assets')) ?></span>
            <span class="tile-value"><?= $assetStatusCounts['active'] ?></span>
        </div>
        <div class="tile-block tile-green">
            <span class="tile-icon"><?= icon('maintenance') ?></span>
            <span class="tile-label"><?= e(t('dash.status.under_repair')) ?></span>
            <span class="tile-value"><?= $assetStatusCounts['under_repair'] ?></span>
        </div>
        <div class="tile-block tile-green">
            <span class="tile-icon"><?= icon('disposals') ?></span>
            <span class="tile-label"><?= e(t('dash.status.disposed')) ?></span>
            <span class="tile-value"><?= $assetStatusCounts['disposed'] ?></span>
        </div>
        <div class="tile-block tile-navy">
            <span class="tile-icon"><?= icon('categories') ?></span>
            <span class="tile-label"><?= $isHead ? e(t('dash.tile.department_total')) : e(t('dash.total_assets')) ?></span>
            <span class="tile-value"><?= $totalAssets ?></span>
        </div>
    </div>

    <div class="donut-card">
        <h4><?= e(t('dash.allocations_by_status')) ?></h4>
        <?= renderDonut([
            ['label' => t('dash.status.active'), 'value' => $allocStatusCounts['active'], 'color' => 'var(--color-primary)'],
            ['label' => t('dash.status.returned'), 'value' => $allocStatusCounts['returned'], 'color' => 'var(--color-accent)'],
            ['label' => t('dash.status.under_repair'), 'value' => $allocStatusCounts['repair'], 'color' => 'var(--color-warning)'],
        ], (string) $allocTotal, t('allocations')) ?>
    </div>
</div>

<div class="bar-chart-card">
    <h3><?= e(t('dash.activity_7_days')) ?></h3>
    <div class="bar-chart-mini">
        <?php foreach ($activityChart as $day): ?>
            <div class="bar-chart-col">
                <div class="bar-chart-bar" style="height:<?= max(4, round($day['count'] / $maxActivity * 100)) ?>%" title="<?= $day['count'] ?> event(s)"></div>
                <span class="bar-chart-col-label"><?= e($day['label']) ?></span>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="section-heading"><h2><?= e(t('dash.recent_activity')) ?></h2></div>
<div class="table-wrap">
<table>
    <thead><tr><th><?= e(t('dash.table.action')) ?></th><th><?= e(t('dash.table.module')) ?></th><th><?= e(t('dash.table.description')) ?></th><th><?= e(t('dash.table.by')) ?></th><th><?= e(t('dash.table.when')) ?></th></tr></thead>
    <tbody>
    <?php if (!$recentActivity): ?><tr class="empty-row"><td colspan="5"><?= e(t('dash.no_activity')) ?></td></tr><?php endif; ?>
    <?php foreach ($recentActivity as $act): ?>
        <tr>
            <td><?= e($act['action']) ?></td>
            <td><?= e(ucfirst($act['module'] ?? '—')) ?></td>
            <td><?= e($act['description'] ?? '—') ?></td>
            <td><?= e($act['user_name'] ?? 'System') ?></td>
            <td><?= formatDateTime($act['created_at']) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>

<?php include __DIR__ . '/../../includes/layout/footer.php'; ?>
