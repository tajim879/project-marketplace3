<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$categories = global_categories(false);
$technologies = ProjectRepository::popularTechnologies(20);

$pageTitle = 'All Digital Product Categories';
$pageDescription = 'Browse all 14 specialized categories on CodeVault: PHP Projects, HTML/CSS/JS Templates, Android, Java, Kotlin, WordPress, Python, UI/UX & Scripts.';
$pageCanonical = absolute_url('categories.php');

require INCLUDES_PATH . '/layouts/header.php';
?>

<section class="pm-page-header">
    <div class="pm-container">
        <nav class="pm-breadcrumb" aria-label="Breadcrumb">
            <a href="<?= e(url('index.php')) ?>"><?= icon('home', 14) ?><span>Home</span></a>
            <?= icon('chevron-right', 14) ?>
            <span>Categories</span>
        </nav>
        <div class="pm-page-header-row">
            <div>
                <h1 class="pm-h1">All <?= count($categories) ?> Product Categories</h1>
                <p class="pm-muted" style="margin-top:6px;">Dedicated verticals for every modern software engineering, mobile, CMS, and product design discipline.</p>
            </div>
            <a href="<?= e(url('projects.php')) ?>" class="pm-btn pm-btn-primary">
                <?= icon('grid', 17) ?><span>Browse Entire Catalog</span>
            </a>
        </div>
    </div>
</section>

<section class="pm-section">
    <div class="pm-container">
        <div class="pm-cat-grid">
            <?php foreach ($categories as $category):
                require INCLUDES_PATH . '/components/category-card.php';
            endforeach; ?>
        </div>

        <?php if (!empty($technologies)): ?>
            <div class="pm-card pm-card-pad" style="margin-top:44px;display:grid;gap:16px;">
                <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
                    <div>
                        <h2 class="pm-h3">Browse by Technology Stack</h2>
                        <p class="pm-muted" style="font-size:0.84rem;">Jump straight to projects built with your preferred language, runtime, or design tool.</p>
                    </div>
                </div>
                <div style="display:flex;flex-wrap:wrap;gap:9px;">
                    <?php foreach ($technologies as $t): ?>
                        <a href="<?= e(url('projects.php?tech=' . urlencode((string)$t['tech_slug']))) ?>" class="pm-chip">
                            <?= icon('code', 13) ?>
                            <span><?= e($t['tech_name']) ?></span>
                            <span class="pm-muted" style="font-size:0.72rem;">(<?= (int)$t['projects_count'] ?>)</span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require INCLUDES_PATH . '/layouts/footer.php'; ?>
