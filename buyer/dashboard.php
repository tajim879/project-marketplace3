<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$user = require_login();
$uid = (int)$user['id'];
$stats = UserRepository::buyerDashboardStats($uid);
$recentOrders = OrderRepository::buyerOrders($uid, null, 5);
$recentDownloads = OrderRepository::buyerDownloadHistory($uid, 4);

$secStmt = db()->prepare("SELECT * FROM `security_logs` WHERE `user_id` = ? ORDER BY `created_at` DESC LIMIT 5");
$secStmt->execute([$uid]);
$activities = $secStmt->fetchAll();

$pageTitle = 'Buyer Dashboard';
$pageRobots = 'noindex, nofollow';
$pageStyles = ['buyer.css'];
$pageScripts = ['dashboard.js'];

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
                        <h1 class="pm-h2">Welcome back, <?= e($user['full_name']) ?></h1>
                        <p class="pm-muted">Track your purchased projects, secure ZIP downloads, order verification, and account security.</p>
                    </div>
                    <a href="<?= e(url('projects.php')) ?>" class="pm-btn pm-btn-primary">
                        <?= icon('grid', 16) ?><span>Explore Marketplace</span>
                    </a>
                </div>

                <!-- Real MySQL KPI Cards -->
                <div class="pm-kpi-grid">
                    <div class="pm-card pm-kpi-card">
                        <div class="pm-kpi-top">
                            <span class="pm-kpi-label">Total Purchases</span>
                            <span class="pm-stat-icon" style="width:38px;height:38px;border-radius:11px;"><?= icon('package', 18) ?></span>
                        </div>
                        <strong class="pm-kpi-value"><?= number_format($stats['total_purchases']) ?></strong>
                    </div>
                    <div class="pm-card pm-kpi-card">
                        <div class="pm-kpi-top">
                            <span class="pm-kpi-label">Total Downloads</span>
                            <span class="pm-stat-icon is-success" style="width:38px;height:38px;border-radius:11px;"><?= icon('download', 18) ?></span>
                        </div>
                        <strong class="pm-kpi-value"><?= number_format($stats['total_downloads']) ?></strong>
                    </div>
                    <div class="pm-card pm-kpi-card">
                        <div class="pm-kpi-top">
                            <span class="pm-kpi-label">Saved Wishlist</span>
                            <span class="pm-stat-icon" style="width:38px;height:38px;border-radius:11px;"><?= icon('heart', 18) ?></span>
                        </div>
                        <strong class="pm-kpi-value"><?= number_format($stats['wishlist_count']) ?></strong>
                    </div>
                    <div class="pm-card pm-kpi-card">
                        <div class="pm-kpi-top">
                            <span class="pm-kpi-label">Verified Spend</span>
                            <span class="pm-stat-icon is-warning" style="width:38px;height:38px;border-radius:11px;"><?= icon('wallet', 18) ?></span>
                        </div>
                        <strong class="pm-kpi-value"><?= e(format_price($stats['total_spent'])) ?></strong>
                    </div>
                </div>

                <!-- Recent Orders -->
                <div>
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
                        <h2 class="pm-h3">Recent Orders</h2>
                        <a href="<?= e(url('buyer/orders.php')) ?>" class="pm-link" style="font-size:0.84rem;">View all orders</a>
                    </div>

                    <?php if (empty($recentOrders)): ?>
                        <div class="pm-card pm-empty">
                            <div class="pm-icon-orb"><?= icon('shopping-bag', 24) ?></div>
                            <h3 class="pm-h4">No orders placed yet</h3>
                            <p class="pm-muted">Claim a free starter project or add a premium release to your cart.</p>
                            <a href="<?= e(url('free-projects.php')) ?>" class="pm-btn pm-btn-primary pm-btn-sm">Browse Free Projects</a>
                        </div>
                    <?php else: ?>
                        <div class="pm-table-wrap">
                            <table class="pm-table">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Date</th>
                                        <th>Items</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th style="text-align:right;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentOrders as $ord): ?>
                                        <tr>
                                            <td><strong style="color:var(--text-primary);"><?= e($ord['order_number']) ?></strong></td>
                                            <td><?= e(format_date((string)$ord['created_at'])) ?></td>
                                            <td><?= (int)$ord['items_count'] ?></td>
                                            <td class="pm-tabular" style="font-weight:700;color:var(--text-primary);"><?= e(format_price((string)$ord['total_amount'])) ?></td>
                                            <td><?= render_status_badge((string)$ord['status']) ?></td>
                                            <td style="text-align:right;">
                                                <a href="<?= e(url('buyer/order-details.php?order=' . urlencode((string)$ord['order_number']))) ?>" class="pm-btn pm-btn-secondary pm-btn-sm">Details</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Recent Activity & Account Information -->
                <div class="pm-grid-2">
                    <div class="pm-card pm-card-pad" style="display:grid;gap:14px;align-content:start;">
                        <h2 class="pm-h3">Recent Account Activity</h2>
                        <?php if (empty($activities)): ?>
                            <p class="pm-muted">No recent activity logged.</p>
                        <?php else: ?>
                            <div class="pm-timeline">
                                <?php foreach ($activities as $act): ?>
                                    <div class="pm-timeline-step">
                                        <strong style="font-size:0.86rem;color:var(--text-primary);"><?= e($act['description']) ?></strong>
                                        <small class="pm-muted"><?= e(time_ago((string)$act['created_at'])) ?> · IP <?= e($act['ip_address'] ?: '—') ?></small>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="pm-card pm-card-pad" style="display:grid;gap:14px;align-content:start;">
                        <div style="display:flex;align-items:center;justify-content:space-between;">
                            <h2 class="pm-h3">Account Information</h2>
                            <a href="<?= e(url('buyer/profile.php')) ?>" class="pm-btn pm-btn-ghost pm-btn-sm"><?= icon('edit', 14) ?><span>Edit</span></a>
                        </div>
                        <dl class="pm-spec-list">
                            <div class="pm-spec-row"><dt>Full Name</dt><dd><?= e($user['full_name']) ?></dd></div>
                            <div class="pm-spec-row"><dt>Username</dt><dd>@<?= e($user['username']) ?></dd></div>
                            <div class="pm-spec-row"><dt>Email</dt><dd><?= e($user['email']) ?></dd></div>
                            <div class="pm-spec-row"><dt>Account Status</dt><dd><?= render_status_badge((string)$user['status']) ?></dd></div>
                            <div class="pm-spec-row"><dt>Seller Privileges</dt><dd><?= $user['seller_status'] === 'none' ? '<span class="pm-muted">Buyer Only</span>' : render_status_badge((string)$user['seller_status']) ?></dd></div>
                            <div class="pm-spec-row"><dt>Member Since</dt><dd><?= e(format_date((string)$user['created_at'])) ?></dd></div>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require INCLUDES_PATH . '/layouts/footer.php'; ?>
