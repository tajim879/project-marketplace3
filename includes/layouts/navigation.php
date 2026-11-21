<?php
declare(strict_types=1);

$navUser = current_user();
$wishCount = $navUser ? user_wishlist_count() : 0;
$cartTotalItems = active_cart_count();
$unreadNotifs = $navUser ? unread_notifications_count() : 0;
$siteNameNav = site_setting('site_name', 'CodeVault');
$siteTagNav = site_setting('site_tagline', 'Digital Project Marketplace');
$curNavScript = current_script_rel();
$isSellerApproved = $navUser ? is_approved_seller($navUser) : false;
$catTotalNav = count(global_categories());

$sideBrowseLinks = [
    ['label' => 'Home', 'url' => 'index.php', 'icon' => 'home', 'meta' => ''],
    ['label' => 'Explore Projects', 'url' => 'projects.php', 'icon' => 'grid', 'meta' => ''],
    ['label' => 'All Categories', 'url' => 'categories.php', 'icon' => 'layers', 'meta' => $catTotalNav > 0 ? (string)$catTotalNav : ''],
    ['label' => 'Featured Projects', 'url' => 'featured-projects.php', 'icon' => 'award', 'meta' => ''],
    ['label' => 'Free Projects', 'url' => 'free-projects.php', 'icon' => 'tag', 'meta' => ''],
];

$sideBuyerPrimary = [
    ['label' => 'My Dashboard', 'url' => 'buyer/dashboard.php', 'icon' => 'grid'],
    ['label' => 'My Purchases', 'url' => 'buyer/purchases.php', 'icon' => 'package'],
    ['label' => 'My Orders', 'url' => 'buyer/orders.php', 'icon' => 'receipt'],
    ['label' => 'Downloads', 'url' => 'buyer/downloads.php', 'icon' => 'download'],
    ['label' => 'Payment History', 'url' => 'buyer/payments.php', 'icon' => 'credit-card'],
];

$sideBuyerMore = [
    ['label' => 'My Reviews', 'url' => 'buyer/reviews.php', 'icon' => 'star'],
    ['label' => 'Support Tickets', 'url' => 'buyer/support.php', 'icon' => 'life-buoy'],
    ['label' => 'Profile Settings', 'url' => 'buyer/profile.php', 'icon' => 'user'],
    ['label' => 'Security Settings', 'url' => 'buyer/security.php', 'icon' => 'lock'],
];
$buyerMoreOpen = in_array($curNavScript, array_column($sideBuyerMore, 'url'), true);

$sideSellerPrimary = [
    ['label' => 'Studio Overview', 'url' => 'seller/dashboard.php', 'icon' => 'store'],
    ['label' => 'Upload Project', 'url' => 'seller/upload-project.php', 'icon' => 'upload'],
    ['label' => 'My Projects', 'url' => 'seller/projects.php', 'icon' => 'package'],
    ['label' => 'Sales History', 'url' => 'seller/sales.php', 'icon' => 'shopping-bag'],
    ['label' => 'Earnings & Ledger', 'url' => 'seller/earnings.php', 'icon' => 'bar-chart'],
    ['label' => 'Withdrawals', 'url' => 'seller/withdrawals.php', 'icon' => 'wallet'],
];

$sideSellerMore = [
    ['label' => 'Version Updates', 'url' => 'seller/project-updates.php', 'icon' => 'layers'],
    ['label' => 'Buyer Reviews', 'url' => 'seller/reviews.php', 'icon' => 'star'],
    ['label' => 'Product Q&A', 'url' => 'seller/questions.php', 'icon' => 'help-circle'],
    ['label' => 'Store & Payouts', 'url' => 'seller/store-profile.php', 'icon' => 'settings'],
];
$sellerMoreOpen = in_array($curNavScript, array_column($sideSellerMore, 'url'), true);

$sideSupportLinks = [
    ['label' => 'About Us', 'url' => 'about.php', 'icon' => 'info'],
    ['label' => 'FAQ', 'url' => 'faq.php', 'icon' => 'help-circle'],
    ['label' => 'Contact Support', 'url' => 'contact.php', 'icon' => 'mail'],
];

$sideLegalLinks = [
    ['label' => 'License Policy', 'url' => 'license-policy.php', 'icon' => 'file-text'],
    ['label' => 'Terms & Conditions', 'url' => 'terms.php', 'icon' => 'file-text'],
    ['label' => 'Privacy Policy', 'url' => 'privacy.php', 'icon' => 'lock'],
    ['label' => 'Refund Policy', 'url' => 'refund-policy.php', 'icon' => 'receipt'],
];
$legalOpen = in_array($curNavScript, array_column($sideLegalLinks, 'url'), true);
?>
<header class="pm-header">
    <div class="pm-container pm-header-inner">
        <div class="pm-header-left">
            <button type="button" class="pm-menu-trigger" data-drawer-open="pmMobileDrawer" aria-label="Open side menu" aria-controls="pmMobileDrawer">
                <?= icon('menu', 19) ?>
                <span class="pm-menu-trigger-label">Menu</span>
            </button>
            <a href="<?= e(url('index.php')) ?>" class="pm-brand" aria-label="<?= e($siteNameNav) ?> Home">
                <?= brand_logo($siteNameNav, true, $siteTagNav) ?>
            </a>
        </div>

        <div class="pm-header-actions">
            <button type="button" class="pm-icon-btn" data-modal-open="pmSearchModal" aria-label="Search projects" title="Search (Ctrl+K)">
                <?= icon('search', 18) ?>
            </button>

            <a href="<?= e(url('cart.php')) ?>" class="pm-icon-btn" aria-label="Shopping cart" title="Shopping Cart">
                <?= icon('shopping-bag', 18) ?>
                <span class="pm-counter-dot" data-cart-count <?= $cartTotalItems > 0 ? '' : 'hidden' ?>><?= $cartTotalItems > 99 ? '99+' : $cartTotalItems ?></span>
            </a>

            <?php if ($navUser): ?>
                <button type="button" class="pm-header-user-btn" data-drawer-open="pmMobileDrawer" aria-label="Open account menu">
                    <span class="pm-avatar">
                        <?php if (!empty($navUser['avatar'])): ?>
                            <img src="<?= e(media_url((string)$navUser['avatar'])) ?>" alt="<?= e($navUser['full_name']) ?>">
                        <?php else: ?>
                            <?= e(user_initials((string)$navUser['full_name'])) ?>
                        <?php endif; ?>
                    </span>
                </button>
            <?php else: ?>
                <a href="<?= e(url('login.php')) ?>" class="pm-btn pm-btn-soft pm-btn-sm pm-header-signin">
                    <span>Sign In</span>
                </a>
            <?php endif; ?>
        </div>
    </div>
</header>

<!-- Organized Slide-Out Side Menu -->
<div class="pm-drawer-backdrop" id="pmDrawerBackdrop"></div>
<aside class="pm-mobile-drawer pm-side-menu" id="pmMobileDrawer" aria-label="Side Menu">
    <!-- Top Brand & Close Header -->
    <div class="pm-side-menu-head">
        <a href="<?= e(url('index.php')) ?>" class="pm-brand">
            <?= brand_logo($siteNameNav) ?>
        </a>
        <button type="button" class="pm-icon-btn pm-icon-btn-sm" data-drawer-close aria-label="Close side menu">
            <?= icon('close', 17) ?>
        </button>
    </div>

    <div class="pm-side-menu-scroll">
        <!-- Search Box -->
        <form action="<?= e(url('search.php')) ?>" method="get" class="pm-input-wrap pm-side-search">
            <?= icon('search', 16) ?>
            <input type="search" name="q" class="pm-input" placeholder="Search projects &amp; templates...">
        </form>

        <!-- Account Summary / Auth Box -->
        <?php if ($navUser): ?>
            <div class="pm-side-user-card">
                <span class="pm-avatar">
                    <?php if (!empty($navUser['avatar'])): ?>
                        <img src="<?= e(media_url((string)$navUser['avatar'])) ?>" alt="<?= e($navUser['full_name']) ?>">
                    <?php else: ?>
                        <?= e(user_initials((string)$navUser['full_name'])) ?>
                    <?php endif; ?>
                </span>
                <div class="pm-side-user-meta">
                    <strong><?= e($navUser['full_name']) ?></strong>
                    <small>@<?= e($navUser['username']) ?></small>
                </div>
                <span class="pm-side-role-pill"><?= $isSellerApproved ? 'Seller' : 'Buyer' ?></span>
            </div>
        <?php else: ?>
            <div class="pm-side-auth-card">
                <div class="pm-side-auth-row">
                    <a href="<?= e(url('login.php')) ?>" class="pm-btn pm-btn-soft pm-btn-sm">
                        <?= icon('user', 15) ?><span>Sign In</span>
                    </a>
                    <a href="<?= e(url('register.php')) ?>" class="pm-btn pm-btn-primary pm-btn-sm">
                        <?= icon('user-plus', 15) ?><span>Register</span>
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <!-- Quick Shortcut Tiles (Cart / Wishlist / Alerts) -->
        <div class="pm-side-quick-grid <?= $navUser ? 'is-three' : 'is-two' ?>">
            <a href="<?= e(url('cart.php')) ?>" class="pm-side-quick-tile <?= in_array($curNavScript, ['cart.php', 'checkout.php', 'payment.php'], true) ? 'is-active' : '' ?>">
                <span class="pm-side-quick-icon">
                    <?= icon('shopping-bag', 17) ?>
                    <span class="pm-side-quick-badge" data-cart-count <?= $cartTotalItems > 0 ? '' : 'hidden' ?>><?= $cartTotalItems > 99 ? '99+' : $cartTotalItems ?></span>
                </span>
                <span class="pm-side-quick-label">Cart</span>
            </a>

            <a href="<?= e(url('buyer/wishlist.php')) ?>" class="pm-side-quick-tile <?= $curNavScript === 'buyer/wishlist.php' ? 'is-active' : '' ?>">
                <span class="pm-side-quick-icon">
                    <?= icon('heart', 17) ?>
                    <span class="pm-side-quick-badge" data-wishlist-count <?= $wishCount > 0 ? '' : 'hidden' ?>><?= $wishCount > 99 ? '99+' : $wishCount ?></span>
                </span>
                <span class="pm-side-quick-label">Wishlist</span>
            </a>

            <?php if ($navUser): ?>
                <a href="<?= e(url('buyer/notifications.php')) ?>" class="pm-side-quick-tile <?= $curNavScript === 'buyer/notifications.php' ? 'is-active' : '' ?>">
                    <span class="pm-side-quick-icon">
                        <?= icon('bell', 17) ?>
                        <span class="pm-side-quick-badge" data-notif-count <?= $unreadNotifs > 0 ? '' : 'hidden' ?>><?= $unreadNotifs > 99 ? '99+' : $unreadNotifs ?></span>
                    </span>
                    <span class="pm-side-quick-label">Alerts</span>
                </a>
            <?php endif; ?>
        </div>

        <!-- 1. Browse Marketplace -->
        <div class="pm-side-group">
            <div class="pm-side-group-title">Marketplace</div>
            <div class="pm-side-card">
                <ul class="pm-side-list">
                    <?php foreach ($sideBrowseLinks as $item):
                        $active = is_active_nav($item['url'])
                            || ($item['url'] === 'categories.php' && $curNavScript === 'category.php')
                            || ($item['url'] === 'projects.php' && in_array($curNavScript, ['project.php', 'search.php'], true)); ?>
                        <li>
                            <a href="<?= e(url($item['url'])) ?>" class="pm-side-link <?= $active ? 'is-active' : '' ?>">
                                <span class="pm-side-icon-box"><?= icon($item['icon'], 16) ?></span>
                                <span class="pm-side-label"><?= e($item['label']) ?></span>
                                <?php if ($item['meta'] !== ''): ?>
                                    <span class="pm-side-meta-pill"><?= e($item['meta']) ?></span>
                                <?php endif; ?>
                                <span class="pm-side-arrow"><?= icon('chevron-right', 15) ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <!-- 2. Buyer Account (When Signed In) -->
        <?php if ($navUser): ?>
            <div class="pm-side-group">
                <div class="pm-side-group-title">My Account</div>
                <div class="pm-side-card">
                    <ul class="pm-side-list">
                        <?php foreach ($sideBuyerPrimary as $item):
                            $active = is_active_nav($item['url']) || ($item['url'] === 'buyer/orders.php' && $curNavScript === 'buyer/order-details.php'); ?>
                            <li>
                                <a href="<?= e(url($item['url'])) ?>" class="pm-side-link <?= $active ? 'is-active' : '' ?>">
                                    <span class="pm-side-icon-box"><?= icon($item['icon'], 16) ?></span>
                                    <span class="pm-side-label"><?= e($item['label']) ?></span>
                                    <span class="pm-side-arrow"><?= icon('chevron-right', 15) ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <details class="pm-side-collapse" <?= $buyerMoreOpen ? 'open' : '' ?>>
                        <summary class="pm-side-collapse-btn">
                            <span class="pm-side-icon-box"><?= icon('settings', 16) ?></span>
                            <span class="pm-side-label">Settings &amp; Tickets</span>
                            <span class="pm-side-chevron"><?= icon('chevron-down', 15) ?></span>
                        </summary>
                        <ul class="pm-side-sublist">
                            <?php foreach ($sideBuyerMore as $item):
                                $active = is_active_nav($item['url']); ?>
                                <li>
                                    <a href="<?= e(url($item['url'])) ?>" class="pm-side-sublink <?= $active ? 'is-active' : '' ?>">
                                        <?= icon($item['icon'], 15) ?>
                                        <span><?= e($item['label']) ?></span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </details>
                </div>
            </div>
        <?php endif; ?>

        <!-- 3. Seller Studio / Start Selling -->
        <div class="pm-side-group">
            <div class="pm-side-group-title"><?= ($navUser && $isSellerApproved) ? 'Seller Studio' : 'Creator Program' ?></div>
            <?php if ($navUser && $isSellerApproved): ?>
                <div class="pm-side-card">
                    <ul class="pm-side-list">
                        <?php foreach ($sideSellerPrimary as $item):
                            $active = is_active_nav($item['url']) || ($item['url'] === 'seller/projects.php' && $curNavScript === 'seller/edit-project.php'); ?>
                            <li>
                                <a href="<?= e(url($item['url'])) ?>" class="pm-side-link <?= $active ? 'is-active' : '' ?>">
                                    <span class="pm-side-icon-box"><?= icon($item['icon'], 16) ?></span>
                                    <span class="pm-side-label"><?= e($item['label']) ?></span>
                                    <span class="pm-side-arrow"><?= icon('chevron-right', 15) ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <details class="pm-side-collapse" <?= $sellerMoreOpen ? 'open' : '' ?>>
                        <summary class="pm-side-collapse-btn">
                            <span class="pm-side-icon-box"><?= icon('sliders', 16) ?></span>
                            <span class="pm-side-label">More Studio Tools</span>
                            <span class="pm-side-chevron"><?= icon('chevron-down', 15) ?></span>
                        </summary>
                        <ul class="pm-side-sublist">
                            <?php foreach ($sideSellerMore as $item):
                                $active = is_active_nav($item['url']); ?>
                                <li>
                                    <a href="<?= e(url($item['url'])) ?>" class="pm-side-sublink <?= $active ? 'is-active' : '' ?>">
                                        <?= icon($item['icon'], 15) ?>
                                        <span><?= e($item['label']) ?></span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </details>
                </div>
            <?php else: ?>
                <a href="<?= e(url($navUser ? 'seller/become-seller.php' : 'register.php?role=seller')) ?>"
                   class="pm-side-seller-cta <?= $curNavScript === 'seller/become-seller.php' ? 'is-active' : '' ?>">
                    <span class="pm-side-seller-icon"><?= icon('store', 18) ?></span>
                    <span class="pm-side-seller-copy">
                        <strong><?= $navUser ? 'Become a Seller' : 'Start Selling' ?></strong>
                        <small>Sell your projects &amp; earn in BDT</small>
                    </span>
                    <span class="pm-side-arrow"><?= icon('chevron-right', 16) ?></span>
                </a>
            <?php endif; ?>
        </div>

        <!-- 4. Support & Policies -->
        <div class="pm-side-group">
            <div class="pm-side-group-title">Help &amp; Information</div>
            <div class="pm-side-card">
                <ul class="pm-side-list">
                    <?php foreach ($sideSupportLinks as $item):
                        $active = is_active_nav($item['url']); ?>
                        <li>
                            <a href="<?= e(url($item['url'])) ?>" class="pm-side-link <?= $active ? 'is-active' : '' ?>">
                                <span class="pm-side-icon-box"><?= icon($item['icon'], 16) ?></span>
                                <span class="pm-side-label"><?= e($item['label']) ?></span>
                                <span class="pm-side-arrow"><?= icon('chevron-right', 15) ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <details class="pm-side-collapse" <?= $legalOpen ? 'open' : '' ?>>
                    <summary class="pm-side-collapse-btn">
                        <span class="pm-side-icon-box"><?= icon('file-text', 16) ?></span>
                        <span class="pm-side-label">Legal &amp; Policies</span>
                        <span class="pm-side-chevron"><?= icon('chevron-down', 15) ?></span>
                    </summary>
                    <ul class="pm-side-sublist">
                        <?php foreach ($sideLegalLinks as $item):
                            $active = is_active_nav($item['url']); ?>
                            <li>
                                <a href="<?= e(url($item['url'])) ?>" class="pm-side-sublink <?= $active ? 'is-active' : '' ?>">
                                    <?= icon($item['icon'], 15) ?>
                                    <span><?= e($item['label']) ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </details>
            </div>
        </div>
    </div>

    <!-- Sticky Drawer Footer -->
    <div class="pm-side-menu-foot">
        <?php if ($navUser): ?>
            <form method="post" action="<?= e(url('logout.php')) ?>">
                <?= csrf_field() ?>
                <button type="submit" class="pm-btn pm-btn-secondary pm-btn-sm pm-btn-block">
                    <?= icon('logout', 16) ?><span>Sign Out</span>
                </button>
            </form>
        <?php else: ?>
            <div class="pm-side-foot-note">
                <?= icon('lock', 13) ?>
                <span>Secure Payments · bKash · Nagad · Rocket</span>
            </div>
        <?php endif; ?>
    </div>
</aside>

<!-- Instant Search Command Modal -->
<div class="pm-modal" id="pmSearchModal" aria-hidden="true" role="dialog" aria-label="Quick project search">
    <div class="pm-modal-dialog pm-search-palette">
        <div class="pm-modal-head">
            <strong class="pm-h4" style="display:inline-flex;align-items:center;gap:8px;"><?= icon('search', 18) ?><span>Search Marketplace</span></strong>
            <button type="button" class="pm-icon-btn pm-icon-btn-sm" data-modal-close aria-label="Close search"><?= icon('close', 16) ?></button>
        </div>
        <form action="<?= e(url('search.php')) ?>" method="get">
            <div class="pm-input-wrap">
                <?= icon('search', 18) ?>
                <input type="search" id="pmQuickSearchInput" name="q" class="pm-input" placeholder="Search by title, stack (PHP, Kotlin, WordPress, Python, Figma)..." autocomplete="off">
            </div>
        </form>
        <div id="pmQuickSearchResults" class="pm-live-results">
            <p class="pm-muted" style="text-align:center;padding:18px 8px;">Type at least 2 characters to preview instant results, or press Enter for full filters.</p>
        </div>
    </div>
</div>
