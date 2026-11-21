<?php
declare(strict_types=1);

$q = $adminQueues ?? admin_pending_counts();

$adminGroups = [
    'Overview' => [
        ['label' => 'Dashboard', 'url' => 'admin/dashboard.php', 'icon' => 'grid', 'badge' => 0],
    ],
    'Commerce & Finance' => [
        ['label' => 'Payment Verification', 'url' => 'admin/payments.php', 'icon' => 'credit-card', 'badge' => $q['payments']],
        ['label' => 'Orders & Invoices', 'url' => 'admin/orders.php', 'icon' => 'shopping-bag', 'badge' => 0, 'alt' => 'admin/order-view.php'],
        ['label' => 'Commissions & Ledger', 'url' => 'admin/commissions.php', 'icon' => 'bar-chart', 'badge' => 0],
        ['label' => 'Seller Withdrawals', 'url' => 'admin/withdrawals.php', 'icon' => 'wallet', 'badge' => $q['withdrawals']],
        ['label' => 'Payment Methods', 'url' => 'admin/payment-methods.php', 'icon' => 'smartphone', 'badge' => 0],
        ['label' => 'Discount Coupons', 'url' => 'admin/coupons.php', 'icon' => 'tag', 'badge' => 0],
    ],
    'Catalog & Users' => [
        ['label' => 'Projects Moderation', 'url' => 'admin/projects.php', 'icon' => 'package', 'badge' => $q['projects'], 'alt' => 'admin/project-review.php'],
        ['label' => 'Categories', 'url' => 'admin/categories.php', 'icon' => 'layers', 'badge' => 0],
        ['label' => 'Sellers & Wallets', 'url' => 'admin/sellers.php', 'icon' => 'store', 'badge' => $q['sellers']],
        ['label' => 'Users & Roles', 'url' => 'admin/users.php', 'icon' => 'users', 'badge' => 0],
    ],
    'Trust & Support' => [
        ['label' => 'Support Tickets', 'url' => 'admin/support.php', 'icon' => 'life-buoy', 'badge' => $q['tickets']],
        ['label' => 'Reviews & Reports', 'url' => 'admin/reviews.php', 'icon' => 'star', 'badge' => $q['reports']],
        ['label' => 'Notifications', 'url' => 'admin/notifications.php', 'icon' => 'bell', 'badge' => 0],
    ],
    'Website Control' => [
        ['label' => 'Website & Homepage', 'url' => 'admin/website-control.php', 'icon' => 'settings', 'badge' => 0],
        ['label' => 'CMS Pages & FAQs', 'url' => 'admin/pages.php', 'icon' => 'file-text', 'badge' => 0],
        ['label' => 'Logs & DB Backup', 'url' => 'admin/logs.php', 'icon' => 'shield', 'badge' => 0],
    ],
];
?>
<aside class="pm-admin-sidebar" id="pmAdminSidebar" aria-label="Admin Navigation">
    <div class="pm-admin-drawer-head">
        <span class="pm-admin-brand-tag">Admin Menu</span>
        <button type="button" class="pm-icon-btn" id="pmAdminCloseBtn" aria-label="Close admin menu"><?= icon('close', 18) ?></button>
    </div>
    <?php foreach ($adminGroups as $groupLabel => $links): ?>
        <div class="pm-admin-nav-group">
            <span class="pm-admin-nav-heading"><?= e($groupLabel) ?></span>
            <?php foreach ($links as $item):
                $active = is_active_nav($item['url']) || (!empty($item['alt']) && is_active_nav($item['alt'])); ?>
                <a href="<?= e(url($item['url'])) ?>" class="pm-admin-link <?= $active ? 'is-active' : '' ?>">
                    <span class="pm-admin-link-left">
                        <?= icon($item['icon'], 16) ?>
                        <span><?= e($item['label']) ?></span>
                    </span>
                    <?php if ($item['badge'] > 0): ?>
                        <span class="pm-admin-pill"><?= (int)$item['badge'] ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
</aside>
