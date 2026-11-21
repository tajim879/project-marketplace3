<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$pageTitle = 'Categories Management';
require INCLUDES_PATH . '/layouts/admin-header.php';

$pdo = db();
$categories = $pdo->query(
    "SELECT c.*, (SELECT COUNT(*) FROM `projects` p WHERE p.category_id = c.id) AS projects_count
     FROM `categories` c ORDER BY c.sort_order ASC, c.name ASC"
)->fetchAll();

$editId = (int)($_GET['edit'] ?? 0);
$editing = null;
foreach ($categories as $c) {
    if ((int)$c['id'] === $editId) {
        $editing = $c;
        break;
    }
}
?>

<div class="pm-admin-header">
    <div>
        <h1>Product Categories</h1>
        <p>Create, edit, reorder, and govern all digital product categories across the marketplace.</p>
    </div>
</div>

<div class="pm-admin-split">
    <div class="pm-card pm-card-pad">
        <div class="pm-table-wrap">
            <table class="pm-table">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Category</th>
                        <th>Slug</th>
                        <th>Projects</th>
                        <th>Status</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $c): ?>
                        <tr>
                            <td class="pm-tabular"><?= (int)$c['sort_order'] ?></td>
                            <td>
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <span class="pm-stat-icon pm-cat-mini" style="width:38px;height:38px;"><?= category_icon_html($c, 16) ?></span>
                                    <div>
                                        <strong><?= e($c['name']) ?></strong>
                                        <?php if ((int)$c['is_popular']): ?><small class="pm-muted" style="display:block;">Homepage Popular</small><?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td><code><?= e($c['slug']) ?></code></td>
                            <td class="pm-tabular"><?= (int)$c['projects_count'] ?></td>
                            <td><?= render_status_badge((string)$c['status']) ?></td>
                            <td style="text-align:right;">
                                <div class="pm-btn-row" style="justify-content:flex-end;">
                                    <a href="<?= e(url('admin/categories.php?edit=' . (int)$c['id'])) ?>" class="pm-btn pm-btn-secondary pm-btn-sm">Edit</a>
                                    <form method="post" action="<?= e(url('actions/admin/categories.php')) ?>">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                                        <button type="submit" class="pm-btn pm-btn-ghost pm-btn-sm"><?= $c['status'] === 'active' ? 'Disable' : 'Enable' ?></button>
                                    </form>
                                    <?php if ((int)$c['projects_count'] === 0): ?>
                                        <form method="post" action="<?= e(url('actions/admin/categories.php')) ?>">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                                            <button type="submit" class="pm-btn pm-btn-ghost pm-btn-sm" style="color:var(--danger);" onclick="return confirm('Delete empty category?');"><?= icon('trash', 14) ?></button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <form method="post" action="<?= e(url('actions/admin/categories.php')) ?>" enctype="multipart/form-data" class="pm-card pm-card-pad" style="display:grid;gap:14px;">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= (int)($editing['id'] ?? 0) ?>">
        <h2 class="pm-h3"><?= $editing ? 'Edit Category: ' . e($editing['name']) : 'Add New Category' ?></h2>

        <div class="pm-field">
            <label class="pm-label">Category Name</label>
            <input type="text" name="name" class="pm-input" value="<?= e((string)($editing['name'] ?? '')) ?>" placeholder="e.g. Flutter Apps" required>
        </div>
        <div class="pm-field">
            <label class="pm-label">URL Slug (auto if blank)</label>
            <input type="text" name="slug" class="pm-input" value="<?= e((string)($editing['slug'] ?? '')) ?>" placeholder="flutter-apps">
        </div>
        <div class="pm-field">
            <label class="pm-label">Short Tagline</label>
            <input type="text" name="short_description" class="pm-input" value="<?= e((string)($editing['short_description'] ?? '')) ?>" placeholder="Concise card subtitle">
        </div>
        <div class="pm-field">
            <label class="pm-label">Full Description</label>
            <textarea name="description" class="pm-textarea" rows="3"><?= e((string)($editing['description'] ?? '')) ?></textarea>
        </div>
        <?php
            $curType = (string)($editing['icon_type'] ?? 'icon');
            $curImg = (string)($editing['icon_image'] ?? '');
            $curImgIsUrl = (bool)preg_match('#^https?://#i', $curImg);
        ?>
        <fieldset class="pm-field pm-cat-media" style="border:1px solid var(--border);border-radius:14px;padding:14px;display:grid;gap:12px;margin:0;">
            <legend class="pm-label" style="padding:0 6px;">Category Icon / Image</legend>
            <div style="display:flex;align-items:center;gap:12px;">
                <span class="pm-cat-icon" id="catPreview" style="width:56px;height:56px;"><?= $editing ? category_icon_html($editing, 24) : icon('code', 24) ?></span>
                <select name="icon_type" id="catIconType" class="pm-select">
                    <option value="icon" <?= $curType === 'icon' ? 'selected' : '' ?>>Built-in icon (key)</option>
                    <option value="image" <?= $curType === 'image' ? 'selected' : '' ?>>Upload image (JPG / PNG / WebP)</option>
                    <option value="url" <?= $curType === 'url' ? 'selected' : '' ?>>Image URL</option>
                    <option value="svg" <?= $curType === 'svg' ? 'selected' : '' ?>>SVG code</option>
                </select>
            </div>
            <div data-cat-pane="icon" class="pm-field">
                <label class="pm-label">Icon Key</label>
                <input type="text" name="icon_name" class="pm-input" value="<?= e((string)($editing['icon_name'] ?? 'code')) ?>" placeholder="code, layout, zap, globe...">
            </div>
            <div data-cat-pane="image" class="pm-field">
                <label class="pm-label">Upload Image (max 3 MB)</label>
                <input type="file" name="icon_file" class="pm-input" accept="image/jpeg,image/png,image/webp">
                <?php if ($curType === 'image' && $curImg !== ''): ?><small class="pm-muted">Current image will be kept unless you choose a new file.</small><?php endif; ?>
            </div>
            <div data-cat-pane="url" class="pm-field">
                <label class="pm-label">Image URL</label>
                <input type="url" name="icon_url" class="pm-input" value="<?= e($curType === 'url' && $curImgIsUrl ? $curImg : '') ?>" placeholder="https://example.com/icon.png">
            </div>
            <div data-cat-pane="svg" class="pm-field">
                <label class="pm-label">SVG Code</label>
                <textarea name="icon_svg" class="pm-textarea" rows="5" placeholder="&lt;svg viewBox=&quot;0 0 24 24&quot;&gt;...&lt;/svg&gt;"><?= e((string)($editing['icon_svg'] ?? '')) ?></textarea>
                <small class="pm-muted">Scripts and event handlers are removed automatically.</small>
            </div>
        </fieldset>
        <div class="pm-grid-3">
            <div class="pm-field">
                <label class="pm-label">Sort Order</label>
                <input type="number" name="sort_order" class="pm-input" value="<?= (int)($editing['sort_order'] ?? (count($categories) + 1)) ?>">
            </div>
            <div class="pm-field">
                <label class="pm-label">Status</label>
                <select name="status" class="pm-select">
                    <option value="active" <?= ($editing['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= ($editing['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
        </div>
        <label style="display:inline-flex;align-items:center;gap:8px;font-size:0.84rem;">
            <input type="checkbox" name="is_popular" value="1" <?= (int)($editing['is_popular'] ?? 1) === 1 ? 'checked' : '' ?>>
            <span>Highlight in Popular Categories on Homepage</span>
        </label>
        <div class="pm-btn-row">
            <button type="submit" class="pm-btn pm-btn-primary"><?= $editing ? 'Update Category' : 'Create Category' ?></button>
            <?php if ($editing): ?><a href="<?= e(url('admin/categories.php')) ?>" class="pm-btn pm-btn-ghost">Cancel</a><?php endif; ?>
        </div>
    </form>
</div>

<script>
(function () {
    var sel = document.getElementById('catIconType');
    if (!sel) return;
    var panes = document.querySelectorAll('[data-cat-pane]');
    function sync() {
        panes.forEach(function (p) { p.style.display = p.getAttribute('data-cat-pane') === sel.value ? '' : 'none'; });
    }
    sel.addEventListener('change', sync);
    sync();

    var prev = document.getElementById('catPreview');
    var form = sel.form;
    function show(html) { if (prev) prev.innerHTML = html; }
    function esc(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
    form.querySelector('[name=icon_file]').addEventListener('change', function (e) {
        var f = e.target.files[0];
        if (f) show('<img class="pm-cat-img" src="' + URL.createObjectURL(f) + '" alt="">');
    });
    form.querySelector('[name=icon_url]').addEventListener('input', function (e) {
        var v = e.target.value.trim();
        if (/^https?:\/\//i.test(v)) show('<img class="pm-cat-img" src="' + esc(v) + '" alt="">');
    });
    form.querySelector('[name=icon_svg]').addEventListener('input', function (e) {
        var v = e.target.value.trim();
        if (/^<svg[\s>]/i.test(v)) {
            var doc = new DOMParser().parseFromString(v, 'image/svg+xml');
            var root = doc.documentElement;
            if (root && root.nodeName.toLowerCase() === 'svg' && !doc.querySelector('parsererror')) {
                root.querySelectorAll('script,foreignObject').forEach(function (n) { n.remove(); });
                [].slice.call(root.querySelectorAll('*')).concat(root).forEach(function (n) {
                    [].slice.call(n.attributes).forEach(function (a) { if (/^on/i.test(a.name)) n.removeAttribute(a.name); });
                });
                show('<span class="pm-cat-svg">' + root.outerHTML + '</span>');
            }
        }
    });
})();
</script>

<?php require INCLUDES_PATH . '/layouts/admin-footer.php'; ?>
