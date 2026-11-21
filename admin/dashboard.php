<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$pageTitle = 'Overview Dashboard';
require INCLUDES_PATH . '/layouts/admin-header.php';

$pdo = db();
$totalUsers = (int)$pdo->query("SELECT COUNT(*) FROM `users`")->fetchColumn();
$activeSellers = (int)$pdo->query("SELECT COUNT(*) FROM `users` WHERE `is_seller` = 1 AND `seller_status` = 'approved' AND `status` = 'active'")->fetchColumn();
$liveProjects = (int)$pdo->query("SELECT COUNT(*) FROM `projects` WHERE `status` = 'approved'")->fetchColumn();
$totalProjects = (int)$pdo->query("SELECT COUNT(*) FROM `projects`")->fetchColumn();
$paidOrders = (int)$pdo->query("SELECT COUNT(*) FROM `orders` WHERE `status` = 'paid'")->fetchColumn();

$grossRevenue = (string)($pdo->query("SELECT COALESCE(printf('%.2f', SUM(CAST(`total_amount` AS REAL))), '0.00') FROM `orders` WHERE `status` = 'paid'")->fetchColumn() ?: '0.00');
$adminCommission = (string)($pdo->query("SELECT COALESCE(printf('%.2f', SUM(CAST(`admin_commission` AS REAL))), '0.00') FROM `commissions` WHERE `status` = 'credited'")->fetchColumn() ?: '0.00');
$sellerPaidOut = (string)($pdo->query("SELECT COALESCE(printf('%.2f', SUM(CAST(`amount` AS REAL))), '0.00') FROM `withdrawals` WHERE `status` = 'paid'")->fetchColumn() ?: '0.00');
$pendingWdAmt = (string)($pdo->query("SELECT COALESCE(printf('%.2f', SUM(CAST(`amount` AS REAL))), '0.00') FROM `withdrawals` WHERE `status` IN ('pending','approved')")->fetchColumn() ?: '0.00');

// 6-month revenue chart
$months = [];
$maxMonthVal = 1.0;
for ($i = 5; $i >= 0; $i--) {
    $ym = date('Y-m', strtotime("-{$i} months"));
    $label = date('M', strtotime($ym . '-01'));
    $mStmt = $pdo->prepare("SELECT COALESCE(SUM(CAST(`total_amount` AS REAL)), 0) FROM `orders` WHERE `status` = 'paid' AND substr(`paid_at`, 1, 7) = ?");
    $mStmt->execute([$ym]);
    $val = (float)$mStmt->fetchColumn();
    if ($val > $maxMonthVal) {
        $maxMonthVal = $val;
    }
    $months[] = ['label' => $label, 'value' => $val];
}

// Pending payment queue
$pendingPayments = $pdo->query(
    "SELECT pay.*, o.order_number, o.total_amount AS order_total, u.full_name AS buyer_name,
            (SELECT pp.id FROM `payment_proofs` pp WHERE pp.payment_id = pay.id ORDER BY pp.id DESC LIMIT 1) AS proof_id
     FROM `payments` pay
     INNER JOIN `orders` o ON o.id = pay.order_id
     INNER JOIN `users` u ON u.id = pay.buyer_id
     WHERE pay.status IN ('submitted','under_verification')
     ORDER BY pay.submitted_at ASC LIMIT 6"
)->fetchAll();

// Recent orders
$recentOrders = $pdo->query(
    "SELECT o.*, u.full_name AS buyer_name
     FROM `orders` o
     INNER JOIN `users` u ON u.id = o.buyer_id
     ORDER BY o.id DESC LIMIT 6"
)->fetchAll();

// Recent admin audit logs
$recentLogs = $pdo->query(
    "SELECT al.*, u.username AS admin_username
     FROM `admin_logs` al
     INNER JOIN `users` u ON u.id = al.admin_id
     ORDER BY al.id DESC LIMIT 6"
)->fetchAll();
?>

<div class="pm-admin-header">
    <div>
        <h1>Marketplace Executive Overview</h1>
        <p>Real-time commerce, bKash/Nagad/Rocket payment verification, <?= e(site_setting('default_commission_rate', '10.00')) ?>% commission accounting, and platform health.</p>
    </div>
    <div class="pm-btn-row">
        <a href="<?= e(url('admin/payments.php')) ?>" class="pm-btn pm-btn-primary">
            <?= icon('credit-card', 16) ?><span>Verify Payments (<?= (int)$adminQueues['payments'] ?>)</span>
        </a>
        <a href="<?= e(url('admin/website-control.php')) ?>" class="pm-btn pm-btn-secondary">
            <?= icon('settings', 16) ?><span>Website Control</span>
        </a>
    </div>
</div>

<!-- 8 Real-Time KPI Cards -->
<div class="pm-admin-kpi-grid">
    <div class="pm-card pm-admin-kpi">
        <div>
            <strong class="pm-admin-kpi-val"><?= e(format_price($grossRevenue)) ?></strong>
            <span class="pm-admin-kpi-lbl">Verified Gross Revenue</span>
            <span class="pm-admin-kpi-sub"><?= number_format($paidOrders) ?> paid orders</span>
        </div>
        <span class="pm-stat-icon is-success"><?= icon('trending-up', 20) ?></span>
    </div>
    <div class="pm-card pm-admin-kpi">
        <div>
            <strong class="pm-admin-kpi-val"><?= e(format_price($adminCommission)) ?></strong>
            <span class="pm-admin-kpi-lbl">Platform Commission Earned</span>
            <span class="pm-admin-kpi-sub">Default rate: <?= e(site_setting('default_commission_rate', '10.00')) ?>%</span>
        </div>
        <span class="pm-stat-icon"><?= icon('bar-chart', 20) ?></span>
    </div>
    <div class="pm-card pm-admin-kpi">
        <div>
            <strong class="pm-admin-kpi-val"><?= (int)$adminQueues['payments'] ?></strong>
            <span class="pm-admin-kpi-lbl">Pending Payment Proofs</span>
            <a href="<?= e(url('admin/payments.php')) ?>" class="pm-admin-kpi-sub">Review queue</a>
        </div>
        <span class="pm-stat-icon is-warning"><?= icon('credit-card', 20) ?></span>
    </div>
    <div class="pm-card pm-admin-kpi">
        <div>
            <strong class="pm-admin-kpi-val"><?= e(format_price($pendingWdAmt)) ?></strong>
            <span class="pm-admin-kpi-lbl">Pending Seller Payouts</span>
            <span class="pm-admin-kpi-sub"><?= (int)$adminQueues['withdrawals'] ?> requests · Paid <?= e(format_price($sellerPaidOut)) ?></span>
        </div>
        <span class="pm-stat-icon is-warning"><?= icon('wallet', 20) ?></span>
    </div>
    <div class="pm-card pm-admin-kpi">
        <div>
            <strong class="pm-admin-kpi-val"><?= number_format($totalUsers) ?></strong>
            <span class="pm-admin-kpi-lbl">Registered Accounts</span>
            <a href="<?= e(url('admin/users.php')) ?>" class="pm-admin-kpi-sub">Manage users</a>
        </div>
        <span class="pm-stat-icon"><?= icon('users', 20) ?></span>
    </div>
    <div class="pm-card pm-admin-kpi">
        <div>
            <strong class="pm-admin-kpi-val"><?= number_format($activeSellers) ?></strong>
            <span class="pm-admin-kpi-lbl">Approved Sellers</span>
            <span class="pm-admin-kpi-sub"><?= (int)$adminQueues['sellers'] ?> awaiting approval</span>
        </div>
        <span class="pm-stat-icon is-success"><?= icon('store', 20) ?></span>
    </div>
    <div class="pm-card pm-admin-kpi">
        <div>
            <strong class="pm-admin-kpi-val"><?= $liveProjects ?> / <?= $totalProjects ?></strong>
            <span class="pm-admin-kpi-lbl">Live / Total Projects</span>
            <span class="pm-admin-kpi-sub"><?= (int)$adminQueues['projects'] ?> pending moderation</span>
        </div>
        <span class="pm-stat-icon"><?= icon('package', 20) ?></span>
    </div>
    <div class="pm-card pm-admin-kpi">
        <div>
            <strong class="pm-admin-kpi-val"><?= (int)$adminQueues['tickets'] ?></strong>
            <span class="pm-admin-kpi-lbl">Open Support Tickets</span>
            <span class="pm-admin-kpi-sub"><?= (int)$adminQueues['reports'] ?> flagged reports</span>
        </div>
        <span class="pm-stat-icon"><?= icon('life-buoy', 20) ?></span>
    </div>
</div>

<!-- Pending Manual Payments Queue + 6-Month Revenue Chart -->
<div class="pm-admin-split">
    <div class="pm-card pm-card-pad" style="display:grid;gap:14px;">
        <div class="pm-admin-toolbar">
            <h2 class="pm-h3">Manual Payments Awaiting Verification</h2>
            <a href="<?= e(url('admin/payments.php')) ?>" class="pm-link" style="font-size:0.82rem;">Open Full Queue</a>
        </div>
        <?php if (empty($pendingPayments)): ?>
            <p class="pm-muted" style="padding:20px 0;">All submitted bKash, Nagad, and Rocket payments have been processed.</p>
        <?php else: ?>
            <div class="pm-table-wrap">
                <table class="pm-table">
                    <thead><tr><th>Order</th><th>Method</th><th>TrxID &amp; Sender</th><th>Amount</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php foreach ($pendingPayments as $pp): ?>
                            <tr>
                                <td><code><?= e($pp['order_number']) ?></code><small class="pm-muted" style="display:block;"><?= e($pp['buyer_name']) ?></small></td>
                                <td><?= payment_brand_badge((string)$pp['method_code'], 'sm') ?></td>
                                <td><code><?= e($pp['transaction_id']) ?></code><small class="pm-muted pm-tabular" style="display:block;"><?= e($pp['sender_number']) ?></small></td>
                                <td class="pm-tabular" style="font-weight:700;"><?= e(format_price((string)$pp['amount'])) ?></td>
                                <td><a href="<?= e(url('admin/payments.php')) ?>" class="pm-btn pm-btn-primary pm-btn-sm">Verify</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div class="pm-card pm-card-pad" style="display:grid;gap:10px;">
        <div class="pm-admin-toolbar">
            <h2 class="pm-h3">6-Month Verified Revenue (BDT)</h2>
            <a href="<?= e(url('admin/commissions.php')) ?>" class="pm-link" style="font-size:0.82rem;">Ledger</a>
        </div>
        <div class="pm-admin-chart">
            <?php foreach ($months as $m):
                $pct = $m['value'] > 0 ? max(8, (int)round(($m['value'] / $maxMonthVal) * 100)) : 4; ?>
                <div class="pm-admin-bar-col">
                    <span class="pm-admin-bar-val"><?= $m['value'] > 0 ? '৳' . number_format($m['value'], 0) : '৳0' ?></span>
                    <div class="pm-admin-bar" style="height:<?= $pct ?>%;" title="<?= e($m['label']) ?>: <?= e(format_price((string)$m['value'])) ?>"></div>
                    <span class="pm-admin-bar-lbl"><?= e($m['label']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Recent Orders + Recent Audit Trail -->
<div class="pm-admin-split-equal">
    <div class="pm-card pm-card-pad" style="display:grid;gap:14px;">
        <div class="pm-admin-toolbar">
            <h2 class="pm-h3">Latest Marketplace Orders</h2>
            <a href="<?= e(url('admin/orders.php')) ?>" class="pm-link" style="font-size:0.82rem;">All Orders</a>
        </div>
        <?php if (empty($recentOrders)): ?>
            <p class="pm-muted" style="padding:16px 0;">No buyer orders placed yet.</p>
        <?php else: ?>
            <div class="pm-table-wrap">
                <table class="pm-table">
                    <thead><tr><th>Order #</th><th>Buyer</th><th>Total</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        <?php foreach ($recentOrders as $ro): ?>
                            <tr>
                                <td><code><?= e($ro['order_number']) ?></code></td>
                                <td><?= e($ro['buyer_name']) ?></td>
                                <td class="pm-tabular" style="font-weight:700;"><?= e(format_price((string)$ro['total_amount'])) ?></td>
                                <td><?= render_status_badge((string)$ro['status']) ?></td>
                                <td style="text-align:right;"><a href="<?= e(url('admin/order-view.php?order=' . urlencode((string)$ro['order_number']))) ?>" class="pm-btn pm-btn-ghost pm-btn-sm">Open</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div class="pm-card pm-card-pad" style="display:grid;gap:14px;">
        <div class="pm-admin-toolbar">
            <h2 class="pm-h3">Recent Admin Activity</h2>
            <a href="<?= e(url('admin/logs.php')) ?>" class="pm-link" style="font-size:0.82rem;">Full Audit Log</a>
        </div>
        <?php if (empty($recentLogs)): ?>
            <p class="pm-muted" style="padding:16px 0;">No admin actions logged yet.</p>
        <?php else: ?>
            <div class="pm-table-wrap">
                <table class="pm-table">
                    <thead><tr><th>Action</th><th>Admin</th><th>Details</th><th>Time</th></tr></thead>
                    <tbody>
                        <?php foreach ($recentLogs as $lg): ?>
                            <tr>
                                <td><code><?= e($lg['action']) ?></code></td>
                                <td>@<?= e($lg['admin_username']) ?></td>
                                <td style="font-size:0.8rem;"><?= e((string)($lg['details'] ?? '—')) ?></td>
                                <td style="font-size:0.78rem;" class="pm-muted"><?= e(format_datetime((string)$lg['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require INCLUDES_PATH . '/layouts/admin-footer.php'; ?>
