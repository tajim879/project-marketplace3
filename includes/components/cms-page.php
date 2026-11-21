<?php
declare(strict_types=1);

/**
 * Reusable Database-Backed CMS Page Renderer (About, Terms, Privacy, Refund, License)
 * Expects string $cmsSlug.
 */
$stmt = db()->prepare("SELECT * FROM `pages` WHERE `slug` = ? AND `status` = 'published' LIMIT 1");
$stmt->execute([$cmsSlug]);
$page = $stmt->fetch();

if (!$page) {
    redirect(url('index.php'));
}

$policyLinks = [
    ['slug' => 'about', 'url' => 'about.php', 'label' => 'About Marketplace', 'icon' => 'info'],
    ['slug' => 'terms', 'url' => 'terms.php', 'label' => 'Terms & Conditions', 'icon' => 'file-text'],
    ['slug' => 'privacy', 'url' => 'privacy.php', 'label' => 'Privacy Policy', 'icon' => 'lock'],
    ['slug' => 'refund-policy', 'url' => 'refund-policy.php', 'label' => 'Refund Policy', 'icon' => 'receipt'],
    ['slug' => 'license-policy', 'url' => 'license-policy.php', 'label' => 'License Policy', 'icon' => 'shield'],
    ['slug' => 'faq', 'url' => 'faq.php', 'label' => 'FAQ Center', 'icon' => 'help-circle'],
    ['slug' => 'contact', 'url' => 'contact.php', 'label' => 'Contact Support', 'icon' => 'mail'],
];

$pageTitle = (string)($page['meta_title'] ?: $page['title']);
$pageDescription = (string)($page['meta_description'] ?: $page['subtitle']);
$pageStyles = ['buyer.css'];

require INCLUDES_PATH . '/layouts/header.php';
?>

<section class="pm-page-header">
    <div class="pm-container">
        <nav class="pm-breadcrumb" aria-label="Breadcrumb">
            <a href="<?= e(url('index.php')) ?>"><?= icon('home', 14) ?><span>Home</span></a>
            <?= icon('chevron-right', 14) ?>
            <span><?= e($page['title']) ?></span>
        </nav>
        <h1 class="pm-h1"><?= e($page['title']) ?></h1>
        <?php if (!empty($page['subtitle'])): ?>
            <p class="pm-muted" style="margin-top:6px;max-width:680px;"><?= e($page['subtitle']) ?></p>
        <?php endif; ?>
        <small class="pm-muted" style="display:inline-flex;align-items:center;gap:6px;margin-top:12px;font-size:0.78rem;">
            <?= icon('calendar', 13) ?>Last updated <?= e(format_date((string)$page['updated_at'])) ?>
        </small>
    </div>
</section>

<section class="pm-section-tight">
    <div class="pm-container">
        <div class="pm-dashboard-shell">
            <aside class="pm-card pm-card-pad" style="position:sticky;top:calc(var(--header-height) + 20px);">
                <h2 class="pm-h4" style="margin-bottom:12px;">Information &amp; Policies</h2>
                <nav class="pm-dash-nav">
                    <?php foreach ($policyLinks as $pl): ?>
                        <a href="<?= e(url($pl['url'])) ?>" class="pm-dash-link <?= $pl['slug'] === $cmsSlug ? 'is-active' : '' ?>">
                            <span class="pm-dash-link-left">
                                <?= icon($pl['icon'], 16) ?>
                                <span><?= e($pl['label']) ?></span>
                            </span>
                        </a>
                    <?php endforeach; ?>
                </nav>
            </aside>

            <article class="pm-card pm-card-pad" style="padding:34px;">
                <div class="pm-prose">
                    <?= $page['content'] /* Trusted admin-authored CMS HTML stored in pages table */ ?>
                </div>
            </article>
        </div>
    </div>
</section>

<?php require INCLUDES_PATH . '/layouts/footer.php'; ?>
