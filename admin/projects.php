<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$pageTitle = 'Projects Moderation';
require INCLUDES_PATH . '/layouts/admin-header.php';

$status = trim((string)($_GET['status'] ?? 'all'));
$q = trim((string)($_GET['q'] ?? ''));

$sql = "SELECT p.*, c.name AS category_name, u.username AS seller_username, up.store_name
        FROM `projects` p
        INNER JOIN `categories` c ON c.id = p.category_id
        INNER JOIN `users` u ON u.id = p.seller_id
        LEFT JOIN `user_profiles` up ON up.user_id = u.id
        WHERE 1=1";
$params = [];

if ($status === 'featured') {
    $sql .= " AND p.is_featured = 1";
} elseif (in_array($status, ['pending', 'approved', 'rejected', 'draft', 'hidden', 'archived'], true)) {
    $sql .= " AND p.status = ?";
    $params[] = $status;
}

if ($q !== '') {
    $sql .= " AND (p.title LIKE ? OR u.username LIKE ? OR up.store_name LIKE ?)";
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like);
}

$sql .= " ORDER BY CASE WHEN p.status = 'pending' THEN 0 ELSE 1 END, p.id DESC LIMIT 150";
$stmt = db()->prepare($sql);
$stmt->execute($params);
$projects = $stmt->fetchAll();
?>

<div class="pm-admin-header">
    <div>
        <h1>Projects Catalog &amp; Moderation</h1>
        <p>Inspect submitted creator packages, approve or reject with notes, toggle featured/trending badges, and govern listings.</p>
    </div>
    <a href="<?= e(url('seller/upload-project.php')) ?>" class="pm-btn pm-btn-primary">
        <?= icon('upload', 16) ?><span>Upload Project</span>
    </a>
</div>

<div class="pm-card pm-card-pad pm-admin-toolbar">
    <div class="pm-admin-tabs">
        <?php foreach (['all' => 'All', 'pending' => 'Pending Review', 'approved' => 'Approved', 'featured' => 'Featured', 'rejected' => 'Rejected', 'hidden' => 'Hidden', 'draft' => 'Drafts'] as $sk => $sl): ?>
            <a href="<?= e(build_query_url('admin/projects.php', ['status' => $sk])) ?>" class="pm-chip <?= $status === $sk ? 'is-active' : '' ?>"><?= e($sl) ?></a>
        <?php endforeach; ?>
    </div>
    <form method="get" action="<?= e(url('admin/projects.php')) ?>" class="pm-btn-row">
        <input type="hidden" name="status" value="<?= e($status) ?>">
        <input type="search" name="q" value="<?= e($q) ?>" class="pm-input" placeholder="Search title or seller..." style="width:220px;">
        <button type="submit" class="pm-btn pm-btn-secondary pm-btn-sm">Search</button>
    </form>
</div>

<div class="pm-card pm-card-pad">
    <?php if (empty($projects)): ?>
        <div class="pm-empty">
            <div class="pm-icon-orb"><?= icon('package', 26) ?></div>
            <h2 class="pm-h3">No projects in this filter</h2>
            <p class="pm-muted">Projects uploaded by sellers appear here for code inspection and moderation.</p>
        </div>
    <?php else: ?>
        <div class="pm-table-wrap">
            <table class="pm-table">
                <thead>
                    <tr>
                        <th>Project</th>
                        <th>Seller</th>
                        <th>Price</th>
                        <th>Sales</th>
                        <th>Flags</th>
                        <th>Status</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($projects as $pr): ?>
                        <tr>
                            <td>
                                <div style="display:flex;align-items:center;gap:12px;">
                                    <img src="<?= e(media_url((string)$pr['thumbnail'], 'assets/images/placeholders/project-default.svg')) ?>" alt="" style="width:56px;height:38px;border-radius:8px;object-fit:cover;flex-shrink:0;">
                                    <div>
                                        <strong><?= e($pr['title']) ?></strong>
                                        <small class="pm-muted" style="display:block;"><?= e($pr['category_name']) ?> · v<?= e($pr['version']) ?> · <?= e($pr['file_size_label'] ?: 'ZIP') ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><?= e($pr['store_name'] ?: '@' . $pr['seller_username']) ?></td>
                            <td class="pm-tabular" style="font-weight:700;"><?= (int)$pr['is_free'] === 1 ? 'Free' : e(format_price(effective_project_price($pr))) ?></td>
                            <td class="pm-tabular"><?= (int)$pr['sales_count'] ?></td>
                            <td>
                                <?php if ((int)$pr['is_featured'] === 1): ?><span class="pm-badge pm-badge-primary">Featured</span><?php endif; ?>
                                <?php if ((int)$pr['is_trending'] === 1): ?><span class="pm-badge pm-badge-warning">Trending</span><?php endif; ?>
                                <?php if (!(int)$pr['is_featured'] && !(int)$pr['is_trending']): ?><span class="pm-muted">—</span><?php endif; ?>
                            </td>
                            <td><?= render_status_badge((string)$pr['status']) ?></td>
                            <td style="text-align:right;">
                                <div class="pm-btn-row" style="justify-content:flex-end;">
                                    <?php if ($pr['status'] === 'pending'): ?>
                                        <form method="post" action="<?= e(url('actions/admin/projects.php')) ?>">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="approve">
                                            <input type="hidden" name="project_id" value="<?= (int)$pr['id'] ?>">
                                            <button type="submit" class="pm-btn pm-btn-primary pm-btn-sm">Approve</button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="post" action="<?= e(url('actions/admin/projects.php')) ?>">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="toggle_featured">
                                        <input type="hidden" name="project_id" value="<?= (int)$pr['id'] ?>">
                                        <button type="submit" class="pm-btn pm-btn-ghost pm-btn-sm"><?= (int)$pr['is_featured'] ? 'Unfeature' : 'Feature' ?></button>
                                    </form>
                                    <a href="<?= e(url('admin/project-review.php?id=' . (int)$pr['id'])) ?>" class="pm-btn pm-btn-secondary pm-btn-sm">Inspect</a>
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
