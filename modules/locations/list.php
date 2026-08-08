<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requireRole([ROLE_ADMIN]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $name     = clean($_POST['location_name'] ?? '');
        $building = clean($_POST['building'] ?? '');
        $room     = clean($_POST['room'] ?? '');
        $id       = (int) ($_POST['location_id'] ?? 0);

        if ($name === '') {
            flash('error', 'Location name is required.');
        } else {
            if ($action === 'create') {
                $pdo->prepare('INSERT INTO locations (location_name, building, room) VALUES (:n, :b, :r)')
                    ->execute(['n' => $name, 'b' => $building !== '' ? $building : null, 'r' => $room !== '' ? $room : null]);
                logActivity($pdo, $_SESSION['user_id'], 'Create Location', 'locations', "Created location: $name.");
                flash('success', 'Location created.');
            } else {
                $pdo->prepare('UPDATE locations SET location_name = :n, building = :b, room = :r WHERE location_id = :id')
                    ->execute(['n' => $name, 'b' => $building !== '' ? $building : null, 'r' => $room !== '' ? $room : null, 'id' => $id]);
                logActivity($pdo, $_SESSION['user_id'], 'Update Location', 'locations', "Updated location #$id.");
                flash('success', 'Location updated.');
            }
        }
        redirect(APP_URL . '/modules/locations/list.php');
    } elseif ($action === 'delete') {
        $id = (int) ($_POST['location_id'] ?? 0);
        try {
            $pdo->prepare('DELETE FROM locations WHERE location_id = :id')->execute(['id' => $id]);
            logActivity($pdo, $_SESSION['user_id'], 'Delete Location', 'locations', "Deleted location #$id.");
            flash('success', 'Location deleted.');
        } catch (PDOException $e) {
            flash('error', 'This location cannot be deleted because assets are still placed there.');
        }
        redirect(APP_URL . '/modules/locations/list.php');
    }
}

$locations = $pdo->query(
    'SELECT l.*, (SELECT COUNT(*) FROM assets a WHERE a.location_id = l.location_id) AS asset_count
     FROM locations l ORDER BY l.location_name'
)->fetchAll();

$pageTitle  = 'Locations';
$activeMenu = 'locations';
include __DIR__ . '/../../includes/layout/header.php';
?>
<div class="card-header">
    <div></div>
    <button type="button" class="btn btn-primary" data-modal-target="modalCreateLoc">+ Add Location</button>
</div>

<div class="table-wrap">
<table id="locTable">
    <thead><tr><th data-sort>Name</th><th data-sort>Building</th><th data-sort>Room</th><th data-sort>Assets</th><th class="no-sort">Actions</th></tr></thead>
    <tbody>
    <?php if (!$locations): ?><tr class="empty-row"><td colspan="5">No locations yet.</td></tr><?php endif; ?>
    <?php foreach ($locations as $l): ?>
        <tr>
            <td><?= e($l['location_name']) ?></td>
            <td><?= e($l['building'] ?? '—') ?></td>
            <td><?= e($l['room'] ?? '—') ?></td>
            <td><?= (int) $l['asset_count'] ?></td>
            <td class="table-actions">
                <button type="button" class="btn btn-sm btn-outline" data-modal-target="modalEditLoc<?= $l['location_id'] ?>">Edit</button>
                <form method="post" action="" style="display:inline;" data-confirm="Delete location '<?= e($l['location_name']) ?>'?">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="location_id" value="<?= (int) $l['location_id'] ?>">
                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                </form>
            </td>
        </tr>
        <div class="modal-overlay" id="modalEditLoc<?= $l['location_id'] ?>">
            <div class="modal">
                <div class="modal-header"><h3>Edit Location</h3><button type="button" class="modal-close" data-modal-close>&times;</button></div>
                <form method="post" action="">
                    <div class="modal-body">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="location_id" value="<?= (int) $l['location_id'] ?>">
                        <div class="form-group"><label>Location Name *</label><input type="text" name="location_name" required value="<?= e($l['location_name']) ?>"></div>
                        <div class="form-row">
                            <div class="form-group"><label>Building</label><input type="text" name="building" value="<?= e($l['building'] ?? '') ?>"></div>
                            <div class="form-group"><label>Room</label><input type="text" name="room" value="<?= e($l['room'] ?? '') ?>"></div>
                        </div>
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

<div class="modal-overlay" id="modalCreateLoc">
    <div class="modal">
        <div class="modal-header"><h3>Add Location</h3><button type="button" class="modal-close" data-modal-close>&times;</button></div>
        <form method="post" action="">
            <div class="modal-body">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="create">
                <div class="form-group"><label>Location Name *</label><input type="text" name="location_name" required></div>
                <div class="form-row">
                    <div class="form-group"><label>Building</label><input type="text" name="building"></div>
                    <div class="form-group"><label>Room</label><input type="text" name="room"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary">Add Location</button>
            </div>
        </form>
    </div>
</div>
<?php include __DIR__ . '/../../includes/layout/footer.php'; ?>
