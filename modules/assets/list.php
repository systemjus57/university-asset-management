<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requireLogin();

$canEdit = hasRole([ROLE_ADMIN, ROLE_OFFICER]);
$isHead  = hasRole([ROLE_HEAD]);

$q            = clean($_GET['q'] ?? '');
$categoryId   = $_GET['category_id'] ?? '';
$departmentId = $_GET['department_id'] ?? '';
$status       = $_GET['status'] ?? '';
$page         = max(1, (int) ($_GET['page'] ?? 1));
$perPage      = max(5, (int) getSetting($pdo, 'records_per_page', '15'));
$offset       = ($page - 1) * $perPage;

$where  = [];
$params = [];

if ($isHead) {
    $where[] = 'a.department_id = :dept_lock';
    $params['dept_lock'] = $_SESSION['department_id'];
} elseif ($departmentId !== '') {
    $where[] = 'a.department_id = :department_id';
    $params['department_id'] = $departmentId;
}

if ($q !== '') {
    $where[] = '(a.name LIKE :q1 OR a.serial_no LIKE :q2)';
    $params['q1'] = "%$q%";
    $params['q2'] = "%$q%";
}
if ($categoryId !== '') {
    $where[] = 'a.category_id = :category_id';
    $params['category_id'] = $categoryId;
}
if ($status !== '') {
    $where[] = 'a.status = :status';
    $params['status'] = $status;
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM assets a $whereSql");
$countStmt->execute($params);
$totalRows  = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalRows / $perPage));

$sql = "SELECT a.*, c.category_name, d.department_name, l.location_name
        FROM assets a
        LEFT JOIN categories c ON c.category_id = a.category_id
        LEFT JOIN departments d ON d.department_id = a.department_id
        LEFT JOIN locations l ON l.location_id = a.location_id
        $whereSql
        ORDER BY a.asset_id ASC
        LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) {
    $stmt->bindValue(':' . $k, $v);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$assets = $stmt->fetchAll();

$categories  = $pdo->query('SELECT category_id, category_name FROM categories ORDER BY category_name')->fetchAll();
$departments = $pdo->query('SELECT department_id, department_name FROM departments ORDER BY department_name')->fetchAll();

$pageTitle  = 'Assets';
$activeMenu = 'assets';
include __DIR__ . '/../../includes/layout/header.php';
?>

<div class="card-header">
    <div></div>
    <?php if ($canEdit): ?>
        <a href="<?= APP_URL ?>/modules/assets/add.php" class="btn btn-primary">+ Add Asset</a>
    <?php endif; ?>
</div>

<form method="get" class="filter-bar">
    <div class="form-group">
        <label for="q">Search</label>
        <input type="search" id="q" name="q" placeholder="Name or serial no." value="<?= e($q) ?>">
    </div>
    <div class="form-group">
        <label for="category_id">Category</label>
        <select id="category_id" name="category_id">
            <option value="">All Categories</option>
            <?php foreach ($categories as $c): ?>
                <option value="<?= $c['category_id'] ?>" <?= (string) $categoryId === (string) $c['category_id'] ? 'selected' : '' ?>><?= e($c['category_name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
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
        <label for="status">Status</label>
        <select id="status" name="status">
            <option value="">All Statuses</option>
            <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
            <option value="under_repair" <?= $status === 'under_repair' ? 'selected' : '' ?>>Under Repair</option>
            <option value="disposed" <?= $status === 'disposed' ? 'selected' : '' ?>>Disposed</option>
        </select>
    </div>
    <div class="form-group">
        <button type="submit" class="btn btn-outline">Filter</button>
    </div>
</form>

<div class="table-wrap">
<table id="assetsTable">
    <thead>
        <tr>
            <th data-sort>ID</th>
            <th data-sort>Name</th>
            <th data-sort>Category</th>
            <th data-sort>Serial No.</th>
            <th data-sort>Department</th>
            <th data-sort>Location</th>
            <th data-sort>Cost</th>
            <th data-sort>Status</th>
            <th class="no-sort">Actions</th>
        </tr>
    </thead>
    <tbody>
    <?php if (!$assets): ?>
        <tr class="empty-row"><td colspan="9">No assets found matching your filters.</td></tr>
    <?php endif; ?>
    <?php foreach ($assets as $a): ?>
        <tr>
            <td>#<?= (int) $a['asset_id'] ?></td>
            <td><?= e($a['name']) ?></td>
            <td><?= e($a['category_name'] ?? '—') ?></td>
            <td><?= e($a['serial_no'] ?? '—') ?></td>
            <td><?= e($a['department_name'] ?? '—') ?></td>
            <td><?= e($a['location_name'] ?? '—') ?></td>
            <td><?= formatMoney($a['purchase_cost']) ?></td>
            <td><?= statusBadge($a['status']) ?></td>
            <td class="table-actions">
                <a class="btn btn-sm btn-outline" href="<?= APP_URL ?>/modules/assets/view.php?id=<?= (int) $a['asset_id'] ?>">View</a>
                <?php if ($canEdit): ?>
                    <a class="btn btn-sm btn-outline" href="<?= APP_URL ?>/modules/assets/edit.php?id=<?= (int) $a['asset_id'] ?>">Edit</a>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>

<?php if ($totalPages > 1): ?>
<div class="pagination">
    <?php
    $qs = $_GET;
    for ($p = 1; $p <= $totalPages; $p++):
        $qs['page'] = $p;
        $isCurrent = $p === $page;
    ?>
        <?php if ($isCurrent): ?>
            <span class="current"><?= $p ?></span>
        <?php else: ?>
            <a href="?<?= http_build_query($qs) ?>"><?= $p ?></a>
        <?php endif; ?>
    <?php endfor; ?>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../../includes/layout/footer.php'; ?>
