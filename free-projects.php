<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$categorySlug = trim((string)($_GET['category'] ?? ''));
$sort = trim((string)($_GET['sort'] ?? 'popular'));
$page = max(1, (int)($_GET['page'] ?? 1));

$result = ProjectRepository::search([
    'pricing' => 'free',
    'category' => $categorySlug,
    'sort' => $sort,
], $page, PROJECTS_PER_PAGE);

$pageTitle = 'Free Projects & Open Starter Kits';
$pageDescription = 'Download inspected, zero-cost PHP scripts, HTML/CSS templates, JavaScript tools, and UI/UX starter kits directly from CodeVault.';
$pageCanonical = absolute_url('free-projects.php');
$pageStyles = ['marketplace.css'];
$pageScripts = ['filters.js'];

require INCLUDES_PATH . '/layouts/header.php';
?>

<section class="pm-page-header">
    <div class="pm-container">
        <nav class="pm-breadcrumb" aria-label="Breadcrumb">
            <a href="<?= e(url('index.php')) ?>"><?= icon('home', 14) ?><span>Home</span></a>
            <?= icon('chevron-right', 14) ?>
            <span>Free Projects</span>
        </nav>
        <div class="pm-page-header-row">
            <div>
                <span class="pm-eyebrow" style="margin-bottom:10px;"><?= icon('tag', 14) ?><span>Community Edition — ৳0</span></span>
                <h1 class="pm-h1">Free Starter Projects &amp; Templates</h1>
                <p class="pm-muted" style="margin-top:6px;">Security-inspected open source kits and developer utilities with instant authenticated ZIP downloads.</p>
            </div>
            <a href="<?= e(url('projects.php')) ?>" class="pm-btn pm-btn-secondary">
                <?= icon('grid', 16) ?><span>Browse Full Catalog</span>
            </a>
        </div>
    </div>
</section>

<section class="pm-section-tight">
    <div class="pm-container">
        <?php require INCLUDES_PATH . '/components/alerts.php'; ?>

        <div class="pm-catalog-toolbar">
            <p class="pm-toolbar-count"><strong><?= number_format($result['total']) ?></strong> free <?= $result['total'] === 1 ? 'project' : 'projects' ?> available for instant download</p>
            <select class="pm-select pm-sort-select" data-sort-select aria-label="Sort free projects">
                <option value="popular" <?= $sort === 'popular' ? 'selected' : '' ?>>Most Popular</option>
                <option value="latest" <?= $sort === 'latest' ? 'selected' : '' ?>>Latest Releases</option>
                <option value="best_rated" <?= $sort === 'best_rated' ? 'selected' : '' ?>>Best Rated</option>
                <option value="best_selling" <?= $sort === 'best_selling' ? 'selected' : '' ?>>Most Downloaded</option>
            </select>
        </div>

        <?php if (empty($result['items'])): ?>
            <div class="pm-card pm-empty">
                <div class="pm-icon-orb"><?= icon('tag', 26) ?></div>
                <h2 class="pm-h3">No free projects match this filter yet</h2>
                <a href="<?= e(url('free-projects.php')) ?>" class="pm-btn pm-btn-primary">View All Free Items</a>
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
            $paginationBasePath = 'free-projects.php';
            require INCLUDES_PATH . '/components/pagination.php';
            ?>
        <?php endif; ?>
    </div>
</section>

<?php require INCLUDES_PATH . '/layouts/footer.php'; ?>
