<?php
/**
 * Shared form fields for add.php / edit.php.
 * Expects: $asset (array of current values, empty array for a new asset),
 * $categories, $departments, $locations, $errors (array),
 * $committedQuantity (int, only set on edit — quantity already allocated/
 * in maintenance/disposed, used as the minimum allowed on save).
 */
?>
<?php if ($errors): ?>
    <div class="alert alert-error">
        <ul style="margin:0; padding-left:1.1rem;">
            <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="post" action="" novalidate>
    <?= csrfField() ?>
    <div class="form-row">
        <div class="form-group">
            <label for="name">Asset Name *</label>
            <input type="text" id="name" name="name" required value="<?= e($asset['name'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="category_id">Category *</label>
            <select id="category_id" name="category_id" required>
                <option value="">Select category</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= $c['category_id'] ?>" <?= (string) ($asset['category_id'] ?? '') === (string) $c['category_id'] ? 'selected' : '' ?>><?= e($c['category_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label for="serial_no">Serial No. (optional)</label>
            <input type="text" id="serial_no" name="serial_no" value="<?= e($asset['serial_no'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="location_id">Location</label>
            <select id="location_id" name="location_id">
                <option value="">Not set</option>
                <?php foreach ($locations as $l): ?>
                    <option value="<?= $l['location_id'] ?>" <?= (string) ($asset['location_id'] ?? '') === (string) $l['location_id'] ? 'selected' : '' ?>><?= e($l['location_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label for="department_id">Custodian Department</label>
            <select id="department_id" name="department_id">
                <option value="">Not assigned</option>
                <?php foreach ($departments as $d): ?>
                    <option value="<?= $d['department_id'] ?>" <?= (string) ($asset['department_id'] ?? '') === (string) $d['department_id'] ? 'selected' : '' ?>><?= e($d['department_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Status</label>
            <div><?= statusBadge($asset['status'] ?? 'active') ?></div>
            <small class="text-muted">Derived automatically from quantity, allocations, maintenance, and disposal records — not editable directly.</small>
        </div>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label for="purchase_date">Purchase Date *</label>
            <input type="date" id="purchase_date" name="purchase_date" required value="<?= e($asset['purchase_date'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="purchase_cost">Purchase Cost per Unit (USD) *</label>
            <input type="number" id="purchase_cost" name="purchase_cost" step="0.01" min="0" required value="<?= e($asset['purchase_cost'] ?? '') ?>">
        </div>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label for="quantity">Quantity *</label>
            <input type="number" id="quantity" name="quantity" step="1" min="<?= max(1, (int) ($committedQuantity ?? 1)) ?>" required value="<?= e($asset['quantity'] ?? '1') ?>">
            <?php if (!empty($committedQuantity)): ?>
                <small class="text-muted"><?= (int) $committedQuantity ?> unit<?= $committedQuantity == 1 ? '' : 's' ?> currently allocated, in maintenance, or disposed — quantity can't go below that.</small>
            <?php endif; ?>
        </div>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label for="warranty_expiry">Warranty Expiry</label>
            <input type="date" id="warranty_expiry" name="warranty_expiry" value="<?= e($asset['warranty_expiry'] ?? '') ?>">
        </div>
    </div>

    <div class="form-group">
        <label for="description">Description</label>
        <textarea id="description" name="description" rows="3"><?= e($asset['description'] ?? '') ?></textarea>
    </div>

    <div class="d-flex gap-1">
        <button type="submit" class="btn btn-primary"><?= isset($asset['asset_id']) ? 'Save Changes' : 'Add Asset' ?></button>
        <a href="<?= APP_URL ?>/modules/assets/list.php" class="btn btn-outline">Cancel</a>
    </div>
</form>
