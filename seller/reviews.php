<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$user = require_seller();
$uid = (int)$user['id'];

$stmt = db()->prepare(
    "SELECT r.*, p.title AS project_title, p.slug AS project_slug, u.full_name AS buyer_name
     FROM `reviews` r
     INNER JOIN `projects` p ON p.id = r.project_id
     INNER JOIN `users` u ON u.id = r.buyer_id
     WHERE p.seller_id = ? AND r.status = 'published'
     ORDER BY r.created_at DESC"
);
$stmt->execute([$uid]);
$reviews = $stmt->fetchAll();

$pageTitle = 'Buyer Reviews — Seller Studio';
$pageRobots = 'noindex, nofollow';
$pageStyles = ['dashboard.css'];

require INCLUDES_PATH . '/layouts/header.php';
?>

<section class="pm-dash-shell">
    <div class="pm-container">
        <div class="pm-dash-grid">
            <?php require INCLUDES_PATH . '/layouts/seller-sidebar.php'; ?>

            <div class="pm-dash-main">
                <?php require INCLUDES_PATH . '/components/alerts.php'; ?>

                <div>
                    <h1 class="pm-h2">Verified Buyer Reviews</h1>
                    <p class="pm-muted">Read customer feedback and post official author responses.</p>
                </div>

                <?php if (empty($reviews)): ?>
                    <div class="pm-card pm-empty">
                        <div class="pm-icon-orb"><?= icon('star', 26) ?></div>
                        <h2 class="pm-h3">No reviews yet</h2>
                        <p class="pm-muted">Verified buyers can rate your projects once their orders are approved.</p>
                    </div>
                <?php else: ?>
                    <div style="display:grid;gap:14px;">
                        <?php foreach ($reviews as $r): ?>
                            <article class="pm-card pm-card-pad" style="display:grid;gap:12px;">
                                <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;">
                                    <div>
                                        <strong><?= e($r['buyer_name']) ?></strong>
                                        <span class="pm-muted" style="font-size:0.82rem;"> on <a href="<?= e(url('project.php?slug=' . urlencode((string)$r['project_slug']))) ?>" class="pm-link"><?= e($r['project_title']) ?></a></span>
                                    </div>
                                    <div style="display:flex;align-items:center;gap:10px;">
                                        <?= render_stars((float)$r['rating']) ?>
                                        <small class="pm-muted"><?= e(format_date((string)$r['created_at'])) ?></small>
                                    </div>
                                </div>
                                <?php if (!empty($r['title'])): ?><h3 class="pm-h3" style="font-size:0.95rem;"><?= e($r['title']) ?></h3><?php endif; ?>
                                <p class="pm-muted"><?= nl2br(e((string)$r['comment'])) ?></p>

                                <form method="post" action="<?= e(url('actions/seller/engage.php')) ?>" style="display:grid;gap:8px;padding-top:10px;border-top:1px solid var(--border);">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="reply_review">
                                    <input type="hidden" name="review_id" value="<?= (int)$r['id'] ?>">
                                    <label class="pm-label">Official Author Reply</label>
                                    <textarea name="seller_reply" class="pm-textarea" rows="2" placeholder="Thank the buyer or answer their feedback..." required><?= e((string)($r['seller_reply'] ?? '')) ?></textarea>
                                    <div><button type="submit" class="pm-btn pm-btn-secondary pm-btn-sm"><?= !empty($r['seller_reply']) ? 'Update Reply' : 'Post Reply' ?></button></div>
                                </form>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php require INCLUDES_PATH . '/layouts/footer.php'; ?>
