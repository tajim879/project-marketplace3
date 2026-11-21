<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$user = require_seller();
$uid = (int)$user['id'];
$bal = ensure_seller_balance($uid);
$rate = seller_commission_rate($uid);

$lStmt = db()->prepare("SELECT * FROM `financial_ledger` WHERE `user_id` = ? ORDER BY `id` DESC LIMIT 100");
$lStmt->execute([$uid]);
$ledger = $lStmt->fetchAll();

$pageTitle = 'Earnings & Financial Ledger — Seller Studio';
$pageRobots = 'noindex, nofollow';
$pageStyles = ['dashboard.css'];

require INCLUDES_PATH . '/layouts/header.php';
?>

<section class="pm-dash-shell">
    <div class="pm-container">
        <div class="pm-dash-grid">
            <?php require INCLUDES_PATH . '/layouts/seller-sidebar.php'; ?>

            <div class="pm-dash-main">
                <?php require INCLUDES_PATH . '/components/alerts.php'; ?>

                <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                    <div>
                        <h1 class="pm-h2">Earnings &amp; Financial Ledger</h1>
                        <p class="pm-muted">Your commission rate is <strong><?= e($rate) ?>%</strong> — you keep <strong><?= e(number_format(100 - (float)$rate, 2)) ?>%</strong> of every verified sale.</p>
                    </div>
                    <a href="<?= e(url('seller/withdrawals.php')) ?>" class="pm-btn pm-btn-primary">
                        <?= icon('wallet', 17) ?><span>Request Withdrawal</span>
                    </a>
                </div>

                <div class="pm-grid-3">
                    <div class="pm-card pm-kpi-card"><span class="pm-stat-icon"><?= icon('shopping-bag', 20) ?></span><div><strong class="pm-kpi-value pm-tabular"><?= e(format_price((string)$bal['gross_sales'])) ?></strong><span class="pm-kpi-label">Gross Sales</span></div></div>
                    <div class="pm-card pm-kpi-card"><span class="pm-stat-icon is-warning"><?= icon('percent', 20) ?></span><div><strong class="pm-kpi-value pm-tabular"><?= e(format_price((string)$bal['total_commission'])) ?></strong><span class="pm-kpi-label">Platform Commission (<?= e($rate) ?>%)</span></div></div>
                    <div class="pm-card pm-kpi-card"><span class="pm-stat-icon is-success"><?= icon('trending-up', 20) ?></span><div><strong class="pm-kpi-value pm-tabular"><?= e(format_price((string)$bal['total_earnings'])) ?></strong><span class="pm-kpi-label">Total Net Earnings</span></div></div>
                    <div class="pm-card pm-kpi-card"><span class="pm-stat-icon is-success"><?= icon('wallet', 20) ?></span><div><strong class="pm-kpi-value pm-tabular"><?= e(format_price((string)$bal['available_balance'])) ?></strong><span class="pm-kpi-label">Available to Withdraw</span></div></div>
                    <div class="pm-card pm-kpi-card"><span class="pm-stat-icon is-warning"><?= icon('clock', 20) ?></span><div><strong class="pm-kpi-value pm-tabular"><?= e(format_price((string)$bal['pending_withdrawal'])) ?></strong><span class="pm-kpi-label">Pending Withdrawals</span></div></div>
                    <div class="pm-card pm-kpi-card"><span class="pm-stat-icon"><?= icon('check-circle', 20) ?></span><div><strong class="pm-kpi-value pm-tabular"><?= e(format_price((string)$bal['withdrawn_total'])) ?></strong><span class="pm-kpi-label">Total Withdrawn Paid</span></div></div>
                </div>

                <div class="pm-card pm-card-pad" style="display:grid;gap:14px;">
                    <h2 class="pm-h3">Immutable Financial Ledger</h2>
                    <?php if (empty($ledger)): ?>
                        <p class="pm-muted" style="padding:18px 0;">No ledger entries recorded yet.</p>
                    <?php else: ?>
                        <div class="pm-table-wrap">
                            <table class="pm-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Entry Type</th>
                                        <th>Description</th>
                                        <th>Amount</th>
                                        <th>Balance After</th>
                                        <th>Timestamp</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($ledger as $row): ?>
                                        <tr>
                                            <td class="pm-tabular">#<?= (int)$row['id'] ?></td>
                                            <td><?= render_status_badge((string)$row['entry_type']) ?></td>
                                            <td><?= e($row['description']) ?></td>
                                            <td class="pm-tabular" style="font-weight:700;color:<?= $row['direction'] === 'credit' ? 'var(--success)' : 'var(--danger)' ?>;">
                                                <?= $row['direction'] === 'credit' ? '+' : '-' ?><?= e(format_price((string)$row['amount'])) ?>
                                            </td>
                                            <td class="pm-tabular"><?= e(format_price((string)$row['balance_after'])) ?></td>
                                            <td><?= e(format_datetime((string)$row['created_at'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require INCLUDES_PATH . '/layouts/footer.php'; ?>
