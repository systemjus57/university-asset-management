<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requireRole([ROLE_ADMIN]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $name = clean($_POST['category_name'] ?? '');
        $desc = clean($_POST['description'] ?? '');
        $id   = (int) ($_POST['category_id'] ?? 0);

        if ($name === '') {
            flash('error', 'Category name is required.');
        } else {
            if ($action === 'create') {
                $pdo->prepare('INSERT INTO categories (category_name, description) VALUES (:n, :d)')
                    ->execute(['n' => $name, 'd' => $desc !== '' ? $desc : null]);
                logActivity($pdo, $_SESSION['user_id'], 'Create Category', 'categories', "Created category: $name.");
                flash('success', 'Category created.');
            } else {
                $pdo->prepare('UPDATE categories SET category_name = :n, description = :d WHERE category_id = :id')
                    ->execute(['n' => $name, 'd' => $desc !== '' ? $desc : null, 'id' => $id]);
                logActivity($pdo, $_SESSION['user_id'], 'Update Category', 'categories', "Updated category #$id.");
                flash('success', 'Category updated.');
            }
        }
        redirect(APP_URL . '/modules/categories/list.php');
    } elseif ($action === 'delete') {
        $id = (int) ($_POST['category_id'] ?? 0);
        try {
            $pdo->prepare('DELETE FROM categories WHERE category_id = :id')->execute(['id' => $id]);
            logActivity($pdo, $_SESSION['user_id'], 'Delete Category', 'categories', "Deleted category #$id.");
            flash('success', 'Category deleted.');
        } catch (PDOException $e) {
            flash('error', 'This category cannot be deleted because assets are still assigned to it.');
        }
        redirect(APP_URL . '/modules/categories/list.php');
    }
}

$categories = $pdo->query(
    'SELECT c.*, (SELECT COUNT(*) FROM assets a WHERE a.category_id = c.category_id) AS asset_count
     FROM categories c ORDER BY c.category_name'
)->fetchAll();

$pageTitle  = 'Categories';
$activeMenu = 'categories';
include __DIR__ . '/../../includes/layout/header.php';
?>
<div class="card-header">
    <div></div>
    <button type="button" class="btn btn-primary" data-modal-target="modalCreateCat">+ Add Category</button>
</div>

<div class="table-wrap">
<table id="catTable">
    <thead><tr><th data-sort>Name</th><th class="no-sort">Description</th><th data-sort>Assets</th><th class="no-sort">Actions</th></tr></thead>
    <tbody>
    <?php if (!$categories): ?><tr class="empty-row"><td colspan="4">No categories yet.</td></tr><?php endif; ?>
    <?php foreach ($categories as $c): ?>
        <tr>
            <td><?= e($c['category_name']) ?></td>
            <td><?= e($c['description'] ?? '—') ?></td>
            <td><?= (int) $c['asset_count'] ?></td>
            <td class="table-actions">
                <button type="button" class="btn btn-sm btn-outline" data-modal-target="modalEditCat<?= $c['category_id'] ?>">Edit</button>
                <form method="post" action="" style="display:inline;" data-confirm="Delete category '<?= e($c['category_name']) ?>'?">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="category_id" value="<?= (int) $c['category_id'] ?>">
                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                </form>
            </td>
        </tr>
        <div class="modal-overlay" id="modalEditCat<?= $c['category_id'] ?>">
            <div class="modal">
                <div class="modal-header"><h3>Edit Category</h3><button type="button" class="modal-close" data-modal-close>&times;</button></div>
                <form method="post" action="">
                    <div class="modal-body">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="category_id" value="<?= (int) $c['category_id'] ?>">
                        <div class="form-group"><label>Category Name *</label><input type="text" name="category_name" required value="<?= e($c['category_name']) ?>"></div>
                        <div class="form-group"><label>Description</label><textarea name="description" rows="2"><?= e($c['description'] ?? '') ?></textarea></div>
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

<div class="modal-overlay" id="modalCreateCat">
    <div class="modal">
        <div class="modal-header"><h3>Add Category</h3><button type="button" class="modal-close" data-modal-close>&times;</button></div>
        <form method="post" action="">
            <div class="modal-body">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="create">
                <div class="form-group"><label>Category Name *</label><input type="text" name="category_name" required></div>
                <div class="form-group"><label>Description</label><textarea name="description" rows="2"></textarea></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary">Add Category</button>
            </div>
        </form>
    </div>
</div>
<?php include __DIR__ . '/../../includes/layout/footer.php'; ?>
