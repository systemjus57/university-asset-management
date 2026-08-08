<?php
/** Shared sub-navigation for the Settings module. $activeSettingsTab set by the including page. */
$settingsTabs = [
    'general'    => ['label' => 'General', 'href' => 'index.php'],
    'smtp'       => ['label' => 'Email (SMTP)', 'href' => 'smtp.php'],
    'backup'     => ['label' => 'Backup & Restore', 'href' => 'backup.php'],
    'logs'       => ['label' => 'Activity Logs', 'href' => 'logs.php'],
    'login_logs' => ['label' => 'Login Logs', 'href' => 'login_logs.php'],
    'system'     => ['label' => 'System Info', 'href' => 'system_info.php'],
];
?>
<div class="tabs">
    <?php foreach ($settingsTabs as $key => $tab): ?>
        <a class="tab-link <?= $activeSettingsTab === $key ? 'active' : '' ?>" style="text-decoration:none;"
           href="<?= APP_URL ?>/modules/settings/<?= $tab['href'] ?>"><?= e($tab['label']) ?></a>
    <?php endforeach; ?>
</div>
