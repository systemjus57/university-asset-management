<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

if (isLoggedIn()) {
    redirect(APP_URL . '/modules/dashboard/index.php');
}

// ---- Password reset (Forgot Password) tuning ----
const PW_RESET_OTP_TTL_MINUTES  = 10;
const PW_RESET_MAX_ATTEMPTS     = 5;
const PW_RESET_MIN_INTERVAL_SEC = 60;
const PW_RESET_MAX_PER_HOUR     = 5;

// ---- Login brute-force lockout tuning ----
const LOGIN_MAX_FAILED_ATTEMPTS   = 5;
const LOGIN_LOCKOUT_WINDOW_MINUTES = 15;

/** True if this IP has hit the failed-login threshold within the lockout window. */
function isLoginLockedOut(PDO $pdo, string $ip): bool
{
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM login_logs
         WHERE ip_address = :ip AND status = 'failed'
           AND created_at >= DATE_SUB(NOW(), INTERVAL :mins MINUTE)"
    );
    $stmt->execute(['ip' => $ip, 'mins' => LOGIN_LOCKOUT_WINDOW_MINUTES]);
    return (int) $stmt->fetchColumn() >= LOGIN_MAX_FAILED_ATTEMPTS;
}

/** Masks an email for display without fully revealing it, e.g. "ad***@sun.edu.so". */
function maskEmailForDisplay(string $email): string
{
    if (!str_contains($email, '@')) {
        return $email;
    }
    [$local, $domain] = explode('@', $email, 2);
    $visibleLen = min(2, mb_strlen($local));
    $visible    = mb_substr($local, 0, $visibleLen);
    $maskedLen  = max(1, mb_strlen($local) - $visibleLen);
    return $visible . str_repeat('*', $maskedLen) . '@' . $domain;
}

/**
 * Starts (or restarts, for "Resend Code") an OTP request for $email.
 * Deliberately behaves identically whether or not the email belongs to an
 * active account — no row is created and no email is sent for an unknown
 * or inactive account, but the caller always proceeds to the same
 * "check your email" panel, so account existence is never revealed.
 */
function issuePasswordResetOtp(PDO $pdo, string $email): void
{
    $_SESSION['pwreset_email'] = $email;
    $_SESSION['pwreset_stage'] = 'otp';
    unset($_SESSION['pwreset_verified'], $_SESSION['pwreset_id'], $_SESSION['pwreset_user_id'], $_SESSION['pwreset_fake_attempts']);

    $stmt = $pdo->prepare('SELECT user_id, name, status FROM users WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if (!$user || $user['status'] !== 'active') {
        return;
    }
    $userId = (int) $user['user_id'];

    // Rate limiting, scoped to this account: a cool-down between requests, and a cap per hour.
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) AS recent_count, MAX(created_at) AS last_created
         FROM password_resets WHERE user_id = :uid AND created_at >= (NOW() - INTERVAL 1 HOUR)'
    );
    $stmt->execute(['uid' => $userId]);
    $rate = $stmt->fetch();
    if ((int) $rate['recent_count'] >= PW_RESET_MAX_PER_HOUR) {
        return;
    }
    if ($rate['last_created'] && (time() - strtotime($rate['last_created'])) < PW_RESET_MIN_INTERVAL_SEC) {
        return;
    }

    $otp     = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $otpHash = password_hash($otp, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare('INSERT INTO password_resets (user_id, otp_hash, expires_at) VALUES (:uid, :hash, :exp)');
    $stmt->execute([
        'uid'  => $userId,
        'hash' => $otpHash,
        'exp'  => date('Y-m-d H:i:s', time() + PW_RESET_OTP_TTL_MINUTES * 60),
    ]);
    $resetId = (int) $pdo->lastInsertId();

    $uniName = getSetting($pdo, 'university_name', 'Somali National University');
    $subject = 'Your password reset code — ' . $uniName;
    $html = '<p>Hello ' . e($user['name']) . ',</p>'
          . '<p>Your password reset verification code is:</p>'
          . '<p style="font-size:28px; font-weight:700; letter-spacing:4px;">' . e($otp) . '</p>'
          . '<p>This code expires in ' . PW_RESET_OTP_TTL_MINUTES . ' minutes and can only be used once.</p>'
          . '<p>If you did not request a password reset, you can safely ignore this email.</p>';

    $sent = sendSystemEmail($pdo, $email, $user['name'], $subject, $html);

    if ($sent) {
        $_SESSION['pwreset_user_id'] = $userId;
        $_SESSION['pwreset_id']      = $resetId;
        logActivity($pdo, $userId, 'Password Reset Requested', 'auth', 'A password reset code was requested for ' . $email . '.');
    } else {
        // Delivery failed (e.g. SMTP not configured) — drop the unusable row rather than leaving a
        // code nobody received; the response shown to the browser is identical either way.
        $pdo->prepare('DELETE FROM password_resets WHERE reset_id = :id')->execute(['id' => $resetId]);
    }
}

$errors           = [];
$loginValue       = '';
$resetErrors      = [];
$resetEmailValue  = '';
$showResendButton = false;
$activePanel      = null; // forced by a branch below when needed; otherwise derived from session

// Redirected here by includes/bootstrap.php after an idle session was timed out.
if (($_GET['expired'] ?? '') === '1') {
    $errors[] = t('login.err.session_expired');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formAction = $_POST['form_action'] ?? 'login';

    if (!verifyCsrf()) {
        if ($formAction === 'login') {
            $errors[] = t('login.err.session_expired');
        } else {
            $resetErrors[] = t('login.err.session_expired');
        }
    } elseif ($formAction === 'login') {
        // ---- Existing login flow, unchanged ----
        $loginValue = clean($_POST['login'] ?? '');
        $password   = (string) ($_POST['password'] ?? '');

        if ($loginValue === '' || $password === '') {
            $errors[] = t('login.err.required');
        } elseif (isLoginLockedOut($pdo, clientIp())) {
            // Deliberately skip the credential check entirely once locked out — no
            // DB lookup, no logLogin() write, so a lockout can't itself be used to
            // keep probing which usernames exist or to grow the log unbounded.
            $errors[] = t('login.err.locked_out');
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
                $_SESSION['profile_picture']    = $user['profile_picture'];
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
    } elseif ($formAction === 'cancel_reset') {
        // ---- "Back to Login" from any reset step: abandon the flow server-side ----
        unset(
            $_SESSION['pwreset_stage'], $_SESSION['pwreset_email'], $_SESSION['pwreset_user_id'],
            $_SESSION['pwreset_id'], $_SESSION['pwreset_verified'], $_SESSION['pwreset_fake_attempts']
        );
        $activePanel = 'login';
    } elseif ($formAction === 'request_reset') {
        // ---- Step 2 -> 3: submit email, send OTP ----
        $resetEmailValue = clean($_POST['reset_email'] ?? '');
        if ($resetEmailValue === '' || !filter_var($resetEmailValue, FILTER_VALIDATE_EMAIL)) {
            $resetErrors[] = t('login.reset.err.invalid_email');
            $activePanel = 'forgot_email';
        } else {
            require_once __DIR__ . '/../../includes/mailer.php';
            issuePasswordResetOtp($pdo, $resetEmailValue);
        }
    } elseif ($formAction === 'resend_otp') {
        // ---- From the OTP panel: request a fresh code for the same email ----
        $resendEmail = $_SESSION['pwreset_email'] ?? '';
        if ($resendEmail === '') {
            $resetErrors[] = t('login.reset.err.session');
            $activePanel = 'login';
        } else {
            require_once __DIR__ . '/../../includes/mailer.php';
            issuePasswordResetOtp($pdo, $resendEmail);
        }
    } elseif ($formAction === 'verify_otp') {
        // ---- Step 4: verify the code ----
        if (($_SESSION['pwreset_stage'] ?? '') !== 'otp') {
            $resetErrors[] = t('login.reset.err.session');
            $activePanel = 'login';
        } elseif (!empty($_SESSION['pwreset_verified'])) {
            // Already verified by an earlier submit in this session — idempotent, just show the next step.
        } else {
            $code = preg_replace('/\D/', '', (string) ($_POST['otp'] ?? ''));
            if ($code === '' || strlen($code) !== 6) {
                $resetErrors[] = t('login.reset.err.otp_required');
            } elseif (empty($_SESSION['pwreset_id']) || empty($_SESSION['pwreset_user_id'])) {
                // Unknown/inactive-email path: no real row exists. Mirror the same attempt-limit
                // behaviour so timing/response never reveals that the account doesn't exist.
                $_SESSION['pwreset_fake_attempts'] = ($_SESSION['pwreset_fake_attempts'] ?? 0) + 1;
                if ($_SESSION['pwreset_fake_attempts'] >= PW_RESET_MAX_ATTEMPTS) {
                    $resetErrors[] = t('login.reset.err.otp_locked');
                    $showResendButton = true;
                } else {
                    $resetErrors[] = t('login.reset.err.otp_invalid');
                }
            } else {
                $stmt = $pdo->prepare('SELECT * FROM password_resets WHERE reset_id = :id AND user_id = :uid LIMIT 1');
                $stmt->execute(['id' => $_SESSION['pwreset_id'], 'uid' => $_SESSION['pwreset_user_id']]);
                $reset = $stmt->fetch();

                if (!$reset) {
                    $resetErrors[] = t('login.reset.err.session');
                    $activePanel = 'login';
                } elseif ($reset['used_at'] !== null) {
                    $resetErrors[] = t('login.reset.err.otp_used');
                    $showResendButton = true;
                } elseif (strtotime($reset['expires_at']) < time()) {
                    $resetErrors[] = t('login.reset.err.otp_expired');
                    $showResendButton = true;
                } elseif ((int) $reset['attempts'] >= PW_RESET_MAX_ATTEMPTS) {
                    $resetErrors[] = t('login.reset.err.otp_locked');
                    $showResendButton = true;
                } elseif (password_verify($code, $reset['otp_hash'])) {
                    $pdo->prepare('UPDATE password_resets SET verified_at = NOW() WHERE reset_id = :id')
                        ->execute(['id' => $reset['reset_id']]);
                    $_SESSION['pwreset_verified'] = true;
                } else {
                    $pdo->prepare('UPDATE password_resets SET attempts = attempts + 1 WHERE reset_id = :id')
                        ->execute(['id' => $reset['reset_id']]);
                    if ((int) $reset['attempts'] + 1 >= PW_RESET_MAX_ATTEMPTS) {
                        $resetErrors[] = t('login.reset.err.otp_locked');
                        $showResendButton = true;
                    } else {
                        $resetErrors[] = t('login.reset.err.otp_invalid');
                    }
                }
            }
        }
    } elseif ($formAction === 'reset_password') {
        // ---- Step 5 -> 6: set the new password ----
        if (empty($_SESSION['pwreset_verified']) || empty($_SESSION['pwreset_id']) || empty($_SESSION['pwreset_user_id'])) {
            $resetErrors[] = t('login.reset.err.session');
            unset($_SESSION['pwreset_stage'], $_SESSION['pwreset_verified']);
            $activePanel = 'login';
        } else {
            $stmt = $pdo->prepare('SELECT * FROM password_resets WHERE reset_id = :id AND user_id = :uid LIMIT 1');
            $stmt->execute(['id' => $_SESSION['pwreset_id'], 'uid' => $_SESSION['pwreset_user_id']]);
            $reset = $stmt->fetch();

            if (!$reset || $reset['used_at'] !== null || $reset['verified_at'] === null || strtotime($reset['expires_at']) < time()) {
                $resetErrors[] = t('login.reset.err.session');
                unset($_SESSION['pwreset_stage'], $_SESSION['pwreset_verified']);
                $activePanel = 'login';
            } else {
                $newPassword     = (string) ($_POST['new_password'] ?? '');
                $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

                if (strlen($newPassword) < 6) {
                    $resetErrors[] = t('login.reset.err.password_length');
                } elseif ($newPassword !== $confirmPassword) {
                    $resetErrors[] = t('login.reset.err.password_mismatch');
                } else {
                    $pdo->prepare('UPDATE users SET password = :hash WHERE user_id = :uid')
                        ->execute(['hash' => password_hash($newPassword, PASSWORD_DEFAULT), 'uid' => $_SESSION['pwreset_user_id']]);
                    $pdo->prepare('UPDATE password_resets SET used_at = NOW() WHERE reset_id = :id')
                        ->execute(['id' => $reset['reset_id']]);
                    logActivity($pdo, $_SESSION['pwreset_user_id'], 'Password Reset', 'auth', 'Password reset via emailed verification code.');
                    notifyUser(
                        $pdo, (int) $_SESSION['pwreset_user_id'],
                        'Password changed',
                        'Your password was reset via email verification on ' . date('d M Y, H:i') . '. If this wasn\'t you, contact the system administrator immediately.',
                        'warning', 'auth', null
                    );

                    unset(
                        $_SESSION['pwreset_email'], $_SESSION['pwreset_user_id'], $_SESSION['pwreset_id'],
                        $_SESSION['pwreset_verified'], $_SESSION['pwreset_fake_attempts']
                    );
                    $_SESSION['pwreset_stage'] = 'success';
                }
            }
        }
    }
}

if ($activePanel === null) {
    $activePanel = 'login';
    if (($_SESSION['pwreset_stage'] ?? '') === 'success') {
        $activePanel = 'success';
    } elseif (!empty($_SESSION['pwreset_verified'])) {
        $activePanel = 'new_password';
    } elseif (($_SESSION['pwreset_stage'] ?? '') === 'otp') {
        $activePanel = 'otp';
    }
}
if ($activePanel === 'success') {
    unset($_SESSION['pwreset_stage']); // one-time: a later visit falls back to the login panel
}

$resetMaskedEmail = maskEmailForDisplay($_SESSION['pwreset_email'] ?? '');
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

        <!-- Panel 1: Login (existing form, unchanged) -->
        <div class="tab-panel<?= $activePanel === 'login' ? ' active' : '' ?>" id="panel-login">
            <?php if ($errors): ?>
                <div class="alert alert-error">
                    <ul style="margin:0; padding-left:1.1rem;">
                        <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" action="" novalidate id="loginForm">
                <?= csrfField() ?>
                <input type="hidden" name="form_action" value="login">
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
            <p class="auth-hint" style="margin-bottom:0.25rem;">
                <button type="button" class="link-count" data-auth-show="panel-forgot-email"><?= e(t('login.forgot_link')) ?></button>
            </p>
            <p class="auth-hint"><?= e(t('login.hint')) ?></p>
        </div>

        <!-- Panel 2: Forgot Password — enter email -->
        <div class="tab-panel<?= $activePanel === 'forgot_email' ? ' active' : '' ?>" id="panel-forgot-email">
            <h1 style="font-size:1.05rem; text-align:center; margin-bottom:0.75rem;"><?= e(t('login.reset.title')) ?></h1>
            <?php if ($activePanel === 'forgot_email' && $resetErrors): ?>
                <div class="alert alert-error">
                    <ul style="margin:0; padding-left:1.1rem;">
                        <?php foreach ($resetErrors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            <p class="auth-hint" style="margin-top:0;"><?= e(t('login.reset.email_intro')) ?></p>
            <form method="post" action="" novalidate>
                <?= csrfField() ?>
                <input type="hidden" name="form_action" value="request_reset">
                <div class="form-group">
                    <label for="reset_email"><?= e(t('login.reset.email_label')) ?></label>
                    <input type="email" id="reset_email" name="reset_email" value="<?= e($resetEmailValue) ?>" required autocomplete="email">
                </div>
                <button type="submit" class="btn btn-primary btn-block"><?= e(t('login.reset.send_code')) ?></button>
            </form>
            <p class="auth-hint">
                <button type="button" class="link-count" data-auth-show="panel-login"><?= e(t('login.reset.back_to_login')) ?></button>
            </p>
        </div>

        <!-- Panel 3: Forgot Password — verify code -->
        <div class="tab-panel<?= $activePanel === 'otp' ? ' active' : '' ?>" id="panel-forgot-otp">
            <h1 style="font-size:1.05rem; text-align:center; margin-bottom:0.75rem;"><?= e(t('login.reset.otp_title')) ?></h1>
            <?php if ($activePanel === 'otp' && $resetErrors): ?>
                <div class="alert alert-error">
                    <ul style="margin:0; padding-left:1.1rem;">
                        <?php foreach ($resetErrors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
                    </ul>
                </div>
            <?php elseif ($activePanel === 'otp'): ?>
                <div class="alert alert-info"><?= e(t('login.reset.generic_sent')) ?></div>
            <?php endif; ?>
            <p class="auth-hint" style="margin-top:0;"><?= e(t('login.reset.otp_intro')) ?> <strong><?= e($resetMaskedEmail) ?></strong></p>
            <form method="post" action="" novalidate>
                <?= csrfField() ?>
                <div class="form-group">
                    <label for="otp"><?= e(t('login.reset.otp_label')) ?></label>
                    <input type="text" id="otp" name="otp" inputmode="numeric" pattern="[0-9]{6}" maxlength="6"
                           autocomplete="one-time-code" required style="letter-spacing:4px; text-align:center; font-size:1.2rem;">
                </div>
                <button type="submit" name="form_action" value="verify_otp" class="btn btn-primary btn-block"><?= e(t('login.reset.verify_code')) ?></button>
                <?php if ($activePanel === 'otp' && $showResendButton): ?>
                    <button type="submit" name="form_action" value="resend_otp" class="btn btn-outline btn-block mt-1"><?= e(t('login.reset.resend_code')) ?></button>
                <?php endif; ?>
            </form>
            <p class="auth-hint">
                <button type="button" class="link-count" data-auth-cancel><?= e(t('login.reset.back_to_login')) ?></button>
            </p>
        </div>

        <!-- Panel 4: Forgot Password — new password -->
        <div class="tab-panel<?= $activePanel === 'new_password' ? ' active' : '' ?>" id="panel-forgot-new-password">
            <h1 style="font-size:1.05rem; text-align:center; margin-bottom:0.75rem;"><?= e(t('login.reset.new_password_title')) ?></h1>
            <?php if ($activePanel === 'new_password' && $resetErrors): ?>
                <div class="alert alert-error">
                    <ul style="margin:0; padding-left:1.1rem;">
                        <?php foreach ($resetErrors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            <form method="post" action="" novalidate>
                <?= csrfField() ?>
                <input type="hidden" name="form_action" value="reset_password">
                <div class="form-group">
                    <label for="new_password"><?= e(t('login.reset.new_password_label')) ?></label>
                    <input type="password" id="new_password" name="new_password" required minlength="6" autocomplete="new-password">
                </div>
                <div class="form-group">
                    <label for="confirm_password"><?= e(t('login.reset.confirm_password_label')) ?></label>
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="6" autocomplete="new-password">
                </div>
                <button type="submit" class="btn btn-primary btn-block"><?= e(t('login.reset.submit_new_password')) ?></button>
            </form>
            <p class="auth-hint">
                <button type="button" class="link-count" data-auth-cancel><?= e(t('login.reset.back_to_login')) ?></button>
            </p>
        </div>

        <!-- Panel 5: Forgot Password — success -->
        <div class="tab-panel<?= $activePanel === 'success' ? ' active' : '' ?>" id="panel-forgot-success">
            <h1 style="font-size:1.05rem; text-align:center; margin-bottom:0.75rem;"><?= e(t('login.reset.success_title')) ?></h1>
            <div class="alert alert-success"><?= e(t('login.reset.success_message')) ?></div>
            <button type="button" class="btn btn-primary btn-block" data-auth-cancel><?= e(t('login.reset.back_to_login')) ?></button>
        </div>

        <form method="post" action="" id="cancelResetForm" style="display:none">
            <?= csrfField() ?>
            <input type="hidden" name="form_action" value="cancel_reset">
        </form>
    </div>
</div>
<script src="<?= APP_URL ?>/static/js/validation.js?v=<?= filemtime(APP_ROOT . '/static/js/validation.js') ?>"></script>
<script src="<?= APP_URL ?>/static/js/password_reset.js?v=<?= filemtime(APP_ROOT . '/static/js/password_reset.js') ?>"></script>
</body>
</html>
