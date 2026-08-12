<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

if (isLoggedIn()) {
    redirect(APP_URL . '/modules/dashboard/index.php');
}

$errors = [];
$loginValue = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        $errors[] = t('login.err.session_expired');
    } else {
        $loginValue = clean($_POST['login'] ?? '');
        $password   = (string) ($_POST['password'] ?? '');

        if ($loginValue === '' || $password === '') {
            $errors[] = t('login.err.required');
        } else {
            $stmt = $pdo->prepare(
                'SELECT u.*, r.role_name, d.department_name
                 FROM users u
                 JOIN roles r ON r.role_id = u.role_id
                 LEFT JOIN departments d ON d.department_id = u.department_id
                 WHERE u.email = :login1 OR u.username = :login2 LIMIT 1'
            );
            $stmt->execute(['login1' => $loginValue, 'login2' => $loginValue]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($password, $user['password'])) {
                $errors[] = t('login.err.invalid');
                logLogin($pdo, $user ? $user['user_id'] : null, $loginValue, 'failed');
            } elseif ($user['status'] !== 'active') {
                $errors[] = t('login.err.deactivated');
                logLogin($pdo, $user['user_id'], $loginValue, 'failed');
            } else {
                session_regenerate_id(true);
                $_SESSION['user_id']         = (int) $user['user_id'];
                $_SESSION['name']             = $user['name'];
                $_SESSION['email']             = $user['email'];
                $_SESSION['username']          = $user['username'];
                $_SESSION['role_id']            = (int) $user['role_id'];
                $_SESSION['role_name']           = $user['role_name'];
                $_SESSION['department_id']        = $user['department_id'] !== null ? (int) $user['department_id'] : null;
                $_SESSION['department_name']       = $user['department_name'];

                logLogin($pdo, $user['user_id'], $loginValue, 'success');
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
<html lang="<?= e(activeLanguage()) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e(t('login.button')) ?> · <?= e($uniName) ?></title>
<link rel="icon" type="image/webp" href="<?= e(appLogoUrl($pdo)) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= APP_URL ?>/static/css/style.css?v=<?= filemtime(APP_ROOT . '/static/css/style.css') ?>">
</head>
<body>
<div class="auth-shell">
    <div class="auth-card">
        <div class="auth-brand">
            <img class="auth-logo" src="<?= e(appLogoUrl($pdo)) ?>" alt="<?= e($uniName) ?> logo">
            <h1><?= e($uniName) ?></h1>
            <p><?= e(t('login.subtitle')) ?></p>
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
                <label for="login"><?= e(t('login.field')) ?></label>
                <input type="text" id="login" name="login" value="<?= e($loginValue) ?>" required autofocus autocomplete="username">
            </div>
            <div class="form-group">
                <label for="password"><?= e(t('login.password')) ?></label>
                <input type="password" id="password" name="password" required minlength="4" autocomplete="current-password">
            </div>
            <button type="submit" class="btn btn-primary btn-block"><?= e(t('login.button')) ?></button>
        </form>
        <p class="auth-hint"><?= e(t('login.hint')) ?></p>
    </div>
</div>
<script src="<?= APP_URL ?>/static/js/validation.js?v=<?= filemtime(APP_ROOT . '/static/js/validation.js') ?>"></script>
</body>
</html>
