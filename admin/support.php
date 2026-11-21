<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$pageTitle = 'Support Helpdesk';
require INCLUDES_PATH . '/layouts/admin-header.php';

$pdo = db();
$status = trim((string)($_GET['status'] ?? 'open_all'));
$ticketNo = trim((string)($_GET['ticket'] ?? ''));

$activeTicket = null;
$messages = [];
if ($ticketNo !== '') {
    $tStmt = $pdo->prepare(
        "SELECT st.*, u.full_name, u.email, u.username, o.order_number
         FROM `support_tickets` st
         LEFT JOIN `users` u ON u.id = st.user_id
         LEFT JOIN `orders` o ON o.id = st.order_id
         WHERE st.ticket_number = ? LIMIT 1"
    );
    $tStmt->execute([$ticketNo]);
    $activeTicket = $tStmt->fetch();
    if ($activeTicket) {
        $mStmt = $pdo->prepare(
            "SELECT tm.*, u.full_name AS sender_name
             FROM `ticket_messages` tm
             LEFT JOIN `users` u ON u.id = tm.sender_id
             WHERE tm.ticket_id = ? ORDER BY tm.id ASC"
        );
        $mStmt->execute([(int)$activeTicket['id']]);
        $messages = $mStmt->fetchAll();
    }
}

$sql = "SELECT st.*, u.full_name, u.email
        FROM `support_tickets` st
        LEFT JOIN `users` u ON u.id = st.user_id
        WHERE 1=1";
$params = [];
if ($status === 'open_all') {
    $sql .= " AND st.status IN ('open','in_progress')";
} elseif (in_array($status, ['open', 'in_progress', 'answered', 'resolved', 'closed'], true)) {
    $sql .= " AND st.status = ?";
    $params[] = $status;
}
$sql .= " ORDER BY st.last_reply_at DESC, st.id DESC LIMIT 120";
$stmt = db()->prepare($sql);
$stmt->execute($params);
$tickets = $stmt->fetchAll();
?>

<div class="pm-admin-header">
    <div>
        <h1>Support Tickets &amp; Helpdesk Center</h1>
        <p>Respond to buyer, seller, and guest inquiries, review uploaded attachments, and manage ticket resolution status.</p>
    </div>
    <?php if ($activeTicket): ?>
        <a href="<?= e(url('admin/support.php')) ?>" class="pm-btn pm-btn-secondary">Back to All Tickets</a>
    <?php endif; ?>
</div>

<?php if ($activeTicket): ?>
    <div class="pm-admin-split">
        <div class="pm-card pm-card-pad" style="display:grid;gap:14px;">
            <div>
                <span class="pm-muted" style="font-size:0.8rem;"><code><?= e($activeTicket['ticket_number']) ?></code> · <?= e(ucfirst((string)$activeTicket['category'])) ?> · Priority: <?= e(ucfirst((string)$activeTicket['priority'])) ?></span>
                <h2 class="pm-h3" style="margin-top:4px;"><?= e($activeTicket['subject']) ?> <?= render_status_badge((string)$activeTicket['status']) ?></h2>
                <small class="pm-muted">From: <?= e($activeTicket['full_name'] ?: ($activeTicket['guest_name'] ?: 'Guest')) ?> (<?= e($activeTicket['email'] ?: ($activeTicket['guest_email'] ?: '')) ?>)<?= !empty($activeTicket['order_number']) ? ' · Order ' . e($activeTicket['order_number']) : '' ?></small>
            </div>

            <div style="display:grid;gap:10px;border-top:1px solid var(--border);padding-top:14px;">
                <?php foreach ($messages as $m):
                    $isStaff = $m['sender_role'] === 'admin'; ?>
                    <div style="padding:14px;border-radius:14px;background:<?= $isStaff ? 'var(--primary-soft)' : 'var(--bg)' ?>;border:1px solid var(--border);">
                        <div style="display:flex;justify-content:space-between;font-size:0.78rem;margin-bottom:6px;">
                            <strong><?= $isStaff ? 'Admin Support (' . e((string)($m['sender_name'] ?: 'Staff')) . ')' : e((string)($m['sender_name'] ?: ($activeTicket['guest_name'] ?: 'User'))) ?></strong>
                            <span class="pm-muted"><?= e(format_datetime((string)$m['created_at'])) ?></span>
                        </div>
                        <p style="font-size:0.88rem;line-height:1.6;"><?= nl2br(e((string)$m['message'])) ?></p>
                        <?php if (!empty($m['attachment_path'])): ?>
                            <a href="<?= e(url('payment-proof.php?msg=' . (int)$m['id'])) ?>" target="_blank" class="pm-link" style="display:inline-block;margin-top:8px;font-size:0.8rem;">
                                View Attachment (<?= e((string)($m['attachment_name'] ?: 'image')) ?>)
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <form method="post" action="<?= e(url('actions/admin/moderation.php')) ?>" class="pm-card pm-card-pad" style="display:grid;gap:14px;">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="ticket_reply">
            <input type="hidden" name="ticket_id" value="<?= (int)$activeTicket['id'] ?>">
            <h2 class="pm-h3">Send Official Staff Reply</h2>
            <div class="pm-field">
                <label class="pm-label">Message (optional if only changing status)</label>
                <textarea name="message" class="pm-textarea" rows="5" placeholder="Write a helpful response..."></textarea>
            </div>
            <div class="pm-field">
                <label class="pm-label">Update Ticket Status</label>
                <select name="status" class="pm-select">
                    <?php foreach (['answered' => 'Answered', 'in_progress' => 'In Progress', 'resolved' => 'Resolved', 'closed' => 'Closed', 'open' => 'Open'] as $sk => $sl): ?>
                        <option value="<?= $sk ?>" <?= $activeTicket['status'] === $sk ? 'selected' : '' ?>><?= $sl ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="pm-btn pm-btn-primary pm-btn-block"><?= icon('send', 16) ?><span>Send Reply &amp; Update Status</span></button>
        </form>
    </div>
<?php else: ?>
    <div class="pm-admin-tabs">
        <?php foreach (['open_all' => 'Open Queue', 'all' => 'All', 'open' => 'Open', 'in_progress' => 'In Progress', 'answered' => 'Answered', 'resolved' => 'Resolved', 'closed' => 'Closed'] as $sk => $sl): ?>
            <a href="<?= e(url('admin/support.php?status=' . $sk)) ?>" class="pm-chip <?= $status === $sk ? 'is-active' : '' ?>"><?= e($sl) ?></a>
        <?php endforeach; ?>
    </div>

    <div class="pm-card pm-card-pad">
        <?php if (empty($tickets)): ?>
            <div class="pm-empty">
                <div class="pm-icon-orb"><?= icon('life-buoy', 26) ?></div>
                <h2 class="pm-h3">No support tickets in this view</h2>
                <p class="pm-muted">Customer and seller support inquiries appear here automatically.</p>
            </div>
        <?php else: ?>
            <div class="pm-table-wrap">
                <table class="pm-table">
                    <thead><tr><th>Ticket #</th><th>Subject</th><th>Sender</th><th>Category</th><th>Priority</th><th>Status</th><th>Updated</th><th></th></tr></thead>
                    <tbody>
                        <?php foreach ($tickets as $t): ?>
                            <tr>
                                <td><code><?= e($t['ticket_number']) ?></code></td>
                                <td><strong><?= e($t['subject']) ?></strong></td>
                                <td><?= e($t['full_name'] ?: ($t['guest_name'] ?: 'Guest')) ?></td>
                                <td><?= e(ucfirst((string)$t['category'])) ?></td>
                                <td><?= render_status_badge((string)$t['priority']) ?></td>
                                <td><?= render_status_badge((string)$t['status']) ?></td>
                                <td><?= e(format_datetime((string)$t['last_reply_at'])) ?></td>
                                <td style="text-align:right;"><a href="<?= e(url('admin/support.php?ticket=' . urlencode((string)$t['ticket_number']))) ?>" class="pm-btn pm-btn-primary pm-btn-sm">Reply</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php require INCLUDES_PATH . '/layouts/admin-footer.php'; ?>
