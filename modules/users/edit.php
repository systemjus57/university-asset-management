<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requireRole([ROLE_ADMIN]);

$userId = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM users WHERE user_id = :id');
$stmt->execute(['id' => $userId]);
$user = $stmt->fetch();

if (!$user) {
    flash('error', 'User not found.');
    redirect(APP_URL . '/modules/users/list.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $input = [
        'name'          => clean($_POST['name'] ?? ''),
        'email'         => clean($_POST['email'] ?? ''),
        'role_id'       => $_POST['role_id'] ?? '',
        'department_id' => $_POST['department_id'] ?? '',
    ];
    $newPassword = (string) ($_POST['password'] ?? '');

    $errors = validateRequired($input, ['name' => 'Name', 'email' => 'Email', 'role_id' => 'Role']);
    if ($input['email'] !== '' && !filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email address.';
    }
    if ($newPassword !== '' && strlen($newPassword) < 6) {
        $errors[] = 'New password must be at least 6 characters.';
    }

    if (!$errors) {
        $check = $pdo->prepare('SELECT 1 FROM users WHERE email = :email AND user_id != :id');
        $check->execute(['email' => $input['email'], 'id' => $userId]);
        if ($check->fetch()) {
            $errors[] = 'Another user already uses this email.';
        }
    }

    if (!$errors) {
        if ($newPassword !== '') {
            $stmt = $pdo->prepare(
                'UPDATE users SET name = :name, email = :email, role_id = :role_id, department_id = :department_id, password = :password WHERE user_id = :id'
            );
            $stmt->execute([
                'name' => $input['name'], 'email' => $input['email'], 'role_id' => $input['role_id'],
                'department_id' => $input['department_id'] !== '' ? $input['department_id'] : null,
                'password' => password_hash($newPassword, PASSWORD_DEFAULT), 'id' => $userId,
            ]);
        } else {
            $stmt = $pdo->prepare(
                'UPDATE users SET name = :name, email = :email, role_id = :role_id, department_id = :department_id WHERE user_id = :id'
            );
            $stmt->execute([
                'name' => $input['name'], 'email' => $input['email'], 'role_id' => $input['role_id'],
                'department_id' => $input['department_id'] !== '' ? $input['department_id'] : null, 'id' => $userId,
            ]);
        }
        logActivity($pdo, $_SESSION['user_id'], 'Update User', 'users', "Updated user #$userId.");
        flash('success', 'User updated successfully.');
        redirect(APP_URL . '/modules/users/list.php');
    }
    $user = array_merge($user, $input);
}

$roles       = $pdo->query('SELECT role_id, role_name FROM roles ORDER BY role_id')->fetchAll();
$departments = $pdo->query('SELECT department_id, department_name FROM departments ORDER BY department_name')->fetchAll();

$pageTitle  = 'Edit User';
$activeMenu = 'users';
include __DIR__ . '/../../includes/layout/header.php';
?>
<div class="card" style="max-width:640px;">
    <?php if ($errors): ?>
        <div class="alert alert-error">
            <ul style="margin:0; padding-left:1.1rem;"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>
    <form method="post" action="" novalidate>
        <?= csrfField() ?>
        <div class="form-group">
            <label for="name">Full Name *</label>
            <input type="text" id="name" name="name" required value="<?= e($user['name']) ?>">
        </div>
        <div class="form-group">
            <label for="email">Email *</label>
            <input type="email" id="email" name="email" required value="<?= e($user['email']) ?>">
        </div>
        <div class="form-group">
            <label for="password">Reset Password (optional)</label>
            <input type="password" id="password" name="password" minlength="6" placeholder="Leave blank to keep current password">
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="role_id">Role *</label>
                <select id="role_id" name="role_id" required>
                    <?php foreach ($roles as $r): ?>
                        <option value="<?= $r['role_id'] ?>" <?= (string) $user['role_id'] === (string) $r['role_id'] ? 'selected' : '' ?>><?= e($r['role_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="department_id">Department</label>
                <select id="department_id" name="department_id">
                    <option value="">None</option>
                    <?php foreach ($departments as $d): ?>
                        <option value="<?= $d['department_id'] ?>" <?= (string) $user['department_id'] === (string) $d['department_id'] ? 'selected' : '' ?>><?= e($d['department_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="d-flex gap-1">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="<?= APP_URL ?>/modules/users/list.php" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>
<?php include __DIR__ . '/../../includes/layout/footer.php'; ?>
