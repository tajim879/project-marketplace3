<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$user = require_seller();
$uid = (int)$user['id'];

$stmt = db()->prepare(
    "SELECT oi.*, o.order_number, o.status AS order_status, o.paid_at, o.created_at AS order_date,
            u.full_name AS buyer_name, u.username AS buyer_username
     FROM `order_items` oi
     INNER JOIN `orders` o ON o.id = oi.order_id
     INNER JOIN `users` u ON u.id = o.buyer_id
     WHERE oi.seller_id = ?
     ORDER BY oi.id DESC"
);
$stmt->execute([$uid]);
$sales = $stmt->fetchAll();

$pageTitle = 'Sales History — Seller Studio';
$pageRobots = 'noindex, nofollow';
$pageStyles = ['dashboard.css'];

require INCLUDES_PATH . '/layouts/header.php';
?>

<section class="pm-dash-shell">
    <div class="pm-container">
        <div class="pm-dash-grid">
            <?php require INCLUDES_PATH . '/layouts/seller-sidebar.php'; ?>

            <div class="pm-dash-main">
                <?php require INCLUDES_PATH . '/components/alerts.php'; ?>

                <div>
                    <h1 class="pm-h2">Sales History</h1>
                    <p class="pm-muted">Complete order-by-order breakdown with platform commission and net seller credit.</p>
                </div>

                <div class="pm-card pm-card-pad">
                    <?php if (empty($sales)): ?>
                        <div class="pm-empty">
                            <div class="pm-icon-orb"><?= icon('shopping-bag', 26) ?></div>
                            <h2 class="pm-h3">No sales yet</h2>
                            <p class="pm-muted">Every verified buyer order for your projects appears here with license and commission details.</p>
                        </div>
                    <?php else: ?>
                        <div class="pm-table-wrap">
                            <table class="pm-table">
                                <thead>
                                    <tr>
                                        <th>Order</th>
                                        <th>Project &amp; License</th>
                                        <th>Buyer</th>
                                        <th>Sale Price</th>
                                        <th>Commission</th>
                                        <th>Your Net</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($sales as $s): ?>
                                        <tr>
                                            <td><code><?= e($s['order_number']) ?></code></td>
                                            <td>
                                                <strong><?= e($s['project_title']) ?></strong>
                                                <small class="pm-muted" style="display:block;"><?= e(ucfirst((string)$s['license_type'])) ?><?= !empty($s['license_key']) ? ' · ' . e($s['license_key']) : '' ?></small>
                                            </td>
                                            <td><?= e($s['buyer_name']) ?></td>
                                            <td class="pm-tabular"><?= e(format_price((string)$s['final_price'])) ?></td>
                                            <td class="pm-tabular pm-muted">-<?= e(format_price((string)$s['commission_amount'])) ?> (<?= e($s['commission_rate']) ?>%)</td>
                                            <td class="pm-tabular" style="font-weight:700;color:var(--success);"><?= e(format_price((string)$s['seller_earning'])) ?></td>
                                            <td><?= render_status_badge((string)$s['order_status']) ?></td>
                                            <td><?= e(format_date((string)($s['paid_at'] ?: $s['order_date']))) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require INCLUDES_PATH . '/layouts/footer.php'; ?>
