<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requireRole([ROLE_ADMIN, ROLE_OFFICER, ROLE_HEAD]);

$isHead = hasRole([ROLE_HEAD]);
$errors = [];
$input  = ['asset_id' => '', 'issue_description' => '', 'reported_date' => date('Y-m-d')];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $input = [
        'asset_id'          => $_POST['asset_id'] ?? '',
        'issue_description' => clean($_POST['issue_description'] ?? ''),
        'reported_date'     => $_POST['reported_date'] ?? '',
    ];

    $errors = validateRequired($input, [
        'asset_id'          => 'Asset',
        'issue_description' => 'Issue description',
        'reported_date'     => 'Reported date',
    ]);

    if (!$errors && $isHead) {
        $check = $pdo->prepare('SELECT department_id FROM assets WHERE asset_id = :id');
        $check->execute(['id' => $input['asset_id']]);
        $deptId = $check->fetchColumn();
        if ((string) $deptId !== (string) $_SESSION['department_id']) {
            $errors[] = 'You can only report issues for assets in your own department.';
        }
    }

    if (!$errors) {
        $stmt = $pdo->prepare(
            'INSERT INTO asset_maintenance (asset_id, issue_description, reported_by, reported_date, status)
             VALUES (:asset_id, :issue_description, :reported_by, :reported_date, "pending")'
        );
        $stmt->execute([
            'asset_id'          => $input['asset_id'],
            'issue_description' => $input['issue_description'],
            'reported_by'       => $_SESSION['user_id'],
            'reported_date'     => $input['reported_date'],
        ]);
        recomputeAssetStatus($pdo, (int) $input['asset_id']);
        logActivity($pdo, $_SESSION['user_id'], 'Report Maintenance Issue', 'maintenance', "Reported issue for asset #{$input['asset_id']}.");
        flash('success', 'Maintenance issue reported successfully.');
        redirect(APP_URL . '/modules/maintenance/list.php');
    }
}

if ($isHead) {
    $stmt = $pdo->prepare("SELECT asset_id, name, serial_no FROM assets WHERE status != 'disposed' AND department_id = :dept ORDER BY name");
    $stmt->execute(['dept' => $_SESSION['department_id']]);
    $assets = $stmt->fetchAll();
} else {
    $assets = $pdo->query("SELECT asset_id, name, serial_no FROM assets WHERE status != 'disposed' ORDER BY name")->fetchAll();
}

$pageTitle  = 'Report Maintenance Issue';
$activeMenu = 'maintenance';
include __DIR__ . '/../../includes/layout/header.php';
?>
<div class="card" style="max-width:700px;">
    <?php if ($errors): ?>
        <div class="alert alert-error">
            <ul style="margin:0; padding-left:1.1rem;"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>
    <form method="post" action="" novalidate>
        <?= csrfField() ?>
        <div class="form-group">
            <label for="asset_id">Asset *</label>
            <select id="asset_id" name="asset_id" required>
                <option value="">Select asset</option>
                <?php foreach ($assets as $a): ?>
                    <option value="<?= $a['asset_id'] ?>" <?= (string) $input['asset_id'] === (string) $a['asset_id'] ? 'selected' : '' ?>>
                        <?= e($a['name']) ?><?= $a['serial_no'] ? ' (' . e($a['serial_no']) . ')' : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="issue_description">Issue Description *</label>
            <textarea id="issue_description" name="issue_description" rows="3" required><?= e($input['issue_description']) ?></textarea>
        </div>
        <div class="form-group">
            <label for="reported_date">Reported Date *</label>
            <input type="date" id="reported_date" name="reported_date" required value="<?= e($input['reported_date']) ?>">
        </div>
        <div class="d-flex gap-1">
            <button type="submit" class="btn btn-primary">Report Issue</button>
            <a href="<?= APP_URL ?>/modules/maintenance/list.php" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>
<?php include __DIR__ . '/../../includes/layout/footer.php'; ?>
