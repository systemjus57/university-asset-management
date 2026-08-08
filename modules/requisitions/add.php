<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requireRole([ROLE_ADMIN, ROLE_HEAD]);

$isHead = hasRole([ROLE_HEAD]);
$errors = [];
$input  = ['department_id' => $isHead ? $_SESSION['department_id'] : '', 'category_id' => '', 'quantity' => 1, 'requisition_date' => date('Y-m-d'), 'purpose' => '', 'description' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $input = [
        'department_id'    => $isHead ? $_SESSION['department_id'] : ($_POST['department_id'] ?? ''),
        'category_id'      => $_POST['category_id'] ?? '',
        'quantity'         => $_POST['quantity'] ?? '',
        'requisition_date' => $_POST['requisition_date'] ?? '',
        'purpose'          => clean($_POST['purpose'] ?? ''),
        'description'      => clean($_POST['description'] ?? ''),
    ];

    $errors = validateRequired($input, [
        'department_id'    => 'Department',
        'quantity'         => 'Quantity',
        'requisition_date' => 'Requisition date',
        'purpose'          => 'Purpose',
    ]);
    if (!empty($input['quantity']) && (!is_numeric($input['quantity']) || (int) $input['quantity'] < 1)) {
        $errors[] = 'Quantity must be a positive number.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare(
            'INSERT INTO requisitions (department_id, requester_id, category_id, quantity, requisition_date, purpose, description, status)
             VALUES (:department_id, :requester_id, :category_id, :quantity, :requisition_date, :purpose, :description, "pending")'
        );
        $stmt->execute([
            'department_id'    => $input['department_id'],
            'requester_id'     => $_SESSION['user_id'],
            'category_id'      => $input['category_id'] !== '' ? $input['category_id'] : null,
            'quantity'         => $input['quantity'],
            'requisition_date' => $input['requisition_date'],
            'purpose'          => $input['purpose'],
            'description'      => $input['description'] !== '' ? $input['description'] : null,
        ]);
        logActivity($pdo, $_SESSION['user_id'], 'Submit Requisition', 'requisitions', 'Submitted a new asset requisition.');
        flash('success', 'Requisition submitted for review.');
        redirect(APP_URL . '/modules/requisitions/list.php');
    }
}

$departments = $pdo->query('SELECT department_id, department_name FROM departments ORDER BY department_name')->fetchAll();
$categories  = $pdo->query('SELECT category_id, category_name FROM categories ORDER BY category_name')->fetchAll();

$pageTitle  = 'New Requisition';
$activeMenu = 'requisitions';
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
        <?php if ($isHead): ?>
            <div class="form-group">
                <label>Department</label>
                <input type="text" value="<?= e($_SESSION['department_name'] ?? '') ?>" disabled>
            </div>
        <?php else: ?>
            <div class="form-group">
                <label for="department_id">Department *</label>
                <select id="department_id" name="department_id" required>
                    <option value="">Select department</option>
                    <?php foreach ($departments as $d): ?>
                        <option value="<?= $d['department_id'] ?>" <?= (string) $input['department_id'] === (string) $d['department_id'] ? 'selected' : '' ?>><?= e($d['department_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>
        <div class="form-row">
            <div class="form-group">
                <label for="category_id">Asset Category</label>
                <select id="category_id" name="category_id">
                    <option value="">Not specific</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= $c['category_id'] ?>" <?= (string) $input['category_id'] === (string) $c['category_id'] ? 'selected' : '' ?>><?= e($c['category_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="quantity">Quantity *</label>
                <input type="number" id="quantity" name="quantity" min="1" required value="<?= e($input['quantity']) ?>">
            </div>
        </div>
        <div class="form-group">
            <label for="requisition_date">Requisition Date *</label>
            <input type="date" id="requisition_date" name="requisition_date" required value="<?= e($input['requisition_date']) ?>">
        </div>
        <div class="form-group">
            <label for="purpose">Purpose *</label>
            <input type="text" id="purpose" name="purpose" required value="<?= e($input['purpose']) ?>">
        </div>
        <div class="form-group">
            <label for="description">Additional Details</label>
            <textarea id="description" name="description" rows="3"><?= e($input['description']) ?></textarea>
        </div>
        <div class="d-flex gap-1">
            <button type="submit" class="btn btn-primary">Submit Requisition</button>
            <a href="<?= APP_URL ?>/modules/requisitions/list.php" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>
<?php include __DIR__ . '/../../includes/layout/footer.php'; ?>
