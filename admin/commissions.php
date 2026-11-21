<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$pageTitle = 'Commissions & Financial Ledger';
require INCLUDES_PATH . '/layouts/admin-header.php';

$pdo = db();
$tab = trim((string)($_GET['tab'] ?? 'commissions'));

$totals = $pdo->query(
    "SELECT
        COALESCE(printf('%.2f', SUM(CASE WHEN `status`='credited' THEN CAST(`gross_amount` AS REAL) ELSE 0 END)), '0.00') AS gross,
        COALESCE(printf('%.2f', SUM(CASE WHEN `status`='credited' THEN CAST(`admin_commission` AS REAL) ELSE 0 END)), '0.00') AS admin_comm,
        COALESCE(printf('%.2f', SUM(CASE WHEN `status`='credited' THEN CAST(`seller_earning` AS REAL) ELSE 0 END)), '0.00') AS seller_net,
        COALESCE(printf('%.2f', SUM(CASE WHEN `status`='reversed' THEN CAST(`gross_amount` AS REAL) ELSE 0 END)), '0.00') AS reversed_gross
     FROM `commissions`"
)->fetch();

$commissions = $pdo->query(
    "SELECT c.*, oi.project_title, o.order_number, u.username AS seller_username, up.store_name
     FROM `commissions` c
     INNER JOIN `order_items` oi ON oi.id = c.order_item_id
     INNER JOIN `orders` o ON o.id = oi.order_id
     INNER JOIN `users` u ON u.id = c.seller_id
     LEFT JOIN `user_profiles` up ON up.user_id = u.id
     ORDER BY c.id DESC LIMIT 120"
)->fetchAll();

$ledger = $pdo->query(
    "SELECT fl.*, u.username, up.store_name
     FROM `financial_ledger` fl
     INNER JOIN `users` u ON u.id = fl.user_id
     LEFT JOIN `user_profiles` up ON up.user_id = u.id
     ORDER BY fl.id DESC LIMIT 150"
)->fetchAll();
?>

<div class="pm-admin-header">
    <div>
        <h1>Commission Engine &amp; Immutable Financial Ledger</h1>
        <p>Configure default commission split, withdrawal thresholds, and audit every BDT transaction with mathematical precision.</p>
    </div>
</div>

<div class="pm-admin-kpi-grid">
    <div class="pm-card pm-admin-kpi"><div><strong class="pm-admin-kpi-val"><?= e(format_price((string)$totals['gross'])) ?></strong><span class="pm-admin-kpi-lbl">Verified Gross Volume</span></div><span class="pm-stat-icon"><?= icon('shopping-bag', 20) ?></span></div>
    <div class="pm-card pm-admin-kpi"><div><strong class="pm-admin-kpi-val"><?= e(format_price((string)$totals['admin_comm'])) ?></strong><span class="pm-admin-kpi-lbl">Admin Commission Earned</span></div><span class="pm-stat-icon is-success"><?= icon('trending-up', 20) ?></span></div>
    <div class="pm-card pm-admin-kpi"><div><strong class="pm-admin-kpi-val"><?= e(format_price((string)$totals['seller_net'])) ?></strong><span class="pm-admin-kpi-lbl">Seller Net Credited</span></div><span class="pm-stat-icon is-success"><?= icon('wallet', 20) ?></span></div>
    <div class="pm-card pm-admin-kpi"><div><strong class="pm-admin-kpi-val"><?= e(format_price((string)$totals['reversed_gross'])) ?></strong><span class="pm-admin-kpi-lbl">Refunded / Reversed Volume</span></div><span class="pm-stat-icon is-warning"><?= icon('alert-circle', 20) ?></span></div>
</div>

<!-- Policy Settings Form -->
<form method="post" action="<?= e(url('actions/admin/commissions.php')) ?>" class="pm-card pm-card-pad" style="display:grid;gap:16px;">
    <?= csrf_field() ?>
    <h2 class="pm-h3">Global Commission &amp; Payout Rules</h2>
    <div class="pm-admin-kpi-grid">
        <div class="pm-field">
            <label class="pm-label">Default Commission Rate (%)</label>
            <input type="number" step="0.01" min="0" max="50" name="default_commission_rate" class="pm-input pm-tabular" value="<?= e(site_setting('default_commission_rate', '10.00')) ?>" required>
        </div>
        <div class="pm-field">
            <label class="pm-label">Minimum Withdrawal (৳ BDT)</label>
            <input type="number" step="0.01" min="50" name="min_withdrawal_amount" class="pm-input pm-tabular" value="<?= e(site_setting('min_withdrawal_amount', '500.00')) ?>" required>
        </div>
        <div class="pm-field">
            <label class="pm-label">Maximum Withdrawal (৳ BDT)</label>
            <input type="number" step="0.01" min="500" name="max_withdrawal_amount" class="pm-input pm-tabular" value="<?= e(site_setting('max_withdrawal_amount', '100000.00')) ?>" required>
        </div>
        <div class="pm-field">
            <label class="pm-label">Default Download Limit / License</label>
            <input type="number" min="1" max="1000" name="default_download_limit" class="pm-input pm-tabular" value="<?= e(site_setting('default_download_limit', '25')) ?>" required>
        </div>
    </div>
    <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;">
        <div class="pm-btn-row" style="gap:20px;flex-wrap:wrap;">
            <label style="display:inline-flex;align-items:center;gap:8px;font-size:0.85rem;">
                <input type="checkbox" name="seller_auto_approve" value="1" <?= site_setting('seller_auto_approve', '1') === '1' ? 'checked' : '' ?>>
                <span>Auto-approve new Seller registrations</span>
            </label>
            <label style="display:inline-flex;align-items:center;gap:8px;font-size:0.85rem;">
                <input type="checkbox" name="project_auto_approve" value="1" <?= site_setting('project_auto_approve', '0') === '1' ? 'checked' : '' ?>>
                <span>Auto-approve Seller project uploads (skip manual review)</span>
            </label>
        </div>
        <button type="submit" class="pm-btn pm-btn-primary"><?= icon('check', 16) ?><span>Save Commission Rules</span></button>
    </div>
</form>

<div class="pm-admin-tabs">
    <a href="<?= e(url('admin/commissions.php?tab=commissions')) ?>" class="pm-chip <?= $tab === 'commissions' ? 'is-active' : '' ?>">Sale Commission Splits (<?= count($commissions) ?>)</a>
    <a href="<?= e(url('admin/commissions.php?tab=ledger')) ?>" class="pm-chip <?= $tab === 'ledger' ? 'is-active' : '' ?>">Immutable Financial Ledger (<?= count($ledger) ?>)</a>
</div>

<div class="pm-card pm-card-pad">
    <?php if ($tab === 'ledger'): ?>
        <?php if (empty($ledger)): ?>
            <p class="pm-muted" style="padding:20px 0;">No ledger entries recorded yet.</p>
        <?php else: ?>
            <div class="pm-table-wrap">
                <table class="pm-table">
                    <thead><tr><th>Reference</th><th>Seller</th><th>Type</th><th>Description</th><th>Amount</th><th>Balance After</th><th>Time</th></tr></thead>
                    <tbody>
                        <?php foreach ($ledger as $row): ?>
                            <tr>
                                <td><code style="font-size:0.76rem;"><?= e($row['reference_code']) ?></code></td>
                                <td><?= e($row['store_name'] ?: '@' . $row['username']) ?></td>
                                <td><?= render_status_badge((string)$row['entry_type']) ?></td>
                                <td style="font-size:0.83rem;"><?= e($row['description']) ?></td>
                                <td class="pm-tabular" style="font-weight:700;color:<?= $row['direction'] === 'credit' ? 'var(--success)' : 'var(--danger)' ?>;">
                                    <?= $row['direction'] === 'credit' ? '+' : '-' ?><?= e(format_price((string)$row['amount'])) ?>
                                </td>
                                <td class="pm-tabular"><?= e(format_price((string)$row['balance_after'])) ?></td>
                                <td style="font-size:0.8rem;"><?= e(format_datetime((string)$row['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <?php if (empty($commissions)): ?>
            <p class="pm-muted" style="padding:20px 0;">No commission splits recorded yet. Approve a paid order to generate commission &amp; seller credits.</p>
        <?php else: ?>
            <div class="pm-table-wrap">
                <table class="pm-table">
                    <thead><tr><th>Order</th><th>Project</th><th>Seller</th><th>Gross</th><th>Rate</th><th>Admin Fee</th><th>Seller Net</th><th>State</th></tr></thead>
                    <tbody>
                        <?php foreach ($commissions as $c): ?>
                            <tr>
                                <td><code><?= e($c['order_number']) ?></code></td>
                                <td><strong><?= e($c['project_title']) ?></strong></td>
                                <td><?= e($c['store_name'] ?: '@' . $c['seller_username']) ?></td>
                                <td class="pm-tabular"><?= e(format_price((string)$c['gross_amount'])) ?></td>
                                <td class="pm-tabular"><?= e($c['commission_rate']) ?>%</td>
                                <td class="pm-tabular" style="font-weight:700;color:var(--primary);"><?= e(format_price((string)$c['admin_commission'])) ?></td>
                                <td class="pm-tabular" style="font-weight:700;color:var(--success);"><?= e(format_price((string)$c['seller_earning'])) ?></td>
                                <td><?= render_status_badge((string)$c['status']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require INCLUDES_PATH . '/layouts/admin-footer.php'; ?>
