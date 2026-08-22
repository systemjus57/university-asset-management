<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requireRole([ROLE_ADMIN, ROLE_OFFICER]);

$errors = [];
$input  = ['asset_id' => '', 'audit_date' => date('Y-m-d'), 'expected_quantity' => '', 'actual_quantity' => '', 'damaged_quantity' => '0', 'result' => 'found', 'remarks' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $input = [
        'asset_id'          => $_POST['asset_id'] ?? '',
        'audit_date'        => $_POST['audit_date'] ?? '',
        'expected_quantity' => $_POST['expected_quantity'] ?? '',
        'actual_quantity'   => $_POST['actual_quantity'] ?? '',
        'damaged_quantity'  => $_POST['damaged_quantity'] ?? '0',
        'result'            => $_POST['result'] ?? '',
        'remarks'           => clean($_POST['remarks'] ?? ''),
    ];

    $errors = validateRequired($input, [
        'asset_id'          => 'Asset',
        'audit_date'        => 'Audit date',
        'expected_quantity' => 'Expected quantity',
        'actual_quantity'   => 'Actual quantity found',
        'result'            => 'Result',
    ]);
    if (!in_array($input['result'], ['found', 'missing', 'damaged'], true)) {
        $errors[] = 'Invalid audit result.';
    }
    foreach (['expected_quantity' => 'Expected quantity', 'actual_quantity' => 'Actual quantity', 'damaged_quantity' => 'Damaged quantity'] as $field => $label) {
        if ($input[$field] !== '' && (!ctype_digit((string) $input[$field]) || (int) $input[$field] < 0)) {
            $errors[] = "$label must be a whole number of 0 or more.";
        }
    }
    if (!$errors && (int) $input['damaged_quantity'] > (int) $input['actual_quantity']) {
        $errors[] = 'Damaged quantity cannot exceed the actual quantity found.';
    }

    $missingQuantity = null;
    if (!$errors) {
        $missingQuantity = max(0, (int) $input['expected_quantity'] - (int) $input['actual_quantity']);
        $stmt = $pdo->prepare(
            'INSERT INTO asset_audits (asset_id, expected_quantity, actual_quantity, missing_quantity, damaged_quantity, audited_by, audit_date, result, remarks)
             VALUES (:asset_id, :expected, :actual, :missing, :damaged, :audited_by, :audit_date, :result, :remarks)'
        );
        $stmt->execute([
            'asset_id'   => $input['asset_id'],
            'expected'   => (int) $input['expected_quantity'],
            'actual'     => (int) $input['actual_quantity'],
            'missing'    => $missingQuantity,
            'damaged'    => (int) $input['damaged_quantity'],
            'audited_by' => $_SESSION['user_id'],
            'audit_date' => $input['audit_date'],
            'result'     => $input['result'],
            'remarks'    => $input['remarks'] !== '' ? $input['remarks'] : null,
        ]);
        logActivity($pdo, $_SESSION['user_id'], 'Record Audit', 'audits', "Recorded audit for asset #{$input['asset_id']}: expected {$input['expected_quantity']}, found {$input['actual_quantity']}, missing $missingQuantity, damaged {$input['damaged_quantity']}.");
        flash('success', 'Audit record saved.');
        redirect(APP_URL . '/modules/audits/list.php');
    }
}

$assets = $pdo->query('SELECT asset_id, name, serial_no, quantity FROM assets ORDER BY name')->fetchAll();

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
            <select id="asset_id" name="asset_id" required data-asset-select>
                <option value="">Select asset</option>
                <?php foreach ($assets as $a): ?>
                    <option value="<?= $a['asset_id'] ?>" data-quantity="<?= (int) $a['quantity'] ?>" <?= (string) $input['asset_id'] === (string) $a['asset_id'] ? 'selected' : '' ?>>
                        <?= e($a['name']) ?><?= $a['serial_no'] ? ' (' . e($a['serial_no']) . ')' : '' ?> — qty <?= (int) $a['quantity'] ?>
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
        <div class="form-row">
            <div class="form-group">
                <label for="expected_quantity">Expected Quantity *</label>
                <input type="number" id="expected_quantity" name="expected_quantity" step="1" min="0" required value="<?= e($input['expected_quantity']) ?>" data-expected-quantity>
            </div>
            <div class="form-group">
                <label for="actual_quantity">Actual Quantity Found *</label>
                <input type="number" id="actual_quantity" name="actual_quantity" step="1" min="0" required value="<?= e($input['actual_quantity']) ?>">
            </div>
            <div class="form-group">
                <label for="damaged_quantity">Damaged Quantity</label>
                <input type="number" id="damaged_quantity" name="damaged_quantity" step="1" min="0" value="<?= e($input['damaged_quantity']) ?>">
            </div>
        </div>
        <p class="text-muted">Missing quantity is calculated automatically as Expected − Actual.</p>
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
<script>
(function () {
    var assetSelect = document.querySelector('[data-asset-select]');
    var expected = document.querySelector('[data-expected-quantity]');
    if (!assetSelect || !expected) return;
    assetSelect.addEventListener('change', function () {
        if (expected.value !== '') return;
        var opt = assetSelect.options[assetSelect.selectedIndex];
        var qty = opt ? opt.getAttribute('data-quantity') : null;
        if (qty !== null) expected.value = qty;
    });
})();
</script>
<?php include __DIR__ . '/../../includes/layout/footer.php'; ?>
