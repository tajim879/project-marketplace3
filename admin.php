<?php
declare(strict_types=1);

/**
 * Dedicated Private Administration Sign-In (/admin.php)
 * Completely decoupled from the public storefront layout, navigation, and user session.
 */

require_once __DIR__ . '/includes/bootstrap.php';

header('X-Robots-Tag: noindex, nofollow, noarchive');
header('Cache-Control: no-store, no-cache, must-revalidate');

// Provision the first owner account only from explicit deployment environment variables.
require_once INCLUDES_PATH . '/classes/AutoBootstrap.php';
AutoBootstrap::seedOwnerAdmin(db());
$adminSetupMissing = !admin_exists();

if (is_admin()) {
    redirect(url('admin/dashboard.php'));
}

$siteName = site_setting('site_name', 'CodeVault');
$customFav = site_setting('custom_favicon_path', '');
$faviconHref = ($customFav !== '' && is_file(ROOT_PATH . '/' . ltrim($customFav, '/')))
    ? url($customFav)
    : asset_url('images/logo/favicon.svg');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — <?= e($siteName) ?></title>
    <meta name="robots" content="noindex, nofollow, noarchive">
    <meta name="referrer" content="no-referrer">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <meta name="base-url" content="<?= e(base_path()) ?>">
    <link rel="icon" href="<?= e($faviconHref) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset_url('css/variables.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('css/reset.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('css/typography.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('css/components.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('css/auth.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('css/responsive.css')) ?>">
</head>
<body class="pm-admin-login-body">
<main class="pm-admin-login-screen">
    <div class="pm-admin-login-box">
        <div class="pm-card pm-auth-card pm-auth-card-admin">
            <div class="pm-auth-head">
                <div class="pm-auth-admin-orb">
                    <?= icon('lock', 22) ?>
                </div>
                <h1 class="pm-h2">Admin Sign In</h1>
                <p class="pm-muted">Enter your administrator credentials to continue.</p>
            </div>

            <?php require INCLUDES_PATH . '/components/alerts.php'; ?>
            <?php if ($adminSetupMissing): ?>
                <div class="pm-alert pm-alert-warning" role="alert">Owner admin is not configured. Add <code>MARKETPLACE_ADMIN_EMAIL</code> and <code>MARKETPLACE_ADMIN_PASSWORD</code> in your hosting environment. Use a unique password with at least 12 characters, then redeploy.</div>
            <?php endif; ?>

            <form method="post" action="<?= e(url('actions/admin/auth.php')) ?>" class="pm-auth-form" data-submit-lock>
                <?= csrf_field() ?>

                <div class="pm-field">
                    <label class="pm-label" for="identity">Email Address</label>
                    <div class="pm-input-wrap">
                        <?= icon('mail', 17) ?>
                        <input type="email" id="identity" name="identity" class="pm-input" value="<?= e(old('identity')) ?>" placeholder="Enter admin email" autocomplete="username" required autofocus>
                    </div>
                </div>

                <div class="pm-field">
                    <label class="pm-label" for="admin_login_password">Password</label>
                    <div class="pm-input-wrap">
                        <?= icon('lock', 17) ?>
                        <input type="password" id="admin_login_password" name="password" class="pm-input" style="padding-right:44px;" placeholder="Enter password" autocomplete="current-password" required>
                        <button type="button" class="pm-input-action" data-toggle-password="admin_login_password" aria-label="Show password">
                            <span class="icon-show"><?= icon('eye', 17) ?></span>
                            <span class="icon-hide" style="display:none;"><?= icon('eye-off', 17) ?></span>
                        </button>
                    </div>
                </div>

                <button type="submit" class="pm-btn pm-btn-primary pm-btn-lg pm-btn-block">
                    <span>Sign In</span><?= icon('arrow-right', 17) ?>
                </button>
            </form>
        </div>
    </div>
</main>
<script src="<?= e(asset_url('js/ajax.js')) ?>" defer></script>
<script src="<?= e(asset_url('js/app.js')) ?>" defer></script>
<script src="<?= e(asset_url('js/validation.js')) ?>" defer></script>
</body>
</html>
<?php clear_old_input(); ?>
