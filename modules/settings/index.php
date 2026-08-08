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
        $errors[] = 'University name is required.';
    }
    if (!is_numeric($fields['session_timeout_minutes']) || (int) $fields['session_timeout_minutes'] < 5) {
        $errors[] = 'Session timeout must be at least 5 minutes.';
    }
    if (!is_numeric($fields['records_per_page']) || (int) $fields['records_per_page'] < 5) {
        $errors[] = 'Records per page must be at least 5.';
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
        flash('success', 'Settings saved successfully.');
        redirect(APP_URL . '/modules/settings/index.php');
    }
}

$settings = getAllSettings($pdo);

$activeSettingsTab = 'general';
$pageTitle  = 'Settings — General';
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
            <div class="form-group"><label for="university_name">University Name *</label><input type="text" id="university_name" name="university_name" required value="<?= e($settings['university_name'] ?? '') ?>"></div>
            <div class="form-group"><label for="system_name">System Name</label><input type="text" id="system_name" name="system_name" value="<?= e($settings['system_name'] ?? '') ?>"></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label for="academic_year">Academic Year</label><input type="text" id="academic_year" name="academic_year" placeholder="2025/2026" value="<?= e($settings['academic_year'] ?? '') ?>"></div>
            <div class="form-group">
                <label for="language">Language</label>
                <select id="language" name="language">
                    <option value="en" <?= ($settings['language'] ?? 'en') === 'en' ? 'selected' : '' ?>>English</option>
                    <option value="so" <?= ($settings['language'] ?? '') === 'so' ? 'selected' : '' ?>>Somali</option>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="timezone">Time Zone</label>
                <select id="timezone" name="timezone">
                    <?php foreach (['Africa/Mogadishu', 'Africa/Nairobi', 'UTC'] as $tz): ?>
                        <option value="<?= $tz ?>" <?= ($settings['timezone'] ?? '') === $tz ? 'selected' : '' ?>><?= $tz ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="date_format">Date Format</label>
                <select id="date_format" name="date_format">
                    <?php foreach (['d-m-Y', 'm-d-Y', 'Y-m-d'] as $df): ?>
                        <option value="<?= $df ?>" <?= ($settings['date_format'] ?? '') === $df ? 'selected' : '' ?>><?= $df ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group"><label for="session_timeout_minutes">Session Timeout (minutes)</label><input type="number" id="session_timeout_minutes" name="session_timeout_minutes" min="5" value="<?= e($settings['session_timeout_minutes'] ?? '60') ?>"></div>
            <div class="form-group"><label for="records_per_page">Records Per Page</label><input type="number" id="records_per_page" name="records_per_page" min="5" value="<?= e($settings['records_per_page'] ?? '15') ?>"></div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="theme">Theme</label>
                <select id="theme" name="theme">
                    <option value="light" <?= ($settings['theme'] ?? 'light') === 'light' ? 'selected' : '' ?>>Light</option>
                    <option value="dark" <?= ($settings['theme'] ?? '') === 'dark' ? 'selected' : '' ?>>Dark</option>
                </select>
            </div>
            <div class="form-group">
                <label for="logo">University Logo</label>
                <input type="file" id="logo" name="logo" accept="image/png,image/jpeg,image/gif">
                <?php if (!empty($settings['logo_path'])): ?>
                    <img src="<?= e($settings['logo_path']) ?>" alt="Current logo" style="height:40px; margin-top:0.5rem;">
                <?php endif; ?>
            </div>
        </div>

        <div class="form-group" style="background:var(--color-danger-bg); padding:0.9rem; border-radius:8px;">
            <label style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0;">
                <input type="checkbox" name="maintenance_mode" value="1" style="width:auto;" <?= ($settings['maintenance_mode'] ?? '0') === '1' ? 'checked' : '' ?>>
                Enable Maintenance Mode (blocks access for everyone except Admin)
            </label>
        </div>

        <button type="submit" class="btn btn-primary mt-1">Save Settings</button>
    </form>
</div>

<?php include __DIR__ . '/../../includes/layout/footer.php'; ?>
