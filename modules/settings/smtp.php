<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requireRole([ROLE_ADMIN]);

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $fields = [
        'smtp_host'       => clean($_POST['smtp_host'] ?? ''),
        'smtp_port'        => $_POST['smtp_port'] ?? '587',
        'smtp_username'      => clean($_POST['smtp_username'] ?? ''),
        'smtp_encryption'      => $_POST['smtp_encryption'] ?? 'tls',
    ];
    $smtpPassword = (string) ($_POST['smtp_password'] ?? '');

    if ($fields['smtp_host'] !== '' && !is_numeric($fields['smtp_port'])) {
        $errors[] = 'SMTP port must be numeric.';
    }

    if (!$errors) {
        foreach ($fields as $key => $value) {
            setSetting($pdo, $key, (string) $value);
        }
        if ($smtpPassword !== '') {
            setSetting($pdo, 'smtp_password', $smtpPassword);
        }
        logActivity($pdo, $_SESSION['user_id'], 'Update SMTP Settings', 'settings', 'Updated email/SMTP configuration.');
        flash('success', 'Email settings saved.');
        redirect(APP_URL . '/modules/settings/smtp.php');
    }
}

$settings = getAllSettings($pdo);

$activeSettingsTab = 'smtp';
$pageTitle  = 'Settings — Email';
$activeMenu = 'settings';
include __DIR__ . '/../../includes/layout/header.php';
include __DIR__ . '/_subnav.php';
?>

<?php if ($errors): ?>
    <div class="alert alert-error"><ul style="margin:0; padding-left:1.1rem;"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<div class="card" style="max-width:600px;">
    <p class="text-muted">Used for outgoing notification emails (e.g. maintenance updates, requisition decisions). Leave blank to keep the current password unchanged.</p>
    <form method="post" action="" novalidate>
        <?= csrfField() ?>
        <div class="form-group"><label for="smtp_host">SMTP Host</label><input type="text" id="smtp_host" name="smtp_host" placeholder="smtp.gmail.com" value="<?= e($settings['smtp_host'] ?? '') ?>"></div>
        <div class="form-row">
            <div class="form-group"><label for="smtp_port">Port</label><input type="number" id="smtp_port" name="smtp_port" value="<?= e($settings['smtp_port'] ?? '587') ?>"></div>
            <div class="form-group">
                <label for="smtp_encryption">Encryption</label>
                <select id="smtp_encryption" name="smtp_encryption">
                    <option value="tls" <?= ($settings['smtp_encryption'] ?? 'tls') === 'tls' ? 'selected' : '' ?>>TLS</option>
                    <option value="ssl" <?= ($settings['smtp_encryption'] ?? '') === 'ssl' ? 'selected' : '' ?>>SSL</option>
                    <option value="none" <?= ($settings['smtp_encryption'] ?? '') === 'none' ? 'selected' : '' ?>>None</option>
                </select>
            </div>
        </div>
        <div class="form-group"><label for="smtp_username">SMTP Username</label><input type="text" id="smtp_username" name="smtp_username" value="<?= e($settings['smtp_username'] ?? '') ?>"></div>
        <div class="form-group"><label for="smtp_password">SMTP Password</label><input type="password" id="smtp_password" name="smtp_password" placeholder="Leave blank to keep current password"></div>
        <button type="submit" class="btn btn-primary">Save Email Settings</button>
    </form>
</div>

<?php include __DIR__ . '/../../includes/layout/footer.php'; ?>
