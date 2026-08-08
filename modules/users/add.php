<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requireRole([ROLE_ADMIN]);

$errors = [];
$input  = ['name' => '', 'email' => '', 'role_id' => '', 'department_id' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $input = [
        'name'          => clean($_POST['name'] ?? ''),
        'email'         => clean($_POST['email'] ?? ''),
        'role_id'       => $_POST['role_id'] ?? '',
        'department_id' => $_POST['department_id'] ?? '',
    ];
    $password = (string) ($_POST['password'] ?? '');

    $errors = validateRequired($input, [
        'name'    => 'Name',
        'email'   => 'Email',
        'role_id' => 'Role',
    ]);
    if ($input['email'] !== '' && !filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email address.';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }

    if (!$errors) {
        $check = $pdo->prepare('SELECT 1 FROM users WHERE email = :email');
        $check->execute(['email' => $input['email']]);
        if ($check->fetch()) {
            $errors[] = 'A user with this email already exists.';
        }
    }

    if (!$errors) {
        $stmt = $pdo->prepare(
            'INSERT INTO users (name, email, password, role_id, department_id, status)
             VALUES (:name, :email, :password, :role_id, :department_id, "active")'
        );
        $stmt->execute([
            'name'          => $input['name'],
            'email'         => $input['email'],
            'password'      => password_hash($password, PASSWORD_DEFAULT),
            'role_id'       => $input['role_id'],
            'department_id' => $input['department_id'] !== '' ? $input['department_id'] : null,
        ]);
        logActivity($pdo, $_SESSION['user_id'], 'Create User', 'users', "Created user account for {$input['name']}.");
        flash('success', 'User created successfully.');
        redirect(APP_URL . '/modules/users/list.php');
    }
}

$roles       = $pdo->query('SELECT role_id, role_name FROM roles ORDER BY role_id')->fetchAll();
$departments = $pdo->query('SELECT department_id, department_name FROM departments ORDER BY department_name')->fetchAll();

$pageTitle  = 'Add User';
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
            <input type="text" id="name" name="name" required value="<?= e($input['name']) ?>">
        </div>
        <div class="form-group">
            <label for="email">Email *</label>
            <input type="email" id="email" name="email" required value="<?= e($input['email']) ?>">
        </div>
        <div class="form-group">
            <label for="password">Temporary Password *</label>
            <input type="password" id="password" name="password" required minlength="6">
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="role_id">Role *</label>
                <select id="role_id" name="role_id" required>
                    <option value="">Select role</option>
                    <?php foreach ($roles as $r): ?>
                        <option value="<?= $r['role_id'] ?>" <?= (string) $input['role_id'] === (string) $r['role_id'] ? 'selected' : '' ?>><?= e($r['role_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="department_id">Department</label>
                <select id="department_id" name="department_id">
                    <option value="">None</option>
                    <?php foreach ($departments as $d): ?>
                        <option value="<?= $d['department_id'] ?>" <?= (string) $input['department_id'] === (string) $d['department_id'] ? 'selected' : '' ?>><?= e($d['department_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="d-flex gap-1">
            <button type="submit" class="btn btn-primary">Create User</button>
            <a href="<?= APP_URL ?>/modules/users/list.php" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>
<?php include __DIR__ . '/../../includes/layout/footer.php'; ?>
