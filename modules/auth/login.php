<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

if (isLoggedIn()) {
    redirect(APP_URL . '/modules/dashboard/index.php');
}

$errors = [];
$emailValue = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        $emailValue = clean($_POST['email'] ?? '');
        $password   = (string) ($_POST['password'] ?? '');

        if ($emailValue === '' || $password === '') {
            $errors[] = 'Email and password are both required.';
        } else {
            $stmt = $pdo->prepare(
                'SELECT u.*, r.role_name, d.department_name
                 FROM users u
                 JOIN roles r ON r.role_id = u.role_id
                 LEFT JOIN departments d ON d.department_id = u.department_id
                 WHERE u.email = :email LIMIT 1'
            );
            $stmt->execute(['email' => $emailValue]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($password, $user['password'])) {
                $errors[] = 'Invalid email or password.';
                logLogin($pdo, $user ? $user['user_id'] : null, $emailValue, 'failed');
            } elseif ($user['status'] !== 'active') {
                $errors[] = 'Your account has been deactivated. Contact the system administrator.';
                logLogin($pdo, $user['user_id'], $emailValue, 'failed');
            } else {
                session_regenerate_id(true);
                $_SESSION['user_id']         = (int) $user['user_id'];
                $_SESSION['name']             = $user['name'];
                $_SESSION['email']             = $user['email'];
                $_SESSION['role_id']            = (int) $user['role_id'];
                $_SESSION['role_name']           = $user['role_name'];
                $_SESSION['department_id']        = $user['department_id'] !== null ? (int) $user['department_id'] : null;
                $_SESSION['department_name']       = $user['department_name'];

                logLogin($pdo, $user['user_id'], $emailValue, 'success');
                logActivity($pdo, $user['user_id'], 'Login', 'auth', $user['name'] . ' logged in.');

                $redirectTo = $_SESSION['redirect_after_login'] ?? (APP_URL . '/modules/dashboard/index.php');
                unset($_SESSION['redirect_after_login']);
                redirect($redirectTo ?: APP_URL . '/modules/dashboard/index.php');
            }
        }
    }
}

$uniName = getSetting($pdo, 'university_name', 'Somali National University');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login · <?= e($uniName) ?></title>
<link rel="icon" type="image/webp" href="<?= e(appLogoUrl($pdo)) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= APP_URL ?>/static/css/style.css">
</head>
<body>
<div class="auth-shell">
    <div class="auth-card">
        <div class="auth-brand">
            <img class="auth-logo" src="<?= e(appLogoUrl($pdo)) ?>" alt="<?= e($uniName) ?> logo">
            <h1><?= e($uniName) ?></h1>
            <p>University Asset Management System</p>
        </div>

        <?php if ($errors): ?>
            <div class="alert alert-error">
                <ul style="margin:0; padding-left:1.1rem;">
                    <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" action="" novalidate id="loginForm">
            <?= csrfField() ?>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?= e($emailValue) ?>" required autofocus>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required minlength="4">
            </div>
            <button type="submit" class="btn btn-primary btn-block">Login</button>
        </form>
        <p class="auth-hint">Use your university-issued email and password. Contact the Admin if you cannot access your account.</p>
    </div>
</div>
<script src="<?= APP_URL ?>/static/js/validation.js"></script>
</body>
</html>
