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
            <span class="brand-text">Asset Management</span>
            <span class="brand-subtext">University System</span>
        </span>
    </div>

    <nav class="sidebar-nav">
        <?= navItem('dashboard', 'Dashboard', APP_URL . '/modules/dashboard/index.php', 'dashboard', $activeMenu) ?>

        <div class="nav-section-label">Asset Lifecycle</div>
        <?= navItem('assets', 'Assets', APP_URL . '/modules/assets/list.php', 'assets', $activeMenu) ?>
        <?= navItem('assigned', 'Allocations', APP_URL . '/modules/assigned/list.php', 'allocations', $activeMenu) ?>
        <?= navItem('transfers', 'Transfers', APP_URL . '/modules/transfers/list.php', 'transfers', $activeMenu) ?>
        <?= navItem('maintenance', 'Maintenance', APP_URL . '/modules/maintenance/list.php', 'maintenance', $activeMenu) ?>
        <?= navItem('audits', 'Audits', APP_URL . '/modules/audits/list.php', 'audits', $activeMenu) ?>
        <?= navItem('disposals', 'Disposals', APP_URL . '/modules/disposals/list.php', 'disposals', $activeMenu) ?>

        <div class="nav-section-label">Requests</div>
        <?= navItem('requisitions', 'Requisitions', APP_URL . '/modules/requisitions/list.php', 'requisitions', $activeMenu) ?>

        <div class="nav-section-label">Insights</div>
        <?= navItem('reports', 'Reports', APP_URL . '/modules/reports/index.php', 'reports', $activeMenu) ?>

        <?php if ($role === ROLE_ADMIN): ?>
        <div class="nav-section-label">Administration</div>
        <?= navItem('users', 'Users', APP_URL . '/modules/users/list.php', 'users', $activeMenu) ?>
        <?= navItem('departments', 'Departments', APP_URL . '/modules/departments/list.php', 'departments', $activeMenu) ?>
        <?= navItem('categories', 'Categories', APP_URL . '/modules/categories/list.php', 'categories', $activeMenu) ?>
        <?= navItem('locations', 'Locations', APP_URL . '/modules/locations/list.php', 'locations', $activeMenu) ?>
        <?= navItem('settings', 'Settings', APP_URL . '/modules/settings/index.php', 'settings', $activeMenu) ?>
        <?php endif; ?>
    </nav>
</aside>
