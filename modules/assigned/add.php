<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requireRole([ROLE_ADMIN, ROLE_OFFICER]);

$errors = [];
$input  = ['asset_id' => '', 'assigned_to' => '', 'assigned_user_id' => '', 'quantity' => '1', 'assigned_date' => date('Y-m-d'), 'remarks' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $input = [
        'asset_id'          => $_POST['asset_id'] ?? '',
        'assigned_to'       => $_POST['assigned_to'] ?? '',
        'assigned_user_id'  => $_POST['assigned_user_id'] ?? '',
        'quantity'          => $_POST['quantity'] ?? '1',
        'assigned_date'     => $_POST['assigned_date'] ?? '',
        'remarks'           => clean($_POST['remarks'] ?? ''),
    ];

    $errors = validateRequired($input, [
        'asset_id'      => 'Asset',
        'assigned_to'   => 'Department',
        'quantity'      => 'Quantity',
        'assigned_date' => 'Assigned date',
    ]);

    $available = null;
    if (!$errors) {
        $check = $pdo->prepare('SELECT status FROM assets WHERE asset_id = :id');
        $check->execute(['id' => $input['asset_id']]);
        $assetStatus = $check->fetchColumn();
        if ($assetStatus === false) {
            $errors[] = 'Selected asset does not exist.';
        } elseif ($assetStatus === 'disposed') {
            $errors[] = 'This asset has been disposed and cannot be assigned.';
        }
    }

    if (!$errors && (!ctype_digit((string) $input['quantity']) || (int) $input['quantity'] < 1)) {
        $errors[] = 'Quantity must be a whole number of at least 1.';
    }

    if (!$errors) {
        $available = getAvailableQuantity($pdo, (int) $input['asset_id']);
        if ((int) $input['quantity'] > $available) {
            $errors[] = "Only $available unit(s) of this asset are available to allocate.";
        }
    }

    // Custodian is optional, but if one is chosen it must actually belong to the
    // selected department — re-checked server-side, not trusted from the client-filtered dropdown.
    if (!$errors && $input['assigned_user_id'] !== '') {
        $custodianCheck = $pdo->prepare('SELECT department_id FROM users WHERE user_id = :id');
        $custodianCheck->execute(['id' => $input['assigned_user_id']]);
        $custodianDept = $custodianCheck->fetchColumn();
        if ($custodianDept === false) {
            $errors[] = 'Selected custodian does not exist.';
        } elseif ((string) $custodianDept !== (string) $input['assigned_to']) {
            $errors[] = 'Selected custodian does not belong to the chosen department.';
        }
    }

    if (!$errors) {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO asset_assigned (asset_id, quantity, assigned_to, assigned_user_id, assigned_by, assigned_date, status, remarks)
                 VALUES (:asset_id, :quantity, :assigned_to, :assigned_user_id, :assigned_by, :assigned_date, "active", :remarks)'
            );
            $stmt->execute([
                'asset_id'         => $input['asset_id'],
                'quantity'         => (int) $input['quantity'],
                'assigned_to'      => $input['assigned_to'],
                'assigned_user_id' => $input['assigned_user_id'] !== '' ? $input['assigned_user_id'] : null,
                'assigned_by'      => $_SESSION['user_id'],
                'assigned_date'    => $input['assigned_date'],
                'remarks'          => $input['remarks'] !== '' ? $input['remarks'] : null,
            ]);
            // Allocation is a temporary custodian assignment, not a permanent move — unlike
            // Transfer, it deliberately does not change assets.department_id (the asset's
            // home department), since a quantity-based asset can be partially allocated to
            // several departments at once while the remainder stays home.
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }

        logActivity($pdo, $_SESSION['user_id'], 'Assign Asset', 'assigned', "Assigned {$input['quantity']} unit(s) of asset #{$input['asset_id']} to department #{$input['assigned_to']}.");
        flash('success', 'Asset assigned successfully.');
        redirect(APP_URL . '/modules/assigned/list.php');
    }
}

$assets = $pdo->query("SELECT asset_id, name, serial_no, quantity FROM assets WHERE status != 'disposed' ORDER BY name")->fetchAll();
foreach ($assets as &$a) {
    $a['available'] = getAvailableQuantity($pdo, (int) $a['asset_id']);
}
unset($a);
$departments = $pdo->query('SELECT department_id, department_name FROM departments ORDER BY department_name')->fetchAll();
$users       = $pdo->query('SELECT user_id, name, department_id FROM users ORDER BY name')->fetchAll();

$pageTitle  = 'Assign Asset';
$activeMenu = 'assigned';
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
                    <option value="<?= $a['asset_id'] ?>" data-available="<?= (int) $a['available'] ?>" <?= (string) $input['asset_id'] === (string) $a['asset_id'] ? 'selected' : '' ?>>
                        <?= e($a['name']) ?><?= $a['serial_no'] ? ' (' . e($a['serial_no']) . ')' : '' ?> — <?= (int) $a['available'] ?> available
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="quantity">Quantity *</label>
            <input type="number" id="quantity" name="quantity" step="1" min="1" required value="<?= e($input['quantity']) ?>" data-quantity-input>
            <p class="text-muted" data-available-hint>Select an asset to see how many units are available.</p>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="assigned_to">Assign To Department *</label>
                <select id="assigned_to" name="assigned_to" required data-department-select>
                    <option value="">Select department</option>
                    <?php foreach ($departments as $d): ?>
                        <option value="<?= $d['department_id'] ?>" <?= (string) $input['assigned_to'] === (string) $d['department_id'] ? 'selected' : '' ?>><?= e($d['department_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="assigned_user_id">Custodian (optional)</label>
                <select id="assigned_user_id" name="assigned_user_id" data-custodian-select>
                    <option value="">Not specific to a person</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?= $u['user_id'] ?>" data-department="<?= (int) $u['department_id'] ?>" <?= (string) $input['assigned_user_id'] === (string) $u['user_id'] ? 'selected' : '' ?>><?= e($u['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="text-muted">Only staff in the selected department are shown.</p>
            </div>
        </div>
        <div class="form-group">
            <label for="assigned_date">Assigned Date *</label>
            <input type="date" id="assigned_date" name="assigned_date" required value="<?= e($input['assigned_date']) ?>">
        </div>
        <div class="form-group">
            <label for="remarks">Remarks</label>
            <textarea id="remarks" name="remarks" rows="2"><?= e($input['remarks']) ?></textarea>
        </div>
        <div class="d-flex gap-1">
            <button type="submit" class="btn btn-primary">Assign Asset</button>
            <a href="<?= APP_URL ?>/modules/assigned/list.php" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>
<script src="<?= APP_URL ?>/static/js/custodian_filter.js"></script>
<script src="<?= APP_URL ?>/static/js/quantity_hint.js"></script>
<?php include __DIR__ . '/../../includes/layout/footer.php'; ?>
