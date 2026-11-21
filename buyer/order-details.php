<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$user = require_login();
$ref = trim((string)($_GET['order'] ?? ''));
$order = $ref !== '' ? OrderRepository::findBuyerOrder((int)$user['id'], $ref) : null;

if (!$order) {
    flash('warning', 'Order not found.');
    redirect(url('buyer/orders.php'));
}

$pageTitle = 'Order ' . $order['order_number'];
$pageRobots = 'noindex, nofollow';
$pageStyles = ['buyer.css', 'project-details.css'];

require INCLUDES_PATH . '/layouts/header.php';
?>

<section class="pm-dashboard-section">
    <div class="pm-container">
        <div class="pm-dashboard-shell">
            <?php require INCLUDES_PATH . '/layouts/buyer-sidebar.php'; ?>

            <div class="pm-dash-main">
                <div class="pm-dash-mobile-bar">
                    <button type="button" class="pm-btn pm-btn-secondary pm-btn-sm" data-drawer-open="pmBuyerSidebar">
                        <?= icon('menu', 16) ?><span>Dashboard Menu</span>
                    </button>
                    <a href="<?= e(url('invoice.php?order=' . urlencode((string)$order['order_number']))) ?>" class="pm-btn pm-btn-primary pm-btn-sm">
                        <?= icon('printer', 15) ?><span>Invoice</span>
                    </a>
                </div>

                <div class="pm-dash-header">
                    <div>
                        <a href="<?= e(url('buyer/orders.php')) ?>" class="pm-link" style="font-size:0.82rem;display:inline-flex;align-items:center;gap:5px;margin-bottom:6px;">
                            <?= icon('arrow-left', 14) ?><span>Back to Orders</span>
                        </a>
                        <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                            <h1 class="pm-h2"><?= e($order['order_number']) ?></h1>
                            <?= render_status_badge((string)$order['status']) ?>
                        </div>
                        <p class="pm-muted">Created on <?= e(format_date((string)$order['created_at'], 'M j, Y · g:i A')) ?></p>
                    </div>
                    <a href="<?= e(url('invoice.php?order=' . urlencode((string)$order['order_number']))) ?>" class="pm-btn pm-btn-secondary">
                        <?= icon('printer', 16) ?><span>Open Printable Invoice</span>
                    </a>
                </div>

                <!-- Purchased Items -->
                <div class="pm-table-wrap">
                    <table class="pm-table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>License</th>
                                <th>Price</th>
                                <th style="text-align:right;">Download</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($order['items'] as $it):
                                $ok = $order['status'] === 'paid' && (int)$it['download_permitted'] === 1 && $it['status'] === 'active'; ?>
                                <tr>
                                    <td>
                                        <a href="<?= e(url('project.php?slug=' . urlencode((string)$it['project_slug']))) ?>" style="font-weight:700;color:var(--text-primary);display:block;"><?= e($it['project_title']) ?></a>
                                        <small class="pm-muted">by <?= e($it['seller_name']) ?> · v<?= e($it['version']) ?> (<?= e($it['file_size_label']) ?>)</small>
                                    </td>
                                    <td>
                                        <span><?= e(license_label((string)$it['license_type'])) ?></span>
                                        <?php if ($ok && !empty($it['license_key'])): ?>
                                            <small style="display:block;margin-top:3px;"><code><?= e($it['license_key']) ?></code></small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="pm-tabular" style="font-weight:700;color:var(--text-primary);"><?= e(format_price((string)$it['final_price'])) ?></td>
                                    <td style="text-align:right;">
                                        <?php if ($ok): ?>
                                            <a href="<?= e(url('download.php?item=' . (int)$it['id'])) ?>" class="pm-btn pm-btn-primary pm-btn-sm">
                                                <?= icon('download', 14) ?><span>Download ZIP</span>
                                            </a>
                                        <?php else: ?>
                                            <span class="pm-badge pm-badge-muted"><?= icon('lock', 12) ?><span>Locked</span></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="pm-grid-2">
                    <!-- Payment Verification Record -->
                    <div class="pm-card pm-card-pad" style="display:grid;gap:14px;align-content:start;">
                        <h2 class="pm-h3">Payment &amp; Financial Summary</h2>
                        <dl class="pm-spec-list">
                            <div class="pm-spec-row"><dt>Subtotal</dt><dd><?= e(format_price((string)$order['subtotal_amount'])) ?></dd></div>
                            <div class="pm-spec-row"><dt>Discount</dt><dd>-<?= e(format_price((string)$order['discount_amount'])) ?></dd></div>
                            <div class="pm-spec-row"><dt>Order Total</dt><dd style="font-size:1rem;color:var(--primary);"><?= e(format_price((string)$order['total_amount'])) ?></dd></div>
                            <?php if (!empty($order['payments'])):
                                $py = $order['payments'][0]; ?>
                                <div class="pm-spec-row"><dt>Method</dt><dd><?= payment_brand_badge((string)($py['method_code'] ?? $py['method_name']), 'sm') ?></dd></div>
                                <div class="pm-spec-row"><dt>Sender Number</dt><dd><?= e($py['sender_number']) ?></dd></div>
                                <div class="pm-spec-row"><dt>Transaction ID</dt><dd><code><?= e($py['transaction_id']) ?></code></dd></div>
                                <div class="pm-spec-row"><dt>Verification</dt><dd><?= render_status_badge((string)$py['status']) ?></dd></div>
                                <?php if (!empty($py['rejection_reason'])): ?>
                                    <div class="pm-spec-row"><dt>Review Note</dt><dd style="color:var(--danger);"><?= e($py['rejection_reason']) ?></dd></div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </dl>
                        <?php if (in_array((string)$order['status'], ['pending_payment', 'rejected'], true) && bccomp((string)$order['total_amount'], '0.00', 2) > 0): ?>
                            <a href="<?= e(url('payment.php?order=' . urlencode((string)$order['order_number']))) ?>" class="pm-btn pm-btn-primary pm-btn-block">
                                <?= icon('credit-card', 17) ?>
                                <span><?= $order['status'] === 'rejected' ? 'Resubmit Manual Payment' : 'Complete Manual Payment' ?></span>
                            </a>
                        <?php endif; ?>
                    </div>

                    <!-- Order Timeline -->
                    <div class="pm-card pm-card-pad" style="display:grid;gap:14px;align-content:start;">
                        <h2 class="pm-h3">Order Status Timeline</h2>
                        <?php if (empty($order['timeline'])): ?>
                            <p class="pm-muted">Order created on <?= e(format_date((string)$order['created_at'])) ?>.</p>
                        <?php else: ?>
                            <div class="pm-timeline">
                                <?php foreach ($order['timeline'] as $st): ?>
                                    <div class="pm-timeline-step">
                                        <strong style="font-size:0.86rem;color:var(--text-primary);"><?= e(status_badge_meta((string)$st['new_status'])['label']) ?></strong>
                                        <?php if (!empty($st['note'])): ?>
                                            <span style="font-size:0.82rem;color:var(--text-secondary);"><?= e($st['note']) ?></span>
                                        <?php endif; ?>
                                        <small class="pm-muted"><?= e(format_date((string)$st['created_at'], 'M j, Y · g:i A')) ?></small>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require INCLUDES_PATH . '/layouts/footer.php'; ?>
