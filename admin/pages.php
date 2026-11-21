<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$pageTitle = 'CMS Pages, FAQs & Newsletter';
require INCLUDES_PATH . '/layouts/admin-header.php';

$pdo = db();
$tab = trim((string)($_GET['tab'] ?? 'pages'));
$editId = (int)($_GET['edit'] ?? 0);

$pages = $pdo->query("SELECT * FROM `pages` ORDER BY `id` ASC")->fetchAll();
$faqs = $pdo->query("SELECT * FROM `faqs` ORDER BY `sort_order` ASC, `id` ASC")->fetchAll();
$subs = $pdo->query("SELECT * FROM `newsletter_subscribers` ORDER BY `id` DESC LIMIT 200")->fetchAll();

$editingPage = null;
if ($tab === 'pages' && $editId > 0) {
    foreach ($pages as $pg) {
        if ((int)$pg['id'] === $editId) {
            $editingPage = $pg;
        }
    }
}
$editingFaq = null;
if ($tab === 'faqs' && $editId > 0) {
    foreach ($faqs as $fq) {
        if ((int)$fq['id'] === $editId) {
            $editingFaq = $fq;
        }
    }
}
?>

<div class="pm-admin-header">
    <div>
        <h1>CMS Pages, FAQ Knowledgebase &amp; Newsletter</h1>
        <p>Edit About, Terms, Privacy, Refund &amp; License policies, manage FAQs, and export newsletter subscribers.</p>
    </div>
</div>

<div class="pm-admin-tabs">
    <a href="<?= e(url('admin/pages.php?tab=pages')) ?>" class="pm-chip <?= $tab === 'pages' ? 'is-active' : '' ?>">Static Legal &amp; Info Pages (<?= count($pages) ?>)</a>
    <a href="<?= e(url('admin/pages.php?tab=faqs')) ?>" class="pm-chip <?= $tab === 'faqs' ? 'is-active' : '' ?>">FAQs (<?= count($faqs) ?>)</a>
    <a href="<?= e(url('admin/pages.php?tab=newsletter')) ?>" class="pm-chip <?= $tab === 'newsletter' ? 'is-active' : '' ?>">Newsletter Subscribers (<?= count($subs) ?>)</a>
</div>

<?php if ($tab === 'faqs'): ?>
    <div class="pm-admin-split">
        <div class="pm-card pm-card-pad">
            <div class="pm-table-wrap">
                <table class="pm-table">
                    <thead><tr><th>#</th><th>Question</th><th>Category</th><th>Status</th><th style="text-align:right;">Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($faqs as $fq): ?>
                            <tr>
                                <td class="pm-tabular"><?= (int)$fq['sort_order'] ?></td>
                                <td><strong><?= e($fq['question']) ?></strong></td>
                                <td><span class="pm-badge"><?= e($fq['category']) ?></span></td>
                                <td><?= render_status_badge((string)$fq['status']) ?></td>
                                <td style="text-align:right;">
                                    <div class="pm-btn-row" style="justify-content:flex-end;">
                                        <a href="<?= e(url('admin/pages.php?tab=faqs&edit=' . (int)$fq['id'])) ?>" class="pm-btn pm-btn-secondary pm-btn-sm">Edit</a>
                                        <form method="post" action="<?= e(url('actions/admin/cms.php')) ?>">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="faq_delete">
                                            <input type="hidden" name="id" value="<?= (int)$fq['id'] ?>">
                                            <button type="submit" class="pm-btn pm-btn-ghost pm-btn-sm" style="color:var(--danger);" onclick="return confirm('Delete FAQ?');"><?= icon('trash', 14) ?></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <form method="post" action="<?= e(url('actions/admin/cms.php')) ?>" class="pm-card pm-card-pad" style="display:grid;gap:12px;">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="faq_save">
            <input type="hidden" name="id" value="<?= (int)($editingFaq['id'] ?? 0) ?>">
            <h2 class="pm-h3"><?= $editingFaq ? 'Edit FAQ' : 'Add New FAQ' ?></h2>
            <div class="pm-form-grid-2">
                <div class="pm-field"><label class="pm-label">Category</label><input type="text" name="category" class="pm-input" value="<?= e((string)($editingFaq['category'] ?? 'General')) ?>"></div>
                <div class="pm-field"><label class="pm-label">Sort Order</label><input type="number" name="sort_order" class="pm-input" value="<?= (int)($editingFaq['sort_order'] ?? (count($faqs) + 1)) ?>"></div>
            </div>
            <div class="pm-field"><label class="pm-label">Question</label><input type="text" name="question" class="pm-input" value="<?= e((string)($editingFaq['question'] ?? '')) ?>" required></div>
            <div class="pm-field"><label class="pm-label">Answer</label><textarea name="answer" class="pm-textarea" rows="4" required><?= e((string)($editingFaq['answer'] ?? '')) ?></textarea></div>
            <div class="pm-form-grid-2">
                <select name="status" class="pm-select">
                    <option value="active" <?= ($editingFaq['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= ($editingFaq['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
                <label style="display:inline-flex;align-items:center;gap:8px;font-size:0.84rem;">
                    <input type="checkbox" name="show_on_homepage" value="1" <?= (int)($editingFaq['show_on_homepage'] ?? 1) ? 'checked' : '' ?>> Show on Homepage
                </label>
            </div>
            <div class="pm-btn-row">
                <button type="submit" class="pm-btn pm-btn-primary"><?= $editingFaq ? 'Update FAQ' : 'Add FAQ' ?></button>
                <?php if ($editingFaq): ?><a href="<?= e(url('admin/pages.php?tab=faqs')) ?>" class="pm-btn pm-btn-ghost">Cancel</a><?php endif; ?>
            </div>
        </form>
    </div>

<?php elseif ($tab === 'newsletter'): ?>
    <div class="pm-card pm-card-pad" style="display:grid;gap:14px;">
        <div class="pm-admin-toolbar">
            <h2 class="pm-h3">Newsletter Subscriber List</h2>
            <form method="post" action="<?= e(url('actions/admin/cms.php')) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="newsletter_export">
                <button type="submit" class="pm-btn pm-btn-primary pm-btn-sm"><?= icon('download', 15) ?><span>Export CSV</span></button>
            </form>
        </div>
        <?php if (empty($subs)): ?>
            <p class="pm-muted" style="padding:18px 0;">No newsletter subscribers yet.</p>
        <?php else: ?>
            <div class="pm-table-wrap">
                <table class="pm-table">
                    <thead><tr><th>Email</th><th>Status</th><th>Subscribed At</th><th></th></tr></thead>
                    <tbody>
                        <?php foreach ($subs as $sb): ?>
                            <tr>
                                <td><strong><?= e($sb['email']) ?></strong></td>
                                <td><?= render_status_badge((string)$sb['status']) ?></td>
                                <td><?= e(format_datetime((string)$sb['subscribed_at'])) ?></td>
                                <td style="text-align:right;">
                                    <form method="post" action="<?= e(url('actions/admin/cms.php')) ?>">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="newsletter_delete">
                                        <input type="hidden" name="id" value="<?= (int)$sb['id'] ?>">
                                        <button type="submit" class="pm-btn pm-btn-ghost pm-btn-sm" style="color:var(--danger);">Remove</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

<?php else: ?>
    <div class="pm-admin-split">
        <div class="pm-card pm-card-pad">
            <div class="pm-table-wrap">
                <table class="pm-table">
                    <thead><tr><th>Title</th><th>Slug</th><th>Status</th><th>Updated</th><th style="text-align:right;">Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($pages as $pg): ?>
                            <tr>
                                <td><strong><?= e($pg['title']) ?></strong></td>
                                <td><code><?= e($pg['slug']) ?></code></td>
                                <td><?= render_status_badge((string)$pg['status']) ?></td>
                                <td><?= e(format_date((string)$pg['updated_at'])) ?></td>
                                <td style="text-align:right;">
                                    <div class="pm-btn-row" style="justify-content:flex-end;">
                                        <a href="<?= e(url('admin/pages.php?tab=pages&edit=' . (int)$pg['id'])) ?>" class="pm-btn pm-btn-secondary pm-btn-sm">Edit</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <form method="post" action="<?= e(url('actions/admin/cms.php')) ?>" class="pm-card pm-card-pad" style="display:grid;gap:12px;">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="page_save">
            <input type="hidden" name="id" value="<?= (int)($editingPage['id'] ?? 0) ?>">
            <h2 class="pm-h3"><?= $editingPage ? 'Edit Page: ' . e($editingPage['title']) : 'Create New CMS Page' ?></h2>
            <div class="pm-form-grid-2">
                <div class="pm-field"><label class="pm-label">Page Title</label><input type="text" name="title" class="pm-input" value="<?= e((string)($editingPage['title'] ?? '')) ?>" required></div>
                <div class="pm-field"><label class="pm-label">Slug</label><input type="text" name="slug" class="pm-input" value="<?= e((string)($editingPage['slug'] ?? '')) ?>" placeholder="about-us" required></div>
            </div>
            <div class="pm-field"><label class="pm-label">Subtitle</label><input type="text" name="subtitle" class="pm-input" value="<?= e((string)($editingPage['subtitle'] ?? '')) ?>"></div>
            <div class="pm-field"><label class="pm-label">Page Content (HTML/Paragraphs supported)</label><textarea name="content" class="pm-textarea" rows="8" required><?= e((string)($editingPage['content'] ?? '')) ?></textarea></div>
            <div class="pm-form-grid-2">
                <div class="pm-field"><label class="pm-label">Meta Title</label><input type="text" name="meta_title" class="pm-input" value="<?= e((string)($editingPage['meta_title'] ?? '')) ?>"></div>
                <div class="pm-field">
                    <label class="pm-label">Status</label>
                    <select name="status" class="pm-select">
                        <option value="published" <?= ($editingPage['status'] ?? 'published') === 'published' ? 'selected' : '' ?>>Published</option>
                        <option value="draft" <?= ($editingPage['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option>
                    </select>
                </div>
            </div>
            <div class="pm-btn-row">
                <button type="submit" class="pm-btn pm-btn-primary"><?= $editingPage ? 'Save Page Changes' : 'Publish CMS Page' ?></button>
                <?php if ($editingPage): ?><a href="<?= e(url('admin/pages.php?tab=pages')) ?>" class="pm-btn pm-btn-ghost">Cancel</a><?php endif; ?>
            </div>
        </form>
    </div>
<?php endif; ?>

<?php require INCLUDES_PATH . '/layouts/admin-footer.php'; ?>
