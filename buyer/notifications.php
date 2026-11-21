<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$user = require_login();
$uid = (int)$user['id'];
$unreadOnly = ($_GET['filter'] ?? '') === 'unread';
$notifications = Notification::forUser($uid, 50, 0, $unreadOnly);
$unreadCount = unread_notifications_count();

$pageTitle = 'Notifications';
$pageRobots = 'noindex, nofollow';
$pageStyles = ['buyer.css'];

require INCLUDES_PATH . '/layouts/header.php';
?>

<section class="pm-dashboard-section">
    <div class="pm-container">
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
                        <h1 class="pm-h2">Notifications</h1>
                        <p class="pm-muted">Real-time updates for orders, payment verification, download unlocks, and support replies.</p>
                    </div>
                    <?php if ($unreadCount > 0): ?>
                        <button type="button" class="pm-btn pm-btn-secondary pm-btn-sm" data-notif-mark-all>
                            <?= icon('check', 15) ?><span>Mark All as Read</span>
                        </button>
                    <?php endif; ?>
                </div>

                <div class="pm-btn-row">
                    <a href="<?= e(url('buyer/notifications.php')) ?>" class="pm-chip <?= !$unreadOnly ? 'is-active' : '' ?>">All Notifications</a>
                    <a href="<?= e(url('buyer/notifications.php?filter=unread')) ?>" class="pm-chip <?= $unreadOnly ? 'is-active' : '' ?>">Unread (<?= $unreadCount ?>)</a>
                </div>

                <?php if (empty($notifications)): ?>
                    <div class="pm-card pm-empty">
                        <div class="pm-icon-orb"><?= icon('bell', 25) ?></div>
                        <h2 class="pm-h3">No notifications to display</h2>
                        <p class="pm-muted">You are completely up to date.</p>
                    </div>
                <?php else: ?>
                    <div class="pm-card" style="overflow:hidden;">
                        <?php foreach ($notifications as $n): ?>
                            <div class="pm-notif-item <?= (int)$n['is_read'] === 0 ? 'is-unread' : '' ?>" data-notif-row style="align-items:center;padding:16px 20px;">
                                <span class="pm-notif-icon"><?= icon((string)$n['icon_name'], 18) ?></span>
                                <div class="pm-notif-body">
                                    <strong><?= e($n['title']) ?></strong>
                                    <p style="-webkit-line-clamp:unset;"><?= e($n['message']) ?></p>
                                    <time><?= e(format_date((string)$n['created_at'], 'M j, Y · g:i A')) ?></time>
                                </div>
                                <div class="pm-btn-row" style="flex-shrink:0;">
                                    <?php if (!empty($n['action_url'])): ?>
                                        <a href="<?= e((string)$n['action_url']) ?>" class="pm-btn pm-btn-secondary pm-btn-sm">Open</a>
                                    <?php endif; ?>
                                    <?php if ((int)$n['is_read'] === 0): ?>
                                        <button type="button" class="pm-btn pm-btn-ghost pm-btn-sm" data-notif-read="<?= (int)$n['id'] ?>" title="Mark read"><?= icon('check', 15) ?></button>
                                    <?php endif; ?>
                                    <button type="button" class="pm-btn pm-btn-ghost pm-btn-sm" style="color:var(--danger);" data-notif-delete="<?= (int)$n['id'] ?>" title="Delete" aria-label="Delete notification"><?= icon('trash', 15) ?></button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php require INCLUDES_PATH . '/layouts/footer.php'; ?>
