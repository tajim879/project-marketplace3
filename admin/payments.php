<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$pageTitle = 'Manual Payment Verification';
require INCLUDES_PATH . '/layouts/admin-header.php';

$status = trim((string)($_GET['status'] ?? 'queue'));
$method = trim((string)($_GET['method'] ?? 'all'));
$q = trim((string)($_GET['q'] ?? ''));

$sql = "SELECT pay.*, o.order_number, o.total_amount AS order_total, o.status AS order_status,
               u.full_name AS buyer_name, u.email AS buyer_email, u.username AS buyer_username,
               v.username AS verifier_username,
               (SELECT pp.id FROM `payment_proofs` pp WHERE pp.payment_id = pay.id ORDER BY pp.id DESC LIMIT 1) AS proof_id
        FROM `payments` pay
        INNER JOIN `orders` o ON o.id = pay.order_id
        INNER JOIN `users` u ON u.id = pay.buyer_id
        LEFT JOIN `users` v ON v.id = pay.verified_by
        WHERE 1=1";
$params = [];

if ($status === 'queue') {
    $sql .= " AND pay.status IN ('submitted','under_verification')";
} elseif (in_array($status, ['submitted', 'under_verification', 'approved', 'rejected', 'refunded'], true)) {
    $sql .= " AND pay.status = ?";
    $params[] = $status;
}

if (in_array($method, ['bkash', 'nagad', 'rocket'], true)) {
    $sql .= " AND pay.method_code = ?";
    $params[] = $method;
}

if ($q !== '') {
    $sql .= " AND (pay.transaction_id LIKE ? OR pay.sender_number LIKE ? OR o.order_number LIKE ? OR u.email LIKE ?)";
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like, $like);
}

$sql .= " ORDER BY CASE WHEN pay.status IN ('submitted','under_verification') THEN 0 ELSE 1 END, pay.id DESC LIMIT 150";
$stmt = db()->prepare($sql);
$stmt->execute($params);
$payments = $stmt->fetchAll();
?>

<div class="pm-admin-header">
    <div>
        <h1>bKash / Nagad / Rocket Payment Verification</h1>
        <p>Inspect Transaction IDs, sender wallet numbers, and uploaded screenshots. Approving atomically unlocks buyer downloads and credits 90% seller earnings + 10% platform commission.</p>
    </div>
    <a href="<?= e(url('admin/payment-methods.php')) ?>" class="pm-btn pm-btn-secondary">
        <?= icon('smartphone', 16) ?><span>Configure Wallet Numbers</span>
    </a>
</div>

<div class="pm-card pm-card-pad pm-admin-toolbar">
    <div class="pm-admin-tabs">
        <?php foreach (['queue' => 'Pending Queue', 'all' => 'All', 'submitted' => 'Submitted', 'under_verification' => 'Under Review', 'approved' => 'Approved', 'rejected' => 'Rejected', 'refunded' => 'Refunded'] as $sk => $sl): ?>
            <a href="<?= e(build_query_url('admin/payments.php', ['status' => $sk])) ?>" class="pm-chip <?= $status === $sk ? 'is-active' : '' ?>"><?= e($sl) ?></a>
        <?php endforeach; ?>
    </div>
    <form method="get" action="<?= e(url('admin/payments.php')) ?>" class="pm-btn-row">
        <input type="hidden" name="status" value="<?= e($status) ?>">
        <select name="method" class="pm-select" style="width:130px;">
            <option value="all">All Wallets</option>
            <?php foreach (['bkash' => 'bKash', 'nagad' => 'Nagad', 'rocket' => 'Rocket'] as $mk => $ml): ?>
                <option value="<?= $mk ?>" <?= $method === $mk ? 'selected' : '' ?>><?= $ml ?></option>
            <?php endforeach; ?>
        </select>
        <input type="search" name="q" value="<?= e($q) ?>" class="pm-input" placeholder="TrxID, sender, order #" style="width:200px;">
        <button type="submit" class="pm-btn pm-btn-secondary pm-btn-sm">Filter</button>
    </form>
</div>

<div class="pm-card pm-card-pad">
    <?php if (empty($payments)): ?>
        <div class="pm-empty">
            <div class="pm-icon-orb"><?= icon('check-circle', 26) ?></div>
            <h2 class="pm-h3">No payments match this filter</h2>
            <p class="pm-muted">Submitted manual bKash, Nagad, and Rocket proofs appear here immediately for verification.</p>
        </div>
    <?php else: ?>
        <div class="pm-table-wrap">
            <table class="pm-table">
                <thead>
                    <tr>
                        <th>Order &amp; Buyer</th>
                        <th>Gateway</th>
                        <th>Sender &amp; TrxID</th>
                        <th>Paid / Order</th>
                        <th>Screenshot</th>
                        <th>Status</th>
                        <th style="text-align:right;">Verification</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $p):
                        $amountMatch = bccomp((string)$p['amount'], (string)$p['order_total'], 2) >= 0; ?>
                        <tr>
                            <td>
                                <a href="<?= e(url('admin/order-view.php?order=' . urlencode((string)$p['order_number']))) ?>" class="pm-link"><code><?= e($p['order_number']) ?></code></a>
                                <small style="display:block;font-weight:600;color:var(--text-primary);"><?= e($p['buyer_name']) ?></small>
                                <small class="pm-muted" style="display:block;"><?= e(format_datetime((string)$p['submitted_at'])) ?></small>
                            </td>
                            <td><?= payment_brand_badge((string)$p['method_code'], 'md') ?></td>
                            <td>
                                <code style="font-size:0.86rem;font-weight:700;"><?= e($p['transaction_id']) ?></code>
                                <small class="pm-muted pm-tabular" style="display:block;">From: <?= e($p['sender_number']) ?></small>
                                <?php if (!empty($p['buyer_note'])): ?>
                                    <small class="pm-muted" style="display:block;">Note: <?= e($p['buyer_note']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td class="pm-tabular">
                                <strong style="color:<?= $amountMatch ? 'var(--success)' : 'var(--danger)' ?>;"><?= e(format_price((string)$p['amount'])) ?></strong>
                                <small class="pm-muted" style="display:block;">Due: <?= e(format_price((string)$p['order_total'])) ?></small>
                            </td>
                            <td>
                                <?php if (!empty($p['proof_id'])): ?>
                                    <a href="<?= e(url('payment-proof.php?id=' . (int)$p['proof_id'])) ?>" target="_blank" rel="noopener" title="Open full payment screenshot">
                                        <img src="<?= e(url('payment-proof.php?id=' . (int)$p['proof_id'])) ?>" alt="Proof" class="pm-proof-thumb">
                                    </a>
                                <?php else: ?>
                                    <span class="pm-muted" style="font-size:0.78rem;">No image</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= render_status_badge((string)$p['status']) ?>
                                <?php if (!empty($p['verifier_username'])): ?>
                                    <small class="pm-muted" style="display:block;">by @<?= e($p['verifier_username']) ?></small>
                                <?php endif; ?>
                                <?php if (!empty($p['rejection_reason'])): ?>
                                    <small style="color:var(--danger);display:block;max-width:180px;"><?= e($p['rejection_reason']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td style="text-align:right;">
                                <?php if (in_array((string)$p['status'], ['submitted', 'under_verification'], true)): ?>
                                    <div style="display:flex;gap:6px;justify-content:flex-end;flex-wrap:wrap;align-items:flex-start;">
                                        <form method="post" action="<?= e(url('actions/admin/payments.php')) ?>">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="approve">
                                            <input type="hidden" name="payment_id" value="<?= (int)$p['id'] ?>">
                                            <button type="submit" class="pm-btn pm-btn-primary pm-btn-sm"><?= icon('check', 14) ?><span>Approve</span></button>
                                        </form>
                                        <?php if ($p['status'] === 'submitted'): ?>
                                            <form method="post" action="<?= e(url('actions/admin/payments.php')) ?>">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="under_verification">
                                                <input type="hidden" name="payment_id" value="<?= (int)$p['id'] ?>">
                                                <button type="submit" class="pm-btn pm-btn-secondary pm-btn-sm">Reviewing</button>
                                            </form>
                                        <?php endif; ?>
                                        <details class="pm-inline-action">
                                            <summary class="pm-btn pm-btn-ghost pm-btn-sm" style="color:var(--danger);">Reject</summary>
                                            <form method="post" action="<?= e(url('actions/admin/payments.php')) ?>" class="pm-inline-pop">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="reject">
                                                <input type="hidden" name="payment_id" value="<?= (int)$p['id'] ?>">
                                                <input type="text" name="rejection_reason" class="pm-input" placeholder="Reason (e.g. invalid TrxID)" required>
                                                <button type="submit" class="pm-btn pm-btn-primary pm-btn-sm" style="background:var(--danger);">Confirm Reject</button>
                                            </form>
                                        </details>
                                    </div>
                                <?php else: ?>
                                    <a href="<?= e(url('admin/order-view.php?order=' . urlencode((string)$p['order_number']))) ?>" class="pm-btn pm-btn-ghost pm-btn-sm">Order</a>
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
