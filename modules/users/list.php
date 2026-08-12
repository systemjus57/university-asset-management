<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requireRole([ROLE_ADMIN]);

$q      = clean($_GET['q'] ?? '');
$roleId = $_GET['role_id'] ?? '';

$where  = [];
$params = [];
if ($q !== '') {
    $where[] = '(u.name LIKE :q OR u.email LIKE :q OR u.username LIKE :q)';
    $params['q'] = "%$q%";
}
if ($roleId !== '') {
    $where[] = 'u.role_id = :role_id';
    $params['role_id'] = $roleId;
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$sql = "SELECT u.*, r.role_name, d.department_name
        FROM users u
        LEFT JOIN roles r ON r.role_id = u.role_id
        LEFT JOIN departments d ON d.department_id = u.department_id
        $whereSql
        ORDER BY u.name";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

$roles = $pdo->query('SELECT role_id, role_name FROM roles ORDER BY role_id')->fetchAll();

$pageTitle  = 'Users';
$activeMenu = 'users';
include __DIR__ . '/../../includes/layout/header.php';
?>
<div class="card-header">
    <div></div>
    <a href="<?= APP_URL ?>/modules/users/add.php" class="btn btn-primary">+ Add User</a>
</div>

<form method="get" class="filter-bar">
    <div class="form-group">
        <label for="q">Search</label>
        <input type="search" id="q" name="q" placeholder="Name, email, or username" value="<?= e($q) ?>">
    </div>
    <div class="form-group">
        <label for="role_id">Role</label>
        <select id="role_id" name="role_id">
            <option value="">All Roles</option>
            <?php foreach ($roles as $r): ?>
                <option value="<?= $r['role_id'] ?>" <?= (string) $roleId === (string) $r['role_id'] ? 'selected' : '' ?>><?= e($r['role_name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group"><button type="submit" class="btn btn-outline">Filter</button></div>
</form>

<div class="table-wrap">
<table id="usersTable">
    <thead>
        <tr>
            <th data-sort>Name</th>
            <th data-sort>Email</th>
            <th data-sort>Username</th>
            <th data-sort>Role</th>
            <th data-sort>Department</th>
            <th data-sort>Status</th>
            <th class="no-sort">Actions</th>
        </tr>
    </thead>
    <tbody>
    <?php if (!$users): ?><tr class="empty-row"><td colspan="7">No users found.</td></tr><?php endif; ?>
    <?php foreach ($users as $u): ?>
        <tr>
            <td><?= e($u['name']) ?></td>
            <td><?= e($u['email']) ?></td>
            <td><?= e($u['username']) ?></td>
            <td><?= e($u['role_name']) ?></td>
            <td><?= e($u['department_name'] ?? '—') ?></td>
            <td><?= statusBadge($u['status']) ?></td>
            <td class="table-actions">
                <a class="btn btn-sm btn-outline" href="<?= APP_URL ?>/modules/users/edit.php?id=<?= (int) $u['user_id'] ?>">Edit</a>
                <?php if ((int) $u['user_id'] !== (int) $_SESSION['user_id']): ?>
                    <form method="post" action="<?= APP_URL ?>/modules/users/deactivate.php" style="display:inline;"
                          data-confirm="<?= $u['status'] === 'active' ? 'Deactivate' : 'Reactivate' ?> this user account?">
                        <?= csrfField() ?>
                        <input type="hidden" name="user_id" value="<?= (int) $u['user_id'] ?>">
                        <button type="submit" class="btn btn-sm <?= $u['status'] === 'active' ? 'btn-danger' : 'btn-accent' ?>">
                            <?= $u['status'] === 'active' ? 'Deactivate' : 'Reactivate' ?>
                        </button>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php include __DIR__ . '/../../includes/layout/footer.php'; ?>
