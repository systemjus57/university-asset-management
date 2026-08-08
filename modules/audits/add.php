<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requireRole([ROLE_ADMIN, ROLE_OFFICER]);

$errors = [];
$input  = ['asset_id' => '', 'audit_date' => date('Y-m-d'), 'result' => 'found', 'remarks' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $input = [
        'asset_id'   => $_POST['asset_id'] ?? '',
        'audit_date' => $_POST['audit_date'] ?? '',
        'result'     => $_POST['result'] ?? '',
        'remarks'    => clean($_POST['remarks'] ?? ''),
    ];

    $errors = validateRequired($input, [
        'asset_id'   => 'Asset',
        'audit_date' => 'Audit date',
        'result'     => 'Result',
    ]);
    if (!in_array($input['result'], ['found', 'missing', 'damaged'], true)) {
        $errors[] = 'Invalid audit result.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare(
            'INSERT INTO asset_audits (asset_id, audited_by, audit_date, result, remarks)
             VALUES (:asset_id, :audited_by, :audit_date, :result, :remarks)'
        );
        $stmt->execute([
            'asset_id'   => $input['asset_id'],
            'audited_by' => $_SESSION['user_id'],
            'audit_date' => $input['audit_date'],
            'result'     => $input['result'],
            'remarks'    => $input['remarks'] !== '' ? $input['remarks'] : null,
        ]);
        logActivity($pdo, $_SESSION['user_id'], 'Record Audit', 'audits', "Recorded audit for asset #{$input['asset_id']}: {$input['result']}.");
        flash('success', 'Audit record saved.');
        redirect(APP_URL . '/modules/audits/list.php');
    }
}

$assets = $pdo->query('SELECT asset_id, name, serial_no FROM assets ORDER BY name')->fetchAll();

$pageTitle  = 'Record Asset Audit';
$activeMenu = 'audits';
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
        <div class="form-row">
            <div class="form-group">
                <label for="audit_date">Audit Date *</label>
                <input type="date" id="audit_date" name="audit_date" required value="<?= e($input['audit_date']) ?>">
            </div>
            <div class="form-group">
                <label for="result">Result *</label>
                <select id="result" name="result" required>
                    <option value="found" <?= $input['result'] === 'found' ? 'selected' : '' ?>>Found</option>
                    <option value="missing" <?= $input['result'] === 'missing' ? 'selected' : '' ?>>Missing</option>
                    <option value="damaged" <?= $input['result'] === 'damaged' ? 'selected' : '' ?>>Damaged</option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label for="remarks">Remarks</label>
            <textarea id="remarks" name="remarks" rows="2"><?= e($input['remarks']) ?></textarea>
        </div>
        <div class="d-flex gap-1">
            <button type="submit" class="btn btn-primary">Save Audit</button>
            <a href="<?= APP_URL ?>/modules/audits/list.php" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>
<?php include __DIR__ . '/../../includes/layout/footer.php'; ?>
