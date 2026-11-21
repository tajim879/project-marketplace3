<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$rawPricing = (string)($_GET['pricing'] ?? 'all');
$filters = [
    'q' => trim((string)($_GET['q'] ?? '')),
    'category' => trim((string)($_GET['category'] ?? '')),
    'pricing' => in_array($rawPricing, ['all', 'paid', 'free', 'discounted'], true) ? $rawPricing : 'all',
    'min_price' => trim((string)($_GET['min_price'] ?? '')),
    'max_price' => trim((string)($_GET['max_price'] ?? '')),
    'rating' => trim((string)($_GET['rating'] ?? '')),
    'tech' => trim((string)($_GET['tech'] ?? '')),
    'seller' => trim((string)($_GET['seller'] ?? '')),
    'sort' => trim((string)($_GET['sort'] ?? 'latest')),
];

$page = max(1, (int)($_GET['page'] ?? 1));
$result = ProjectRepository::search($filters, $page, PROJECTS_PER_PAGE);

$categories = global_categories(false);
$technologies = ProjectRepository::popularTechnologies(16);
$sellers = UserRepository::allApprovedSellersForFilter();

$sortOptions = [
    'latest' => 'Latest Releases',
    'oldest' => 'Oldest First',
    'price_asc' => 'Price: Low to High',
    'price_desc' => 'Price: High to Low',
    'popular' => 'Most Popular',
    'best_rated' => 'Best Rated',
    'best_selling' => 'Best Selling',
];

$pageTitle = 'Explore Digital Projects';
$pageDescription = 'Search, filter, and sort production-ready PHP projects, Android & Kotlin apps, WordPress themes & plugins, Python tools, and UI/UX templates.';
$pageCanonical = absolute_url('projects.php');
$pageStyles = ['marketplace.css'];
$pageScripts = ['filters.js'];

require INCLUDES_PATH . '/layouts/header.php';
?>

<section class="pm-page-header">
    <div class="pm-container">
        <nav class="pm-breadcrumb" aria-label="Breadcrumb">
            <a href="<?= e(url('index.php')) ?>"><?= icon('home', 14) ?><span>Home</span></a>
            <?= icon('chevron-right', 14) ?>
            <span>Explore Projects</span>
        </nav>
        <div class="pm-page-header-row">
            <div>
                <h1 class="pm-h1">Explore Marketplace Projects</h1>
                <p class="pm-muted" style="margin-top:6px;">Filter across 14 digital engineering categories by stack, budget, verified rating, and author.</p>
            </div>
            <div class="pm-btn-row">
                <a href="<?= e(url('featured-projects.php')) ?>" class="pm-btn pm-btn-soft pm-btn-sm"><?= icon('award', 15) ?><span>Featured</span></a>
                <a href="<?= e(url('free-projects.php')) ?>" class="pm-btn pm-btn-emerald-soft pm-btn-sm"><?= icon('tag', 15) ?><span>Free Items</span></a>
            </div>
        </div>
    </div>
</section>

<section class="pm-section-tight">
    <div class="pm-container">
        <?php require INCLUDES_PATH . '/components/alerts.php'; ?>

        <div class="pm-catalog-layout">
            <!-- FACETED FILTER SIDEBAR -->
            <form method="get" action="<?= e(url('projects.php')) ?>" class="pm-card pm-filter-sidebar" id="pmFilterSidebar">
                <input type="hidden" name="sort" value="<?= e($filters['sort']) ?>">

                <div class="pm-filter-head">
                    <h2><?= icon('sliders', 17) ?><span>Filter Catalog</span></h2>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <a href="<?= e(url('projects.php')) ?>" class="pm-link" style="font-size:0.78rem;">Reset</a>
                        <button type="button" class="pm-icon-btn pm-filter-drawer-btn" style="width:32px;height:32px;" data-drawer-close aria-label="Close filters"><?= icon('close', 15) ?></button>
                    </div>
                </div>

                <div class="pm-filter-group">
                    <label class="pm-filter-title" for="fSearch">Keyword</label>
                    <div class="pm-input-wrap">
                        <?= icon('search', 16) ?>
                        <input type="search" id="fSearch" name="q" value="<?= e($filters['q']) ?>" class="pm-input" placeholder="Title or description...">
                    </div>
                </div>

                <div class="pm-filter-group">
                    <label class="pm-filter-title" for="fCat">Category</label>
                    <select id="fCat" name="category" class="pm-select">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= e($cat['slug']) ?>" <?= $filters['category'] === $cat['slug'] ? 'selected' : '' ?>>
                                <?= e($cat['name']) ?> (<?= (int)$cat['projects_count'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="pm-filter-group">
                    <span class="pm-filter-title">Pricing Type</span>
                    <div class="pm-segmented">
                        <label><input type="radio" name="pricing" value="all" <?= $filters['pricing'] === 'all' ? 'checked' : '' ?>><span>All</span></label>
                        <label><input type="radio" name="pricing" value="paid" <?= $filters['pricing'] === 'paid' ? 'checked' : '' ?>><span>Paid</span></label>
                        <label><input type="radio" name="pricing" value="free" <?= $filters['pricing'] === 'free' ? 'checked' : '' ?>><span>Free</span></label>
                    </div>
                </div>

                <div class="pm-filter-group">
                    <span class="pm-filter-title">Price Range (<?= e(site_setting('currency_symbol', '৳')) ?>)</span>
                    <div class="pm-price-range-row">
                        <input type="number" name="min_price" min="0" step="50" value="<?= e($filters['min_price']) ?>" class="pm-input" placeholder="Min ৳" aria-label="Minimum price">
                        <input type="number" name="max_price" min="0" step="50" value="<?= e($filters['max_price']) ?>" class="pm-input" placeholder="Max ৳" aria-label="Maximum price">
                    </div>
                </div>

                <div class="pm-filter-group">
                    <label class="pm-filter-title" for="fRating">Minimum Rating</label>
                    <select id="fRating" name="rating" class="pm-select">
                        <option value="">Any Rating</option>
                        <option value="4.5" <?= $filters['rating'] === '4.5' ? 'selected' : '' ?>>4.5 stars &amp; up</option>
                        <option value="4" <?= $filters['rating'] === '4' ? 'selected' : '' ?>>4.0 stars &amp; up</option>
                        <option value="3" <?= $filters['rating'] === '3' ? 'selected' : '' ?>>3.0 stars &amp; up</option>
                    </select>
                </div>

                <div class="pm-filter-group">
                    <label class="pm-filter-title" for="fSeller">Verified Seller</label>
                    <select id="fSeller" name="seller" class="pm-select">
                        <option value="">All Authors</option>
                        <?php foreach ($sellers as $s): ?>
                            <option value="<?= e($s['username']) ?>" <?= $filters['seller'] === $s['username'] ? 'selected' : '' ?>>
                                <?= e($s['display_name']) ?> (<?= (int)$s['projects_count'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="pm-filter-group">
                    <label class="pm-filter-title" for="fTech">Technology</label>
                    <select id="fTech" name="tech" class="pm-select">
                        <option value="">All Technologies</option>
                        <?php foreach ($technologies as $t): ?>
                            <option value="<?= e($t['tech_slug']) ?>" <?= $filters['tech'] === $t['tech_slug'] ? 'selected' : '' ?>>
                                <?= e($t['tech_name']) ?> (<?= (int)$t['projects_count'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="pm-btn pm-btn-primary pm-btn-block">
                    <?= icon('filter', 16) ?><span>Apply Filters</span>
                </button>
            </form>

            <!-- CATALOG RESULTS COLUMN -->
            <div>
                <div class="pm-catalog-toolbar">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <button type="button" class="pm-btn pm-btn-secondary pm-btn-sm pm-filter-drawer-btn" data-drawer-open="pmFilterSidebar">
                            <?= icon('sliders', 15) ?><span>Filters</span>
                        </button>
                        <p class="pm-toolbar-count">
                            Showing <strong><?= count($result['items']) ?></strong> of <strong><?= number_format($result['total']) ?></strong> projects
                        </p>
                    </div>

                    <div class="pm-toolbar-controls">
                        <label for="sortSelect" class="pm-sr-only">Sort projects</label>
                        <select id="sortSelect" class="pm-select pm-sort-select" data-sort-select>
                            <?php foreach ($sortOptions as $val => $lbl): ?>
                                <option value="<?= e($val) ?>" <?= $result['sort'] === $val ? 'selected' : '' ?>><?= e($lbl) ?></option>
                            <?php endforeach; ?>
                        </select>

                        <div class="pm-view-toggle" role="group" aria-label="View layout">
                            <button type="button" class="is-active" data-view-mode="grid" aria-label="Grid view"><?= icon('grid', 16) ?></button>
                            <button type="button" data-view-mode="list" aria-label="List view"><?= icon('list', 16) ?></button>
                        </div>
                    </div>
                </div>

                <!-- Active Filter Chips -->
                <?php
                $activeChips = [];
                if ($filters['q'] !== '') $activeChips[] = ['label' => 'Query: ' . $filters['q'], 'remove' => 'q'];
                if ($filters['category'] !== '') $activeChips[] = ['label' => 'Category: ' . $filters['category'], 'remove' => 'category'];
                if ($filters['pricing'] !== 'all') $activeChips[] = ['label' => 'Type: ' . ucfirst($filters['pricing']), 'remove' => 'pricing'];
                if ($filters['min_price'] !== '') $activeChips[] = ['label' => 'Min: ' . format_price($filters['min_price']), 'remove' => 'min_price'];
                if ($filters['max_price'] !== '') $activeChips[] = ['label' => 'Max: ' . format_price($filters['max_price']), 'remove' => 'max_price'];
                if ($filters['rating'] !== '') $activeChips[] = ['label' => $filters['rating'] . '+ Stars', 'remove' => 'rating'];
                if ($filters['tech'] !== '') $activeChips[] = ['label' => 'Tech: ' . $filters['tech'], 'remove' => 'tech'];
                if ($filters['seller'] !== '') $activeChips[] = ['label' => 'Seller: @' . $filters['seller'], 'remove' => 'seller'];
                if (!empty($activeChips)): ?>
                    <div class="pm-active-filters">
                        <?php foreach ($activeChips as $chip): ?>
                            <a href="<?= e(build_query_url('projects.php', [], [$chip['remove'], 'page'])) ?>" class="pm-active-chip">
                                <span><?= e($chip['label']) ?></span>
                                <?= icon('close', 13) ?>
                            </a>
                        <?php endforeach; ?>
                        <a href="<?= e(url('projects.php')) ?>" class="pm-link" style="font-size:0.8rem;margin-left:4px;">Clear all</a>
                    </div>
                <?php endif; ?>

                <?php if (empty($result['items'])): ?>
                    <div class="pm-card pm-empty">
                        <div class="pm-icon-orb"><?= icon('search', 26) ?></div>
                        <h2 class="pm-h3">No matching projects found</h2>
                        <p class="pm-muted" style="max-width:420px;">Try broadening your price range, clearing one of the active technology or rating filters, or browsing all categories.</p>
                        <a href="<?= e(url('projects.php')) ?>" class="pm-btn pm-btn-primary" style="margin-top:6px;">
                            <?= icon('refresh', 16) ?><span>Reset All Filters</span>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="pm-catalog-grid" data-catalog-grid>
                        <?php foreach ($result['items'] as $project):
                            require INCLUDES_PATH . '/components/project-card.php';
                        endforeach; ?>
                    </div>
                    <?php
                    $currentPage = $result['page'];
                    $totalPages = $result['total_pages'];
                    $paginationBasePath = 'projects.php';
                    require INCLUDES_PATH . '/components/pagination.php';
                    ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php require INCLUDES_PATH . '/layouts/footer.php'; ?>
