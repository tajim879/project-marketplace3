<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$user = require_seller();
$uid = (int)$user['id'];
$filter = trim((string)($_GET['status'] ?? 'all'));
$allowed = ['all', 'approved', 'pending', 'draft', 'rejected', 'hidden'];
if (!in_array($filter, $allowed, true)) {
    $filter = 'all';
}

$sql = "SELECT p.*, c.name AS category_name FROM `projects` p
        INNER JOIN `categories` c ON c.id = p.category_id
        WHERE p.seller_id = ?";
$params = [$uid];
if ($filter !== 'all') {
    $sql .= " AND p.status = ?";
    $params[] = $filter;
}
$sql .= " ORDER BY p.created_at DESC";
$stmt = db()->prepare($sql);
$stmt->execute($params);
$projects = $stmt->fetchAll();

$pageTitle = 'My Projects — Seller Studio';
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

                <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                    <div>
                        <h1 class="pm-h2">My Projects</h1>
                        <p class="pm-muted">Manage listings, push version updates, and track approval status.</p>
                    </div>
                    <a href="<?= e(url('seller/upload-project.php')) ?>" class="pm-btn pm-btn-primary">
                        <?= icon('upload', 17) ?><span>Upload New Project</span>
                    </a>
                </div>

                <div class="pm-btn-row" style="flex-wrap:wrap;">
                    <?php foreach ($allowed as $st): ?>
                        <a href="<?= e(url('seller/projects.php?status=' . $st)) ?>" class="pm-chip <?= $filter === $st ? 'is-active' : '' ?>">
                            <?= e(ucfirst($st)) ?>
                        </a>
                    <?php endforeach; ?>
                </div>

                <div class="pm-card pm-card-pad">
                    <?php if (empty($projects)): ?>
                        <div class="pm-empty">
                            <div class="pm-icon-orb"><?= icon('package', 26) ?></div>
                            <h2 class="pm-h3">No projects in this view</h2>
                            <p class="pm-muted">Upload a ZIP package with cover thumbnail and screenshots to publish on the marketplace.</p>
                            <a href="<?= e(url('seller/upload-project.php')) ?>" class="pm-btn pm-btn-primary" style="margin-top:8px;">
                                <?= icon('upload', 16) ?><span>Upload Project</span>
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="pm-table-wrap">
                            <table class="pm-table">
                                <thead>
                                    <tr>
                                        <th>Project</th>
                                        <th>Price</th>
                                        <th>Version</th>
                                        <th>Sales</th>
                                        <th>Rating</th>
                                        <th>Status</th>
                                        <th style="text-align:right;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($projects as $pr): ?>
                                        <tr>
                                            <td>
                                                <div style="display:flex;align-items:center;gap:12px;">
                                                    <img src="<?= e(media_url((string)$pr['thumbnail'], 'assets/images/placeholders/project-default.svg')) ?>" alt="<?= e($pr['title']) ?>" style="width:58px;height:40px;border-radius:8px;object-fit:cover;flex-shrink:0;">
                                                    <div>
                                                        <strong style="color:var(--text-primary);"><?= e($pr['title']) ?></strong>
                                                        <small class="pm-muted" style="display:block;"><?= e($pr['category_name']) ?> · <?= e($pr['file_size_label'] ?: 'ZIP') ?></small>
                                                        <?php if ($pr['status'] === 'rejected' && !empty($pr['rejection_reason'])): ?>
                                                            <small style="color:var(--danger);display:block;">Feedback: <?= e($pr['rejection_reason']) ?></small>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="pm-tabular" style="font-weight:700;"><?= (int)$pr['is_free'] === 1 ? 'Free' : e(format_price(effective_project_price($pr))) ?></td>
                                            <td><code>v<?= e($pr['version']) ?></code></td>
                                            <td class="pm-tabular"><?= (int)$pr['sales_count'] ?></td>
                                            <td class="pm-tabular"><?= (int)$pr['reviews_count'] > 0 ? number_format((float)$pr['average_rating'], 1) . ' (' . (int)$pr['reviews_count'] . ')' : '—' ?></td>
                                            <td><?= render_status_badge((string)$pr['status']) ?></td>
                                            <td style="text-align:right;">
                                                <div class="pm-btn-row" style="justify-content:flex-end;">
                                                    <?php if ($pr['status'] === 'approved'): ?>
                                                        <a href="<?= e(url('project.php?slug=' . urlencode((string)$pr['slug']))) ?>" class="pm-btn pm-btn-ghost pm-btn-sm" title="View live"><?= icon('eye', 15) ?></a>
                                                    <?php endif; ?>
                                                    <a href="<?= e(url('seller/project-updates.php?project=' . (int)$pr['id'])) ?>" class="pm-btn pm-btn-ghost pm-btn-sm" title="Push version">Update</a>
                                                    <a href="<?= e(url('seller/edit-project.php?id=' . (int)$pr['id'])) ?>" class="pm-btn pm-btn-secondary pm-btn-sm">Edit</a>
                                                    <?php if (in_array((string)$pr['status'], ['approved', 'hidden'], true)): ?>
                                                        <form method="post" action="<?= e(url('actions/seller/engage.php')) ?>">
                                                            <?= csrf_field() ?>
                                                            <input type="hidden" name="action" value="toggle_visibility">
                                                            <input type="hidden" name="project_id" value="<?= (int)$pr['id'] ?>">
                                                            <button type="submit" class="pm-btn pm-btn-ghost pm-btn-sm"><?= $pr['status'] === 'approved' ? 'Hide' : 'Unhide' ?></button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
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
