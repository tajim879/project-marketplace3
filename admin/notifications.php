<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$pageTitle = 'Notifications Center';
require INCLUDES_PATH . '/layouts/admin-header.php';

$pdo = db();
$users = $pdo->query("SELECT `id`, `full_name`, `username`, `role` FROM `users` WHERE `status` = 'active' ORDER BY `full_name` ASC LIMIT 200")->fetchAll();
$recent = $pdo->query(
    "SELECT n.*, u.username FROM `notifications` n
     INNER JOIN `users` u ON u.id = n.user_id
     ORDER BY n.id DESC LIMIT 50"
)->fetchAll();
?>

<div class="pm-admin-header">
    <div>
        <h1>Notifications &amp; Announcements</h1>
        <p>Broadcast marketplace announcements to all users, sellers, buyers, or send targeted in-app alerts to a single member.</p>
    </div>
</div>

<div class="pm-admin-split">
    <form method="post" action="<?= e(url('actions/admin/moderation.php')) ?>" class="pm-card pm-card-pad" style="display:grid;gap:14px;">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="send_notification">
        <h2 class="pm-h3">Compose Announcement</h2>

        <div class="pm-form-grid-2">
            <div class="pm-field">
                <label class="pm-label">Target Audience</label>
                <select name="audience" class="pm-select">
                    <option value="all">All Active Users</option>
                    <option value="sellers">All Approved Sellers</option>
                    <option value="buyers">All Buyers</option>
                    <option value="user">Specific Individual User</option>
                </select>
            </div>
            <div class="pm-field">
                <label class="pm-label">Specific User (if selected)</label>
                <select name="target_user_id" class="pm-select">
                    <option value="0">— Select user —</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?= (int)$u['id'] ?>"><?= e($u['full_name']) ?> (@<?= e($u['username']) ?> · <?= e($u['role']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="pm-field">
            <label class="pm-label">Title</label>
            <input type="text" name="title" class="pm-input" placeholder="e.g. Weekend Developer Sale — 15% Off" required>
        </div>

        <div class="pm-field">
            <label class="pm-label">Message Body</label>
            <textarea name="message" class="pm-textarea" rows="4" placeholder="Write your notification message..." required></textarea>
        </div>

        <div class="pm-field">
            <label class="pm-label">Action Link (Optional relative URL)</label>
            <input type="text" name="action_url" class="pm-input" placeholder="e.g. projects.php or seller/dashboard.php">
        </div>

        <div><button type="submit" class="pm-btn pm-btn-primary"><?= icon('send', 16) ?><span>Deliver Notification</span></button></div>
    </form>

    <div class="pm-card pm-card-pad" style="display:grid;gap:12px;">
        <h2 class="pm-h3">Recent Dispatched Notifications</h2>
        <?php if (empty($recent)): ?>
            <p class="pm-muted">No notifications recorded yet.</p>
        <?php else: ?>
            <div class="pm-table-wrap">
                <table class="pm-table">
                    <thead><tr><th>Recipient</th><th>Notification</th><th>Read</th><th>Sent</th></tr></thead>
                    <tbody>
                        <?php foreach ($recent as $n): ?>
                            <tr>
                                <td>@<?= e($n['username']) ?></td>
                                <td><strong><?= e($n['title']) ?></strong><small class="pm-muted" style="display:block;"><?= e(mb_substr((string)$n['message'], 0, 80)) ?></small></td>
                                <td><?= (int)$n['is_read'] ? '<span class="pm-badge pm-badge-success">Read</span>' : '<span class="pm-badge">Unread</span>' ?></td>
                                <td style="font-size:0.78rem;"><?= e(format_datetime((string)$n['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require INCLUDES_PATH . '/layouts/admin-footer.php'; ?>
