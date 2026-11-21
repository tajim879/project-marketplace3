<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$user = require_seller();
$uid = (int)$user['id'];
$pdo = db();

$pStmt = $pdo->prepare("SELECT `id`, `title`, `version`, `status` FROM `projects` WHERE `seller_id` = ? ORDER BY `title` ASC");
$pStmt->execute([$uid]);
$projects = $pStmt->fetchAll();

$selectedId = (int)($_GET['project'] ?? ($projects[0]['id'] ?? 0));
$versions = [];
if ($selectedId > 0) {
    $vStmt = $pdo->prepare(
        "SELECT pv.*, pf.file_size_bytes, pf.is_current
         FROM `project_versions` pv
         LEFT JOIN `project_files` pf ON pf.id = pv.file_id
         INNER JOIN `projects` p ON p.id = pv.project_id
         WHERE pv.project_id = ? AND p.seller_id = ?
         ORDER BY pv.id DESC"
    );
    $vStmt->execute([$selectedId, $uid]);
    $versions = $vStmt->fetchAll();
}

$pageTitle = 'Version Updates — Seller Studio';
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
                    <h1 class="pm-h2">Release &amp; Version Manager</h1>
                    <p class="pm-muted">Upload new ZIP builds with changelogs. Existing verified buyers are notified automatically.</p>
                </div>

                <?php if (empty($projects)): ?>
                    <div class="pm-card pm-empty">
                        <div class="pm-icon-orb"><?= icon('layers', 26) ?></div>
                        <h2 class="pm-h3">Upload a project first</h2>
                        <p class="pm-muted">Once you upload a project, you can push version updates and changelogs from here.</p>
                        <a href="<?= e(url('seller/upload-project.php')) ?>" class="pm-btn pm-btn-primary" style="margin-top:8px;">Upload Project</a>
                    </div>
                <?php else: ?>
                    <form method="post" action="<?= e(url('actions/seller/project-update.php')) ?>" enctype="multipart/form-data" class="pm-card pm-card-pad" style="display:grid;gap:16px;" data-submit-lock>
                        <?= csrf_field() ?>
                        <h2 class="pm-h3">Publish New Version</h2>
                        <div class="pm-grid-3">
                            <div class="pm-field">
                                <label class="pm-label" for="project_id">Select Project</label>
                                <select id="project_id" name="project_id" class="pm-select" onchange="window.location='?project='+this.value" required>
                                    <?php foreach ($projects as $p): ?>
                                        <option value="<?= (int)$p['id'] ?>" <?= $selectedId === (int)$p['id'] ? 'selected' : '' ?>>
                                            <?= e($p['title']) ?> (v<?= e($p['version']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="pm-field">
                                <label class="pm-label" for="version">New Version Tag</label>
                                <input type="text" id="version" name="version" class="pm-input" placeholder="e.g. 1.1.0" required>
                            </div>
                            <div class="pm-field">
                                <label class="pm-label" for="project_zip">Updated ZIP Archive</label>
                                <input type="file" id="project_zip" name="project_zip" class="pm-input" accept=".zip,application/zip" required>
                            </div>
                        </div>
                        <div class="pm-field">
                            <label class="pm-label" for="changelog">Release Changelog</label>
                            <textarea id="changelog" name="changelog" class="pm-textarea" rows="3" placeholder="List new features, fixes, and upgrade instructions..." required></textarea>
                        </div>
                        <div>
                            <button type="submit" class="pm-btn pm-btn-primary"><?= icon('upload', 17) ?><span>Publish Update &amp; Notify Buyers</span></button>
                        </div>
                    </form>

                    <div class="pm-card pm-card-pad" style="display:grid;gap:14px;">
                        <h2 class="pm-h3">Release History</h2>
                        <div class="pm-table-wrap">
                            <table class="pm-table">
                                <thead><tr><th>Version</th><th>Changelog</th><th>Size</th><th>Released</th><th>State</th></tr></thead>
                                <tbody>
                                    <?php foreach ($versions as $ver): ?>
                                        <tr>
                                            <td><code>v<?= e($ver['version']) ?></code></td>
                                            <td><?= e($ver['changelog']) ?></td>
                                            <td class="pm-tabular"><?= UploadService::formatBytes((int)($ver['file_size_bytes'] ?? 0)) ?></td>
                                            <td><?= e(format_date((string)$ver['released_at'])) ?></td>
                                            <td><?= (int)($ver['is_current'] ?? 0) === 1 ? '<span class="pm-badge pm-badge-success">Current</span>' : '<span class="pm-badge">Archived</span>' ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php require INCLUDES_PATH . '/layouts/footer.php'; ?>
