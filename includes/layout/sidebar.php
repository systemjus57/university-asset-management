<?php
/**
 * Role-aware sidebar navigation.
 * $activeMenu (set by the calling page) controls the highlighted item.
 */
$role = $_SESSION['role_name'] ?? '';
$sidebarLogo = isset($pdo) ? appLogoUrl($pdo) : APP_URL . '/static/images/logo.webp';

function navItem(string $key, string $label, string $href, string $iconName, string $activeMenu): string
{
    $active = $key === $activeMenu ? ' active' : '';
    return '<a class="nav-item' . $active . '" href="' . $href . '"><span class="nav-icon">' . icon($iconName) . '</span><span class="nav-label">' . e($label) . '</span></a>';
}
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <img class="brand-mark" src="<?= e($sidebarLogo) ?>" alt="<?= e($uniName ?? 'University') ?> logo">
        <span class="brand-text-group">
            <span class="brand-text"><?= e(t('brand.title')) ?></span>
            <span class="brand-subtext"><?= e(t('brand.subtitle')) ?></span>
        </span>
    </div>

    <nav class="sidebar-nav">
        <?= navItem('dashboard', t('nav.dashboard'), APP_URL . '/modules/dashboard/index.php', 'dashboard', $activeMenu) ?>

        <div class="nav-section-label"><?= e(t('nav.section.assets')) ?></div>
        <?= navItem('assets', t('nav.assets'), APP_URL . '/modules/assets/list.php', 'assets', $activeMenu) ?>
        <?= navItem('assigned', t('nav.assigned'), APP_URL . '/modules/assigned/list.php', 'allocations', $activeMenu) ?>
        <?= navItem('transfers', t('nav.transfers'), APP_URL . '/modules/transfers/list.php', 'transfers', $activeMenu) ?>
        <?= navItem('maintenance', t('nav.maintenance'), APP_URL . '/modules/maintenance/list.php', 'maintenance', $activeMenu) ?>
        <?= navItem('audits', t('nav.audits'), APP_URL . '/modules/audits/list.php', 'audits', $activeMenu) ?>
        <?= navItem('disposals', t('nav.disposals'), APP_URL . '/modules/disposals/list.php', 'disposals', $activeMenu) ?>

        <div class="nav-section-label"><?= e(t('nav.section.requests')) ?></div>
        <?= navItem('requisitions', t('nav.requisitions'), APP_URL . '/modules/requisitions/list.php', 'requisitions', $activeMenu) ?>

        <div class="nav-section-label"><?= e(t('nav.section.insights')) ?></div>
        <?= navItem('reports', t('nav.reports'), APP_URL . '/modules/reports/index.php', 'reports', $activeMenu) ?>

        <?php if ($role === ROLE_ADMIN): ?>
        <div class="nav-section-label"><?= e(t('nav.section.admin')) ?></div>
        <?= navItem('users', t('nav.users'), APP_URL . '/modules/users/list.php', 'users', $activeMenu) ?>
        <?= navItem('departments', t('nav.departments'), APP_URL . '/modules/departments/list.php', 'departments', $activeMenu) ?>
        <?= navItem('categories', t('nav.categories'), APP_URL . '/modules/categories/list.php', 'categories', $activeMenu) ?>
        <?= navItem('locations', t('nav.locations'), APP_URL . '/modules/locations/list.php', 'locations', $activeMenu) ?>
        <?= navItem('settings', t('nav.settings'), APP_URL . '/modules/settings/index.php', 'settings', $activeMenu) ?>
        <?php endif; ?>
    </nav>
</aside>
