<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$pageTitle = 'Sellers & Wallets';
require INCLUDES_PATH . '/layouts/admin-header.php';

$status = trim((string)($_GET['status'] ?? 'all'));
$defaultRate = site_setting('default_commission_rate', '10.00');

$sql = "SELECT u.*, up.store_name, up.payout_method, up.payout_number, up.payout_account_type, up.custom_commission_rate,
               COALESCE(sb.gross_sales, '0.00') AS gross_sales,
               COALESCE(sb.total_commission, '0.00') AS total_commission,
               COALESCE(sb.available_balance, '0.00') AS available_balance,
               COALESCE(sb.withdrawn_total, '0.00') AS withdrawn_total,
               (SELECT COUNT(*) FROM `projects` p WHERE p.seller_id = u.id) AS projects_count
        FROM `users` u
        LEFT JOIN `user_profiles` up ON up.user_id = u.id
        LEFT JOIN `seller_balances` sb ON sb.seller_id = u.id
        WHERE (u.is_seller = 1 OR u.seller_status <> 'none')";
$params = [];
if (in_array($status, ['pending', 'approved', 'rejected', 'suspended'], true)) {
    $sql .= " AND u.seller_status = ?";
    $params[] = $status;
}
$sql .= " ORDER BY CASE WHEN u.seller_status = 'pending' THEN 0 ELSE 1 END, u.id DESC";
$stmt = db()->prepare($sql);
$stmt->execute($params);
$sellers = $stmt->fetchAll();
?>

<div class="pm-admin-header">
    <div>
        <h1>Seller Stores, Verification &amp; Wallets</h1>
        <p>Approve seller onboarding, award verified creator badges, inspect wallet balances, and configure per-seller commission rates (global default: <?= e($defaultRate) ?>%).</p>
    </div>
</div>

<div class="pm-admin-tabs">
    <?php foreach (['all' => 'All Sellers', 'pending' => 'Pending Approval', 'approved' => 'Approved', 'rejected' => 'Rejected', 'suspended' => 'Suspended'] as $sk => $sl): ?>
        <a href="<?= e(url('admin/sellers.php?status=' . $sk)) ?>" class="pm-chip <?= $status === $sk ? 'is-active' : '' ?>"><?= e($sl) ?></a>
    <?php endforeach; ?>
</div>

<div class="pm-card pm-card-pad">
    <?php if (empty($sellers)): ?>
        <div class="pm-empty">
            <div class="pm-icon-orb"><?= icon('store', 26) ?></div>
            <h2 class="pm-h3">No sellers in this view</h2>
            <p class="pm-muted">Creators who sign up as Sellers on the registration page appear here with full wallet tracking.</p>
        </div>
    <?php else: ?>
        <div class="pm-table-wrap">
            <table class="pm-table">
                <thead>
                    <tr>
                        <th>Seller / Store</th>
                        <th>Payout Wallet</th>
                        <th>Projects</th>
                        <th>Gross / Balance</th>
                        <th>Commission %</th>
                        <th>Status</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sellers as $s): ?>
                        <tr>
                            <td>
                                <strong><?= e($s['store_name'] ?: $s['full_name']) ?></strong>
                                <?php if ((int)$s['is_verified_seller']): ?><span class="pm-badge pm-badge-primary" style="margin-left:4px;">Verified</span><?php endif; ?>
                                <small class="pm-muted" style="display:block;">@<?= e($s['username']) ?> · <?= e($s['email']) ?></small>
                            </td>
                            <td>
                                <?php if (!empty($s['payout_method'])): ?>
                                    <?= payment_brand_badge((string)$s['payout_method'], 'sm') ?>
                                    <small class="pm-muted pm-tabular" style="display:block;margin-top:3px;"><?= e((string)$s['payout_number']) ?> (<?= e((string)$s['payout_account_type']) ?>)</small>
                                <?php else: ?>
                                    <span class="pm-muted">Not set</span>
                                <?php endif; ?>
                            </td>
                            <td class="pm-tabular"><?= (int)$s['projects_count'] ?></td>
                            <td class="pm-tabular">
                                <strong><?= e(format_price((string)$s['gross_sales'])) ?></strong>
                                <small style="display:block;color:var(--success);font-weight:700;">Avail: <?= e(format_price((string)$s['available_balance'])) ?></small>
                            </td>
                            <td>
                                <form method="post" action="<?= e(url('actions/admin/users.php')) ?>" class="pm-btn-row">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="custom_commission">
                                    <input type="hidden" name="user_id" value="<?= (int)$s['id'] ?>">
                                    <input type="number" step="0.01" min="0" max="50" name="custom_commission_rate" value="<?= e((string)($s['custom_commission_rate'] ?? '')) ?>" placeholder="<?= e($defaultRate) ?>%" class="pm-input pm-tabular" style="width:82px;padding:6px 8px;">
                                    <button type="submit" class="pm-btn pm-btn-ghost pm-btn-sm">Set</button>
                                </form>
                            </td>
                            <td><?= render_status_badge((string)$s['seller_status']) ?></td>
                            <td style="text-align:right;">
                                <div class="pm-btn-row" style="justify-content:flex-end;">
                                    <?php if ($s['seller_status'] !== 'approved'): ?>
                                        <form method="post" action="<?= e(url('actions/admin/users.php')) ?>">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="seller_decision">
                                            <input type="hidden" name="user_id" value="<?= (int)$s['id'] ?>">
                                            <input type="hidden" name="seller_status" value="approved">
                                            <button type="submit" class="pm-btn pm-btn-primary pm-btn-sm">Approve</button>
                                        </form>
                                    <?php else: ?>
                                        <form method="post" action="<?= e(url('actions/admin/users.php')) ?>">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="seller_decision">
                                            <input type="hidden" name="user_id" value="<?= (int)$s['id'] ?>">
                                            <input type="hidden" name="seller_status" value="toggle_verified">
                                            <button type="submit" class="pm-btn pm-btn-secondary pm-btn-sm"><?= (int)$s['is_verified_seller'] ? 'Unverify' : 'Verify' ?></button>
                                        </form>
                                        <?php if ($s['role'] !== 'admin'): ?>
                                            <form method="post" action="<?= e(url('actions/admin/users.php')) ?>">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="seller_decision">
                                                <input type="hidden" name="user_id" value="<?= (int)$s['id'] ?>">
                                                <input type="hidden" name="seller_status" value="suspended">
                                                <button type="submit" class="pm-btn pm-btn-ghost pm-btn-sm" style="color:var(--danger);">Suspend</button>
                                            </form>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require INCLUDES_PATH . '/layouts/admin-footer.php'; ?>
