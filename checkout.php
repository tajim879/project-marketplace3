<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$user = require_login();
$summary = OrderService::getCartSummary();

if (empty($summary['items'])) {
    flash('info', 'Your cart is empty. Add a project to proceed to checkout.');
    redirect(url('projects.php'));
}

$methods = enabled_payment_methods();
$defaultCode = !empty($methods) ? strtolower((string)$methods[0]['code']) : 'bkash';

$pageTitle = 'Secure Checkout';
$pageRobots = 'noindex, nofollow';
$pageStyles = ['checkout.css'];

require INCLUDES_PATH . '/layouts/header.php';
?>

<section class="pm-page-header">
    <div class="pm-container">
        <nav class="pm-breadcrumb" aria-label="Breadcrumb">
            <a href="<?= e(url('index.php')) ?>"><?= icon('home', 14) ?><span>Home</span></a>
            <?= icon('chevron-right', 14) ?>
            <a href="<?= e(url('cart.php')) ?>">Cart</a>
            <?= icon('chevron-right', 14) ?>
            <span>Checkout</span>
        </nav>
        <div class="pm-page-header-row">
            <div>
                <h1 class="pm-h1">Checkout &amp; Payment Selection</h1>
                <p class="pm-muted" style="margin-top:4px;">Review your project licenses, select your preferred manual payment wallet, and create your order.</p>
            </div>
            <span class="pm-badge pm-badge-primary"><?= icon('shield-check', 14) ?><span>Verified BDT Checkout</span></span>
        </div>
    </div>
</section>

<section class="pm-section">
    <div class="pm-container">
        <?php require INCLUDES_PATH . '/components/alerts.php'; ?>

        <form method="post" action="<?= e(url('actions/orders/create.php')) ?>" class="pm-cart-layout" data-submit-lock>
            <?= csrf_field() ?>

            <div style="display:grid;gap:20px;align-content:start;">
                <!-- Buyer Account Summary -->
                <div class="pm-card pm-card-pad" style="display:grid;gap:14px;">
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;">
                        <h2 class="pm-h3">1. Licensee Information</h2>
                        <span class="pm-badge pm-badge-success"><?= icon('user-check', 13) ?><span>Authenticated Account</span></span>
                    </div>
                    <div class="pm-form-grid-2">
                        <div class="pm-field">
                            <span class="pm-label">Full Name</span>
                            <input type="text" class="pm-input" value="<?= e($user['full_name']) ?>" readonly>
                        </div>
                        <div class="pm-field">
                            <span class="pm-label">Email Address</span>
                            <input type="email" class="pm-input" value="<?= e($user['email']) ?>" readonly>
                        </div>
                    </div>
                </div>

                <!-- Manual Payment Method Selection with Large Real SVG Logos -->
                <?php if (bccomp((string)$summary['total'], '0.00', 2) > 0): ?>
                    <div class="pm-card pm-card-pad" style="display:grid;gap:16px;">
                        <div>
                            <h2 class="pm-h3">2. Choose Manual Payment Wallet</h2>
                            <p class="pm-muted" style="font-size:0.85rem;margin-top:3px;">On the next step you will see our official account number &amp; submit your Transaction ID (TrxID) with receipt screenshot.</p>
                        </div>

                        <?php if (empty($methods)): ?>
                            <div class="pm-alert pm-alert-warning">
                                <?= icon('alert-circle', 18) ?>
                                <span>Manual payment channels are currently being configured by the administrator. Please check back shortly.</span>
                            </div>
                        <?php else: ?>
                            <div class="pm-pay-select-grid">
                                <?php foreach ($methods as $m):
                                    $code = strtolower((string)$m['code']); ?>
                                    <label class="pm-pay-option" data-method="<?= e($code) ?>">
                                        <input type="radio" name="preferred_method" value="<?= e($code) ?>" <?= $code === $defaultCode ? 'checked' : '' ?> required>
                                        <span class="pm-pay-option-card" style="min-height:108px;padding:18px 14px;">
                                            <span class="pm-pay-option-check"><?= icon('check', 13) ?></span>
                                            <?= payment_brand_svg($code, 46, true) ?>
                                            <span class="pm-pay-option-meta"><?= e(ucfirst((string)$m['account_type'])) ?> · <?= e($m['account_number']) ?></span>
                                        </span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <div class="pm-field">
                            <label class="pm-label" for="order_notes">Order Note (Optional)</label>
                            <input type="text" id="order_notes" name="order_notes" class="pm-input" maxlength="400" placeholder="Optional invoice reference or organization name">
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Order Items Review -->
                <div class="pm-card pm-card-pad" style="display:grid;gap:14px;">
                    <div style="display:flex;align-items:center;justify-content:space-between;">
                        <h2 class="pm-h3">3. Items in This Order (<?= (int)$summary['items_count'] ?>)</h2>
                        <a href="<?= e(url('cart.php')) ?>" class="pm-link" style="font-size:0.83rem;">Edit Cart</a>
                    </div>
                    <div style="display:grid;gap:12px;">
                        <?php foreach ($summary['items'] as $item): ?>
                            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;padding:12px;border-radius:14px;background:var(--bg);border:1px solid var(--border);">
                                <div style="display:flex;align-items:center;gap:12px;min-width:0;">
                                    <img src="<?= e(media_url((string)$item['thumbnail'], 'assets/images/placeholders/project-default.svg')) ?>" alt="<?= e($item['title']) ?>" style="width:68px;height:46px;border-radius:9px;object-fit:cover;flex-shrink:0;">
                                    <div style="min-width:0;">
                                        <strong style="display:block;font-size:0.9rem;color:var(--text-primary);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= e($item['title']) ?></strong>
                                        <small class="pm-muted">by <?= e($item['seller_name']) ?> · <?= e(license_label((string)$item['license_type'])) ?> · v<?= e($item['version']) ?></small>
                                    </div>
                                </div>
                                <strong class="pm-tabular" style="font-size:0.96rem;color:var(--text-primary);white-space:nowrap;"><?= e(format_price((string)$item['computed_price'])) ?></strong>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Sticky Summary Box -->
            <aside class="pm-card pm-summary-card">
                <h2 class="pm-h3">Payable Summary</h2>

                <div class="pm-summary-rows">
                    <div class="pm-summary-row">
                        <span>Subtotal</span>
                        <strong class="pm-tabular"><?= e(format_price((string)$summary['subtotal'])) ?></strong>
                    </div>
                    <?php if ($summary['coupon'] && bccomp((string)$summary['discount'], '0.00', 2) > 0): ?>
                        <div class="pm-summary-row is-discount">
                            <span>Coupon (<?= e($summary['coupon']['code']) ?>)</span>
                            <span class="pm-tabular">-<?= e(format_price((string)$summary['discount'])) ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="pm-summary-row is-total">
                        <span>Total Payable</span>
                        <span class="pm-tabular"><?= e(format_price((string)$summary['total'])) ?></span>
                    </div>
                </div>

                <div style="padding:13px 14px;border-radius:12px;background:var(--bg-inset);font-size:0.8rem;color:var(--text-secondary);line-height:1.55;display:grid;gap:6px;">
                    <span style="display:flex;align-items:center;gap:7px;font-weight:600;color:var(--text-primary);"><?= icon('lock', 14) ?>Protected License Delivery</span>
                    <span>Downloads and unique license keys activate immediately once your payment TrxID is verified by our team.</span>
                </div>

                <button type="submit" class="pm-btn pm-btn-success pm-btn-lg pm-btn-block" <?= (bccomp((string)$summary['total'], '0.00', 2) > 0 && empty($methods)) ? 'disabled' : '' ?>>
                    <?= icon('arrow-right', 18) ?>
                    <span>Place Order &amp; Continue to Pay</span>
                </button>
                <a href="<?= e(url('cart.php')) ?>" class="pm-btn pm-btn-ghost pm-btn-sm pm-btn-block">
                    <?= icon('arrow-left', 15) ?><span>Back to Cart</span>
                </a>
            </aside>
        </form>
    </div>
</section>

<?php require INCLUDES_PATH . '/layouts/footer.php'; ?>
