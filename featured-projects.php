<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$sort = trim((string)($_GET['sort'] ?? 'best_selling'));
$page = max(1, (int)($_GET['page'] ?? 1));

$result = ProjectRepository::search([
    'featured' => 1,
    'sort' => $sort,
], $page, PROJECTS_PER_PAGE);

$pageTitle = 'Featured Premium Projects';
$pageDescription = 'Staff-curated flagship software releases audited for architecture quality, documentation depth, and responsive UI polish.';
$pageCanonical = absolute_url('featured-projects.php');
$pageStyles = ['marketplace.css'];
$pageScripts = ['filters.js'];

require INCLUDES_PATH . '/layouts/header.php';
?>

<section class="pm-page-header">
    <div class="pm-container">
        <nav class="pm-breadcrumb" aria-label="Breadcrumb">
            <a href="<?= e(url('index.php')) ?>"><?= icon('home', 14) ?><span>Home</span></a>
            <?= icon('chevron-right', 14) ?>
            <span>Featured Projects</span>
        </nav>
        <div class="pm-page-header-row">
            <div>
                <span class="pm-eyebrow" style="margin-bottom:10px;"><?= icon('award', 14) ?><span>Editor's Selection</span></span>
                <h1 class="pm-h1">Featured Premium Projects</h1>
                <p class="pm-muted" style="margin-top:6px;">Handpicked releases setting the benchmark for security, code maintainability, and user experience.</p>
            </div>
            <a href="<?= e(url('projects.php')) ?>" class="pm-btn pm-btn-secondary">
                <?= icon('grid', 16) ?><span>Explore All Projects</span>
            </a>
        </div>
    </div>
</section>

<section class="pm-section-tight">
    <div class="pm-container">
        <div class="pm-catalog-toolbar">
            <p class="pm-toolbar-count"><strong><?= number_format($result['total']) ?></strong> editor-featured projects</p>
            <select class="pm-select pm-sort-select" data-sort-select aria-label="Sort featured projects">
                <option value="best_selling" <?= $sort === 'best_selling' ? 'selected' : '' ?>>Best Selling</option>
                <option value="best_rated" <?= $sort === 'best_rated' ? 'selected' : '' ?>>Best Rated</option>
                <option value="latest" <?= $sort === 'latest' ? 'selected' : '' ?>>Latest Featured</option>
                <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price: Low to High</option>
                <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price: High to Low</option>
            </select>
        </div>

        <?php if (empty($result['items'])): ?>
            <div class="pm-card pm-empty">
                <div class="pm-icon-orb"><?= icon('award', 26) ?></div>
                <h2 class="pm-h3">Featured lineup is updating</h2>
                <a href="<?= e(url('projects.php')) ?>" class="pm-btn pm-btn-primary">Explore All Projects</a>
            </div>
        <?php else: ?>
            <div class="pm-grid-3">
                <?php foreach ($result['items'] as $project):
                    require INCLUDES_PATH . '/components/project-card.php';
                endforeach; ?>
            </div>
            <?php
            $currentPage = $result['page'];
            $totalPages = $result['total_pages'];
            $paginationBasePath = 'featured-projects.php';
            require INCLUDES_PATH . '/components/pagination.php';
            ?>
        <?php endif; ?>
    </div>
</section>

<?php require INCLUDES_PATH . '/layouts/footer.php'; ?>
