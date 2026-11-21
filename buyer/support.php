<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$user = require_login();
$uid = (int)$user['id'];
$pdo = db();

$selectedTicketNo = trim((string)($_GET['ticket'] ?? ''));
$activeTicket = null;
$messages = [];

if ($selectedTicketNo !== '') {
    $tStmt = $pdo->prepare("SELECT * FROM `support_tickets` WHERE `ticket_number` = ? AND `user_id` = ? LIMIT 1");
    $tStmt->execute([$selectedTicketNo, $uid]);
    $activeTicket = $tStmt->fetch() ?: null;

    if ($activeTicket) {
        $mStmt = $pdo->prepare(
            "SELECT tm.*, u.full_name AS sender_name
             FROM `ticket_messages` tm
             LEFT JOIN `users` u ON u.id = tm.sender_id
             WHERE tm.ticket_id = ?
             ORDER BY tm.created_at ASC, tm.id ASC"
        );
        $mStmt->execute([$activeTicket['id']]);
        $messages = $mStmt->fetchAll();
    }
}

$listStmt = $pdo->prepare("SELECT * FROM `support_tickets` WHERE `user_id` = ? ORDER BY `last_reply_at` DESC");
$listStmt->execute([$uid]);
$tickets = $listStmt->fetchAll();

$pageTitle = 'Support Tickets';
$pageRobots = 'noindex, nofollow';
$pageStyles = ['buyer.css'];

require INCLUDES_PATH . '/layouts/header.php';
?>

<section class="pm-dashboard-section">
    <div class="pm-container">
        <?php require INCLUDES_PATH . '/components/alerts.php'; ?>

        <div class="pm-dashboard-shell">
            <?php require INCLUDES_PATH . '/layouts/buyer-sidebar.php'; ?>

            <div class="pm-dash-main">
                <div class="pm-dash-mobile-bar">
                    <button type="button" class="pm-btn pm-btn-secondary pm-btn-sm" data-drawer-open="pmBuyerSidebar">
                        <?= icon('menu', 16) ?><span>Dashboard Menu</span>
                    </button>
                </div>

                <?php if ($activeTicket): ?>
                    <div class="pm-dash-header">
                        <div>
                            <a href="<?= e(url('buyer/support.php')) ?>" class="pm-link" style="font-size:0.82rem;display:inline-flex;align-items:center;gap:5px;margin-bottom:6px;">
                                <?= icon('arrow-left', 14) ?><span>All Support Tickets</span>
                            </a>
                            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                                <h1 class="pm-h2"><?= e($activeTicket['subject']) ?></h1>
                                <?= render_status_badge((string)$activeTicket['status']) ?>
                            </div>
                            <small class="pm-muted"><?= e($activeTicket['ticket_number']) ?> · Category: <?= e(ucfirst((string)$activeTicket['category'])) ?></small>
                        </div>

                        <?php if ($activeTicket['status'] !== 'closed'): ?>
                            <form method="post" action="<?= e(url('actions/support/ticket.php')) ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="close">
                                <input type="hidden" name="ticket_number" value="<?= e($activeTicket['ticket_number']) ?>">
                                <button type="submit" class="pm-btn pm-btn-secondary pm-btn-sm">
                                    <?= icon('check-circle', 15) ?><span>Close Ticket</span>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>

                    <div class="pm-thread">
                        <?php foreach ($messages as $m): ?>
                            <article class="pm-msg <?= $m['sender_role'] === 'admin' ? 'is-admin' : '' ?>">
                                <div class="pm-msg-head">
                                    <strong style="display:inline-flex;align-items:center;gap:6px;">
                                        <?= icon($m['sender_role'] === 'admin' ? 'shield' : 'user', 14) ?>
                                        <span><?= e($m['sender_role'] === 'admin' ? ($m['sender_name'] ?: 'Support Team') . ' (Staff)' : ($m['sender_name'] ?: $user['full_name'])) ?></span>
                                    </strong>
                                    <span class="pm-muted"><?= e(format_date((string)$m['created_at'], 'M j, Y · g:i A')) ?></span>
                                </div>
                                <p style="color:var(--text-secondary);line-height:1.65;"><?= nl2br(e((string)$m['message'])) ?></p>
                                <?php if (!empty($m['attachment_name'])): ?>
                                    <span class="pm-badge pm-badge-muted" style="margin-top:10px;">
                                        <?= icon('image', 13) ?><span>Attached: <?= e($m['attachment_name']) ?></span>
                                    </span>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($activeTicket['status'] !== 'closed'): ?>
                        <form method="post" action="<?= e(url('actions/support/ticket.php')) ?>" enctype="multipart/form-data" class="pm-card pm-card-pad" style="display:grid;gap:14px;" data-submit-lock>
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="reply">
                            <input type="hidden" name="ticket_number" value="<?= e($activeTicket['ticket_number']) ?>">
                            <div class="pm-field">
                                <label class="pm-label" for="repMsg">Add a Reply</label>
                                <textarea id="repMsg" name="message" class="pm-textarea" placeholder="Write your follow-up message..." required></textarea>
                            </div>
                            <div style="display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;">
                                <input type="file" name="attachment" accept=".jpg,.jpeg,.png,.webp" style="font-size:0.82rem;color:var(--text-muted);">
                                <button type="submit" class="pm-btn pm-btn-primary">
                                    <?= icon('send', 15) ?><span>Send Reply</span>
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>

                <?php else: ?>
                    <div class="pm-dash-header">
                        <div>
                            <h1 class="pm-h2">Support Tickets</h1>
                            <p class="pm-muted">Get direct assistance with manual payment verification, downloads, orders, or refunds.</p>
                        </div>
                        <button type="button" class="pm-btn pm-btn-primary" data-modal-open="pmNewTicketModal">
                            <?= icon('plus', 16) ?><span>New Ticket</span>
                        </button>
                    </div>

                    <?php if (empty($tickets)): ?>
                        <div class="pm-card pm-empty">
                            <div class="pm-icon-orb"><?= icon('life-buoy', 25) ?></div>
                            <h2 class="pm-h3">No support tickets yet</h2>
                            <p class="pm-muted">Need help with an order, payment, or download? Open a ticket anytime.</p>
                            <button type="button" class="pm-btn pm-btn-primary pm-btn-sm" data-modal-open="pmNewTicketModal">Create Ticket</button>
                        </div>
                    <?php else: ?>
                        <div class="pm-table-wrap">
                            <table class="pm-table">
                                <thead>
                                    <tr>
                                        <th>Ticket ID</th>
                                        <th>Subject</th>
                                        <th>Category</th>
                                        <th>Status</th>
                                        <th>Last Updated</th>
                                        <th style="text-align:right;">Open</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tickets as $t): ?>
                                        <tr>
                                            <td><code><?= e($t['ticket_number']) ?></code></td>
                                            <td style="font-weight:600;color:var(--text-primary);"><?= e($t['subject']) ?></td>
                                            <td><?= e(ucfirst((string)$t['category'])) ?></td>
                                            <td><?= render_status_badge((string)$t['status']) ?></td>
                                            <td><?= e(time_ago((string)$t['last_reply_at'])) ?></td>
                                            <td style="text-align:right;">
                                                <a href="<?= e(url('buyer/support.php?ticket=' . urlencode((string)$t['ticket_number']))) ?>" class="pm-btn pm-btn-secondary pm-btn-sm">Thread</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- Create Ticket Modal -->
<div class="pm-modal" id="pmNewTicketModal" aria-hidden="true" role="dialog" aria-label="Create support ticket">
    <div class="pm-modal-dialog">
        <div class="pm-modal-head">
            <strong class="pm-h3">Create Support Ticket</strong>
            <button type="button" class="pm-icon-btn" style="width:34px;height:34px;" data-modal-close aria-label="Close"><?= icon('close', 16) ?></button>
        </div>
        <form method="post" action="<?= e(url('actions/support/ticket.php')) ?>" enctype="multipart/form-data" style="display:grid;gap:14px;" data-submit-lock>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="create">
            <div class="pm-field">
                <label class="pm-label" for="tkCat">Category</label>
                <select id="tkCat" name="category" class="pm-select" required>
                    <option value="order">Order Support</option>
                    <option value="payment">bKash / Nagad / Rocket Payment</option>
                    <option value="download">Download &amp; License</option>
                    <option value="technical">Project Technical Issue</option>
                    <option value="refund">Refund Request</option>
                    <option value="seller">Seller Account</option>
                    <option value="general">General Question</option>
                </select>
            </div>
            <div class="pm-field">
                <label class="pm-label" for="tkSubj">Subject</label>
                <input id="tkSubj" name="subject" class="pm-input" placeholder="Brief summary of your issue" required>
            </div>
            <div class="pm-field">
                <label class="pm-label" for="tkMsg">Description</label>
                <textarea id="tkMsg" name="message" class="pm-textarea" placeholder="Describe your issue clearly and include any Order ID or TrxID..." required></textarea>
            </div>
            <div class="pm-field">
                <label class="pm-label" for="tkFile">Optional Screenshot (JPG/PNG/WebP, max 5 MB)</label>
                <input type="file" id="tkFile" name="attachment" accept=".jpg,.jpeg,.png,.webp">
            </div>
            <div style="display:flex;justify-content:flex-end;gap:10px;padding-top:6px;">
                <button type="button" class="pm-btn pm-btn-secondary pm-btn-sm" data-modal-close>Cancel</button>
                <button type="submit" class="pm-btn pm-btn-primary pm-btn-sm"><?= icon('send', 14) ?><span>Submit Ticket</span></button>
            </div>
        </form>
    </div>
</div>

<?php require INCLUDES_PATH . '/layouts/footer.php'; ?>
