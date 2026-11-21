<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$user = require_login();
$orderRef = trim((string)($_GET['order'] ?? ''));
$order = $orderRef !== '' ? OrderRepository::findBuyerOrder((int)$user['id'], $orderRef) : null;

if (!$order) {
    flash('error', 'Order not found.');
    redirect(url('buyer/orders.php'));
}

if (!in_array((string)$order['status'], ['pending_payment', 'rejected'], true)) {
    redirect(url('order-success.php?order=' . urlencode((string)$order['order_number'])));
}

$methods = enabled_payment_methods();
$preferred = strtolower(trim((string)($_GET['method'] ?? '')));
$selectedId = (int)old('payment_method_id', '0');
if ($selectedId <= 0 && !empty($methods)) {
    $selectedId = (int)$methods[0]['id'];
    foreach ($methods as $m) {
        if (strtolower((string)$m['code']) === $preferred) {
            $selectedId = (int)$m['id'];
            break;
        }
    }
}

$latestRejected = null;
foreach (($order['payments'] ?? []) as $py) {
    if ($py['status'] === 'rejected') {
        $latestRejected = $py;
        break;
    }
}

$pageTitle = 'Manual Payment — ' . $order['order_number'];
$pageRobots = 'noindex, nofollow';
$pageStyles = ['checkout.css'];

require INCLUDES_PATH . '/layouts/header.php';
?>

<section class="pm-page-header">
    <div class="pm-container">
        <nav class="pm-breadcrumb" aria-label="Breadcrumb">
            <a href="<?= e(url('buyer/orders.php')) ?>">My Orders</a>
            <?= icon('chevron-right', 14) ?>
            <span><?= e($order['order_number']) ?></span>
            <?= icon('chevron-right', 14) ?>
            <span>Submit Manual Payment</span>
        </nav>
        <div class="pm-page-header-row">
            <div>
                <h1 class="pm-h1">Complete Manual Payment</h1>
                <p class="pm-muted" style="margin-top:4px;">Order <strong><?= e($order['order_number']) ?></strong> · Send exact <strong><?= e(format_price((string)$order['total_amount'])) ?></strong> via bKash, Nagad, or Rocket and submit your Transaction ID below.</p>
            </div>
            <?= render_status_badge((string)$order['status']) ?>
        </div>
    </div>
</section>

<section class="pm-section">
    <div class="pm-container">
        <?php require INCLUDES_PATH . '/components/alerts.php'; ?>

        <?php if ($latestRejected && !empty($latestRejected['rejection_reason'])): ?>
            <div class="pm-alert pm-alert-danger" style="margin-bottom:20px;">
                <?= icon('alert-circle', 18) ?>
                <span><strong>Previous payment rejected:</strong> <?= e($latestRejected['rejection_reason']) ?> — Please verify and submit your valid Transaction ID below.</span>
            </div>
        <?php endif; ?>

        <?php if (empty($methods)): ?>
            <div class="pm-card pm-empty">
                <div class="pm-icon-orb"><?= icon('alert-circle', 28) ?></div>
                <h2 class="pm-h3">No Manual Payment Methods Active</h2>
                <p class="pm-muted">Please contact support while our administrator updates receiving accounts.</p>
            </div>
        <?php else: ?>
            <form method="post" action="<?= e(url('actions/payments/submit.php')) ?>" enctype="multipart/form-data" class="pm-cart-layout" data-submit-lock>
                <?= csrf_field() ?>
                <input type="hidden" name="order_number" value="<?= e($order['order_number']) ?>">

                <div style="display:grid;gap:20px;align-content:start;">
                    <!-- Step 1: Select Wallet -->
                    <div class="pm-card pm-card-pad" style="display:grid;gap:16px;">
                        <h2 class="pm-h3">Step 1 — Select Payment Wallet</h2>
                        <div class="pm-pay-select-grid">
                            <?php foreach ($methods as $m):
                                $code = strtolower((string)$m['code']);
                                $mid = (int)$m['id']; ?>
                                <label class="pm-pay-option" data-method="<?= e($code) ?>">
                                    <input type="radio"
                                           name="payment_method_id"
                                           value="<?= $mid ?>"
                                           data-method-panel="pmMethodPanel<?= $mid ?>"
                                           <?= $selectedId === $mid ? 'checked' : '' ?>
                                           required>
                                    <span class="pm-pay-option-card" style="min-height:112px;padding:18px 14px;">
                                        <span class="pm-pay-option-check"><?= icon('check', 13) ?></span>
                                        <?= payment_brand_svg($code, 48, true) ?>
                                        <span class="pm-pay-option-meta"><?= e(ucfirst((string)$m['account_type'])) ?> Account</span>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>

                        <!-- Dynamic Receiver Account & Step-by-Step Instructions -->
                        <?php foreach ($methods as $m):
                            $mid = (int)$m['id'];
                            $code = strtolower((string)$m['code']);
                            $lines = preg_split('/\\\\n|\r\n|\r|\n/', (string)$m['instructions']) ?: []; ?>
                            <div id="pmMethodPanel<?= $mid ?>"
                                 data-payment-detail
                                 style="display:<?= $selectedId === $mid ? 'grid' : 'none' ?>;gap:14px;padding:20px;border-radius:16px;background:var(--bg);border:1.5px solid var(--border);">
                                <div style="display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;padding-bottom:14px;border-bottom:1px solid var(--border);">
                                    <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
                                        <?= payment_brand_badge($code, 'md') ?>
                                        <div>
                                            <span class="pm-muted" style="font-size:0.75rem;font-weight:700;text-transform:uppercase;display:block;">Official <?= e(ucfirst((string)$m['account_type'])) ?> Number</span>
                                            <strong class="pm-tabular" style="font-size:1.3rem;color:var(--text-primary);letter-spacing:0.02em;"><?= e($m['account_number']) ?></strong>
                                        </div>
                                    </div>
                                    <button type="button" class="pm-btn pm-btn-secondary pm-btn-sm" data-copy="<?= e($m['account_number']) ?>">
                                        <?= icon('copy', 15) ?><span>Copy Number</span>
                                    </button>
                                </div>

                                <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;padding:11px 14px;border-radius:12px;background:var(--surface);border:1px solid var(--border);">
                                    <span style="font-size:0.84rem;font-weight:600;color:var(--text-secondary);">Exact Amount to Send (Reference: <code><?= e($order['order_number']) ?></code>)</span>
                                    <strong class="pm-tabular" style="font-size:1.15rem;color:var(--primary);"><?= e(format_price((string)$order['total_amount'])) ?></strong>
                                </div>

                                <div style="display:grid;gap:6px;font-size:0.85rem;color:var(--text-secondary);line-height:1.55;">
                                    <?php foreach ($lines as $ln):
                                        if (trim($ln) === '') continue; ?>
                                        <div><?= e(trim($ln)) ?></div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Step 2: Enter Transaction Proof -->
                    <div class="pm-card pm-card-pad" style="display:grid;gap:16px;">
                        <h2 class="pm-h3">Step 2 — Submit Payment Verification Proof</h2>

                        <div class="pm-form-grid-2">
                            <div class="pm-field">
                                <label class="pm-label" for="sender_number">Your Sender Mobile Number</label>
                                <input type="tel" id="sender_number" name="sender_number" class="pm-input" value="<?= e(old('sender_number', (string)($user['phone'] ?? ''))) ?>" placeholder="01XXXXXXXXX" required>
                            </div>
                            <div class="pm-field">
                                <label class="pm-label" for="transaction_id">Transaction ID (TrxID)</label>
                                <input type="text" id="transaction_id" name="transaction_id" class="pm-input" value="<?= e(old('transaction_id')) ?>" placeholder="e.g. BK94X72M1Q" style="text-transform:uppercase;font-weight:700;letter-spacing:0.04em;" required>
                            </div>
                        </div>

                        <div class="pm-form-grid-2">
                            <div class="pm-field">
                                <label class="pm-label" for="paid_amount">Exact Amount Paid (BDT ৳)</label>
                                <input type="number" step="0.01" id="paid_amount" name="paid_amount" class="pm-input pm-tabular" value="<?= e(number_format((float)$order['total_amount'], 2, '.', '')) ?>" readonly required>
                            </div>
                            <div class="pm-field">
                                <label class="pm-label" for="payment_proof">Payment Screenshot (JPG / PNG / WebP)</label>
                                <input type="file" id="payment_proof" name="payment_proof" class="pm-input" accept="image/png,image/jpeg,image/webp">
                                <small class="pm-field-hint">Stored privately — speeds up verification (max <?= (int)site_setting('max_proof_mb', '5') ?> MB).</small>
                            </div>
                        </div>

                        <div class="pm-field">
                            <label class="pm-label" for="buyer_note">Additional Note (Optional)</label>
                            <textarea id="buyer_note" name="buyer_note" class="pm-textarea" rows="2" placeholder="Last 4 digits, transfer timestamp, or any note for our review team..."><?= e(old('buyer_note')) ?></textarea>
                        </div>

                        <button type="submit" class="pm-btn pm-btn-success pm-btn-lg pm-btn-block">
                            <?= icon('check-circle', 18) ?><span>Submit Payment for Verification</span>
                        </button>
                    </div>
                </div>

                <!-- Order Recap Sidebar -->
                <aside class="pm-card pm-summary-card">
                    <h2 class="pm-h3">Order Recap</h2>
                    <div style="display:grid;gap:10px;">
                        <?php foreach ($order['items'] as $it): ?>
                            <div style="display:flex;justify-content:space-between;gap:10px;font-size:0.84rem;">
                                <span style="color:var(--text-secondary);"><?= e($it['project_title']) ?> <small class="pm-muted">(<?= e(ucfirst((string)$it['license_type'])) ?>)</small></span>
                                <strong class="pm-tabular"><?= e(format_price((string)$it['unit_price'])) ?></strong>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="pm-summary-rows">
                        <div class="pm-summary-row"><span>Subtotal</span><span class="pm-tabular"><?= e(format_price((string)$order['subtotal_amount'])) ?></span></div>
                        <?php if (bccomp((string)$order['discount_amount'], '0.00', 2) > 0): ?>
                            <div class="pm-summary-row is-discount"><span>Discount</span><span class="pm-tabular">-<?= e(format_price((string)$order['discount_amount'])) ?></span></div>
                        <?php endif; ?>
                        <div class="pm-summary-row is-total"><span>Total Payable</span><span class="pm-tabular"><?= e(format_price((string)$order['total_amount'])) ?></span></div>
                    </div>
                    <a href="<?= e(url('buyer/order-details.php?order=' . urlencode((string)$order['order_number']))) ?>" class="pm-btn pm-btn-ghost pm-btn-sm pm-btn-block">
                        <?= icon('receipt', 15) ?><span>View Full Order Details</span>
                    </a>
                </aside>
            </form>
        <?php endif; ?>
    </div>
</section>

<?php require INCLUDES_PATH . '/layouts/footer.php'; ?>
