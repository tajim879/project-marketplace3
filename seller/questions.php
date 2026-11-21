<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$user = require_seller();
$uid = (int)$user['id'];

$stmt = db()->prepare(
    "SELECT pq.*, p.title AS project_title, p.slug AS project_slug, u.full_name AS asker_name
     FROM `project_questions` pq
     INNER JOIN `projects` p ON p.id = pq.project_id
     INNER JOIN `users` u ON u.id = pq.user_id
     WHERE p.seller_id = ? AND pq.status = 'visible'
     ORDER BY (pq.answer_text IS NULL) DESC, pq.created_at DESC"
);
$stmt->execute([$uid]);
$questions = $stmt->fetchAll();

$pageTitle = 'Product Q&A — Seller Studio';
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
                    <h1 class="pm-h2">Pre-Sale &amp; Technical Q&amp;A</h1>
                    <p class="pm-muted">Fast answers increase buyer confidence and conversion rate.</p>
                </div>

                <?php if (empty($questions)): ?>
                    <div class="pm-card pm-empty">
                        <div class="pm-icon-orb"><?= icon('help-circle', 26) ?></div>
                        <h2 class="pm-h3">No questions yet</h2>
                        <p class="pm-muted">Questions asked on your product pages will appear here.</p>
                    </div>
                <?php else: ?>
                    <div style="display:grid;gap:14px;">
                        <?php foreach ($questions as $q): ?>
                            <article class="pm-card pm-card-pad" style="display:grid;gap:12px;">
                                <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;">
                                    <div>
                                        <strong><?= e($q['asker_name']) ?></strong>
                                        <span class="pm-muted" style="font-size:0.82rem;"> asked on <a href="<?= e(url('project.php?slug=' . urlencode((string)$q['project_slug']))) ?>" class="pm-link"><?= e($q['project_title']) ?></a></span>
                                    </div>
                                    <?= empty($q['answer_text']) ? '<span class="pm-badge pm-badge-warning">Needs Answer</span>' : '<span class="pm-badge pm-badge-success">Answered</span>' ?>
                                </div>
                                <p style="font-weight:600;">Q: <?= e($q['question_text']) ?></p>

                                <form method="post" action="<?= e(url('actions/seller/engage.php')) ?>" style="display:grid;gap:8px;">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="answer_question">
                                    <input type="hidden" name="question_id" value="<?= (int)$q['id'] ?>">
                                    <textarea name="answer" class="pm-textarea" rows="2" placeholder="Write a clear technical answer..." required><?= e((string)($q['answer_text'] ?? '')) ?></textarea>
                                    <div><button type="submit" class="pm-btn pm-btn-primary pm-btn-sm"><?= !empty($q['answer_text']) ? 'Update Answer' : 'Publish Answer' ?></button></div>
                                </form>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php require INCLUDES_PATH . '/layouts/footer.php'; ?>
