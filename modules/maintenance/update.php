<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requireRole([ROLE_ADMIN, ROLE_OFFICER]);

$id = (int) ($_GET['id'] ?? $_POST['maintenance_id'] ?? 0);
$stmt = $pdo->prepare(
    'SELECT m.*, a.name AS asset_name FROM asset_maintenance m JOIN assets a ON a.asset_id = m.asset_id WHERE m.maintenance_id = :id'
);
$stmt->execute(['id' => $id]);
$record = $stmt->fetch();

if (!$record) {
    flash('error', 'Maintenance record not found.');
    redirect(APP_URL . '/modules/maintenance/list.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $status            = $_POST['status'] ?? 'pending';
    $completedDate     = $_POST['completed_date'] ?? '';
    $cost              = $_POST['cost'] ?? '';
    $technicianVendor  = clean($_POST['technician_vendor'] ?? '');

    if (!in_array($status, ['pending', 'in_progress', 'completed'], true)) {
        $errors[] = 'Invalid status.';
    }
    if ($status === 'completed' && $completedDate === '') {
        $errors[] = 'Completed date is required when marking as completed.';
    }
    if ($cost !== '' && !is_numeric($cost)) {
        $errors[] = 'Cost must be a number.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare(
            'UPDATE asset_maintenance SET status = :status, completed_date = :completed_date, cost = :cost, technician_vendor = :tech
             WHERE maintenance_id = :id'
        );
        $stmt->execute([
            'status'         => $status,
            'completed_date' => $status === 'completed' ? $completedDate : null,
            'cost'           => $cost !== '' ? $cost : null,
            'tech'           => $technicianVendor !== '' ? $technicianVendor : null,
            'id'             => $id,
        ]);
        recomputeAssetStatus($pdo, (int) $record['asset_id']);
        logActivity($pdo, $_SESSION['user_id'], 'Update Maintenance', 'maintenance', "Updated maintenance #$id to status $status.");
        flash('success', 'Maintenance record updated.');
        redirect(APP_URL . '/modules/maintenance/list.php');
    }
    $record = array_merge($record, compact('status', 'completedDate', 'cost', 'technicianVendor'));
}

$pageTitle  = 'Update Maintenance';
$activeMenu = 'maintenance';
include __DIR__ . '/../../includes/layout/header.php';
?>
<div class="card" style="max-width:640px;">
    <h3>Asset: <?= e($record['asset_name']) ?></h3>
    <p class="text-muted"><?= e($record['issue_description']) ?></p>

    <?php if ($errors): ?>
        <div class="alert alert-error">
            <ul style="margin:0; padding-left:1.1rem;"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>

    <form method="post" action="" novalidate>
        <?= csrfField() ?>
        <input type="hidden" name="maintenance_id" value="<?= (int) $record['maintenance_id'] ?>">
        <div class="form-group">
            <label for="status">Status *</label>
            <select id="status" name="status" required>
                <option value="pending" <?= $record['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="in_progress" <?= $record['status'] === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                <option value="completed" <?= $record['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
            </select>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="completed_date">Completed Date</label>
                <input type="date" id="completed_date" name="completed_date" value="<?= e($record['completed_date'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="cost">Cost (USD)</label>
                <input type="number" id="cost" name="cost" step="0.01" min="0" value="<?= e($record['cost'] ?? '') ?>">
            </div>
        </div>
        <div class="form-group">
            <label for="technician_vendor">Technician / Vendor</label>
            <input type="text" id="technician_vendor" name="technician_vendor" value="<?= e($record['technician_vendor'] ?? '') ?>">
        </div>
        <div class="d-flex gap-1">
            <button type="submit" class="btn btn-primary">Save Update</button>
            <a href="<?= APP_URL ?>/modules/maintenance/list.php" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>
<?php include __DIR__ . '/../../includes/layout/footer.php'; ?>
