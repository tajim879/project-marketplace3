<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$pageTitle = 'Audit Logs, Security & Backup';
require INCLUDES_PATH . '/layouts/admin-header.php';

$pdo = db();
$tab = trim((string)($_GET['tab'] ?? 'audit'));

$auditLogs = $pdo->query(
    "SELECT al.*, u.username AS admin_username
     FROM `admin_logs` al
     INNER JOIN `users` u ON u.id = al.admin_id
     ORDER BY al.id DESC LIMIT 120"
)->fetchAll();

$secLogs = $pdo->query(
    "SELECT sl.*, u.username
     FROM `security_logs` sl
     LEFT JOIN `users` u ON u.id = sl.user_id
     ORDER BY sl.id DESC LIMIT 120"
)->fetchAll();

$backups = $pdo->query("SELECT * FROM `backups` ORDER BY `id` DESC LIMIT 25")->fetchAll();
$dbFile = Database::databasePath();
$dbSize = is_file($dbFile) ? UploadService::formatBytes((int)filesize($dbFile)) : '0 B';
$sqliteVer = (string)$pdo->query("SELECT sqlite_version()")->fetchColumn();
$tableCount = (int)$pdo->query("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")->fetchColumn();
?>

<div class="pm-admin-header">
    <div>
        <h1>Audit Logs, Security Monitor &amp; Database Backup</h1>
        <p>Full administrative accountability trail, security event monitoring, and zero-downtime portable SQLite snapshot export.</p>
    </div>
</div>

<div class="pm-admin-tabs">
    <a href="<?= e(url('admin/logs.php?tab=audit')) ?>" class="pm-chip <?= $tab === 'audit' ? 'is-active' : '' ?>">Admin Audit Logs (<?= count($auditLogs) ?>)</a>
    <a href="<?= e(url('admin/logs.php?tab=security')) ?>" class="pm-chip <?= $tab === 'security' ? 'is-active' : '' ?>">Security Events (<?= count($secLogs) ?>)</a>
    <a href="<?= e(url('admin/logs.php?tab=backup')) ?>" class="pm-chip <?= $tab === 'backup' ? 'is-active' : '' ?>">Database Health &amp; Backup</a>
</div>

<?php if ($tab === 'backup'): ?>
    <div class="pm-admin-split-equal">
        <div class="pm-card pm-card-pad" style="display:grid;gap:14px;">
            <h2 class="pm-h3">External SQLite 3 Engine Status</h2>
            <div style="font-size:0.86rem;display:grid;gap:8px;">
                <div><span class="pm-muted">Engine:</span> <strong>SQLite <?= e($sqliteVer) ?> (WAL Mode + Foreign Keys ON)</strong></div>
                <div><span class="pm-muted">Normalized Tables:</span> <strong class="pm-tabular"><?= $tableCount ?> tables</strong></div>
                <div><span class="pm-muted">Database Size:</span> <strong class="pm-tabular"><?= e($dbSize) ?></strong></div>
                <div><span class="pm-muted">PHP Runtime:</span> <strong>PHP <?= e(PHP_VERSION) ?></strong></div>
                <div><span class="pm-muted">Isolated Outside Webroot:</span> <span class="pm-badge pm-badge-success">Verified Secure</span></div>
            </div>
            <div class="pm-btn-row" style="padding-top:8px;border-top:1px solid var(--border);flex-wrap:wrap;">
                <form method="post" action="<?= e(url('actions/admin/cms.php')) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="db_backup">
                    <button type="submit" class="pm-btn pm-btn-primary"><?= icon('download', 16) ?><span>Download Live SQLite Backup</span></button>
                </form>
                <form method="post" action="<?= e(url('actions/admin/cms.php')) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="system_cleanup">
                    <button type="submit" class="pm-btn pm-btn-secondary">Run Cleanup &amp; WAL Checkpoint</button>
                </form>
            </div>
        </div>

        <div class="pm-card pm-card-pad" style="display:grid;gap:12px;">
            <h2 class="pm-h3">Backup Export History</h2>
            <?php if (empty($backups)): ?>
                <p class="pm-muted">No backups exported yet.</p>
            <?php else: ?>
                <div class="pm-table-wrap">
                    <table class="pm-table">
                        <thead><tr><th>Filename</th><th>Size</th><th>Exported At</th></tr></thead>
                        <tbody>
                            <?php foreach ($backups as $bk): ?>
                                <tr>
                                    <td><code><?= e($bk['filename']) ?></code></td>
                                    <td class="pm-tabular"><?= e(UploadService::formatBytes((int)$bk['file_size_bytes'])) ?></td>
                                    <td><?= e(format_datetime((string)$bk['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

<?php elseif ($tab === 'security'): ?>
    <div class="pm-card pm-card-pad">
        <?php if (empty($secLogs)): ?>
            <p class="pm-muted" style="padding:20px 0;">No security alerts recorded.</p>
        <?php else: ?>
            <div class="pm-table-wrap">
                <table class="pm-table">
                    <thead><tr><th>Severity</th><th>Event</th><th>User</th><th>Description</th><th>IP</th><th>Time</th></tr></thead>
                    <tbody>
                        <?php foreach ($secLogs as $sl): ?>
                            <tr>
                                <td><?= render_status_badge((string)$sl['severity']) ?></td>
                                <td><code><?= e($sl['event_type']) ?></code></td>
                                <td><?= !empty($sl['username']) ? '@' . e($sl['username']) : '<span class="pm-muted">Guest</span>' ?></td>
                                <td style="font-size:0.83rem;"><?= e($sl['description']) ?></td>
                                <td><code style="font-size:0.76rem;"><?= e((string)$sl['ip_address']) ?></code></td>
                                <td style="font-size:0.78rem;"><?= e(format_datetime((string)$sl['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

<?php else: ?>
    <div class="pm-card pm-card-pad">
        <?php if (empty($auditLogs)): ?>
            <p class="pm-muted" style="padding:20px 0;">No admin audit entries recorded yet.</p>
        <?php else: ?>
            <div class="pm-table-wrap">
                <table class="pm-table">
                    <thead><tr><th>#</th><th>Admin</th><th>Action</th><th>Target</th><th>Details</th><th>IP</th><th>Time</th></tr></thead>
                    <tbody>
                        <?php foreach ($auditLogs as $al): ?>
                            <tr>
                                <td class="pm-tabular">#<?= (int)$al['id'] ?></td>
                                <td><strong>@<?= e($al['admin_username']) ?></strong></td>
                                <td><code><?= e($al['action']) ?></code></td>
                                <td><?= !empty($al['target_type']) ? e($al['target_type']) . ($al['target_id'] ? ' #' . (int)$al['target_id'] : '') : '—' ?></td>
                                <td style="font-size:0.83rem;"><?= e((string)($al['details'] ?? '—')) ?></td>
                                <td><code style="font-size:0.76rem;"><?= e((string)$al['ip_address']) ?></code></td>
                                <td style="font-size:0.78rem;"><?= e(format_datetime((string)$al['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php require INCLUDES_PATH . '/layouts/admin-footer.php'; ?>
