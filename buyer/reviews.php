<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$user = require_login();
$uid = (int)$user['id'];

$revStmt = db()->prepare(
    "SELECT r.*, p.title AS project_title, p.slug AS project_slug, o.order_number
     FROM `reviews` r
     INNER JOIN `projects` p ON p.id = r.project_id
     INNER JOIN `order_items` oi ON oi.id = r.order_item_id
     INNER JOIN `orders` o ON o.id = oi.order_id
     WHERE r.buyer_id = ?
     ORDER BY r.created_at DESC"
);
$revStmt->execute([$uid]);
$myReviews = $revStmt->fetchAll();

// Unreviewed paid items eligible for review
$eligStmt = db()->prepare(
    "SELECT oi.id AS order_item_id, oi.project_title, o.order_number, p.slug AS project_slug
     FROM `order_items` oi
     INNER JOIN `orders` o ON o.id = oi.order_id
     INNER JOIN `projects` p ON p.id = oi.project_id
     LEFT JOIN `reviews` r ON r.order_item_id = oi.id
     WHERE o.buyer_id = ? AND o.status = 'paid' AND oi.status = 'active' AND r.id IS NULL
     ORDER BY o.paid_at DESC"
);
$eligStmt->execute([$uid]);
$eligible = $eligStmt->fetchAll();

$pageTitle = 'My Reviews';
$pageRobots = 'noindex, nofollow';
$pageStyles = ['buyer.css', 'project-details.css'];

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
                        <h1 class="pm-h2">My Verified Reviews</h1>
                        <p class="pm-muted">Rate and edit reviews for your verified completed purchases.</p>
                    </div>
                </div>

                <?php if (!empty($eligible)): ?>
                    <form method="post" action="<?= e(url('actions/reviews/save.php')) ?>" class="pm-card pm-card-pad" style="display:grid;gap:14px;" data-submit-lock>
                        <?= csrf_field() ?>
                        <h2 class="pm-h3">Write a Review (<?= count($eligible) ?> eligible)</h2>

                        <div class="pm-form-grid-2">
                            <div class="pm-field">
                                <label class="pm-label" for="eligItem">Purchased Project</label>
                                <select id="eligItem" name="order_item_id" class="pm-select" required>
                                    <?php foreach ($eligible as $el): ?>
                                        <option value="<?= (int)$el['order_item_id'] ?>"><?= e($el['project_title']) ?> (<?= e($el['order_number']) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="pm-field">
                                <span class="pm-label">Star Rating</span>
                                <div class="pm-star-picker" style="margin-top:4px;">
                                    <?php for ($s = 5; $s >= 1; $s--): ?>
                                        <input type="radio" id="newStar<?= $s ?>" name="rating" value="<?= $s ?>" <?= $s === 5 ? 'checked' : '' ?>>
                                        <label for="newStar<?= $s ?>"><?= icon('star', 22) ?></label>
                                    <?php endfor; ?>
                                </div>
                            </div>
                        </div>

                        <div class="pm-field">
                            <label class="pm-label" for="rHead">Review Headline</label>
                            <input id="rHead" name="title" class="pm-input" placeholder="Summarize your experience in a few words">
                        </div>

                        <div class="pm-field">
                            <label class="pm-label" for="rBody">Written Review</label>
                            <textarea id="rBody" name="comment" class="pm-textarea" placeholder="Share honest feedback on architecture, code quality, docs, and support..." required></textarea>
                        </div>

                        <div><button type="submit" class="pm-btn pm-btn-primary"><?= icon('check', 16) ?><span>Publish Verified Review</span></button></div>
                    </form>
                <?php endif; ?>

                <?php if (empty($myReviews)): ?>
                    <div class="pm-card pm-empty">
                        <div class="pm-icon-orb"><?= icon('star', 25) ?></div>
                        <h2 class="pm-h3">No reviews published yet</h2>
                        <p class="pm-muted">Once you purchase and test a project, you can leave a verified rating here.</p>
                    </div>
                <?php else: ?>
                    <div style="display:grid;gap:14px;">
                        <?php foreach ($myReviews as $rev): ?>
                            <article class="pm-card pm-card-pad" style="display:grid;gap:10px;">
                                <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                                    <div>
                                        <a href="<?= e(url('project.php?slug=' . urlencode((string)$rev['project_slug']))) ?>" class="pm-h4"><?= e($rev['project_title']) ?></a>
                                        <small class="pm-muted" style="display:block;">Order <?= e($rev['order_number']) ?> · <?= e(format_date((string)$rev['created_at'])) ?></small>
                                    </div>
                                    <div style="display:flex;align-items:center;gap:10px;">
                                        <?= render_stars((float)$rev['rating'], 15) ?>
                                        <?= render_status_badge((string)$rev['status']) ?>
                                    </div>
                                </div>
                                <?php if (!empty($rev['title'])): ?>
                                    <strong><?= e($rev['title']) ?></strong>
                                <?php endif; ?>
                                <p style="color:var(--text-secondary);"><?= nl2br(e((string)$rev['comment'])) ?></p>

                                <details style="margin-top:4px;">
                                    <summary class="pm-link" style="font-size:0.82rem;cursor:pointer;">Edit this review</summary>
                                    <form method="post" action="<?= e(url('actions/reviews/save.php')) ?>" style="display:grid;gap:10px;margin-top:12px;" data-submit-lock>
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="order_item_id" value="<?= (int)$rev['order_item_id'] ?>">
                                        <select name="rating" class="pm-select" style="max-width:160px;">
                                            <?php for ($s = 5; $s >= 1; $s--): ?>
                                                <option value="<?= $s ?>" <?= (int)$rev['rating'] === $s ? 'selected' : '' ?>><?= $s ?> Stars</option>
                                            <?php endfor; ?>
                                        </select>
                                        <input name="title" class="pm-input" value="<?= e($rev['title'] ?? '') ?>">
                                        <textarea name="comment" class="pm-textarea" required><?= e($rev['comment']) ?></textarea>
                                        <div><button type="submit" class="pm-btn pm-btn-secondary pm-btn-sm">Save Changes</button></div>
                                    </form>
                                </details>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php require INCLUDES_PATH . '/layouts/footer.php'; ?>
