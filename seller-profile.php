<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$username = trim((string)($_GET['username'] ?? ''));
if ($username === '') {
    redirect(url('projects.php'));
}

$seller = UserRepository::findSellerByUsername($username);
if (!$seller) {
    flash('warning', 'Seller profile not found.');
    redirect(url('projects.php'));
}

$sort = trim((string)($_GET['sort'] ?? 'best_selling'));
$page = max(1, (int)($_GET['page'] ?? 1));
$portfolio = ProjectRepository::search([
    'seller' => (int)$seller['id'],
    'sort' => $sort,
], $page, PROJECTS_PER_PAGE);

$sellerReviews = UserRepository::sellerReviews((int)$seller['id'], 12);
$displayName = trim((string)($seller['store_name'] ?? '')) !== '' ? $seller['store_name'] : $seller['full_name'];
$skills = array_filter(array_map('trim', explode(',', (string)($seller['skills'] ?? ''))));

$pageTitle = $displayName . ' — Verified Seller Profile';
$pageDescription = (string)($seller['headline'] ?: 'Explore verified digital projects published by ' . $displayName);
$pageCanonical = absolute_url('seller-profile.php?username=' . urlencode((string)$seller['username']));
$pageStyles = ['marketplace.css', 'project-details.css'];
$pageScripts = ['gallery.js', 'filters.js'];

require INCLUDES_PATH . '/layouts/header.php';
?>

<section class="pm-section-tight">
    <div class="pm-container">
        <nav class="pm-breadcrumb" aria-label="Breadcrumb">
            <a href="<?= e(url('index.php')) ?>"><?= icon('home', 14) ?><span>Home</span></a>
            <?= icon('chevron-right', 14) ?>
            <a href="<?= e(url('projects.php')) ?>">Authors</a>
            <?= icon('chevron-right', 14) ?>
            <span><?= e($displayName) ?></span>
        </nav>

        <div class="pm-store-banner">
            <div class="pm-store-identity">
                <span class="pm-avatar pm-avatar-xl">
                    <?php if (!empty($seller['avatar'])): ?>
                        <img src="<?= e(media_url((string)$seller['avatar'])) ?>" alt="<?= e($displayName) ?>">
                    <?php else: ?>
                        <?= e(user_initials((string)$displayName)) ?>
                    <?php endif; ?>
                </span>
                <div style="display:grid;gap:6px;">
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                        <h1 class="pm-h1" style="font-size:1.75rem;"><?= e($displayName) ?></h1>
                        <?php if (!empty($seller['is_verified_seller'])): ?>
                            <span class="pm-badge pm-badge-primary"><?= icon('badge-check', 14) ?><span>Verified Seller</span></span>
                        <?php endif; ?>
                    </div>
                    <p style="color:var(--text-secondary);font-weight:500;"><?= e($seller['headline'] ?: ('Software Author · @' . $seller['username'])) ?></p>
                    <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;font-size:0.8rem;color:var(--text-muted);">
                        <?php if (!empty($seller['city']) || !empty($seller['country'])): ?>
                            <span style="display:inline-flex;align-items:center;gap:5px;"><?= icon('map-pin', 14) ?><?= e(trim(($seller['city'] ?? '') . ', ' . ($seller['country'] ?? ''), ', ')) ?></span>
                        <?php endif; ?>
                        <span style="display:inline-flex;align-items:center;gap:5px;"><?= icon('calendar', 14) ?>Member since <?= e(format_date((string)$seller['created_at'], 'M Y')) ?></span>
                        <?php
                        $respRaw = trim((string)($seller['response_time'] ?? ''));
                        $respText = $respRaw === '' ? 'Responds within 12 hours'
                            : (preg_match('/^(usually|responds|replies)/i', $respRaw) ? ucfirst($respRaw) : 'Responds ' . strtolower($respRaw));
                        ?>
                        <span style="display:inline-flex;align-items:center;gap:5px;"><?= icon('clock', 14) ?><?= e($respText) ?></span>
                    </div>
                    <?php if (!empty($skills)): ?>
                        <div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:4px;">
                            <?php foreach ($skills as $sk): ?>
                                <span class="pm-chip" style="font-size:0.73rem;padding:3px 9px;"><?= e($sk) ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="pm-store-stats">
                <div class="pm-store-stat">
                    <strong><?= (int)$seller['approved_projects_count'] ?></strong>
                    <span>Projects</span>
                </div>
                <div class="pm-store-stat">
                    <strong><?= number_format((int)$seller['total_sales']) ?></strong>
                    <span>Total Sales</span>
                </div>
                <div class="pm-store-stat">
                    <strong><?= number_format((float)$seller['avg_rating'], 1) ?></strong>
                    <span><?= (int)$seller['reviews_count'] ?> Reviews</span>
                </div>
            </div>
        </div>

        <?php if (!empty($seller['bio'])): ?>
            <div class="pm-card pm-card-pad" style="margin-top:20px;">
                <h2 class="pm-h4" style="margin-bottom:6px;">About <?= e($displayName) ?></h2>
                <p class="pm-muted" style="color:var(--text-secondary);"><?= nl2br(e((string)$seller['bio'])) ?></p>
            </div>
        <?php endif; ?>

        <div class="pm-tabs-bar" style="margin-top:26px;" data-tabs>
            <button type="button" class="pm-tab-btn is-active" data-tab-target="sellerPortfolio">
                <?= icon('package', 16) ?><span>Published Projects (<?= $portfolio['total'] ?>)</span>
            </button>
            <button type="button" class="pm-tab-btn" data-tab-target="sellerReviewsTab">
                <?= icon('star', 16) ?><span>Buyer Reviews (<?= count($sellerReviews) ?>)</span>
            </button>
        </div>

        <div id="sellerPortfolio" class="pm-tab-panel is-active" data-tab-panel style="margin-top:22px;">
            <div class="pm-grid-3">
                <?php foreach ($portfolio['items'] as $project):
                    require INCLUDES_PATH . '/components/project-card.php';
                endforeach; ?>
            </div>
            <?php
            $currentPage = $portfolio['page'];
            $totalPages = $portfolio['total_pages'];
            $paginationBasePath = 'seller-profile.php';
            require INCLUDES_PATH . '/components/pagination.php';
            ?>
        </div>

        <div id="sellerReviewsTab" class="pm-tab-panel" data-tab-panel style="margin-top:22px;">
            <?php if (empty($sellerReviews)): ?>
                <div class="pm-card pm-empty">
                    <p class="pm-muted">No published buyer reviews for this seller yet.</p>
                </div>
            <?php else: ?>
                <div class="pm-grid-2">
                    <?php foreach ($sellerReviews as $r): ?>
                        <article class="pm-card pm-card-pad" style="display:grid;gap:10px;">
                            <div style="display:flex;align-items:center;justify-content:space-between;">
                                <?= render_stars((float)$r['rating'], 15) ?>
                                <small class="pm-muted"><?= e(format_date((string)$r['created_at'])) ?></small>
                            </div>
                            <p style="color:var(--text-secondary);font-size:0.9rem;">"<?= e($r['comment']) ?>"</p>
                            <small class="pm-muted">
                                By <strong><?= e($r['buyer_name']) ?></strong> on
                                <a class="pm-link" href="<?= e(url('project.php?slug=' . urlencode((string)$r['project_slug']))) ?>"><?= e($r['project_title']) ?></a>
                            </small>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require INCLUDES_PATH . '/layouts/footer.php'; ?>
