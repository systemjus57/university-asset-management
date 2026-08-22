<?php
/**
 * Shared page header/shell. Expects $pageTitle and optional $activeMenu
 * to be set by the including module before this file is required.
 */
$pageTitle  = $pageTitle ?? APP_NAME;
$activeMenu = $activeMenu ?? '';
$user       = currentUser();
$uniName    = isset($pdo) ? getSetting($pdo, 'university_name', 'Somali National University') : 'Somali National University';
$theme      = isset($pdo) ? getSetting($pdo, 'theme', 'light') : 'light';
$pendingAlerts = isset($pdo) ? getPendingAlerts($pdo) : [];
$pendingAlertTotal = array_sum(array_column($pendingAlerts, 'count'));
$notifications = ($user && isset($pdo)) ? getUserNotifications($pdo, (int) $user['user_id'], 10) : [];
$unreadNotificationCount = ($user && isset($pdo)) ? getUnreadNotificationCount($pdo, (int) $user['user_id']) : 0;
$bellTotal = $pendingAlertTotal + $unreadNotificationCount;
$notificationModuleUrls = [
    'requisitions' => APP_URL . '/modules/requisitions/list.php',
    'disposals'    => APP_URL . '/modules/disposals/list.php',
    'maintenance'  => APP_URL . '/modules/maintenance/list.php',
    'transfers'    => APP_URL . '/modules/transfers/list.php',
    'auth'         => APP_URL . '/modules/profile/index.php',
];
?>
<!DOCTYPE html>
<html lang="<?= e(activeLanguage()) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?> · <?= e($uniName) ?></title>
<link rel="icon" type="image/webp" href="<?= isset($pdo) ? e(appLogoUrl($pdo)) : APP_URL . '/static/images/logo.webp' ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= APP_URL ?>/static/css/style.css?v=<?= filemtime(APP_ROOT . '/static/css/style.css') ?>">
</head>
<body class="theme-<?= e($theme) ?>">
<div class="app-shell">
    <?php include __DIR__ . '/sidebar.php'; ?>

    <div class="app-main">
        <header class="topbar">
            <button type="button" class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle menu"><?= icon('menu') ?></button>
            <h1 class="page-title"><?= e($pageTitle) ?></h1>
            <div class="topbar-user">
                <div class="topbar-dropdown">
                    <button type="button" class="topbar-bell" aria-label="<?= e(t('topbar.notifications')) ?>" data-dropdown-toggle="alertsMenu">
                        <?= icon('bell') ?>
                        <?php if ($bellTotal > 0): ?><span class="topbar-bell-badge"><?= $bellTotal > 9 ? '9+' : $bellTotal ?></span><?php endif; ?>
                    </button>
                    <div class="topbar-dropdown-menu topbar-alerts-menu" id="alertsMenu">
                        <?php if ($pendingAlerts): ?>
                            <div class="topbar-alerts-header">Needs Your Attention</div>
                            <?php foreach ($pendingAlerts as $alert): ?>
                                <a href="<?= e($alert['url']) ?>"><?= icon('bell') ?> <span><?= e($alert['text']) ?></span></a>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <div class="topbar-alerts-header" style="display:flex; align-items:center; justify-content:space-between;">
                            <span><?= e(t('topbar.notifications')) ?></span>
                            <?php if ($unreadNotificationCount > 0): ?>
                                <form method="post" action="<?= APP_URL ?>/modules/notifications/mark_read.php" style="margin:0;">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="mark_all" value="1">
                                    <input type="hidden" name="back" value="<?= e($_SERVER['REQUEST_URI'] ?? '') ?>">
                                    <button type="submit" class="link-count" style="font-size:.75rem; font-weight:500;">Mark all read</button>
                                </form>
                            <?php endif; ?>
                        </div>
                        <?php if (!$pendingAlerts && !$notifications): ?>
                            <div class="topbar-alerts-empty"><?= e(t('topbar.all_caught_up')) ?></div>
                        <?php elseif (!$notifications): ?>
                            <div class="topbar-alerts-empty">No notifications yet.</div>
                        <?php else: ?>
                            <?php foreach ($notifications as $n): ?>
                                <div class="topbar-alerts-item<?= $n['is_read'] ? '' : ' unread' ?>" style="display:flex; align-items:center; gap:.4rem;">
                                    <a href="<?= e($notificationModuleUrls[$n['module']] ?? (APP_URL . '/modules/dashboard/index.php')) ?>" style="flex:1;">
                                        <?= icon('bell') ?> <span><strong><?= e($n['title']) ?></strong> — <?= e($n['message']) ?></span>
                                    </a>
                                    <?php if (!$n['is_read']): ?>
                                        <form method="post" action="<?= APP_URL ?>/modules/notifications/mark_read.php" style="margin:0;">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="notification_id" value="<?= (int) $n['notification_id'] ?>">
                                            <input type="hidden" name="back" value="<?= e($_SERVER['REQUEST_URI'] ?? '') ?>">
                                            <button type="submit" class="link-count" style="font-size:.7rem;" title="Mark read">&#10003;</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="topbar-dropdown">
                    <button type="button" class="topbar-user-btn" data-dropdown-toggle="userMenu">
                        <?php if (!empty($user['profile_picture'])): ?>
                            <img class="topbar-avatar topbar-avatar-img" src="<?= e($user['profile_picture']) ?>" alt="">
                        <?php else: ?>
                            <span class="topbar-avatar"><?= e(mb_strtoupper(mb_substr($user['name'] ?? 'A', 0, 1))) ?></span>
                        <?php endif; ?>
                        <span class="topbar-user-info">
                            <span class="topbar-user-name"><?= e($user['name'] ?? 'Account') ?></span>
                            <span class="topbar-role"><?= e($user['role_name'] ?? '') ?></span>
                        </span>
                        <span class="caret"><?= icon('chevron-down') ?></span>
                    </button>
                    <div class="topbar-dropdown-menu" id="userMenu">
                        <a href="<?= APP_URL ?>/modules/profile/index.php"><?= icon('profile') ?> <?= e(t('topbar.my_profile')) ?></a>
                        <a href="<?= APP_URL ?>/modules/auth/logout.php" class="danger-link"><?= icon('logout') ?> <?= e(t('topbar.logout')) ?></a>
                    </div>
                </div>
            </div>
        </header>

        <main class="content">
            <?php foreach (['success', 'error', 'info'] as $flashKey): ?>
                <?php if ($msg = flash($flashKey)): ?>
                    <div class="alert alert-<?= $flashKey ?>"><?= e($msg) ?></div>
                <?php endif; ?>
            <?php endforeach; ?>
