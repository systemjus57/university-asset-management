<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requireRole([ROLE_ADMIN, ROLE_OFFICER]);

$errors = [];
$input  = ['asset_id' => '', 'to_department_id' => '', 'quantity' => '1', 'transfer_date' => date('Y-m-d'), 'reason' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $input = [
        'asset_id'          => $_POST['asset_id'] ?? '',
        'to_department_id'  => $_POST['to_department_id'] ?? '',
        'quantity'          => $_POST['quantity'] ?? '1',
        'transfer_date'     => $_POST['transfer_date'] ?? '',
        'reason'            => clean($_POST['reason'] ?? ''),
    ];

    $errors = validateRequired($input, [
        'asset_id'         => 'Asset',
        'to_department_id' => 'Destination department',
        'quantity'         => 'Quantity',
        'transfer_date'    => 'Transfer date',
    ]);

    $asset = null;
    if (!$errors) {
        $stmt = $pdo->prepare('SELECT * FROM assets WHERE asset_id = :id');
        $stmt->execute(['id' => $input['asset_id']]);
        $asset = $stmt->fetch();
        if (!$asset) {
            $errors[] = 'Selected asset does not exist.';
        } elseif ($asset['status'] === 'disposed') {
            $errors[] = 'This asset has been disposed and cannot be transferred.';
        } elseif ((string) $asset['department_id'] === (string) $input['to_department_id']) {
            $errors[] = 'Asset is already in the selected department.';
        }
    }

    if (!$errors && (!ctype_digit((string) $input['quantity']) || (int) $input['quantity'] < 1)) {
        $errors[] = 'Quantity must be a whole number of at least 1.';
    }

    $available = null;
    if (!$errors) {
        $available = getAvailableQuantity($pdo, (int) $input['asset_id']);
        if ((int) $input['quantity'] > $available) {
            $errors[] = "Only $available unit(s) of this asset are available to transfer.";
        }
    }

    if (!$errors) {
        $qty = (int) $input['quantity'];
        $pdo->beginTransaction();
        try {
            // Full move: the whole recorded batch is free (nothing else allocated/in
            // maintenance/disposed) and every unit is going to the same destination —
            // exactly today's one-row-per-item behaviour, just relabelled.
            $isFullMove = $qty === (int) $asset['quantity'] && $available === (int) $asset['quantity'];

            if ($isFullMove) {
                $destinationAssetId = (int) $asset['asset_id'];
                $pdo->prepare('UPDATE assets SET department_id = :dept WHERE asset_id = :id')
                    ->execute(['dept' => $input['to_department_id'], 'id' => $asset['asset_id']]);
            } else {
                // Partial move: this batch is splitting across two departments, which a
                // single assets row (one department_id/quantity pair) can't represent.
                // The source row keeps the remaining quantity; a new row is created for
                // the destination department carrying the moved quantity — both rows
                // stay linked to this one transfer record via asset_id/quantity for history.
                $pdo->prepare('UPDATE assets SET quantity = quantity - :qty WHERE asset_id = :id')
                    ->execute(['qty' => $qty, 'id' => $asset['asset_id']]);

                $insert = $pdo->prepare(
                    'INSERT INTO assets (name, category_id, serial_no, location_id, department_id, purchase_date, purchase_cost, quantity, warranty_expiry, status, description)
                     VALUES (:name, :category_id, :serial_no, :location_id, :department_id, :purchase_date, :purchase_cost, :quantity, :warranty_expiry, "active", :description)'
                );
                $insert->execute([
                    'name'            => $asset['name'],
                    'category_id'     => $asset['category_id'],
                    'serial_no'       => $asset['serial_no'],
                    'location_id'     => $asset['location_id'],
                    'department_id'   => $input['to_department_id'],
                    'purchase_date'   => $asset['purchase_date'],
                    'purchase_cost'   => $asset['purchase_cost'],
                    'quantity'        => $qty,
                    'warranty_expiry' => $asset['warranty_expiry'],
                    'description'     => $asset['description'],
                ]);
                $destinationAssetId = (int) $pdo->lastInsertId();
            }

            $stmt = $pdo->prepare(
                'INSERT INTO asset_transfers (asset_id, quantity, from_department_id, to_department_id, transfer_date, handled_by, reason)
                 VALUES (:asset_id, :quantity, :from_dept, :to_dept, :transfer_date, :handled_by, :reason)'
            );
            $stmt->execute([
                'asset_id'      => $asset['asset_id'],
                'quantity'      => $qty,
                'from_dept'     => $asset['department_id'],
                'to_dept'       => $input['to_department_id'],
                'transfer_date' => $input['transfer_date'],
                'handled_by'    => $_SESSION['user_id'],
                'reason'        => $input['reason'] !== '' ? $input['reason'] : null,
            ]);

            recomputeAssetStatus($pdo, (int) $asset['asset_id']);
            if (!$isFullMove) {
                recomputeAssetStatus($pdo, $destinationAssetId);
            }
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }

        logActivity($pdo, $_SESSION['user_id'], 'Transfer Asset', 'transfers', "Transferred $qty unit(s) of asset #{$asset['asset_id']} to department #{$input['to_department_id']}.");

        // Notify the receiving (and, where set, sending) department head that a transfer completed.
        $headStmt = $pdo->prepare('SELECT head_id FROM departments WHERE department_id = :id');
        foreach (array_unique(array_filter([$input['to_department_id'], $asset['department_id']])) as $notifyDeptId) {
            $headStmt->execute(['id' => $notifyDeptId]);
            $headId = $headStmt->fetchColumn();
            if ($headId && (int) $headId !== (int) $_SESSION['user_id']) {
                notifyUser(
                    $pdo, (int) $headId,
                    'Asset transfer completed',
                    "$qty unit(s) of {$asset['name']} transferred to department #{$input['to_department_id']}.",
                    'info', 'transfers', $asset['asset_id']
                );
            }
        }

        flash('success', 'Asset transferred successfully.');
        redirect(APP_URL . '/modules/transfers/list.php');
    }
}

$assets = $pdo->query("SELECT asset_id, name, serial_no, department_id, quantity FROM assets WHERE status != 'disposed' ORDER BY name")->fetchAll();
foreach ($assets as &$a) {
    $a['available'] = getAvailableQuantity($pdo, (int) $a['asset_id']);
}
unset($a);
$departments = $pdo->query('SELECT department_id, department_name FROM departments ORDER BY department_name')->fetchAll();

$pageTitle  = 'Transfer Asset';
$activeMenu = 'transfers';
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
            <p class="text-muted" data-available-hint>Select an asset to see how many units are available. Transferring fewer than the full available amount splits the asset record between departments.</p>
        </div>
        <div class="form-group">
            <label for="to_department_id">Transfer To Department *</label>
            <select id="to_department_id" name="to_department_id" required>
                <option value="">Select department</option>
                <?php foreach ($departments as $d): ?>
                    <option value="<?= $d['department_id'] ?>" <?= (string) $input['to_department_id'] === (string) $d['department_id'] ? 'selected' : '' ?>><?= e($d['department_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="transfer_date">Transfer Date *</label>
            <input type="date" id="transfer_date" name="transfer_date" required value="<?= e($input['transfer_date']) ?>">
        </div>
        <div class="form-group">
            <label for="reason">Reason</label>
            <textarea id="reason" name="reason" rows="2"><?= e($input['reason']) ?></textarea>
        </div>
        <div class="d-flex gap-1">
            <button type="submit" class="btn btn-primary">Transfer Asset</button>
            <a href="<?= APP_URL ?>/modules/transfers/list.php" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>
<script src="<?= APP_URL ?>/static/js/quantity_hint.js"></script>
<?php include __DIR__ . '/../../includes/layout/footer.php'; ?>
