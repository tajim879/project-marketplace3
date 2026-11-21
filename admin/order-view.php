<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$orderKey = trim((string)($_GET['order'] ?? ''));
$order = $orderKey !== '' ? OrderRepository::findAnyOrder($orderKey) : null;
if (!$order) {
    require_admin();
    flash('error', 'Order not found.');
    redirect(url('admin/orders.php'));
}

$pageTitle = 'Order ' . $order['order_number'];
require INCLUDES_PATH . '/layouts/admin-header.php';
$selfPath = 'admin/order-view.php?order=' . urlencode((string)$order['order_number']);
?>

<div class="pm-admin-header">
    <div>
        <h1>Order <?= e($order['order_number']) ?> <?= render_status_badge((string)$order['status']) ?></h1>
        <p>Buyer: <strong><?= e($order['buyer_name']) ?></strong> (<?= e($order['buyer_email']) ?><?= !empty($order['buyer_phone']) ? ' · ' . e($order['buyer_phone']) : '' ?>) · Placed <?= e(format_datetime((string)$order['created_at'])) ?></p>
    </div>
    <div class="pm-btn-row">
        <a href="<?= e(url('invoice.php?order=' . urlencode((string)$order['order_number']))) ?>" target="_blank" class="pm-btn pm-btn-secondary"><?= icon('receipt', 16) ?><span>Printable Invoice</span></a>
        <a href="<?= e(url('admin/orders.php')) ?>" class="pm-btn pm-btn-ghost">Back to Orders</a>
    </div>
</div>

<!-- Line Items & Commission Split -->
<div class="pm-card pm-card-pad" style="display:grid;gap:14px;">
    <h2 class="pm-h3">Ordered Projects, Licenses &amp; Commission Breakdown</h2>
    <div class="pm-table-wrap">
        <table class="pm-table">
            <thead>
                <tr>
                    <th>Project</th>
                    <th>License</th>
                    <th>Final Price</th>
                    <th>Admin Commission</th>
                    <th>Seller Net</th>
                    <th>Downloads</th>
                    <th>Item Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($order['items'] as $it): ?>
                    <tr>
                        <td>
                            <strong><?= e($it['project_title']) ?></strong>
                            <small class="pm-muted" style="display:block;">Seller: <?= e((string)($it['seller_store'] ?? $it['store_name'] ?? $it['seller_username'] ?? $it['seller_name'] ?? '')) ?></small>
                        </td>
                        <td>
                            <span><?= e(ucfirst((string)$it['license_type'])) ?></span>
                            <?php if (!empty($it['license_key'])): ?><code style="display:block;font-size:0.74rem;"><?= e($it['license_key']) ?></code><?php endif; ?>
                        </td>
                        <td class="pm-tabular" style="font-weight:700;"><?= e(format_price((string)$it['final_price'])) ?></td>
                        <td class="pm-tabular"><?= e(format_price((string)$it['commission_amount'])) ?> (<?= e($it['commission_rate']) ?>%)</td>
                        <td class="pm-tabular" style="color:var(--success);font-weight:700;"><?= e(format_price((string)$it['seller_earning'])) ?></td>
                        <td class="pm-tabular"><?= (int)$it['download_count'] ?> / <?= $it['download_limit'] !== null ? (int)$it['download_limit'] : '∞' ?></td>
                        <td><?= render_status_badge((string)$it['status']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div style="display:flex;justify-content:flex-end;gap:24px;flex-wrap:wrap;padding-top:10px;border-top:1px solid var(--border);">
        <span class="pm-muted">Subtotal: <strong class="pm-tabular"><?= e(format_price((string)$order['subtotal_amount'])) ?></strong></span>
        <?php if (bccomp((string)$order['discount_amount'], '0.00', 2) > 0): ?>
            <span style="color:var(--success);">Discount<?= !empty($order['coupon_code']) ? ' (' . e($order['coupon_code']) . ')' : '' ?>: <strong class="pm-tabular">-<?= e(format_price((string)$order['discount_amount'])) ?></strong></span>
        <?php endif; ?>
        <span style="font-size:1.05rem;">Grand Total: <strong class="pm-tabular" style="color:var(--primary);"><?= e(format_price((string)$order['total_amount'])) ?></strong></span>
    </div>
</div>

<!-- Payment Submissions -->
<div class="pm-admin-split">
    <div class="pm-card pm-card-pad" style="display:grid;gap:14px;">
        <h2 class="pm-h3">Manual Payment Submissions</h2>
        <?php if (empty($order['payments'])): ?>
            <p class="pm-muted">No manual payment proof submitted for this order yet.</p>
        <?php else: ?>
            <?php foreach ($order['payments'] as $pay): ?>
                <div style="padding:16px;border-radius:14px;background:var(--bg);border:1px solid var(--border);display:grid;gap:12px;">
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;">
                        <?= payment_brand_badge((string)$pay['method_code'], 'md') ?>
                        <?= render_status_badge((string)$pay['status']) ?>
                    </div>
                    <div class="pm-form-grid-2" style="font-size:0.86rem;">
                        <div><span class="pm-muted">Transaction ID:</span> <code><?= e($pay['transaction_id']) ?></code></div>
                        <div><span class="pm-muted">Sender Wallet:</span> <strong class="pm-tabular"><?= e($pay['sender_number']) ?></strong></div>
                        <div><span class="pm-muted">Submitted Amount:</span> <strong class="pm-tabular"><?= e(format_price((string)$pay['amount'])) ?></strong></div>
                        <div><span class="pm-muted">Submitted At:</span> <?= e(format_datetime((string)$pay['submitted_at'])) ?></div>
                    </div>
                    <?php if (!empty($pay['proof_id'])): ?>
                        <div>
                            <a href="<?= e(url('payment-proof.php?id=' . (int)$pay['proof_id'])) ?>" target="_blank" rel="noopener">
                                <img src="<?= e(url('payment-proof.php?id=' . (int)$pay['proof_id'])) ?>" alt="Payment Screenshot" style="max-height:220px;border-radius:10px;border:1px solid var(--border);">
                            </a>
                        </div>
                    <?php endif; ?>
                    <?php if (in_array((string)$pay['status'], ['submitted', 'under_verification'], true)): ?>
                        <div class="pm-btn-row" style="flex-wrap:wrap;">
                            <form method="post" action="<?= e(url('actions/admin/payments.php')) ?>" class="pm-btn-row">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="approve">
                                <input type="hidden" name="payment_id" value="<?= (int)$pay['id'] ?>">
                                <input type="hidden" name="return_to" value="<?= e($selfPath) ?>">
                                <input type="text" name="admin_note" class="pm-input" placeholder="Optional verification note" style="width:200px;">
                                <button type="submit" class="pm-btn pm-btn-primary pm-btn-sm"><?= icon('check', 14) ?><span>Approve &amp; Unlock</span></button>
                            </form>
                            <form method="post" action="<?= e(url('actions/admin/payments.php')) ?>" class="pm-btn-row">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="reject">
                                <input type="hidden" name="payment_id" value="<?= (int)$pay['id'] ?>">
                                <input type="hidden" name="return_to" value="<?= e($selfPath) ?>">
                                <input type="text" name="rejection_reason" class="pm-input" placeholder="Rejection reason" style="width:190px;" required>
                                <button type="submit" class="pm-btn pm-btn-secondary pm-btn-sm" style="color:var(--danger);">Reject</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div style="display:grid;gap:18px;">
        <?php if (!in_array((string)$order['status'], ['cancelled', 'refunded'], true)): ?>
            <form method="post" action="<?= e(url('actions/admin/payments.php')) ?>" class="pm-card pm-card-pad" style="display:grid;gap:12px;">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="cancel_or_refund">
                <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
                <input type="hidden" name="return_to" value="<?= e($selfPath) ?>">
                <h2 class="pm-h3"><?= $order['status'] === 'paid' ? 'Issue Full Refund & Revoke License' : 'Cancel Unpaid Order' ?></h2>
                <p class="pm-muted" style="font-size:0.83rem;">
                    <?= $order['status'] === 'paid'
                        ? 'Refunding atomically revokes download access, reverses platform commission, deducts seller balance, and records a refund entry in the immutable ledger.'
                        : 'Cancelling closes this unpaid order and logs the audit reason.' ?>
                </p>
                <input type="text" name="reason" class="pm-input" placeholder="Audit reason for cancellation / refund" required>
                <button type="submit" class="pm-btn pm-btn-secondary" style="color:var(--danger);" onclick="return confirm('Confirm order cancellation/refund?');">
                    <?= $order['status'] === 'paid' ? 'Refund Order & Reverse Ledger' : 'Cancel Order' ?>
                </button>
            </form>
        <?php endif; ?>

        <div class="pm-card pm-card-pad" style="display:grid;gap:10px;">
            <h2 class="pm-h3">Status Audit Trail</h2>
            <?php
            $hRows = $order['history'] ?? null;
            if (!is_array($hRows)) {
                $hSt = db()->prepare("SELECT * FROM `order_status_history` WHERE `order_id` = ? ORDER BY `id` DESC");
                $hSt->execute([(int)$order['id']]);
                $hRows = $hSt->fetchAll();
            }
            foreach ($hRows as $h): ?>
                <div style="padding:8px 0;border-bottom:1px solid var(--border);font-size:0.82rem;">
                    <?= render_status_badge((string)$h['new_status']) ?>
                    <span class="pm-muted" style="margin-left:6px;"><?= e(format_datetime((string)$h['created_at'])) ?></span>
                    <?php if (!empty($h['note'])): ?><p class="pm-muted" style="margin-top:4px;"><?= e($h['note']) ?></p><?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php require INCLUDES_PATH . '/layouts/admin-footer.php'; ?>
