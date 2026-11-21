<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$adminActor = current_admin();
$user = $adminActor ?: require_login();
$orderRef = trim((string)($_GET['order'] ?? ''));

$order = null;
if ($orderRef !== '') {
    $order = $adminActor
        ? OrderRepository::findAnyOrder($orderRef)
        : OrderRepository::findBuyerOrder((int)$user['id'], $orderRef);
}
if (!$order) {
    flash('error', 'Invoice not found.');
    redirect(url($adminActor ? 'admin/orders.php' : 'buyer/orders.php'));
}

$pageTitle = 'Invoice ' . $order['order_number'];
$pageRobots = 'noindex, nofollow';
$pageStyles = ['checkout.css'];

require INCLUDES_PATH . '/layouts/header.php';
?>

<div class="pm-container">
    <div class="pm-btn-row pm-no-print" style="max-width:820px;margin:26px auto -16px;justify-content:space-between;">
        <a href="<?= e(url(($adminActor ? 'admin/orders.php' : 'buyer/order-details.php?order=' . urlencode((string)$order['order_number'])))) ?>" class="pm-btn pm-btn-secondary pm-btn-sm">
            <?= icon('arrow-left', 15) ?><span>Back to Order</span>
        </a>
        <button type="button" onclick="window.print()" class="pm-btn pm-btn-primary pm-btn-sm">
            <?= icon('printer', 15) ?><span>Print / Save PDF</span>
        </button>
    </div>

    <section class="pm-invoice-sheet">
        <div class="pm-invoice-head">
            <div>
                <div class="pm-brand"><?= brand_logo(site_setting('site_name', 'CodeVault'), true, 'Official Purchase Invoice') ?></div>
                <p class="pm-muted" style="font-size:0.8rem;margin-top:10px;max-width:300px;">
                    <?= e(site_setting('contact_address', '')) ?><br>
                    <?= e(site_setting('contact_email', '')) ?>
                </p>
            </div>
            <div style="text-align:right;display:grid;gap:6px;justify-items:end;">
                <?= render_status_badge((string)$order['status']) ?>
                <strong class="pm-h3" style="margin-top:4px;"><?= e($order['order_number']) ?></strong>
                <small class="pm-muted">Issued: <?= e(format_date((string)$order['created_at'], 'M j, Y · g:i A')) ?></small>
                <?php if (!empty($order['paid_at'])): ?>
                    <small class="pm-muted">Verified Paid: <?= e(format_date((string)$order['paid_at'], 'M j, Y · g:i A')) ?></small>
                <?php endif; ?>
            </div>
        </div>

        <div class="pm-invoice-parties">
            <div>
                <span class="pm-muted" style="font-size:0.74rem;text-transform:uppercase;font-weight:700;letter-spacing:0.05em;">Billed To</span>
                <strong style="display:block;margin-top:4px;"><?= e($order['buyer_name']) ?></strong>
                <span style="color:var(--text-secondary);display:block;"><?= e($order['buyer_email']) ?></span>
                <?php if (!empty($order['buyer_phone'])): ?>
                    <span style="color:var(--text-secondary);display:block;"><?= e($order['buyer_phone']) ?></span>
                <?php endif; ?>
            </div>
            <div>
                <span class="pm-muted" style="font-size:0.74rem;text-transform:uppercase;font-weight:700;letter-spacing:0.05em;">Payment Verification</span>
                <?php if (!empty($order['payments'])):
                    $p = $order['payments'][0]; ?>
                    <strong style="display:block;margin-top:4px;"><?= e($p['method_name']) ?> Manual Transfer</strong>
                    <span style="color:var(--text-secondary);display:block;">TrxID: <code><?= e($p['transaction_id']) ?></code></span>
                    <span style="color:var(--text-secondary);display:block;">Sender: <?= e($p['sender_number']) ?></span>
                <?php elseif (bccomp((string)$order['total_amount'], '0.00', 2) === 0): ?>
                    <strong style="display:block;margin-top:4px;">Community Free License</strong>
                    <span style="color:var(--text-secondary);">Zero-cost verified issuance</span>
                <?php else: ?>
                    <strong style="display:block;margin-top:4px;">Awaiting Manual Payment</strong>
                <?php endif; ?>
            </div>
        </div>

        <div class="pm-table-wrap" style="box-shadow:none;">
            <table class="pm-table">
                <thead>
                    <tr>
                        <th>Digital Project &amp; License</th>
                        <th>Author</th>
                        <th style="text-align:right;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($order['items'] as $it): ?>
                        <tr>
                            <td>
                                <strong style="color:var(--text-primary);display:block;"><?= e($it['project_title']) ?></strong>
                                <small class="pm-muted"><?= e(license_label((string)$it['license_type'])) ?><?php if (!empty($it['license_key']) && $order['status'] === 'paid'): ?> · Key: <code><?= e($it['license_key']) ?></code><?php endif; ?></small>
                            </td>
                            <td><?= e($it['seller_name']) ?></td>
                            <td style="text-align:right;font-weight:700;color:var(--text-primary);" class="pm-tabular"><?= e(format_price((string)$it['unit_price'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div style="max-width:300px;margin-left:auto;width:100%;" class="pm-summary-rows">
            <div class="pm-summary-row"><span>Subtotal</span><strong class="pm-tabular"><?= e(format_price((string)$order['subtotal_amount'])) ?></strong></div>
            <?php if (bccomp((string)$order['discount_amount'], '0.00', 2) > 0): ?>
                <div class="pm-summary-row is-discount"><span>Discount <?= $order['coupon_code'] ? '(' . e($order['coupon_code']) . ')' : '' ?></span><span class="pm-tabular">-<?= e(format_price((string)$order['discount_amount'])) ?></span></div>
            <?php endif; ?>
            <div class="pm-summary-row is-total"><span>Total (BDT)</span><span class="pm-tabular"><?= e(format_price((string)$order['total_amount'])) ?></span></div>
        </div>
    </section>
</div>

<?php require INCLUDES_PATH . '/layouts/footer.php'; ?>
