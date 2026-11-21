<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$pageTitle = 'Orders & Invoices';
require INCLUDES_PATH . '/layouts/admin-header.php';

$status = trim((string)($_GET['status'] ?? 'all'));
$q = trim((string)($_GET['q'] ?? ''));
$validStatuses = ['all', 'pending_payment', 'payment_submitted', 'under_verification', 'paid', 'rejected', 'cancelled', 'refunded'];
if (!in_array($status, $validStatuses, true)) {
    $status = 'all';
}

$sql = "SELECT o.*, u.full_name AS buyer_name, u.email AS buyer_email,
               (SELECT COUNT(*) FROM `order_items` oi WHERE oi.order_id = o.id) AS items_count,
               (SELECT pay.method_code FROM `payments` pay WHERE pay.order_id = o.id ORDER BY pay.id DESC LIMIT 1) AS last_method
        FROM `orders` o
        INNER JOIN `users` u ON u.id = o.buyer_id
        WHERE 1=1";
$params = [];
if ($status !== 'all') {
    $sql .= " AND o.status = ?";
    $params[] = $status;
}
if ($q !== '') {
    $sql .= " AND (o.order_number LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)";
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like);
}
$sql .= " ORDER BY o.id DESC LIMIT 150";
$stmt = db()->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();
?>

<div class="pm-admin-header">
    <div>
        <h1>Orders &amp; Invoice Management</h1>
        <p>Inspect every customer order, license allocation, payment trail, printable invoice, and refund/cancellation state.</p>
    </div>
</div>

<div class="pm-card pm-card-pad pm-admin-toolbar">
    <div class="pm-admin-tabs">
        <?php foreach ($validStatuses as $st): ?>
            <a href="<?= e(build_query_url('admin/orders.php', ['status' => $st])) ?>" class="pm-chip <?= $status === $st ? 'is-active' : '' ?>">
                <?= e(ucwords(str_replace('_', ' ', $st))) ?>
            </a>
        <?php endforeach; ?>
    </div>
    <form method="get" action="<?= e(url('admin/orders.php')) ?>" class="pm-btn-row">
        <input type="hidden" name="status" value="<?= e($status) ?>">
        <input type="search" name="q" value="<?= e($q) ?>" class="pm-input" placeholder="Order #, buyer, email..." style="width:220px;">
        <button type="submit" class="pm-btn pm-btn-secondary pm-btn-sm">Search</button>
    </form>
</div>

<div class="pm-card pm-card-pad">
    <?php if (empty($orders)): ?>
        <div class="pm-empty">
            <div class="pm-icon-orb"><?= icon('shopping-bag', 26) ?></div>
            <h2 class="pm-h3">No orders found</h2>
            <p class="pm-muted">Marketplace orders appear here automatically as buyers complete checkout.</p>
        </div>
    <?php else: ?>
        <div class="pm-table-wrap">
            <table class="pm-table">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Buyer</th>
                        <th>Items</th>
                        <th>Gateway</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $o): ?>
                        <tr>
                            <td><code><?= e($o['order_number']) ?></code></td>
                            <td>
                                <strong><?= e($o['buyer_name']) ?></strong>
                                <small class="pm-muted" style="display:block;"><?= e($o['buyer_email']) ?></small>
                            </td>
                            <td class="pm-tabular"><?= (int)$o['items_count'] ?></td>
                            <td><?= !empty($o['last_method']) ? payment_brand_badge((string)$o['last_method'], 'sm') : '<span class="pm-muted">—</span>' ?></td>
                            <td class="pm-tabular" style="font-weight:700;"><?= e(format_price((string)$o['total_amount'])) ?></td>
                            <td><?= render_status_badge((string)$o['status']) ?></td>
                            <td><?= e(format_datetime((string)$o['created_at'])) ?></td>
                            <td style="text-align:right;">
                                <div class="pm-btn-row" style="justify-content:flex-end;">
                                    <a href="<?= e(url('invoice.php?order=' . urlencode((string)$o['order_number']))) ?>" class="pm-btn pm-btn-ghost pm-btn-sm" target="_blank">Invoice</a>
                                    <a href="<?= e(url('admin/order-view.php?order=' . urlencode((string)$o['order_number']))) ?>" class="pm-btn pm-btn-secondary pm-btn-sm">Manage</a>
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
