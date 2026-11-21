<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$slug = trim((string)($_GET['slug'] ?? ''));
if ($slug === '') {
    redirect(url('categories.php'));
}

$category = ProjectRepository::findCategoryBySlug($slug);
if (!$category) {
    flash('warning', 'The requested category could not be found.');
    redirect(url('categories.php'));
}

$sort = trim((string)($_GET['sort'] ?? 'latest'));
$rawPricing = (string)($_GET['pricing'] ?? 'all');
$pricing = in_array($rawPricing, ['all', 'paid', 'free'], true) ? $rawPricing : 'all';
$page = max(1, (int)($_GET['page'] ?? 1));

$result = ProjectRepository::search([
    'category' => $category['slug'],
    'pricing' => $pricing,
    'sort' => $sort,
], $page, PROJECTS_PER_PAGE);

$allCategories = global_categories(false);
$sortOptions = [
    'latest' => 'Latest Releases',
    'oldest' => 'Oldest First',
    'price_asc' => 'Price: Low to High',
    'price_desc' => 'Price: High to Low',
    'popular' => 'Most Popular',
    'best_rated' => 'Best Rated',
    'best_selling' => 'Best Selling',
];

$pageTitle = (string)($category['meta_title'] ?: $category['name']);
$pageDescription = (string)($category['meta_description'] ?: $category['short_description']);
$pageCanonical = absolute_url('category.php?slug=' . urlencode((string)$category['slug']));
$pageStyles = ['marketplace.css'];
$pageScripts = ['filters.js'];

require INCLUDES_PATH . '/layouts/header.php';
?>

<section class="pm-page-header">
    <div class="pm-container">
        <nav class="pm-breadcrumb" aria-label="Breadcrumb">
            <a href="<?= e(url('index.php')) ?>"><?= icon('home', 14) ?><span>Home</span></a>
            <?= icon('chevron-right', 14) ?>
            <a href="<?= e(url('categories.php')) ?>">Categories</a>
            <?= icon('chevron-right', 14) ?>
            <span><?= e($category['name']) ?></span>
        </nav>

        <div class="pm-page-header-row">
            <div class="pm-category-hero-head">
                <span class="pm-cat-icon" style="width:56px;height:56px;flex-shrink:0;--cat-accent:<?= e($category['accent_color']) ?>;">
                    <?= category_icon_html($category, 26) ?>
                </span>
                <div>
                    <h1 class="pm-h1"><?= e($category['name']) ?></h1>
                    <p class="pm-muted" style="margin-top:6px;"><?= e($category['description'] ?: $category['short_description']) ?></p>
                </div>
            </div>

            <div class="pm-btn-row">
                <span class="pm-badge pm-badge-primary" style="padding:7px 13px;font-size:0.8rem;">
                    <?= icon('package', 14) ?><span><?= (int)$category['projects_count'] ?> Projects</span>
                </span>
                <span class="pm-badge pm-badge-success" style="padding:7px 13px;font-size:0.8rem;">
                    <?= icon('shopping-bag', 14) ?><span><?= number_format((int)$category['total_sales']) ?> Sales</span>
                </span>
            </div>
        </div>

        <div style="display:flex;gap:8px;overflow-x:auto;padding-top:22px;padding-bottom:2px;">
            <?php foreach ($allCategories as $c): ?>
                <a href="<?= e(url('category.php?slug=' . urlencode((string)$c['slug']))) ?>"
                   class="pm-chip <?= $c['slug'] === $category['slug'] ? 'is-active' : '' ?>" style="flex-shrink:0;">
                    <?= icon((string)$c['icon_name'], 13) ?>
                    <span><?= e($c['name']) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="pm-section-tight">
    <div class="pm-container">
        <div class="pm-catalog-toolbar">
            <div class="pm-btn-row">
                <a href="<?= e(build_query_url('category.php', ['slug' => $category['slug'], 'pricing' => 'all'], ['page'])) ?>" class="pm-chip <?= $pricing === 'all' ? 'is-active' : '' ?>">All Items (<?= $result['total'] ?>)</a>
                <a href="<?= e(build_query_url('category.php', ['slug' => $category['slug'], 'pricing' => 'paid'], ['page'])) ?>" class="pm-chip <?= $pricing === 'paid' ? 'is-active' : '' ?>">Premium</a>
                <a href="<?= e(build_query_url('category.php', ['slug' => $category['slug'], 'pricing' => 'free'], ['page'])) ?>" class="pm-chip <?= $pricing === 'free' ? 'is-active' : '' ?>">Free</a>
            </div>
            <div class="pm-toolbar-controls">
                <select class="pm-select pm-sort-select" data-sort-select aria-label="Sort category projects">
                    <?php foreach ($sortOptions as $val => $lbl): ?>
                        <option value="<?= e($val) ?>" <?= $result['sort'] === $val ? 'selected' : '' ?>><?= e($lbl) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <?php if (empty($result['items'])): ?>
            <div class="pm-card pm-empty">
                <div class="pm-icon-orb"><?= icon((string)$category['icon_name'], 26) ?></div>
                <h2 class="pm-h3">No projects in <?= e($category['name']) ?> matching this filter yet</h2>
                <p class="pm-muted" style="max-width:440px;">Explore other active categories or apply as a verified seller to publish the next release in <?= e($category['name']) ?>.</p>
                <div class="pm-btn-row" style="margin-top:8px;">
                    <a href="<?= e(url('projects.php')) ?>" class="pm-btn pm-btn-primary"><?= icon('grid', 16) ?><span>Explore All Projects</span></a>
                    <a href="<?= e(url('seller/become-seller.php')) ?>" class="pm-btn pm-btn-secondary"><?= icon('store', 16) ?><span>Publish a Project</span></a>
                </div>
            </div>
        <?php else: ?>
            <div class="pm-project-grid" data-catalog-grid>
                <?php foreach ($result['items'] as $project):
                    require INCLUDES_PATH . '/components/project-card.php';
                endforeach; ?>
            </div>
            <?php
            $currentPage = $result['page'];
            $totalPages = $result['total_pages'];
            $paginationBasePath = 'category.php';
            require INCLUDES_PATH . '/components/pagination.php';
            ?>
        <?php endif; ?>
    </div>
</section>

<?php require INCLUDES_PATH . '/layouts/footer.php'; ?>
