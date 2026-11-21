<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$pageTitle = 'Reviews, Q&A & Reports';
require INCLUDES_PATH . '/layouts/admin-header.php';

$pdo = db();
$tab = trim((string)($_GET['tab'] ?? 'reviews'));

$reviews = $pdo->query(
    "SELECT r.*, p.title AS project_title, u.full_name AS buyer_name
     FROM `reviews` r
     INNER JOIN `projects` p ON p.id = r.project_id
     INNER JOIN `users` u ON u.id = r.buyer_id
     ORDER BY r.id DESC LIMIT 100"
)->fetchAll();

$reports = $pdo->query(
    "SELECT rr.*, r.comment, r.rating, p.title AS project_title, u.username AS reporter
     FROM `review_reports` rr
     INNER JOIN `reviews` r ON r.id = rr.review_id
     INNER JOIN `projects` p ON p.id = r.project_id
     INNER JOIN `users` u ON u.id = rr.reported_by
     ORDER BY rr.id DESC LIMIT 100"
)->fetchAll();

$questions = $pdo->query(
    "SELECT pq.*, p.title AS project_title, u.username AS asker
     FROM `project_questions` pq
     INNER JOIN `projects` p ON p.id = pq.project_id
     INNER JOIN `users` u ON u.id = pq.user_id
     ORDER BY pq.id DESC LIMIT 100"
)->fetchAll();
?>

<div class="pm-admin-header">
    <div>
        <h1>Reviews, Product Q&amp;A &amp; Abuse Reports</h1>
        <p>Moderate verified buyer ratings, feature homepage testimonials, handle flagged reports, and govern Q&amp;A threads.</p>
    </div>
</div>

<div class="pm-admin-tabs">
    <a href="<?= e(url('admin/reviews.php?tab=reviews')) ?>" class="pm-chip <?= $tab === 'reviews' ? 'is-active' : '' ?>">Buyer Reviews (<?= count($reviews) ?>)</a>
    <a href="<?= e(url('admin/reviews.php?tab=reports')) ?>" class="pm-chip <?= $tab === 'reports' ? 'is-active' : '' ?>">Flagged Reports (<?= count($reports) ?>)</a>
    <a href="<?= e(url('admin/reviews.php?tab=questions')) ?>" class="pm-chip <?= $tab === 'questions' ? 'is-active' : '' ?>">Product Q&amp;A (<?= count($questions) ?>)</a>
</div>

<div class="pm-card pm-card-pad">
    <?php if ($tab === 'reports'): ?>
        <?php if (empty($reports)): ?>
            <p class="pm-muted" style="padding:20px 0;">Zero flagged reports — all clear.</p>
        <?php else: ?>
            <div class="pm-table-wrap">
                <table class="pm-table">
                    <thead><tr><th>Project &amp; Review</th><th>Reason</th><th>Reporter</th><th>Status</th><th style="text-align:right;">Action</th></tr></thead>
                    <tbody>
                        <?php foreach ($reports as $rp): ?>
                            <tr>
                                <td><strong><?= e($rp['project_title']) ?></strong><small class="pm-muted" style="display:block;"><?= e(mb_substr((string)$rp['comment'], 0, 90)) ?></small></td>
                                <td><?= e($rp['reason']) ?></td>
                                <td>@<?= e($rp['reporter']) ?></td>
                                <td><?= render_status_badge((string)$rp['status']) ?></td>
                                <td style="text-align:right;">
                                    <?php if (in_array((string)$rp['status'], ['pending', 'open'], true)): ?>
                                        <div class="pm-btn-row" style="justify-content:flex-end;">
                                            <form method="post" action="<?= e(url('actions/admin/moderation.php')) ?>">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="resolve_review_report">
                                                <input type="hidden" name="report_id" value="<?= (int)$rp['id'] ?>">
                                                <input type="hidden" name="status" value="reviewed">
                                                <input type="hidden" name="hide_review" value="1">
                                                <button type="submit" class="pm-btn pm-btn-primary pm-btn-sm">Hide Review &amp; Resolve</button>
                                            </form>
                                            <form method="post" action="<?= e(url('actions/admin/moderation.php')) ?>">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="resolve_review_report">
                                                <input type="hidden" name="report_id" value="<?= (int)$rp['id'] ?>">
                                                <input type="hidden" name="status" value="dismissed">
                                                <button type="submit" class="pm-btn pm-btn-ghost pm-btn-sm">Dismiss</button>
                                            </form>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    <?php elseif ($tab === 'questions'): ?>
        <?php if (empty($questions)): ?>
            <p class="pm-muted" style="padding:20px 0;">No product questions submitted yet.</p>
        <?php else: ?>
            <div class="pm-table-wrap">
                <table class="pm-table">
                    <thead><tr><th>Project</th><th>Question &amp; Answer</th><th>Asked By</th><th>Status</th><th style="text-align:right;">Action</th></tr></thead>
                    <tbody>
                        <?php foreach ($questions as $qu): ?>
                            <tr>
                                <td><strong><?= e($qu['project_title']) ?></strong></td>
                                <td>
                                    <p><strong>Q:</strong> <?= e($qu['question_text']) ?></p>
                                    <?php if (!empty($qu['answer_text'])): ?><small class="pm-muted"><strong>A:</strong> <?= e($qu['answer_text']) ?></small><?php endif; ?>
                                </td>
                                <td>@<?= e($qu['asker']) ?></td>
                                <td><?= render_status_badge((string)$qu['status']) ?></td>
                                <td style="text-align:right;">
                                    <form method="post" action="<?= e(url('actions/admin/moderation.php')) ?>" class="pm-btn-row" style="justify-content:flex-end;">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="question_status">
                                        <input type="hidden" name="question_id" value="<?= (int)$qu['id'] ?>">
                                        <button type="submit" name="status" value="<?= $qu['status'] === 'visible' ? 'hidden' : 'visible' ?>" class="pm-btn pm-btn-secondary pm-btn-sm"><?= $qu['status'] === 'visible' ? 'Hide' : 'Show' ?></button>
                                        <button type="submit" name="status" value="delete" class="pm-btn pm-btn-ghost pm-btn-sm" style="color:var(--danger);">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <?php if (empty($reviews)): ?>
            <p class="pm-muted" style="padding:20px 0;">No buyer reviews submitted yet.</p>
        <?php else: ?>
            <div class="pm-table-wrap">
                <table class="pm-table">
                    <thead><tr><th>Project &amp; Buyer</th><th>Rating</th><th>Review</th><th>Status</th><th style="text-align:right;">Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($reviews as $rv): ?>
                            <tr>
                                <td><strong><?= e($rv['project_title']) ?></strong><small class="pm-muted" style="display:block;">by <?= e($rv['buyer_name']) ?></small></td>
                                <td><?= render_stars((float)$rv['rating']) ?></td>
                                <td style="max-width:340px;font-size:0.84rem;">
                                    <?= e($rv['comment']) ?>
                                    <?php if ((int)$rv['is_featured_testimonial']): ?><span class="pm-badge pm-badge-primary" style="margin-left:4px;">Testimonial</span><?php endif; ?>
                                </td>
                                <td><?= render_status_badge((string)$rv['status']) ?></td>
                                <td style="text-align:right;">
                                    <form method="post" action="<?= e(url('actions/admin/moderation.php')) ?>" class="pm-btn-row" style="justify-content:flex-end;">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="review_status">
                                        <input type="hidden" name="review_id" value="<?= (int)$rv['id'] ?>">
                                        <button type="submit" name="status" value="toggle_testimonial" class="pm-btn pm-btn-ghost pm-btn-sm"><?= (int)$rv['is_featured_testimonial'] ? 'Unfeature' : 'Testimonial' ?></button>
                                        <button type="submit" name="status" value="<?= $rv['status'] === 'published' ? 'hidden' : 'published' ?>" class="pm-btn pm-btn-secondary pm-btn-sm"><?= $rv['status'] === 'published' ? 'Hide' : 'Publish' ?></button>
                                        <button type="submit" name="status" value="delete" class="pm-btn pm-btn-ghost pm-btn-sm" style="color:var(--danger);" onclick="return confirm('Delete review?');"><?= icon('trash', 14) ?></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require INCLUDES_PATH . '/layouts/admin-footer.php'; ?>
