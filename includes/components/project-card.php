<?php
declare(strict_types=1);

/**
 * Reusable Project Card Component — compact marketplace tile (2 per row on phones).
 * Expects array $project; optional bool $removeOnUnsave.
 */
$pid = (int)$project['id'];
$pUrl = url('project.php?slug=' . urlencode((string)$project['slug']));
$catUrl = url('category.php?slug=' . urlencode((string)$project['category_slug']));
$sellerUrl = url('seller-profile.php?username=' . urlencode((string)$project['seller_username']));
$isFree = (int)($project['is_free'] ?? 0) === 1;
$effPrice = effective_project_price($project);
$discPct = discount_percentage($project);
$inWish = in_array($pid, user_wishlist_ids(), true);
$rating = (float)($project['average_rating'] ?? 0);
$revCount = (int)($project['reviews_count'] ?? 0);
$thumb = media_url((string)($project['thumbnail'] ?? ''), 'assets/images/placeholders/project-default.svg');
$sellerDisplay = (string)($project['seller_display_name'] ?? $project['seller_name'] ?? 'Author');
?>
<article class="pm-project-card">
    <div class="pm-project-media" style="--pm-thumb:url('<?= e($thumb) ?>');">
        <img src="<?= e($thumb) ?>" alt="<?= e($project['title']) ?>" loading="lazy" decoding="async">

        <div class="pm-project-badges">
            <?php if ($isFree): ?>
                <span class="pm-pill-float pm-pill-free">FREE</span>
            <?php elseif ($discPct > 0): ?>
                <span class="pm-pill-float pm-pill-discount">-<?= $discPct ?>%</span>
            <?php endif; ?>
            <?php if (!empty($project['is_featured'])): ?>
                <span class="pm-pill-float pm-pill-featured"><?= icon('award', 11) ?><span>Featured</span></span>
            <?php endif; ?>
        </div>

        <button type="button"
                class="pm-wishlist-btn <?= $inWish ? 'is-active' : '' ?>"
                data-wishlist-toggle="<?= $pid ?>"
                <?= !empty($removeOnUnsave) ? 'data-remove-card-on-unsave="1"' : '' ?>
                aria-pressed="<?= $inWish ? 'true' : 'false' ?>"
                aria-label="<?= $inWish ? 'Remove from wishlist' : 'Save to wishlist' ?>">
            <?= icon('heart', 15) ?>
        </button>
    </div>

    <div class="pm-project-body">
        <a href="<?= e($catUrl) ?>" class="pm-project-cat">
            <?= icon((string)($project['category_icon'] ?? 'code'), 12) ?>
            <span><?= e($project['category_name']) ?></span>
        </a>

        <h3 class="pm-project-title">
            <a href="<?= e($pUrl) ?>" class="pm-project-link"><?= e($project['title']) ?></a>
        </h3>

        <p class="pm-project-desc"><?= e($project['short_description']) ?></p>

        <div class="pm-project-author">
            <a href="<?= e($sellerUrl) ?>" class="pm-author-link">
                <span class="pm-avatar">
                    <?php if (!empty($project['seller_avatar'])): ?>
                        <img src="<?= e(media_url((string)$project['seller_avatar'])) ?>" alt="">
                    <?php else: ?>
                        <?= e(user_initials($sellerDisplay)) ?>
                    <?php endif; ?>
                </span>
                <span class="name"><?= e($sellerDisplay) ?></span>
                <?php if (!empty($project['is_verified_seller'])): ?>
                    <span class="pm-verified" title="Verified Seller"><?= icon('badge-check', 13, '', 'Verified Seller') ?></span>
                <?php endif; ?>
            </a>
            <?php if ($revCount > 0): ?>
                <span class="pm-project-rating" title="<?= number_format($rating, 1) ?> out of 5 from <?= $revCount ?> verified reviews">
                    <?= icon('star', 12) ?><span><?= number_format($rating, 1) ?></span>
                </span>
            <?php else: ?>
                <span class="pm-sales-meta" title="Total verified sales">
                    <?= icon('download', 12) ?><span><?= number_format((int)($project['sales_count'] ?? 0)) ?></span>
                </span>
            <?php endif; ?>
        </div>
    </div>

    <div class="pm-project-foot">
        <div class="pm-price-group">
            <?php if ($isFree): ?>
                <span class="pm-price-current is-free">Free</span>
            <?php else: ?>
                <span class="pm-price-current"><?= e(format_price($effPrice)) ?></span>
                <?php if ($discPct > 0): ?>
                    <span class="pm-price-old"><?= e(format_price((string)$project['price'])) ?></span>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <span class="pm-project-go" aria-hidden="true"><?= icon('arrow-right', 15) ?></span>
    </div>
</article>
