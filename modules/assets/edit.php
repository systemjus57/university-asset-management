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
        'quantity'         => $_POST['quantity'] ?? '1',
        'warranty_expiry'  => $_POST['warranty_expiry'] ?? '',
        'description'      => clean($_POST['description'] ?? ''),
    ];

    $errors = validateRequired($input, [
        'name'          => 'Asset name',
        'category_id'   => 'Category',
        'purchase_date' => 'Purchase date',
        'purchase_cost' => 'Purchase cost',
        'quantity'      => 'Quantity',
    ]);
    if (!is_numeric($input['purchase_cost'] ?? '')) {
        $errors[] = 'Purchase cost must be a number.';
    }

    // Quantity can never drop below what's already committed (active allocations +
    // open maintenance + approved disposals) — that would make the ledger negative.
    $committedQuantity = (int) $asset['quantity'] - getAvailableQuantity($pdo, $assetId);
    if (!ctype_digit((string) $input['quantity']) || (int) $input['quantity'] < 1) {
        $errors[] = 'Quantity must be a whole number of at least 1.';
    } elseif ((int) $input['quantity'] < $committedQuantity) {
        $errors[] = "Quantity can't be less than $committedQuantity, the number of units already allocated, in maintenance, or disposed.";
    }

    if (!$errors) {
        $stmt = $pdo->prepare(
            'UPDATE assets SET name = :name, category_id = :category_id, serial_no = :serial_no,
             location_id = :location_id, department_id = :department_id, purchase_date = :purchase_date,
             purchase_cost = :purchase_cost, quantity = :quantity, warranty_expiry = :warranty_expiry, description = :description
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
            'quantity'        => (int) $input['quantity'],
            'warranty_expiry' => $input['warranty_expiry'] !== '' ? $input['warranty_expiry'] : null,
            'description'     => $input['description'] !== '' ? $input['description'] : null,
            'id'              => $assetId,
        ]);
        recomputeAssetStatus($pdo, $assetId);
        logActivity($pdo, $_SESSION['user_id'], 'Update Asset', 'assets', "Updated asset #$assetId ({$input['name']}).");
        flash('success', 'Asset updated successfully.');
        redirect(APP_URL . '/modules/assets/view.php?id=' . $assetId);
    }
    $asset = array_merge($asset, $input);
}

$committedQuantity = $committedQuantity ?? ($asset['quantity'] - getAvailableQuantity($pdo, $assetId));
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
