<?php
declare(strict_types=1);

/**
 * Reusable Top-Rated Seller Card Component
 * Expects array $seller.
 */
$displayName = trim((string)($seller['store_name'] ?? '')) !== '' ? $seller['store_name'] : $seller['full_name'];
$sUrl = url('seller-profile.php?username=' . urlencode((string)$seller['username']));
?>
<article class="pm-card pm-card-interactive pm-seller-card">
    <div class="pm-seller-head">
        <span class="pm-avatar pm-avatar-lg">
            <?php if (!empty($seller['avatar'])): ?>
                <img src="<?= e(media_url((string)$seller['avatar'])) ?>" alt="<?= e($displayName) ?>">
            <?php else: ?>
                <?= e(user_initials((string)$displayName)) ?>
            <?php endif; ?>
        </span>
        <div class="pm-seller-title">
            <h3>
                <span><?= e($displayName) ?></span>
                <?php if (!empty($seller['is_verified_seller'])): ?>
                    <span class="pm-verified" title="Verified Seller"><?= icon('badge-check', 16, '', 'Verified Seller') ?></span>
                <?php endif; ?>
            </h3>
            <p><?= e($seller['headline'] ?: ('@' . $seller['username'])) ?></p>
        </div>
    </div>

    <div class="pm-seller-metrics">
        <div>
            <strong><?= number_format((float)($seller['avg_rating'] ?? 0), 1) ?></strong>
            <span>Rating</span>
        </div>
        <div>
            <strong><?= number_format((int)($seller['total_sales'] ?? 0)) ?></strong>
            <span>Sales</span>
        </div>
        <div>
            <strong><?= (int)($seller['projects_count'] ?? 0) ?></strong>
            <span>Items</span>
        </div>
    </div>

    <a href="<?= e($sUrl) ?>" class="pm-btn pm-btn-secondary pm-btn-sm pm-btn-block">
        <?= icon('store', 15) ?>
        <span>Visit Storefront</span>
    </a>
</article>
