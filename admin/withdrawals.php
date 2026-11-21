<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$pageTitle = 'Seller Withdrawals';
require INCLUDES_PATH . '/layouts/admin-header.php';

$status = trim((string)($_GET['status'] ?? 'queue'));
$sql = "SELECT w.*, u.full_name, u.username, up.store_name, sb.available_balance, sb.pending_withdrawal
        FROM `withdrawals` w
        INNER JOIN `users` u ON u.id = w.seller_id
        LEFT JOIN `user_profiles` up ON up.user_id = u.id
        LEFT JOIN `seller_balances` sb ON sb.seller_id = u.id
        WHERE 1=1";
$params = [];
if ($status === 'queue') {
    $sql .= " AND w.status IN ('pending','approved')";
} elseif (in_array($status, ['pending', 'approved', 'paid', 'rejected'], true)) {
    $sql .= " AND w.status = ?";
    $params[] = $status;
}
$sql .= " ORDER BY CASE WHEN w.status IN ('pending','approved') THEN 0 ELSE 1 END, w.id DESC";
$stmt = db()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();
?>

<div class="pm-admin-header">
    <div>
        <h1>Seller Payouts &amp; Withdrawals (bKash / Nagad / Rocket)</h1>
        <p>Approve requests, disburse funds to the seller's wallet, record payout TrxID, or reject (automatically releases held funds back to seller balance).</p>
    </div>
</div>

<div class="pm-admin-tabs">
    <?php foreach (['queue' => 'Pending Queue', 'all' => 'All', 'pending' => 'Pending', 'approved' => 'Approved', 'paid' => 'Paid Out', 'rejected' => 'Rejected'] as $sk => $sl): ?>
        <a href="<?= e(url('admin/withdrawals.php?status=' . $sk)) ?>" class="pm-chip <?= $status === $sk ? 'is-active' : '' ?>"><?= e($sl) ?></a>
    <?php endforeach; ?>
</div>

<div class="pm-card pm-card-pad">
    <?php if (empty($rows)): ?>
        <div class="pm-empty">
            <div class="pm-icon-orb"><?= icon('wallet', 26) ?></div>
            <h2 class="pm-h3">No withdrawal requests in this queue</h2>
            <p class="pm-muted">When sellers submit bKash, Nagad, or Rocket withdrawal requests, they appear here for processing.</p>
        </div>
    <?php else: ?>
        <div class="pm-table-wrap">
            <table class="pm-table">
                <thead>
                    <tr>
                        <th>Request #</th>
                        <th>Seller</th>
                        <th>Wallet &amp; Number</th>
                        <th>Amount</th>
                        <th>Status &amp; Ref</th>
                        <th>Requested</th>
                        <th style="text-align:right;">Process</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $w): ?>
                        <tr>
                            <td><code><?= e($w['request_number']) ?></code></td>
                            <td>
                                <strong><?= e($w['store_name'] ?: $w['full_name']) ?></strong>
                                <small class="pm-muted pm-tabular" style="display:block;">Held: <?= e(format_price((string)($w['pending_withdrawal'] ?? '0.00'))) ?></small>
                            </td>
                            <td>
                                <?= payment_brand_badge((string)$w['method'], 'md') ?>
                                <strong class="pm-tabular" style="display:block;margin-top:4px;"><?= e($w['account_number']) ?></strong>
                            </td>
                            <td class="pm-tabular" style="font-size:1rem;font-weight:800;"><?= e(format_price((string)$w['amount'])) ?></td>
                            <td>
                                <?= render_status_badge((string)$w['status']) ?>
                                <?php if (!empty($w['payment_reference'])): ?><code style="display:block;font-size:0.76rem;margin-top:3px;">Ref: <?= e($w['payment_reference']) ?></code><?php endif; ?>
                                <?php if (!empty($w['admin_note'])): ?><small class="pm-muted" style="display:block;"><?= e($w['admin_note']) ?></small><?php endif; ?>
                            </td>
                            <td><?= e(format_datetime((string)$w['created_at'])) ?></td>
                            <td style="text-align:right;">
                                <?php if (in_array((string)$w['status'], ['pending', 'approved'], true)): ?>
                                    <div style="display:flex;gap:6px;justify-content:flex-end;flex-wrap:wrap;align-items:flex-start;">
                                        <?php if ($w['status'] === 'pending'): ?>
                                            <form method="post" action="<?= e(url('actions/admin/withdrawals.php')) ?>">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="approve">
                                                <input type="hidden" name="withdrawal_id" value="<?= (int)$w['id'] ?>">
                                                <button type="submit" class="pm-btn pm-btn-secondary pm-btn-sm">Approve</button>
                                            </form>
                                        <?php endif; ?>
                                        <details class="pm-inline-action">
                                            <summary class="pm-btn pm-btn-primary pm-btn-sm">Mark Paid</summary>
                                            <form method="post" action="<?= e(url('actions/admin/withdrawals.php')) ?>" class="pm-inline-pop">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="pay">
                                                <input type="hidden" name="withdrawal_id" value="<?= (int)$w['id'] ?>">
                                                <input type="text" name="payment_reference" class="pm-input" placeholder="Sent bKash/Nagad/Rocket TrxID" required>
                                                <input type="text" name="admin_note" class="pm-input" placeholder="Optional note">
                                                <button type="submit" class="pm-btn pm-btn-primary pm-btn-sm">Confirm Paid</button>
                                            </form>
                                        </details>
                                        <details class="pm-inline-action">
                                            <summary class="pm-btn pm-btn-ghost pm-btn-sm" style="color:var(--danger);">Reject</summary>
                                            <form method="post" action="<?= e(url('actions/admin/withdrawals.php')) ?>" class="pm-inline-pop">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="reject">
                                                <input type="hidden" name="withdrawal_id" value="<?= (int)$w['id'] ?>">
                                                <input type="text" name="rejection_reason" class="pm-input" placeholder="Reason (balance auto-returned)" required>
                                                <button type="submit" class="pm-btn pm-btn-primary pm-btn-sm" style="background:var(--danger);">Reject &amp; Refund Wallet</button>
                                            </form>
                                        </details>
                                    </div>
                                <?php else: ?>
                                    <span class="pm-muted" style="font-size:0.8rem;">Finalized</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require INCLUDES_PATH . '/layouts/admin-footer.php'; ?>
