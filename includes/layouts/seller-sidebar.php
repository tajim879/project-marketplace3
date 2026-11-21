<?php
declare(strict_types=1);

$ssUser = current_user();
$ssUid = (int)($ssUser['id'] ?? 0);
$ssBal = $ssUid > 0 ? ensure_seller_balance($ssUid) : ['available_balance' => '0.00'];

$ssQStmt = db()->prepare(
    "SELECT COUNT(*) FROM `project_questions` pq
     INNER JOIN `projects` p ON p.id = pq.project_id
     WHERE p.seller_id = ? AND pq.answer_text IS NULL AND pq.status = 'visible'"
);
$ssQStmt->execute([$ssUid]);
$ssUnanswered = (int)$ssQStmt->fetchColumn();

$sellerNav = [
    ['label' => 'Studio Overview', 'url' => 'seller/dashboard.php', 'icon' => 'grid', 'badge' => 0],
    ['label' => 'Upload Project', 'url' => 'seller/upload-project.php', 'icon' => 'upload', 'badge' => 0],
    ['label' => 'My Projects', 'url' => 'seller/projects.php', 'icon' => 'package', 'badge' => 0],
    ['label' => 'Version Updates', 'url' => 'seller/project-updates.php', 'icon' => 'layers', 'badge' => 0],
    ['label' => 'Sales History', 'url' => 'seller/sales.php', 'icon' => 'shopping-bag', 'badge' => 0],
    ['label' => 'Earnings & Ledger', 'url' => 'seller/earnings.php', 'icon' => 'bar-chart', 'badge' => 0],
    ['label' => 'Withdrawals', 'url' => 'seller/withdrawals.php', 'icon' => 'wallet', 'badge' => 0],
    ['label' => 'Buyer Reviews', 'url' => 'seller/reviews.php', 'icon' => 'star', 'badge' => 0],
    ['label' => 'Product Q&A', 'url' => 'seller/questions.php', 'icon' => 'help-circle', 'badge' => $ssUnanswered],
    ['label' => 'Store & Payouts', 'url' => 'seller/store-profile.php', 'icon' => 'store', 'badge' => 0],
];
?>
<div class="pm-dash-mobile-bar pm-seller-mobile-bar">
    <button type="button" class="pm-btn pm-btn-secondary pm-btn-sm" data-drawer-open="pmSellerSidebar">
        <?= icon('menu', 16) ?><span>Studio Menu</span>
    </button>
    <a href="<?= e(url('seller/upload-project.php')) ?>" class="pm-btn pm-btn-primary pm-btn-sm">
        <?= icon('upload', 15) ?><span>Upload</span>
    </a>
</div>
<aside class="pm-card pm-dash-sidebar" id="pmSellerSidebar" aria-label="Seller Studio Menu">
    <div class="pm-dash-drawer-close-row">
        <span class="pm-badge pm-badge-violet"><?= icon('store', 13) ?><span>Seller Studio</span></span>
        <button type="button" class="pm-icon-btn" data-drawer-close aria-label="Close seller menu"><?= icon('close', 18) ?></button>
    </div>
    <div class="pm-dash-user">
        <span class="pm-avatar pm-avatar-lg">
            <?php if (!empty($ssUser['avatar'])): ?>
                <img src="<?= e(media_url((string)$ssUser['avatar'])) ?>" alt="<?= e($ssUser['full_name'] ?? '') ?>">
            <?php else: ?>
                <?= e(user_initials((string)($ssUser['store_name'] ?: ($ssUser['full_name'] ?? 'S')))) ?>
            <?php endif; ?>
        </span>
        <div class="pm-dash-user-meta">
            <strong><?= e($ssUser['store_name'] ?: ($ssUser['full_name'] ?? 'Seller Studio')) ?></strong>
            <small class="pm-tabular" style="color:var(--success);font-weight:700;">Balance: <?= e(format_price((string)$ssBal['available_balance'])) ?></small>
        </div>
    </div>

    <nav class="pm-dash-nav">
        <?php foreach ($sellerNav as $item):
            $active = is_active_nav($item['url']) || ($item['url'] === 'seller/projects.php' && is_active_nav('seller/edit-project.php')); ?>
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
        <a href="<?= e(url('seller-profile.php?username=' . urlencode((string)($ssUser['username'] ?? '')))) ?>" class="pm-btn pm-btn-secondary pm-btn-sm pm-btn-block">
            <?= icon('external-link', 15) ?><span>Public Storefront</span>
        </a>
        <a href="<?= e(url('buyer/dashboard.php')) ?>" class="pm-btn pm-btn-soft pm-btn-sm pm-btn-block">
            <?= icon('user', 15) ?><span>Buyer Account</span>
        </a>
    </div>
</aside>
