<?php
declare(strict_types=1);

$siteName = site_setting('site_name', 'CodeVault');
$siteTagline = site_setting('site_tagline', 'Premium Project Marketplace');
$fullTitle = isset($pageTitle) && $pageTitle !== ''
    ? $pageTitle . ' — ' . $siteName
    : site_setting('meta_title', $siteName . ' — ' . $siteTagline);
$metaDesc = $pageDescription ?? site_setting('meta_description', site_setting('site_description', ''));
$canonicalUrl = $pageCanonical ?? absolute_url(current_script_rel());
$robotsPolicy = $pageRobots ?? site_setting('robots_policy', 'index, follow');
$ogImage = $pageOgImage ?? site_setting('og_image', '');
$primaryColor = site_setting('primary_color', '#5B50FF');
$secondaryColor = site_setting('secondary_color', '#475569');
$extraStyles = $pageStyles ?? [];
$extraScripts = $pageScripts ?? [];
    $curScript = current_script_rel();
    if (
        site_setting('maintenance_mode', '0') === '1'
        && !is_admin()
        && !in_array($curScript, ['admin.php', 'login.php', 'logout.php'], true)
        && !str_starts_with($curScript, 'admin/')
    ) {
        http_response_code(503);
        echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Scheduled Maintenance — ' . e($siteName) . '</title><link rel="stylesheet" href="' . e(asset_url('css/variables.css')) . '"><link rel="stylesheet" href="' . e(asset_url('css/reset.css')) . '"><link rel="stylesheet" href="' . e(asset_url('css/typography.css')) . '"><link rel="stylesheet" href="' . e(asset_url('css/components.css')) . '"></head><body style="min-height:100vh;display:grid;place-items:center;padding:24px;background:var(--bg);"><div class="pm-card pm-empty" style="max-width:480px;padding:40px 28px;"><div class="pm-icon-orb">' . icon('settings', 26) . '</div><h1 class="pm-h2">' . e($siteName) . ' is under brief maintenance</h1><p class="pm-muted">We are applying scheduled updates and will be back online shortly. Thank you for your patience.</p></div></body></html>';
        exit;
    }
    $customFav = site_setting('custom_favicon_path', '');
    $faviconHref = ($customFav !== '' && is_file(ROOT_PATH . '/' . ltrim($customFav, '/'))) ? url($customFav) : asset_url('images/logo/favicon.svg');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($fullTitle) ?></title>
    <meta name="description" content="<?= e($metaDesc) ?>">
    <meta name="robots" content="<?= e($robotsPolicy) ?>">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <meta name="base-url" content="<?= e(base_path()) ?>">
    <link rel="canonical" href="<?= e($canonicalUrl) ?>">

    <meta property="og:type" content="<?= e($ogType ?? 'website') ?>">
    <meta property="og:site_name" content="<?= e($siteName) ?>">
    <meta property="og:title" content="<?= e($fullTitle) ?>">
    <meta property="og:description" content="<?= e($metaDesc) ?>">
    <meta property="og:url" content="<?= e($canonicalUrl) ?>">
    <?php if ($ogImage !== ''): ?>
    <meta property="og:image" content="<?= e($ogImage) ?>">
    <?php endif; ?>

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
    <?php foreach ($extraStyles as $cssFile): ?>
    <link rel="stylesheet" href="<?= e(asset_url('css/' . ltrim($cssFile, '/'))) ?>">
    <?php endforeach; ?>
    <link rel="stylesheet" href="<?= e(asset_url('css/responsive.css')) ?>">

    <?php if (preg_match('/^#[0-9a-fA-F]{6}$/', $primaryColor) && !in_array(strtoupper($primaryColor), ['#635BFF', '#5B50FF', '#4F46E5'], true)): ?>
    <style>:root { --primary: <?= e($primaryColor) ?>; --secondary: <?= e(preg_match('/^#[0-9a-fA-F]{6}$/', $secondaryColor) ? $secondaryColor : '#475569') ?>; }</style>
    <?php endif; ?>

    <?php if (!empty($structuredData) && is_array($structuredData)): ?>
    <script type="application/ld+json"><?= json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
    <?php endif; ?>
</head>
<body>
<?php require INCLUDES_PATH . '/layouts/navigation.php'; ?>
<main id="mainContent">
