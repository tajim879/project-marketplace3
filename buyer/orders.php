<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$user = require_login();
$statusFilter = trim((string)($_GET['status'] ?? 'all'));
$orders = OrderRepository::buyerOrders((int)$user['id'], $statusFilter, 50);

$statusTabs = [
    'all' => 'All Orders',
    'paid' => 'Paid',
    'payment_submitted' => 'Submitted',
    'under_verification' => 'Verifying',
    'pending_payment' => 'Pending',
    'rejected' => 'Rejected',
];

$pageTitle = 'My Orders';
$pageRobots = 'noindex, nofollow';
$pageStyles = ['buyer.css'];

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
                        <h1 class="pm-h2">My Orders</h1>
                        <p class="pm-muted">Complete order timeline, payment verification states, and downloadable BDT invoices.</p>
                    </div>
                </div>

                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                    <?php foreach ($statusTabs as $k => $lbl): ?>
                        <a href="<?= e(url('buyer/orders.php?status=' . urlencode($k))) ?>" class="pm-chip <?= $statusFilter === $k ? 'is-active' : '' ?>">
                            <?= e($lbl) ?>
                        </a>
                    <?php endforeach; ?>
                </div>

                <?php if (empty($orders)): ?>
                    <div class="pm-card pm-empty">
                        <div class="pm-icon-orb"><?= icon('shopping-bag', 25) ?></div>
                        <h2 class="pm-h3">No orders in this view</h2>
                        <a href="<?= e(url('projects.php')) ?>" class="pm-btn pm-btn-primary pm-btn-sm">Browse Projects</a>
                    </div>
                <?php else: ?>
                    <div class="pm-table-wrap">
                        <table class="pm-table">
                            <thead>
                                <tr>
                                    <th>Order Number</th>
                                    <th>Placed</th>
                                    <th>Channel</th>
                                    <th>Total</th>
                                    <th>Order Status</th>
                                    <th style="text-align:right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $o): ?>
                                    <tr>
                                        <td><strong style="color:var(--text-primary);"><?= e($o['order_number']) ?></strong><small class="pm-muted" style="display:block;"><?= (int)$o['items_count'] ?> <?= (int)$o['items_count'] === 1 ? 'item' : 'items' ?></small></td>
                                        <td><?= e(format_date((string)$o['created_at'])) ?></td>
                                        <td><?= e($o['payment_method_name'] ?: (bccomp((string)$o['total_amount'], '0.00', 2) === 0 ? 'Free License' : 'Manual Transfer')) ?></td>
                                        <td class="pm-tabular" style="font-weight:700;color:var(--text-primary);"><?= e(format_price((string)$o['total_amount'])) ?></td>
                                        <td><?= render_status_badge((string)$o['status']) ?></td>
                                        <td style="text-align:right;">
                                            <div class="pm-btn-row" style="justify-content:flex-end;">
                                                <?php if (in_array((string)$o['status'], ['pending_payment', 'rejected'], true) && bccomp((string)$o['total_amount'], '0.00', 2) > 0): ?>
                                                    <a href="<?= e(url('payment.php?order=' . urlencode((string)$o['order_number']))) ?>" class="pm-btn pm-btn-primary pm-btn-sm"><?= icon('credit-card', 14) ?><span>Pay Now</span></a>
                                                <?php endif; ?>
                                                <a href="<?= e(url('invoice.php?order=' . urlencode((string)$o['order_number']))) ?>" class="pm-btn pm-btn-ghost pm-btn-sm" title="Invoice"><?= icon('receipt', 15) ?><span>Invoice</span></a>
                                                <a href="<?= e(url('buyer/order-details.php?order=' . urlencode((string)$o['order_number']))) ?>" class="pm-btn pm-btn-secondary pm-btn-sm">View</a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php require INCLUDES_PATH . '/layouts/footer.php'; ?>
