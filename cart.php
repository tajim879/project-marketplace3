<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$summary = OrderService::getCartSummary();
$methods = db()->query("SELECT * FROM `payment_methods` WHERE `is_enabled` = 1 ORDER BY `sort_order` ASC")->fetchAll();

$pageTitle = 'Shopping Cart';
$pageRobots = 'noindex, nofollow';
$pageStyles = ['checkout.css'];

require INCLUDES_PATH . '/layouts/header.php';
?>

<section class="pm-page-header">
    <div class="pm-container">
        <nav class="pm-breadcrumb" aria-label="Breadcrumb">
            <a href="<?= e(url('index.php')) ?>"><?= icon('home', 14) ?><span>Home</span></a>
            <?= icon('chevron-right', 14) ?>
            <span>Shopping Cart</span>
        </nav>
        <h1 class="pm-h1">Your Shopping Cart</h1>
        <p class="pm-muted" style="margin-top:5px;">All prices and coupons are validated strictly on the server in BDT (<?= e(site_setting('currency_symbol', '৳')) ?>).</p>
    </div>
</section>

<section class="pm-section-tight">
    <div class="pm-container">
        <?php require INCLUDES_PATH . '/components/alerts.php'; ?>

        <?php if (empty($summary['items'])): ?>
            <div class="pm-card pm-empty">
                <div class="pm-icon-orb"><?= icon('shopping-bag', 26) ?></div>
                <h2 class="pm-h3">Your cart is currently empty</h2>
                <p class="pm-muted" style="max-width:420px;">Browse verified PHP applications, Kotlin apps, WordPress solutions, Python systems, and UI kits to get started.</p>
                <div class="pm-btn-row" style="margin-top:8px;">
                    <a href="<?= e(url('projects.php')) ?>" class="pm-btn pm-btn-primary"><?= icon('grid', 17) ?><span>Explore Projects</span></a>
                    <a href="<?= e(url('free-projects.php')) ?>" class="pm-btn pm-btn-secondary"><?= icon('tag', 17) ?><span>Free Starter Kits</span></a>
                </div>
            </div>
        <?php else: ?>
            <div class="pm-cart-layout">
                <div class="pm-cart-list">
                    <?php foreach ($summary['items'] as $item): ?>
                        <article class="pm-card pm-cart-item">
                            <a href="<?= e(url('project.php?slug=' . urlencode((string)$item['slug']))) ?>" class="pm-cart-thumb">
                                <img src="<?= e(media_url((string)$item['thumbnail'], 'assets/images/placeholders/project-default.svg')) ?>" alt="<?= e($item['title']) ?>">
                            </a>
                            <div class="pm-cart-info">
                                <span class="pm-badge pm-badge-primary" style="width:fit-content;"><?= e(license_label((string)$item['license_type'])) ?></span>
                                <h3><a href="<?= e(url('project.php?slug=' . urlencode((string)$item['slug']))) ?>"><?= e($item['title']) ?></a></h3>
                                <small class="pm-muted">
                                    <?= e($item['category_name']) ?> · by <?= e($item['seller_name']) ?> · v<?= e($item['version']) ?> · <?= (int)$item['support_months'] ?>m support
                                </small>
                            </div>
                            <div class="pm-cart-price-col">
                                <strong class="pm-price-current"><?= e(format_price((string)$item['computed_price'])) ?></strong>
                                <form method="post" action="<?= e(url('actions/orders/cart.php')) ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="project_id" value="<?= (int)$item['project_id'] ?>">
                                    <button type="submit" class="pm-btn pm-btn-danger pm-btn-sm">
                                        <?= icon('trash', 14) ?><span>Remove</span>
                                    </button>
                                </form>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <aside class="pm-card pm-summary-card">
                    <h2 class="pm-h3">Order Summary</h2>

                    <?php if ($summary['coupon']): ?>
                        <div class="pm-alert pm-alert-success" style="margin:0;align-items:center;justify-content:space-between;">
                            <span style="display:inline-flex;align-items:center;gap:7px;">
                                <?= icon('tag', 15) ?>
                                <strong><?= e($summary['coupon']['code']) ?></strong>
                            </span>
                            <form method="post" action="<?= e(url('actions/orders/cart.php')) ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="remove_coupon">
                                <button type="submit" class="pm-link" style="font-size:0.78rem;">Remove</button>
                            </form>
                        </div>
                    <?php else: ?>
                        <form method="post" action="<?= e(url('actions/orders/cart.php')) ?>" class="pm-coupon-form">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="apply_coupon">
                            <input type="text" name="coupon_code" class="pm-input" placeholder="Enter coupon code" aria-label="Coupon code">
                            <button type="submit" class="pm-btn pm-btn-secondary">Apply</button>
                        </form>
                    <?php endif; ?>

                    <div class="pm-summary-rows">
                        <div class="pm-summary-row">
                            <span>Subtotal (<?= $summary['items_count'] ?> <?= $summary['items_count'] === 1 ? 'item' : 'items' ?>)</span>
                            <strong class="pm-tabular"><?= e(format_price($summary['subtotal'])) ?></strong>
                        </div>
                        <?php if (bccomp($summary['discount'], '0.00', 2) > 0): ?>
                            <div class="pm-summary-row is-discount">
                                <span>Coupon Discount</span>
                                <span class="pm-tabular">-<?= e(format_price($summary['discount'])) ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="pm-summary-row is-total">
                            <span>Payable Total</span>
                            <span class="pm-tabular"><?= e(format_price($summary['total'])) ?></span>
                        </div>
                    </div>

                    <div style="padding:14px;border-radius:14px;background:var(--bg-inset);box-shadow:var(--shadow-inset);display:grid;gap:10px;">
                        <span style="font-size:0.79rem;font-weight:700;color:var(--text-primary);display:flex;align-items:center;gap:6px;">
                            <?= icon('shield-check', 15) ?><span>Supported Manual Payments</span>
                        </span>
                        <div class="pm-pay-strip">
                            <?php foreach ($methods as $m): ?>
                                <?= payment_brand_badge((string)$m['code'], 'sm') ?>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <?php if (is_logged_in()): ?>
                        <a href="<?= e(url('checkout.php')) ?>" class="pm-btn pm-btn-success pm-btn-lg pm-btn-block">
                            <?= icon('shield-check', 18) ?><span>Proceed to Checkout</span>
                        </a>
                    <?php else: ?>
                        <a href="<?= e(url('login.php')) ?>" class="pm-btn pm-btn-primary pm-btn-lg pm-btn-block">
                            <?= icon('lock', 18) ?><span>Sign In to Checkout</span>
                        </a>
                    <?php endif; ?>
                    <a href="<?= e(url('projects.php')) ?>" class="pm-btn pm-btn-ghost pm-btn-sm pm-btn-block">
                        <?= icon('arrow-left', 15) ?><span>Continue Browsing</span>
                    </a>
                </aside>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require INCLUDES_PATH . '/layouts/footer.php'; ?>
