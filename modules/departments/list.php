<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requireRole([ROLE_ADMIN]);

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $name     = clean($_POST['department_name'] ?? '');
        $headId   = $_POST['head_id'] ?? '';
        $location = clean($_POST['location'] ?? '');
        $deptId   = (int) ($_POST['department_id'] ?? 0);

        if ($name === '') {
            $errors[] = 'Department name is required.';
        }

        if (!$errors) {
            if ($action === 'create') {
                $pdo->prepare('INSERT INTO departments (department_name, head_id, location) VALUES (:n, :h, :l)')
                    ->execute(['n' => $name, 'h' => $headId !== '' ? $headId : null, 'l' => $location !== '' ? $location : null]);
                logActivity($pdo, $_SESSION['user_id'], 'Create Department', 'departments', "Created department: $name.");
                flash('success', 'Department created.');
            } else {
                $pdo->prepare('UPDATE departments SET department_name = :n, head_id = :h, location = :l WHERE department_id = :id')
                    ->execute(['n' => $name, 'h' => $headId !== '' ? $headId : null, 'l' => $location !== '' ? $location : null, 'id' => $deptId]);
                logActivity($pdo, $_SESSION['user_id'], 'Update Department', 'departments', "Updated department #$deptId.");
                flash('success', 'Department updated.');
            }
            redirect(APP_URL . '/modules/departments/list.php');
        }
    } elseif ($action === 'delete') {
        $deptId = (int) ($_POST['department_id'] ?? 0);
        try {
            $pdo->prepare('DELETE FROM departments WHERE department_id = :id')->execute(['id' => $deptId]);
            logActivity($pdo, $_SESSION['user_id'], 'Delete Department', 'departments', "Deleted department #$deptId.");
            flash('success', 'Department deleted.');
        } catch (PDOException $e) {
            flash('error', 'This department cannot be deleted because it still has assets, users, or history linked to it.');
        }
        redirect(APP_URL . '/modules/departments/list.php');
    }
}

$departments = $pdo->query(
    'SELECT d.*, u.name AS head_name,
     (SELECT COUNT(*) FROM assets a WHERE a.department_id = d.department_id) AS asset_count
     FROM departments d LEFT JOIN users u ON u.user_id = d.head_id ORDER BY d.department_name'
)->fetchAll();
$potentialHeads = $pdo->query("SELECT user_id, name FROM users WHERE role_id IN (SELECT role_id FROM roles WHERE role_name = 'Department Head') ORDER BY name")->fetchAll();

$pageTitle  = 'Departments';
$activeMenu = 'departments';
include __DIR__ . '/../../includes/layout/header.php';
?>
<div class="card-header">
    <div></div>
    <button type="button" class="btn btn-primary" data-modal-target="modalCreateDept">+ Add Department</button>
</div>

<?php if ($errors): ?>
    <div class="alert alert-error"><ul style="margin:0; padding-left:1.1rem;"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<div class="table-wrap">
<table id="deptTable">
    <thead><tr><th data-sort>Name</th><th data-sort>Head</th><th data-sort>Location</th><th data-sort>Assets</th><th class="no-sort">Actions</th></tr></thead>
    <tbody>
    <?php if (!$departments): ?><tr class="empty-row"><td colspan="5">No departments yet.</td></tr><?php endif; ?>
    <?php foreach ($departments as $d): ?>
        <tr>
            <td><?= e($d['department_name']) ?></td>
            <td><?= e($d['head_name'] ?? '—') ?></td>
            <td><?= e($d['location'] ?? '—') ?></td>
            <td><?= (int) $d['asset_count'] ?></td>
            <td class="table-actions">
                <button type="button" class="btn btn-sm btn-outline" data-modal-target="modalEditDept<?= $d['department_id'] ?>">Edit</button>
                <form method="post" action="" style="display:inline;" data-confirm="Delete department '<?= e($d['department_name']) ?>'? This cannot be undone.">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="department_id" value="<?= (int) $d['department_id'] ?>">
                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                </form>
            </td>
        </tr>

        <div class="modal-overlay" id="modalEditDept<?= $d['department_id'] ?>">
            <div class="modal">
                <div class="modal-header"><h3>Edit Department</h3><button type="button" class="modal-close" data-modal-close>&times;</button></div>
                <form method="post" action="">
                    <div class="modal-body">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="department_id" value="<?= (int) $d['department_id'] ?>">
                        <div class="form-group"><label>Department Name *</label><input type="text" name="department_name" required value="<?= e($d['department_name']) ?>"></div>
                        <div class="form-group">
                            <label>Head of Department</label>
                            <select name="head_id">
                                <option value="">None</option>
                                <?php foreach ($potentialHeads as $h): ?>
                                    <option value="<?= $h['user_id'] ?>" <?= (string) $d['head_id'] === (string) $h['user_id'] ? 'selected' : '' ?>><?= e($h['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group"><label>Location</label><input type="text" name="location" value="<?= e($d['location'] ?? '') ?>"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
    </tbody>
</table>
</div>

<div class="modal-overlay" id="modalCreateDept">
    <div class="modal">
        <div class="modal-header"><h3>Add Department</h3><button type="button" class="modal-close" data-modal-close>&times;</button></div>
        <form method="post" action="">
            <div class="modal-body">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="create">
                <div class="form-group"><label>Department Name *</label><input type="text" name="department_name" required></div>
                <div class="form-group">
                    <label>Head of Department</label>
                    <select name="head_id">
                        <option value="">None</option>
                        <?php foreach ($potentialHeads as $h): ?>
                            <option value="<?= $h['user_id'] ?>"><?= e($h['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label>Location</label><input type="text" name="location"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary">Add Department</button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../includes/layout/footer.php'; ?>
