<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$user = require_login();
$payments = OrderRepository::buyerPayments((int)$user['id']);

$pageTitle = 'Payment History';
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
                        <h1 class="pm-h2">Payment History</h1>
                        <p class="pm-muted">All bKash, Nagad, and Rocket payment submissions and verification updates.</p>
                    </div>
                </div>

                <?php if (empty($payments)): ?>
                    <div class="pm-card pm-empty">
                        <div class="pm-icon-orb"><?= icon('receipt', 25) ?></div>
                        <h2 class="pm-h3">No manual payment submissions yet</h2>
                        <p class="pm-muted">All verified bKash, Nagad, and Rocket TrxID records appear here automatically.</p>
                    </div>
                <?php else: ?>
                    <div class="pm-table-wrap">
                        <table class="pm-table">
                            <thead>
                                <tr>
                                    <th>Order</th>
                                    <th>Method</th>
                                    <th>Sender Number</th>
                                    <th>Transaction ID</th>
                                    <th>Amount</th>
                                    <th>Submitted</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payments as $p): ?>
                                    <tr>
                                        <td>
                                            <a class="pm-link" href="<?= e(url('buyer/order-details.php?order=' . urlencode((string)$p['order_number']))) ?>">
                                                <?= e($p['order_number']) ?>
                                            </a>
                                        </td>
                                        <td>
                                            <?= payment_brand_badge((string)($p['method_code'] ?? $p['method_name']), 'sm') ?>
                                        </td>
                                        <td><?= e($p['sender_number']) ?></td>
                                        <td><code><?= e($p['transaction_id']) ?></code></td>
                                        <td class="pm-tabular" style="font-weight:700;color:var(--text-primary);"><?= e(format_price((string)$p['amount'])) ?></td>
                                        <td><?= e(format_date((string)$p['submitted_at'], 'M j, Y')) ?></td>
                                        <td>
                                            <?= render_status_badge((string)$p['status']) ?>
                                            <?php if (!empty($p['rejection_reason'])): ?>
                                                <small style="display:block;color:var(--danger);margin-top:3px;"><?= e($p['rejection_reason']) ?></small>
                                            <?php endif; ?>
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
