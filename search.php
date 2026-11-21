<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$q = trim((string)($_GET['q'] ?? ''));
$categorySlug = trim((string)($_GET['category'] ?? ''));
$sort = trim((string)($_GET['sort'] ?? 'latest'));
$page = max(1, (int)($_GET['page'] ?? 1));

$result = ProjectRepository::search([
    'q' => $q,
    'category' => $categorySlug,
    'sort' => $sort,
], $page, PROJECTS_PER_PAGE);

$categories = global_categories(false);
$popularTechs = ProjectRepository::popularTechnologies(10);
$sortOptions = [
    'latest' => 'Latest Releases',
    'popular' => 'Most Popular',
    'best_selling' => 'Best Selling',
    'best_rated' => 'Best Rated',
    'price_asc' => 'Price: Low to High',
    'price_desc' => 'Price: High to Low',
    'oldest' => 'Oldest First',
];

$pageTitle = $q !== '' ? 'Search results for "' . $q . '"' : 'Search Projects';
$pageCanonical = absolute_url('search.php' . ($q !== '' ? '?q=' . urlencode($q) : ''));
$pageStyles = ['marketplace.css', 'homepage.css'];
$pageScripts = ['filters.js'];

require INCLUDES_PATH . '/layouts/header.php';
?>

<section class="pm-page-header">
    <div class="pm-container">
        <nav class="pm-breadcrumb" aria-label="Breadcrumb">
            <a href="<?= e(url('index.php')) ?>"><?= icon('home', 14) ?><span>Home</span></a>
            <?= icon('chevron-right', 14) ?>
            <span>Search Results</span>
        </nav>

        <h1 class="pm-h1">
            <?= $q !== '' ? 'Results for "' . e($q) . '"' : 'Search the Marketplace' ?>
        </h1>

        <form action="<?= e(url('search.php')) ?>" method="get" class="pm-hero-search" style="margin-top:18px;max-width:780px;">
            <span class="pm-hero-search-icon"><?= icon('search', 20) ?></span>
            <input type="search" name="q" value="<?= e($q) ?>" class="pm-hero-search-input" placeholder="Search by project title, description, or stack...">
            <select name="category" class="pm-hero-search-select" aria-label="Category filter">
                <option value="">All Categories</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= e($c['slug']) ?>" <?= $categorySlug === $c['slug'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="pm-btn pm-btn-primary"><?= icon('search', 16) ?><span>Search</span></button>
        </form>
    </div>
</section>

<section class="pm-section-tight">
    <div class="pm-container">
        <div class="pm-catalog-toolbar">
            <p class="pm-toolbar-count">
                Found <strong><?= number_format($result['total']) ?></strong> matching <?= $result['total'] === 1 ? 'project' : 'projects' ?>
                <?php if ($q !== '' || $categorySlug !== ''): ?>
                    · <a class="pm-link" href="<?= e(url('projects.php?' . http_build_query(['q' => $q, 'category' => $categorySlug]))) ?>">Open advanced filters</a>
                <?php endif; ?>
            </p>
            <select class="pm-select pm-sort-select" data-sort-select aria-label="Sort results">
                <?php foreach ($sortOptions as $v => $l): ?>
                    <option value="<?= e($v) ?>" <?= $result['sort'] === $v ? 'selected' : '' ?>><?= e($l) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <?php if (empty($result['items'])): ?>
            <div class="pm-card pm-empty">
                <div class="pm-icon-orb"><?= icon('search', 26) ?></div>
                <h2 class="pm-h3">No projects matched "<?= e($q) ?>"</h2>
                <p class="pm-muted" style="max-width:460px;">Try searching with broader keywords or pick one of the popular technology stacks below.</p>
                <div style="display:flex;flex-wrap:wrap;justify-content:center;gap:8px;margin-top:8px;">
                    <?php foreach ($popularTechs as $t): ?>
                        <a href="<?= e(url('projects.php?tech=' . urlencode((string)$t['tech_slug']))) ?>" class="pm-chip"><?= e($t['tech_name']) ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="pm-project-grid">
                <?php foreach ($result['items'] as $project):
                    require INCLUDES_PATH . '/components/project-card.php';
                endforeach; ?>
            </div>
            <?php
            $currentPage = $result['page'];
            $totalPages = $result['total_pages'];
            $paginationBasePath = 'search.php';
            require INCLUDES_PATH . '/components/pagination.php';
            ?>
        <?php endif; ?>
    </div>
</section>

<?php require INCLUDES_PATH . '/layouts/footer.php'; ?>
