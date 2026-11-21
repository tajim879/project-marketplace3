<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$stats = marketplace_stats();
$allCategories = global_categories(false);
$popularTechs = ProjectRepository::popularTechnologies(6);

// Load admin-manageable homepage sections ordered by sort_order
$secRows = db()->query(
    "SELECT * FROM `homepage_sections` WHERE `is_visible` = 1 ORDER BY `sort_order` ASC, `id` ASC"
)->fetchAll();

$heroBadge = site_setting('hero_badge', 'Curated Multi-Vendor Digital Code Marketplace');
$heroHeading = site_setting('hero_title', site_setting('hero_heading', 'Build faster with verified source code, apps & templates'));
$heroDescription = site_setting('hero_subtitle', site_setting('hero_description', ''));
$heroPrimaryCta = site_setting('hero_primary_cta_label', site_setting('hero_primary_cta', 'Explore Projects'));
$heroPrimaryUrl = site_setting('hero_primary_cta_url', 'projects.php');
$heroSecondaryCta = site_setting('hero_secondary_cta_label', site_setting('hero_secondary_cta', 'Start Selling'));
$heroSecondaryUrl = site_setting('hero_secondary_cta_url', 'register.php?role=seller');
$showStatsBar = site_setting('show_stats_bar', '1') === '1';

$pageStyles = ['homepage.css'];
$structuredData = [
    '@context' => 'https://schema.org',
    '@type' => 'WebSite',
    'name' => site_setting('site_name', 'CodeVault'),
    'url' => absolute_url('index.php'),
    'potentialAction' => [
        '@type' => 'SearchAction',
        'target' => absolute_url('search.php') . '?q={search_term_string}',
        'query-input' => 'required name=search_term_string',
    ],
];

require INCLUDES_PATH . '/layouts/header.php';
?>

<!-- HERO SECTION -->
<section class="pm-hero">
    <div class="pm-hero-decor" aria-hidden="true">
        <svg class="pm-hero-grid-svg" viewBox="0 0 1440 540" fill="none">
            <defs>
                <pattern id="heroGrid" width="56" height="56" patternUnits="userSpaceOnUse">
                    <path d="M 56 0 L 0 0 0 56" stroke="#635BFF" stroke-opacity="0.065" stroke-width="1"/>
                </pattern>
                <radialGradient id="gridFade" cx="50%" cy="35%" r="55%">
                    <stop offset="0%" stop-color="#FFFFFF" stop-opacity="1"/>
                    <stop offset="100%" stop-color="#FFFFFF" stop-opacity="0"/>
                </radialGradient>
                <mask id="heroMask">
                    <rect width="1440" height="540" fill="url(#gridFade)"/>
                </mask>
            </defs>
            <rect width="1440" height="540" fill="url(#heroGrid)" mask="url(#heroMask)"/>
        </svg>
    </div>

    <div class="pm-container">
        <?php require INCLUDES_PATH . '/components/alerts.php'; ?>

        <div class="pm-hero-center">
            <span class="pm-eyebrow">
                <?= icon('sparkles', 14) ?>
                <span><?= e($heroBadge) ?></span>
            </span>

            <h1 class="pm-display"><?= e($heroHeading) ?></h1>

            <p class="pm-hero-desc"><?= e($heroDescription) ?></p>

            <form action="<?= e(url('search.php')) ?>" method="get" class="pm-hero-search" role="search">
                <span class="pm-hero-search-icon"><?= icon('search', 20) ?></span>
                <label for="heroSearchInput" class="pm-sr-only">Search digital projects</label>
                <input type="search"
                       id="heroSearchInput"
                       name="q"
                       class="pm-hero-search-input"
                       placeholder="Search PHP ERP, Kotlin fintech app, WooCommerce theme, Python AI...">

                <label for="heroCatSelect" class="pm-sr-only">Filter by category</label>
                <select id="heroCatSelect" name="category" class="pm-hero-search-select">
                    <option value="">All Categories</option>
                    <?php foreach ($allCategories as $cat): ?>
                        <option value="<?= e($cat['slug']) ?>"><?= e($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>

                <button type="submit" class="pm-btn pm-btn-primary">
                    <?= icon('search', 17) ?>
                    <span>Search</span>
                </button>
            </form>

            <?php if (!empty($popularTechs)): ?>
                <div class="pm-hero-tags">
                    <span>Trending stacks:</span>
                    <?php foreach ($popularTechs as $tech): ?>
                        <a href="<?= e(url('projects.php?tech=' . urlencode((string)$tech['tech_slug']))) ?>" class="pm-chip">
                            <?= e($tech['tech_name']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="pm-hero-ctas">
                <a href="<?= e(url($heroPrimaryUrl)) ?>" class="pm-btn pm-btn-primary pm-btn-lg">
                    <?= icon('grid', 18) ?>
                    <span><?= e($heroPrimaryCta) ?></span>
                </a>
                <a href="<?= e(url(is_approved_seller() ? 'seller/upload-project.php' : $heroSecondaryUrl)) ?>" class="pm-btn pm-btn-success pm-btn-lg">
                    <?= icon('store', 18) ?>
                    <span><?= e(is_approved_seller() ? 'Upload Project' : $heroSecondaryCta) ?></span>
                </a>
            </div>

            <div style="margin-top:22px;display:flex;flex-direction:column;align-items:center;gap:10px;">
                <span class="pm-muted" style="font-size:0.78rem;font-weight:700;letter-spacing:0.03em;text-transform:uppercase;">Instant Verified Checkout &amp; Seller Payouts</span>
                <div class="pm-pay-strip" style="justify-content:center;">
                    <?= payment_brand_badge('bkash', 'lg') ?>
                    <?= payment_brand_badge('nagad', 'lg') ?>
                    <?= payment_brand_badge('rocket', 'lg') ?>
                </div>
            </div>
        </div>

        <?php if ($showStatsBar): ?>
        <!-- LIVE MARKETPLACE STATISTICS -->
        <div class="pm-stats-bar" aria-label="Marketplace statistics">
            <div class="pm-stat-tile">
                <span class="pm-stat-icon"><?= icon('package', 22) ?></span>
                <div>
                    <strong class="pm-stat-value"><?= number_format($stats['total_projects']) ?></strong>
                    <span class="pm-stat-label">Verified Projects</span>
                </div>
            </div>
            <div class="pm-stat-tile">
                <span class="pm-stat-icon is-success"><?= icon('badge-check', 22) ?></span>
                <div>
                    <strong class="pm-stat-value"><?= number_format($stats['total_sellers']) ?></strong>
                    <span class="pm-stat-label">Approved Sellers</span>
                </div>
            </div>
            <div class="pm-stat-tile">
                <span class="pm-stat-icon"><?= icon('users', 22) ?></span>
                <div>
                    <strong class="pm-stat-value"><?= number_format($stats['total_buyers']) ?></strong>
                    <span class="pm-stat-label">Active Members</span>
                </div>
            </div>
            <div class="pm-stat-tile">
                <span class="pm-stat-icon is-warning"><?= icon('shopping-bag', 22) ?></span>
                <div>
                    <strong class="pm-stat-value"><?= number_format($stats['total_sales']) ?></strong>
                    <span class="pm-stat-label">Total Sales Delivered</span>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php if ((int)$stats['total_projects'] === 0): ?>
<section class="pm-section pm-section-alt">
    <div class="pm-container">
        <div class="pm-card pm-empty" style="padding:46px 28px;">
            <div class="pm-icon-orb"><?= icon('upload', 28) ?></div>
            <h2 class="pm-h2">Publish the First Verified Project</h2>
            <p class="pm-muted" style="max-width:540px;">
                This marketplace runs exclusively on real seller uploads — zero demo listings. Create a Seller account to upload PHP, HTML/CSS, JavaScript, Android, Java, Kotlin, WordPress, Python, or UI/UX projects and earn in BDT via bKash, Nagad &amp; Rocket.
            </p>
            <div class="pm-btn-row" style="margin-top:10px;">
                <a href="<?= e(url(is_approved_seller() ? 'seller/upload-project.php' : 'register.php?role=seller')) ?>" class="pm-btn pm-btn-primary pm-btn-lg">
                    <?= icon('store', 18) ?><span><?= is_approved_seller() ? 'Upload Your First Project' : 'Create Seller Account' ?></span>
                </a>
                <a href="<?= e(url('categories.php')) ?>" class="pm-btn pm-btn-soft pm-btn-lg">
                    <?= icon('categories', 18) ?><span>Browse All <?= count($allCategories) ?> Categories</span>
                </a>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<?php
// Render every enabled homepage section in admin-configured order
foreach ($secRows as $sec):
    $key = (string)$sec['section_key'];
    $limit = max(1, (int)$sec['items_limit']);
    $badge = (string)($sec['badge_text'] ?? '');
    $title = (string)$sec['title'];
    $subtitle = (string)($sec['subtitle'] ?? '');

    if ($key === 'popular_categories'):
        $popCats = global_categories(true, $limit);
?>
<section class="pm-section">
    <div class="pm-container">
        <div class="pm-section-head">
            <div class="pm-section-head-copy">
                <?php if ($badge !== ''): ?><span class="pm-eyebrow"><?= icon('categories', 14) ?><span><?= e($badge) ?></span></span><?php endif; ?>
                <h2 class="pm-h2"><?= e($title) ?></h2>
                <?php if ($subtitle !== ''): ?><p class="pm-muted"><?= e($subtitle) ?></p><?php endif; ?>
            </div>
            <a href="<?= e(url('categories.php')) ?>" class="pm-btn pm-btn-soft">
                <span>All <?= count($allCategories) ?> Categories</span><?= icon('arrow-right', 16) ?>
            </a>
        </div>
        <div class="pm-cat-grid">
            <?php foreach ($popCats as $category):
                require INCLUDES_PATH . '/components/category-card.php';
            endforeach; ?>
        </div>
    </div>
</section>

<?php elseif ($key === 'featured_projects'):
        $items = ProjectRepository::homepageCollection('featured', $limit);
        if (!empty($items)): ?>
<section class="pm-section pm-section-alt">
    <div class="pm-container">
        <div class="pm-section-head">
            <div class="pm-section-head-copy">
                <?php if ($badge !== ''): ?><span class="pm-eyebrow"><?= icon('award', 14) ?><span><?= e($badge) ?></span></span><?php endif; ?>
                <h2 class="pm-h2"><?= e($title) ?></h2>
                <?php if ($subtitle !== ''): ?><p class="pm-muted"><?= e($subtitle) ?></p><?php endif; ?>
            </div>
            <a href="<?= e(url('featured-projects.php')) ?>" class="pm-btn pm-btn-soft">
                <span>View All Featured</span><?= icon('arrow-right', 16) ?>
            </a>
        </div>
        <div class="pm-project-grid">
            <?php foreach ($items as $project):
                require INCLUDES_PATH . '/components/project-card.php';
            endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php elseif ($key === 'trending_projects'):
        $items = ProjectRepository::homepageCollection('trending', $limit);
        if (!empty($items)): ?>
<section class="pm-section">
    <div class="pm-container">
        <div class="pm-section-head">
            <div class="pm-section-head-copy">
                <?php if ($badge !== ''): ?><span class="pm-eyebrow"><?= icon('trending-up', 14) ?><span><?= e($badge) ?></span></span><?php endif; ?>
                <h2 class="pm-h2"><?= e($title) ?></h2>
                <?php if ($subtitle !== ''): ?><p class="pm-muted"><?= e($subtitle) ?></p><?php endif; ?>
            </div>
            <a href="<?= e(url('projects.php?sort=popular')) ?>" class="pm-btn pm-btn-secondary">
                <span>Explore Popular</span><?= icon('arrow-right', 16) ?>
            </a>
        </div>
        <div class="pm-project-grid">
            <?php foreach ($items as $project):
                require INCLUDES_PATH . '/components/project-card.php';
            endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php elseif ($key === 'latest_projects'):
        $items = ProjectRepository::homepageCollection('latest', $limit);
        if (!empty($items)): ?>
<section class="pm-section pm-section-alt">
    <div class="pm-container">
        <div class="pm-section-head">
            <div class="pm-section-head-copy">
                <?php if ($badge !== ''): ?><span class="pm-eyebrow"><?= icon('clock', 14) ?><span><?= e($badge) ?></span></span><?php endif; ?>
                <h2 class="pm-h2"><?= e($title) ?></h2>
                <?php if ($subtitle !== ''): ?><p class="pm-muted"><?= e($subtitle) ?></p><?php endif; ?>
            </div>
            <a href="<?= e(url('projects.php?sort=latest')) ?>" class="pm-btn pm-btn-secondary">
                <span>Browse Latest</span><?= icon('arrow-right', 16) ?>
            </a>
        </div>
        <div class="pm-project-grid">
            <?php foreach ($items as $project):
                require INCLUDES_PATH . '/components/project-card.php';
            endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php elseif ($key === 'best_selling'):
        $items = ProjectRepository::homepageCollection('best_selling', $limit);
        if (!empty($items)): ?>
<section class="pm-section">
    <div class="pm-container">
        <div class="pm-section-head">
            <div class="pm-section-head-copy">
                <?php if ($badge !== ''): ?><span class="pm-eyebrow"><?= icon('chart', 14) ?><span><?= e($badge) ?></span></span><?php endif; ?>
                <h2 class="pm-h2"><?= e($title) ?></h2>
                <?php if ($subtitle !== ''): ?><p class="pm-muted"><?= e($subtitle) ?></p><?php endif; ?>
            </div>
            <a href="<?= e(url('projects.php?sort=best_selling')) ?>" class="pm-btn pm-btn-secondary">
                <span>All Best Sellers</span><?= icon('arrow-right', 16) ?>
            </a>
        </div>
        <div class="pm-project-grid">
            <?php foreach ($items as $project):
                require INCLUDES_PATH . '/components/project-card.php';
            endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php elseif ($key === 'free_projects'):
        $items = ProjectRepository::homepageCollection('free', $limit);
        if (!empty($items)): ?>
<section class="pm-section pm-section-alt">
    <div class="pm-container">
        <div class="pm-section-head">
            <div class="pm-section-head-copy">
                <?php if ($badge !== ''): ?><span class="pm-eyebrow"><?= icon('tag', 14) ?><span><?= e($badge) ?></span></span><?php endif; ?>
                <h2 class="pm-h2"><?= e($title) ?></h2>
                <?php if ($subtitle !== ''): ?><p class="pm-muted"><?= e($subtitle) ?></p><?php endif; ?>
            </div>
            <a href="<?= e(url('free-projects.php')) ?>" class="pm-btn pm-btn-emerald-soft">
                <span>All Free Projects</span><?= icon('arrow-right', 16) ?>
            </a>
        </div>
        <div class="pm-project-grid">
            <?php foreach ($items as $project):
                require INCLUDES_PATH . '/components/project-card.php';
            endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php elseif ($key === 'top_sellers'):
        $sellers = UserRepository::topRatedSellers($limit);
        if (!empty($sellers)): ?>
<section class="pm-section">
    <div class="pm-container">
        <div class="pm-section-head">
            <div class="pm-section-head-copy">
                <?php if ($badge !== ''): ?><span class="pm-eyebrow"><?= icon('badge-check', 14) ?><span><?= e($badge) ?></span></span><?php endif; ?>
                <h2 class="pm-h2"><?= e($title) ?></h2>
                <?php if ($subtitle !== ''): ?><p class="pm-muted"><?= e($subtitle) ?></p><?php endif; ?>
            </div>
            <a href="<?= e(url('seller/become-seller.php')) ?>" class="pm-btn pm-btn-secondary">
                <?= icon('store', 16) ?><span>Join as Author</span>
            </a>
        </div>
        <div class="pm-grid-4">
            <?php foreach ($sellers as $seller):
                require INCLUDES_PATH . '/components/seller-card.php';
            endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php elseif ($key === 'promo_banner'):
        $banner = db()->query("SELECT * FROM `banners` WHERE `position` = 'homepage_promo' AND `status` = 'active' ORDER BY `sort_order` ASC LIMIT 1")->fetch();
        $commRate = (float)site_setting('commission_percentage', '10');
        $sellerShare = 100 - $commRate;
        if ($banner): ?>
<section class="pm-section-tight">
    <div class="pm-container">
        <div class="pm-promo-banner">
            <div class="pm-promo-copy">
                <?php if (!empty($banner['badge_text'])): ?>
                    <span class="pm-eyebrow"><?= icon('wallet', 14) ?><span><?= e($banner['badge_text']) ?></span></span>
                <?php endif; ?>
                <h2 class="pm-h1"><?= e($banner['title']) ?></h2>
                <p class="pm-lead" style="font-size:0.98rem;"><?= e($banner['subtitle']) ?></p>
                <div class="pm-btn-row" style="margin-top:6px;">
                    <?php if (!empty($banner['primary_cta_label'])): ?>
                        <a href="<?= e(url((string)$banner['primary_cta_url'])) ?>" class="pm-btn pm-btn-success pm-btn-lg">
                            <?= icon('store', 18) ?><span><?= e($banner['primary_cta_label']) ?></span>
                        </a>
                    <?php endif; ?>
                    <?php if (!empty($banner['secondary_cta_label'])): ?>
                        <a href="<?= e(url((string)$banner['secondary_cta_url'])) ?>" class="pm-btn pm-btn-soft pm-btn-lg">
                            <span><?= e($banner['secondary_cta_label']) ?></span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="pm-promo-visual">
                <div class="pm-promo-metric-card">
                    <div>
                        <strong><?= number_format($sellerShare, 0) ?>% Author Earnings</strong>
                        <span>Only <?= number_format($commRate, 0) ?>% flat platform commission per sale</span>
                    </div>
                    <span class="pm-stat-icon is-success"><?= icon('wallet', 22) ?></span>
                </div>
                <div class="pm-promo-metric-card">
                    <div>
                        <strong>bKash · Nagad · Rocket</strong>
                        <span>Direct local payouts backed by audited ledger records</span>
                    </div>
                    <span class="pm-stat-icon"><?= icon('banknote', 22) ?></span>
                </div>
                <div class="pm-promo-metric-card">
                    <div>
                        <strong>Private ZIP Vault</strong>
                        <span>License keys &amp; zero public exposure for your source code</span>
                    </div>
                    <span class="pm-stat-icon"><?= icon('shield', 22) ?></span>
                </div>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<?php elseif ($key === 'testimonials'):
        $testimonials = ProjectRepository::featuredTestimonials($limit);
        if (!empty($testimonials)): ?>
<section class="pm-section">
    <div class="pm-container">
        <div class="pm-section-head">
            <div class="pm-section-head-copy">
                <?php if ($badge !== ''): ?><span class="pm-eyebrow"><?= icon('star', 14) ?><span><?= e($badge) ?></span></span><?php endif; ?>
                <h2 class="pm-h2"><?= e($title) ?></h2>
                <?php if ($subtitle !== ''): ?><p class="pm-muted"><?= e($subtitle) ?></p><?php endif; ?>
            </div>
        </div>
        <div class="pm-grid-3">
            <?php foreach ($testimonials as $t): ?>
                <article class="pm-card pm-testimonial-card">
                    <div class="pm-testimonial-top">
                        <?= render_stars((float)$t['rating'], 15) ?>
                        <span class="pm-quote-mark"><?= icon('quote', 16) ?></span>
                    </div>
                    <p class="pm-testimonial-text">"<?= e($t['comment']) ?>"</p>
                    <div class="pm-testimonial-foot">
                        <div class="pm-testimonial-buyer">
                            <span class="pm-avatar"><?= e(user_initials((string)$t['buyer_name'])) ?></span>
                            <div>
                                <strong><?= e($t['buyer_name']) ?></strong>
                                <small>Purchased <a class="pm-link" href="<?= e(url('project.php?slug=' . urlencode((string)$t['project_slug']))) ?>"><?= e($t['project_title']) ?></a></small>
                            </div>
                        </div>
                        <span class="pm-badge pm-badge-success"><?= icon('check', 12) ?><span>Verified</span></span>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php elseif ($key === 'faq'):
        $faqs = db()->query("SELECT * FROM `faqs` WHERE `status` = 'active' AND `show_on_homepage` = 1 ORDER BY `sort_order` ASC LIMIT {$limit}")->fetchAll();
        if (!empty($faqs)): ?>
<section class="pm-section pm-section-alt">
    <div class="pm-container">
        <div class="pm-section-head" style="justify-content:center;text-align:center;">
            <div class="pm-section-head-copy" style="justify-items:center;">
                <?php if ($badge !== ''): ?><span class="pm-eyebrow"><?= icon('help-circle', 14) ?><span><?= e($badge) ?></span></span><?php endif; ?>
                <h2 class="pm-h2"><?= e($title) ?></h2>
                <?php if ($subtitle !== ''): ?><p class="pm-muted"><?= e($subtitle) ?></p><?php endif; ?>
            </div>
        </div>
        <div class="pm-faq-grid">
            <?php foreach ($faqs as $idx => $faq): ?>
                <div class="pm-faq-item <?= $idx === 0 ? 'is-open' : '' ?>">
                    <button type="button" class="pm-faq-trigger" data-faq-toggle aria-expanded="<?= $idx === 0 ? 'true' : 'false' ?>">
                        <span><?= e($faq['question']) ?></span>
                        <span class="pm-faq-chevron"><?= icon('chevron-down', 16) ?></span>
                    </button>
                    <div class="pm-faq-panel"><?= e($faq['answer']) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
        <div style="text-align:center;margin-top:26px;">
            <a href="<?= e(url('faq.php')) ?>" class="pm-btn pm-btn-soft">
                <span>View Complete FAQ Center</span><?= icon('arrow-right', 16) ?>
            </a>
        </div>
    </div>
</section>
<?php endif; ?>

<?php elseif ($key === 'newsletter'): ?>
<section class="pm-section">
    <div class="pm-container">
        <div class="pm-card pm-newsletter-card">
            <div class="pm-newsletter-copy">
                <?php if ($badge !== ''): ?><span class="pm-eyebrow" style="width:fit-content;"><?= icon('mail', 14) ?><span><?= e($badge) ?></span></span><?php endif; ?>
                <h2 class="pm-h2" style="margin-top:4px;"><?= e($title) ?></h2>
                <?php if ($subtitle !== ''): ?><p class="pm-muted"><?= e($subtitle) ?></p><?php endif; ?>
            </div>
            <form id="pmNewsletterForm" class="pm-newsletter-form">
                <div class="pm-input-wrap" style="flex:1;">
                    <?= icon('mail', 17) ?>
                    <label for="newsletterEmail" class="pm-sr-only">Email address</label>
                    <input type="email" id="newsletterEmail" name="email" class="pm-input" placeholder="you@company.com" required>
                </div>
                <button type="submit" class="pm-btn pm-btn-sky">
                    <span>Subscribe</span><?= icon('send', 15) ?>
                </button>
            </form>
        </div>
    </div>
</section>
<?php endif;
endforeach; ?>

<?php require INCLUDES_PATH . '/layouts/footer.php'; ?>
