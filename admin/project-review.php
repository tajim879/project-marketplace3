<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$pid = (int)($_GET['id'] ?? 0);
$pdo = db();
$stmt = $pdo->prepare(
    "SELECT p.*, c.name AS category_name, u.full_name AS seller_name, u.username AS seller_username, u.email AS seller_email, up.store_name
     FROM `projects` p
     INNER JOIN `categories` c ON c.id = p.category_id
     INNER JOIN `users` u ON u.id = p.seller_id
     LEFT JOIN `user_profiles` up ON up.user_id = u.id
     WHERE p.id = ? LIMIT 1"
);
$stmt->execute([$pid]);
$project = $stmt->fetch();

if (!$project) {
    require_admin();
    flash('error', 'Project not found.');
    redirect(url('admin/projects.php'));
}

$fStmt = $pdo->prepare("SELECT * FROM `project_files` WHERE `project_id` = ? ORDER BY `is_current` DESC, `id` DESC");
$fStmt->execute([$pid]);
$files = $fStmt->fetchAll();

$iStmt = $pdo->prepare("SELECT * FROM `project_images` WHERE `project_id` = ? ORDER BY `sort_order` ASC");
$iStmt->execute([$pid]);
$images = $iStmt->fetchAll();

$tStmt = $pdo->prepare("SELECT `tech_name` FROM `project_technologies` WHERE `project_id` = ?");
$tStmt->execute([$pid]);
$techs = $tStmt->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = 'Inspect: ' . $project['title'];
require INCLUDES_PATH . '/layouts/admin-header.php';
$selfPath = 'admin/project-review.php?id=' . $pid;
?>

<div class="pm-admin-header">
    <div>
        <h1><?= e($project['title']) ?> <?= render_status_badge((string)$project['status']) ?></h1>
        <p>Seller: <strong><?= e($project['store_name'] ?: $project['seller_name']) ?></strong> (@<?= e($project['seller_username']) ?> · <?= e($project['seller_email']) ?>) · Category: <?= e($project['category_name']) ?></p>
    </div>
    <div class="pm-btn-row">
        <a href="<?= e(url('download.php?project=' . $pid)) ?>" class="pm-btn pm-btn-secondary">
            <?= icon('download', 16) ?><span>Download ZIP for Audit</span>
        </a>
        <a href="<?= e(url('seller/edit-project.php?id=' . $pid)) ?>" class="pm-btn pm-btn-primary">Edit Project Details</a>
        <a href="<?= e(url('admin/projects.php')) ?>" class="pm-btn pm-btn-ghost">Back</a>
    </div>
</div>

<div class="pm-admin-split">
    <div style="display:grid;gap:18px;">
        <div class="pm-card pm-card-pad" style="display:grid;gap:14px;">
            <img src="<?= e(media_url((string)$project['thumbnail'], 'assets/images/placeholders/project-default.svg')) ?>" alt="<?= e($project['title']) ?>" style="width:100%;max-height:340px;object-fit:cover;border-radius:12px;">
            <h2 class="pm-h3">Summary</h2>
            <p><?= e($project['short_description']) ?></p>
            <h2 class="pm-h3">Full Technical Description</h2>
            <div class="pm-muted" style="line-height:1.7;"><?= nl2br(e((string)$project['description'])) ?></div>
            <?php if (!empty($techs)): ?>
                <div class="pm-btn-row" style="flex-wrap:wrap;">
                    <?php foreach ($techs as $t): ?><span class="pm-badge"><?= e($t) ?></span><?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($images)): ?>
            <div class="pm-card pm-card-pad" style="display:grid;gap:12px;">
                <h2 class="pm-h3">Gallery Screenshots (<?= count($images) ?>)</h2>
                <div class="pm-grid-3">
                    <?php foreach ($images as $im): ?>
                        <img src="<?= e(media_url((string)$im['image_path'])) ?>" alt="" style="width:100%;height:140px;object-fit:cover;border-radius:10px;border:1px solid var(--border);">
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="pm-card pm-card-pad" style="display:grid;gap:12px;">
            <h2 class="pm-h3">Private ZIP Archives</h2>
            <div class="pm-table-wrap">
                <table class="pm-table">
                    <thead><tr><th>Version</th><th>Original Filename</th><th>Size</th><th>SHA-256</th><th>State</th></tr></thead>
                    <tbody>
                        <?php foreach ($files as $fl): ?>
                            <tr>
                                <td><code>v<?= e($fl['version']) ?></code></td>
                                <td><?= e($fl['original_name']) ?></td>
                                <td class="pm-tabular"><?= e(UploadService::formatBytes((int)$fl['file_size_bytes'])) ?></td>
                                <td><code style="font-size:0.72rem;"><?= e(substr((string)$fl['checksum_sha256'], 0, 16)) ?>…</code></td>
                                <td><?= (int)$fl['is_current'] ? '<span class="pm-badge pm-badge-success">Current</span>' : '<span class="pm-badge">Archived</span>' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div style="display:grid;gap:18px;">
        <div class="pm-card pm-card-pad" style="display:grid;gap:14px;">
            <h2 class="pm-h3">Moderation Controls</h2>
            <div style="font-size:0.86rem;display:grid;gap:6px;">
                <div><span class="pm-muted">Pricing:</span> <strong class="pm-tabular"><?= (int)$project['is_free'] ? 'Free (৳0)' : format_price(effective_project_price($project)) . ' (Regular ' . format_price((string)$project['price']) . ')' ?></strong></div>
                <div><span class="pm-muted">License:</span> <strong><?= e(ucfirst((string)$project['license_type'])) ?></strong> · Support: <?= (int)$project['support_months'] ?> months</div>
                <div><span class="pm-muted">Compatibility:</span> <?= e((string)($project['compatibility'] ?: '—')) ?></div>
                <?php if (!empty($project['demo_url'])): ?>
                    <div><span class="pm-muted">Live Demo:</span> <a href="<?= e($project['demo_url']) ?>" target="_blank" rel="noopener" class="pm-link"><?= e($project['demo_url']) ?></a></div>
                <?php endif; ?>
            </div>

            <form method="post" action="<?= e(url('actions/admin/projects.php')) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="approve">
                <input type="hidden" name="project_id" value="<?= $pid ?>">
                <input type="hidden" name="return_to" value="<?= e($selfPath) ?>">
                <button type="submit" class="pm-btn pm-btn-primary pm-btn-block"><?= icon('check', 17) ?><span>Approve &amp; Publish Live</span></button>
            </form>

            <div class="pm-form-grid-2">
                <form method="post" action="<?= e(url('actions/admin/projects.php')) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="toggle_featured">
                    <input type="hidden" name="project_id" value="<?= $pid ?>">
                    <input type="hidden" name="return_to" value="<?= e($selfPath) ?>">
                    <button type="submit" class="pm-btn pm-btn-secondary pm-btn-sm pm-btn-block"><?= (int)$project['is_featured'] ? 'Remove Featured' : 'Mark Featured' ?></button>
                </form>
                <form method="post" action="<?= e(url('actions/admin/projects.php')) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="toggle_trending">
                    <input type="hidden" name="project_id" value="<?= $pid ?>">
                    <input type="hidden" name="return_to" value="<?= e($selfPath) ?>">
                    <button type="submit" class="pm-btn pm-btn-secondary pm-btn-sm pm-btn-block"><?= (int)$project['is_trending'] ? 'Remove Trending' : 'Mark Trending' ?></button>
                </form>
            </div>

            <form method="post" action="<?= e(url('actions/admin/projects.php')) ?>" style="display:grid;gap:8px;padding-top:10px;border-top:1px solid var(--border);">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="reject">
                <input type="hidden" name="project_id" value="<?= $pid ?>">
                <input type="hidden" name="return_to" value="<?= e($selfPath) ?>">
                <label class="pm-label">Reject with Feedback to Seller</label>
                <textarea name="rejection_reason" class="pm-textarea" rows="3" placeholder="Explain what must be fixed before approval..." required><?= e((string)($project['rejection_reason'] ?? '')) ?></textarea>
                <button type="submit" class="pm-btn pm-btn-secondary" style="color:var(--danger);">Reject Submission</button>
            </form>

            <form method="post" action="<?= e(url('actions/admin/projects.php')) ?>" style="padding-top:10px;border-top:1px solid var(--border);">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="project_id" value="<?= $pid ?>">
                <button type="submit" class="pm-btn pm-btn-ghost pm-btn-sm pm-btn-block" style="color:var(--danger);" onclick="return confirm('Delete or archive this project?');">
                    <?= icon('trash', 14) ?><span>Delete / Archive Project</span>
                </button>
            </form>
        </div>
    </div>
</div>

<?php require INCLUDES_PATH . '/layouts/admin-footer.php'; ?>
