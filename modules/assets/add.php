<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requireRole([ROLE_ADMIN, ROLE_OFFICER]);

$errors = [];
$asset  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $asset = [
        'name'             => clean($_POST['name'] ?? ''),
        'category_id'      => $_POST['category_id'] ?? '',
        'serial_no'        => clean($_POST['serial_no'] ?? ''),
        'location_id'      => $_POST['location_id'] ?? '',
        'department_id'    => $_POST['department_id'] ?? '',
        'purchase_date'    => $_POST['purchase_date'] ?? '',
        'purchase_cost'    => $_POST['purchase_cost'] ?? '',
        'quantity'         => $_POST['quantity'] ?? '1',
        'warranty_expiry'  => $_POST['warranty_expiry'] ?? '',
        'description'      => clean($_POST['description'] ?? ''),
    ];

    $errors = validateRequired($asset, [
        'name'          => 'Asset name',
        'category_id'   => 'Category',
        'purchase_date' => 'Purchase date',
        'purchase_cost' => 'Purchase cost',
        'quantity'      => 'Quantity',
    ]);
    if (!is_numeric($asset['purchase_cost'] ?? '')) {
        $errors[] = 'Purchase cost must be a number.';
    }
    if (!ctype_digit((string) $asset['quantity']) || (int) $asset['quantity'] < 1) {
        $errors[] = 'Quantity must be a whole number of at least 1.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare(
            'INSERT INTO assets (name, category_id, serial_no, location_id, department_id, purchase_date, purchase_cost, quantity, warranty_expiry, status, description)
             VALUES (:name, :category_id, :serial_no, :location_id, :department_id, :purchase_date, :purchase_cost, :quantity, :warranty_expiry, "active", :description)'
        );
        $stmt->execute([
            'name'            => $asset['name'],
            'category_id'     => $asset['category_id'],
            'serial_no'       => $asset['serial_no'] !== '' ? $asset['serial_no'] : null,
            'location_id'     => $asset['location_id'] !== '' ? $asset['location_id'] : null,
            'department_id'   => $asset['department_id'] !== '' ? $asset['department_id'] : null,
            'purchase_date'   => $asset['purchase_date'],
            'purchase_cost'   => $asset['purchase_cost'],
            'quantity'        => (int) $asset['quantity'],
            'warranty_expiry' => $asset['warranty_expiry'] !== '' ? $asset['warranty_expiry'] : null,
            'description'     => $asset['description'] !== '' ? $asset['description'] : null,
        ]);
        $newId = (int) $pdo->lastInsertId();
        logActivity($pdo, $_SESSION['user_id'], 'Create Asset', 'assets', "Registered asset #$newId ({$asset['name']}), quantity {$asset['quantity']}.");
        flash('success', 'Asset registered successfully.');
        redirect(APP_URL . '/modules/assets/view.php?id=' . $newId);
    }
}

$categories  = $pdo->query('SELECT category_id, category_name FROM categories ORDER BY category_name')->fetchAll();
$departments = $pdo->query('SELECT department_id, department_name FROM departments ORDER BY department_name')->fetchAll();
$locations   = $pdo->query('SELECT location_id, location_name FROM locations ORDER BY location_name')->fetchAll();

$pageTitle  = 'Add Asset';
$activeMenu = 'assets';
include __DIR__ . '/../../includes/layout/header.php';
?>
<div class="card" style="max-width:820px;">
    <?php include __DIR__ . '/_form.php'; ?>
</div>
<?php include __DIR__ . '/../../includes/layout/footer.php'; ?>
