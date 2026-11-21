<?php
declare(strict_types=1);

$sbUser = current_user();
$sbUnread = $sbUser ? unread_notifications_count() : 0;
$sbWish = $sbUser ? user_wishlist_count() : 0;

$buyerNav = [
    ['label' => 'Overview', 'url' => 'buyer/dashboard.php', 'icon' => 'grid', 'badge' => 0],
    ['label' => 'My Purchases', 'url' => 'buyer/purchases.php', 'icon' => 'package', 'badge' => 0],
    ['label' => 'My Orders', 'url' => 'buyer/orders.php', 'icon' => 'shopping-bag', 'badge' => 0],
    ['label' => 'Downloads', 'url' => 'buyer/downloads.php', 'icon' => 'download', 'badge' => 0],
    ['label' => 'Wishlist', 'url' => 'buyer/wishlist.php', 'icon' => 'heart', 'badge' => $sbWish],
    ['label' => 'My Reviews', 'url' => 'buyer/reviews.php', 'icon' => 'star', 'badge' => 0],
    ['label' => 'Payment History', 'url' => 'buyer/payments.php', 'icon' => 'receipt', 'badge' => 0],
    ['label' => 'Notifications', 'url' => 'buyer/notifications.php', 'icon' => 'bell', 'badge' => $sbUnread],
    ['label' => 'Support Tickets', 'url' => 'buyer/support.php', 'icon' => 'life-buoy', 'badge' => 0],
    ['label' => 'Profile Settings', 'url' => 'buyer/profile.php', 'icon' => 'user', 'badge' => 0],
    ['label' => 'Security Settings', 'url' => 'buyer/security.php', 'icon' => 'lock', 'badge' => 0],
];
?>
<aside class="pm-card pm-dash-sidebar" id="pmBuyerSidebar" aria-label="Buyer Dashboard Menu">
    <div class="pm-dash-drawer-close-row">
        <span class="pm-badge pm-badge-primary"><?= icon('grid', 13) ?><span>Buyer Menu</span></span>
        <button type="button" class="pm-icon-btn" data-drawer-close aria-label="Close dashboard menu"><?= icon('close', 18) ?></button>
    </div>
    <div class="pm-dash-user">
        <span class="pm-avatar pm-avatar-lg">
            <?php if (!empty($sbUser['avatar'])): ?>
                <img src="<?= e(media_url((string)$sbUser['avatar'])) ?>" alt="<?= e($sbUser['full_name'] ?? '') ?>">
            <?php else: ?>
                <?= e(user_initials((string)($sbUser['full_name'] ?? 'U'))) ?>
            <?php endif; ?>
        </span>
        <div class="pm-dash-user-meta">
            <strong><?= e($sbUser['full_name'] ?? '') ?></strong>
            <small>@<?= e($sbUser['username'] ?? '') ?></small>
        </div>
    </div>

    <nav class="pm-dash-nav">
        <?php foreach ($buyerNav as $item):
            $active = is_active_nav($item['url']) || ($item['url'] === 'buyer/orders.php' && is_active_nav('buyer/order-details.php')); ?>
            <a href="<?= e(url($item['url'])) ?>" class="pm-dash-link <?= $active ? 'is-active' : '' ?>">
                <span class="pm-dash-link-left">
                    <?= icon($item['icon'], 17) ?>
                    <span><?= e($item['label']) ?></span>
                </span>
                <?php if ($item['badge'] > 0): ?>
                    <span class="pm-dash-pill"><?= (int)$item['badge'] ?></span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="pm-dash-sidebar-foot">
        <?php if (is_approved_seller($sbUser)): ?>
            <a href="<?= e(url('seller/dashboard.php')) ?>" class="pm-btn pm-btn-soft pm-btn-sm pm-btn-block">
                <?= icon('store', 16) ?><span>Switch to Seller Studio</span>
            </a>
        <?php else: ?>
            <a href="<?= e(url('seller/become-seller.php')) ?>" class="pm-btn pm-btn-soft pm-btn-sm pm-btn-block">
                <?= icon('store', 16) ?><span>Become a Seller</span>
            </a>
        <?php endif; ?>
        <form method="post" action="<?= e(url('logout.php')) ?>">
            <?= csrf_field() ?>
            <button type="submit" class="pm-btn pm-btn-secondary pm-btn-sm pm-btn-block">
                <?= icon('logout', 16) ?><span>Sign Out</span>
            </button>
        </form>
    </div>
</aside>
