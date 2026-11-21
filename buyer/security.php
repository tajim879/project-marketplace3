<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$user = require_login();
$uid = (int)$user['id'];
$currToken = (string)($_SESSION['auth_session_token'] ?? '');

$sessStmt = db()->prepare(
    "SELECT * FROM `user_sessions`
     WHERE `user_id` = ? AND `status` = 'active'
     ORDER BY `last_activity_at` DESC
     LIMIT 10"
);
$sessStmt->execute([$uid]);
$sessions = $sessStmt->fetchAll();

$logStmt = db()->prepare("SELECT * FROM `security_logs` WHERE `user_id` = ? ORDER BY `created_at` DESC LIMIT 10");
$logStmt->execute([$uid]);
$logs = $logStmt->fetchAll();

$pageTitle = 'Security Settings';
$pageRobots = 'noindex, nofollow';
$pageStyles = ['buyer.css', 'auth.css'];

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

                <div class="pm-dash-header">
                    <div>
                        <h1 class="pm-h2">Security Settings</h1>
                        <p class="pm-muted">Update your password, inspect active device sessions, and review your security audit log.</p>
                    </div>
                </div>

                <!-- Change Password -->
                <form method="post" action="<?= e(url('actions/buyer/security.php')) ?>" class="pm-card pm-card-pad" style="display:grid;gap:16px;padding:28px;" data-submit-lock>
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="change_password">
                    <h2 class="pm-h3">Change Password</h2>

                    <div class="pm-field" style="max-width:440px;">
                        <label class="pm-label" for="curPw">Current Password</label>
                        <input type="password" id="curPw" name="current_password" class="pm-input" autocomplete="current-password" required>
                    </div>

                    <div class="pm-form-grid-2">
                        <div class="pm-field">
                            <label class="pm-label" for="secNewPw">New Password</label>
                            <input type="password" id="secNewPw" name="new_password" class="pm-input" minlength="8" data-password-strength autocomplete="new-password" required>
                            <div class="pm-strength-meter"><div id="pmStrengthFill" class="pm-strength-fill"></div></div>
                            <small id="pmStrengthLabel" class="pm-field-hint">Use 8+ characters with letters and numbers</small>
                        </div>
                        <div class="pm-field">
                            <label class="pm-label" for="secNewPw2">Confirm New Password</label>
                            <input type="password" id="secNewPw2" name="new_password_confirmation" class="pm-input" minlength="8" autocomplete="new-password" required>
                        </div>
                    </div>

                    <div>
                        <button type="submit" class="pm-btn pm-btn-primary">
                            <?= icon('lock', 16) ?><span>Update Password</span>
                        </button>
                    </div>
                </form>

                <!-- Active Sessions -->
                <div>
                    <h2 class="pm-h3" style="margin-bottom:14px;">Active Browser Sessions</h2>
                    <div class="pm-table-wrap">
                        <table class="pm-table">
                            <thead>
                                <tr>
                                    <th>Device / Browser</th>
                                    <th>IP Address</th>
                                    <th>Last Active</th>
                                    <th style="text-align:right;">Session</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sessions as $s):
                                    $isCurrent = hash_equals($currToken, (string)$s['session_token']); ?>
                                    <tr>
                                        <td style="max-width:340px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= e($s['user_agent'] ?: 'Web Browser') ?></td>
                                        <td><code><?= e($s['ip_address'] ?: '—') ?></code></td>
                                        <td><?= e(time_ago((string)$s['last_activity_at'])) ?></td>
                                        <td style="text-align:right;">
                                            <?php if ($isCurrent): ?>
                                                <span class="pm-badge pm-badge-success"><?= icon('check-circle', 12) ?><span>Current Session</span></span>
                                            <?php else: ?>
                                                <form method="post" action="<?= e(url('actions/buyer/security.php')) ?>" style="display:inline;">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="action" value="revoke_session">
                                                    <input type="hidden" name="session_id" value="<?= (int)$s['id'] ?>">
                                                    <button type="submit" class="pm-btn pm-btn-danger pm-btn-sm">Revoke</button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Security Log -->
                <div>
                    <h2 class="pm-h3" style="margin-bottom:14px;">Recent Security Audit Events</h2>
                    <div class="pm-table-wrap">
                        <table class="pm-table">
                            <thead>
                                <tr>
                                    <th>Event</th>
                                    <th>IP Address</th>
                                    <th>Timestamp</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($logs as $l): ?>
                                    <tr>
                                        <td style="font-weight:500;color:var(--text-primary);"><?= e($l['description']) ?></td>
                                        <td><code><?= e($l['ip_address'] ?: '—') ?></code></td>
                                        <td><?= e(format_date((string)$l['created_at'], 'M j, Y · g:i A')) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require INCLUDES_PATH . '/layouts/footer.php'; ?>
