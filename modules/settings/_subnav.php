<?php
/** Shared sub-navigation for the Settings module. $activeSettingsTab set by the including page. */
$settingsTabs = [
    'general'    => ['label' => t('settings.tab.general'), 'href' => 'index.php'],
    'smtp'       => ['label' => t('settings.tab.smtp'), 'href' => 'smtp.php'],
    'backup'     => ['label' => t('settings.tab.backup'), 'href' => 'backup.php'],
    'logs'       => ['label' => t('settings.tab.logs'), 'href' => 'logs.php'],
    'login_logs' => ['label' => t('settings.tab.login_logs'), 'href' => 'login_logs.php'],
    'system'     => ['label' => t('settings.tab.system'), 'href' => 'system_info.php'],
];
?>
<div class="tabs">
    <?php foreach ($settingsTabs as $key => $tab): ?>
        <a class="tab-link <?= $activeSettingsTab === $key ? 'active' : '' ?>" style="text-decoration:none;"
           href="<?= APP_URL ?>/modules/settings/<?= $tab['href'] ?>"><?= e($tab['label']) ?></a>
    <?php endforeach; ?>
</div>
