<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$user = require_login();
$wishlistItems = ProjectRepository::wishlistProjects((int)$user['id']);

$pageTitle = 'Saved Wishlist';
$pageRobots = 'noindex, nofollow';
$pageStyles = ['buyer.css'];

require INCLUDES_PATH . '/layouts/header.php';
?>

<section class="pm-dashboard-section">
    <div class="pm-container">
        <?php require INCLUDES_PATH . '/components/alerts.php'; ?>

        <div class="pm-dashboard-shell">
            <?php require INCLUDES_PATH . '/layouts/buyer-sidebar.php'; ?>

            <div class="pm-dash-main">
                <div class="pm-dash-mobile-bar">
                    <button type="button" class="pm-btn pm-btn-secondary pm-btn-sm" data-drawer-open="pmBuyerSidebar">
                        <?= icon('menu', 16) ?><span>Dashboard Menu</span>
                    </button>
                </div>

                <div class="pm-dash-header">
                    <div>
                        <h1 class="pm-h2">Saved Wishlist (<?= count($wishlistItems) ?>)</h1>
                        <p class="pm-muted">Bookmarked projects you are evaluating for upcoming builds.</p>
                    </div>
                    <a href="<?= e(url('projects.php')) ?>" class="pm-btn pm-btn-secondary pm-btn-sm">
                        <?= icon('plus', 15) ?><span>Discover More</span>
                    </a>
                </div>

                <?php if (empty($wishlistItems)): ?>
                    <div class="pm-card pm-empty">
                        <div class="pm-icon-orb"><?= icon('heart', 26) ?></div>
                        <h2 class="pm-h3">Your wishlist is empty</h2>
                        <p class="pm-muted" style="max-width:420px;">Tap the heart icon on any project card to save it here for quick comparison and cart access.</p>
                        <a href="<?= e(url('projects.php')) ?>" class="pm-btn pm-btn-primary">Explore Projects</a>
                    </div>
                <?php else: ?>
                    <div class="pm-grid-2" data-wishlist-grid>
                        <?php
                        $removeOnUnsave = true;
                        foreach ($wishlistItems as $project):
                            require INCLUDES_PATH . '/components/project-card.php';
                        endforeach;
                        ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php require INCLUDES_PATH . '/layouts/footer.php'; ?>
