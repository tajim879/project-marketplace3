<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$user = require_login();
$orderRef = trim((string)($_GET['order'] ?? ''));
$order = $orderRef !== '' ? OrderRepository::findBuyerOrder((int)$user['id'], $orderRef) : null;

if (!$order) {
    redirect(url('buyer/orders.php'));
}

$isPaid = ($order['status'] === 'paid');
$latestPay = $order['payments'][0] ?? null;

$pageTitle = $isPaid ? 'Order Completed' : 'Payment Submitted';
$pageRobots = 'noindex, nofollow';
$pageStyles = ['checkout.css'];

require INCLUDES_PATH . '/layouts/header.php';
?>

<section class="pm-section">
    <div class="pm-container" style="max-width:740px;">
        <?php require INCLUDES_PATH . '/components/alerts.php'; ?>

        <div class="pm-card pm-card-pad" style="padding:clamp(22px,4vw,38px) clamp(16px,4vw,34px);display:grid;gap:22px;text-align:center;">
            <div class="pm-icon-orb <?= $isPaid ? 'is-success' : '' ?>" style="margin:0 auto;">
                <?= icon($isPaid ? 'check-circle' : 'clock', 30) ?>
            </div>

            <div style="display:grid;gap:6px;">
                <h1 class="pm-h1"><?= $isPaid ? 'Payment Verified — Downloads Ready!' : 'Payment Submitted for Verification' ?></h1>
                <p class="pm-muted" style="max-width:540px;margin:0 auto;">
                    <?php if ($isPaid): ?>
                        Order <strong><?= e($order['order_number']) ?></strong> is paid and active. Download your project archives and license certificates anytime from your Downloads Vault.
                    <?php else: ?>
                        Thank you! We received your transaction details for order <strong><?= e($order['order_number']) ?></strong>. Once our administrator confirms your transfer, your project downloads &amp; license keys unlock automatically.
                    <?php endif; ?>
                </p>
            </div>

            <div style="padding:20px;border-radius:16px;background:var(--bg);border:1px solid var(--border);text-align:left;display:grid;gap:12px;">
                <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
                    <span class="pm-muted" style="font-size:0.82rem;">Order Number</span>
                    <strong style="font-size:0.96rem;"><?= e($order['order_number']) ?></strong>
                </div>
                <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
                    <span class="pm-muted" style="font-size:0.82rem;">Order Status</span>
                    <?= render_status_badge((string)$order['status']) ?>
                </div>
                <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
                    <span class="pm-muted" style="font-size:0.82rem;">Total Amount</span>
                    <strong class="pm-tabular" style="font-size:1.05rem;color:var(--primary);"><?= e(format_price((string)$order['total_amount'])) ?></strong>
                </div>
                <?php if ($latestPay): ?>
                    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
                        <span class="pm-muted" style="font-size:0.82rem;">Payment Wallet</span>
                        <?= payment_brand_badge((string)$latestPay['method_code'], 'sm') ?>
                    </div>
                    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
                        <span class="pm-muted" style="font-size:0.82rem;">Sender &amp; TrxID</span>
                        <span><?= e($latestPay['sender_number']) ?> · <code><?= e($latestPay['transaction_id']) ?></code></span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="pm-btn-row" style="justify-content:center;">
                <?php if ($isPaid): ?>
                    <a href="<?= e(url('buyer/downloads.php')) ?>" class="pm-btn pm-btn-primary pm-btn-lg">
                        <?= icon('download', 18) ?><span>Go to Downloads Vault</span>
                    </a>
                <?php else: ?>
                    <a href="<?= e(url('buyer/order-details.php?order=' . urlencode((string)$order['order_number']))) ?>" class="pm-btn pm-btn-primary pm-btn-lg">
                        <?= icon('eye', 18) ?><span>Track Order Status</span>
                    </a>
                <?php endif; ?>
                <a href="<?= e(url('invoice.php?order=' . urlencode((string)$order['order_number']))) ?>" class="pm-btn pm-btn-secondary pm-btn-lg">
                    <?= icon('receipt', 18) ?><span>View Invoice</span>
                </a>
                <a href="<?= e(url('projects.php')) ?>" class="pm-btn pm-btn-ghost">
                    <span>Explore More</span>
                </a>
            </div>
        </div>
    </div>
</section>

<?php require INCLUDES_PATH . '/layouts/footer.php'; ?>
