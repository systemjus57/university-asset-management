<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requireRole([ROLE_ADMIN, ROLE_OFFICER]);

$assetId = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM assets WHERE asset_id = :id');
$stmt->execute(['id' => $assetId]);
$asset = $stmt->fetch();

if (!$asset) {
    flash('error', 'Asset not found.');
    redirect(APP_URL . '/modules/assets/list.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $input = [
        'name'             => clean($_POST['name'] ?? ''),
        'category_id'      => $_POST['category_id'] ?? '',
        'serial_no'        => clean($_POST['serial_no'] ?? ''),
        'location_id'      => $_POST['location_id'] ?? '',
        'department_id'    => $_POST['department_id'] ?? '',
        'purchase_date'    => $_POST['purchase_date'] ?? '',
        'purchase_cost'    => $_POST['purchase_cost'] ?? '',
        'warranty_expiry'  => $_POST['warranty_expiry'] ?? '',
        'status'           => $_POST['status'] ?? 'active',
        'description'      => clean($_POST['description'] ?? ''),
    ];

    $errors = validateRequired($input, [
        'name'          => 'Asset name',
        'category_id'   => 'Category',
        'purchase_date' => 'Purchase date',
        'purchase_cost' => 'Purchase cost',
    ]);
    if (!is_numeric($input['purchase_cost'] ?? '')) {
        $errors[] = 'Purchase cost must be a number.';
    }
    if (!in_array($input['status'], ['active', 'under_repair', 'disposed'], true)) {
        $errors[] = 'Invalid status selected.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare(
            'UPDATE assets SET name = :name, category_id = :category_id, serial_no = :serial_no,
             location_id = :location_id, department_id = :department_id, purchase_date = :purchase_date,
             purchase_cost = :purchase_cost, warranty_expiry = :warranty_expiry, status = :status, description = :description
             WHERE asset_id = :id'
        );
        $stmt->execute([
            'name'            => $input['name'],
            'category_id'     => $input['category_id'],
            'serial_no'       => $input['serial_no'] !== '' ? $input['serial_no'] : null,
            'location_id'     => $input['location_id'] !== '' ? $input['location_id'] : null,
            'department_id'   => $input['department_id'] !== '' ? $input['department_id'] : null,
            'purchase_date'   => $input['purchase_date'],
            'purchase_cost'   => $input['purchase_cost'],
            'warranty_expiry' => $input['warranty_expiry'] !== '' ? $input['warranty_expiry'] : null,
            'status'          => $input['status'],
            'description'     => $input['description'] !== '' ? $input['description'] : null,
            'id'              => $assetId,
        ]);
        logActivity($pdo, $_SESSION['user_id'], 'Update Asset', 'assets', "Updated asset #$assetId ({$input['name']}).");
        flash('success', 'Asset updated successfully.');
        redirect(APP_URL . '/modules/assets/view.php?id=' . $assetId);
    }
    $asset = array_merge($asset, $input);
}

$categories  = $pdo->query('SELECT category_id, category_name FROM categories ORDER BY category_name')->fetchAll();
$departments = $pdo->query('SELECT department_id, department_name FROM departments ORDER BY department_name')->fetchAll();
$locations   = $pdo->query('SELECT location_id, location_name FROM locations ORDER BY location_name')->fetchAll();

$pageTitle  = 'Edit Asset';
$activeMenu = 'assets';
include __DIR__ . '/../../includes/layout/header.php';
?>
<div class="card" style="max-width:820px;">
    <?php include __DIR__ . '/_form.php'; ?>
</div>
<?php include __DIR__ . '/../../includes/layout/footer.php'; ?>
