<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requireRole([ROLE_ADMIN, ROLE_OFFICER]);

$errors = [];
$input  = ['asset_id' => '', 'to_department_id' => '', 'transfer_date' => date('Y-m-d'), 'reason' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $input = [
        'asset_id'          => $_POST['asset_id'] ?? '',
        'to_department_id'  => $_POST['to_department_id'] ?? '',
        'transfer_date'     => $_POST['transfer_date'] ?? '',
        'reason'            => clean($_POST['reason'] ?? ''),
    ];

    $errors = validateRequired($input, [
        'asset_id'         => 'Asset',
        'to_department_id' => 'Destination department',
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

    if (!$errors) {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO asset_transfers (asset_id, from_department_id, to_department_id, transfer_date, handled_by, reason)
                 VALUES (:asset_id, :from_dept, :to_dept, :transfer_date, :handled_by, :reason)'
            );
            $stmt->execute([
                'asset_id'   => $input['asset_id'],
                'from_dept'  => $asset['department_id'],
                'to_dept'    => $input['to_department_id'],
                'transfer_date' => $input['transfer_date'],
                'handled_by' => $_SESSION['user_id'],
                'reason'     => $input['reason'] !== '' ? $input['reason'] : null,
            ]);
            $pdo->prepare('UPDATE assets SET department_id = :dept WHERE asset_id = :id')
                ->execute(['dept' => $input['to_department_id'], 'id' => $input['asset_id']]);
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }

        logActivity($pdo, $_SESSION['user_id'], 'Transfer Asset', 'transfers', "Transferred asset #{$input['asset_id']} to department #{$input['to_department_id']}.");
        flash('success', 'Asset transferred successfully.');
        redirect(APP_URL . '/modules/transfers/list.php');
    }
}

$assets      = $pdo->query("SELECT asset_id, name, serial_no, department_id FROM assets WHERE status != 'disposed' ORDER BY name")->fetchAll();
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
<?php include __DIR__ . '/../../includes/layout/footer.php'; ?>
