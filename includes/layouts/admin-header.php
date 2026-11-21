<?php
declare(strict_types=1);

$adminUser = require_admin();
$adminQueues = admin_pending_counts();
$totalUrgent = $adminQueues['payments'] + $adminQueues['withdrawals'] + $adminQueues['projects'] + $adminQueues['tickets'] + $adminQueues['sellers'] + $adminQueues['reports'];
$siteName = site_setting('site_name', 'CodeVault');
$customFavicon = trim(site_setting('custom_favicon_path', ''));
$faviconHref = ($customFavicon !== '' && is_file(ROOT_PATH . '/' . ltrim($customFavicon, '/'))) ? url($customFavicon) : asset_url('images/logo/favicon.svg');
$fullTitle = !empty($pageTitle) ? ($pageTitle . ' — ' . $siteName . ' Admin') : ($siteName . ' Admin Panel');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <meta name="base-url" content="<?= e(base_path()) ?>">
    <title><?= e($fullTitle) ?></title>
    <link rel="icon" href="<?= e($faviconHref) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@600;700;800&family=Noto+Sans+Bengali:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset_url('css/variables.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('css/reset.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('css/typography.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('css/global.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('css/components.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('css/animations.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('css/dashboard.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('css/admin.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('css/responsive.css')) ?>">
</head>
<body class="pm-admin-body">
<header class="pm-admin-topbar">
    <div class="pm-admin-topbar-left">
        <button type="button" class="pm-icon-btn pm-admin-menu-btn" id="pmAdminMenuBtn" aria-label="Toggle admin menu">
            <?= icon('menu', 19) ?>
        </button>
        <a href="<?= e(url('admin/dashboard.php')) ?>" class="pm-brand">
            <?= brand_logo($siteName) ?>
        </a>
        <span class="pm-admin-brand-tag pm-hide-sm">Admin Control</span>
        <?php if (site_setting('maintenance_mode', '0') === '1'): ?>
            <span class="pm-badge pm-badge-warning pm-hide-sm">Maintenance ON</span>
        <?php endif; ?>
    </div>

    <div class="pm-admin-topbar-right">
        <?php if ($totalUrgent > 0): ?>
            <a href="<?= e(url('admin/payments.php')) ?>" class="pm-badge pm-badge-warning pm-hide-sm" style="text-decoration:none;">
                <?= icon('bell', 13) ?><span><?= $totalUrgent ?> Pending</span>
            </a>
        <?php endif; ?>
        <a href="<?= e(url('index.php')) ?>" class="pm-btn pm-btn-secondary pm-btn-sm" target="_blank" rel="noopener">
            <?= icon('external-link', 14) ?><span class="pm-hide-sm">Storefront</span>
        </a>
        <a href="<?= e(url('admin/logout.php')) ?>" class="pm-btn pm-btn-ghost pm-btn-sm" title="Sign Out">
            <?= icon('log-out', 15) ?><span class="pm-hide-sm">Logout</span>
        </a>
    </div>
</header>

<div class="pm-admin-backdrop" id="pmAdminBackdrop"></div>
<div class="pm-admin-layout">
    <?php require INCLUDES_PATH . '/layouts/admin-sidebar.php'; ?>
    <main class="pm-admin-main">
        <?php require INCLUDES_PATH . '/components/alerts.php'; ?>
