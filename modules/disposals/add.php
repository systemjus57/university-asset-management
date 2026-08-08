<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requireRole([ROLE_ADMIN, ROLE_OFFICER]);

$errors = [];
$input  = ['asset_id' => '', 'request_date' => date('Y-m-d'), 'method' => 'scrapped', 'reason' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $input = [
        'asset_id'     => $_POST['asset_id'] ?? '',
        'request_date' => $_POST['request_date'] ?? '',
        'method'       => $_POST['method'] ?? '',
        'reason'       => clean($_POST['reason'] ?? ''),
    ];

    $errors = validateRequired($input, [
        'asset_id'     => 'Asset',
        'request_date' => 'Request date',
        'method'       => 'Disposal method',
        'reason'       => 'Reason',
    ]);
    if (!in_array($input['method'], ['sold', 'scrapped', 'donated', 'other'], true)) {
        $errors[] = 'Invalid disposal method.';
    }

    if (!$errors) {
        $check = $pdo->prepare('SELECT status FROM assets WHERE asset_id = :id');
        $check->execute(['id' => $input['asset_id']]);
        $assetStatus = $check->fetchColumn();
        if ($assetStatus === false) {
            $errors[] = 'Selected asset does not exist.';
        } elseif ($assetStatus === 'disposed') {
            $errors[] = 'This asset has already been disposed.';
        } else {
            $pending = $pdo->prepare("SELECT 1 FROM asset_disposals WHERE asset_id = :id AND status = 'pending' LIMIT 1");
            $pending->execute(['id' => $input['asset_id']]);
            if ($pending->fetch()) {
                $errors[] = 'A disposal request for this asset is already pending review.';
            }
        }
    }

    if (!$errors) {
        $stmt = $pdo->prepare(
            'INSERT INTO asset_disposals (asset_id, requested_by, request_date, method, reason, status)
             VALUES (:asset_id, :requested_by, :request_date, :method, :reason, "pending")'
        );
        $stmt->execute([
            'asset_id'     => $input['asset_id'],
            'requested_by' => $_SESSION['user_id'],
            'request_date' => $input['request_date'],
            'method'       => $input['method'],
            'reason'       => $input['reason'],
        ]);
        logActivity($pdo, $_SESSION['user_id'], 'Request Disposal', 'disposals', "Requested disposal of asset #{$input['asset_id']}.");
        flash('success', 'Disposal request submitted for Top Management approval.');
        redirect(APP_URL . '/modules/disposals/list.php');
    }
}

$assets = $pdo->query("SELECT asset_id, name, serial_no FROM assets WHERE status != 'disposed' ORDER BY name")->fetchAll();

$pageTitle  = 'Request Asset Disposal';
$activeMenu = 'disposals';
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
                <label for="request_date">Request Date *</label>
                <input type="date" id="request_date" name="request_date" required value="<?= e($input['request_date']) ?>">
            </div>
            <div class="form-group">
                <label for="method">Disposal Method *</label>
                <select id="method" name="method" required>
                    <option value="sold" <?= $input['method'] === 'sold' ? 'selected' : '' ?>>Sold</option>
                    <option value="scrapped" <?= $input['method'] === 'scrapped' ? 'selected' : '' ?>>Scrapped</option>
                    <option value="donated" <?= $input['method'] === 'donated' ? 'selected' : '' ?>>Donated</option>
                    <option value="other" <?= $input['method'] === 'other' ? 'selected' : '' ?>>Other</option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label for="reason">Reason *</label>
            <textarea id="reason" name="reason" rows="3" required><?= e($input['reason']) ?></textarea>
        </div>
        <div class="d-flex gap-1">
            <button type="submit" class="btn btn-primary">Submit Request</button>
            <a href="<?= APP_URL ?>/modules/disposals/list.php" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>
<?php include __DIR__ . '/../../includes/layout/footer.php'; ?>
