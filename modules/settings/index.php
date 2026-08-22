<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requireRole([ROLE_ADMIN]);

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $fields = [
        'university_name'          => clean($_POST['university_name'] ?? ''),
        'system_name'                => clean($_POST['system_name'] ?? ''),
        'academic_year'               => clean($_POST['academic_year'] ?? ''),
        'timezone'                     => clean($_POST['timezone'] ?? ''),
        'date_format'                   => clean($_POST['date_format'] ?? ''),
        'language'                       => clean($_POST['language'] ?? ''),
        'session_timeout_minutes'         => $_POST['session_timeout_minutes'] ?? '60',
        'records_per_page'                  => $_POST['records_per_page'] ?? '15',
        'theme'                               => $_POST['theme'] ?? 'light',
        'maintenance_mode'                     => isset($_POST['maintenance_mode']) ? '1' : '0',
    ];

    if ($fields['university_name'] === '') {
        $errors[] = t('settings.err.university_name');
    }
    if (!is_numeric($fields['session_timeout_minutes']) || (int) $fields['session_timeout_minutes'] < 5) {
        $errors[] = t('settings.err.session_timeout');
    }
    if (!is_numeric($fields['records_per_page']) || (int) $fields['records_per_page'] < 5) {
        $errors[] = t('settings.err.records_per_page');
    }

    // Logo upload (optional)
    $logoPath = null;
    if (!empty($_FILES['logo']['name'])) {
        $allowedExt = ['jpg', 'jpeg', 'png', 'gif'];
        $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
        if ($_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Logo upload failed. Please try again.';
        } elseif (!in_array($ext, $allowedExt, true)) {
            $errors[] = 'Logo must be a JPG, PNG, or GIF image.';
        } elseif ($_FILES['logo']['size'] > 2 * 1024 * 1024) {
            $errors[] = 'Logo must be smaller than 2MB.';
        } elseif (!isValidImageUpload($_FILES['logo']['tmp_name'])) {
            $errors[] = 'That file is not a valid image.';
        } else {
            $newName = 'logo_' . bin2hex(random_bytes(6)) . '.' . $ext;
            $destination = APP_ROOT . '/uploads/logos/' . $newName;
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $destination)) {
                $logoPath = APP_URL . '/uploads/logos/' . $newName;
            } else {
                $errors[] = 'Could not save the uploaded logo.';
            }
        }
    }

    if (!$errors) {
        foreach ($fields as $key => $value) {
            setSetting($pdo, $key, (string) $value);
        }
        if ($logoPath) {
            setSetting($pdo, 'logo_path', $logoPath);
        }
        logActivity($pdo, $_SESSION['user_id'], 'Update Settings', 'settings', 'Updated general system settings.');
        flash('success', t('settings.saved'));
        redirect(APP_URL . '/modules/settings/index.php');
    }
}

$settings = getAllSettings($pdo);

$activeSettingsTab = 'general';
$pageTitle  = t('settings.title_prefix') . ' ' . t('settings.tab.general');
$activeMenu = 'settings';
include __DIR__ . '/../../includes/layout/header.php';
include __DIR__ . '/_subnav.php';
?>

<?php if ($errors): ?>
    <div class="alert alert-error"><ul style="margin:0; padding-left:1.1rem;"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<div class="card" style="max-width:760px;">
    <form method="post" action="" enctype="multipart/form-data" novalidate>
        <?= csrfField() ?>
        <div class="form-row">
            <div class="form-group"><label for="university_name"><?= e(t('settings.university_name')) ?> *</label><input type="text" id="university_name" name="university_name" required value="<?= e($settings['university_name'] ?? '') ?>"></div>
            <div class="form-group"><label for="system_name"><?= e(t('settings.system_name')) ?></label><input type="text" id="system_name" name="system_name" value="<?= e($settings['system_name'] ?? '') ?>"></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label for="academic_year"><?= e(t('settings.academic_year')) ?></label><input type="text" id="academic_year" name="academic_year" placeholder="2025/2026" value="<?= e($settings['academic_year'] ?? '') ?>"></div>
            <div class="form-group">
                <label for="language"><?= e(t('settings.language')) ?></label>
                <select id="language" name="language">
                    <option value="en" <?= ($settings['language'] ?? 'en') === 'en' ? 'selected' : '' ?>>English</option>
                    <option value="so" <?= ($settings['language'] ?? '') === 'so' ? 'selected' : '' ?>>Soomaali</option>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="timezone"><?= e(t('settings.timezone')) ?></label>
                <select id="timezone" name="timezone">
                    <?php foreach (['Africa/Mogadishu', 'Africa/Nairobi', 'UTC'] as $tz): ?>
                        <option value="<?= $tz ?>" <?= ($settings['timezone'] ?? '') === $tz ? 'selected' : '' ?>><?= $tz ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="date_format"><?= e(t('settings.date_format')) ?></label>
                <select id="date_format" name="date_format">
                    <?php foreach (['d-m-Y', 'm-d-Y', 'Y-m-d'] as $df): ?>
                        <option value="<?= $df ?>" <?= ($settings['date_format'] ?? '') === $df ? 'selected' : '' ?>><?= $df ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group"><label for="session_timeout_minutes"><?= e(t('settings.session_timeout')) ?></label><input type="number" id="session_timeout_minutes" name="session_timeout_minutes" min="5" value="<?= e($settings['session_timeout_minutes'] ?? '60') ?>"></div>
            <div class="form-group"><label for="records_per_page"><?= e(t('settings.records_per_page')) ?></label><input type="number" id="records_per_page" name="records_per_page" min="5" value="<?= e($settings['records_per_page'] ?? '15') ?>"></div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="theme"><?= e(t('settings.theme')) ?></label>
                <select id="theme" name="theme">
                    <option value="light" <?= ($settings['theme'] ?? 'light') === 'light' ? 'selected' : '' ?>><?= e(t('settings.theme.light')) ?></option>
                    <option value="dark" <?= ($settings['theme'] ?? '') === 'dark' ? 'selected' : '' ?>><?= e(t('settings.theme.dark')) ?></option>
                </select>
            </div>
            <div class="form-group">
                <label for="logo"><?= e(t('settings.logo')) ?></label>
                <input type="file" id="logo" name="logo" accept="image/png,image/jpeg,image/gif">
                <?php if (!empty($settings['logo_path'])): ?>
                    <img src="<?= e($settings['logo_path']) ?>" alt="Current logo" style="height:40px; margin-top:0.5rem;">
                <?php endif; ?>
            </div>
        </div>

        <div class="form-group" style="background:var(--color-danger-bg); padding:0.9rem; border-radius:8px;">
            <label style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0;">
                <input type="checkbox" name="maintenance_mode" value="1" style="width:auto;" <?= ($settings['maintenance_mode'] ?? '0') === '1' ? 'checked' : '' ?>>
                <?= e(t('settings.maintenance_mode')) ?>
            </label>
        </div>

        <button type="submit" class="btn btn-primary mt-1"><?= e(t('settings.save')) ?></button>
    </form>
</div>

<?php include __DIR__ . '/../../includes/layout/footer.php'; ?>
